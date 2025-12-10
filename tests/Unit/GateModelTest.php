<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\Gate;
use App\Models\GateEvent;
use App\Models\Truck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GateModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_gate_can_be_created_with_factory(): void
    {
        $gate = Gate::factory()->create([
            'code' => 'TEST-G1',
            'name' => 'Test Gate 1',
            'activo' => true,
        ]);

        $this->assertDatabaseHas('terrestre.gate', [
            'code' => 'TEST-G1',
            'name' => 'Test Gate 1',
            'activo' => true,
        ]);

        $this->assertEquals('TEST-G1', $gate->code);
        $this->assertEquals('Test Gate 1', $gate->name);
        $this->assertTrue($gate->activo);
    }

    public function test_gate_has_many_gate_events(): void
    {
        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();

        GateEvent::factory()->count(3)->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
        ]);

        $this->assertCount(3, $gate->gateEvents);
        $this->assertInstanceOf(GateEvent::class, $gate->gateEvents->first());
    }

    public function test_gate_event_can_be_created_with_factory(): void
    {
        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();

        $gateEvent = GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'ENTRADA',
        ]);

        $this->assertDatabaseHas('terrestre.gate_event', [
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'ENTRADA',
        ]);

        $this->assertEquals('ENTRADA', $gateEvent->action);
    }

    public function test_gate_event_belongs_to_gate(): void
    {
        $gate = Gate::factory()->create(['name' => 'Main Gate']);
        $truck = Truck::factory()->create();

        $gateEvent = GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
        ]);

        $this->assertInstanceOf(Gate::class, $gateEvent->gate);
        $this->assertEquals('Main Gate', $gateEvent->gate->name);
    }

    public function test_gate_event_belongs_to_truck(): void
    {
        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create(['placa' => 'XYZ999']);

        $gateEvent = GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
        ]);

        $this->assertInstanceOf(Truck::class, $gateEvent->truck);
        $this->assertEquals('XYZ999', $gateEvent->truck->placa);
    }

    public function test_gate_event_can_belong_to_appointment(): void
    {
        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();
        $appointment = Appointment::factory()->create(['truck_id' => $truck->id]);

        $gateEvent = GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'cita_id' => $appointment->id,
        ]);

        $this->assertInstanceOf(Appointment::class, $gateEvent->appointment);
        $this->assertEquals($appointment->id, $gateEvent->appointment->id);
    }

    public function test_gate_event_factory_entrance_state(): void
    {
        $gateEvent = GateEvent::factory()->entrance()->create();

        $this->assertEquals('ENTRADA', $gateEvent->action);
    }

    public function test_gate_event_factory_exit_state(): void
    {
        $gateEvent = GateEvent::factory()->exit()->create();

        $this->assertEquals('SALIDA', $gateEvent->action);
    }

    public function test_gate_event_factory_with_appointment_state(): void
    {
        $gateEvent = GateEvent::factory()->withAppointment()->create();

        $this->assertNotNull($gateEvent->cita_id);
        $this->assertInstanceOf(Appointment::class, $gateEvent->appointment);
    }

    public function test_gate_event_extra_field_is_cast_to_array(): void
    {
        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();

        $gateEvent = GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'extra' => ['nota' => 'Test note', 'priority' => 'high'],
        ]);

        $this->assertIsArray($gateEvent->extra);
        $this->assertEquals('Test note', $gateEvent->extra['nota']);
        $this->assertEquals('high', $gateEvent->extra['priority']);
    }

    public function test_each_entrada_has_corresponding_salida(): void
    {
        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();

        // Create a complete cycle: entrada followed by salida
        $entradaTime = now()->subHours(2);
        $salidaTime = now()->subHour();

        GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'ENTRADA',
            'event_ts' => $entradaTime,
        ]);

        GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'SALIDA',
            'event_ts' => $salidaTime,
        ]);

        // Verify temporal integrity: each entrada should have a corresponding salida
        $entradas = GateEvent::where('action', 'ENTRADA')
            ->where('truck_id', $truck->id)
            ->orderBy('event_ts')
            ->get();

        $salidas = GateEvent::where('action', 'SALIDA')
            ->where('truck_id', $truck->id)
            ->orderBy('event_ts')
            ->get();

        // Each entrada should have at least one salida after it
        foreach ($entradas as $entrada) {
            $hasSalida = $salidas->first(function ($salida) use ($entrada) {
                return $salida->event_ts >= $entrada->event_ts;
            });

            $this->assertNotNull(
                $hasSalida,
                "Entrada at {$entrada->event_ts} for truck {$truck->placa} has no corresponding salida"
            );
        }

        // Verify count: should have equal number of entradas and salidas for complete cycles
        $this->assertEquals(
            $entradas->count(),
            $salidas->count(),
            "Number of entradas ({$entradas->count()}) should match salidas ({$salidas->count()})"
        );
    }

    public function test_detects_missing_salida_for_entrada(): void
    {
        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();

        // Create an entrada without a corresponding salida (incomplete cycle)
        GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'ENTRADA',
            'event_ts' => now()->subHours(2),
        ]);

        // Verify that we can detect the missing salida
        $entradas = GateEvent::where('action', 'ENTRADA')
            ->where('truck_id', $truck->id)
            ->orderBy('event_ts')
            ->get();

        $salidas = GateEvent::where('action', 'SALIDA')
            ->where('truck_id', $truck->id)
            ->orderBy('event_ts')
            ->get();

        // Should have 1 entrada and 0 salidas
        $this->assertEquals(1, $entradas->count());
        $this->assertEquals(0, $salidas->count());

        // This indicates an incomplete cycle
        $this->assertNotEquals(
            $entradas->count(),
            $salidas->count(),
            "Incomplete cycle detected: entrada without salida"
        );
    }

    public function test_validates_temporal_sequence_entrada_before_salida(): void
    {
        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();

        // Create a valid sequence: entrada followed by salida
        $entradaTime = now()->subHours(2);
        $salidaTime = now()->subHour();

        $entrada = GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'ENTRADA',
            'event_ts' => $entradaTime,
        ]);

        $salida = GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'SALIDA',
            'event_ts' => $salidaTime,
        ]);

        // Verify temporal sequence: entrada timestamp < salida timestamp
        $this->assertTrue(
            $entrada->event_ts < $salida->event_ts,
            "Entrada timestamp ({$entrada->event_ts}) must be before salida timestamp ({$salida->event_ts})"
        );

        // Verify the time difference is positive (salida is after entrada)
        $timeDiffMinutes = $entrada->event_ts->diffInMinutes($salida->event_ts, false);
        $this->assertGreaterThan(
            0,
            $timeDiffMinutes,
            "Time difference between entrada and salida must be positive (got {$timeDiffMinutes} minutes)"
        );
    }

    public function test_detects_invalid_temporal_sequence(): void
    {
        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();

        // Create an invalid sequence: salida before entrada (should not happen in real system)
        $entradaTime = now()->subHour();
        $salidaTime = now()->subHours(2); // Earlier than entrada - invalid!

        $entrada = GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'ENTRADA',
            'event_ts' => $entradaTime,
        ]);

        $salida = GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'SALIDA',
            'event_ts' => $salidaTime,
        ]);

        // Verify we can detect the invalid sequence
        $this->assertFalse(
            $entrada->event_ts < $salida->event_ts,
            "Invalid temporal sequence detected: salida ({$salida->event_ts}) is before entrada ({$entrada->event_ts})"
        );

        // This should be caught by validation logic in production
        $this->assertGreaterThan(
            $salida->event_ts,
            $entrada->event_ts,
            "Entrada timestamp should be after salida timestamp in this invalid case"
        );
    }

    public function test_validates_temporal_sequence_for_multiple_trucks(): void
    {
        $gate = Gate::factory()->create();
        $truck1 = Truck::factory()->create(['placa' => 'ABC123']);
        $truck2 = Truck::factory()->create(['placa' => 'XYZ789']);

        // Create valid sequences for multiple trucks
        $baseTime = now()->subHours(4);

        // Truck 1: entrada at T+0, salida at T+1
        $entrada1 = GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck1->id,
            'action' => 'ENTRADA',
            'event_ts' => $baseTime,
        ]);

        $salida1 = GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck1->id,
            'action' => 'SALIDA',
            'event_ts' => $baseTime->copy()->addHour(),
        ]);

        // Truck 2: entrada at T+2, salida at T+3
        $entrada2 = GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck2->id,
            'action' => 'ENTRADA',
            'event_ts' => $baseTime->copy()->addHours(2),
        ]);

        $salida2 = GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck2->id,
            'action' => 'SALIDA',
            'event_ts' => $baseTime->copy()->addHours(3),
        ]);

        // Verify temporal sequence for truck 1
        $this->assertTrue(
            $entrada1->event_ts < $salida1->event_ts,
            "Truck 1: entrada must be before salida"
        );

        // Verify temporal sequence for truck 2
        $this->assertTrue(
            $entrada2->event_ts < $salida2->event_ts,
            "Truck 2: entrada must be before salida"
        );

        // Verify all events are in correct chronological order
        $allEvents = GateEvent::where('gate_id', $gate->id)
            ->orderBy('event_ts')
            ->get();

        $this->assertCount(4, $allEvents);

        // Verify the sequence: entrada1 < salida1 < entrada2 < salida2
        $this->assertTrue($allEvents[0]->event_ts < $allEvents[1]->event_ts);
        $this->assertTrue($allEvents[1]->event_ts < $allEvents[2]->event_ts);
        $this->assertTrue($allEvents[2]->event_ts < $allEvents[3]->event_ts);
    }
}
