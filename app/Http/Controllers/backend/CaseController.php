<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CaseActivity;
use App\Models\CaseUserMapping;
use App\Models\CourtCase;
use App\Models\Item;
use App\Models\Menu;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserRoleMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    /**
     * List all cases
     */
    public function index(Request $request)
    {
        $logUser = User::leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
            ->where('users.id', Auth::id())
            ->select('users.*', 'urm.role_value as user_role_id', 'urm.tenant_id')
            ->first();
        
        $search     = $request->input('search');
        $statusFilter = $request->input('status');
        $sortField  = $request->input('sort', 'case_number');
        $sortOrder  = $request->input('order', 'asc');

        $allowedSorts = ['case_number', 'case_type_value', 'case_status_value'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'case_number';
        }

        
        $cases = [];
        if ($logUser->user_role_id == 'EMP') {

            $cases = CourtCase::query()
                ->with('createdBy')
                ->where('is_active', true)
                ->whereIn('id', function ($query) use ($logUser) {
                    $query->select('case_id')
                        ->from('case_user_mapping')
                        ->where('user_id', $logUser->tenant_id);
                })
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('case_number', 'ILIKE', "%{$search}%")
                        ->orWhere('case_type_value', 'ILIKE', "%{$search}%")
                        ->orWhere('case_status_value', 'ILIKE', "%{$search}%");
                    });
                })
                ->when($statusFilter, function ($query, $status) {
                    $query->where('case_status_value', $status);
                })
                ->orderBy($sortField, $sortOrder)
                ->paginate(10);

        } else {

            $cases = CourtCase::query()
                ->with('createdBy')
                ->where('is_active', true)
                ->where('created_by', Auth::id())
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('case_number', 'ILIKE', "%{$search}%")
                        ->orWhere('case_type_value', 'ILIKE', "%{$search}%")
                        ->orWhere('case_status_value', 'ILIKE', "%{$search}%");
                    });
                })
                ->when($statusFilter, function ($query, $status) {
                    $query->where('case_status_value', $status);
                })
                ->orderBy($sortField, $sortOrder)
                ->paginate(10);
               
        }

        $caseTypes = DB::table('data_element')
            ->whereIn('category_id', [6])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $caseStatuses = DB::table('data_element')
            ->whereIn('value', CourtCase::query()->distinct()->pluck('case_status_value')->filter()->toArray())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($caseStatuses->isEmpty()) {
            $caseStatuses = DB::table('data_element')
                ->where('value', 'like', 'C_%')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        return view('backend.cases.index', compact('cases', 'search', 'sortField', 'sortOrder', 'logUser', 'caseTypes', 'caseStatuses'));
    }


    /**
     * Show create form
     */
    public function show($id)
    {
        // Allowed roles for user assignment or display
        $role = DB::table('data_element')
            ->whereIn('category_id', [2])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $caseTypes = DB::table('data_element')
            ->whereIn('category_id', [6])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // 1. Get the Case
        $case = CourtCase::with(['createdBy', 'modifiedBy', 'distributedBy'])->findOrFail($id);

        // 2. Get Case Users using JOINs (Case -> CaseUserMapping -> Users + Roles)
        $caseUsers = CaseUserMapping::select(
                'case_user_mapping.*',
                'users.name as user_name',
                'users.email as user_email',
                'users.phone_number as user_phone',
                'role.name as role_name'
            )
            ->join('users', 'case_user_mapping.user_id', '=', 'users.id')
            ->join('data_element as role', 'case_user_mapping.role_value', '=', 'role.value')
            ->where('case_user_mapping.case_id', $id)
            ->whereIn('case_user_mapping.role_value', ['SAAS_ADM', 'TENANT_A', 'EMP', 'PL', 'DEF', 'DEL', 'LEGAL_RE'])
            ->get();
        
        // Manually set the relation so the view can iterate over $case->caseUsers (as a collection of models/objects)
        $case->setRelation('caseUsers', $caseUsers);

        $logUser = User::leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
            ->where('users.id', Auth::id())
            ->select('users.*', 'urm.role_value as user_role_id', 'urm.tenant_id')
            ->first();

        $loggedUserTenantId = $logUser->tenant_id;

        $usersWithRole7 = User::whereHas('userRoleMappings', function ($query) use ($loggedUserTenantId) {
            $query->where('role_value', 'EMP')
            ->where('tenant_id', $loggedUserTenantId);
        })->get();

        $assignedUsers = CaseUserMapping::where('case_id', $id)
            ->where('role_value', 'EMP')
            ->pluck('user_id')
            ->toArray();

        // Items logic...
        $items = Item::with(['createdBy', 'modifiedBy'])
                    ->where('case_id', $id)
                    ->orderBy('id', 'asc')
                    ->get();
                    
        return view('backend.cases.show', compact('case', 'role', 'usersWithRole7', 'items', 'logUser', 'assignedUsers'));
    }

    public function create()
    {
        $menus = Menu::where('is_active', true)->where('admin_type', 'TA')->orderBy('sort_order', 'ASC')->get(); // adjust if needed

        $role = DB::table('data_element')
            ->whereIn('category_id', [2])
            ->where('is_active', true)
            ->where('value', '!=', 'DEL')
            ->orderBy('name')
            ->get();

        $caseTypes = DB::table('data_element')
            ->whereIn('category_id', [6])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('backend.cases.create', compact('menus', 'role', 'caseTypes'));
    }

    /**
     * Store case
     */
    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'case_number'      => 'required|unique:cases,case_number',
            'case_type'        => 'required|exists:data_element,value',
            'case_description' => 'nullable|string',
            'court_name'       => 'nullable|string|max:256',
            'sla_deadline'     => 'required|date',
            'asset_sla_in_days' => 'required|integer|min:0',
            'max_number_of_arbitation_per_user' => 'required|integer|min:0',
            'contacts'         => 'required|array|min:1',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.name'  => 'nullable|string|max:255',
            'contacts.*.phone' => 'nullable|string|max:20',
            'contacts.*.user_id' => 'nullable|integer|exists:users,id',
            'contacts.*.role_id' => 'required|string|exists:data_element,value|not_in:DEL',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (!is_array($request->contacts)) {
                return;
            }
            foreach ($request->contacts as $i => $c) {
                $hasUserId = !empty($c['user_id']);
                if (!$hasUserId && (empty($c['email']) || empty($c['name']) || empty($c['phone']))) {
                    $validator->errors()->add("contacts.$i.email", 'Either choose an existing employee or enter email, name and phone.');
                }
            }
            $plaintiffCount = collect($request->contacts)->where('role_id', 'PL')->count();
            $defendantCount = collect($request->contacts)->where('role_id', 'DEF')->count();
            if ($plaintiffCount > 1) {
                $validator->errors()->add('contacts', 'Only one Plaintiff can be added per case.');
            }
            if ($defendantCount > 1) {
                $validator->errors()->add('contacts', 'Only one Defendant can be added per case.');
            }
            $legalReCount = collect($request->contacts)->where('role_id', 'LEGAL_RE')->count();
            if ($legalReCount < 1) {
                $validator->errors()->add('contacts', 'At least one Legal Representative (LEGAL_RE) is required.');
            }
        });

        $validator->validate();

        // Check loggedin user
        $loggedUser = User::leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
                    ->where('users.id', Auth::id())
                    ->select('users.*', 'urm.role_value as user_role_id', 'urm.tenant_id')
                    ->first();

        try {
            DB::beginTransaction();

            $case = CourtCase::create([
                'case_number'      => $request->case_number,
                'case_type_value'  => $request->case_type,
                'case_status_value' => 'C_NEW',
                'case_description' => $request->case_description,
                'court_name'       => $request->court_name ?: null,
                'sla_deadline'     => $request->sla_deadline ?: null,
                'asset_sla_in_days' => $request->asset_sla_in_days !== '' && $request->asset_sla_in_days !== null ? (int) $request->asset_sla_in_days : null,
                'max_number_of_arbitation_per_user' => $request->max_number_of_arbitation_per_user !== '' && $request->max_number_of_arbitation_per_user !== null ? (int) $request->max_number_of_arbitation_per_user : null,
                'is_active'        => true,
                'created_by'       => Auth::id(),
                'created_date'     => now(),
                'modified_by'      => Auth::id(),
                'last_modified_date' => now(),
            ]);

            // Add the case creator as LEGAL_RE only if they did not explicitly add themselves as LEGAL_RE in contacts
            $creatorAlreadyLegalRe = collect($request->contacts)->contains(function ($c) {
                $isCreator = (isset($c['user_id']) && (int) $c['user_id'] === (int) Auth::id())
                    || (isset($c['email']) && strcasecmp(trim($c['email'] ?? ''), Auth::user()->email ?? '') === 0);
                return $isCreator && ($c['role_id'] ?? '') === 'LEGAL_RE';
            });
            if (!$creatorAlreadyLegalRe) {
                CaseUserMapping::create([
                    'case_id'   => $case->id,
                    'user_id'   => Auth::id(),
                    'role_value' => 'LEGAL_RE',
                    'user_status_value' => 'READY',
                    'participate_in_distribution' => false,
                    'allocated_item_count' => 0,
                    'allocated_value' => 0,
                    'value_difference' => 0,
                    'is_active'        => true,
                    'created_by'       => Auth::id(),
                    'created_date'     => now(),
                    'modified_by'      => Auth::id(),
                    'last_modified_date' => now(),
                ]);
            }

            foreach ($request->contacts as $row) {
                if (!empty($row['user_id'])) {
                    $user = User::find($row['user_id']);
                } else {
                    $user = User::where('email', $row['email'])->first();
                }

                if ($user) {
                    $participateInDistribution = in_array($row['role_id'], ['PL', 'DEF']);
                    CaseUserMapping::create([
                        'user_id'   => $user->id,
                        'case_id'   => $case->id,
                        'role_value'   => $row['role_id'],
                        'user_status_value' => 'READY',
                        'participate_in_distribution' => $participateInDistribution,
                        'allocated_item_count' => 0,
                        'allocated_value' => 0,
                        'value_difference' => 0,
                        'is_active'        => true,
                        'created_by'       => Auth::id(),
                        'created_date'     => now(),
                        'modified_by'      => Auth::id(),
                        'last_modified_date' => now(),
                    ]);
                } else {
                    $newUser = User::create([
                        'email'  => $row['email'],
                        'name'   => $row['name'],
                        'phone_number' => $row['phone'],
                        'password'  => md5('12345'),
                        'preferred_language' => 'en',
                        'is_active'        => true,
                        'created_by'       => Auth::id(),
                        'created_date'     => now(),
                        'modified_by'      => Auth::id(),
                        'last_modified_date' => now(),
                    ]);

                    // Plaintiff or Defendant new contacts: mark as EC [end client] in user_role_mapping and tie to tenant 1
                    $isEndClient = in_array($row['role_id'] ?? '', ['PL', 'DEF']);
                    UserRoleMapping::create([
                        'user_id' => $newUser->id,
                        'role_value' => $isEndClient ? 'EC' : $row['role_id'],
                        'tenant_id' => $isEndClient ? 1 : $loggedUser->tenant_id,
                        'is_active'        => true,
                        'created_by'       => Auth::id(),
                        'created_date'     => now(),
                        'modified_by'      => Auth::id(),
                        'last_modified_date' => now(),
                    ]);

                    $participateInDistribution = in_array($row['role_id'], ['PL', 'DEF']);
                    CaseUserMapping::create([
                        'user_id'   => $newUser->id,
                        'case_id'   => $case->id,
                        'role_value'   => $row['role_id'],
                        'user_status_value' => 'READY',
                        'participate_in_distribution' => $participateInDistribution,
                        'allocated_item_count' => 0,
                        'allocated_value' => 0,
                        'value_difference' => 0,
                        'is_active'        => true,
                        'created_by'       => Auth::id(),
                        'created_date'     => now(),
                        'modified_by'      => Auth::id(),
                        'last_modified_date' => now(),
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('admin.cases.index')->with('success', 'Case created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage());
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Edit case
     */
    public function edit($id)
    {
        $case = CourtCase::findOrFail($id);

        // load existing mapped users
        $caseUsers = CaseUserMapping::with('user')
            ->where('case_id', $id)
            ->whereIn('role_value', ['SAAS_ADM', 'TENANT_A', 'DEF', 'DEL', 'LEGAL_RE', 'PL']) // Assuming 3,4 were these. Or maybe just leave IDs if Role::whereIn is used. But this is CaseUserMapping, which uses role_value (string). So MUST match string values. 3=SAAS_ADM, 4=TENANT_A.
            ->get();
        
        $caseType = DB::table('data_element')
            ->whereIn('category_id', [6])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $role = DB::table('data_element')
            ->whereIn('category_id', [2])
            ->where('is_active', true)
            ->where('value', '!=', 'DEL')
            ->orderBy('name')
            ->get();

        return view('backend.cases.edit', compact('case', 'caseUsers', 'role', 'caseType'));
    }


    /**
     * Update case
     */
    public function update(Request $request, $id)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'case_number' => 'required|unique:cases,case_number,' . $id,
            'case_type' => 'required|max:255',
            'case_description' => 'nullable|string',
            'court_name' => 'nullable|string|max:256',
            'sla_deadline' => 'required|date',
            'asset_sla_in_days' => 'required|integer|min:0',
            'max_number_of_arbitation_per_user' => 'required|integer|min:0',

            'is_legal_hold' => 'nullable|boolean',
            'legal_hold_reason' => 'nullable|string|max:4000',
            'legal_hold_start_date' => 'nullable|date',
            'legal_hold_end_date' => 'nullable|date|after_or_equal:legal_hold_start_date',

            'users' => 'nullable|array',
            'users.*.email' => 'nullable|email|max:255',
            'users.*.name'  => 'nullable|string|max:255',
            'users.*.phone' => 'nullable|string|max:20',
            'users.*.user_id' => 'nullable|integer|exists:users,id',
            'users.*.mapping_id' => 'nullable|integer',
            'users.*.role' => 'required|string|exists:data_element,value|not_in:DEL',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (!is_array($request->users)) {
                return;
            }
            $plaintiffCount = collect($request->users)->where('role', 'PL')->count();
            $defendantCount = collect($request->users)->where('role', 'DEF')->count();
            if ($plaintiffCount > 1) {
                $validator->errors()->add('users', 'Only one Plaintiff can be added per case.');
            }
            if ($defendantCount > 1) {
                $validator->errors()->add('users', 'Only one Defendant can be added per case.');
            }
            $legalReCount = collect($request->users)->where('role', 'LEGAL_RE')->count();
            if ($legalReCount < 1) {
                $validator->errors()->add('users', 'At least one Legal Representative (LEGAL_RE) is required.');
            }
        });

        $validator->validate();

        try {
            DB::beginTransaction();

            /** Update Parent Case */
            $case = CourtCase::findOrFail($id);
            $case->update([
                'case_number' => $request->case_number,
                'case_type_value' => $request->case_type,
                'case_description' => $request->case_description,
                'court_name' => $request->court_name ?: null,
                'sla_deadline' => $request->sla_deadline,
                'asset_sla_in_days' => (int) $request->asset_sla_in_days,
                'max_number_of_arbitation_per_user' => (int) $request->max_number_of_arbitation_per_user,
                'is_active' => $request->has('is_active') ? (bool) $request->boolean('is_active') : $case->is_active,
                'is_legal_hold' => (bool) $request->boolean('is_legal_hold', false),
                'legal_hold_reason' => $request->filled('legal_hold_reason') ? $request->legal_hold_reason : null,
                'legal_hold_start_date' => $request->filled('legal_hold_start_date') ? $request->legal_hold_start_date : null,
                'legal_hold_end_date' => $request->filled('legal_hold_end_date') ? $request->legal_hold_end_date : null,
                'modified_by' => Auth::id(),
                'last_modified_date' => now(),
            ]);

            /** Get submitted mapping IDs */
            $submittedMappingIds = collect($request->users)
                ->pluck('mapping_id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->toArray();

            /** Delete removed rows (never delete Plaintiff or Defendant) */
            CaseUserMapping::where('case_id', $case->id)
                ->whereNotIn('role_value', ['PL', 'DEF'])
                ->when(
                    count($submittedMappingIds) > 0,
                    fn($q) => $q->whereNotIn('id', $submittedMappingIds)
                )
                ->delete();

            /** Logged User & Tenant Context if needed */
            $loggedUser = User::leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
                ->where('users.id', Auth::id())
                ->select('users.*', 'urm.role_value as user_role_id', 'urm.tenant_id')
                ->first();

            /** Save / Update Users & Mappings */
            foreach ($request->users as $row) {
                $mappingId = $row['mapping_id'] ?? null;

                if (!empty($row['user_id'])) {
                    $user = User::find($row['user_id']);
                } else {
                    /** Create OR Update user */
                    $user = User::firstOrCreate(
                        ['email' => $row['email']],
                        [
                            'name' => $row['name'],
                            'phone_number' => $row['phone'],
                            'password' => md5('12345'),
                            'preferred_language' => 'en',
                            'is_active' => true,
                            'created_by' => Auth::id(),
                            'created_date' => now(),
                        ]
                    );

                    if ($user->wasRecentlyCreated == false) {
                        $user->update([
                            'name' => $row['name'],
                            'phone_number' => $row['phone'],
                            'modified_by' => Auth::id(),
                            'last_modified_date' => now(),
                        ]);
                    }
                }

                if (!$user) {
                    continue;
                }

                $participateInDistribution = in_array($row['role'], ['PL', 'DEF']);

                /** Update existing mapping */
                if ($mappingId) {
                    CaseUserMapping::where('id', $mappingId)->update([
                        'user_id' => $user->id,
                        'role_value' => $row['role'],
                        'participate_in_distribution' => $participateInDistribution,
                        'modified_by' => Auth::id(),
                        'last_modified_date' => now(),
                    ]);
                } else {
                    /** Create new mapping */
                    CaseUserMapping::create([
                        'case_id' => $case->id,
                        'user_id' => $user->id,
                        'role_value' => $row['role'],
                        'user_status_value' => 'READY',
                        'participate_in_distribution' => $participateInDistribution,
                        'allocated_item_count' => 0,
                        'allocated_value' => 0,
                        'value_difference' => 0,
                        'is_active' => true,
                        'created_by' => Auth::id(),
                        'created_date' => now(),
                        'modified_by' => Auth::id(),
                        'last_modified_date' => now(),
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('admin.cases.index')->with('success', 'Case updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Error: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete case
     */
    public function destroy($id)
    {
        try {
            $case = CourtCase::findOrFail($id);
            $case->delete();

            return redirect()->route('admin.cases.index')->with('success', 'Case deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function saveActivity(Request $request)
    {
        $request->validate([
            'case_id' => 'required',
            'subject' => 'required',
            'case_file' => 'nullable|array',
            'case_file.*' => 'file|max:2048',
        ]);

        $files = [];
        if ($request->hasFile('case_file')) {
            foreach ($request->file('case_file') as $file) {
                $fileName = time().'_'.$file->getClientOriginalName();
                $file->storeAs('case_files', $fileName, 'public');
                $files[] = $fileName;
            }
        }

        CaseActivity::create([
            'case_id' => $request->case_id,
            'created_by' => Auth::id(),
            'subject' => $request->subject,
            'notes' => $request->notes,
            'case_file' => $files,
            'next_follow_up_date' => $request->next_follow_up_date ?: null,
            'created_date' => now(),
        ]);

        return response()->json(['status' => true]);
    }

    public function list(Request $request, $caseId)
    {
        $activities = DB::table('case_activity')
            ->leftJoin('users', 'case_activity.created_by', '=', 'users.id')
            ->where('case_activity.case_id', $caseId)
            ->orderBy('case_activity.created_date', 'desc')
            ->select('case_activity.*', 'users.name as user_name')
            ->paginate(10);

        return response()->json([
            'activities' => $activities->items(),
            'pagination' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
                'prev_page_url' => $activities->previousPageUrl(),
                'next_page_url' => $activities->nextPageUrl(),
            ],
        ]);
    }

    public function updateAssignUsers(Request $request, $id)
    {
        $request->validate([
            'assign_id' => 'array|nullable'
        ]);

        // Check loggedin user
        $loggedUser = User::leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
                ->where('users.id', Auth::id())
                ->select('users.*', 'urm.role_value as user_role_id', 'urm.tenant_id')
                ->first();
        
        $case = CourtCase::findOrFail($id);

        $assignIds = $request->assign_id ? array_map('intval', $request->assign_id) : [];

        foreach ($assignIds as $userId) {
            // Check if mapping already exists (LEGAL_RE for this assignment)
            $existingMapping = CaseUserMapping::where('user_id', $userId)
                ->where('case_id', $case->id)
                ->where('role_value', 'LEGAL_RE')
                ->first();

            if ($existingMapping) {
                continue;
            }

            CaseUserMapping::create([
                'user_id'   => $userId,
                'case_id'   => $case->id,
                'role_value'   => 'LEGAL_RE',
                'user_status_value' => 'READY',
                'participate_in_distribution' => false,
                'allocated_item_count' => 0,
                'allocated_value' => 0,
                'value_difference' => 0,
                'is_active'        => true,
                'created_by'       => Auth::id(),
                'created_date'     => now(),
                'modified_by'      => Auth::id(),
                'last_modified_date' => now(),
            ]);
        }

        // Fetch updated case users for table refresh (same shape as show page)
        $caseUsers = CaseUserMapping::select(
                'case_user_mapping.*',
                'users.name as user_name',
                'users.email as user_email',
                'users.phone_number as user_phone',
                'role.name as role_name'
            )
            ->join('users', 'case_user_mapping.user_id', '=', 'users.id')
            ->join('data_element as role', 'case_user_mapping.role_value', '=', 'role.value')
            ->where('case_user_mapping.case_id', $case->id)
            ->whereIn('case_user_mapping.role_value', ['SAAS_ADM', 'TENANT_A', 'EMP', 'PL', 'DEF', 'DEL', 'LEGAL_RE'])
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Assigned users updated successfully',
            'assign_id' => $assignIds,
            'case_users' => $caseUsers->map(fn ($row) => [
                'mapping_id' => $row->id,
                'role_value' => $row->role_value,
                'user_name' => $row->user_name ?? 'N/A',
                'user_email' => $row->user_email ?? 'N/A',
                'user_phone' => $row->user_phone ?? 'N/A',
                'role_name' => $row->role_name ?? 'Not Assigned',
            ])->values()->toArray(),
        ]);
    }

    /**
     * Remove a user from the case (case users table) by mapping id.
     */
    public function removeCaseUser(Request $request, $id)
    {
        $request->validate([
            'mapping_id' => 'required|integer',
        ]);

        $mapping = CaseUserMapping::where('id', $request->mapping_id)
            ->where('case_id', $id)
            ->firstOrFail();

        $mapping->delete();

        // Return updated case users for table refresh
        $caseUsers = CaseUserMapping::select(
                'case_user_mapping.*',
                'users.name as user_name',
                'users.email as user_email',
                'users.phone_number as user_phone',
                'role.name as role_name'
            )
            ->join('users', 'case_user_mapping.user_id', '=', 'users.id')
            ->join('data_element as role', 'case_user_mapping.role_value', '=', 'role.value')
            ->where('case_user_mapping.case_id', $id)
            ->whereIn('case_user_mapping.role_value', ['SAAS_ADM', 'TENANT_A', 'EMP', 'PL', 'DEF', 'DEL', 'LEGAL_RE'])
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'User removed from case.',
            'case_users' => $caseUsers->map(fn ($row) => [
                'mapping_id' => $row->id,
                'role_value' => $row->role_value,
                'user_name' => $row->user_name ?? 'N/A',
                'user_email' => $row->user_email ?? 'N/A',
                'user_phone' => $row->user_phone ?? 'N/A',
                'role_name' => $row->role_name ?? 'Not Assigned',
            ])->values()->toArray(),
        ]);
    }

}
