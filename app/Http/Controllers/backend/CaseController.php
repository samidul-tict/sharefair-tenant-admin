<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Jobs\SendDistributionSummaryEmailsJob;
use App\Models\AssociatedLocation;
use App\Models\CaseActivity;
use App\Models\CaseUserMapping;
use App\Models\CourtCase;
use App\Models\Item;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserRoleMapping;
use App\Services\ShareFairApiException;
use App\Services\ShareFairApiService;
use App\Services\DistributionSummaryExportService;
use App\Support\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CaseController extends Controller
{
    /**
     * Current admin user with role/tenant from user_role_mapping.
     */
    private function currentLogUser(): ?User
    {
        return AdminContext::logUser();
    }

    /**
     * Restrict a case query to cases the current user may access.
     */
    private function applyCaseAccessScope($query, ?User $logUser = null): void
    {
        $logUser ??= $this->currentLogUser();

        if ($logUser && $logUser->user_role_id === 'EMP') {
            $query->whereIn('id', function ($subQuery) {
                $subQuery->select('case_id')
                    ->from('case_user_mapping')
                    ->where('user_id', Auth::id());
            });
        } else {
            $query->where(function ($q) {
                $q->where('created_by', Auth::id())
                    ->orWhereIn('id', function ($sub) {
                        $sub->select('case_id')
                            ->from('case_user_mapping')
                            ->where('user_id', Auth::id());
                    });
            });
        }
    }

    /**
     * List all cases
     */
    public function index(Request $request)
    {
        $logUser = $this->currentLogUser();
        
        $search     = $request->input('search');
        $statusFilter = $request->input('status');
        $sortField  = $request->input('sort', 'case_number');
        $sortOrder  = $request->input('order', 'asc');

        $allowedSorts = ['case_number', 'case_type_value', 'case_status_value'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'case_number';
        }

        
        $cases = CourtCase::query()
            ->with(['createdBy', 'caseType', 'caseStatus'])
            ->where('is_active', true);
        $this->applyCaseAccessScope($cases, $logUser);

        $cases = $cases
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

        $caseTypes = DB::table('data_element')
            ->whereIn('category_id', [6])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $accessibleCaseQuery = CourtCase::query()->where('is_active', true);
        $this->applyCaseAccessScope($accessibleCaseQuery, $logUser);

        $caseStatuses = DB::table('data_element')
            ->whereIn('value', (clone $accessibleCaseQuery)->distinct()->pluck('case_status_value')->filter()->toArray())
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
        $case = $this->findAccessibleCase($id);
        $case->load(['createdBy', 'modifiedBy', 'distributedBy', 'distributionMethod', 'caseType', 'caseStatus']);

        $caseUsers = $this->caseUsersWithDetails($id);
        $case->setRelation('caseUsers', $caseUsers);

        $assetCount = Item::where('case_id', $id)->count();

        $itemDataElementLabels = DB::table('data_element')
            ->whereIn('category_id', [7, 8, 10, 12, 14])
            ->where('is_active', true)
            ->pluck('name', 'value');

        $locations = AssociatedLocation::where('case_id', $id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $activityCount = CaseActivity::where('case_id', $id)->count();
        $participatingUserCount = CaseUserMapping::where('case_id', $id)
            ->where('participate_in_distribution', true)
            ->count();

        $canDistribute = $case->canLegalRepresentativeDistribute();
        $showDistributionSummary = $case->hasDistributionSummary();

        return view('backend.cases.show', compact(
            'case',
            'assetCount',
            'itemDataElementLabels',
            'locations',
            'activityCount',
            'participatingUserCount',
            'canDistribute',
            'showDistributionSummary'
        ));
    }

    /**
     * Paginated assets for the case show page (server-side filters and search).
     */
    public function listAssets(Request $request, int $id)
    {
        $this->findAccessibleCase($id);

        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|in:25,50,100',
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:64',
            'category' => 'nullable|string|max:64',
            'location_id' => 'nullable|integer',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 25);
        $search = trim((string) ($validated['search'] ?? ''));

        $labels = DB::table('data_element')
            ->whereIn('category_id', [7, 8, 10, 12, 14])
            ->where('is_active', true)
            ->pluck('name', 'value');

        $query = Item::query()
            ->with(['location', 'assignedToUser'])
            ->where('case_id', $id);

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($inner) use ($like) {
                $inner->where('name', 'ILIKE', $like)
                    ->orWhere('other_category', 'ILIKE', $like)
                    ->orWhere('other_brand', 'ILIKE', $like)
                    ->orWhere('assigned_reason', 'ILIKE', $like)
                    ->orWhereHas('assignedToUser', fn ($userQuery) => $userQuery->where('name', 'ILIKE', $like))
                    ->orWhereHas('location', fn ($locationQuery) => $locationQuery->where('name', 'ILIKE', $like));
            });
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if (!empty($validated['location_id'])) {
            $query->where('location_id', (int) $validated['location_id']);
        }

        $assets = $query->orderBy('id')->paginate($perPage);

        $startIndex = ($assets->currentPage() - 1) * $assets->perPage();

        return response()->json([
            'items' => $assets->getCollection()->values()->map(function (Item $item, int $offset) use ($labels, $startIndex) {
                return $this->formatCaseAssetRow($item, $labels, $startIndex + $offset + 1);
            }),
            'pagination' => [
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
                'prev_page_url' => $assets->previousPageUrl(),
                'next_page_url' => $assets->nextPageUrl(),
            ],
            'filters' => $this->caseAssetFilterOptions($id),
            'total_in_case' => Item::where('case_id', $id)->count(),
        ]);
    }

    /**
     * Full-page distribution summary (preview or completed allocation).
     */
    public function distributeReview(int $id)
    {
        $case = $this->findAccessibleCase($id);
        $this->assertCanViewDistributionSummary($case);

        $case->load(['caseType', 'caseStatus']);

        $canConfirmDistribute = $case->canLegalRepresentativeDistribute();
        $emailRecipients = $this->distributionSummaryEmailRecipients($id);
        $showDistributionCaps = in_array($case->distribution_method_value, ['DIST_FCP', 'DIST_CAP'], true);
        $distributionValueCaps = CaseUserMapping::query()
            ->where('case_id', $id)
            ->whereIn('role_value', ['PL', 'DEF'])
            ->get()
            ->keyBy('role_value');

        return view('backend.cases.distribute-review', compact(
            'case',
            'canConfirmDistribute',
            'emailRecipients',
            'showDistributionCaps',
            'distributionValueCaps'
        ));
    }

    /**
     * Preview asset distribution for a case (proxied to Share Fair API).
     */
    public function distributePreview(int $id, ShareFairApiService $shareFairApi)
    {
        $case = $this->findAccessibleCase($id);
        $this->assertCanViewDistributionSummary($case);

        try {
            $payload = $shareFairApi->getDistributePreview($id);
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        return response()->json([
            'status' => true,
            'data' => $payload['data'] ?? $payload,
        ]);
    }

    /**
     * Download distribution summary as PDF or Excel.
     */
    public function distributeDownload(
        Request $request,
        int $id,
        ShareFairApiService $shareFairApi,
        DistributionSummaryExportService $export
    ) {
        $format = strtolower((string) $request->query('format', 'pdf'));
        if (!in_array($format, ['pdf', 'excel'], true)) {
            abort(400, 'Invalid download format. Use pdf or excel.');
        }

        $case = $this->findAccessibleCase($id);
        $this->assertCanViewDistributionSummary($case);

        try {
            $payload = $shareFairApi->getDistributePreview($id);
        } catch (ShareFairApiException $e) {
            return back()->with('error', $e->getMessage());
        }

        $case->load(['caseType', 'caseStatus']);
        $data = $payload['data'] ?? $payload;

        return $format === 'excel'
            ? $export->downloadExcel($case, $data)
            : $export->downloadPdf($case, $data);
    }

    /**
     * Email distribution summary PDF to selected case users.
     */
    public function distributeEmail(
        Request $request,
        int $id,
        ShareFairApiService $shareFairApi,
        DistributionSummaryExportService $export
    ) {
        $validated = $request->validate([
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*' => ['integer'],
        ]);

        $case = $this->findAccessibleCase($id);
        $this->assertCanViewDistributionSummary($case);

        $selectedIds = collect($validated['recipients'])->map(fn ($userId) => (int) $userId)->unique()->values();
        $recipients = $this->distributionSummaryEmailRecipients($id)
            ->whereIn('id', $selectedIds)
            ->values();

        if ($recipients->count() !== $selectedIds->count()) {
            return response()->json([
                'status' => false,
                'message' => 'Please select valid case users with email addresses.',
            ], 422);
        }

        try {
            $payload = $shareFairApi->getDistributePreview($id);
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        $case->load(['caseType', 'caseStatus']);
        $data = $payload['data'] ?? $payload;
        $storedPdf = $export->storePdf($case, $data);

        $recipientPayload = $recipients->map(function ($recipient) {
            return [
                'email' => $recipient->email,
                'name' => $this->distributionSummaryGreetingName($recipient->name, $recipient->email),
            ];
        })->all();

        SendDistributionSummaryEmailsJob::dispatch(
            $case->id,
            $recipientPayload,
            $storedPdf['path'],
            $storedPdf['filename']
        );

        return response()->json([
            'status' => true,
            'message' => 'Distribution summary emails are being sent in the background.',
        ]);
    }

    /**
     * Run asset distribution for a case (proxied to Share Fair API).
     */
    public function distribute(int $id, ShareFairApiService $shareFairApi)
    {
        $case = $this->findAccessibleCase($id);
        $this->assertCanDistribute($case);

        try {
            $payload = $shareFairApi->distributeCase($id);
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        $case->refresh();

        return response()->json([
            'status' => true,
            'message' => $payload['message'] ?? 'Assets distributed successfully.',
            'case_status_value' => $case->case_status_value,
            'data' => $payload['data'] ?? null,
        ]);
    }

    /**
     * Load a case scoped to the current tenant admin user.
     */
    private function findAccessibleCase(int $id): CourtCase
    {
        $query = CourtCase::query()
            ->where('id', $id)
            ->where('is_active', true);

        $this->applyCaseAccessScope($query);

        return $query->firstOrFail();
    }

    /**
     * Case participants with user and role details for display/API refresh.
     */
    private function caseUsersWithDetails(int $caseId)
    {
        return CaseUserMapping::select(
                'case_user_mapping.*',
                'users.name as user_name',
                'users.email as user_email',
                'users.phone_number as user_phone',
                'role.name as role_name'
            )
            ->join('users', 'case_user_mapping.user_id', '=', 'users.id')
            ->join('data_element as role', 'case_user_mapping.role_value', '=', 'role.value')
            ->where('case_user_mapping.case_id', $caseId)
            ->whereIn('case_user_mapping.role_value', ['SAAS_ADM', 'TENANT_A', 'EMP', 'PL', 'DEF', 'DEL', 'LEGAL_RE'])
            ->get();
    }

    /**
     * Preload existing users referenced in contact rows.
     *
     * @return array{by_id: \Illuminate\Support\Collection, by_email: \Illuminate\Support\Collection}
     */
    private function preloadContactUsers(array $contacts): array
    {
        $ids = collect($contacts)->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $emails = collect($contacts)->pluck('email')->filter()->map(fn ($email) => strtolower(trim((string) $email)))->unique()->values();

        return [
            'by_id' => $ids->isNotEmpty()
                ? User::whereIn('id', $ids)->get()->keyBy('id')
                : collect(),
            'by_email' => $emails->isNotEmpty()
                ? User::whereIn('email', $emails)->get()->keyBy(fn (User $user) => strtolower($user->email))
                : collect(),
        ];
    }

    private function resolveContactUser(array $row, array $preloaded): ?User
    {
        if (!empty($row['user_id'])) {
            return $preloaded['by_id']->get((int) $row['user_id']);
        }

        $email = strtolower(trim((string) ($row['email'] ?? '')));
        if ($email === '') {
            return null;
        }

        return $preloaded['by_email']->get($email);
    }

    /**
     * Users on the case who can receive the distribution summary by email.
     */
    private function distributionSummaryEmailRecipients(int $caseId)
    {
        return CaseUserMapping::select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('MIN(role.name) as role_name')
            )
            ->join('users', 'case_user_mapping.user_id', '=', 'users.id')
            ->leftJoin('data_element as role', 'case_user_mapping.role_value', '=', 'role.value')
            ->where('case_user_mapping.case_id', $caseId)
            ->where('users.is_active', true)
            ->whereNotNull('users.email')
            ->where('users.email', '<>', '')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('users.name')
            ->get();
    }

    private function distributionSummaryGreetingName(?string $name, ?string $email): string
    {
        $name = trim((string) $name);
        if ($name !== '') {
            return explode(' ', $name)[0];
        }

        $email = trim((string) $email);
        if ($email !== '' && str_contains($email, '@')) {
            return explode('@', $email)[0];
        }

        return 'there';
    }

    /**
     * Ensure the case is eligible for viewing distribution summary.
     */
    private function assertCanViewDistributionSummary(CourtCase $case): void
    {
        if (!$case->hasDistributionSummary()) {
            abort(403, 'Distribution summary is not available for this case status.');
        }
    }

    /**
     * Ensure the case is eligible for legal-representative distribution.
     */
    private function assertCanDistribute(CourtCase $case): void
    {
        if (!$case->canLegalRepresentativeDistribute()) {
            abort(403, 'This case is not eligible for distribution.');
        }
    }

    public function create()
    {
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

        $distributionMethods = $this->distributionMethods();

        return view('backend.cases.create', compact('role', 'caseTypes', 'distributionMethods'));
    }

    /**
     * Distribution method options (data_element category_id = 15).
     */
    private function distributionMethods()
    {
        return DB::table('data_element')
            ->where('category_id', 15)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['name', 'value', 'helper_text', 'sort_order']);
    }

    /**
     * Validation rules for distribution fields on create/update.
     */
    private function distributionFieldRules(): array
    {
        return [
            'distribution_sla_in_days' => 'nullable|integer|min:0',
            'max_number_of_distribution_attempts' => 'required|integer|min:0',
            'distribution_method' => [
                'required',
                'string',
                Rule::exists('data_element', 'value')->where(function ($query) {
                    $query->where('category_id', 15)->where('is_active', true);
                }),
            ],
            'asset_distributed_by' => 'required|in:client,legal_representative',
        ];
    }

    /**
     * Whether assets are distributed by the client (true) or legal representative (false).
     */
    private function distributeByClientFromRequest(Request $request): bool
    {
        return $request->input('asset_distributed_by') === 'client';
    }

    /**
     * Map request distribution fields for persistence.
     */
    private function distributionAttributesFromRequest(Request $request): array
    {
        return [
            'distribution_sla_in_days' => $request->filled('distribution_sla_in_days')
                ? (int) $request->distribution_sla_in_days
                : null,
            'max_number_of_distribution_attempts' => (int) $request->max_number_of_distribution_attempts,
            'distribution_method_value' => $request->distribution_method,
            'distribute_by_client' => $this->distributeByClientFromRequest($request),
        ];
    }

    private function distributionValueCapFromRow(Request $request, array $row, string $roleKey): ?float
    {
        $requiresCap = in_array($request->distribution_method, ['DIST_FCP', 'DIST_CAP'], true);
        $isParticipant = in_array($row[$roleKey] ?? null, ['PL', 'DEF'], true);
        $value = $row['distribution_value_cap'] ?? null;

        return $requiresCap && $isParticipant && $value !== '' && $value !== null
            ? (float) $value
            : null;
    }

    /**
     * Store case
     */
    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), array_merge([
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
            'contacts.*.distribution_value_cap' => 'nullable|numeric|min:0',
        ], $this->distributionFieldRules()));

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
            if (in_array($request->distribution_method, ['DIST_FCP', 'DIST_CAP'], true)) {
                foreach ($request->contacts as $i => $contact) {
                    if (
                        in_array($contact['role_id'] ?? null, ['PL', 'DEF'], true)
                        && (!isset($contact['distribution_value_cap']) || $contact['distribution_value_cap'] === '')
                    ) {
                        $validator->errors()->add(
                            "contacts.$i.distribution_value_cap",
                            'Distribution value cap is required for the Client and Spouse for this distribution method.'
                        );
                    }
                }
            }
        });

        $validator->validate();

        // Check loggedin user
        $loggedUser = $this->currentLogUser();

        try {
            DB::beginTransaction();

            $case = CourtCase::create(array_merge([
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
            ], $this->distributionAttributesFromRequest($request)));

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

            $preloadedUsers = $this->preloadContactUsers($request->contacts);

            foreach ($request->contacts as $row) {
                $user = $this->resolveContactUser($row, $preloadedUsers);

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
                        'distribution_value_cap' => $this->distributionValueCapFromRow($request, $row, 'role_id'),
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
                        'distribution_value_cap' => $this->distributionValueCapFromRow($request, $row, 'role_id'),
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
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Edit case
     */
    public function edit($id)
    {
        $case = $this->findAccessibleCase($id);

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

        $distributionMethods = $this->distributionMethods();

        return view('backend.cases.edit', compact('case', 'caseUsers', 'role', 'caseType', 'distributionMethods'));
    }


    /**
     * Update case
     */
    public function update(Request $request, $id)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), array_merge([
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
            'users.*.distribution_value_cap' => 'nullable|numeric|min:0',
        ], $this->distributionFieldRules()));

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
            if (in_array($request->distribution_method, ['DIST_FCP', 'DIST_CAP'], true)) {
                foreach ($request->users as $i => $user) {
                    if (
                        in_array($user['role'] ?? null, ['PL', 'DEF'], true)
                        && (!isset($user['distribution_value_cap']) || $user['distribution_value_cap'] === '')
                    ) {
                        $validator->errors()->add(
                            "users.$i.distribution_value_cap",
                            'Distribution value cap is required for the Client and Spouse for this distribution method.'
                        );
                    }
                }
            }
        });

        $validator->validate();

        try {
            DB::beginTransaction();

            /** Update Parent Case */
            $case = $this->findAccessibleCase($id);
            $case->update(array_merge([
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
            ], $this->distributionAttributesFromRequest($request)));

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

            $preloadedUsers = $this->preloadContactUsers(
                collect($request->users)->map(fn ($row) => [
                    'user_id' => $row['user_id'] ?? null,
                    'email' => $row['email'] ?? null,
                ])->all()
            );

            /** Save / Update Users & Mappings */
            foreach ($request->users as $row) {
                $mappingId = $row['mapping_id'] ?? null;

                if (!empty($row['user_id'])) {
                    $user = $preloadedUsers['by_id']->get((int) $row['user_id']);
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
                        'distribution_value_cap' => $this->distributionValueCapFromRow($request, $row, 'role'),
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
                        'distribution_value_cap' => $this->distributionValueCapFromRow($request, $row, 'role'),
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
            $case = $this->findAccessibleCase($id);
            $case->delete();

            return redirect()->route('admin.cases.index')->with('success', 'Case deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function saveActivity(Request $request)
    {
        $request->validate([
            'case_id' => 'required|integer',
            'subject' => 'required',
            'case_file' => 'nullable|array',
            'case_file.*' => 'file|max:2048',
        ]);

        $this->findAccessibleCase((int) $request->case_id);

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
        $this->findAccessibleCase((int) $caseId);

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

        $case = $this->findAccessibleCase((int) $id);

        $assignIds = $request->assign_id ? array_map('intval', $request->assign_id) : [];
        $existingAssigneeIds = CaseUserMapping::where('case_id', $case->id)
            ->where('role_value', 'LEGAL_RE')
            ->whereIn('user_id', $assignIds)
            ->pluck('user_id')
            ->all();
        $existingAssigneeLookup = array_flip($existingAssigneeIds);
        $now = now();

        foreach ($assignIds as $userId) {
            if (isset($existingAssigneeLookup[$userId])) {
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
                'created_date'     => $now,
                'modified_by'      => Auth::id(),
                'last_modified_date' => $now,
            ]);
        }

        $caseUsers = $this->caseUsersWithDetails($case->id);

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

        $this->findAccessibleCase((int) $id);

        $mapping = CaseUserMapping::where('id', $request->mapping_id)
            ->where('case_id', $id)
            ->firstOrFail();

        $mapping->delete();

        $caseUsers = $this->caseUsersWithDetails((int) $id);

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

    /**
     * @param  \Illuminate\Support\Collection<string, string>  $labels
     */
    private function formatCaseAssetRow(Item $item, $labels, int $index): array
    {
        $label = function ($value) use ($labels) {
            if ($value === null || $value === '') {
                return '—';
            }

            return $labels->get($value) ?? $value;
        };

        $money = function ($value) {
            return $value !== null && $value !== ''
                ? '$' . number_format((float) $value, 2)
                : '—';
        };

        $bool = function ($value) {
            if ($value === null) {
                return '—';
            }

            return $value ? 'Yes' : 'No';
        };

        $locationLabel = $item->location
            ? $item->location->name
            : ($item->location_id ? '#' . $item->location_id : '—');

        $assignedLabel = $item->assignedToUser
            ? $item->assignedToUser->name
            : ($item->assigned_to_user_id ? '#' . $item->assigned_to_user_id : '—');

        return [
            'index' => $index,
            'name' => $item->name ?? '—',
            'location' => $locationLabel,
            'category' => $label($item->category),
            'other_category' => $item->other_category ?: '—',
            'condition' => $label($item->condition),
            'brand' => $label($item->brand),
            'other_brand' => $item->other_brand ?: '—',
            'purchase_year' => $item->purchase_year ?? '—',
            'purchase_price' => $money($item->purchase_price),
            'estimated_value' => $money($item->estimated_value),
            'concluded_price' => $money($item->concluded_price),
            'accessories_status' => $label($item->accessories_status_value),
            'original_packaging' => $bool($item->has_original_packaging),
            'valid_warranty' => $bool($item->has_valid_warranty),
            'marital_asset' => $bool($item->is_marital_asset),
            'assigned_to' => $assignedLabel,
            'assigned_reason' => $item->assigned_reason ?: '—',
            'status' => $label($item->status),
        ];
    }

    private function caseAssetFilterOptions(int $caseId): array
    {
        $baseQuery = Item::query()->where('case_id', $caseId);

        $statuses = (clone $baseQuery)
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->filter()
            ->values();

        $categories = (clone $baseQuery)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->values();

        $locationIds = (clone $baseQuery)
            ->whereNotNull('location_id')
            ->distinct()
            ->pluck('location_id')
            ->filter()
            ->values();

        $locations = $locationIds->isNotEmpty()
            ? AssociatedLocation::whereIn('id', $locationIds)->orderBy('name')->get(['id', 'name'])
            : collect();

        return [
            'statuses' => $statuses,
            'categories' => $categories,
            'locations' => $locations,
        ];
    }

}
