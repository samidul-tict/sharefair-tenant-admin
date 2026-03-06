<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Show the current user's profile (name, email, phone, role).
     */
    public function show()
    {
        $user = User::findOrFail(Auth::id());

        $logUser = User::leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
            ->where('users.id', Auth::id())
            ->select('users.*', 'urm.role_value as user_role_id', 'urm.tenant_id')
            ->first();

        $roleName = null;
        if ($logUser && $logUser->user_role_id) {
            $roleRow = DB::table('data_element')
                ->where('value', $logUser->user_role_id)
                ->where('is_active', true)
                ->first();
            $roleName = $roleRow->name ?? $logUser->user_role_id;
        }

        return view('backend.profile.show', [
            'user'     => $user,
            'roleName' => $roleName,
        ]);
    }

    /**
     * Update the current user's profile (name, phone). Email is read-only.
     */
    public function update(Request $request)
    {
        $user = User::findOrFail(Auth::id());

        $request->validate([
            'name'         => 'required|string|max:256',
            'phone_number' => 'nullable|string|max:50',
        ], [
            'name.required' => 'Name is required.',
        ]);

        $user->update([
            'name'                  => $request->name,
            'phone_number'          => $request->phone_number ?? $user->phone_number,
            'modified_by'            => Auth::id(),
            'last_modified_date'     => now(),
        ]);

        return redirect()->route('admin.profile')->with('success', 'Profile updated successfully.');
    }
}
