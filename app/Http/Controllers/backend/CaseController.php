<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Jobs\SendDistributionSummaryEmailsJob;
use App\Mail\CasePartyInvitationMail;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class CaseController extends Controller
{
    private const DEFAULT_CASE_TYPE_VALUE = 'CIVIL';

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
        $attentionFilter = $request->input('attention');
        $sortField  = $request->input('sort', 'case_number');
        $sortOrder  = $request->input('order', 'asc');

        $allowedSorts = ['case_number', 'case_status_value'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'case_number';
        }

        
        $cases = CourtCase::query()
            ->with(['createdBy', 'caseStatus'])
            ->where('is_active', true);
        $this->applyCaseAccessScope($cases, $logUser);

        $cases = $cases
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('case_number', 'ILIKE', "%{$search}%")
                        ->orWhere('case_status_value', 'ILIKE', "%{$search}%");
                    });
                })
                ->when($statusFilter, function ($query, $status) {
                    $query->where('case_status_value', $status);
                })
                ->when($attentionFilter === 'attorney_distribute', function ($query) {
                    $query->needsAttorneyDistribution();
                })
                ->orderBy($sortField, $sortOrder)
                ->paginate(10);

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

        return view('backend.cases.index', compact('cases', 'search', 'sortField', 'sortOrder', 'logUser', 'caseStatuses', 'attentionFilter'));
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

        $assetCount = Item::where('case_id', $id)->where('is_active', true)->count();

        $itemDataElementLabels = DB::table('data_element')
            ->whereIn('category_id', [7, 8, 10, 12, 14])
            ->where('is_active', true)
            ->pluck('name', 'value');

        $locations = AssociatedLocation::where('case_id', $id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $activityCount = CaseActivity::where('case_id', $id)
            ->whereNull('item_id')
            ->count();
        $commentCount = DB::table('comments')
            ->where('case_id', $id)
            ->whereNull('item_id')
            ->whereNull('parent_comment_id')
            ->count();
        $participatingUserCount = CaseUserMapping::where('case_id', $id)
            ->active()
            ->where('participate_in_distribution', true)
            ->count();

        $canDistribute = $case->canLegalRepresentativeDistribute();
        $showDistributionSummary = $case->hasDistributionSummary();
        $canCloseCase = $this->canCurrentAttorneyCloseCase($case);

        return view('backend.cases.show', compact(
            'case',
            'assetCount',
            'itemDataElementLabels',
            'locations',
            'activityCount',
            'commentCount',
            'participatingUserCount',
            'canDistribute',
            'showDistributionSummary',
            'canCloseCase'
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
            'sort_by' => [
                'nullable',
                Rule::in([
                    'name', 'location', 'category', 'other_category', 'condition', 'brand',
                    'other_brand', 'purchase_year', 'purchase_price', 'estimated_value',
                    'concluded_price', 'accessories_status', 'original_packaging',
                    'valid_warranty', 'marital_asset', 'assigned_to', 'assigned_reason', 'status',
                ]),
            ],
            'sort_order' => 'nullable|in:asc,desc',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 25);
        $search = trim((string) ($validated['search'] ?? ''));
        $sortBy = (string) ($validated['sort_by'] ?? 'name');
        $sortOrder = (string) ($validated['sort_order'] ?? 'asc');
        $dataElementCategoryIds = [7, 8, 10, 12, 14];

        $labels = DB::table('data_element')
            ->whereIn('category_id', $dataElementCategoryIds)
            ->where('is_active', true)
            ->pluck('name', 'value');

        $query = Item::query()
            ->with(['location', 'assignedToUser'])
            ->where('case_id', $id)
            ->where('is_active', true);

        if ($search !== '') {
            $like = '%' . $search . '%';
            $matchingLabelValues = DB::table('data_element')
                ->whereIn('category_id', $dataElementCategoryIds)
                ->where('is_active', true)
                ->where('name', 'ILIKE', $like)
                ->pluck('value')
                ->filter()
                ->unique()
                ->values()
                ->all();
            $normalizedNumber = str_replace(['$', ','], '', $search);
            $isNumericSearch = is_numeric($normalizedNumber);
            $numericSearch = $isNumericSearch ? (float) $normalizedNumber : null;
            $integerSearch = $isNumericSearch && floor($numericSearch) === $numericSearch
                ? (int) $numericSearch
                : null;
            $lowerSearch = mb_strtolower($search);
            $booleanSearch = match (true) {
                in_array($lowerSearch, ['yes', 'true'], true) => true,
                in_array($lowerSearch, ['no', 'false'], true) => false,
                default => null,
            };

            $query->where(function ($inner) use (
                $like,
                $matchingLabelValues,
                $isNumericSearch,
                $numericSearch,
                $integerSearch,
                $booleanSearch,
                $lowerSearch
            ) {
                $inner->where('name', 'ILIKE', $like)
                    ->orWhere('other_category', 'ILIKE', $like)
                    ->orWhere('other_brand', 'ILIKE', $like)
                    ->orWhere('assigned_reason', 'ILIKE', $like)
                    ->orWhere('category', 'ILIKE', $like)
                    ->orWhere('condition', 'ILIKE', $like)
                    ->orWhere('brand', 'ILIKE', $like)
                    ->orWhere('accessories_status_value', 'ILIKE', $like)
                    ->orWhere('status', 'ILIKE', $like)
                    ->orWhereHas('assignedToUser', fn ($userQuery) => $userQuery->where('name', 'ILIKE', $like))
                    ->orWhereHas('location', fn ($locationQuery) => $locationQuery->where('name', 'ILIKE', $like));

                if ($matchingLabelValues !== []) {
                    $inner->orWhereIn('category', $matchingLabelValues)
                        ->orWhereIn('condition', $matchingLabelValues)
                        ->orWhereIn('brand', $matchingLabelValues)
                        ->orWhereIn('accessories_status_value', $matchingLabelValues)
                        ->orWhereIn('status', $matchingLabelValues);
                }

                if ($isNumericSearch) {
                    $inner->orWhere('purchase_price', $numericSearch)
                        ->orWhere('estimated_value', $numericSearch)
                        ->orWhere('concluded_price', $numericSearch);
                    if ($integerSearch !== null) {
                        $inner->orWhere('purchase_year', $integerSearch);
                    }
                }

                if ($booleanSearch !== null) {
                    $inner->orWhere('has_original_packaging', $booleanSearch)
                        ->orWhere('has_valid_warranty', $booleanSearch)
                        ->orWhere('is_marital_asset', $booleanSearch);
                }

                if ($lowerSearch === 'marital') {
                    $inner->orWhere('is_marital_asset', true);
                } elseif (in_array($lowerSearch, ['separate', 'non-marital', 'non marital'], true)) {
                    $inner->orWhere('is_marital_asset', false);
                }
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

        $directSortColumns = [
            'name' => 'items.name',
            'other_category' => 'items.other_category',
            'other_brand' => 'items.other_brand',
            'purchase_year' => 'items.purchase_year',
            'purchase_price' => 'items.purchase_price',
            'estimated_value' => 'items.estimated_value',
            'concluded_price' => 'items.concluded_price',
            'original_packaging' => 'items.has_original_packaging',
            'valid_warranty' => 'items.has_valid_warranty',
            'marital_asset' => 'items.is_marital_asset',
            'assigned_reason' => 'items.assigned_reason',
        ];
        $labelSortColumns = [
            'category' => 'category',
            'condition' => 'condition',
            'brand' => 'brand',
            'accessories_status' => 'accessories_status_value',
            'status' => 'status',
        ];

        if ($sortBy === 'location') {
            $query->orderBy(
                AssociatedLocation::query()
                    ->select('name')
                    ->whereColumn('associated_locations.id', 'items.location_id')
                    ->limit(1),
                $sortOrder
            );
        } elseif ($sortBy === 'assigned_to') {
            $query->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn('users.id', 'items.assigned_to_user_id')
                    ->limit(1),
                $sortOrder
            );
        } elseif (isset($labelSortColumns[$sortBy])) {
            $sourceColumn = $labelSortColumns[$sortBy];
            $query->orderBy(
                DB::table('data_element')
                    ->select('name')
                    ->whereColumn('data_element.value', "items.{$sourceColumn}")
                    ->whereIn('category_id', $dataElementCategoryIds)
                    ->where('is_active', true)
                    ->limit(1),
                $sortOrder
            );
        } else {
            $query->orderBy($directSortColumns[$sortBy] ?? 'items.name', $sortOrder);
        }

        $assets = $query->orderBy('items.id', 'asc')->paginate($perPage);

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
            'total_in_case' => Item::where('case_id', $id)->where('is_active', true)->count(),
            'sort' => [
                'by' => $sortBy,
                'order' => $sortOrder,
            ],
        ]);
    }

    /**
     * Asset detail for the case assets table modal.
     */
    public function showAsset(int $id, int $itemId)
    {
        $this->findAccessibleCase($id);

        $item = Item::query()
            ->with(['location', 'assignedToUser'])
            ->where('case_id', $id)
            ->where('id', $itemId)
            ->where('is_active', true)
            ->first();

        if (!$item) {
            return response()->json([
                'status' => false,
                'message' => 'Asset not found for this case.',
            ], 404);
        }

        $labels = DB::table('data_element')
            ->whereIn('category_id', [7, 8, 10, 12, 14])
            ->where('is_active', true)
            ->pluck('name', 'value');

        return response()->json([
            'status' => true,
            'item' => $this->formatCaseAssetDetail($item, $labels),
        ]);
    }

    /**
     * Proxy asset image bytes from Share Fair API (supports R2-backed items.images).
     */
    public function assetImage(int $id, int $itemId, ShareFairApiService $shareFairApi)
    {
        $this->findAccessibleCase($id);

        $item = Item::query()
            ->where('case_id', $id)
            ->where('id', $itemId)
            ->first(['id', 'images']);

        if (!$item || !$item->images) {
            abort(404);
        }

        $stored = trim((string) $item->images);
        if (str_starts_with($stored, 'http://') || str_starts_with($stored, 'https://')) {
            return redirect()->away($stored);
        }

        try {
            return $shareFairApi->proxyItemImage($id, $itemId);
        } catch (ShareFairApiException $e) {
            abort($e->status >= 400 && $e->status < 600 ? $e->status : 404);
        }
    }

    /**
     * Case-level comments (proxied from Share Fair; item_id null).
     */
    public function caseComments(Request $request, int $id, ShareFairApiService $shareFairApi)
    {
        $this->findAccessibleCase($id);

        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:50',
            'sort_order' => 'nullable|in:asc,desc',
            'search' => 'nullable|string|max:100',
        ]);

        try {
            $payload = $shareFairApi->getCaseComments(
                $id,
                (int) ($validated['page'] ?? 1),
                (int) ($validated['limit'] ?? 20),
                (string) ($validated['sort_order'] ?? 'desc'),
                isset($validated['search']) ? trim((string) $validated['search']) : null
            );
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        return $this->commentsListResponse($payload);
    }

    /**
     * Add a case-level comment.
     */
    public function storeCaseComment(Request $request, int $id, ShareFairApiService $shareFairApi)
    {
        $this->findAccessibleCase($id);

        $validated = $request->validate([
            'comment' => 'required|string|min:1|max:5000',
        ]);

        try {
            $payload = $shareFairApi->createCaseComment($id, trim($validated['comment']));
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        return response()->json([
            'status' => true,
            'message' => $payload['message'] ?? 'Comment added successfully',
            'comment' => $payload['data'] ?? null,
        ], 201);
    }

    /**
     * Replies for a case-level comment.
     */
    public function caseCommentResponses(
        Request $request,
        int $id,
        int $commentId,
        ShareFairApiService $shareFairApi
    ) {
        $this->findAccessibleCase($id);
        $this->assertCaseLevelComment($id, $commentId);

        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:50',
            'sort_order' => 'nullable|in:asc,desc',
        ]);

        try {
            $payload = $shareFairApi->getCommentResponses(
                $id,
                $commentId,
                (int) ($validated['page'] ?? 1),
                (int) ($validated['limit'] ?? 20),
                (string) ($validated['sort_order'] ?? 'desc')
            );
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        return $this->commentsListResponse($payload);
    }

    /**
     * Add a reply to a case-level comment.
     */
    public function storeCaseCommentResponse(
        Request $request,
        int $id,
        int $commentId,
        ShareFairApiService $shareFairApi
    ) {
        $this->findAccessibleCase($id);
        $this->assertCaseLevelComment($id, $commentId);

        $validated = $request->validate([
            'comment' => 'required|string|min:1|max:5000',
        ]);

        try {
            $payload = $shareFairApi->createCommentResponse($id, $commentId, trim($validated['comment']));
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        return response()->json([
            'status' => true,
            'message' => $payload['message'] ?? 'Response added successfully',
            'comment' => $payload['data'] ?? null,
        ], 201);
    }

    public function likeCaseComment(int $id, int $commentId, ShareFairApiService $shareFairApi)
    {
        $this->findAccessibleCase($id);
        $this->assertCommentBelongsToCase($id, $commentId);

        try {
            $payload = $shareFairApi->likeComment($id, $commentId);
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        return response()->json([
            'status' => true,
            'likes_count' => (int) ($payload['likes_count'] ?? 0),
            'liked_by_me' => (bool) ($payload['liked_by_me'] ?? true),
        ]);
    }

    public function unlikeCaseComment(int $id, int $commentId, ShareFairApiService $shareFairApi)
    {
        $this->findAccessibleCase($id);
        $this->assertCommentBelongsToCase($id, $commentId);

        try {
            $payload = $shareFairApi->unlikeComment($id, $commentId);
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        return response()->json([
            'status' => true,
            'likes_count' => (int) ($payload['likes_count'] ?? 0),
            'liked_by_me' => (bool) ($payload['liked_by_me'] ?? false),
        ]);
    }

    /**
     * Asset-scoped comments (proxied from Share Fair).
     */
    public function assetComments(Request $request, int $id, int $itemId, ShareFairApiService $shareFairApi)
    {
        $this->findAccessibleCase($id);
        $this->assertAssetBelongsToCase($id, $itemId);

        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:50',
            'sort_order' => 'nullable|in:asc,desc',
            'search' => 'nullable|string|max:100',
        ]);

        try {
            $payload = $shareFairApi->getAssetComments(
                $id,
                $itemId,
                (int) ($validated['page'] ?? 1),
                (int) ($validated['limit'] ?? 20),
                (string) ($validated['sort_order'] ?? 'desc'),
                isset($validated['search']) ? trim((string) $validated['search']) : null
            );
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        return $this->commentsListResponse($payload);
    }

    public function storeAssetComment(Request $request, int $id, int $itemId, ShareFairApiService $shareFairApi)
    {
        $this->findAccessibleCase($id);
        $this->assertAssetBelongsToCase($id, $itemId);

        $validated = $request->validate([
            'comment' => 'required|string|min:1|max:5000',
        ]);

        try {
            $payload = $shareFairApi->createAssetComment($id, $itemId, trim($validated['comment']));
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        return response()->json([
            'status' => true,
            'message' => $payload['message'] ?? 'Comment added successfully',
            'comment' => $payload['data'] ?? null,
        ], 201);
    }

    /**
     * Replies for an asset comment.
     */
    public function assetCommentResponses(
        Request $request,
        int $id,
        int $itemId,
        int $commentId,
        ShareFairApiService $shareFairApi
    ) {
        $this->findAccessibleCase($id);
        $this->assertAssetBelongsToCase($id, $itemId);
        $this->assertAssetComment($id, $itemId, $commentId);

        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:50',
            'sort_order' => 'nullable|in:asc,desc',
        ]);

        try {
            $payload = $shareFairApi->getCommentResponses(
                $id,
                $commentId,
                (int) ($validated['page'] ?? 1),
                (int) ($validated['limit'] ?? 20),
                (string) ($validated['sort_order'] ?? 'desc')
            );
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        return $this->commentsListResponse($payload);
    }

    public function storeAssetCommentResponse(
        Request $request,
        int $id,
        int $itemId,
        int $commentId,
        ShareFairApiService $shareFairApi
    ) {
        $this->findAccessibleCase($id);
        $this->assertAssetBelongsToCase($id, $itemId);
        $this->assertAssetComment($id, $itemId, $commentId);

        $validated = $request->validate([
            'comment' => 'required|string|min:1|max:5000',
        ]);

        try {
            $payload = $shareFairApi->createCommentResponse($id, $commentId, trim($validated['comment']));
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        return response()->json([
            'status' => true,
            'message' => $payload['message'] ?? 'Response added successfully',
            'comment' => $payload['data'] ?? null,
        ], 201);
    }

    /**
     * Asset-scoped activity timeline (proxied from Share Fair).
     */
    public function assetTimeline(Request $request, int $id, int $itemId, ShareFairApiService $shareFairApi)
    {
        $this->findAccessibleCase($id);
        $this->assertAssetBelongsToCase($id, $itemId);

        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $payload = $shareFairApi->getAssetActivities(
                $id,
                $itemId,
                (int) ($validated['page'] ?? 1),
                (int) ($validated['limit'] ?? 10)
            );
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        $data = $payload['data'] ?? [];

        return response()->json([
            'status' => true,
            'activities' => $data['activities'] ?? [],
            'pagination' => [
                'current_page' => (int) ($data['page'] ?? 1),
                'last_page' => (int) ($data['total_pages'] ?? 0),
                'per_page' => (int) ($data['limit'] ?? 10),
                'total' => (int) ($data['total_count'] ?? 0),
            ],
        ]);
    }

    private function assertAssetBelongsToCase(int $caseId, int $itemId): void
    {
        $exists = Item::query()
            ->where('case_id', $caseId)
            ->where('id', $itemId)
            ->exists();

        if (!$exists) {
            abort(404, 'Asset not found for this case.');
        }
    }

    private function commentsListResponse(array $payload): \Illuminate\Http\JsonResponse
    {
        $data = $payload['data'] ?? [];

        return response()->json([
            'status' => true,
            'comments' => $data['comments'] ?? [],
            'pagination' => [
                'current_page' => (int) ($data['page'] ?? 1),
                'last_page' => (int) ($data['total_pages'] ?? 0),
                'per_page' => (int) ($data['limit'] ?? 20),
                'total' => (int) ($data['total_count'] ?? 0),
            ],
        ]);
    }

    private function assertCommentBelongsToCase(int $caseId, int $commentId): void
    {
        $exists = DB::table('comments')
            ->where('case_id', $caseId)
            ->where('id', $commentId)
            ->exists();

        if (!$exists) {
            abort(404, 'Comment not found for this case.');
        }
    }

    private function assertCaseLevelComment(int $caseId, int $commentId): void
    {
        $exists = DB::table('comments')
            ->where('case_id', $caseId)
            ->where('id', $commentId)
            ->whereNull('item_id')
            ->exists();

        if (!$exists) {
            abort(404, 'Case comment not found.');
        }
    }

    private function assertAssetComment(int $caseId, int $itemId, int $commentId): void
    {
        $exists = DB::table('comments')
            ->where('case_id', $caseId)
            ->where('id', $commentId)
            ->where('item_id', $itemId)
            ->exists();

        if (!$exists) {
            abort(404, 'Asset comment not found.');
        }
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
        $canAdjustDistribute = $case->canLegalRepresentativeAdjustDistribution();
        $canCloseCase = $this->canCurrentAttorneyCloseCase($case);
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
            'canAdjustDistribute',
            'canCloseCase',
            'emailRecipients',
            'showDistributionCaps',
            'distributionValueCaps'
        ));
    }

    /**
     * Close a legal matter after its PEND_APP requirements are satisfied.
     */
    public function closeCase(int $id, ShareFairApiService $shareFairApi)
    {
        $case = $this->findAccessibleCase($id);
        if (!$this->canCurrentAttorneyCloseCase($case)) {
            abort(403, 'This case is not eligible to be closed.');
        }

        try {
            $payload = $shareFairApi->closeCase($id);
        } catch (ShareFairApiException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.cases.show', $id)
            ->with('success', $payload['message'] ?? 'Case closed successfully.');
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
     * Persist attorney-adjusted marital assignments while case is PEND_APP.
     */
    public function distributeAdjustDraft(Request $request, int $id, ShareFairApiService $shareFairApi)
    {
        $case = $this->findAccessibleCase($id);
        $this->assertCanAdjustDistribution($case);

        $validated = $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.item_id' => 'required|integer',
            'assignments.*.assigned_to_user_id' => 'required|integer',
            'assignments.*.allocation_reason' => 'nullable|string|max:255',
        ]);

        $assignments = collect($validated['assignments'])
            ->map(fn (array $row) => [
                'item_id' => (int) $row['item_id'],
                'assigned_to_user_id' => (int) $row['assigned_to_user_id'],
                'allocation_reason' => $row['allocation_reason'] ?? 'Attorney Adjusted',
            ])
            ->values()
            ->all();

        $this->assertValidDistributionAssignments($id, $assignments);

        try {
            $payload = $shareFairApi->adjustDistributedCase($id, $assignments);
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        session()->forget('distribute_adjust.' . $id);

        return response()->json([
            'status' => true,
            'message' => $payload['message'] ?? 'Distribution adjusted successfully.',
            'data' => $payload['data'] ?? $payload,
        ]);
    }

    /**
     * Clear unused local adjust draft (no-op after PEND_APP persist flow).
     */
    public function clearDistributeAdjustDraft(int $id)
    {
        $this->findAccessibleCase($id);
        session()->forget('distribute_adjust.' . $id);

        return response()->json([
            'status' => true,
            'message' => 'Adjustments discarded.',
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
     * Optional assignments remaps marital assets before persist.
     */
    public function distribute(Request $request, int $id, ShareFairApiService $shareFairApi)
    {
        $case = $this->findAccessibleCase($id);
        $this->assertCanDistribute($case);

        $validated = $request->validate([
            'assignments' => 'nullable|array',
            'assignments.*.item_id' => 'required_with:assignments|integer',
            'assignments.*.assigned_to_user_id' => 'required_with:assignments|integer',
            'assignments.*.allocation_reason' => 'nullable|string|max:255',
        ]);

        $assignments = collect($validated['assignments'] ?? [])
            ->map(function (array $row) {
                return [
                    'item_id' => (int) $row['item_id'],
                    'assigned_to_user_id' => (int) $row['assigned_to_user_id'],
                    'allocation_reason' => $row['allocation_reason'] ?? 'Attorney Adjusted',
                ];
            })
            ->values()
            ->all();

        if (!empty($assignments)) {
            $this->assertValidDistributionAssignments($id, $assignments);
        }

        try {
            $payload = $shareFairApi->distributeCase($id, $assignments ?: null);
        } catch (ShareFairApiException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 500);
        }

        $case->refresh();
        session()->forget('distribute_adjust.' . $id);

        return response()->json([
            'status' => true,
            'message' => $payload['message'] ?? 'Assets distributed successfully.',
            'case_status_value' => $case->case_status_value,
            'data' => $payload['data'] ?? null,
        ]);
    }

    /**
     * Ensure attorney assignment payload references this case's marital items and participants.
     *
     * @param  array<int, array{item_id: int, assigned_to_user_id: int, allocation_reason?: string}>  $assignments
     */
    private function assertValidDistributionAssignments(int $caseId, array $assignments): void
    {
        $itemIds = collect($assignments)->pluck('item_id')->unique()->values();
        $userIds = collect($assignments)->pluck('assigned_to_user_id')->unique()->values();

        $validItemCount = Item::query()
            ->where('case_id', $caseId)
            ->whereIn('id', $itemIds)
            ->count();

        if ($validItemCount !== $itemIds->count()) {
            abort(response()->json([
                'status' => false,
                'message' => 'One or more assets in the adjusted assignment do not belong to this case.',
            ], 422));
        }

        $validUserIds = CaseUserMapping::query()
            ->where('case_id', $caseId)
            ->where('participate_in_distribution', true)
            ->whereIn('user_id', $userIds)
            ->pluck('user_id')
            ->unique();

        if ($validUserIds->count() !== $userIds->count()) {
            abort(response()->json([
                'status' => false,
                'message' => 'One or more assignees are not distribution participants on this case.',
            ], 422));
        }
    }

    /**
     * Overlay session-adjusted marital assignments onto preview/export payloads.
     */
    private function applyDistributeAdjustDraft(int $caseId, array $data): array
    {
        $assignments = session('distribute_adjust.' . $caseId);
        if (empty($assignments) || !is_array($assignments)) {
            return $data;
        }

        return $this->remapAllocationData($data, $assignments);
    }

    /**
     * @param  array<int, array{item_id: int, assigned_to_user_id: int, allocation_reason?: string}>  $assignments
     */
    private function remapAllocationData(array $data, array $assignments): array
    {
        $allocations = $data['allocations'] ?? [];
        if (!is_array($allocations) || $allocations === []) {
            return $data;
        }

        $itemLookup = [];
        $userMeta = [];
        foreach ($allocations as $key => $alloc) {
            if (!is_array($alloc)) {
                continue;
            }
            $userId = (int) ($alloc['user_id'] ?? 0);
            if ($userId > 0) {
                $userMeta[$userId] = [
                    'key' => $key,
                    'user_id' => $userId,
                    'user_email' => $alloc['user_email'] ?? null,
                    'user_name' => $alloc['user_name'] ?? null,
                    'user_role' => $alloc['user_role'] ?? '',
                    'carry_forward_value' => $alloc['carry_forward_value'] ?? null,
                ];
            }
            foreach ($alloc['items'] ?? [] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $itemId = (int) ($item['id'] ?? $item['item_id'] ?? 0);
                if ($itemId > 0) {
                    $itemLookup[$itemId] = $item;
                }
            }
        }

        $buckets = [];
        foreach ($userMeta as $userId => $meta) {
            $buckets[$userId] = [
                'user_id' => $meta['user_id'],
                'user_email' => $meta['user_email'],
                'user_name' => $meta['user_name'],
                'user_role' => $meta['user_role'],
                'carry_forward_value' => $meta['carry_forward_value'],
                'items' => [],
                'allocated_value' => (float) ($meta['carry_forward_value'] ?? 0),
            ];
        }

        foreach ($assignments as $row) {
            $itemId = (int) ($row['item_id'] ?? 0);
            $userId = (int) ($row['assigned_to_user_id'] ?? 0);
            if ($itemId <= 0 || $userId <= 0 || !isset($itemLookup[$itemId]) || !isset($buckets[$userId])) {
                continue;
            }
            $item = $itemLookup[$itemId];
            $item['allocation_reason'] = $row['allocation_reason'] ?? 'Attorney Adjusted';
            $buckets[$userId]['items'][] = $item;
            $price = $item['concluded_price'] ?? $item['purchase_price'] ?? 0;
            $buckets[$userId]['allocated_value'] += (float) $price;
        }

        $target = (float) ($data['target_value_per_user'] ?? 0);
        $remapped = [];
        foreach ($userMeta as $userId => $meta) {
            $bucket = $buckets[$userId];
            $remapped[$meta['key']] = [
                'user_id' => $bucket['user_id'],
                'user_email' => $bucket['user_email'],
                'user_name' => $bucket['user_name'],
                'user_role' => $bucket['user_role'],
                'allocated_item_count' => count($bucket['items']),
                'allocated_value' => round($bucket['allocated_value'], 2),
                'value_difference' => round($bucket['allocated_value'] - $target, 2),
                'items' => $bucket['items'],
                'carry_forward_value' => $bucket['carry_forward_value'],
            ];
        }

        $data['allocations'] = $remapped;

        return $data;
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
            ->where(function ($q) {
                $q->where('case_user_mapping.is_active', true)->orWhereNull('case_user_mapping.is_active');
            })
            ->get();
    }

    private function emptyPartyRow(): array
    {
        return [
            'email' => '',
            'name' => '',
            'phone' => '',
            'user_id' => '',
            'mapping_id' => '',
            'distribution_value_cap' => '',
        ];
    }

    private function usesPostgresSavepoints(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql'
            && DB::connection()->transactionLevel() > 0;
    }

    private function createDbSavepoint(string $name): void
    {
        if ($this->usesPostgresSavepoints()) {
            DB::statement('SAVEPOINT ' . $name);
        }
    }

    private function rollbackDbSavepoint(string $name): void
    {
        if ($this->usesPostgresSavepoints()) {
            DB::statement('ROLLBACK TO SAVEPOINT ' . $name);
        }
    }

    private function releaseDbSavepoint(string $name): void
    {
        if ($this->usesPostgresSavepoints()) {
            DB::statement('RELEASE SAVEPOINT ' . $name);
        }
    }

    private function uniqueSavepointName(string $prefix): string
    {
        return $prefix . '_' . preg_replace('/[^a-zA-Z0-9_]/', '', uniqid('', true));
    }

    private function shouldMergeExistingCaseUserMapping(CaseUserMapping $existing, array $attributes): bool
    {
        if ($existing->is_active === false) {
            return true;
        }

        // Counsel rows always merge onto the existing case mapping for this user.
        if (array_key_exists('representing_to_user', $attributes)) {
            return true;
        }

        return false;
    }

    private function deactivateCaseUserMapping(int $caseId, int $mappingId): void
    {
        CaseUserMapping::query()
            ->where('id', $mappingId)
            ->where('case_id', $caseId)
            ->update([
                'is_active' => false,
                'modified_by' => Auth::id(),
                'last_modified_date' => now(),
            ]);
    }

    private function isEmptyPartyRow(array $row): bool
    {
        return empty($row['user_id'])
            && trim((string) ($row['email'] ?? '')) === ''
            && trim((string) ($row['name'] ?? '')) === ''
            && trim((string) ($row['phone'] ?? '')) === '';
    }

    private function mappingToPartyRow(CaseUserMapping $mapping): array
    {
        $user = $mapping->user;

        return [
            'mapping_id' => $mapping->id,
            'user_id' => $user?->id ?? '',
            'name' => $user?->name ?? '',
            'email' => $user?->email ?? '',
            'phone' => $user?->phone_number ?? '',
            'phone_number' => $user?->phone_number ?? '',
            'role' => $mapping->role_value,
            'role_id' => $mapping->role_value,
            'distribution_value_cap' => $mapping->distribution_value_cap,
        ];
    }

    /**
     * @return array{partySlots: array<string, array>, additionalContacts: array<int, array>}
     */
    private function organizeContactsForPartyForm(array $oldRows, $caseUsers = null): array
    {
        if (!empty($oldRows)) {
            return $this->organizePartyRowsFromFlat($oldRows);
        }

        $partySlots = [
            'client' => $this->emptyPartyRow(),
            'client_counsel' => $this->emptyPartyRow(),
            'spouse' => $this->emptyPartyRow(),
            'spouse_counsel' => $this->emptyPartyRow(),
        ];
        $additionalContacts = [];

        if (!$caseUsers || $caseUsers->isEmpty()) {
            return compact('partySlots', 'additionalContacts');
        }

        $plMapping = $caseUsers->firstWhere('role_value', 'PL');
        $defMapping = $caseUsers->firstWhere('role_value', 'DEF');

        if ($plMapping) {
            $partySlots['client'] = $this->mappingToPartyRow($plMapping);
        }
        if ($defMapping) {
            $partySlots['spouse'] = $this->mappingToPartyRow($defMapping);
        }

        $plUserId = $plMapping?->user_id;
        $defUserId = $defMapping?->user_id;
        $assignedCounselIds = [];

        foreach ($caseUsers->where('role_value', 'LEGAL_RE') as $mapping) {
            if ($plUserId && (int) $mapping->representing_to_user === (int) $plUserId) {
                $partySlots['client_counsel'] = array_merge($this->mappingToPartyRow($mapping), [
                    'represents_party' => 'client',
                ]);
                $assignedCounselIds[] = $mapping->id;
            } elseif ($defUserId && (int) $mapping->representing_to_user === (int) $defUserId) {
                $partySlots['spouse_counsel'] = array_merge($this->mappingToPartyRow($mapping), [
                    'represents_party' => 'spouse',
                ]);
                $assignedCounselIds[] = $mapping->id;
            }
        }

        $fixedIds = array_filter(array_merge(
            [$plMapping?->id, $defMapping?->id],
            $assignedCounselIds
        ));

        $additionalContacts = $caseUsers
            ->reject(fn ($mapping) => in_array($mapping->id, $fixedIds, true))
            ->map(function ($mapping) {
                $row = $this->mappingToPartyRow($mapping);
                $row['role'] = 'LEGAL_RE';
                $row['role_id'] = 'LEGAL_RE';

                return $row;
            })
            ->values()
            ->all();

        return compact('partySlots', 'additionalContacts');
    }

    /**
     * @return array{partySlots: array<string, array>, additionalContacts: array<int, array>}
     */
    private function organizePartyRowsFromFlat(array $rows): array
    {
        $partySlots = [
            'client' => $rows[0] ?? $this->emptyPartyRow(),
            'client_counsel' => $rows[1] ?? $this->emptyPartyRow(),
            'spouse' => $rows[2] ?? $this->emptyPartyRow(),
            'spouse_counsel' => $rows[3] ?? $this->emptyPartyRow(),
        ];
        $additionalContacts = [];

        if (isset($rows[0]['role_id']) || isset($rows[0]['role'])) {
            $partySlots = [
                'client' => $this->emptyPartyRow(),
                'client_counsel' => $this->emptyPartyRow(),
                'spouse' => $this->emptyPartyRow(),
                'spouse_counsel' => $this->emptyPartyRow(),
            ];

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $role = $row['role_id'] ?? $row['role'] ?? '';
                if ($role === 'PL') {
                    $partySlots['client'] = $row;
                } elseif ($role === 'DEF') {
                    $partySlots['spouse'] = $row;
                } elseif ($role === 'LEGAL_RE') {
                    $represents = $row['represents_party'] ?? null;
                    if ($represents === 'client' && $this->isEmptyPartyRow($partySlots['client_counsel'])) {
                        $row['represents_party'] = 'client';
                        $partySlots['client_counsel'] = $row;
                    } elseif ($represents === 'spouse' && $this->isEmptyPartyRow($partySlots['spouse_counsel'])) {
                        $row['represents_party'] = 'spouse';
                        $partySlots['spouse_counsel'] = $row;
                    } else {
                        $additionalContacts[] = $row;
                    }
                } else {
                    $row['role_id'] = 'LEGAL_RE';
                    $row['role'] = 'LEGAL_RE';
                    $additionalContacts[] = $row;
                }
            }
        } else {
            foreach ($rows as $index => $row) {
                if (!is_array($row)) {
                    continue;
                }
                if ($index >= 4) {
                    $row['role_id'] = 'LEGAL_RE';
                    $row['role'] = 'LEGAL_RE';
                    $additionalContacts[] = $row;
                }
            }
        }

        return compact('partySlots', 'additionalContacts');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizePartyContactRows(array $rows): array
    {
        $normalized = [];
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $slot = (int) $index;
            if ($slot === 3 && $this->isEmptyPartyRow($row)) {
                continue;
            }
            if ($slot >= 4) {
                $row['role_id'] = 'LEGAL_RE';
                $row['role'] = 'LEGAL_RE';
            }
            if ($slot === 1) {
                $row['represents_party'] = $row['represents_party'] ?? 'client';
            }
            if ($slot === 3) {
                $row['represents_party'] = $row['represents_party'] ?? 'spouse';
            }
            $normalized[$slot] = $row;
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * Resolve plaintiff and defendant user IDs first so counsel rows can link representing_to_user.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{client: int|null, spouse: int|null}
     */
    private function resolvePartyUserIdsFromContactRows(
        array $rows,
        string $roleKey,
        array &$preloaded,
        ?User $loggedUser,
        ?CourtCase $case = null
    ): array {
        $partyUserIds = ['client' => null, 'spouse' => null];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $role = $row[$roleKey] ?? null;
            if ($role === null || $role === '') {
                if ((int) $index === 0) {
                    $role = 'PL';
                } elseif ((int) $index === 2) {
                    $role = 'DEF';
                }
            }

            if (!in_array($role, ['PL', 'DEF'], true)) {
                continue;
            }

            if ($case && !empty($row['mapping_id'])) {
                $mapping = CaseUserMapping::query()
                    ->where('id', (int) $row['mapping_id'])
                    ->where('case_id', $case->id)
                    ->first();

                if ($mapping && in_array($mapping->role_value, ['PL', 'DEF'], true)) {
                    $userId = (int) $mapping->user_id;
                    if ($mapping->role_value === 'PL') {
                        $partyUserIds['client'] = $userId;
                    }
                    if ($mapping->role_value === 'DEF') {
                        $partyUserIds['spouse'] = $userId;
                    }
                    continue;
                }
            }

            $user = $this->findOrCreateContactUser($row, $preloaded, $loggedUser, $roleKey);
            $userId = (int) $user->id;
            if ($role === 'PL') {
                $partyUserIds['client'] = $userId;
            }
            if ($role === 'DEF') {
                $partyUserIds['spouse'] = $userId;
            }
        }

        return $partyUserIds;
    }

    /**
     * @param  array{client: int|null, spouse: int|null}  $partyUserIds
     */
    private function representingToUserForRow(array $row, int $index, array $partyUserIds): ?int
    {
        $represents = $row['represents_party'] ?? null;
        if ($represents === 'client' || ($index === 1 && ($row['role'] ?? $row['role_id'] ?? '') === 'LEGAL_RE')) {
            return !empty($partyUserIds['client']) ? (int) $partyUserIds['client'] : null;
        }
        if ($represents === 'spouse' || ($index === 3 && ($row['role'] ?? $row['role_id'] ?? '') === 'LEGAL_RE')) {
            return !empty($partyUserIds['spouse']) ? (int) $partyUserIds['spouse'] : null;
        }

        return null;
    }

    /**
     * @param  array{client: int|null, spouse: int|null}  $partyUserIds
     * @return array<string, mixed>
     */
    private function caseUserMappingAttributesFromRow(
        array $row,
        Request $request,
        string $roleKey,
        array $partyUserIds,
        int $index = -1
    ): array {
        $role = $row[$roleKey] ?? null;
        $attributes = [
            'role_value' => $role,
            'user_status_value' => 'READY',
            'participate_in_distribution' => in_array($role, ['PL', 'DEF'], true),
            'allocated_item_count' => 0,
            'allocated_value' => 0,
            'value_difference' => 0,
            'distribution_value_cap' => $this->distributionValueCapFromRow($request, $row, $roleKey),
            'is_active' => true,
            'created_by' => Auth::id(),
            'created_date' => now(),
            'modified_by' => Auth::id(),
            'last_modified_date' => now(),
        ];

        $representingToUser = $this->representingToUserForRow($row, $index, $partyUserIds);
        if ($representingToUser !== null) {
            $attributes['representing_to_user'] = $representingToUser;
        }

        return $attributes;
    }

    /**
     * Mapping fields safe to apply when updating an existing case_user_mapping row.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function caseUserMappingUpdateAttributes(array $attributes): array
    {
        unset($attributes['created_by'], $attributes['created_date']);

        return array_merge($attributes, [
            'is_active' => true,
            'modified_by' => Auth::id(),
            'last_modified_date' => now(),
        ]);
    }

    /**
     * Preload existing users referenced in contact rows.
     *
     * @return array{by_id: \Illuminate\Support\Collection, by_email: \Illuminate\Support\Collection}
     */
    private function preloadContactUsers(array $contacts): array
    {
        $ids = collect($contacts)->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $emails = collect($contacts)
            ->pluck('email')
            ->filter()
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values();

        $byEmail = collect();
        if ($emails->isNotEmpty()) {
            // Case-insensitive match: Postgres whereIn is case-sensitive by default.
            $byEmail = User::query()
                ->where(function ($query) use ($emails) {
                    foreach ($emails as $email) {
                        $query->orWhereRaw('LOWER(TRIM(email)) = ?', [$email]);
                    }
                })
                ->get()
                ->keyBy(fn (User $user) => strtolower(trim((string) $user->email)));
        }

        return [
            'by_id' => $ids->isNotEmpty()
                ? User::whereIn('id', $ids)->get()->keyBy('id')
                : collect(),
            'by_email' => $byEmail,
        ];
    }

    private function resolveContactUser(array $row, array $preloaded): ?User
    {
        $email = strtolower(trim((string) ($row['email'] ?? '')));

        if (!empty($row['user_id'])) {
            $user = $preloaded['by_id']->get((int) $row['user_id']);
            if ($user) {
                $userEmail = strtolower(trim((string) $user->email));
                if ($email !== '' && $userEmail !== $email) {
                    $user = null;
                }
            }

            if ($user) {
                return $user;
            }
        }

        if ($email === '') {
            return null;
        }

        $user = $preloaded['by_email']->get($email);
        if ($user) {
            return $user;
        }

        return User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();
    }

    private function normalizeContactPhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', trim($phone));

        return $digits !== '' ? $digits : null;
    }

    private function findExistingUserByEmailOrPhone(string $emailKey, ?string $phone): ?User
    {
        if ($emailKey !== '') {
            $user = User::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$emailKey])
                ->first();
            if ($user) {
                return $user;
            }
        }

        $phone = $this->normalizeContactPhone($phone);
        if ($phone !== null && $phone !== '') {
            return User::query()->where('phone_number', $phone)->first();
        }

        return null;
    }

    private function uniqueConstraintContactMessage(\Illuminate\Database\UniqueConstraintViolationException $e): string
    {
        $detail = $e->getMessage();

        if (str_contains($detail, 'users_phone_number_key')) {
            return 'That phone number is already registered to another user. Search for the existing user or enter a different phone number.';
        }

        if (str_contains($detail, 'users_email_key')) {
            return 'That email is already registered. Search for the existing user instead of entering duplicate contact details.';
        }

        if (str_contains($detail, 'uq_case_user_mapping')) {
            return 'Each person can only be added once per case. Search for the user if they are already listed on this case.';
        }

        return 'Unable to save contact because a duplicate record was detected.';
    }

    /**
     * Find an existing user by id/email or create one. Skips creating when the email already exists (case-insensitive).
     */
    private function casePartyRoleLabel(?string $roleValue): string
    {
        return match ($roleValue) {
            'PL' => 'Client',
            'DEF' => 'Spouse',
            'LEGAL_RE' => 'Legal Representative',
            default => 'Participant',
        };
    }

    private function partyUserDisplayName(?int $userId): ?string
    {
        if (!$userId) {
            return null;
        }

        $name = trim((string) User::query()->whereKey($userId)->value('name'));

        return $name !== '' ? $name : null;
    }

    /**
     * @param  array<int, array{user_id: int, role_value: string}>  $queue
     */
    private function queueCasePartyInvitation(array &$queue, int $userId, ?string $roleValue): void
    {
        if ($userId <= 0 || $userId === (int) Auth::id()) {
            return;
        }

        $queue[$userId] = [
            'user_id' => $userId,
            'role_value' => (string) ($roleValue ?? ''),
        ];
    }

    /**
     * @param  array<int, true>  $usersOnCaseBefore
     * @param  array<int, array{user_id: int, role_value: string}>  $queue
     */
    private function maybeQueueCasePartyInvitation(
        array &$queue,
        array $usersOnCaseBefore,
        int $userId,
        ?string $roleValue
    ): void {
        if (isset($usersOnCaseBefore[$userId])) {
            return;
        }

        $this->queueCasePartyInvitation($queue, $userId, $roleValue);
    }

    /**
     * @param  array<int, array{user_id: int, role_value: string}>  $invitations
     * @param  array{client: int|null, spouse: int|null}  $partyUserIds
     */
    private function sendCasePartyInvitationEmails(
        CourtCase $case,
        array $invitations,
        array $partyUserIds,
        ?User $legalCounsel
    ): void {
        if ($invitations === []) {
            return;
        }

        $clientName = $this->partyUserDisplayName($partyUserIds['client'] ?? null);
        $spouseName = $this->partyUserDisplayName($partyUserIds['spouse'] ?? null);
        $counselName = trim((string) ($legalCounsel?->name ?? ''));
        if ($counselName === '') {
            $counselName = trim((string) ($legalCounsel?->email ?? 'Your legal representative'));
        }
        if ($counselName === '') {
            $counselName = 'Your legal representative';
        }

        $joinUrl = route('admin.login');

        foreach ($invitations as $invitation) {
            $user = User::query()->find((int) ($invitation['user_id'] ?? 0));
            if (!$user || trim((string) $user->email) === '') {
                continue;
            }

            $recipientName = trim((string) ($user->name ?? ''));
            if ($recipientName === '') {
                $recipientName = $user->email;
            }

            try {
                Mail::to($user->email)->send(new CasePartyInvitationMail(
                    recipientName: $recipientName,
                    caseNumber: (string) $case->case_number,
                    legalCounselName: $counselName,
                    roleLabel: $this->casePartyRoleLabel($invitation['role_value'] ?? null),
                    clientName: $clientName,
                    spouseName: $spouseName,
                    joinUrl: $joinUrl,
                ));
            } catch (\Throwable $e) {
                Log::warning('Failed to send case party invitation email', [
                    'case_id' => $case->id,
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function rejectContactPhoneEmailMismatch(User $user, string $emailKey, ?string $validationKey): void
    {
        if ($emailKey === '') {
            return;
        }

        $existingEmail = strtolower(trim((string) $user->email));
        if ($existingEmail === $emailKey) {
            return;
        }

        $ownerLabel = trim((string) ($user->name ?? ''));
        if ($ownerLabel === '') {
            $ownerLabel = (string) $user->email;
        }

        $field = $validationKey !== null && $validationKey !== ''
            ? $validationKey . '.phone'
            : 'phone';

        throw \Illuminate\Validation\ValidationException::withMessages([
            $field => 'That phone number is already registered to ' . $ownerLabel . '. Enter a different phone number or search for the existing user.',
        ]);
    }

    private function findOrCreateContactUser(
        array $row,
        array &$preloaded,
        ?User $loggedUser,
        string $roleKey,
        ?string $validationKey = null
    ): User {
        $user = $this->resolveContactUser($row, $preloaded);
        if ($user) {
            return $user;
        }

        $email = trim((string) ($row['email'] ?? ''));
        if ($email === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Either choose an existing user or enter email, name and phone.',
            ]);
        }

        $emailKey = strtolower($email);
        $user = $this->findExistingUserByEmailOrPhone($emailKey, $row['phone'] ?? null);

        if ($user) {
            $this->rejectContactPhoneEmailMismatch($user, $emailKey, $validationKey);
            $preloaded['by_id']->put($user->id, $user);
            $preloaded['by_email']->put(strtolower(trim((string) $user->email)), $user);

            return $user;
        }

        $savepoint = $this->uniqueSavepointName('contact_user_create');
        $this->createDbSavepoint($savepoint);

        try {
            $user = User::create([
                'email' => $email,
                'name' => $row['name'] ?? '',
                'phone_number' => $this->normalizeContactPhone($row['phone'] ?? null),
                'password' => md5('12345'),
                'preferred_language' => 'en',
                'is_active' => true,
                'created_by' => Auth::id(),
                'created_date' => now(),
                'modified_by' => Auth::id(),
                'last_modified_date' => now(),
            ]);
            $this->releaseDbSavepoint($savepoint);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $this->rollbackDbSavepoint($savepoint);
            $user = $this->findExistingUserByEmailOrPhone($emailKey, $row['phone'] ?? null);
            if (!$user) {
                $field = $validationKey !== null && $validationKey !== ''
                    ? $validationKey . '.email'
                    : 'email';
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $field => $this->uniqueConstraintContactMessage($e),
                ]);
            }
            $this->rejectContactPhoneEmailMismatch($user, $emailKey, $validationKey);
            $preloaded['by_id']->put($user->id, $user);
            $preloaded['by_email']->put(strtolower(trim((string) $user->email)), $user);

            return $user;
        }

        $roleValue = $row[$roleKey] ?? null;
        $isEndClient = in_array($roleValue, ['PL', 'DEF'], true);
        $tenantId = $isEndClient ? 1 : ($loggedUser->tenant_id ?? null);
        if (!$isEndClient && $roleValue !== '' && $tenantId === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'users' => 'Unable to assign this role because your account tenant could not be determined.',
            ]);
        }

        $this->ensureUserRoleMapping(
            $user->id,
            $isEndClient ? 'EC' : (string) $roleValue,
            $tenantId
        );

        $preloaded['by_id']->put($user->id, $user);
        $preloaded['by_email']->put($emailKey, $user);

        return $user;
    }

    /**
     * Ensure a user_role_mapping row exists; skip if this user already has that role.
     */
    private function ensureUserRoleMapping(int $userId, string $roleValue, $tenantId): void
    {
        if ($roleValue === '') {
            return;
        }

        $exists = UserRoleMapping::where('user_id', $userId)
            ->where('role_value', $roleValue)
            ->exists();
        if ($exists) {
            return;
        }

        $savepoint = $this->uniqueSavepointName('user_role_mapping');
        $this->createDbSavepoint($savepoint);

        try {
            UserRoleMapping::create([
                'user_id' => $userId,
                'role_value' => $roleValue,
                'tenant_id' => $tenantId,
                'is_active' => true,
                'created_by' => Auth::id(),
                'created_date' => now(),
                'modified_by' => Auth::id(),
                'last_modified_date' => now(),
            ]);
            $this->releaseDbSavepoint($savepoint);
        } catch (\Illuminate\Database\QueryException $e) {
            $this->rollbackDbSavepoint($savepoint);

            if ($e instanceof \Illuminate\Database\UniqueConstraintViolationException) {
                return;
            }

            throw $e;
        }
    }

    /**
     * Create a case_user_mapping row only if this user is not already on the case.
     */
    private function createCaseUserMappingIfAbsent(int $caseId, int $userId, array $attributes, array &$mappedUserIds): bool
    {
        $existing = CaseUserMapping::where('case_id', $caseId)->where('user_id', $userId)->first();
        if ($existing) {
            $mappedUserIds[$userId] = true;
            if ($this->shouldMergeExistingCaseUserMapping($existing, $attributes)) {
                $existing->update($this->caseUserMappingUpdateAttributes($attributes));

                return true;
            }

            return false;
        }

        if (isset($mappedUserIds[$userId])) {
            return false;
        }

        $savepoint = $this->uniqueSavepointName('case_user_mapping');
        $this->createDbSavepoint($savepoint);

        try {
            CaseUserMapping::create(array_merge([
                'case_id' => $caseId,
                'user_id' => $userId,
            ], $attributes));
            $this->releaseDbSavepoint($savepoint);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $this->rollbackDbSavepoint($savepoint);

            $existing = CaseUserMapping::where('case_id', $caseId)->where('user_id', $userId)->first();
            if ($existing) {
                $mappedUserIds[$userId] = true;
                $existing->update($this->caseUserMappingUpdateAttributes($attributes));

                return true;
            }

            throw \Illuminate\Validation\ValidationException::withMessages([
                'users' => $this->uniqueConstraintContactMessage($e),
            ]);
        }

        $mappedUserIds[$userId] = true;

        return true;
    }

    /**
     * Soft-delete case users removed from the edit form (never Client or Spouse).
     */
    private function softDeleteRemovedCaseUsers(CourtCase $case, array $submittedMappingIds): void
    {
        $query = CaseUserMapping::query()
            ->where('case_id', $case->id)
            ->active()
            ->whereNotIn('role_value', ['PL', 'DEF']);

        if (count($submittedMappingIds) > 0) {
            $query->whereNotIn('id', $submittedMappingIds);
        }

        $query->update([
            'is_active' => false,
            'modified_by' => Auth::id(),
            'last_modified_date' => now(),
        ]);
    }

    /**
     * Client and Spouse cannot be removed once saved on the case.
     */
    private function rejectRemovedCaseParties($validator, CourtCase $case, Request $request): void
    {
        $submittedMappingIds = collect($request->users ?? [])
            ->pluck('mapping_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $requiredPartyIds = CaseUserMapping::query()
            ->where('case_id', $case->id)
            ->whereIn('role_value', ['PL', 'DEF'])
            ->active()
            ->pluck('id')
            ->all();

        foreach ($requiredPartyIds as $partyId) {
            if (!in_array($partyId, $submittedMappingIds, true)) {
                $validator->errors()->add('users', 'Client and Spouse cannot be removed from a case.');
                return;
            }
        }
    }

    /**
     * Saved Client and Spouse mappings must keep their party role and identity.
     */
    private function rejectChangedCaseParties($validator, CourtCase $case, Request $request): void
    {
        if (!is_array($request->users)) {
            return;
        }

        $partyMappings = CaseUserMapping::query()
            ->with('user')
            ->where('case_id', $case->id)
            ->whereIn('role_value', ['PL', 'DEF'])
            ->active()
            ->get()
            ->keyBy('id');

        foreach ($request->users as $index => $user) {
            $mappingId = (int) ($user['mapping_id'] ?? 0);
            if ($mappingId <= 0 || !$partyMappings->has($mappingId)) {
                continue;
            }

            $mapping = $partyMappings->get($mappingId);
            $expectedRole = $mapping->role_value;
            if (($user['role'] ?? '') !== $expectedRole) {
                $validator->errors()->add(
                    "users.$index.role",
                    'Client and Spouse roles cannot be changed after they are saved on the case.'
                );
            }

            $expectedUserId = (int) $mapping->user_id;
            $submittedUserId = (int) ($user['user_id'] ?? 0);
            if ($submittedUserId > 0 && $submittedUserId !== $expectedUserId) {
                $validator->errors()->add(
                    "users.$index.name",
                    'Client and Spouse cannot be changed after they are saved on the case.'
                );
                continue;
            }

            $expectedName = trim((string) ($mapping->user->name ?? ''));
            if ($expectedName !== '' && trim((string) ($user['name'] ?? '')) !== $expectedName) {
                $validator->errors()->add(
                    "users.$index.name",
                    'Client and Spouse name cannot be changed after they are saved on the case.'
                );
            }
        }
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

    private function assertCanAdjustDistribution(CourtCase $case): void
    {
        if (!$case->canLegalRepresentativeAdjustDistribution()) {
            abort(403, 'This case is not eligible for distribution adjustment.');
        }
    }

    private function canCurrentAttorneyCloseCase(CourtCase $case): bool
    {
        if ($case->case_status_value !== 'PEND_APP') {
            return false;
        }

        $isAttorney = CaseUserMapping::query()
            ->where('case_id', $case->id)
            ->where('user_id', Auth::id())
            ->where('role_value', 'LEGAL_RE')
            ->where('is_active', true)
            ->exists();

        if (!$isAttorney) {
            return false;
        }

        if ($case->distribute_by_client !== true) {
            return true;
        }

        $parties = CaseUserMapping::query()
            ->where('case_id', $case->id)
            ->whereIn('role_value', ['PL', 'DEF'])
            ->where('is_active', true);

        $hasUnreadyParty = (clone $parties)
            ->where(function ($query) {
                $query->where(function ($participationQuery) {
                    $participationQuery->whereNull('participate_in_distribution')
                        ->orWhere('participate_in_distribution', false);
                })
                    ->orWhereNull('user_status_value')
                    ->orWhere('user_status_value', '<>', 'C_CLOSE');
            })
            ->exists();

        return (clone $parties)->exists() && !$hasUnreadyParty;
    }

    public function create()
    {
        $role = DB::table('data_element')
            ->whereIn('category_id', [2])
            ->where('is_active', true)
            ->where('value', '!=', 'DEL')
            ->orderBy('name')
            ->get();

        $distributionMethods = $this->distributionMethods();

        $partyForm = $this->organizeContactsForPartyForm(old('contacts', []));
        $partySlots = $partyForm['partySlots'];
        $additionalContacts = $partyForm['additionalContacts'];

        return view('backend.cases.create', compact(
            'role',
            'distributionMethods',
            'partySlots',
            'additionalContacts'
        ));
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
    /**
     * Map legal hold fields from the request.
     */
    private function legalHoldAttributesFromRequest(Request $request): array
    {
        return [
            'is_legal_hold' => (bool) $request->boolean('is_legal_hold', false),
            'legal_hold_reason' => $request->filled('legal_hold_reason') ? $request->legal_hold_reason : null,
            'legal_hold_start_date' => $request->filled('legal_hold_start_date') ? $request->legal_hold_start_date : null,
            'legal_hold_end_date' => $request->filled('legal_hold_end_date') ? $request->legal_hold_end_date : null,
        ];
    }

    /**
     * Validation rules for case update, respecting status-based field locks.
     */
    private function caseUpdateValidationRules(CourtCase $case): array
    {
        $locks = $case->caseEditLockFlags();

        if ($locks['legal_hold_only']) {
            return [
                'is_legal_hold' => 'nullable|boolean',
                'legal_hold_reason' => 'nullable|string|max:4000',
                'legal_hold_start_date' => 'nullable|date',
                'legal_hold_end_date' => 'nullable|date|after_or_equal:legal_hold_start_date',
            ];
        }

        $rules = array_merge([
            'case_description' => 'nullable|string',
            'is_legal_hold' => 'nullable|boolean',
            'legal_hold_reason' => 'nullable|string|max:4000',
            'legal_hold_start_date' => 'nullable|date',
            'legal_hold_end_date' => 'nullable|date|after_or_equal:legal_hold_start_date',
            'users' => 'nullable|array',
            'users.*.email' => 'nullable|email|max:255',
            'users.*.name' => 'nullable|string|max:255',
            'users.*.phone' => 'nullable|string|max:20',
            'users.*.user_id' => 'nullable|integer|exists:users,id',
            'users.*.mapping_id' => 'nullable|integer',
            'users.*.role' => 'required|string|exists:data_element,value|not_in:DEL',
            'users.*.distribution_value_cap' => 'nullable|numeric|min:0',
        ], $this->distributionFieldRules($locks));

        if (!$locks['distribution_config']) {
            $rules['asset_sla_in_days'] = 'required|integer|min:0';
            $rules['max_number_of_arbitation_per_user'] = 'required|integer|min:0';
        }

        return $rules;
    }

    /**
     * Build persisted case attributes for update, preserving locked fields.
     */
    private function buildCaseUpdateAttributes(CourtCase $case, Request $request): array
    {
        $base = [
            'modified_by' => Auth::id(),
            'last_modified_date' => now(),
        ];

        $locks = $case->caseEditLockFlags();

        if ($locks['legal_hold_only']) {
            return array_merge($base, $this->legalHoldAttributesFromRequest($request));
        }

        $attributes = array_merge($base, $this->legalHoldAttributesFromRequest($request), [
            'case_description' => $request->case_description,
        ]);

        if (!$locks['distribution_config']) {
            $attributes['asset_sla_in_days'] = (int) $request->asset_sla_in_days;
            $attributes['max_number_of_arbitation_per_user'] = (int) $request->max_number_of_arbitation_per_user;
            $attributes = array_merge($attributes, $this->distributionAttributesFromRequest($request, $locks));
        } elseif (!$locks['distribution_attempts']) {
            $attributes['max_number_of_distribution_attempts'] = (int) $request->max_number_of_distribution_attempts;
        }

        return $attributes;
    }

    /**
     * Reject attempts to change identity fields that are fixed after case creation.
     */
    private function rejectChangedIdentityFields($validator, CourtCase $case, Request $request): void
    {
        if ($request->has('case_number') && (string) $request->case_number !== (string) $case->case_number) {
            $validator->errors()->add('case_number', 'Case number cannot be changed after the case is created.');
        }

        if ($request->has('sla_deadline')) {
            $existingDeadline = $case->sla_deadline ? $case->sla_deadline->format('Y-m-d') : '';
            if ((string) $request->sla_deadline !== $existingDeadline) {
                $validator->errors()->add('sla_deadline', 'SLA deadline cannot be changed after the case is created.');
            }
        }
    }

    private function distributionFieldRules(?array $locks = null): array
    {
        $rules = [
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

        if ($locks !== null) {
            if ($locks['distribution_config'] ?? false) {
                unset($rules['distribution_sla_in_days'], $rules['distribution_method'], $rules['asset_distributed_by']);
            }
            if ($locks['distribution_attempts'] ?? false) {
                unset($rules['max_number_of_distribution_attempts']);
            }
        }

        return $rules;
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
    private function distributionAttributesFromRequest(Request $request, ?array $locks = null): array
    {
        $attributes = [
            'distribution_sla_in_days' => $request->filled('distribution_sla_in_days')
                ? (int) $request->distribution_sla_in_days
                : null,
            'max_number_of_distribution_attempts' => (int) $request->max_number_of_distribution_attempts,
            'distribution_method_value' => $request->distribution_method,
            'distribute_by_client' => $this->distributeByClientFromRequest($request),
        ];

        if ($locks !== null) {
            if ($locks['distribution_config'] ?? false) {
                unset($attributes['distribution_sla_in_days'], $attributes['distribution_method_value'], $attributes['distribute_by_client']);
            }
            if ($locks['distribution_attempts'] ?? false) {
                unset($attributes['max_number_of_distribution_attempts']);
            }
        }

        return $attributes;
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
            'case_description' => 'nullable|string',
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
            $contacts = $this->normalizePartyContactRows($request->input('contacts', []));
            if ($contacts === []) {
                $validator->errors()->add('contacts', 'At least one contact is required.');
                return;
            }
            foreach ($contacts as $i => $c) {
                $hasUserId = !empty($c['user_id']);
                if (!$hasUserId && (empty($c['email']) || empty($c['name']) || empty($c['phone']))) {
                    $validator->errors()->add("contacts.$i.email", 'Either choose an existing employee or enter email, name and phone.');
                }
            }
            $plaintiffCount = collect($contacts)->where('role_id', 'PL')->count();
            $defendantCount = collect($contacts)->where('role_id', 'DEF')->count();
            if ($plaintiffCount !== 1) {
                $validator->errors()->add('contacts', 'Exactly one Client is required.');
            }
            if ($defendantCount !== 1) {
                $validator->errors()->add('contacts', 'Exactly one Spouse is required.');
            }
            $legalReCount = collect($contacts)->where('role_id', 'LEGAL_RE')->count();
            if ($legalReCount < 1) {
                $validator->errors()->add('contacts', 'At least one attorney (Legal Representative) is required for the Client.');
            }
            if (in_array($request->distribution_method, ['DIST_FCP', 'DIST_CAP'], true)) {
                foreach ($contacts as $i => $contact) {
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

        $contacts = $this->normalizePartyContactRows($request->input('contacts', []));

        // Check loggedin user
        $loggedUser = $this->currentLogUser();

        try {
            DB::beginTransaction();

            $case = CourtCase::create(array_merge([
                'case_number'      => $request->case_number,
                'case_type_value'  => self::DEFAULT_CASE_TYPE_VALUE,
                'case_status_value' => 'C_NEW',
                'case_description' => $request->case_description,
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
            $creatorAlreadyLegalRe = collect($contacts)->contains(function ($c) {
                $isCreator = (isset($c['user_id']) && (int) $c['user_id'] === (int) Auth::id())
                    || (isset($c['email']) && strcasecmp(trim($c['email'] ?? ''), Auth::user()->email ?? '') === 0);
                return $isCreator && ($c['role_id'] ?? '') === 'LEGAL_RE';
            });
            $mappedUserIds = [];
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
                $mappedUserIds[(int) Auth::id()] = true;
            }

            $preloadedUsers = $this->preloadContactUsers($contacts);
            $partyUserIds = $this->resolvePartyUserIdsFromContactRows(
                $contacts,
                'role_id',
                $preloadedUsers,
                $loggedUser
            );

            $invitationQueue = [];
            foreach ($contacts as $index => $row) {
                try {
                    $user = $this->findOrCreateContactUser($row, $preloadedUsers, $loggedUser, 'role_id', 'contacts.' . $index);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    throw $e;
                }

                $userId = (int) $user->id;
                if (($row['role_id'] ?? '') === 'PL') {
                    $partyUserIds['client'] = $userId;
                }
                if (($row['role_id'] ?? '') === 'DEF') {
                    $partyUserIds['spouse'] = $userId;
                }

                if ($this->createCaseUserMappingIfAbsent(
                    (int) $case->id,
                    $userId,
                    $this->caseUserMappingAttributesFromRow($row, $request, 'role_id', $partyUserIds, (int) $index),
                    $mappedUserIds
                )) {
                    $this->queueCasePartyInvitation($invitationQueue, $userId, $row['role_id'] ?? null);
                }
            }

            DB::commit();
            $this->sendCasePartyInvitationEmails($case, $invitationQueue, $partyUserIds, $loggedUser);
            return redirect()->route('admin.cases.index')->with('success', 'Case created successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            DB::rollBack();

            throw \Illuminate\Validation\ValidationException::withMessages([
                'users' => $this->uniqueConstraintContactMessage($e),
            ]);
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
            ->active()
            ->whereIn('role_value', ['SAAS_ADM', 'TENANT_A', 'DEF', 'DEL', 'LEGAL_RE', 'PL'])
            ->get();
        
        $role = DB::table('data_element')
            ->whereIn('category_id', [2])
            ->where('is_active', true)
            ->where('value', '!=', 'DEL')
            ->orderBy('name')
            ->get();

        $distributionMethods = $this->distributionMethods();

        $caseEditLocks = $case->caseEditLockFlags();

        $partyForm = $this->organizeContactsForPartyForm(old('users', []), $caseUsers);
        $partySlots = $partyForm['partySlots'];
        $additionalContacts = $partyForm['additionalContacts'];

        return view('backend.cases.edit', compact(
            'case',
            'caseUsers',
            'role',
            'distributionMethods',
            'caseEditLocks',
            'partySlots',
            'additionalContacts'
        ));
    }


    /**
     * Update case
     */
    public function update(Request $request, $id)
    {
        $case = $this->findAccessibleCase($id);
        $locks = $case->caseEditLockFlags();

        $validator = \Illuminate\Support\Facades\Validator::make(
            $request->all(),
            $this->caseUpdateValidationRules($case)
        );

        $validator->after(function ($validator) use ($request, $locks, $case) {
            if ($locks['legal_hold_only']) {
                return;
            }

            if ($locks['identity']) {
                $this->rejectChangedIdentityFields($validator, $case, $request);
            }

            $this->rejectRemovedCaseParties($validator, $case, $request);
            $this->rejectChangedCaseParties($validator, $case, $request);

            if (!is_array($request->users)) {
                return;
            }

            $users = $this->normalizePartyContactRows($request->input('users', []));
            foreach ($users as $i => $user) {
                $hasUserId = !empty($user['user_id']);
                if (!$hasUserId && (empty($user['email']) || empty($user['name']) || empty($user['phone']))) {
                    $validator->errors()->add(
                        "users.$i.email",
                        'Either choose an existing user or enter email, name and phone.'
                    );
                }
            }

            $plaintiffCount = collect($users)->where('role', 'PL')->count();
            $defendantCount = collect($users)->where('role', 'DEF')->count();
            if ($plaintiffCount !== 1) {
                $validator->errors()->add('users', 'Exactly one Client is required.');
            }
            if ($defendantCount !== 1) {
                $validator->errors()->add('users', 'Exactly one Spouse is required.');
            }
            $legalReCount = collect($users)->where('role', 'LEGAL_RE')->count();
            if ($legalReCount < 1) {
                $validator->errors()->add('users', 'At least one attorney (Legal Representative) is required for the Client.');
            }
            if (
                !$locks['distribution_config']
                && in_array($request->distribution_method, ['DIST_FCP', 'DIST_CAP'], true)
            ) {
                foreach ($users as $i => $user) {
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

        $loggedUser = $this->currentLogUser();
        $users = $this->normalizePartyContactRows($request->input('users', []));

        try {
            DB::beginTransaction();

            /** Update Parent Case */
            $case->update($this->buildCaseUpdateAttributes($case, $request));

            if ($locks['legal_hold_only']) {
                DB::commit();
                return redirect()->route('admin.cases.index')->with('success', 'Case updated successfully');
            }

            /** Get submitted mapping IDs */
            $submittedMappingIds = collect($users)
                ->pluck('mapping_id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->toArray();

            /** Soft-delete removed rows (never Client or Spouse) */
            $usersOnCaseBefore = CaseUserMapping::query()
                ->where('case_id', $case->id)
                ->active()
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->flip()
                ->all();

            $this->softDeleteRemovedCaseUsers($case, $submittedMappingIds);

            $preloadedUsers = $this->preloadContactUsers(
                collect($users)->map(fn ($row) => [
                    'user_id' => $row['user_id'] ?? null,
                    'email' => $row['email'] ?? null,
                ])->all()
            );

            $mappedUserIds = CaseUserMapping::where('case_id', $case->id)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->flip()
                ->all();

            $partyUserIds = $this->resolvePartyUserIdsFromContactRows(
                $users,
                'role',
                $preloadedUsers,
                $loggedUser,
                $case
            );

            /** Save / Update Users & Mappings */
            $invitationQueue = [];
            foreach ($users as $rowIndex => $row) {
                $mappingId = $row['mapping_id'] ?? null;

                if ($mappingId) {
                    $existingMapping = CaseUserMapping::query()
                        ->where('id', $mappingId)
                        ->where('case_id', $case->id)
                        ->first();

                    if ($existingMapping && in_array($existingMapping->role_value, ['PL', 'DEF'], true)) {
                        if ($existingMapping->role_value === 'PL') {
                            $partyUserIds['client'] = (int) $existingMapping->user_id;
                        }
                        if ($existingMapping->role_value === 'DEF') {
                            $partyUserIds['spouse'] = (int) $existingMapping->user_id;
                        }

                        CaseUserMapping::where('id', $mappingId)->update([
                            'distribution_value_cap' => $this->distributionValueCapFromRow($request, $row, 'role'),
                            'is_active' => true,
                            'modified_by' => Auth::id(),
                            'last_modified_date' => now(),
                        ]);
                        $mappedUserIds[(int) $existingMapping->user_id] = true;
                        continue;
                    }
                }

                try {
                    $user = $this->findOrCreateContactUser(
                        $row,
                        $preloadedUsers,
                        $loggedUser,
                        'role',
                        'users.' . $rowIndex
                    );
                } catch (\Illuminate\Validation\ValidationException $e) {
                    throw $e;
                }

                $userId = (int) $user->id;
                if (($row['role'] ?? '') === 'PL') {
                    $partyUserIds['client'] = $userId;
                }
                if (($row['role'] ?? '') === 'DEF') {
                    $partyUserIds['spouse'] = $userId;
                }

                $mappingAttributes = $this->caseUserMappingAttributesFromRow(
                    $row,
                    $request,
                    'role',
                    $partyUserIds,
                    (int) $rowIndex
                );

                /** Update existing mapping */
                if ($mappingId) {
                    $conflictMapping = CaseUserMapping::where('case_id', $case->id)
                        ->where('user_id', $userId)
                        ->where('id', '!=', $mappingId)
                        ->first();

                    if ($conflictMapping) {
                        // User is already on the case — merge counsel onto their existing mapping.
                        $conflictMapping->update($this->caseUserMappingUpdateAttributes($mappingAttributes));
                        $this->deactivateCaseUserMapping((int) $case->id, (int) $mappingId);
                        $mappedUserIds[$userId] = true;
                        continue;
                    }

                    CaseUserMapping::where('id', $mappingId)->update(array_merge(
                        $this->caseUserMappingUpdateAttributes($mappingAttributes),
                        ['user_id' => $user->id]
                    ));
                    $mappedUserIds[$userId] = true;
                    $this->maybeQueueCasePartyInvitation(
                        $invitationQueue,
                        $usersOnCaseBefore,
                        $userId,
                        $row['role'] ?? null
                    );
                    continue;
                }

                if ($this->createCaseUserMappingIfAbsent((int) $case->id, $userId, $mappingAttributes, $mappedUserIds)) {
                    $this->maybeQueueCasePartyInvitation(
                        $invitationQueue,
                        $usersOnCaseBefore,
                        $userId,
                        $row['role'] ?? null
                    );
                }
            }

            DB::commit();
            $this->sendCasePartyInvitationEmails($case, $invitationQueue, $partyUserIds, $loggedUser);
            return redirect()->route('admin.cases.index')->with('success', 'Case updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            DB::rollBack();

            throw \Illuminate\Validation\ValidationException::withMessages([
                'users' => $this->uniqueConstraintContactMessage($e),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['users' => 'Unable to update case: ' . $e->getMessage()])
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

            if (!$case->canBeDeleted()) {
                return back()->with('error', 'This case cannot be deleted because it is closed or pending closure.');
            }

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
            ->whereNull('case_activity.item_id')
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
            ->active()
            ->firstOrFail();

        if (in_array($mapping->role_value, ['PL', 'DEF'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'Client and Spouse cannot be removed from a case.',
            ], 422);
        }

        $mapping->update([
            'is_active' => false,
            'modified_by' => Auth::id(),
            'last_modified_date' => now(),
        ]);

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
            'id' => $item->id,
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

    private function formatCaseAssetDetail(Item $item, $labels): array
    {
        $row = $this->formatCaseAssetRow($item, $labels, 0);
        unset($row['index']);

        $hasImage = filled($item->images);
        $row['description'] = $item->description ?: '—';
        $row['notes'] = $item->notes ?: '—';
        $row['tags'] = $item->tags ?: '—';
        $row['model'] = $item->model ?: '—';
        $row['serial_number'] = $item->serial_number ?: '—';
        $row['quantity'] = $item->quantity ?? 1;
        $row['links'] = $item->links ?: '—';
        $row['has_image'] = $hasImage;
        $row['image_url'] = $hasImage
            ? route('admin.cases.assets.image', ['id' => $item->case_id, 'itemId' => $item->id])
            : null;

        return $row;
    }

    private function caseAssetFilterOptions(int $caseId): array
    {
        $baseQuery = Item::query()->where('case_id', $caseId)->where('is_active', true);

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
