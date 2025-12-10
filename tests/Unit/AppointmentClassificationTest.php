<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Truck;
use App\Models\VesselCall;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for Appointment Classification
 * Requirements: US-3.3 - Clasificación de citas: A tiempo (±15 min), Tarde, No Show
 */
class AppointmentClassificationTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $reportService;

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

        $this->reportService = new ReportService();
    }

    /**
     * Test: Cita clasificada como A_TIEMPO cuando llega exactamente a tiempo
     * Requirements: US-3.3 - Clasificación A tiempo (±15 min)
     */
    public function test_classifies_appointment_as_on_time_when_exact(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);
        $vesselCall = VesselCall::factory()->create();

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 10:00:00',
            'hora_llegada' => '2025-01-01 10:00:00', // Exacto
            'estado' => 'ATENDIDA',
        ]);

        $result = $this->reportService->generateR5([], null);

        $classified = $result['data']->first();
        $this->assertEquals('A_TIEMPO', $classified->clasificacion);
        $this->assertEquals(0.0, $classified->desvio_min);
    }

    /**
     * Test: Cita clasificada como A_TIEMPO cuando llega 15 minutos antes
     * Requirements: US-3.3 - Clasificación A tiempo (±15 min)
     */
    public function test_classifies_appointment_as_on_time_when_15_min_early(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);
        $vesselCall = VesselCall::factory()->create();

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 10:00:00',
            'hora_llegada' => '2025-01-01 09:45:00', // -15 min
            'estado' => 'ATENDIDA',
        ]);

        $result = $this->reportService->generateR5([], null);

        $classified = $result['data']->first();
        $this->assertEquals('A_TIEMPO', $classified->clasificacion);
        $this->assertEquals(-15.0, $classified->desvio_min);
    }

    /**
     * Test: Cita clasificada como A_TIEMPO cuando llega 15 minutos después
     * Requirements: US-3.3 - Clasificación A tiempo (±15 min)
     */
    public function test_classifies_appointment_as_on_time_when_15_min_late(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);
        $vesselCall = VesselCall::factory()->create();

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 10:00:00',
            'hora_llegada' => '2025-01-01 10:15:00', // +15 min
            'estado' => 'ATENDIDA',
        ]);

        $result = $this->reportService->generateR5([], null);

        $classified = $result['data']->first();
        $this->assertEquals('A_TIEMPO', $classified->clasificacion);
        $this->assertEquals(15.0, $classified->desvio_min);
    }

    /**
     * Test: Cita clasificada como TARDE cuando llega 16 minutos después
     * Requirements: US-3.3 - Clasificación Tarde (>15 min)
     */
    public function test_classifies_appointment_as_late_when_16_min_late(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);
        $vesselCall = VesselCall::factory()->create();

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 10:00:00',
            'hora_llegada' => '2025-01-01 10:16:00', // +16 min
            'estado' => 'ATENDIDA',
        ]);

        $result = $this->reportService->generateR5([], null);

        $classified = $result['data']->first();
        $this->assertEquals('TARDE', $classified->clasificacion);
        $this->assertEquals(16.0, $classified->desvio_min);
    }

    /**
     * Test: Cita clasificada como TARDE cuando llega 1 hora después
     * Requirements: US-3.3 - Clasificación Tarde (>15 min)
     */
    public function test_classifies_appointment_as_late_when_1_hour_late(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);
        $vesselCall = VesselCall::factory()->create();

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 10:00:00',
            'hora_llegada' => '2025-01-01 11:00:00', // +60 min
            'estado' => 'ATENDIDA',
        ]);

        $result = $this->reportService->generateR5([], null);

        $classified = $result['data']->first();
        $this->assertEquals('TARDE', $classified->clasificacion);
        $this->assertEquals(60.0, $classified->desvio_min);
    }

    /**
     * Test: Cita clasificada como TARDE cuando llega 16 minutos antes
     * Requirements: US-3.3 - Clasificación Tarde (>15 min adelantado)
     */
    public function test_classifies_appointment_as_late_when_16_min_early(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);
        $vesselCall = VesselCall::factory()->create();

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 10:00:00',
            'hora_llegada' => '2025-01-01 09:44:00', // -16 min
            'estado' => 'ATENDIDA',
        ]);

        $result = $this->reportService->generateR5([], null);

        $classified = $result['data']->first();
        $this->assertEquals('TARDE', $classified->clasificacion);
        $this->assertEquals(-16.0, $classified->desvio_min);
    }

    /**
     * Test: Cita clasificada como NO_SHOW cuando no hay hora_llegada
     * Requirements: US-3.3 - Clasificación No Show (sin llegada)
     */
    public function test_classifies_appointment_as_no_show_when_no_arrival(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);
        $vesselCall = VesselCall::factory()->create();

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 10:00:00',
            'hora_llegada' => null, // Sin llegada
            'estado' => 'CONFIRMADA',
        ]);

        $result = $this->reportService->generateR5([], null);

        $classified = $result['data']->first();
        $this->assertEquals('NO_SHOW', $classified->clasificacion);
        $this->assertNull($classified->desvio_min);
    }

    /**
     * Test: Cita clasificada como NO_SHOW cuando estado es NO_SHOW
     * Requirements: US-3.3 - Clasificación No Show (estado)
     */
    public function test_classifies_appointment_as_no_show_when_status_is_no_show(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);
        $vesselCall = VesselCall::factory()->create();

        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 10:00:00',
            'hora_llegada' => null,
            'estado' => 'NO_SHOW',
        ]);

        $result = $this->reportService->generateR5([], null);

        $classified = $result['data']->first();
        $this->assertEquals('NO_SHOW', $classified->clasificacion);
        $this->assertNull($classified->desvio_min);
    }

    /**
     * Test: KPIs calculan correctamente con clasificaciones mixtas
     * Requirements: US-3.3 - KPIs: pct_no_show, pct_tarde, desvio_medio_min
     */
    public function test_calculates_kpis_correctly_with_mixed_classifications(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);
        $vesselCall = VesselCall::factory()->create();

        // 2 A tiempo
        Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 10:00:00',
            'hora_llegada' => '2025-01-01 10:00:00',
            'estado' => 'ATENDIDA',
        ]);

        Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 11:00:00',
            'hora_llegada' => '2025-01-01 11:10:00', // +10 min
            'estado' => 'ATENDIDA',
        ]);

        // 2 Tarde
        Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 12:00:00',
            'hora_llegada' => '2025-01-01 12:20:00', // +20 min
            'estado' => 'ATENDIDA',
        ]);

        Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 13:00:00',
            'hora_llegada' => '2025-01-01 13:30:00', // +30 min
            'estado' => 'ATENDIDA',
        ]);

        // 1 No Show
        Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 14:00:00',
            'hora_llegada' => null,
            'estado' => 'NO_SHOW',
        ]);

        $result = $this->reportService->generateR5([], null);

        $kpis = $result['kpis'];

        // Total: 5 citas
        $this->assertEquals(5, $kpis['total_citas']);

        // pct_no_show: 1/5 = 20%
        $this->assertEquals(20.0, $kpis['pct_no_show']);

        // pct_tarde: 2/5 = 40%
        $this->assertEquals(40.0, $kpis['pct_tarde']);

        // desvio_medio_min: (0 + 10 + 20 + 30) / 4 = 15 min (no incluye NO_SHOW)
        $this->assertEquals(15.0, $kpis['desvio_medio_min']);
    }

    /**
     * Test: Clasificación funciona con múltiples citas
     * Requirements: US-3.3 - Clasificación de múltiples citas
     */
    public function test_classifies_multiple_appointments_correctly(): void
    {
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);
        $vesselCall = VesselCall::factory()->create();

        // A tiempo
        Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 10:00:00',
            'hora_llegada' => '2025-01-01 10:05:00',
            'estado' => 'ATENDIDA',
        ]);

        // Tarde
        Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 11:00:00',
            'hora_llegada' => '2025-01-01 11:20:00',
            'estado' => 'ATENDIDA',
        ]);

        // No Show
        Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_programada' => '2025-01-01 12:00:00',
            'hora_llegada' => null,
            'estado' => 'NO_SHOW',
        ]);

        $result = $this->reportService->generateR5([], null);

        $classifications = $result['data']->pluck('clasificacion')->toArray();

        $this->assertContains('A_TIEMPO', $classifications);
        $this->assertContains('TARDE', $classifications);
        $this->assertContains('NO_SHOW', $classifications);
        $this->assertCount(3, $classifications);
    }
}
