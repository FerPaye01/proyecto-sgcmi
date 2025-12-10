<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Truck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointment_belongs_to_truck(): void
    {
        $truck = Truck::factory()->create();
        $appointment = Appointment::factory()->create(['truck_id' => $truck->id]);

        $this->assertInstanceOf(Truck::class, $appointment->truck);
        $this->assertEquals($truck->id, $appointment->truck->id);
    }

    public function test_appointment_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $appointment = Appointment::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $appointment->company);
        $this->assertEquals($company->id, $appointment->company->id);
    }

    public function test_appointment_casts_dates_correctly(): void
    {
        $appointment = Appointment::factory()->create([
            'hora_programada' => '2024-12-01 10:00:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $appointment->hora_programada);
    }

    public function test_appointment_has_default_estado(): void
    {
        $appointment = Appointment::factory()->create();

        $this->assertEquals('PROGRAMADA', $appointment->estado);
    }
}
