<?php

namespace Tests\Feature;

use App\Filament\Pages\WhatsAppDiagnostics;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ShieldSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppDiagnosticsPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $generalManager;

    protected User $engineer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(ShieldSeeder::class);
        $this->seed(UserSeeder::class);

        $this->generalManager = User::where('email', 'gm@aljabali.com')->firstOrFail();
        $this->engineer = User::where('email', 'engineer@aljabali.com')->firstOrFail();
    }

    public function test_only_general_manager_can_access_the_diagnostics_page(): void
    {
        $this->actingAs($this->generalManager)
            ->get('/whatsapp-diagnostics')
            ->assertOk()
            ->assertSee(__('whatsapp.diagnostics.heading'));

        $this->actingAs($this->engineer)
            ->get('/whatsapp-diagnostics')
            ->assertForbidden();
    }

    public function test_general_manager_can_send_a_template_test_without_exposing_the_token(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.token' => 'secret-test-token',
            'services.whatsapp.phone_number_id' => '1345311191995637',
            'services.whatsapp.language' => 'ar',
            'services.whatsapp.default_country_code' => '962',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.TEST']]], 200),
        ]);

        $this->actingAs($this->generalManager);

        Livewire::test(WhatsAppDiagnostics::class)
            ->fillForm([
                'phone' => '0790162671',
                'template' => 'sales_request_submitted',
            ])
            ->call('sendTest')
            ->assertSet('result.success', true)
            ->assertSet('result.message_id', 'wamid.TEST')
            ->assertDontSee('secret-test-token');

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $payload['to'] === '962790162671'
                && $payload['template']['name'] === 'sales_request_submitted'
                && count($payload['template']['components'][0]['parameters']) === 3;
        });
    }
}
