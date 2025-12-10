<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Berth;
use App\Models\Role;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VesselCallTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_planificador_can_view_vessel_calls(): void
    {
        $role = Role::where('code', 'PLANIFICADOR_PUERTO')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get('/portuario/vessel-calls');

        $response->assertStatus(200);
    }

    public function test_planificador_can_access_create_form(): void
    {
        $role = Role::where('code', 'PLANIFICADOR_PUERTO')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get('/portuario/vessel-calls/create');

        $response->assertStatus(200);
        $response->assertViewIs('portuario.vessel-calls.create');
    }

    public function test_planificador_can_create_vessel_call(): void
    {
        $role = Role::where('code', 'PLANIFICADOR_PUERTO')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $vessel = Vessel::factory()->create();
        $berth = Berth::factory()->create();

        $data = [
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'viaje_id' => 'V2024TEST',
            'eta' => now()->addDays(3)->toDateTimeString(),
            'estado_llamada' => 'PROGRAMADA',
        ];

        $response = $this->actingAs($user)->post('/portuario/vessel-calls', $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('portuario.vessel_call', ['viaje_id' => 'V2024TEST']);
    }

    public function test_transportista_cannot_create_vessel_call(): void
    {
        $role = Role::where('code', 'TRANSPORTISTA')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $vessel = Vessel::factory()->create();

        $data = [
            'vessel_id' => $vessel->id,
            'eta' => now()->addDays(3)->toDateTimeString(),
            'estado_llamada' => 'PROGRAMADA',
        ];

        $response = $this->actingAs($user)->post('/portuario/vessel-calls', $data);

        $response->assertStatus(403);
    }

    public function test_vessel_call_requires_valid_data(): void
    {
        $role = Role::where('code', 'PLANIFICADOR_PUERTO')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->post('/portuario/vessel-calls', []);

        $response->assertSessionHasErrors(['vessel_id', 'eta', 'estado_llamada']);
    }

    public function test_vessel_call_eta_must_be_date(): void
    {
        $role = Role::where('code', 'PLANIFICADOR_PUERTO')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $vessel = Vessel::factory()->create();

        $data = [
            'vessel_id' => $vessel->id,
            'eta' => 'invalid-date',
            'estado_llamada' => 'PROGRAMADA',
        ];

        $response = $this->actingAs($user)->post('/portuario/vessel-calls', $data);

        $response->assertSessionHasErrors(['eta']);
    }

    public function test_detects_overlapping_vessel_calls_on_same_berth(): void
    {
        // Create a berth
        $berth = Berth::factory()->create();
        $vessel1 = Vessel::factory()->create();
        $vessel2 = Vessel::factory()->create();

        // Create first vessel call: ATB at 10:00, ATD at 14:00
        $atb1 = now()->setTime(10, 0, 0);
        $atd1 = now()->setTime(14, 0, 0);
        
        VesselCall::factory()->create([
            'berth_id' => $berth->id,
            'vessel_id' => $vessel1->id,
            'atb' => $atb1,
            'atd' => $atd1,
            'estado_llamada' => 'OPERANDO',
        ]);

        // Create second vessel call that overlaps: ATB at 12:00, ATD at 16:00
        $atb2 = now()->setTime(12, 0, 0);
        $atd2 = now()->setTime(16, 0, 0);
        
        VesselCall::factory()->create([
            'berth_id' => $berth->id,
            'vessel_id' => $vessel2->id,
            'atb' => $atb2,
            'atd' => $atd2,
            'estado_llamada' => 'OPERANDO',
        ]);

        // Query for overlapping vessel calls on the same berth
        $overlaps = VesselCall::where('berth_id', $berth->id)
            ->whereNotNull('atb')
            ->whereNotNull('atd')
            ->get()
            ->filter(function ($call1) use ($berth) {
                // Check if this call overlaps with any other call on the same berth
                return VesselCall::where('berth_id', $berth->id)
                    ->where('id', '!=', $call1->id)
                    ->whereNotNull('atb')
                    ->whereNotNull('atd')
                    ->where(function ($query) use ($call1) {
                        // Overlap condition: call1.atb < call2.atd AND call1.atd > call2.atb
                        $query->where('atb', '<', $call1->atd)
                              ->where('atd', '>', $call1->atb);
                    })
                    ->exists();
            });

        // Assert that we detected the overlap
        $this->assertGreaterThan(0, $overlaps->count(), 'Should detect overlapping vessel calls on the same berth');
    }

    public function test_no_overlap_when_vessel_calls_are_sequential(): void
    {
        // Create a berth
        $berth = Berth::factory()->create();
        $vessel1 = Vessel::factory()->create();
        $vessel2 = Vessel::factory()->create();

        // Create first vessel call: ATB at 10:00, ATD at 14:00
        $atb1 = now()->setTime(10, 0, 0);
        $atd1 = now()->setTime(14, 0, 0);
        
        VesselCall::factory()->create([
            'berth_id' => $berth->id,
            'vessel_id' => $vessel1->id,
            'atb' => $atb1,
            'atd' => $atd1,
            'estado_llamada' => 'ZARPO',
        ]);

        // Create second vessel call that does NOT overlap: ATB at 14:00, ATD at 18:00
        $atb2 = now()->setTime(14, 0, 0);
        $atd2 = now()->setTime(18, 0, 0);
        
        VesselCall::factory()->create([
            'berth_id' => $berth->id,
            'vessel_id' => $vessel2->id,
            'atb' => $atb2,
            'atd' => $atd2,
            'estado_llamada' => 'OPERANDO',
        ]);

        // Query for overlapping vessel calls on the same berth
        $overlaps = VesselCall::where('berth_id', $berth->id)
            ->whereNotNull('atb')
            ->whereNotNull('atd')
            ->get()
            ->filter(function ($call1) use ($berth) {
                return VesselCall::where('berth_id', $berth->id)
                    ->where('id', '!=', $call1->id)
                    ->whereNotNull('atb')
                    ->whereNotNull('atd')
                    ->where(function ($query) use ($call1) {
                        $query->where('atb', '<', $call1->atd)
                              ->where('atd', '>', $call1->atb);
                    })
                    ->exists();
            });

        // Assert that no overlaps were detected
        $this->assertEquals(0, $overlaps->count(), 'Should not detect overlaps when vessel calls are sequential');
    }
}
