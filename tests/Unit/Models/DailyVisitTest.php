<?php

namespace Tests\Unit\Models;

use App\Models\DailyVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyVisitTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_visit_can_be_created(): void
    {
        $user = User::factory()->create();

        $visit = DailyVisit::create([
            'user_id' => $user->id,
            'visit_date' => '2025-01-15',
            'client_name' => 'Client ABC',
            'location_text' => 'Amman, Jordan',
            'latitude' => 31.9454,
            'longitude' => 35.9284,
            'client_phone' => '+962791234567',
            'visit_reason' => 'Follow-up',
            'visit_results' => 'Client interested in new products',
        ]);

        $this->assertDatabaseHas('daily_visits', [
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
        ]);
    }

    public function test_daily_visit_has_correct_casts(): void
    {
        $user = User::factory()->create();

        $visit = DailyVisit::create([
            'user_id' => $user->id,
            'visit_date' => '2025-01-15',
            'client_name' => 'Client ABC',
            'visit_reason' => 'Follow-up',
            'latitude' => 31.9454,
            'longitude' => 35.9284,
        ]);

        $this->assertInstanceOf('Carbon\Carbon', $visit->visit_date);
        $this->assertEquals(31.9454, (float) $visit->latitude);
        $this->assertEquals(35.9284, (float) $visit->longitude);
    }

    public function test_daily_visit_user_relationship(): void
    {
        $user = User::factory()->create(['name' => 'Engineer']);

        $visit = DailyVisit::create([
            'user_id' => $user->id,
            'visit_date' => '2025-01-15',
            'client_name' => 'Client ABC',
            'visit_reason' => 'Follow-up',
        ]);

        $this->assertEquals('Engineer', $visit->user->name);
    }

    public function test_daily_visit_fillable_attributes(): void
    {
        $visit = new DailyVisit();
        $expected = [
            'user_id', 'visit_date', 'client_name', 'location_text',
            'latitude', 'longitude', 'client_phone', 'visit_reason',
            'visit_results', 'visit_photo',
        ];

        foreach ($expected as $attribute) {
            $this->assertContains($attribute, $visit->getFillable());
        }
    }

    public function test_daily_visit_other_optional_fields(): void
    {
        $user = User::factory()->create();

        $visit = DailyVisit::create([
            'user_id' => $user->id,
            'visit_date' => '2025-01-15',
            'client_name' => 'Client ABC',
            'visit_reason' => 'Follow-up',
        ]);

        $this->assertNull($visit->location_text);
        $this->assertNull($visit->client_phone);
        $this->assertNull($visit->visit_results);
        $this->assertNull($visit->visit_photo);
    }

    public function test_daily_visit_can_be_updated(): void
    {
        $user = User::factory()->create();

        $visit = DailyVisit::create([
            'user_id' => $user->id,
            'visit_date' => '2025-01-15',
            'client_name' => 'Client ABC',
            'visit_reason' => 'Follow-up',
        ]);

        $visit->update([
            'client_name' => 'Client XYZ',
            'visit_results' => 'Updated results',
        ]);

        $fresh = $visit->fresh();
        $this->assertEquals('Client XYZ', $fresh->client_name);
        $this->assertEquals('Updated results', $fresh->visit_results);
    }

    public function test_daily_visit_coordinates_precision(): void
    {
        $user = User::factory()->create();

        $visit = DailyVisit::create([
            'user_id' => $user->id,
            'visit_date' => '2025-01-15',
            'client_name' => 'Client ABC',
            'visit_reason' => 'Follow-up',
            'latitude' => 31.9454123,
            'longitude' => 35.9284567,
        ]);

        $this->assertEquals(31.9454123, (float) $visit->latitude);
        $this->assertEquals(35.9284567, (float) $visit->longitude);
    }
}
