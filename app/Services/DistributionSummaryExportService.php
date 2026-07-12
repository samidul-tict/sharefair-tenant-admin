<?php

namespace App\Services;

use App\Models\CourtCase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DistributionSummaryExportService
{
    public function downloadPdf(CourtCase $case, array $data)
    {
        return $this->buildPdf($case, $data)->download($this->pdfFilename($case));
    }

    public function pdfOutput(CourtCase $case, array $data): string
    {
        return $this->buildPdf($case, $data)->output();
    }

    public function pdfFilename(CourtCase $case): string
    {
        return $this->filename($case, 'pdf');
    }

    /**
     * Persist the distribution summary PDF for queued email delivery.
     *
     * @return array{path: string, filename: string}
     */
    public function storePdf(CourtCase $case, array $data): array
    {
        $filename = $this->pdfFilename($case);
        $directory = 'distribution-summary';
        Storage::disk('local')->makeDirectory($directory);
        $path = $directory . '/' . Str::uuid() . '.pdf';
        Storage::disk('local')->put($path, $this->pdfOutput($case, $data));

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    public function downloadExcel(CourtCase $case, array $data): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Distribution Summary')
            ->setSubject($case->case_number);

        $this->buildOverviewSheet($spreadsheet->getActiveSheet(), $case, $data);
        $this->buildAllocationSheet($spreadsheet->createSheet(), 'Allocations', $data['allocations'] ?? [], $data);
        $this->buildAllocationSheet($spreadsheet->createSheet(), 'Non-marital', $data['non_marital_assets'] ?? [], $data, false);
        $this->buildItemsSheet($spreadsheet->createSheet(), "Don't Want", $data['dont_want_items'] ?? []);
        $this->buildItemsSheet($spreadsheet->createSheet(), 'Donations', $data['donation_items'] ?? []);

        $spreadsheet->setActiveSheetIndex(0);

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $this->filename($case, 'xlsx') . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function buildOverviewSheet($sheet, CourtCase $case, array $data): void
    {
        $sheet->setTitle('Overview');

        $rows = [
            ['Distribution Summary'],
            ['Case number', $case->case_number],
            ['Case type', $case->caseType?->name ?? $case->case_type_value],
            ['Status', $case->caseStatus?->name ?? $case->case_status_value],
            ['Generated', now()->format('M j, Y g:i A')],
            [],
            ['Assets', $data['item_count'] ?? '—'],
            ['Total value', $this->formatMoney($data['total_value'] ?? null)],
            ['Participants', $data['total_users'] ?? '—'],
            ['Target per user', $this->formatMoney($data['target_value_per_user'] ?? null)],
        ];

        if (!empty($data['total_donation_items_count'])) {
            $rows[] = ['Donation items', $data['total_donation_items_count']];
            $rows[] = ['Donation total value', $this->formatMoney($data['total_donation_items_value'] ?? null)];
        }

        $sheet->fromArray($rows, null, 'A1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(36);
    }

    private function buildAllocationSheet($sheet, string $title, array $allocations, array $data, bool $showTarget = true): void
    {
        $sheet->setTitle(substr($title, 0, 31));

        $headers = ['Participant', 'Role', 'Email', 'Items', 'Received value'];
        if ($showTarget) {
            $headers[] = 'Target value';
            $headers[] = 'Difference';
        }
        $headers = array_merge($headers, ['Asset name', 'Price', 'Brand', 'Allocation reason']);

        $sheet->fromArray($headers, null, 'A1');
        $this->styleHeaderRow($sheet, 'A1:' . $sheet->getHighestColumn() . '1');

        $row = 2;
        $target = (float) ($data['target_value_per_user'] ?? 0);

        foreach ($allocations as $alloc) {
            $userName = $alloc['user_name'] ?? $alloc['user_email'] ?? 'User';
            $base = [
                $userName,
                $alloc['user_role'] ?? '',
                $alloc['user_email'] ?? '',
                $alloc['allocated_item_count'] ?? 0,
                $this->formatMoney($alloc['allocated_value'] ?? null),
            ];
            if ($showTarget) {
                $base[] = $this->formatMoney($target);
                $diff = $alloc['value_difference'] ?? null;
                $base[] = $diff !== null && $diff !== '' ? $this->formatMoney($diff) : '—';
            }

            $items = $alloc['items'] ?? [];
            if (empty($items)) {
                $sheet->fromArray(array_merge($base, ['—', '—', '—', '—']), null, 'A' . $row);
                $row++;
                continue;
            }

            foreach ($items as $index => $item) {
                $itemCols = [
                    $item['name'] ?? 'Unnamed asset',
                    $this->formatMoney($this->itemPrice($item)),
                    $item['brand'] ?? '—',
                    $item['allocation_reason'] ?? '—',
                ];
                $sheet->fromArray(array_merge($index === 0 ? $base : array_fill(0, count($base), ''), $itemCols), null, 'A' . $row);
                $row++;
            }
        }

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function buildItemsSheet($sheet, string $title, array $items): void
    {
        $sheet->setTitle(substr($title, 0, 31));

        $headers = ['Asset name', 'Price', 'Brand', 'Allocation reason'];
        $sheet->fromArray($headers, null, 'A1');
        $this->styleHeaderRow($sheet, 'A1:D1');

        $row = 2;
        if (empty($items)) {
            $sheet->setCellValue('A2', 'No items in this section.');
            return;
        }

        foreach ($items as $item) {
            $sheet->fromArray([
                $item['name'] ?? 'Unnamed asset',
                $this->formatMoney($this->itemPrice($item)),
                $item['brand'] ?? '—',
                $item['allocation_reason'] ?? '—',
            ], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function styleHeaderRow($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE8EEF4');
    }

    private function buildPdf(CourtCase $case, array $data)
    {
        $case->loadMissing(['caseType', 'caseStatus']);

        return Pdf::loadView('backend.cases.distribution-summary-pdf', [
            'case' => $case,
            'data' => $data,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');
    }

    private function itemPrice(array $item): mixed
    {
        if (isset($item['concluded_price']) && $item['concluded_price'] !== '' && $item['concluded_price'] !== null) {
            return $item['concluded_price'];
        }

        return $item['purchase_price'] ?? null;
    }

    private function formatMoney(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return '$' . number_format((float) $value, 2);
    }

    private function filename(CourtCase $case, string $ext): string
    {
        $slug = preg_replace('/[^A-Za-z0-9._-]+/', '-', $case->case_number ?? 'case');
        $slug = trim($slug, '-') ?: 'case';

        return 'distribution-summary-' . $slug . '-' . now()->format('Y-m-d') . '.' . $ext;
    }
}
