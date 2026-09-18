<?php

namespace Tests\Unit\Enums;

use App\Enums\HrRequestStatus;
use Tests\TestCase;

class HrRequestStatusTest extends TestCase
{
    public function test_pending_status(): void
    {
        $status = HrRequestStatus::Pending;

        $this->assertEquals('pending', $status->value);
        $this->assertEquals('warning', $status->getColor());
        $this->assertEquals('heroicon-o-clock', $status->getIcon());
        $this->assertIsString($status->getLabel());
    }

    public function test_manager_approved_status(): void
    {
        $status = HrRequestStatus::ManagerApproved;

        $this->assertEquals('manager_approved', $status->value);
        $this->assertEquals('info', $status->getColor());
        $this->assertEquals('heroicon-o-check-badge', $status->getIcon());
        $this->assertIsString($status->getLabel());
    }

    public function test_approved_status(): void
    {
        $status = HrRequestStatus::Approved;

        $this->assertEquals('approved', $status->value);
        $this->assertEquals('success', $status->getColor());
        $this->assertEquals('heroicon-o-check-circle', $status->getIcon());
        $this->assertIsString($status->getLabel());
    }

    public function test_rejected_status(): void
    {
        $status = HrRequestStatus::Rejected;

        $this->assertEquals('rejected', $status->value);
        $this->assertEquals('danger', $status->getColor());
        $this->assertEquals('heroicon-o-x-circle', $status->getIcon());
        $this->assertIsString($status->getLabel());
    }

    public function test_all_cases(): void
    {
        $cases = HrRequestStatus::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(HrRequestStatus::Pending, $cases);
        $this->assertContains(HrRequestStatus::ManagerApproved, $cases);
        $this->assertContains(HrRequestStatus::Approved, $cases);
        $this->assertContains(HrRequestStatus::Rejected, $cases);
    }

    public function test_from_value(): void
    {
        $status = HrRequestStatus::from('pending');
        $this->assertEquals(HrRequestStatus::Pending, $status);

        $status = HrRequestStatus::from('approved');
        $this->assertEquals(HrRequestStatus::Approved, $status);
    }
}
