<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Kantin;
use App\Models\Role;
use App\Models\SalesDetail;
use App\Models\SalesImport;
use App\Models\Settlement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TenantDashboardAndSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $tenantUser;
    protected Tenant $tenant;
    protected Kantin $kantin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
        $tenantRole = Role::create(['name' => 'tenant', 'display_name' => 'Tenant']);

        $this->kantin = Kantin::create(['name' => 'Airlangga Foodcourt']);
        $this->tenant = Tenant::create([
            'kantin_id' => $this->kantin->id,
            'name' => '01 HENI SRI WAHYUNI',
            'fee_percentage' => 15.00,
            'target_omzet' => 55000000.00,
            'fixed_fee' => 50000.00,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'role_id' => $adminRole->id,
        ]);

        $this->tenantUser = User::create([
            'name' => 'Tenant Test',
            'email' => 'tenant@test.com',
            'password' => bcrypt('password'),
            'role' => 'tenant',
            'role_id' => $tenantRole->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_tenant_sales_stats_widget_renders_correctly(): void
    {
        $this->actingAs($this->tenantUser);

        $import = SalesImport::create([
            'kantin_id' => $this->kantin->id,
            'file_name' => 'test.xlsx',
            'period_raw' => '01-09-2026 - 04-09-2026',
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-04',
            'total_rows' => 2,
            'total_amount' => 50000,
        ]);

        SalesDetail::create([
            'sales_import_id' => $import->id,
            'tenant_id' => $this->tenant->id,
            'item_name' => '01 Gado-Gado',
            'qty' => 2,
            'gross_sales' => 28000,
            'discount' => 0,
            'grand_total' => 28000,
        ]);

        Livewire::test(\App\Filament\Widgets\TenantSalesStatsWidget::class)
            ->assertSuccessful();
    }

    public function test_tenant_announcement_widget_displays_active_announcements(): void
    {
        $this->actingAs($this->tenantUser);

        Announcement::create([
            'kantin_id' => $this->kantin->id,
            'title' => 'Maintenance Listrik Outlet',
            'category' => 'maintenance',
            'event_date' => '2026-09-23',
            'content' => 'Pemeliharaan listrik 23 September 2026.',
            'is_active' => true,
        ]);

        Livewire::test(\App\Filament\Widgets\TenantAnnouncementWidget::class)
            ->assertSuccessful()
            ->assertSee('Maintenance Listrik Outlet')
            ->assertSee('Pemeliharaan listrik 23 September 2026.');
    }

    public function test_settlement_calculation_for_tenant(): void
    {
        $import = SalesImport::create([
            'kantin_id' => $this->kantin->id,
            'file_name' => 'test_settlement.xlsx',
            'period_raw' => '01-09-2026 - 15-09-2026',
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-15',
            'total_rows' => 1,
            'total_amount' => 25000000,
        ]);

        SalesDetail::create([
            'sales_import_id' => $import->id,
            'tenant_id' => $this->tenant->id,
            'item_name' => 'Nasi Campur Special',
            'qty' => 1000,
            'gross_sales' => 25000000,
            'discount' => 0,
            'grand_total' => 25000000,
        ]);

        $settlement = Settlement::calculateForTenant(
            $this->tenant,
            '2026-09-01',
            '2026-09-15',
            100000,
            'Biaya Kebersihan'
        );

        $this->assertEquals(25000000, $settlement->gross_sales);
        $this->assertEquals(15.00, $settlement->revenue_share_percentage);
        $this->assertEquals(3750000, $settlement->revenue_share_amount); // 15% of 25m
        $this->assertEquals(50000, $settlement->operational_fee);
        $this->assertEquals(100000, $settlement->other_deductions);
        $this->assertEquals(21100000, $settlement->settlement_amount); // 25m - 3.75m - 50k - 100k
    }

    public function test_tenant_can_view_their_settlement_records(): void
    {
        $this->actingAs($this->tenantUser);

        Settlement::create([
            'tenant_id' => $this->tenant->id,
            'period_raw' => 'Periode 1–15 September 2026',
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-15',
            'gross_sales' => 25000000,
            'revenue_share_percentage' => 15.00,
            'revenue_share_amount' => 3750000,
            'operational_fee' => 50000,
            'other_deductions' => 100000,
            'deduction_notes' => 'Maintenance Listrik',
            'settlement_amount' => 21100000,
            'status' => 'approved',
        ]);

        Livewire::test(\App\Filament\Resources\Settlements\Pages\ManageSettlements::class)
            ->assertSuccessful()
            ->assertSee('Periode 1–15 September 2026')
            ->assertSee('Rp 21.100.000');
    }
}
