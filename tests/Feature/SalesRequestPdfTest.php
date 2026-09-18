<?php

namespace Tests\Feature;

use App\Enums\ProjectType;
use App\Enums\SalesRequestStatus;
use App\Models\Product;
use App\Models\SalesApprovalRequest;
use App\Models\SalesRequestItem;
use App\Models\User;
use Database\Seeders\ProductCatalogSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesRequestPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $engineer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(ProductCatalogSeeder::class);

        $this->engineer = User::where('email', 'engineer@aljabali.com')->firstOrFail();
    }

    public function test_approved_request_pdf_downloads_successfully(): void
    {
        $request = $this->makeRequest([
            'status' => SalesRequestStatus::Approved,
            'current_stage' => 3,
        ]);

        $response = $this->actingAs($this->engineer)
            ->get(route('sales-requests.pdf', $request));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('%PDF-', $response->getContent());
    }

    public function test_engineer_can_download_own_request_pdf_when_not_approved(): void
    {
        $request = $this->makeRequest([
            'status' => SalesRequestStatus::InProgress,
            'current_stage' => 2,
        ]);

        $response = $this->actingAs($this->engineer)
            ->get(route('sales-requests.pdf', $request));

        $response->assertOk();
    }

    public function test_unrelated_user_cannot_download_non_approved_request(): void
    {
        $otherEngineer = User::factory()->create([
            'email' => 'other-engineer@aljabali.com',
            'locale' => 'ar',
            'is_active' => true,
        ]);
        $otherEngineer->assignRole('engineer');

        $request = $this->makeRequest([
            'status' => SalesRequestStatus::InProgress,
            'current_stage' => 2,
        ]);

        $response = $this->actingAs($otherEngineer)
            ->get(route('sales-requests.pdf', $request));

        $response->assertForbidden();
    }

    protected function makeRequest(array $overrides = []): SalesApprovalRequest
    {
        $product = Product::with('productUnits')->firstOrFail();
        $productUnit = $product->productUnits->first();

        $request = SalesApprovalRequest::create(array_merge([
            'request_number' => 'SAR-PDF-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'user_id' => $this->engineer->id,
            'client_name' => 'PDF Client',
            'client_phone' => '+962790000222',
            'client_address' => 'Amman',
            'region' => 'عمان',
            'project_type' => ProjectType::Farm,
            'project_size' => '5 دونم',
            'payment_method' => 'on_account',
            'total_amount' => 250,
            'current_stage' => 1,
            'status' => SalesRequestStatus::InProgress,
        ], $overrides));

        SalesRequestItem::create([
            'sales_approval_request_id' => $request->id,
            'product_id' => $product->id,
            'product_unit_id' => $productUnit?->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit' => $productUnit?->label ?? 'وحدة',
            'unit_price' => (float) ($productUnit?->price ?? 0),
            'total_price' => round(2 * (float) ($productUnit?->price ?? 0), 2),
        ]);

        return $request->fresh();
    }
}
