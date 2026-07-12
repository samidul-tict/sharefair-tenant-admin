<?php

namespace App\Support;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminContext
{
    private static ?User $logUser = null;

    private static bool $logUserLoaded = false;

    /** @var array<string, array<string, bool>>|null */
    private static ?array $permissions = null;

    private static ?Collection $menus = null;

    public static function logUser(): ?User
    {
        if (self::$logUserLoaded) {
            return self::$logUser;
        }

        self::$logUserLoaded = true;

        if (!Auth::check()) {
            return self::$logUser = null;
        }

        self::$logUser = User::query()
            ->leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
            ->where('users.id', Auth::id())
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.phone_number',
                'urm.role_value as user_role_id',
                'urm.tenant_id'
            )
            ->first();

        return self::$logUser;
    }

    public static function isTenantAdmin(): bool
    {
        return self::logUser()?->user_role_id === 'TENANT_A';
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public static function permissions(): array
    {
        if (self::$permissions !== null) {
            return self::$permissions;
        }

        if (!Auth::check()) {
            return self::$permissions = [];
        }

        self::$permissions = [];

        $rows = DB::table('menu_permission')
            ->join('menu', 'menu.id', '=', 'menu_permission.menu_id')
            ->where('menu_permission.user_id', Auth::id())
            ->select(
                'menu.menu_key',
                'menu_permission.can_create',
                'menu_permission.can_view',
                'menu_permission.can_edit',
                'menu_permission.can_delete',
                'menu_permission.can_upload'
            )
            ->get();

        foreach ($rows as $row) {
            self::$permissions[$row->menu_key] = [
                'create' => (bool) $row->can_create,
                'view' => (bool) $row->can_view,
                'edit' => (bool) $row->can_edit,
                'delete' => (bool) $row->can_delete,
                'upload' => (bool) $row->can_upload,
            ];
        }

        return self::$permissions;
    }

    public static function hasPermission(string $menuKey, string $action): bool
    {
        if (self::isTenantAdmin()) {
            return true;
        }

        return (bool) (self::permissions()[$menuKey][$action] ?? false);
    }

    public static function menus(): Collection
    {
        if (self::$menus !== null) {
            return self::$menus;
        }

        $logUser = self::logUser();
        if (!$logUser) {
            return self::$menus = collect();
        }

        if ($logUser->user_role_id === 'TENANT_A') {
            return self::$menus = Menu::query()
                ->select('menu.*')
                ->where('menu.is_active', true)
                ->where('menu.admin_type', 'TA')
                ->orderBy('menu.sort_order', 'ASC')
                ->get();
        }

        return self::$menus = Menu::query()
            ->select('menu.*', 'mp.can_view', 'mp.can_create', 'mp.can_edit', 'mp.can_delete')
            ->join('menu_permission as mp', 'menu.id', '=', 'mp.menu_id')
            ->where('mp.user_id', $logUser->id)
            ->where('menu.is_active', true)
            ->where('menu.admin_type', 'TA')
            ->where(function ($query) {
                $query->where('mp.can_view', true)
                    ->orWhere('mp.can_create', true)
                    ->orWhere('mp.can_edit', true)
                    ->orWhere('mp.can_delete', true);
            })
            ->orderBy('menu.sort_order', 'ASC')
            ->get();
    }
}
