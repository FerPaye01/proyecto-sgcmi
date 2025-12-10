<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OperationsMeeting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsMeetingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_planificador_can_view_operations_meetings(): void
    {
        $role = Role::where('code', 'PLANIFICADOR_PUERTO')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get('/portuario/operations-meeting');

        $response->assertStatus(200);
        $response->assertViewIs('portuario.operations-meeting.index');
    }

    public function test_planificador_can_access_create_form(): void
    {
        $role = Role::where('code', 'PLANIFICADOR_PUERTO')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get('/portuario/operations-meeting/create');

        $response->assertStatus(200);
        $response->assertViewIs('portuario.operations-meeting.create');
    }

    public function test_planificador_can_create_operations_meeting(): void
    {
        $role = Role::where('code', 'PLANIFICADOR_PUERTO')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $data = [
            'meeting_date' => now()->toDateString(),
            'meeting_time' => '09:00',
            'attendees' => [
                ['name' => 'Juan Pérez', 'role' => 'Jefe de Operaciones'],
                ['name' => 'María García', 'role' => 'Supervisor de Muelle'],
            ],
            'agreements' => 'Se acordó priorizar la descarga del buque MV Pacific Star.',
            'next_24h_schedule' => [
                [
                    'vessel' => 'MV Pacific Star',
                    'operation' => 'DESCARGA',
                    'start_time' => '10:00',
                    'estimated_duration_h' => 8,
                ],
                [
                    'vessel' => 'MV Atlantic Wave',
                    'operation' => 'CARGA',
                    'start_time' => '18:00',
                    'estimated_duration_h' => 6,
                ],
            ],
        ];

        $response = $this->actingAs($user)->post('/portuario/operations-meeting', $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('portuario.operations_meeting', [
            'meeting_date' => now()->toDateString(),
            'meeting_time' => '09:00:00',
        ]);
    }

    public function test_transportista_cannot_create_operations_meeting(): void
    {
        $role = Role::where('code', 'TRANSPORTISTA')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $data = [
            'meeting_date' => now()->toDateString(),
            'meeting_time' => '09:00',
            'attendees' => [
                ['name' => 'Juan Pérez', 'role' => 'Jefe de Operaciones'],
            ],
            'agreements' => 'Test agreement',
            'next_24h_schedule' => [
                [
                    'vessel' => 'MV Test',
                    'operation' => 'DESCARGA',
                    'start_time' => '10:00',
                    'estimated_duration_h' => 8,
                ],
            ],
        ];

        $response = $this->actingAs($user)->post('/portuario/operations-meeting', $data);

        $response->assertStatus(403);
    }

    public function test_operations_meeting_requires_valid_data(): void
    {
        $role = Role::where('code', 'PLANIFICADOR_PUERTO')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->post('/portuario/operations-meeting', []);

        $response->assertSessionHasErrors([
            'meeting_date',
            'meeting_time',
            'attendees',
            'agreements',
            'next_24h_schedule',
        ]);
    }

    public function test_planificador_can_view_operations_meeting_details(): void
    {
        $role = Role::where('code', 'PLANIFICADOR_PUERTO')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $meeting = OperationsMeeting::factory()->create([
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get("/portuario/operations-meeting/{$meeting->id}");

        $response->assertStatus(200);
        $response->assertViewIs('portuario.operations-meeting.show');
        $response->assertViewHas('operationsMeeting', $meeting);
    }

    public function test_operations_meeting_filters_by_date_range(): void
    {
        $role = Role::where('code', 'PLANIFICADOR_PUERTO')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        // Create meetings on different dates
        OperationsMeeting::factory()->create([
            'meeting_date' => now()->subDays(5),
            'created_by' => $user->id,
        ]);

        OperationsMeeting::factory()->create([
            'meeting_date' => now(),
            'created_by' => $user->id,
        ]);

        OperationsMeeting::factory()->create([
            'meeting_date' => now()->addDays(5),
            'created_by' => $user->id,
        ]);

        // Filter for meetings from today onwards
        $response = $this->actingAs($user)->get('/portuario/operations-meeting?date_from=' . now()->toDateString());

        $response->assertStatus(200);
        $response->assertViewIs('portuario.operations-meeting.index');
    }
}
