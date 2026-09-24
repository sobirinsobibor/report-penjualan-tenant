<?php

namespace Tests\Feature;

use App\Models\Kantin;
use App\Models\SalesDetail;
use App\Models\SalesImport;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EsbImportService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EsbImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_import_sales_report_and_auto_create_kantin_and_tenants(): void
    {
        $csvContent = <<<CSV
Sales Menu Recapitulation Report
Branch: Kantin Gedung Utama
Period: 01/09/2026 - 07/09/2026

Menu Category,Item Name,Sales Qty,Gross Sales,Discount,Grand Total
Ayam Geprek Bu Sri,Paket Geprek Original,15,300000,0,300000
Ayam Geprek Bu Sri,Es Teh Manis,20,100000,10000,90000
Kopi Kenangan Mantan,Kopi Kenangan Mantan R,25,450000,25000,425000
Kopi Kenangan Mantan,Roti Coklat,10,120000,0,120000
CSV;

        $tempFile = tempnam(sys_get_temp_dir(), 'esb_') . '.csv';
        file_put_contents($tempFile, $csvContent);

        $service = new EsbImportService();
        $result = $service->import($tempFile, 'report.csv');

        @unlink($tempFile);

        $this->assertEquals('Kantin Gedung Utama', $result['kantin']);
        $this->assertEquals(4, $result['total_rows']);
        $this->assertEquals(935000, $result['total_amount']);

        $this->assertDatabaseHas('kantins', ['name' => 'Kantin Gedung Utama']);
        $this->assertDatabaseHas('tenants', ['name' => 'Ayam Geprek Bu Sri', 'fee_percentage' => 15.00]);
        $this->assertDatabaseHas('tenants', ['name' => 'Kopi Kenangan Mantan', 'fee_percentage' => 15.00]);
        $this->assertDatabaseCount('sales_details', 4);
    }

    public function test_prevents_duplicate_import_for_same_kantin_and_period(): void
    {
        $csvContent = <<<CSV
Sales Menu Recapitulation Report
Branch: Kantin Timur
Period: 01/08/2026 - 31/08/2026

Menu Category,Item Name,Sales Qty,Gross Sales,Discount,Grand Total
Bakso Pak Kumis,Bakso Urat,10,200000,0,200000
CSV;

        $tempFile = tempnam(sys_get_temp_dir(), 'esb_') . '.csv';
        file_put_contents($tempFile, $csvContent);

        $service = new EsbImportService();
        $service->import($tempFile, 'report1.csv');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("sudah pernah diunggah");

        $service->import($tempFile, 'report1.csv');

        @unlink($tempFile);
    }

    public function test_can_import_full_esb_format_without_zero_price_issue(): void
    {
        $csvContent = <<<CSV
Sales Menu Recapitulation Report
Branch: Kantin Barat
Period: 10/09/2026 - 15/09/2026

Sales Type,Menu Category,Menu Category Detail,Menu,Menu Short Name,Menu Custom Name,Menu Code,Menu Tag,Menu Info,Order Mode,Qty,Unit Price,Subtotal,Menu Discount Total,Bill Discount Total,Net Sales Total,Service Charge Total,Tax Total,VAT Total,Grand Total
Dine In,Nasi Goreng Mas Edi,Food,Nasi Goreng Spesial,NGS,Nasi Goreng Spesial,101,,,Takeaway,2,25000,50000,5000,0,45000,0,0,0,45000
Dine In,Nasi Goreng Mas Edi,Food,Es Jeruk,EJ,Es Jeruk,102,,,Takeaway,5,10000,50000,0,0,50000,0,0,0,50000
CSV;

        $tempFile = tempnam(sys_get_temp_dir(), 'esb_') . '.csv';
        file_put_contents($tempFile, $csvContent);

        $service = new EsbImportService();
        $result = $service->import($tempFile, 'full_esb_report.csv');

        @unlink($tempFile);

        $this->assertEquals('Kantin Barat', $result['kantin']);
        $this->assertEquals(2, $result['total_rows']);
        $this->assertEquals(95000, $result['total_amount']);

        $detail = SalesDetail::where('item_name', 'Nasi Goreng Spesial')->first();
        $this->assertNotNull($detail);
        $this->assertEquals(50000, $detail->gross_sales);
        $this->assertEquals(5000, $detail->discount);
        $this->assertEquals(45000, $detail->grand_total);
    }
}
