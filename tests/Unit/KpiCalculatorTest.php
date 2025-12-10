<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\Berth;
use App\Models\Company;
use App\Models\Gate;
use App\Models\GateEvent;
use App\Models\Tramite;
use App\Models\Truck;
use App\Models\Vessel;
use App\Models\VesselCall;
use App\Services\KpiCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for KpiCalculator service
 * Tests individual KPI calculation methods
 */
class KpiCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private KpiCalculator $kpiCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        // Drop all schemas to ensure clean state
        \DB::statement('DROP SCHEMA IF EXISTS admin CASCADE');
        \DB::statement('DROP SCHEMA IF EXISTS portuario CASCADE');
        \DB::statement('DROP SCHEMA IF EXISTS terrestre CASCADE');
        \DB::statement('DROP SCHEMA IF EXISTS aduanas CASCADE');
        \DB::statement('DROP SCHEMA IF EXISTS analytics CASCADE');
        \DB::statement('DROP SCHEMA IF EXISTS audit CASCADE');
        \DB::statement('DROP SCHEMA IF EXISTS reports CASCADE');

        // Run migrations
        $this->artisan('migrate:fresh');

        $this->kpiCalculator = new KpiCalculator();
    }

    /**
     * Test: calculateTurnaround calcula correctamente el tiempo de turnaround
     * Turnaround = ATD - ATA en horas
     */
    public function test_calculate_turnaround_returns_correct_hours(): void
    {
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();

        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'ata' => '2025-01-01 08:00:00',
            'atd' => '2025-01-01 20:00:00', // 12 horas después
        ]);

        $turnaround = $this->kpiCalculator->calculateTurnaround($vesselCall->id);

        $this->assertEquals(12.0, $turnaround);
    }

    /**
     * Test: calculateTurnaround retorna null si no hay ATA
     */
    public function test_calculate_turnaround_returns_null_when_no_ata(): void
    {
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();

        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'ata' => null,
            'atd' => '2025-01-01 20:00:00',
        ]);

        $turnaround = $this->kpiCalculator->calculateTurnaround($vesselCall->id);

        $this->assertNull($turnaround);
    }

    /**
     * Test: calculateTurnaround retorna null si no hay ATD
     */
    public function test_calculate_turnaround_returns_null_when_no_atd(): void
    {
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();

        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'ata' => '2025-01-01 08:00:00',
            'atd' => null,
        ]);

        $turnaround = $this->kpiCalculator->calculateTurnaround($vesselCall->id);

        $this->assertNull($turnaround);
    }

    /**
     * Test: calculateTurnaround retorna null si el vessel_call no existe
     */
    public function test_calculate_turnaround_returns_null_when_vessel_call_not_found(): void
    {
        $turnaround = $this->kpiCalculator->calculateTurnaround(99999);

        $this->assertNull($turnaround);
    }

    /**
     * Test: calculateWaitingTime calcula correctamente el tiempo de espera
     * Tiempo de espera = primer evento ENTRADA - hora_llegada en horas
     */
    public function test_calculate_waiting_time_returns_correct_hours(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);
        $gate = Gate::factory()->create();

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'hora_llegada' => '2025-01-01 08:00:00',
        ]);

        // Crear evento de entrada 2 horas después
        GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'cita_id' => $appointment->id,
            'action' => 'ENTRADA',
            'event_ts' => '2025-01-01 10:00:00',
        ]);

        $waitingTime = $this->kpiCalculator->calculateWaitingTime($appointment->id);

        $this->assertEquals(2.0, $waitingTime);
    }

    /**
     * Test: calculateWaitingTime retorna null si no hay hora_llegada
     */
    public function test_calculate_waiting_time_returns_null_when_no_hora_llegada(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'hora_llegada' => null,
        ]);

        $waitingTime = $this->kpiCalculator->calculateWaitingTime($appointment->id);

        $this->assertNull($waitingTime);
    }

    /**
     * Test: calculateWaitingTime retorna null si no hay eventos de entrada
     */
    public function test_calculate_waiting_time_returns_null_when_no_gate_events(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'hora_llegada' => '2025-01-01 08:00:00',
        ]);

        $waitingTime = $this->kpiCalculator->calculateWaitingTime($appointment->id);

        $this->assertNull($waitingTime);
    }

    /**
     * Test: calculateWaitingTime usa el primer evento de entrada
     */
    public function test_calculate_waiting_time_uses_first_entrada_event(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);
        $gate = Gate::factory()->create();

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'hora_llegada' => '2025-01-01 08:00:00',
        ]);

        // Crear múltiples eventos de entrada
        GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'cita_id' => $appointment->id,
            'action' => 'ENTRADA',
            'event_ts' => '2025-01-01 10:00:00', // Primer evento
        ]);

        GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'cita_id' => $appointment->id,
            'action' => 'ENTRADA',
            'event_ts' => '2025-01-01 12:00:00', // Segundo evento (no debe usarse)
        ]);

        $waitingTime = $this->kpiCalculator->calculateWaitingTime($appointment->id);

        $this->assertEquals(2.0, $waitingTime); // Usa el primer evento (10:00)
    }

    /**
     * Test: calculateWaitingTime no retorna valores negativos
     */
    public function test_calculate_waiting_time_does_not_return_negative_values(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);
        $gate = Gate::factory()->create();

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'hora_llegada' => '2025-01-01 10:00:00',
        ]);

        // Evento de entrada antes de la hora de llegada (caso anómalo)
        GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'cita_id' => $appointment->id,
            'action' => 'ENTRADA',
            'event_ts' => '2025-01-01 08:00:00',
        ]);

        $waitingTime = $this->kpiCalculator->calculateWaitingTime($appointment->id);

        $this->assertEquals(0.0, $waitingTime); // No debe ser negativo
    }

    /**
     * Test: calculateAppointmentCompliance clasifica correctamente como A_TIEMPO
     * A_TIEMPO = desvío <= 15 minutos
     */
    public function test_calculate_appointment_compliance_classifies_a_tiempo(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'hora_programada' => '2025-01-01 08:00:00',
            'hora_llegada' => '2025-01-01 08:10:00', // +10 minutos
        ]);

        $result = $this->kpiCalculator->calculateAppointmentCompliance($appointment->id);

        $this->assertEquals('A_TIEMPO', $result['clasificacion']);
        $this->assertEquals(10.0, $result['desvio_min']);
    }

    /**
     * Test: calculateAppointmentCompliance clasifica correctamente como TARDE
     * TARDE = desvío > 15 minutos
     */
    public function test_calculate_appointment_compliance_classifies_tarde(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'hora_programada' => '2025-01-01 08:00:00',
            'hora_llegada' => '2025-01-01 08:30:00', // +30 minutos
        ]);

        $result = $this->kpiCalculator->calculateAppointmentCompliance($appointment->id);

        $this->assertEquals('TARDE', $result['clasificacion']);
        $this->assertEquals(30.0, $result['desvio_min']);
    }

    /**
     * Test: calculateAppointmentCompliance clasifica correctamente como NO_SHOW
     * NO_SHOW = sin hora_llegada
     */
    public function test_calculate_appointment_compliance_classifies_no_show(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'hora_programada' => '2025-01-01 08:00:00',
            'hora_llegada' => null,
        ]);

        $result = $this->kpiCalculator->calculateAppointmentCompliance($appointment->id);

        $this->assertEquals('NO_SHOW', $result['clasificacion']);
        $this->assertNull($result['desvio_min']);
    }

    /**
     * Test: calculateAppointmentCompliance clasifica NO_SHOW por estado
     */
    public function test_calculate_appointment_compliance_classifies_no_show_by_estado(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'hora_programada' => '2025-01-01 08:00:00',
            'hora_llegada' => '2025-01-01 08:10:00',
            'estado' => 'NO_SHOW',
        ]);

        $result = $this->kpiCalculator->calculateAppointmentCompliance($appointment->id);

        $this->assertEquals('NO_SHOW', $result['clasificacion']);
        $this->assertNull($result['desvio_min']);
    }

    /**
     * Test: calculateAppointmentCompliance maneja llegadas adelantadas
     */
    public function test_calculate_appointment_compliance_handles_early_arrivals(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'hora_programada' => '2025-01-01 08:00:00',
            'hora_llegada' => '2025-01-01 07:50:00', // -10 minutos
        ]);

        $result = $this->kpiCalculator->calculateAppointmentCompliance($appointment->id);

        $this->assertEquals('A_TIEMPO', $result['clasificacion']);
        $this->assertEquals(-10.0, $result['desvio_min']);
    }

    /**
     * Test: calculateCustomsLeadTime calcula correctamente el lead time
     * Lead time = fecha_fin - fecha_inicio en horas
     */
    public function test_calculate_customs_lead_time_returns_correct_hours(): void
    {
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();
        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
        ]);

        $tramite = Tramite::factory()->create([
            'vessel_call_id' => $vesselCall->id,
            'estado' => 'APROBADO',
            'fecha_inicio' => '2025-01-01 08:00:00',
            'fecha_fin' => '2025-01-02 08:00:00', // 24 horas después
        ]);

        $leadTime = $this->kpiCalculator->calculateCustomsLeadTime($tramite->id);

        $this->assertEquals(24.0, $leadTime);
    }

    /**
     * Test: calculateCustomsLeadTime retorna null si el trámite no está aprobado
     */
    public function test_calculate_customs_lead_time_returns_null_when_not_approved(): void
    {
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();
        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
        ]);

        $tramite = Tramite::factory()->create([
            'vessel_call_id' => $vesselCall->id,
            'estado' => 'EN_REVISION',
            'fecha_inicio' => '2025-01-01 08:00:00',
            'fecha_fin' => '2025-01-02 08:00:00',
        ]);

        $leadTime = $this->kpiCalculator->calculateCustomsLeadTime($tramite->id);

        $this->assertNull($leadTime);
    }

    /**
     * Test: calculateCustomsLeadTime retorna null si no hay fecha_fin
     */
    public function test_calculate_customs_lead_time_returns_null_when_no_fecha_fin(): void
    {
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();
        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
        ]);

        $tramite = Tramite::factory()->create([
            'vessel_call_id' => $vesselCall->id,
            'estado' => 'APROBADO',
            'fecha_inicio' => '2025-01-01 08:00:00',
            'fecha_fin' => null,
        ]);

        $leadTime = $this->kpiCalculator->calculateCustomsLeadTime($tramite->id);

        $this->assertNull($leadTime);
    }

    /**
     * Test: calculateCustomsLeadTime retorna null si el trámite no existe
     */
    public function test_calculate_customs_lead_time_returns_null_when_tramite_not_found(): void
    {
        $leadTime = $this->kpiCalculator->calculateCustomsLeadTime(99999);

        $this->assertNull($leadTime);
    }
}
