<?php

namespace Tests\Unit\Models;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_options_returns_only_active_keyed_by_key(): void
    {
        PaymentMethod::create(['key' => 'check', 'name_ar' => 'شيك', 'name_en' => 'Check', 'color' => 'gray', 'sort_order' => 1]);
        PaymentMethod::create(['key' => 'archived', 'name_ar' => 'مؤرشف', 'name_en' => 'Archived', 'is_active' => false, 'sort_order' => 2]);

        $options = PaymentMethod::options();

        $this->assertArrayHasKey('check', $options);
        $this->assertArrayNotHasKey('archived', $options);
    }

    public function test_label_for_resolves_localized_label(): void
    {
        PaymentMethod::create(['key' => 'check', 'name_ar' => 'شيك', 'name_en' => 'Check', 'color' => 'gray']);

        app()->setLocale('en');
        $this->assertEquals('Check', PaymentMethod::labelFor('check'));

        app()->setLocale('ar');
        $this->assertEquals('شيك', PaymentMethod::labelFor('check'));
    }

    public function test_label_and_color_for_unknown_key_fall_back(): void
    {
        $this->assertEquals('mystery', PaymentMethod::labelFor('mystery'));
        $this->assertEquals('gray', PaymentMethod::colorFor('mystery'));
        $this->assertNull(PaymentMethod::labelFor(null));
    }

    public function test_color_for_returns_stored_color(): void
    {
        PaymentMethod::create(['key' => 'check', 'name_ar' => 'شيك', 'name_en' => 'Check', 'color' => 'gray']);

        $this->assertEquals('gray', PaymentMethod::colorFor('check'));
    }
}
