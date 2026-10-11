<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ServiceEngineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ServiceEngineSeeder::class);
    }

    private function createAdmin(): User
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@korbo.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::ADMIN->value,
            'status' => 'ACTIVE',
        ]);
        $admin->assignRole(UserRole::ADMIN->value);
        return $admin;
    }

    private function createCustomer(): User
    {
        $customer = User::create([
            'name' => 'Customer User',
            'email' => 'customer@korbo.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::CUSTOMER->value,
            'status' => 'ACTIVE',
        ]);
        $customer->assignRole(UserRole::CUSTOMER->value);
        return $customer;
    }

    public function test_public_user_can_list_all_services(): void
    {
        $response = $this->getJson('/api/v1/services');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'estimated_days',
                        'category' => ['id', 'name', 'slug'],
                        'fee_breakdown' => ['government_fee', 'korbo_fee', 'agent_fee', 'total_amount'],
                    ],
                ],
            ]);
    }

    public function test_public_user_can_filter_services_by_category_and_search(): void
    {
        // 1. Filter by category
        $responseCategory = $this->getJson('/api/v1/services?category=passport-travel-documents');
        $responseCategory->assertStatus(200);
        $data = $responseCategory->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $item) {
            $this->assertEquals('passport-travel-documents', $item['category']['slug']);
        }

        // 2. Search by keyword
        $responseSearch = $this->getJson('/api/v1/services?search=Renewal');
        $responseSearch->assertStatus(200);
        $searchData = $responseSearch->json('data');
        $this->assertNotEmpty($searchData);
        $this->assertTrue(collect($searchData)->contains('slug', 'passport-renewal'));
    }

    public function test_public_user_can_get_service_details_with_requirements_steps_and_fees(): void
    {
        $response = $this->getJson('/api/v1/services/passport-renewal');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Passport Renewal')
            ->assertJsonPath('data.slug', 'passport-renewal')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'estimated_days',
                    'category' => ['id', 'name', 'slug'],
                    'requirements' => [
                        '*' => ['id', 'document_name', 'is_mandatory'],
                    ],
                    'steps' => [
                        '*' => ['id', 'step_name', 'step_order'],
                    ],
                    'fees' => [
                        '*' => ['id', 'fee_type', 'amount'],
                    ],
                    'fee_breakdown' => [
                        'government_fee',
                        'korbo_fee',
                        'agent_fee',
                        'total_amount',
                    ],
                ],
            ]);

        // Validate fee calculations for Passport Renewal
        $response->assertJsonPath('data.fee_breakdown.government_fee', 4025)
            ->assertJsonPath('data.fee_breakdown.korbo_fee', 500)
            ->assertJsonPath('data.fee_breakdown.agent_fee', 800)
            ->assertJsonPath('data.fee_breakdown.total_amount', 5325);

        // Validate dynamic requirements
        $reqs = collect($response->json('data.requirements'));
        $this->assertTrue($reqs->contains('document_name', 'National ID Card (NID)'));
        $this->assertTrue($reqs->contains('document_name', 'Existing Passport Copy'));

        // Validate ordered steps
        $steps = collect($response->json('data.steps'));
        $this->assertTrue($steps->contains('step_name', 'Document Verification & Audit'));
        $this->assertEquals(1, $steps->firstWhere('step_name', 'Document Verification & Audit')['step_order']);
    }

    public function test_admin_can_create_dynamic_database_driven_service(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $category = ServiceCategory::first();

        $payload = [
            'service_category_id' => $category->id,
            'name' => 'Police Clearance Certificate',
            'slug' => 'police-clearance-certificate',
            'description' => 'Official police verification and foreign ministry attestation assistance.',
            'estimated_days' => '5-7 working days',
            'is_active' => true,
            'requirements' => [
                ['document_name' => 'Valid Passport', 'is_mandatory' => true],
                ['document_name' => 'National ID Card', 'is_mandatory' => true],
                ['document_name' => 'Ward Councillor Address Proof', 'is_mandatory' => false],
            ],
            'steps' => [
                ['step_name' => 'Online Police Clearance Filing', 'step_order' => 1],
                ['step_name' => 'Local Thana Liaison & Verification', 'step_order' => 2],
                ['step_name' => 'DMP / SP Office Endorsement', 'step_order' => 3],
                ['step_name' => 'Delivery of Sealed Certificate', 'step_order' => 4],
            ],
            'fees' => [
                ['fee_type' => 'GOVERNMENT', 'amount' => 500.00],
                ['fee_type' => 'KORBO', 'amount' => 500.00],
                ['fee_type' => 'AGENT', 'amount' => 800.00],
            ],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/services', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Police Clearance Certificate')
            ->assertJsonPath('data.fee_breakdown.total_amount', 1800);

        $this->assertDatabaseHas('services', [
            'slug' => 'police-clearance-certificate',
            'name' => 'Police Clearance Certificate',
        ]);

        $this->assertDatabaseHas('service_requirements', [
            'document_name' => 'Valid Passport',
            'is_mandatory' => true,
        ]);

        $this->assertDatabaseHas('service_steps', [
            'step_name' => 'Online Police Clearance Filing',
            'step_order' => 1,
        ]);

        $this->assertDatabaseHas('service_fees', [
            'fee_type' => 'GOVERNMENT',
            'amount' => 500.00,
        ]);
    }

    public function test_admin_can_update_service_and_sync_requirements_steps_and_fees(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $service = Service::where('slug', 'passport-renewal')->first();

        $updatePayload = [
            'name' => 'Passport Renewal (Urgent Express)',
            'estimated_days' => '3-5 working days',
            'fees' => [
                ['fee_type' => 'GOVERNMENT', 'amount' => 8050.00],
                ['fee_type' => 'KORBO', 'amount' => 1000.00],
                ['fee_type' => 'AGENT', 'amount' => 1500.00],
            ],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/services/{$service->id}", $updatePayload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Passport Renewal (Urgent Express)')
            ->assertJsonPath('data.estimated_days', '3-5 working days')
            ->assertJsonPath('data.fee_breakdown.government_fee', 8050)
            ->assertJsonPath('data.fee_breakdown.total_amount', 10550);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Passport Renewal (Urgent Express)',
        ]);

        $this->assertDatabaseHas('service_fees', [
            'service_id' => $service->id,
            'fee_type' => 'GOVERNMENT',
            'amount' => 8050.00,
        ]);
    }

    public function test_admin_can_delete_service(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $service = Service::where('slug', 'passport-renewal')->first();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/services/{$service->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('services', [
            'id' => $service->id,
        ]);
    }

    public function test_customer_and_unauthenticated_users_cannot_create_or_modify_services(): void
    {
        $customer = $this->createCustomer();
        $token = $customer->createToken('customer_token')->plainTextToken;

        $category = ServiceCategory::first();
        $payload = [
            'service_category_id' => $category->id,
            'name' => 'Unauthorized Service',
        ];

        // 1. Customer cannot create (403 Forbidden)
        $customerResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/services', $payload);
        $customerResponse->assertStatus(403);

        // 2. Unauthenticated cannot create (401 Unauthorized)
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();

        $unauthResponse = $this->postJson('/api/v1/services', $payload);
        $unauthResponse->assertStatus(401);
    }

    public function test_public_user_can_list_and_view_service_categories(): void
    {
        $response = $this->getJson('/api/v1/service-categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'description', 'is_active'],
                ],
            ]);

        $responseCategory = $this->getJson('/api/v1/service-categories/passport-travel-documents');
        $responseCategory->assertStatus(200)
            ->assertJsonPath('data.slug', 'passport-travel-documents')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'services' => [
                        '*' => ['id', 'name', 'slug', 'fee_breakdown'],
                    ],
                ],
            ]);
    }
}
