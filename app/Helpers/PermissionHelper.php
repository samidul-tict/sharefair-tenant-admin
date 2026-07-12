<?php

use App\Support\AdminContext;

if (!function_exists('hasPermission')) {
    function hasPermission($menuKey, $action)
    {
        return AdminContext::hasPermission($menuKey, $action);
    }
}
