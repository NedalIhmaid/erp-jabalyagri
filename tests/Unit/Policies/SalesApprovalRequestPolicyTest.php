<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\SalesApprovalRequest;
use App\Policies\SalesApprovalRequestPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesApprovalRequestPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected SalesApprovalRequestPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SalesApprovalRequestPolicy();
    }

    public function test_view_any_policy_exists(): void
    {
        $user = User::factory()->create();
        $this->assertIsBool($this->policy->viewAny($user));
    }

    public function test_view_policy_exists(): void
    {
        $user = User::factory()->create();
        $request = SalesApprovalRequest::factory()->create(['user_id' => $user->id]);
        $this->assertIsBool($this->policy->view($user, $request));
    }

    public function test_create_policy_exists(): void
    {
        $user = User::factory()->create();
        $this->assertIsBool($this->policy->create($user));
    }

    public function test_update_policy_exists(): void
    {
        $user = User::factory()->create();
        $request = SalesApprovalRequest::factory()->create(['user_id' => $user->id]);
        $this->assertIsBool($this->policy->update($user, $request));
    }

    public function test_delete_policy_exists(): void
    {
        $user = User::factory()->create();
        $request = SalesApprovalRequest::factory()->create(['user_id' => $user->id]);
        $this->assertIsBool($this->policy->delete($user, $request));
    }

    public function test_delete_any_policy_exists(): void
    {
        $user = User::factory()->create();
        $this->assertIsBool($this->policy->deleteAny($user));
    }

    public function test_restore_policy_exists(): void
    {
        $user = User::factory()->create();
        $request = SalesApprovalRequest::factory()->create(['user_id' => $user->id]);
        $this->assertIsBool($this->policy->restore($user, $request));
    }

    public function test_force_delete_policy_exists(): void
    {
        $user = User::factory()->create();
        $request = SalesApprovalRequest::factory()->create(['user_id' => $user->id]);
        $this->assertIsBool($this->policy->forceDelete($user, $request));
    }

    public function test_force_delete_any_policy_exists(): void
    {
        $user = User::factory()->create();
        $this->assertIsBool($this->policy->forceDeleteAny($user));
    }

    public function test_restore_any_policy_exists(): void
    {
        $user = User::factory()->create();
        $this->assertIsBool($this->policy->restoreAny($user));
    }

    public function test_replicate_policy_exists(): void
    {
        $user = User::factory()->create();
        $request = SalesApprovalRequest::factory()->create(['user_id' => $user->id]);
        $this->assertIsBool($this->policy->replicate($user, $request));
    }

    public function test_reorder_policy_exists(): void
    {
        $user = User::factory()->create();
        $this->assertIsBool($this->policy->reorder($user));
    }
}
