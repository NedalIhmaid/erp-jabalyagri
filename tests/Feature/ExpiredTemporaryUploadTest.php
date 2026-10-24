<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Livewire;
use Tests\TestCase;

class ExpiredTemporaryUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_temporary_upload_returns_validation_error_instead_of_crashing(): void
    {
        Storage::fake('public');
        Storage::fake(FileUploadConfiguration::disk());

        $this->seed(RolePermissionSeeder::class);
        $this->seed(UserSeeder::class);

        $this->actingAs(User::where('email', 'gm@aljabali.com')->firstOrFail());

        $family = ProductFamily::create(['name' => 'خيار', 'is_active' => true]);

        $component = Livewire::test(CreateProduct::class)
            ->fillForm([
                'product_family_id' => $family->id,
                'name' => 'خيار شامي',
            ])
            ->set('data.image_path', [UploadedFile::fake()->image('photo.jpg')]);

        // Simulates the temp file being consumed by an earlier failed submit, or purged by Livewire's cleanup.
        FileUploadConfiguration::storage()->deleteDirectory(FileUploadConfiguration::path());

        $component
            ->call('create')
            ->assertHasFormErrors(['image_path']);

        $this->assertSame(0, Product::count());
    }
}
