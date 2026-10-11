<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Agent;
use App\Models\Document;
use App\Models\DocumentAccessGrant;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $category = ServiceCategory::create([
            'name' => 'Government Services',
            'slug' => 'government-services',
        ]);

        $this->service = Service::create([
            'service_category_id' => $category->id,
            'name' => 'Passport Assistance',
            'slug' => 'passport-assistance',
        ]);
    }

    private function createUserWithRole(UserRole $role, string $email, string $phone): User
    {
        $user = User::create([
            'name' => 'User ' . $role->value,
            'email' => $email,
            'phone' => $phone,
            'password' => bcrypt('password123'),
            'role' => $role->value,
            'status' => 'ACTIVE',
        ]);
        $user->assignRole($role->value);
        return $user;
    }

    public function test_task_policy_permissions_by_role(): void
    {
        $customer1 = $this->createUserWithRole(UserRole::CUSTOMER, 'cust1@test.com', '01711111111');
        $customer2 = $this->createUserWithRole(UserRole::CUSTOMER, 'cust2@test.com', '01722222222');
        $agent = $this->createUserWithRole(UserRole::AGENT, 'agent@test.com', '01733333333');
        $admin = $this->createUserWithRole(UserRole::ADMIN, 'admin@test.com', '01744444444');
        $superAdmin = $this->createUserWithRole(UserRole::SUPER_ADMIN, 'super@test.com', '01755555555');

        $task = Task::create([
            'user_id' => $customer1->id,
            'service_id' => $this->service->id,
            'agent_id' => $agent->id,
            'status' => 'IN_PROGRESS',
            'total_amount' => 500,
        ]);

        // Customer 1 owns the task
        $this->assertTrue(Gate::forUser($customer1)->allows('view', $task));
        $this->assertTrue(Gate::forUser($customer1)->allows('update', $task));
        $this->assertFalse(Gate::forUser($customer1)->allows('assignAgent', $task));

        // Customer 2 cannot view or update customer 1's task
        $this->assertFalse(Gate::forUser($customer2)->allows('view', $task));
        $this->assertFalse(Gate::forUser($customer2)->allows('update', $task));

        // Assigned Agent can view and update task progress
        $this->assertTrue(Gate::forUser($agent)->allows('view', $task));
        $this->assertTrue(Gate::forUser($agent)->allows('update', $task));
        $this->assertFalse(Gate::forUser($agent)->allows('delete', $task));

        // Admin has operational access
        $this->assertTrue(Gate::forUser($admin)->allows('view', $task));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $task));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $task));
        $this->assertTrue(Gate::forUser($admin)->allows('assignAgent', $task));

        // Super Admin has access to everything
        $this->assertTrue(Gate::forUser($superAdmin)->allows('view', $task));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('update', $task));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('delete', $task));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('assignAgent', $task));
    }

    public function test_document_policy_permissions_by_role(): void
    {
        $customer = $this->createUserWithRole(UserRole::CUSTOMER, 'cust@test.com', '01711111111');
        $otherCustomer = $this->createUserWithRole(UserRole::CUSTOMER, 'other@test.com', '01722222222');
        $agent = $this->createUserWithRole(UserRole::AGENT, 'agent@test.com', '01733333333');
        $admin = $this->createUserWithRole(UserRole::ADMIN, 'admin@test.com', '01744444444');

        $task = Task::create([
            'user_id' => $customer->id,
            'service_id' => $this->service->id,
            'agent_id' => $agent->id,
            'status' => 'IN_PROGRESS',
        ]);

        $document = Document::create([
            'user_id' => $customer->id,
            'task_id' => $task->id,
            'document_type' => 'PASSPORT',
            'file_name' => 'passport.pdf',
            'storage_path' => 'documents/passport.pdf',
            'mime_type' => 'application/pdf',
        ]);

        // Owner can view, other customer cannot
        $this->assertTrue(Gate::forUser($customer)->allows('view', $document));
        $this->assertFalse(Gate::forUser($otherCustomer)->allows('view', $document));

        // Assigned Agent can view task-associated document
        $this->assertTrue(Gate::forUser($agent)->allows('view', $document));

        // Explicit access grant test
        $standaloneDoc = Document::create([
            'user_id' => $customer->id,
            'document_type' => 'NID',
            'file_name' => 'nid.pdf',
            'storage_path' => 'documents/nid.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $this->assertFalse(Gate::forUser($agent)->allows('view', $standaloneDoc));

        DocumentAccessGrant::create([
            'document_id' => $standaloneDoc->id,
            'agent_id' => $agent->id,
            'task_id' => $task->id,
            'expires_at' => now()->addDays(2),
        ]);

        $this->assertTrue(Gate::forUser($agent)->allows('view', $standaloneDoc));

        // Only staff can verify documents
        $this->assertTrue(Gate::forUser($admin)->allows('verify', $document));
        $this->assertFalse(Gate::forUser($customer)->allows('verify', $document));
        $this->assertFalse(Gate::forUser($agent)->allows('verify', $document));
    }

    public function test_agent_policy_permissions_by_role(): void
    {
        $agentUser = $this->createUserWithRole(UserRole::AGENT, 'agent@test.com', '01733333333');
        $otherAgentUser = $this->createUserWithRole(UserRole::AGENT, 'otheragent@test.com', '01744444444');
        $admin = $this->createUserWithRole(UserRole::ADMIN, 'admin@test.com', '01755555555');

        $agent = Agent::create([
            'user_id' => $agentUser->id,
            'status' => 'PENDING',
            'bio' => 'Experienced agent',
        ]);

        // Agent can update own profile, but cannot update another agent's profile
        $this->assertTrue(Gate::forUser($agentUser)->allows('update', $agent));
        $this->assertFalse(Gate::forUser($otherAgentUser)->allows('update', $agent));

        // Admin can verify agent, agent cannot self-verify
        $this->assertTrue(Gate::forUser($admin)->allows('verify', $agent));
        $this->assertFalse(Gate::forUser($agentUser)->allows('verify', $agent));
    }

    public function test_payment_policy_permissions_by_role(): void
    {
        $customer = $this->createUserWithRole(UserRole::CUSTOMER, 'cust@test.com', '01711111111');
        $otherCustomer = $this->createUserWithRole(UserRole::CUSTOMER, 'other@test.com', '01722222222');
        $admin = $this->createUserWithRole(UserRole::ADMIN, 'admin@test.com', '01733333333');

        $task = Task::create([
            'user_id' => $customer->id,
            'service_id' => $this->service->id,
            'status' => 'PENDING_PAYMENT',
        ]);

        $payment = Payment::create([
            'task_id' => $task->id,
            'user_id' => $customer->id,
            'amount' => 1500.00,
            'status' => 'PAID',
        ]);

        // Customer can view own payment, other customer cannot
        $this->assertTrue(Gate::forUser($customer)->allows('view', $payment));
        $this->assertFalse(Gate::forUser($otherCustomer)->allows('view', $payment));

        // Admin can view and refund, customer cannot refund
        $this->assertTrue(Gate::forUser($admin)->allows('view', $payment));
        $this->assertTrue(Gate::forUser($admin)->allows('refund', $payment));
        $this->assertFalse(Gate::forUser($customer)->allows('refund', $payment));
    }

    public function test_review_policy_permissions_by_role(): void
    {
        $customer = $this->createUserWithRole(UserRole::CUSTOMER, 'cust@test.com', '01711111111');
        $otherCustomer = $this->createUserWithRole(UserRole::CUSTOMER, 'other@test.com', '01722222222');
        $agent = $this->createUserWithRole(UserRole::AGENT, 'agent@test.com', '01733333333');
        $admin = $this->createUserWithRole(UserRole::ADMIN, 'admin@test.com', '01744444444');

        $task = Task::create([
            'user_id' => $customer->id,
            'service_id' => $this->service->id,
            'agent_id' => $agent->id,
            'status' => 'COMPLETED',
        ]);

        $review = Review::create([
            'task_id' => $task->id,
            'user_id' => $customer->id,
            'agent_id' => $agent->id,
            'rating' => 5,
            'comment' => 'Great assistance!',
        ]);

        // Customer can update and delete their own review
        $this->assertTrue(Gate::forUser($customer)->allows('update', $review));
        $this->assertTrue(Gate::forUser($customer)->allows('delete', $review));

        // Other customer cannot update this review
        $this->assertFalse(Gate::forUser($otherCustomer)->allows('update', $review));

        // Admin has moderation rights
        $this->assertTrue(Gate::forUser($admin)->allows('update', $review));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $review));
    }
}
