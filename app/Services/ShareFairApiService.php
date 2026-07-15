<?php

namespace App\Services;

use App\Models\CourtCase;
use App\Models\Item;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ShareFairApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 500,
        public readonly ?array $body = null,
    ) {
        parent::__construct($message);
    }
}

class ShareFairApiService
{
    public function baseUrl(): string
    {
        return rtrim((string) config('services.sharefair.api_base_url'), '/');
    }

    public function token(): ?string
    {
        return session('sharefair_api_token');
    }

    public function getDistributePreview(int $caseId): array
    {
        $token = $this->token();
        if (!$token) {
            throw new ShareFairApiException('API session expired. Please sign in again.', 401);
        }

        $cacheKey = sprintf(
            'sharefair:distribute_preview:%d:%s',
            $caseId,
            substr(hash('sha256', $token), 0, 16)
        );
        $ttl = (int) config('services.sharefair.preview_cache_seconds', 60);

        if ($cached = Cache::get($cacheKey)) {
            return $this->normalizeDistributePreviewPayload($caseId, $cached);
        }

        $payload = $this->request(
            fn (PendingRequest $client) => $client->get("/cases/{$caseId}/items/distribute/preview")
        );

        $payload = $this->normalizeDistributePreviewPayload($caseId, $payload);

        Cache::put($cacheKey, $payload, $ttl);

        return $payload;
    }

    /**
     * Ensure summary stats use the keys expected by the UI and exports.
     * The API may omit these or use case-table field names instead.
     */
    public function normalizeDistributePreviewPayload(int $caseId, array $payload): array
    {
        $case = CourtCase::query()->find($caseId);
        $hasNestedData = array_key_exists('data', $payload) && is_array($payload['data']);
        $data = $hasNestedData ? $payload['data'] : $payload;

        $itemCount = $data['item_count']
            ?? $data['total_items_count']
            ?? $data['items_count']
            ?? null;

        $totalValue = $data['total_value']
            ?? $data['total_items_value']
            ?? null;

        $totalUsers = $data['total_users'] ?? null;

        $targetPerUser = $data['target_value_per_user'] ?? null;

        if ($case) {
            $itemCount ??= $case->total_items_count;
            $totalValue ??= $case->total_items_value;
            $totalUsers ??= $case->total_users;
            if ($targetPerUser === null || $targetPerUser === '' || (float) $targetPerUser === 0.0) {
                $targetPerUser = $case->target_value_per_user;
            }
        }

        if ($itemCount === null) {
            $itemCount = $this->countPreviewItems($data);
        }

        if ($totalValue === null) {
            $totalValue = $this->sumPreviewItemValues($data);
        }

        if ($totalUsers === null) {
            $totalUsers = is_array($data['allocations'] ?? null)
                ? count($data['allocations'])
                : null;
        }

        $data['item_count'] = $itemCount;
        $data['total_value'] = $totalValue;
        $data['total_users'] = $totalUsers;
        $data['target_value_per_user'] = $targetPerUser;

        $data = $this->enrichPreviewItemBrands($data);

        if ($hasNestedData) {
            $payload['data'] = $data;

            return $payload;
        }

        return $data;
    }

    private function countPreviewItems(array $data): ?int
    {
        $count = 0;
        $count += $this->countItemsInAllocations($data['allocations'] ?? []);
        $count += $this->countItemsInAllocations($data['non_marital_assets'] ?? []);
        $count += count($data['dont_want_items'] ?? []);
        $count += count($data['donation_items'] ?? []);
        $count += count($data['unassigned_items'] ?? []);
        $count += count($data['adjust_available_items'] ?? []);

        return $count > 0 ? $count : null;
    }

    private function countItemsInAllocations(array $allocations): int
    {
        $count = 0;

        foreach ($allocations as $allocation) {
            if (!is_array($allocation)) {
                continue;
            }

            $count += count($allocation['items'] ?? []);
        }

        return $count;
    }

    private function sumPreviewItemValues(array $data): ?float
    {
        $total = 0.0;
        $found = false;

        foreach ([
            $data['allocations'] ?? [],
            $data['non_marital_assets'] ?? [],
            $data['dont_want_items'] ?? [],
            $data['donation_items'] ?? [],
            $data['unassigned_items'] ?? [],
            $data['adjust_available_items'] ?? [],
        ] as $section) {
            if (!is_array($section)) {
                continue;
            }

            foreach ($section as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                if (array_key_exists('items', $entry)) {
                    foreach ($entry['items'] as $item) {
                        $price = $this->previewItemPrice(is_array($item) ? $item : []);
                        if ($price !== null) {
                            $total += $price;
                            $found = true;
                        }
                    }
                    continue;
                }

                $price = $this->previewItemPrice($entry);
                if ($price !== null) {
                    $total += $price;
                    $found = true;
                }
            }
        }

        return $found ? round($total, 2) : null;
    }

    private function previewItemPrice(array $item): ?float
    {
        foreach (['concluded_price', 'purchase_price', 'estimated_value'] as $field) {
            if (isset($item[$field]) && $item[$field] !== '' && $item[$field] !== null) {
                return (float) $item[$field];
            }
        }

        return null;
    }

    private function enrichPreviewItemBrands(array $data): array
    {
        $brandLabels = DB::table('data_element')
            ->where('category_id', 12)
            ->where('is_active', true)
            ->pluck('name', 'value');

        $itemRecords = $this->loadPreviewItemRecords($data);

        $data['allocations'] = $this->enrichAllocationItemBrands($data['allocations'] ?? [], $brandLabels, $itemRecords);
        $data['non_marital_assets'] = $this->enrichAllocationItemBrands($data['non_marital_assets'] ?? [], $brandLabels, $itemRecords);
        $data['dont_want_items'] = $this->enrichFlatItemBrands($data['dont_want_items'] ?? [], $brandLabels, $itemRecords);
        $data['donation_items'] = $this->enrichFlatItemBrands($data['donation_items'] ?? [], $brandLabels, $itemRecords);
        $data['unassigned_items'] = $this->enrichFlatItemBrands($data['unassigned_items'] ?? [], $brandLabels, $itemRecords);
        $data['adjust_available_items'] = $this->enrichFlatItemBrands($data['adjust_available_items'] ?? [], $brandLabels, $itemRecords);

        return $data;
    }

    private function loadPreviewItemRecords(array $data): Collection
    {
        $ids = [];

        foreach ([
            $data['allocations'] ?? [],
            $data['non_marital_assets'] ?? [],
            $data['dont_want_items'] ?? [],
            $data['donation_items'] ?? [],
            $data['unassigned_items'] ?? [],
            $data['adjust_available_items'] ?? [],
        ] as $section) {
            if (!is_array($section)) {
                continue;
            }

            foreach ($section as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                if (array_key_exists('items', $entry)) {
                    foreach ($entry['items'] as $item) {
                        if ($id = $this->previewItemId(is_array($item) ? $item : [])) {
                            $ids[] = $id;
                        }
                    }
                    continue;
                }

                if ($id = $this->previewItemId($entry)) {
                    $ids[] = $id;
                }
            }
        }

        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return collect();
        }

        return Item::query()
            ->whereIn('id', $ids)
            ->get(['id', 'brand', 'other_brand'])
            ->keyBy('id');
    }

    private function previewItemId(array $item): ?int
    {
        foreach (['id', 'item_id'] as $key) {
            if (!empty($item[$key])) {
                return (int) $item[$key];
            }
        }

        return null;
    }

    private function enrichAllocationItemBrands(array $allocations, Collection $brandLabels, Collection $itemRecords): array
    {
        foreach ($allocations as $key => $allocation) {
            if (!is_array($allocation)) {
                continue;
            }

            $allocation['items'] = $this->enrichFlatItemBrands($allocation['items'] ?? [], $brandLabels, $itemRecords);
            $allocations[$key] = $allocation;
        }

        return $allocations;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function enrichFlatItemBrands(array $items, Collection $brandLabels, Collection $itemRecords): array
    {
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $displayBrand = $this->resolveItemBrandLabel($item, $brandLabels, $itemRecords);
            if ($displayBrand !== null && $displayBrand !== '') {
                $item['brand'] = $displayBrand;
            }

            $items[$index] = $item;
        }

        return $items;
    }

    private function resolveItemBrandLabel(array $item, Collection $brandLabels, Collection $itemRecords): ?string
    {
        $otherBrand = trim((string) ($item['other_brand'] ?? ''));
        $code = $item['brand'] ?? $item['brand_value'] ?? null;

        if (($code === null || $code === '') && ($record = $itemRecords->get($this->previewItemId($item) ?? 0))) {
            $code = $record->brand;
            if ($otherBrand === '') {
                $otherBrand = trim((string) ($record->other_brand ?? ''));
            }
        }

        if ($otherBrand !== '' && ($code === null || $code === '' || $this->isOtherBrandCode($code))) {
            return $otherBrand;
        }

        if ($code === null || $code === '') {
            return null;
        }

        if ($this->isOtherBrandCode($code) && $otherBrand !== '') {
            return $otherBrand;
        }

        $label = $brandLabels->get($code);
        if ($label !== null) {
            return $label;
        }

        return is_string($code) ? $code : null;
    }

    private function isOtherBrandCode(mixed $code): bool
    {
        return in_array((string) $code, ['BRD_OTHR', 'OTHER'], true);
    }

    public function distributeCase(int $caseId, ?array $assignments = null): array
    {
        $payload = $this->request(function (PendingRequest $client) use ($caseId, $assignments) {
            $request = $client->asJson();
            $body = [];
            if (!empty($assignments)) {
                $body['assignments'] = array_values($assignments);
            }

            return $request->post("/cases/{$caseId}/items/distribute", $body);
        });

        $this->forgetDistributePreviewCache($caseId);

        return $payload;
    }

    public function closeCase(int $caseId): array
    {
        $payload = $this->request(
            fn (PendingRequest $client) => $client->asJson()->post("/cases/{$caseId}/close")
        );

        $this->forgetDistributePreviewCache($caseId);

        return $payload;
    }

    /**
     * Persist attorney remapping of marital allocations while case is PEND_APP.
     *
     * @param  array<int, array{item_id: int, assigned_to_user_id: int, allocation_reason?: string}>  $assignments
     */
    public function adjustDistributedCase(int $caseId, array $assignments): array
    {
        $payload = $this->request(function (PendingRequest $client) use ($caseId, $assignments) {
            return $client->asJson()->post("/cases/{$caseId}/items/distribute/adjust", [
                'assignments' => array_values($assignments),
            ]);
        });

        $this->forgetDistributePreviewCache($caseId);

        return $this->normalizeDistributePreviewPayload($caseId, $payload);
    }

    /**
     * Stream an item image from the Share Fair API (auth + R2/legacy proxy).
     */
    public function proxyItemImage(int $caseId, int $itemId)
    {
        $token = $this->token();
        if (!$token) {
            throw new ShareFairApiException('API session expired. Please sign in again.', 401);
        }

        try {
            $token = $this->token();
            $timeout = (int) config('services.sharefair.timeout', 15);
            $response = Http::withToken($token)
                ->baseUrl($this->baseUrl())
                ->timeout($timeout)
                ->accept('*/*')
                ->get("/items/case/{$caseId}/items/{$itemId}/image");
        } catch (ConnectionException) {
            throw new ShareFairApiException('Unable to reach Share Fair API. Please try again.', 503);
        }

        if (!$response->successful()) {
            throw new ShareFairApiException(
                'Unable to load asset image.',
                $response->status() ?: 404,
                $response->json()
            );
        }

        $contentType = $response->header('Content-Type') ?: 'image/jpeg';

        return response($response->body(), 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function getAssetComments(
        int $caseId,
        int $itemId,
        int $page = 1,
        int $limit = 20,
        string $sortOrder = 'desc',
        ?string $search = null
    ): array {
        $query = [
            'item_id' => $itemId,
            'page' => $page,
            'limit' => $limit,
            'sort_order' => $sortOrder === 'asc' ? 'asc' : 'desc',
        ];
        if ($search !== null && $search !== '') {
            $query['search'] = $search;
        }

        return $this->request(
            fn (PendingRequest $client) => $client->get("/cases/{$caseId}/comments", $query)
        );
    }

    public function getCaseComments(
        int $caseId,
        int $page = 1,
        int $limit = 20,
        string $sortOrder = 'desc',
        ?string $search = null
    ): array {
        $query = [
            'page' => $page,
            'limit' => $limit,
            'sort_order' => $sortOrder === 'asc' ? 'asc' : 'desc',
        ];
        if ($search !== null && $search !== '') {
            $query['search'] = $search;
        }

        return $this->request(
            fn (PendingRequest $client) => $client->get("/cases/{$caseId}/comments", $query)
        );
    }

    public function createCaseComment(int $caseId, string $comment): array
    {
        return $this->request(
            fn (PendingRequest $client) => $client->post("/cases/{$caseId}/comments", [
                'comment' => $comment,
                'type' => 'C',
            ])
        );
    }

    public function createAssetComment(int $caseId, int $itemId, string $comment): array
    {
        return $this->request(
            fn (PendingRequest $client) => $client->post("/cases/{$caseId}/comments", [
                'comment' => $comment,
                'type' => 'A',
                'item_id' => $itemId,
            ])
        );
    }

    public function createCommentResponse(int $caseId, int $commentId, string $comment): array
    {
        return $this->request(
            fn (PendingRequest $client) => $client->post("/cases/{$caseId}/comments/{$commentId}/responses", [
                'comment' => $comment,
            ])
        );
    }

    public function getCommentResponses(
        int $caseId,
        int $commentId,
        int $page = 1,
        int $limit = 20,
        string $sortOrder = 'desc'
    ): array {
        return $this->request(
            fn (PendingRequest $client) => $client->get("/cases/{$caseId}/comments/{$commentId}/responses", [
                'page' => $page,
                'limit' => $limit,
                'sort_order' => $sortOrder === 'asc' ? 'asc' : 'desc',
            ])
        );
    }

    public function likeComment(int $caseId, int $commentId): array
    {
        return $this->request(
            fn (PendingRequest $client) => $client->post("/cases/{$caseId}/comments/{$commentId}/like")
        );
    }

    public function unlikeComment(int $caseId, int $commentId): array
    {
        return $this->request(
            fn (PendingRequest $client) => $client->delete("/cases/{$caseId}/comments/{$commentId}/like")
        );
    }

    public function getAssetActivities(int $caseId, int $itemId, int $page = 1, int $limit = 10): array
    {
        return $this->request(
            fn (PendingRequest $client) => $client->get("/case-activity/case/{$caseId}/activities", [
                'item_id' => $itemId,
                'page' => $page,
                'limit' => $limit,
                'sort_order' => 'desc',
            ])
        );
    }

    private function forgetDistributePreviewCache(int $caseId): void
    {
        $token = $this->token();
        if ($token) {
            Cache::forget(sprintf(
                'sharefair:distribute_preview:%d:%s',
                $caseId,
                substr(hash('sha256', $token), 0, 16)
            ));
        }
    }

    protected function request(callable $callback): array
    {
        try {
            return $this->handleResponse($callback($this->client()));
        } catch (ConnectionException) {
            throw new ShareFairApiException('Unable to reach Share Fair API. Please try again.', 503);
        }
    }

    protected function client(): PendingRequest
    {
        $token = $this->token();
        if (!$token) {
            throw new ShareFairApiException('API session expired. Please sign in again.', 401);
        }

        $timeout = (int) config('services.sharefair.timeout', 15);
        $retryTimes = (int) config('services.sharefair.retry_times', 2);
        $retrySleep = (int) config('services.sharefair.retry_sleep_ms', 200);

        return Http::withToken($token)
            ->acceptJson()
            ->baseUrl($this->baseUrl())
            ->timeout($timeout)
            ->retry($retryTimes, $retrySleep, throw: false);
    }

    protected function handleResponse($response): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        if ($response->status() === 401) {
            session()->forget('sharefair_api_token');
        }

        $body = $response->json();
        $message = is_array($body)
            ? ($body['message'] ?? $body['detail'] ?? 'Share Fair API request failed.')
            : 'Share Fair API request failed.';

        if (is_array($message)) {
            $message = json_encode($message);
        }

        throw new ShareFairApiException((string) $message, $response->status(), is_array($body) ? $body : null);
    }
}
