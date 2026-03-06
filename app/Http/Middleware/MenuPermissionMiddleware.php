<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Menu;
use App\Models\MenuPermission;
use App\Models\User;

class MenuPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $action)
    {
        // Logged-in user with role + tenant data
        $user = User::leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
                    ->where('users.id', Auth::id())
                    ->select('users.*', 'urm.role_value as user_role_id', 'urm.tenant_id')
                    ->first();


        if (!$user) {
            abort(403, 'Unauthenticated user.');
        }

        if ($user->user_role_id == 2) {
            return $next($request);
        }

        // Get current route name
        $routeName = $request->route()->getName();

        // Fetch menu based on route
        $menu = Menu::where('route_name', $routeName)->first();

        if (!$menu) {
            abort(403, 'Route not assigned to menu. Contact admin.');
        }

        // Check if user has permission
        $permission = MenuPermission::where('user_id', $user->id)
            ->where('menu_id', $menu->id)
            ->first();

        // Checking permission existence and action boolean
        $permissionColumn = 'can_' . $action;
        if (!$permission || empty($permission->$permissionColumn) || $permission->$permissionColumn !== true) {
            abort(403, 'Access denied: You are not allowed to ' . $action);
        }

        return $next($request);
    }
}
