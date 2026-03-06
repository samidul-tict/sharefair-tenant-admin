<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;

if (!function_exists('hasPermission')) {
    function hasPermission($menuKey, $action)
    {
        // Logged-in user with role + tenant data
        $user = User::leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
            ->where('users.id', Auth::id())
            ->select('users.*', 'urm.role_value as user_role_id', 'urm.tenant_id')
            ->first();

        if (!$user) {
            return false;
        }

        $roleId = $user->user_role_id;

        $permission = DB::table('menu_permission')
            ->join('menu', 'menu.id', '=', 'menu_permission.menu_id')
            ->where('menu.menu_key', $menuKey)
            ->where('menu_permission.user_id', Auth::id())
            ->first();

        if (!$permission) {
            return false;
        }

        return match ($action) {
            'create' => $permission->can_create == true,
            'view'   => $permission->can_view == true,
            'edit'   => $permission->can_edit == true,
            'delete' => $permission->can_delete == true,
            'upload' => $permission->can_upload == true,
            default  => false
        };
    }
}
