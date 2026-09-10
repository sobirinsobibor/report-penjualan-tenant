<?php

namespace App\Services;

use App\Models\Kantin;
use App\Models\SalesDetail;
use App\Models\SalesImport;
use App\Models\Tenant;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class EsbImportService
{
    /**
     * Parse and import ESB Sales Menu Recapitulation Report.
     *
     * @param string $filePath Absolute path to the uploaded file
     * @param string $originalFileName
     * @param int|null $userId
     * @return array Result summary
     * @throws Exception
     */
    public function import(string $filePath, string $originalFileName, ?int $userId = null): array
    {
        $extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        $reader = ($extension === 'csv') ? new CsvReader() : new XlsxReader();

        $reader->open($filePath);

        $branchName = null;
        $periodRaw = null;
        $headerRowIndex = null;
        $columnMap = [];
        $dataRows = [];

        $rowIndex = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex++;
                $cells = $row->toArray();

                // 1. Scan metadata (Branch & Period) in initial rows
                if ($rowIndex <= 15) {
                    $rowText = implode(' ', array_filter(array_map('strval', $cells)));

                    // Match Branch
                    if (!$branchName && preg_match('/(?:Branch|Kantin)\s*[:=]?\s*([^,\n\r\t]+)/i', $rowText, $m)) {
                        $branchName = trim(explode('Period', $m[1])[0]);
                        $branchName = trim($branchName, " :\t\n\r\0\x0B");
                    }

                    // Match Period
                    if (!$periodRaw && preg_match('/(?:Period|Periode)\s*[:=]?\s*([0-9a-zA-Z\/\-\s]+(?:\s*-\s*[0-9a-zA-Z\/\-\s]+)?)/i', $rowText, $pm)) {
                        $periodRaw = trim($pm[1], " :\t\n\r\0\x0B");
                    }

                    // Alternative: branch / period located in adjacent cells (e.g. cell 0 = "Branch", cell 1 = "Kantin A")
                    for ($c = 0; $c < count($cells) - 1; $c++) {
                        $val = trim((string)($cells[$c] ?? ''));
                        $nextVal = trim((string)($cells[$c + 1] ?? ''));

                        if (!$branchName && preg_match('/^(?:Branch|Kantin)$/i', $val) && !empty($nextVal)) {
                            $branchName = $nextVal;
                        }
                        if (!$periodRaw && preg_match('/^(?:Period|Periode)$/i', $val) && !empty($nextVal)) {
                            $periodRaw = $nextVal;
                        }
                    }
                }

                // 2. Detect table column header row
                if ($headerRowIndex === null) {
                    $map = $this->detectColumns($cells);
                    if ($map['category'] !== null && $map['item'] !== null) {
                        $headerRowIndex = $rowIndex;
                        $columnMap = $map;
                        continue;
                    }
                }

                // 3. Collect data rows if past header row
                if ($headerRowIndex !== null && $rowIndex > $headerRowIndex) {
                    $category = trim((string)($cells[$columnMap['category']] ?? ''));
                    $item = trim((string)($cells[$columnMap['item']] ?? ''));

                    // Skip empty rows or summary rows
                    if (empty($category) && empty($item)) {
                        continue;
                    }

                    if (preg_match('/^(?:Total|Grand Total|Sub Total|Subtotal)$/i', $category) ||
                        preg_match('/^(?:Total|Grand Total|Sub Total|Subtotal)$/i', $item)) {
                        continue;
                    }

                    // If category is empty but item exists, or vice versa, handle gracefully
                    if (empty($category)) {
                        $category = 'General';
                    }
                    if (empty($item)) {
                        $item = $category;
                    }

                    $qty = $columnMap['qty'] !== null ? $this->parseNumber($cells[$columnMap['qty']] ?? 0) : 1;
                    $gross = $columnMap['gross'] !== null ? $this->parseNumber($cells[$columnMap['gross']] ?? 0) : 0;
                    $discount = $columnMap['discount'] !== null ? $this->parseNumber($cells[$columnMap['discount']] ?? 0) : 0;
                    $grandTotal = $columnMap['grand_total'] !== null ? $this->parseNumber($cells[$columnMap['grand_total']] ?? 0) : ($gross - $discount);

                    $dataRows[] = [
                        'category' => $category,
                        'item_name' => $item,
                        'qty' => $qty,
                        'gross_sales' => $gross,
                        'discount' => $discount,
                        'grand_total' => $grandTotal,
                    ];
                }
            }

            // Read only first sheet
            break;
        }

        $reader->close();

        // Fallbacks if metadata not in header
        if (empty($branchName)) {
            $branchName = 'Kantin Utama';
        }
        if (empty($periodRaw)) {
            $periodRaw = now()->format('d/m/Y');
        }

        if (empty($dataRows)) {
            throw new Exception("Tidak ada data transaksi yang dapat dibaca dari file ini. Pastikan format kolom sesuai.");
        }

        // Parse date range
        [$periodStart, $periodEnd] = $this->parsePeriodDates($periodRaw);

        return DB::transaction(function () use (
            $branchName,
            $periodRaw,
            $periodStart,
            $periodEnd,
            $originalFileName,
            $userId,
            $dataRows
        ) {
            // Auto-match / auto-create Kantin
            $kantin = Kantin::firstOrCreate(
                ['name' => $branchName]
            );

            // Validasi duplikasi: Cek apakah Kantin dan Periode sudah pernah diunggah
            $alreadyExists = SalesImport::where('kantin_id', $kantin->id)
                ->where('period_raw', $periodRaw)
                ->exists();

            if ($alreadyExists) {
                throw new Exception("Data penjualan untuk Kantin '{$kantin->name}' pada periode '{$periodRaw}' sudah pernah diunggah!");
            }

            $totalAmount = 0;
            foreach ($dataRows as $row) {
                $totalAmount += $row['grand_total'];
            }

            // Create Sales Import record
            $salesImport = SalesImport::create([
                'kantin_id' => $kantin->id,
                'uploaded_by' => $userId,
                'file_name' => $originalFileName,
                'period_raw' => $periodRaw,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'total_rows' => count($dataRows),
                'total_amount' => $totalAmount,
            ]);

            // Cache tenants for this kantin to minimize DB queries
            $existingTenants = Tenant::where('kantin_id', $kantin->id)->pluck('id', 'name')->toArray();
            $newTenantsCount = 0;

            $detailsToInsert = [];
            $now = now();

            foreach ($dataRows as $row) {
                $catName = $row['category'];
                if (!isset($existingTenants[$catName])) {
                    $tenant = Tenant::create([
                        'kantin_id' => $kantin->id,
                        'name' => $catName,
                    ]);
                    $existingTenants[$catName] = $tenant->id;
                    $newTenantsCount++;
                }

                $tenantId = $existingTenants[$catName];

                $detailsToInsert[] = [
                    'sales_import_id' => $salesImport->id,
                    'tenant_id' => $tenantId,
                    'item_name' => $row['item_name'],
                    'qty' => $row['qty'],
                    'gross_sales' => $row['gross_sales'],
                    'discount' => $row['discount'],
                    'grand_total' => $row['grand_total'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Batch insert details
            foreach (array_chunk($detailsToInsert, 200) as $chunk) {
                SalesDetail::insert($chunk);
            }

            return [
                'import_id' => $salesImport->id,
                'kantin' => $kantin->name,
                'period' => $periodRaw,
                'period_start' => $periodStart?->format('Y-m-d'),
                'period_end' => $periodEnd?->format('Y-m-d'),
                'total_rows' => count($dataRows),
                'total_amount' => $totalAmount,
                'new_tenants_count' => $newTenantsCount,
            ];
        });
    }

    /**
     * Detect column indexes based on header names.
     */
    protected function detectColumns(array $cells): array
    {
        $map = [
            'category' => null,
            'item' => null,
            'qty' => null,
            'gross' => null,
            'discount' => null,
            'grand_total' => null,
        ];

        foreach ($cells as $index => $raw) {
            $header = trim(strtolower((string)$raw));

            if (preg_match('/(?:menu\s*category|category|tenant|kategori)/i', $header)) {
                $map['category'] = $index;
            } elseif (preg_match('/(?:item\s*name|product|menu\s*name|menu|nama\s*item|nama\s*menu)/i', $header)) {
                $map['item'] = $index;
            } elseif (preg_match('/(?:sales\s*qty|qty|quantity|jumlah)/i', $header)) {
                $map['qty'] = $index;
            } elseif (preg_match('/(?:gross\s*sales|sales\s*gross|gross|bruto)/i', $header)) {
                $map['gross'] = $index;
            } elseif (preg_match('/(?:discount|diskon|potongan)/i', $header)) {
                $map['discount'] = $index;
            } elseif (preg_match('/(?:grand\s*total|net\s*sales|total|netto)/i', $header)) {
                $map['grand_total'] = $index;
            }
        }

        return $map;
    }

    /**
     * Clean and parse numeric strings.
     */
    protected function parseNumber(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }

        if (!is_string($value)) {
            return 0.0;
        }

        // Remove currency symbols, spaces
        $clean = preg_replace('/[^\d\.,\-]/', '', trim($value));

        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            // E.g. 1,250,000.50 or 1.250.000,50
            if (strrpos($clean, '.') > strrpos($clean, ',')) {
                $clean = str_replace(',', '', $clean);
            } else {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            }
        } elseif (str_contains($clean, ',')) {
            // E.g. 1500,50 -> 1500.50
            $clean = str_replace(',', '.', $clean);
        }

        return is_numeric($clean) ? (float)$clean : 0.0;
    }

    /**
     * Try to extract start and end dates from period string.
     */
    protected function parsePeriodDates(string $periodRaw): array
    {
        $dates = [];
        // Match dates like 2026-09-01, 01/09/2026, 01-09-2026, 01 Sep 2026
        if (preg_match_all('/\b\d{4}[-\/]\d{1,2}[-\/]\d{1,2}\b|\b\d{1,2}[-\/]\d{1,2}[-\/]\d{4}\b/', $periodRaw, $matches)) {
            foreach ($matches[0] as $match) {
                try {
                    $dates[] = Carbon::parse(str_replace('/', '-', $match));
                } catch (\Throwable) {
                }
            }
        }

        $start = !empty($dates[0]) ? $dates[0]->startOfDay() : null;
        $end = !empty($dates[1]) ? $dates[1]->endOfDay() : ($start ? $start->copy()->endOfDay() : null);

        return [$start, $end];
    }
}
