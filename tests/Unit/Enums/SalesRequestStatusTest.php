<?php

namespace Tests\Unit\Enums;

use App\Enums\SalesRequestStatus;
use Tests\TestCase;

class SalesRequestStatusTest extends TestCase
{
    public function test_pending_status(): void
    {
        $status = SalesRequestStatus::Pending;

        $this->assertEquals('pending', $status->value);
        $this->assertEquals('warning', $status->getColor());
        $this->assertEquals('heroicon-o-clock', $status->getIcon());
        $this->assertIsString($status->getLabel());
    }

    public function test_in_progress_status(): void
    {
        $status = SalesRequestStatus::InProgress;

        $this->assertEquals('in_progress', $status->value);
        $this->assertEquals('info', $status->getColor());
        $this->assertEquals('heroicon-o-arrow-path', $status->getIcon());
        $this->assertIsString($status->getLabel());
    }

    public function test_approved_status(): void
    {
        $status = SalesRequestStatus::Approved;

        $this->assertEquals('approved', $status->value);
        $this->assertEquals('success', $status->getColor());
        $this->assertEquals('heroicon-o-check-circle', $status->getIcon());
        $this->assertIsString($status->getLabel());
    }

    public function test_rejected_status(): void
    {
        $status = SalesRequestStatus::Rejected;

        $this->assertEquals('rejected', $status->value);
        $this->assertEquals('danger', $status->getColor());
        $this->assertEquals('heroicon-o-x-circle', $status->getIcon());
        $this->assertIsString($status->getLabel());
    }

    public function test_cancelled_status(): void
    {
        $status = SalesRequestStatus::Cancelled;

        $this->assertEquals('cancelled', $status->value);
        $this->assertEquals('danger', $status->getColor());
        $this->assertEquals('heroicon-o-x-circle', $status->getIcon());
        $this->assertIsString($status->getLabel());
    }

    public function test_returned_status(): void
    {
        $status = SalesRequestStatus::Returned;

        $this->assertEquals('returned', $status->value);
        $this->assertEquals('gray', $status->getColor());
        $this->assertEquals('heroicon-o-arrow-uturn-left', $status->getIcon());
        $this->assertIsString($status->getLabel());
    }

    public function test_all_cases(): void
    {
        $cases = SalesRequestStatus::cases();

        $this->assertCount(6, $cases);
        $this->assertContains(SalesRequestStatus::Pending, $cases);
        $this->assertContains(SalesRequestStatus::InProgress, $cases);
        $this->assertContains(SalesRequestStatus::Approved, $cases);
        $this->assertContains(SalesRequestStatus::Cancelled, $cases);
        $this->assertContains(SalesRequestStatus::Returned, $cases);
    }

    public function test_from_value(): void
    {
        $status = SalesRequestStatus::from('in_progress');
        $this->assertEquals(SalesRequestStatus::InProgress, $status);

        $status = SalesRequestStatus::from('approved');
        $this->assertEquals(SalesRequestStatus::Approved, $status);
    }
}
