<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Berth;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselCall;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for R11 Early Warning with Push Notifications
 * Tests that R11 report generation triggers push notifications
 *
 * Requirements: US-5.2 - Sistema de Alertas Tempranas con Notificaciones push
 */
class R11NotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $notificationService;
    private User $user;
    private Role $operacionesRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = app(NotificationService::class);

        // Crear roles
        $this->operacionesRole = Role::factory()->create([
            'code' => 'OPERACIONES_PUERTO',
            'name' => 'Operaciones Puerto',
        ]);

        // Crear usuario con rol OPERACIONES_PUERTO
        $this->user = User::factory()->create();
        $this->user->roles()->attach($this->operacionesRole);

        // Crear permisos
        $kpiReadPermission = Permission::factory()->create([
            'code' => 'KPI_READ',
            'name' => 'Leer KPIs',
        ]);
        $this->operacionesRole->permissions()->attach($kpiReadPermission);

        // Limpiar notificaciones antes de cada test
        $this->notificationService->clearAllNotifications();
    }

    /**
     * Test: R11 report generation triggers push notifications
     *
     * Requirements: US-5.2 - Sistema de Alertas Tempranas con Notificaciones push
     * Property: R11 report generation triggers push notifications
     * Validates: Requirements 5.2
     */
    public function test_r11_report_generation_triggers_push_notifications(): void
    {
        // Crear muelle
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();

        $now = now();

        // Crear vessel calls con alta utilización para generar alertas
        for ($i = 0; $i < 3; $i++) {
            VesselCall::factory()->create([
                'vessel_id' => $vessel->id,
                'berth_id' => $berth->id,
                'atb' => $now->clone()->addHours($i * 2),
                'atd' => $now->clone()->addHours($i * 2 + 3),
            ]);
        }

        // Generar reporte R11 (que dispara notificaciones)
        $response = $this->actingAs($this->user)
            ->get(route('reports.r11'));

        $response->assertStatus(200);

        // Verificar que las notificaciones fueron guardadas
        $notificaciones = $this->notificationService->getAllNotifications();
        $this->assertNotEmpty($notificaciones);
    }

    /**
     * Test: R11 API endpoint returns alerts with notifications
     *
     * Requirements: US-5.2 - API endpoint para obtener alertas tempranas
     * Property: R11 API endpoint returns alerts
     * Validates: Requirements 5.2
     */
    public function test_r11_api_endpoint_returns_alerts_with_notifications(): void
    {
        // Crear muelle
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();

        $now = now();

        // Crear vessel calls con alta utilización para generar alertas
        for ($i = 0; $i < 3; $i++) {
            VesselCall::factory()->create([
                'vessel_id' => $vessel->id,
                'berth_id' => $berth->id,
                'atb' => $now->clone()->addHours($i * 2),
                'atd' => $now->clone()->addHours($i * 2 + 3),
            ]);
        }

        // Generar reporte R11 API (que dispara notificaciones)
        $response = $this->actingAs($this->user)
            ->getJson(route('reports.r11.api'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'alertas',
            'kpis',
            'estado_general',
            'timestamp',
        ]);

        // Verificar que las notificaciones fueron guardadas
        $notificaciones = $this->notificationService->getAllNotifications();
        $this->assertNotEmpty($notificaciones);
    }

    /**
     * Test: Notifications are sent to correct roles
     *
     * Requirements: US-5.2 - Notificaciones push a OPERACIONES_PUERTO y PLANIFICADOR_PUERTO
     * Property: Notifications are sent to correct roles
     * Validates: Requirements 5.2
     */
    public function test_notifications_are_sent_to_correct_roles(): void
    {
        // Crear muelle
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();

        $now = now();

        // Crear vessel calls con alta utilización para generar alertas
        for ($i = 0; $i < 3; $i++) {
            VesselCall::factory()->create([
                'vessel_id' => $vessel->id,
                'berth_id' => $berth->id,
                'atb' => $now->clone()->addHours($i * 2),
                'atd' => $now->clone()->addHours($i * 2 + 3),
            ]);
        }

        // Generar reporte R11
        $this->actingAs($this->user)
            ->get(route('reports.r11'));

        // Obtener notificaciones para OPERACIONES_PUERTO
        $notificacionesOperaciones = $this->notificationService->getNotificationsForRole('OPERACIONES_PUERTO');

        $this->assertNotEmpty($notificacionesOperaciones);
        $this->assertTrue($notificacionesOperaciones->every(function ($notificacion) {
            return in_array('OPERACIONES_PUERTO', $notificacion['destinatarios']);
        }));
    }

    /**
     * Test: Notifications include alert details from R11
     *
     * Requirements: US-5.2 - Notificaciones incluyen detalles de alertas
     * Property: Notifications include alert details
     * Validates: Requirements 5.2
     */
    public function test_notifications_include_alert_details_from_r11(): void
    {
        // Crear muelle
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();

        $now = now();

        // Crear vessel calls con alta utilización para generar alertas
        for ($i = 0; $i < 3; $i++) {
            VesselCall::factory()->create([
                'vessel_id' => $vessel->id,
                'berth_id' => $berth->id,
                'atb' => $now->clone()->addHours($i * 2),
                'atd' => $now->clone()->addHours($i * 2 + 3),
            ]);
        }

        // Generar reporte R11
        $this->actingAs($this->user)
            ->get(route('reports.r11'));

        // Obtener notificaciones
        $notificaciones = $this->notificationService->getAllNotifications();
        $ultimaNotificacion = $notificaciones->last();

        $this->assertNotNull($ultimaNotificacion);
        $this->assertArrayHasKey('alertas', $ultimaNotificacion);
        $this->assertNotEmpty($ultimaNotificacion['alertas']);

        // Verificar que las alertas tienen la estructura correcta
        $alerta = $ultimaNotificacion['alertas'][0];
        $this->assertArrayHasKey('id', $alerta);
        $this->assertArrayHasKey('tipo', $alerta);
        $this->assertArrayHasKey('nivel', $alerta);
        $this->assertArrayHasKey('descripción', $alerta);
        $this->assertArrayHasKey('acciones_recomendadas', $alerta);
    }
}
