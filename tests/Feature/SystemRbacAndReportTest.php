<?php

namespace Tests\Feature;

use App\Models\Kantin;
use App\Models\SalesDetail;
use App\Models\SalesImport;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemRbacAndReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tenantUser1;
    private User $tenantUser2;
    private Tenant $tenant1;
    private Tenant $tenant2;
    private Kantin $kantin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kantin = Kantin::create(['name' => 'Kantin Pusat']);

        $this->tenant1 = Tenant::create([
            'kantin_id' => $this->kantin->id,
            'name' => 'Tenant Nasi Goreng',
        ]);

        $this->tenant2 = Tenant::create([
            'kantin_id' => $this->kantin->id,
            'name' => 'Tenant Jus Buah',
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->tenantUser1 = User::create([
            'name' => 'Tenant 1 User',
            'email' => 'tenant1@test.com',
            'password' => Hash::make('password'),
            'role' => 'tenant',
            'tenant_id' => $this->tenant1->id,
        ]);

        $this->tenantUser2 = User::create([
            'name' => 'Tenant 2 User',
            'email' => 'tenant2@test.com',
            'password' => Hash::make('password'),
            'role' => 'tenant',
            'tenant_id' => $this->tenant2->id,
        ]);

        $import = SalesImport::create([
            'kantin_id' => $this->kantin->id,
            'uploaded_by' => $this->admin->id,
            'file_name' => 'sample.xlsx',
            'period_raw' => '01/09/2026 - 05/09/2026',
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-05',
            'total_rows' => 2,
            'total_amount' => 50000,
        ]);

        SalesDetail::create([
            'sales_import_id' => $import->id,
            'tenant_id' => $this->tenant1->id,
            'item_name' => 'Nasi Goreng Spesial',
            'qty' => 2,
            'gross_sales' => 30000,
            'discount' => 0,
            'grand_total' => 30000,
        ]);

        SalesDetail::create([
            'sales_import_id' => $import->id,
            'tenant_id' => $this->tenant2->id,
            'item_name' => 'Jus Alpukat',
            'qty' => 1,
            'gross_sales' => 20000,
            'discount' => 0,
            'grand_total' => 20000,
        ]);
    }

    public function test_admin_can_access_master_data_and_import(): void
    {
        $this->actingAs($this->admin);

        $this->get('/dashboard')->assertSuccessful();
        $this->get('/dashboard/kantins')->assertSuccessful();
        $this->get('/dashboard/tenants')->assertSuccessful();
        $this->get('/dashboard/users')->assertSuccessful();
        $this->get('/dashboard/import-sales')->assertSuccessful();
        $this->get('/dashboard/sales-details')->assertSuccessful();
    }

    public function test_tenant_cannot_access_master_data_or_import(): void
    {
        $this->actingAs($this->tenantUser1);

        $this->get('/dashboard')->assertSuccessful();
        $this->get('/dashboard/sales-details')->assertSuccessful();

        $this->get('/dashboard/kantins')->assertForbidden();
        $this->get('/dashboard/tenants')->assertForbidden();
        $this->get('/dashboard/users')->assertForbidden();
        $this->get('/dashboard/import-sales')->assertForbidden();
    }

    public function test_tenant_query_is_scoped_to_their_tenant_id(): void
    {
        $this->actingAs($this->tenantUser1);

        $response = $this->get('/dashboard/sales-details');
        $response->assertSuccessful();
        $response->assertSee('Nasi Goreng Spesial');
        $response->assertDontSee('Jus Alpukat');
    }
}
