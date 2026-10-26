<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Service;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaskLifecycleTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createRole(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    private function makeUser(string $role): User
    {
        $this->createRole($role);

        $user = User::factory()->create([
            'role'   => $role,
            'status' => 'ACTIVE',
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function makeService(bool $isActive = true): Service
    {
        $category = \App\Models\ServiceCategory::factory()->create();

        $service = Service::factory()->create([
            'service_category_id' => $category->id,
            'is_active'           => $isActive,
        ]);

        $service->fees()->createMany([
            ['fee_type' => 'GOVERNMENT', 'amount' => 4025],
            ['fee_type' => 'KORBO',      'amount' => 500],
            ['fee_type' => 'AGENT',      'amount' => 800],
        ]);

        return $service;
    }

    private function makeTask(User $customer, ?User $agent = null, string $status = TaskStatus::NEW->value): Task
    {
        $service = $this->makeService();

        $task = Task::factory()->create([
            'user_id'        => $customer->id,
            'service_id'     => $service->id,
            'agent_id'       => $agent?->id,
            'status'         => $status,
            'government_fee' => 4025,
            'service_fee'    => 500,
            'agent_fee'      => 800,
            'total_amount'   => 5325,
            'location'       => 'Dhaka',
        ]);

        $task->statusHistories()->create([
            'status'     => $status,
            'notes'      => 'Task created.',
            'changed_by' => $customer->id,
        ]);

        return $task;
    }

    // ─── Day 7: Task Creation & Listing ─────────────────────────────────────

    #[Test]
    public function test_customer_can_create_a_task(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $service  = $this->makeService();

        $response = $this->actingAs($customer)->postJson('/api/v1/tasks', [
            'service_id'       => $service->id,
            'location'         => 'Dhaka, Mirpur',
            'appointment_date' => now()->addDays(5)->toDateString(),
            'notes'            => 'জরুরি কাজ।',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.status', TaskStatus::NEW->value);

        // Cast-safe comparison (SQLite returns numeric strings, MySQL returns floats)
        $fees = $response->json('data.fees');
        $this->assertEquals(4025.0, (float) $fees['government_fee']);
        $this->assertEquals(5325.0, (float) $fees['total_amount']);

        $this->assertDatabaseHas('tasks', [
            'user_id'    => $customer->id,
            'service_id' => $service->id,
            'status'     => TaskStatus::NEW->value,
            'location'   => 'Dhaka, Mirpur',
        ]);

        $this->assertDatabaseHas('task_status_histories', [
            'status'     => TaskStatus::NEW->value,
            'changed_by' => $customer->id,
        ]);
    }

    #[Test]
    public function test_task_fees_are_auto_calculated_from_service(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $service  = $this->makeService();

        $response = $this->actingAs($customer)->postJson('/api/v1/tasks', [
            'service_id' => $service->id,
            'location'   => 'Chittagong',
        ]);

        $response->assertStatus(201);
        $fees = $response->json('data.fees');

        $this->assertEquals(4025.0, (float) $fees['government_fee']);
        $this->assertEquals(500.0,  (float) $fees['service_fee']);
        $this->assertEquals(800.0,  (float) $fees['agent_fee']);
        $this->assertEquals(5325.0, (float) $fees['total_amount']);
    }

    #[Test]
    public function test_task_creation_fails_with_inactive_service(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $service  = $this->makeService(isActive: false);

        $this->actingAs($customer)->postJson('/api/v1/tasks', [
            'service_id' => $service->id,
            'location'   => 'Dhaka',
        ])->assertStatus(422)
          ->assertJsonPath('success', false);
    }

    #[Test]
    public function test_task_creation_requires_service_id_and_location(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);

        $this->actingAs($customer)->postJson('/api/v1/tasks', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['service_id', 'location']);
    }

    #[Test]
    public function test_customer_can_only_see_own_tasks(): void
    {
        $customer1 = $this->makeUser(UserRole::CUSTOMER->value);
        $customer2 = $this->makeUser(UserRole::CUSTOMER->value);

        $this->makeTask($customer1);
        $this->makeTask($customer2);

        $response = $this->actingAs($customer1)->getJson('/api/v1/tasks');

        $response->assertStatus(200);
        $tasks = $response->json('data');
        $this->assertCount(1, $tasks);
        $this->assertEquals($customer1->id, $tasks[0]['customer']['id']);
    }

    #[Test]
    public function test_agent_can_only_see_assigned_tasks(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $agent    = $this->makeUser(UserRole::AGENT->value);

        $this->makeTask($customer, $agent, TaskStatus::ASSIGNED->value);
        $this->makeTask($customer);  // unassigned — agent should NOT see this

        $response = $this->actingAs($agent)->getJson('/api/v1/tasks');
        $tasks    = $response->json('data');

        $this->assertCount(1, $tasks);
    }

    #[Test]
    public function test_staff_can_see_all_tasks(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $admin    = $this->makeUser(UserRole::ADMIN->value);

        $this->makeTask($customer);
        $this->makeTask($customer);
        $this->makeTask($customer);

        $response = $this->actingAs($admin)->getJson('/api/v1/tasks');
        $total    = $response->json('meta.total');

        $this->assertEquals(3, $total);
    }

    #[Test]
    public function test_customer_can_view_own_task_details(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $task     = $this->makeTask($customer);

        $response = $this->actingAs($customer)->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $task->id)
                 ->assertJsonPath('data.status', TaskStatus::NEW->value);
    }

    #[Test]
    public function test_customer_cannot_view_another_customers_task(): void
    {
        $customer1 = $this->makeUser(UserRole::CUSTOMER->value);
        $customer2 = $this->makeUser(UserRole::CUSTOMER->value);
        $task      = $this->makeTask($customer1);

        $this->actingAs($customer2)->getJson("/api/v1/tasks/{$task->id}")
             ->assertStatus(403);
    }

    // ─── Day 8: Status, Assignment & Documents ───────────────────────────────

    #[Test]
    public function test_staff_can_assign_agent_to_task(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $agent    = $this->makeUser(UserRole::AGENT->value);
        $admin    = $this->makeUser(UserRole::ADMIN->value);
        $task     = $this->makeTask($customer);

        $response = $this->actingAs($admin)->postJson("/api/v1/tasks/{$task->id}/assign", [
            'agent_id' => $agent->id,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.status', TaskStatus::ASSIGNED->value)
                 ->assertJsonPath('data.agent.id', $agent->id);

        $this->assertDatabaseHas('tasks', [
            'id'       => $task->id,
            'agent_id' => $agent->id,
            'status'   => TaskStatus::ASSIGNED->value,
        ]);

        $this->assertDatabaseHas('task_assignments', [
            'task_id'  => $task->id,
            'agent_id' => $agent->id,
        ]);
    }

    #[Test]
    public function test_customer_cannot_assign_agent(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $agent    = $this->makeUser(UserRole::AGENT->value);
        $task     = $this->makeTask($customer);

        $this->actingAs($customer)->postJson("/api/v1/tasks/{$task->id}/assign", [
            'agent_id' => $agent->id,
        ])->assertStatus(403);
    }

    #[Test]
    public function test_non_agent_user_cannot_be_assigned(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $another  = $this->makeUser(UserRole::CUSTOMER->value); // not an agent
        $admin    = $this->makeUser(UserRole::ADMIN->value);
        $task     = $this->makeTask($customer);

        $this->actingAs($admin)->postJson("/api/v1/tasks/{$task->id}/assign", [
            'agent_id' => $another->id,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['agent_id']);
    }

    #[Test]
    public function test_agent_can_update_task_status_to_in_progress(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $agent    = $this->makeUser(UserRole::AGENT->value);
        $task     = $this->makeTask($customer, $agent, TaskStatus::ASSIGNED->value);

        $response = $this->actingAs($agent)->postJson("/api/v1/tasks/{$task->id}/status", [
            'status' => TaskStatus::IN_PROGRESS->value,
            'notes'  => 'কাজ শুরু করলাম।',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.status', TaskStatus::IN_PROGRESS->value);

        $this->assertDatabaseHas('task_status_histories', [
            'task_id'    => $task->id,
            'status'     => TaskStatus::IN_PROGRESS->value,
            'changed_by' => $agent->id,
        ]);
    }

    #[Test]
    public function test_agent_can_complete_task(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $agent    = $this->makeUser(UserRole::AGENT->value);
        $task     = $this->makeTask($customer, $agent, TaskStatus::IN_PROGRESS->value);

        $response = $this->actingAs($agent)->postJson("/api/v1/tasks/{$task->id}/status", [
            'status' => TaskStatus::COMPLETED->value,
            'notes'  => 'কাজ শেষ।',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.status', TaskStatus::COMPLETED->value);

        $this->assertNotNull(Task::find($task->id)->completed_at);
    }

    #[Test]
    public function test_cannot_update_status_of_terminal_task(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $admin    = $this->makeUser(UserRole::ADMIN->value);
        $task     = $this->makeTask($customer, null, TaskStatus::COMPLETED->value);

        $this->actingAs($admin)->postJson("/api/v1/tasks/{$task->id}/status", [
            'status' => TaskStatus::IN_PROGRESS->value,
        ])->assertStatus(422);
    }

    #[Test]
    public function test_invalid_status_value_is_rejected(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $admin    = $this->makeUser(UserRole::ADMIN->value);
        $task     = $this->makeTask($customer);

        $this->actingAs($admin)->postJson("/api/v1/tasks/{$task->id}/status", [
            'status' => 'INVALID_STATUS',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['status']);
    }

    #[Test]
    public function test_customer_can_cancel_own_new_task(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $task     = $this->makeTask($customer, null, TaskStatus::NEW->value);

        $response = $this->actingAs($customer)->postJson("/api/v1/tasks/{$task->id}/cancel", [
            'notes' => 'পরিকল্পনা পরিবর্তন হয়েছে।',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.status', TaskStatus::CANCELLED->value);
    }

    #[Test]
    public function test_customer_cannot_cancel_completed_task(): void
    {
        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $task     = $this->makeTask($customer, null, TaskStatus::COMPLETED->value);

        // TaskPolicy::cancel() returns false for completed tasks → 403
        // (Policy check runs before Action, so we get 403 not 422)
        $this->actingAs($customer)->postJson("/api/v1/tasks/{$task->id}/cancel")
             ->assertStatus(403);
    }

    #[Test]
    public function test_customer_can_upload_document_for_task(): void
    {
        Storage::fake('public');

        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $task     = $this->makeTask($customer);

        $response = $this->actingAs($customer)->postJson("/api/v1/tasks/{$task->id}/documents", [
            'file'          => UploadedFile::fake()->create('nid.pdf', 500, 'application/pdf'),
            'document_type' => 'National ID Card',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('document.document_type', 'National ID Card');

        $this->assertDatabaseHas('documents', [
            'task_id'       => $task->id,
            'user_id'       => $customer->id,
            'document_type' => 'National ID Card',
            'is_verified'   => false,
        ]);
    }

    #[Test]
    public function test_customer_can_list_task_documents(): void
    {
        Storage::fake('public');

        $customer = $this->makeUser(UserRole::CUSTOMER->value);
        $task     = $this->makeTask($customer);

        // Upload two docs directly
        $task->documents()->createMany([
            [
                'user_id'       => $customer->id,
                'document_type' => 'NID',
                'file_name'     => 'nid.pdf',
                'storage_path'  => 'tasks/1/nid.pdf',
                'is_verified'   => false,
            ],
            [
                'user_id'       => $customer->id,
                'document_type' => 'Passport',
                'file_name'     => 'passport.jpg',
                'storage_path'  => 'tasks/1/passport.jpg',
                'is_verified'   => false,
            ],
        ]);

        $response = $this->actingAs($customer)->getJson("/api/v1/tasks/{$task->id}/documents");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    #[Test]
    public function test_unauthenticated_user_cannot_access_tasks(): void
    {
        $this->getJson('/api/v1/tasks')->assertStatus(401);
        $this->postJson('/api/v1/tasks', [])->assertStatus(401);
    }
}
