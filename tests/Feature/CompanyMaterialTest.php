<?php

namespace Tests\Feature;

use App\Models\CompanyMaterial;
use App\Models\User;
use Database\Seeders\CompanyMaterialSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyMaterialTest extends TestCase
{
    use RefreshDatabase;

    private function seedCatalog(): CompanyMaterial
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(CompanyMaterialSeeder::class);

        return CompanyMaterial::firstOrFail();
    }

    public function test_imports_the_full_materials_catalog(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(CompanyMaterialSeeder::class);

        $this->assertGreaterThan(800, CompanyMaterial::count());
        $this->assertSame(6, \App\Models\CompanyMaterialCategory::count());
    }

    public function test_everyone_can_browse_but_only_admin_can_edit(): void
    {
        $material = $this->seedCatalog();

        $engineer = User::where('email', 'engineer@aljabali.com')->firstOrFail();
        $gm = User::where('email', 'gm@aljabali.com')->firstOrFail();

        // Any panel user can browse and view.
        $this->actingAs($engineer)->get('/company-materials')->assertOk();
        $this->actingAs($engineer)->get("/company-materials/{$material->id}")->assertOk();

        // View-only roles never reach the create/edit forms.
        $this->actingAs($engineer)->get('/company-materials/create')->assertForbidden();
        $this->actingAs($engineer)->get("/company-materials/{$material->id}/edit")->assertForbidden();

        // The general manager (admin) can open create/edit forms.
        $this->actingAs($gm)->get('/company-materials/create')->assertOk();
        $this->actingAs($gm)->get("/company-materials/{$material->id}/edit")->assertOk();
    }
}
