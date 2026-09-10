<?php

namespace Tests\Feature;

use App\Models\Kantin;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SalesDetail;
use App\Models\SalesImport;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemRbacAndReportTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $admin;
    private User $tenantUser1;
    private User $tenantUser2;
    private Tenant $tenant1;
    private Tenant $tenant2;
    private Kantin $kantin;

    protected function setUp(): void
    {
        parent::setUp();

        // Run seeder to seed permissions, roles, abilities, and users
        $this->seed(DatabaseSeeder::class);

        $this->superadmin = User::where('email', 'superadmin@kantin.com')->firstOrFail();
        $this->admin = User::where('email', 'admin@kantin.com')->firstOrFail();
        $this->tenantUser1 = User::where('email', 'tenant1@kantin.com')->firstOrFail();
        $this->tenantUser2 = User::where('email', 'tenant2@kantin.com')->firstOrFail();
        $this->tenant1 = Tenant::where('name', 'Ayam Geprek Bu Sri')->firstOrFail();
        $this->tenant2 = Tenant::where('name', 'Kopi Kenangan Mantan')->firstOrFail();
        $this->kantin = Kantin::where('name', 'Kantin Gedung Utama')->firstOrFail();
    }

    public function test_superadmin_has_all_abilities_and_can_access_roles(): void
    {
        $this->actingAs($this->superadmin);

        $this->assertTrue($this->superadmin->hasAbility('kantin.create'));
        $this->assertTrue($this->superadmin->hasAbility('role.manage'));

        $this->get('/dashboard')->assertSuccessful();
        $this->get('/dashboard/roles')->assertSuccessful();
        $this->get('/dashboard/users')->assertSuccessful();
        $this->get('/dashboard/kantins')->assertSuccessful();
        $this->get('/dashboard/tenants')->assertSuccessful();
        $this->get('/dashboard/import-sales')->assertSuccessful();
        $this->get('/dashboard/sales-details')->assertSuccessful();
    }

    public function test_admin_has_operational_abilities_but_cannot_access_roles(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue($this->admin->hasAbility('kantin.view_any'));
        $this->assertTrue($this->admin->hasAbility('sales.import'));
        $this->assertFalse($this->admin->hasAbility('role.manage'));

        $this->get('/dashboard')->assertSuccessful();
        $this->get('/dashboard/kantins')->assertSuccessful();
        $this->get('/dashboard/tenants')->assertSuccessful();
        $this->get('/dashboard/users')->assertSuccessful();
        $this->get('/dashboard/import-sales')->assertSuccessful();
        $this->get('/dashboard/sales-details')->assertSuccessful();

        // By default admin cannot access roles management
        $this->get('/dashboard/roles')->assertForbidden();
    }

    public function test_can_insert_ability_to_role_and_user_inherits_it(): void
    {
        // Initially admin cannot access role.view_any
        $this->assertFalse($this->admin->hasAbility('role.view_any'));

        // Insert ability 'role.view_any' into role 'admin'
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $adminRole->giveAbilities('role.view_any');

        // Refresh user and relation
        $this->admin->refresh();
        $this->admin->load('roleModel.permissions');

        $this->assertTrue($this->admin->hasAbility('role.view_any'));

        // Now admin can access roles page
        $this->actingAs($this->admin);
        $this->get('/dashboard/roles')->assertSuccessful();
    }

    public function test_tenant_cannot_access_master_data_or_import(): void
    {
        $this->actingAs($this->tenantUser1);

        $this->assertTrue($this->tenantUser1->hasAbility('sales.view_own'));
        $this->assertFalse($this->tenantUser1->hasAbility('kantin.view_any'));
        $this->assertFalse($this->tenantUser1->hasAbility('sales.import'));

        $this->get('/dashboard')->assertSuccessful();
        $this->get('/dashboard/sales-details')->assertSuccessful();

        $this->get('/dashboard/kantins')->assertForbidden();
        $this->get('/dashboard/tenants')->assertForbidden();
        $this->get('/dashboard/users')->assertForbidden();
        $this->get('/dashboard/roles')->assertForbidden();
        $this->get('/dashboard/import-sales')->assertForbidden();
    }

    public function test_tenant_query_is_scoped_to_their_tenant_id(): void
    {
        $this->actingAs($this->tenantUser1);

        $response = $this->get('/dashboard/sales-details');
        $response->assertSuccessful();
        $response->assertSee('Paket Geprek Original Level 3');
        $response->assertDontSee('Kopi Kenangan Mantan R');
    }
}
