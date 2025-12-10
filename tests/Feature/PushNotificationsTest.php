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
 * Feature tests for Push Notifications (Mock)
 * Tests notification sending and persistence
 *
 * Requirements: US-5.2 - Notificaciones push (mock) a OPERACIONES_PUERTO y PLANIFICADOR_PUERTO
 */
class PushNotificationsTest extends TestCase
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
     * Test: Push notifications are sent when alerts are generated
     *
     * Requirements: US-5.2 - Notificaciones push (mock) a OPERACIONES_PUERTO y PLANIFICADOR_PUERTO
     * Property: Notifications are sent when alerts are generated
     * Validates: Requirements 5.2
     */
    public function test_push_notifications_are_sent_when_alerts_are_generated(): void
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
     * Test: Notifications are persisted to mock file
     *
     * Requirements: US-5.2 - Notificaciones push (mock en storage/app/mocks/notifications.json)
     * Property: Notifications are persisted to mock file
     * Validates: Requirements 5.2
     */
    public function test_notifications_are_persisted_to_mock_file(): void
    {
        // Crear alertas de prueba
        $alertas = collect([
            [
                'id' => 'TEST_ALERT_1',
                'tipo' => 'CONGESTIÓN_MUELLE',
                'nivel' => 'AMARILLO',
                'descripción' => 'Test alert 1',
                'acciones_recomendadas' => ['Acción 1', 'Acción 2'],
            ],
        ]);

        // Enviar notificaciones
        $resultado = $this->notificationService->sendPushNotifications($alertas);

        $this->assertTrue($resultado);

        // Verificar que el archivo existe
        $mockPath = storage_path('app/mocks/notifications.json');
        $this->assertFileExists($mockPath);

        // Verificar que contiene las notificaciones
        $contenido = file_get_contents($mockPath);
        $notificaciones = json_decode($contenido, true);

        $this->assertIsArray($notificaciones);
        $this->assertNotEmpty($notificaciones);
    }

    /**
     * Test: Notifications include correct recipients
     *
     * Requirements: US-5.2 - Notificaciones push a OPERACIONES_PUERTO y PLANIFICADOR_PUERTO
     * Property: Notifications are sent to correct recipients
     * Validates: Requirements 5.2
     */
    public function test_notifications_include_correct_recipients(): void
    {
        // Crear alertas de prueba
        $alertas = collect([
            [
                'id' => 'TEST_ALERT_1',
                'tipo' => 'CONGESTIÓN_MUELLE',
                'nivel' => 'AMARILLO',
                'descripción' => 'Test alert',
                'acciones_recomendadas' => ['Acción 1'],
            ],
        ]);

        // Enviar notificaciones
        $this->notificationService->sendPushNotifications($alertas);

        // Obtener notificaciones
        $notificaciones = $this->notificationService->getAllNotifications();
        $ultimaNotificacion = $notificaciones->last();

        $this->assertNotNull($ultimaNotificacion);
        $this->assertContains('OPERACIONES_PUERTO', $ultimaNotificacion['destinatarios']);
        $this->assertContains('PLANIFICADOR_PUERTO', $ultimaNotificacion['destinatarios']);
    }

    /**
     * Test: Notifications include timestamp
     *
     * Requirements: US-5.2 - Notificaciones push con timestamp
     * Property: Notifications include timestamp
     * Validates: Requirements 5.2
     */
    public function test_notifications_include_timestamp(): void
    {
        // Crear alertas de prueba
        $alertas = collect([
            [
                'id' => 'TEST_ALERT_1',
                'tipo' => 'CONGESTIÓN_MUELLE',
                'nivel' => 'AMARILLO',
                'descripción' => 'Test alert',
                'acciones_recomendadas' => ['Acción 1'],
            ],
        ]);

        // Enviar notificaciones
        $this->notificationService->sendPushNotifications($alertas);

        // Obtener notificaciones
        $notificaciones = $this->notificationService->getAllNotifications();
        $ultimaNotificacion = $notificaciones->last();

        $this->assertNotNull($ultimaNotificacion);
        $this->assertArrayHasKey('timestamp', $ultimaNotificacion);
        $this->assertNotEmpty($ultimaNotificacion['timestamp']);
    }

    /**
     * Test: Notifications include alert details
     *
     * Requirements: US-5.2 - Notificaciones push incluyen detalles de alertas
     * Property: Notifications include alert details
     * Validates: Requirements 5.2
     */
    public function test_notifications_include_alert_details(): void
    {
        // Crear alertas de prueba
        $alertas = collect([
            [
                'id' => 'TEST_ALERT_1',
                'tipo' => 'CONGESTIÓN_MUELLE',
                'nivel' => 'AMARILLO',
                'descripción' => 'Test alert description',
                'acciones_recomendadas' => ['Acción 1', 'Acción 2'],
            ],
        ]);

        // Enviar notificaciones
        $this->notificationService->sendPushNotifications($alertas);

        // Obtener notificaciones
        $notificaciones = $this->notificationService->getAllNotifications();
        $ultimaNotificacion = $notificaciones->last();

        $this->assertNotNull($ultimaNotificacion);
        $this->assertArrayHasKey('alertas', $ultimaNotificacion);
        $this->assertNotEmpty($ultimaNotificacion['alertas']);

        $alerta = $ultimaNotificacion['alertas'][0];
        $this->assertEquals('TEST_ALERT_1', $alerta['id']);
        $this->assertEquals('CONGESTIÓN_MUELLE', $alerta['tipo']);
        $this->assertEquals('AMARILLO', $alerta['nivel']);
        $this->assertEquals('Test alert description', $alerta['descripción']);
        $this->assertContains('Acción 1', $alerta['acciones_recomendadas']);
    }

    /**
     * Test: Empty alerts do not generate notifications
     *
     * Requirements: US-5.2 - No enviar notificaciones si no hay alertas
     * Property: Empty alerts do not generate notifications
     * Validates: Requirements 5.2
     */
    public function test_empty_alerts_do_not_generate_notifications(): void
    {
        // Crear alertas vacías
        $alertas = collect();

        // Enviar notificaciones
        $resultado = $this->notificationService->sendPushNotifications($alertas);

        $this->assertFalse($resultado);
    }

    /**
     * Test: Get notifications for specific role
     *
     * Requirements: US-5.2 - Obtener notificaciones por rol
     * Property: Notifications can be retrieved by role
     * Validates: Requirements 5.2
     */
    public function test_get_notifications_for_specific_role(): void
    {
        // Crear alertas de prueba
        $alertas = collect([
            [
                'id' => 'TEST_ALERT_1',
                'tipo' => 'CONGESTIÓN_MUELLE',
                'nivel' => 'AMARILLO',
                'descripción' => 'Test alert',
                'acciones_recomendadas' => ['Acción 1'],
            ],
        ]);

        // Enviar notificaciones
        $this->notificationService->sendPushNotifications($alertas);

        // Obtener notificaciones para OPERACIONES_PUERTO
        $notificacionesOperaciones = $this->notificationService->getNotificationsForRole('OPERACIONES_PUERTO');

        $this->assertNotEmpty($notificacionesOperaciones);
        $this->assertTrue($notificacionesOperaciones->every(function ($notificacion) {
            return in_array('OPERACIONES_PUERTO', $notificacion['destinatarios']);
        }));
    }

    /**
     * Test: Get recent notifications
     *
     * Requirements: US-5.2 - Obtener notificaciones recientes
     * Property: Recent notifications can be retrieved
     * Validates: Requirements 5.2
     */
    public function test_get_recent_notifications(): void
    {
        // Crear alertas de prueba
        $alertas = collect([
            [
                'id' => 'TEST_ALERT_1',
                'tipo' => 'CONGESTIÓN_MUELLE',
                'nivel' => 'AMARILLO',
                'descripción' => 'Test alert',
                'acciones_recomendadas' => ['Acción 1'],
            ],
        ]);

        // Enviar notificaciones
        $this->notificationService->sendPushNotifications($alertas);

        // Obtener notificaciones recientes (últimas 24 horas)
        $notificacionesRecientes = $this->notificationService->getRecentNotifications(24);

        $this->assertNotEmpty($notificacionesRecientes);
    }

    /**
     * Test: Get notification count for role
     *
     * Requirements: US-5.2 - Contar notificaciones por rol
     * Property: Notification count can be retrieved by role
     * Validates: Requirements 5.2
     */
    public function test_get_notification_count_for_role(): void
    {
        // Crear alertas de prueba
        $alertas = collect([
            [
                'id' => 'TEST_ALERT_1',
                'tipo' => 'CONGESTIÓN_MUELLE',
                'nivel' => 'AMARILLO',
                'descripción' => 'Test alert',
                'acciones_recomendadas' => ['Acción 1'],
            ],
        ]);

        // Enviar notificaciones
        $this->notificationService->sendPushNotifications($alertas);

        // Obtener conteo de notificaciones para OPERACIONES_PUERTO
        $count = $this->notificationService->getNotificationCountForRole('OPERACIONES_PUERTO');

        $this->assertGreaterThan(0, $count);
    }

    /**
     * Test: Get alert count by type
     *
     * Requirements: US-5.2 - Contar alertas por tipo
     * Property: Alert count can be retrieved by type
     * Validates: Requirements 5.2
     */
    public function test_get_alert_count_by_type(): void
    {
        // Crear alertas de prueba
        $alertas = collect([
            [
                'id' => 'TEST_ALERT_1',
                'tipo' => 'CONGESTIÓN_MUELLE',
                'nivel' => 'AMARILLO',
                'descripción' => 'Test alert',
                'acciones_recomendadas' => ['Acción 1'],
            ],
            [
                'id' => 'TEST_ALERT_2',
                'tipo' => 'CONGESTIÓN_MUELLE',
                'nivel' => 'ROJO',
                'descripción' => 'Test alert 2',
                'acciones_recomendadas' => ['Acción 1'],
            ],
        ]);

        // Enviar notificaciones
        $this->notificationService->sendPushNotifications($alertas);

        // Obtener conteo de alertas de congestión
        $count = $this->notificationService->getAlertCountByType('CONGESTIÓN_MUELLE');

        $this->assertGreaterThanOrEqual(2, $count);
    }

    /**
     * Test: Clear all notifications
     *
     * Requirements: US-5.2 - Limpiar notificaciones (para testing)
     * Property: All notifications can be cleared
     * Validates: Requirements 5.2
     */
    public function test_clear_all_notifications(): void
    {
        // Crear alertas de prueba
        $alertas = collect([
            [
                'id' => 'TEST_ALERT_1',
                'tipo' => 'CONGESTIÓN_MUELLE',
                'nivel' => 'AMARILLO',
                'descripción' => 'Test alert',
                'acciones_recomendadas' => ['Acción 1'],
            ],
        ]);

        // Enviar notificaciones
        $this->notificationService->sendPushNotifications($alertas);

        // Verificar que hay notificaciones
        $notificacionesAntes = $this->notificationService->getAllNotifications();
        $this->assertNotEmpty($notificacionesAntes);

        // Limpiar notificaciones
        $resultado = $this->notificationService->clearAllNotifications();
        $this->assertTrue($resultado);

        // Verificar que no hay notificaciones
        $notificacionesDespues = $this->notificationService->getAllNotifications();
        $this->assertEmpty($notificacionesDespues);
    }

    /**
     * Test: Multiple notifications are accumulated
     *
     * Requirements: US-5.2 - Acumular múltiples notificaciones
     * Property: Multiple notifications are accumulated in file
     * Validates: Requirements 5.2
     */
    public function test_multiple_notifications_are_accumulated(): void
    {
        // Crear primera alerta
        $alertas1 = collect([
            [
                'id' => 'TEST_ALERT_1',
                'tipo' => 'CONGESTIÓN_MUELLE',
                'nivel' => 'AMARILLO',
                'descripción' => 'Test alert 1',
                'acciones_recomendadas' => ['Acción 1'],
            ],
        ]);

        // Enviar primera notificación
        $this->notificationService->sendPushNotifications($alertas1);

        // Crear segunda alerta
        $alertas2 = collect([
            [
                'id' => 'TEST_ALERT_2',
                'tipo' => 'ACUMULACIÓN_CAMIONES',
                'nivel' => 'ROJO',
                'descripción' => 'Test alert 2',
                'acciones_recomendadas' => ['Acción 2'],
            ],
        ]);

        // Enviar segunda notificación
        $this->notificationService->sendPushNotifications($alertas2);

        // Obtener todas las notificaciones
        $notificaciones = $this->notificationService->getAllNotifications();

        // Debe haber al menos 2 notificaciones
        $this->assertGreaterThanOrEqual(2, $notificaciones->count());
    }

    /**
     * Test: Notifications are sent with custom recipients
     *
     * Requirements: US-5.2 - Enviar notificaciones a destinatarios personalizados
     * Property: Notifications can be sent to custom recipients
     * Validates: Requirements 5.2
     */
    public function test_notifications_are_sent_with_custom_recipients(): void
    {
        // Crear alertas de prueba
        $alertas = collect([
            [
                'id' => 'TEST_ALERT_1',
                'tipo' => 'CONGESTIÓN_MUELLE',
                'nivel' => 'AMARILLO',
                'descripción' => 'Test alert',
                'acciones_recomendadas' => ['Acción 1'],
            ],
        ]);

        // Enviar notificaciones a destinatarios personalizados
        $destinatarios = ['ADMIN', 'ANALISTA'];
        $this->notificationService->sendPushNotifications($alertas, $destinatarios);

        // Obtener notificaciones
        $notificaciones = $this->notificationService->getAllNotifications();
        $ultimaNotificacion = $notificaciones->last();

        $this->assertNotNull($ultimaNotificacion);
        $this->assertEquals($destinatarios, $ultimaNotificacion['destinatarios']);
    }

    /**
     * Test: Notification file is valid JSON
     *
     * Requirements: US-5.2 - Notificaciones en formato JSON válido
     * Property: Notification file contains valid JSON
     * Validates: Requirements 5.2
     */
    public function test_notification_file_is_valid_json(): void
    {
        // Crear alertas de prueba
        $alertas = collect([
            [
                'id' => 'TEST_ALERT_1',
                'tipo' => 'CONGESTIÓN_MUELLE',
                'nivel' => 'AMARILLO',
                'descripción' => 'Test alert',
                'acciones_recomendadas' => ['Acción 1'],
            ],
        ]);

        // Enviar notificaciones
        $this->notificationService->sendPushNotifications($alertas);

        // Leer archivo
        $mockPath = storage_path('app/mocks/notifications.json');
        $contenido = file_get_contents($mockPath);

        // Intentar decodificar JSON
        $decoded = json_decode($contenido, true);

        $this->assertIsArray($decoded);
        $this->assertNotEmpty($decoded);
    }
}
