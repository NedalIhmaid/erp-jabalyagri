<?php

namespace Tests\Unit\Enums;

use App\Enums\HrRequestType;
use Tests\TestCase;

class HrRequestTypeTest extends TestCase
{
    public function test_annual_leave(): void
    {
        $type = HrRequestType::AnnualLeave;

        $this->assertEquals('annual_leave', $type->value);
        $this->assertEquals('info', $type->getColor());
        $this->assertEquals('heroicon-o-sun', $type->getIcon());
        $this->assertIsString($type->getLabel());
    }

    public function test_sick_leave(): void
    {
        $type = HrRequestType::SickLeave;

        $this->assertEquals('sick_leave', $type->value);
        $this->assertEquals('danger', $type->getColor());
        $this->assertEquals('heroicon-o-heart', $type->getIcon());
        $this->assertIsString($type->getLabel());
    }

    public function test_unpaid_leave(): void
    {
        $type = HrRequestType::UnpaidLeave;

        $this->assertEquals('unpaid_leave', $type->value);
        $this->assertEquals('warning', $type->getColor());
        $this->assertEquals('heroicon-o-banknotes', $type->getIcon());
        $this->assertIsString($type->getLabel());
    }

    public function test_marriage_leave(): void
    {
        $type = HrRequestType::MarriageLeave;

        $this->assertEquals('marriage_leave', $type->value);
        $this->assertEquals('success', $type->getColor());
        $this->assertEquals('heroicon-o-heart', $type->getIcon());
        $this->assertIsString($type->getLabel());
    }

    public function test_maternity_leave(): void
    {
        $type = HrRequestType::MaternityLeave;

        $this->assertEquals('maternity_leave', $type->value);
        $this->assertEquals('success', $type->getColor());
        $this->assertEquals('heroicon-o-face-smile', $type->getIcon());
        $this->assertIsString($type->getLabel());
    }

    public function test_early_departure(): void
    {
        $type = HrRequestType::EarlyDeparture;

        $this->assertEquals('early_departure', $type->value);
        $this->assertEquals('gray', $type->getColor());
        $this->assertEquals('heroicon-o-clock', $type->getIcon());
        $this->assertIsString($type->getLabel());
    }

    public function test_all_cases(): void
    {
        $cases = HrRequestType::cases();

        $this->assertCount(10, $cases);
        $this->assertContains(HrRequestType::AnnualLeave, $cases);
        $this->assertContains(HrRequestType::SickLeave, $cases);
        $this->assertContains(HrRequestType::UnpaidLeave, $cases);
        $this->assertContains(HrRequestType::MarriageLeave, $cases);
        $this->assertContains(HrRequestType::MaternityLeave, $cases);
        $this->assertContains(HrRequestType::EarlyDeparture, $cases);
    }

    public function test_from_value(): void
    {
        $type = HrRequestType::from('annual_leave');
        $this->assertEquals(HrRequestType::AnnualLeave, $type);

        $type = HrRequestType::from('sick_leave');
        $this->assertEquals(HrRequestType::SickLeave, $type);
    }
}
