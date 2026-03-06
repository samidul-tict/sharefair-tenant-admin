<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CaseUserMapping;
use App\Models\Menu;
use App\Models\MenuPermission;
use App\Models\User;
use App\Models\UserRoleMapping;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Plan; // subscription model
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of the tenant with name and tenant.
     */
    public function index(Request $request)
    {
        $search     = $request->input('search');
        $roleFilter = $request->input('role_id');
        $sortField  = $request->input('sort', 'users.name');
        $sortOrder  = $request->input('order', 'asc');

        // 🔹 Validate sortable fields
        $allowedSorts = ['users.name', 'users.email', 'roles.name', 'users.created_date'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'users.name';
        }

        $logUser = User::leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
            ->where('users.id', Auth::id())
            ->select('users.*', 'urm.role_value as user_role_id', 'urm.tenant_id')
            ->first();
        
        // 🔹 Query users who have role_id = 7 (Tenant Employees)
        $users = User::select(
                'users.id',
                'users.name',
                'users.email',
                'users.phone_number',
                'roles.name as role_name',
                'users.is_active',
                'users.created_date',
                'creator.name as created_by_name'
            )
            ->join('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
            ->join('data_element as roles', 'urm.role_value', '=', 'roles.value')
            ->leftJoin('users as creator', 'users.created_by', '=', 'creator.id')
            ->where('urm.role_value', 'EMP')   // <-- Added filter
            ->where('urm.tenant_id', $logUser->tenant_id)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('users.name', 'ILIKE', "%{$search}%")
                      ->orWhere('users.email', 'ILIKE', "%{$search}%")
                      ->orWhere('users.phone_number', 'ILIKE', "%{$search}%");
                });
            })
            ->when($roleFilter, function ($query, $roleFilter) {
                $query->where('roles.value', $roleFilter);
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate(10);

        $roles = DB::table('data_element')
            ->whereIn('category_id', [1, 2])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('backend.users.index', compact('users', 'roles', 'search', 'roleFilter', 'sortField', 'sortOrder'));
    }

    public function create()
    {
        $menus = Menu::where('is_active', true)->where('admin_type', 'TA')->orderBy('sort_order', 'ASC')->get(); // adjust if needed
        return view('backend.users.create', compact('menus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:256',
            'email'        => 'required|email', // Removed unique check to allow adding existing users to tenant
            'phone_number' => 'nullable',
        ]);
        
        try {
            DB::beginTransaction();

            // Logged-in user with role + tenant data
            $loggedUser = User::leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
                    ->where('users.id', Auth::id())
                    ->select('users.*', 'urm.role_value as user_role_id', 'urm.tenant_id')
                    ->first();

            
            $existingUser = User::where('email', $request->email)->first();
            if($existingUser){
                // Insert Role Mapping
                UserRoleMapping::create([
                    'user_id'           => $existingUser->id,
                    'role_value'        => 'EMP', // Employee role
                    'tenant_id'         => $loggedUser->tenant_id,
                    'is_active'         => true,
                    'created_by'        => Auth::id(),
                    'created_date'      => now(),
                    'modified_by'       => Auth::id(),
                    'last_modified_date'=> now()
                ]);

                // Override previous menu permissions (re-adding employee: delete then insert)
                MenuPermission::where('user_id', $existingUser->id)->delete();

                // ------------ MENU PERMISSION INSERT ------------------
                if ($request->has('permissions')) {
                    foreach ($request->permissions as $menuId => $permissions) {

                        MenuPermission::create([
                            'user_id' => $existingUser->id,
                            'menu_id' => $menuId,
                            'can_view'    => isset($permissions['view']) ? true : false,
                            'can_create'  => isset($permissions['create']) ? true : false,
                            'can_edit'    => isset($permissions['edit']) ? true : false,
                            'can_delete'  => isset($permissions['delete']) ? true : false,
                            'can_upload'  => isset($permissions['upload']) ? true : false,
                            'is_active'   => true,
                            'created_by'  => Auth::id(),
                            'created_date'=> now(),
                            'modified_by' => Auth::id(),
                            'last_modified_date'=> now()
                        ]);
                    }
                }
            }else{
                // Create new user
                $newUser = User::create([
                    'email'             => $request->email,
                    'name'              => $request->name,
                    'phone_number'      => $request->phone,
                    'password'          => md5('12345'),
                    'preferred_language'=> 'en',
                    'is_active'         => true,
                    'created_by'        => Auth::id(),
                    'created_date'      => now(),
                    'modified_by'       => Auth::id(),
                    'last_modified_date'=> now(),
                ]);
            
                // Insert Role Mapping
                UserRoleMapping::create([
                    'user_id'           => $newUser->id,
                    'role_value'        => 'EMP', // Employee role
                    'tenant_id'         => $loggedUser->tenant_id,
                    'is_active'         => true,
                    'created_by'        => Auth::id(),
                    'created_date'      => now(),
                    'modified_by'       => Auth::id(),
                    'last_modified_date'=> now()
                ]);

            // ------------ MENU PERMISSION INSERT ------------------
                if ($request->has('permissions')) {
                    foreach ($request->permissions as $menuId => $permissions) {
                        
                        MenuPermission::create([
                            'user_id' => $newUser->id,
                            'menu_id' => $menuId,
                            'can_view'    => isset($permissions['view']) ? true : false,
                            'can_create'  => isset($permissions['create']) ? true : false,
                            'can_edit'    => isset($permissions['edit']) ? true : false,
                            'can_delete'  => isset($permissions['delete']) ? true : false,
                            'can_upload'  => isset($permissions['upload']) ? true : false,
                            'is_active'   => true,
                            'created_by'  => Auth::id(),
                            'created_date'=> now(),
                            'modified_by' => Auth::id(),
                            'last_modified_date'=> now()
                        ]);
                    }
                }
            }

            DB::commit(); // Commit transaction

            return redirect()->route('admin.users.index')->with('success', 'Employee added successfully.');

        } catch (\Throwable $e) {

            DB::rollBack(); // rollback all changes
            dd($e->getMessage());
            
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);

        // All menus for admin type TA
        $menus = Menu::where('is_active', true)
                    ->where('admin_type', 'TA')
                    ->orderBy('sort_order', 'ASC')
                    ->get();

        // existing permissions
        $existingPermissions = MenuPermission::where('user_id', $id)
                                ->pluck('can_view', 'menu_id')
                                ->toArray();

        $existingPermissionsFull = MenuPermission::where('user_id', $id)
                                ->get()
                                ->keyBy('menu_id');

        return view('backend.users.edit', compact('user', 'menus', 'existingPermissionsFull'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'  => 'required|string|max:256',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:50',
        ]);

        try {
            DB::beginTransaction();

            $user = User::findOrFail($id);

            // Update user
            $user->update([
                'email'             => $request->email,
                'name'              => $request->name,
                'phone_number'      => $request->phone,
                'modified_by'       => Auth::id(),
                'last_modified_date'=> now(),
            ]);

            // Step 1: Remove previous permissions
            MenuPermission::where('user_id', $id)->delete();

            // Step 2: Insert new permissions
            if ($request->has('permissions')) {
                foreach ($request->permissions as $menuId => $permissions) {
                    MenuPermission::create([
                        'user_id'   => $id,
                        'menu_id'   => $menuId,
                        'can_view'    => isset($permissions['view']) ? true : false,
                        'can_create'  => isset($permissions['create']) ? true : false,
                        'can_edit'    => isset($permissions['edit']) ? true : false,
                        'can_delete'  => isset($permissions['delete']) ? true : false,
                        'can_upload'  => isset($permissions['upload']) ? true : false,
                        'is_active'     => true,
                        'created_by'    => Auth::id(),
                        'created_date'  => now(),
                        'modified_by'   => Auth::id(),
                        'last_modified_date'=> now()
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            dd($e->getMessage());
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }
    public function show($id)
    {
        $user = User::with([
            'userRoleMappings.role',
            'userRoleMappings.tenant',
            'createdByUser',
        ])->findOrFail($id);

        $tenantIds = $user->userRoleMappings->pluck('tenant_id')->unique();

        $subscriptions = Subscription::with(['plan', 'coupon'])
            ->whereIn('tenant_id', $tenantIds)
            ->orderBy('id', 'desc')
            ->get();

        return view('backend.users.show', compact('user', 'subscriptions'));
    }

    /**
     * Show the form to reset the current user's password (self-service only).
     */
    public function resetPasswordForm($id)
    {
        if ((int) $id !== (int) Auth::id()) {
            abort(403, 'You can only reset your own password.');
        }
        $user = User::findOrFail($id);
        return view('backend.users.reset-password', compact('user'));
    }

    /**
     * Reset the current user's password (self-service only).
     */
    public function resetPassword(Request $request, $id)
    {
        if ((int) $id !== (int) Auth::id()) {
            abort(403, 'You can only reset your own password.');
        }
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.required'  => 'Password is required.',
            'password.min'       => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $user = User::findOrFail($id);
        $user->update([
            'password'          => md5($request->password),
            'modified_by'       => Auth::id(),
            'last_modified_date' => now(),
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Your password has been updated successfully.');
    }

    /**
     * Remove the specified user (employee).
     * Does not allow deletion if user is mapped to a case as Legal Representative, Plaintiff, or Defendant.
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            $caseMappings = CaseUserMapping::where('user_id', $user->id)
                ->whereIn('role_value', ['LEGAL_RE', 'PL', 'DEF'])
                ->with('courtCase:id,case_number')
                ->get();

            if ($caseMappings->isNotEmpty()) {
                $roles = $caseMappings->map(fn ($m) => match ($m->role_value) {
                    'LEGAL_RE' => 'Legal Representative',
                    'PL' => 'Plaintiff',
                    'DEF' => 'Defendant',
                    default => $m->role_value,
                })->unique()->implode(', ');
                $caseNumbers = $caseMappings->pluck('courtCase.case_number')->filter()->unique()->implode(', ');
                $message = $user->name . ' cannot be deleted because they are assigned to one or more cases as ' . $roles . '.';
                if ($caseNumbers) {
                    $message .= ' Case(s): ' . $caseNumbers . '. Remove them from these cases first if you need to delete this employee.';
                } else {
                    $message .= ' Remove them from the case(s) first if you need to delete this employee.';
                }
                return back()->with('error', $message);
            }

            MenuPermission::where('user_id', $user->id)->delete();
            UserRoleMapping::where('user_id', $user->id)->delete();
            $user->delete();
            return redirect()->route('admin.users.index')->with('success', 'Employee deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Search users (whole user table) by name or email for typeahead/assign to case.
     */
    public function search(Request $request)
    {
        try {
            $q = $request->input('q', '');
            $q = is_string($q) ? trim($q) : '';
            if (strlen($q) < 2) {
                return response()->json([])->header('Content-Type', 'application/json');
            }
            $term = '%' . $q . '%';
            $users = User::select('id', 'name', 'email', 'phone_number')
                ->where(function ($query) use ($term) {
                    $query->where('name', 'LIKE', $term)
                        ->orWhere('email', 'LIKE', $term);
                })
                ->orderBy('name')
                ->limit(25)
                ->get();
            return response()->json($users)->header('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            \Log::warning('User search failed: ' . $e->getMessage());
            return response()->json([])->header('Content-Type', 'application/json');
        }
    }

}
