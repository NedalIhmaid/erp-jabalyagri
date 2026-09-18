<?php

namespace Tests\Unit\Enums;

use App\Enums\ApprovalAction;
use Tests\TestCase;

class ApprovalActionTest extends TestCase
{
    public function test_pending_action(): void
    {
        $action = ApprovalAction::Pending;

        $this->assertEquals('pending', $action->value);
        $this->assertEquals('gray', $action->getColor());
        $this->assertEquals('heroicon-o-clock', $action->getIcon());
        $this->assertIsString($action->getLabel());
    }

    public function test_approved_action(): void
    {
        $action = ApprovalAction::Approved;

        $this->assertEquals('approved', $action->value);
        $this->assertEquals('success', $action->getColor());
        $this->assertEquals('heroicon-o-check-circle', $action->getIcon());
        $this->assertIsString($action->getLabel());
    }

    public function test_rejected_action(): void
    {
        $action = ApprovalAction::Rejected;

        $this->assertEquals('rejected', $action->value);
        $this->assertEquals('danger', $action->getColor());
        $this->assertEquals('heroicon-o-x-circle', $action->getIcon());
        $this->assertIsString($action->getLabel());
    }

    public function test_returned_action(): void
    {
        $action = ApprovalAction::Returned;

        $this->assertEquals('returned', $action->value);
        $this->assertEquals('warning', $action->getColor());
        $this->assertEquals('heroicon-o-arrow-uturn-left', $action->getIcon());
        $this->assertIsString($action->getLabel());
    }

    public function test_viewed_action(): void
    {
        $action = ApprovalAction::Viewed;

        $this->assertEquals('viewed', $action->value);
        $this->assertEquals('info', $action->getColor());
        $this->assertEquals('heroicon-o-eye', $action->getIcon());
        $this->assertIsString($action->getLabel());
    }

    public function test_all_cases(): void
    {
        $cases = ApprovalAction::cases();

        $this->assertCount(5, $cases);
        $this->assertContains(ApprovalAction::Pending, $cases);
        $this->assertContains(ApprovalAction::Approved, $cases);
        $this->assertContains(ApprovalAction::Rejected, $cases);
        $this->assertContains(ApprovalAction::Returned, $cases);
        $this->assertContains(ApprovalAction::Viewed, $cases);
    }

    public function test_from_value(): void
    {
        $action = ApprovalAction::from('approved');
        $this->assertEquals(ApprovalAction::Approved, $action);

        $action = ApprovalAction::from('rejected');
        $this->assertEquals(ApprovalAction::Rejected, $action);
    }
}
