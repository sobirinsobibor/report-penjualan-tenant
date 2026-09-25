<?php

namespace Tests\Feature;

use App\Models\Kantin;
use App\Models\Tenant;
use App\Models\User;
use App\Filament\Pages\BerkasSaya;
use App\Filament\Resources\BerkasTenant\BerkasTenantResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BerkasTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_document_completeness_status_calculation(): void
    {
        $kantin = Kantin::create(['name' => 'Kantin Gedung C']);
        $tenant = Tenant::create([
            'kantin_id' => $kantin->id,
            'name' => 'Kedai Makanan Bu Sri',
        ]);

        $this->assertFalse($tenant->isDocumentComplete());
        $this->assertEquals('Belum Lengkap', $tenant->document_status);

        $tenant->update([
            'owner_name' => 'Heni Sri Wahyuni',
            'nik' => '3578123456780001',
            'no_kk' => '3578123456780002',
            'phone' => '081234567890',
            'address' => 'Jl. Airlangga No. 45 Surabaya',
            'birth_place_date' => 'Surabaya, 10 Mei 1985',
            'nomor_halal' => 'HALAL-12345',
            'file_ktp' => 'tenant-documents/ktp.jpg',
            'file_kk' => 'tenant-documents/kk.jpg',
            'file_npwp' => 'tenant-documents/npwp.jpg',
            'file_sertifikat_higenitas' => 'tenant-documents/higenitas.jpg',
        ]);

        $this->assertTrue($tenant->fresh()->isDocumentComplete());
        $this->assertEquals('Lengkap', $tenant->fresh()->document_status);
    }

    public function test_tenant_user_can_access_berkas_saya_page(): void
    {
        $kantin = Kantin::create(['name' => 'Kantin Utama']);
        $tenant = Tenant::create([
            'kantin_id' => $kantin->id,
            'name' => 'Tenant Kopi',
        ]);

        $user = User::factory()->create([
            'role' => 'tenant',
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);

        Livewire::test(BerkasSaya::class)
            ->assertSuccessful();
    }

    public function test_admin_can_access_berkas_tenant_resource(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $this->assertTrue(BerkasTenantResource::canViewAny());
    }
}
