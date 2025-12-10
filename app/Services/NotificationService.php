<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * NotificationService
 * Handles push notifications (mock) for the system
 * Stores notifications in storage/app/mocks/notifications.json
 */
class NotificationService
{
    /**
     * Send push notifications to specified roles
     * Stores notifications in mock file for testing
     *
     * @param Collection $alertas
     * @param array<string> $destinatarios
     * @return bool
     */
    public function sendPushNotifications(Collection $alertas, array $destinatarios = ['OPERACIONES_PUERTO', 'PLANIFICADOR_PUERTO']): bool
    {
        if ($alertas->isEmpty()) {
            return false;
        }

        // Preparar notificaciones
        $notificaciones = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'destinatarios' => $destinatarios,
            'alertas' => $alertas->map(function ($alerta) {
                return [
                    'id' => $alerta['id'],
                    'tipo' => $alerta['tipo'],
                    'nivel' => $alerta['nivel'],
                    'descripción' => $alerta['descripción'],
                    'acciones_recomendadas' => $alerta['acciones_recomendadas'],
                ];
            })->toArray(),
        ];

        return $this->persistNotifications($notificaciones);
    }

    /**
     * Persist notifications to mock file
     *
     * @param array<string, mixed> $notificaciones
     * @return bool
     */
    private function persistNotifications(array $notificaciones): bool
    {
        try {
            $mockPath = storage_path('app/mocks/notifications.json');
            $directorio = dirname($mockPath);

            // Crear directorio si no existe
            if (!is_dir($directorio)) {
                mkdir($directorio, 0755, true);
            }

            // Leer notificaciones existentes
            $notificacionesExistentes = [];
            if (file_exists($mockPath)) {
                $contenido = file_get_contents($mockPath);
                $notificacionesExistentes = json_decode($contenido, true) ?? [];
            }

            // Asegurar que es un array
            if (!is_array($notificacionesExistentes)) {
                $notificacionesExistentes = [];
            }

            // Agregar nuevas notificaciones
            $notificacionesExistentes[] = $notificaciones;

            // Guardar con formato legible
            $resultado = file_put_contents(
                $mockPath,
                json_encode($notificacionesExistentes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            return $resultado !== false;
        } catch (\Exception $e) {
            \Log::error('Error persisting notifications: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all notifications from mock file
     *
     * @return Collection
     */
    public function getAllNotifications(): Collection
    {
        try {
            $mockPath = storage_path('app/mocks/notifications.json');

            if (!file_exists($mockPath)) {
                return collect();
            }

            $contenido = file_get_contents($mockPath);
            $notificaciones = json_decode($contenido, true) ?? [];

            return collect($notificaciones);
        } catch (\Exception $e) {
            \Log::error('Error reading notifications: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get notifications for a specific role
     *
     * @param string $role
     * @return Collection
     */
    public function getNotificationsForRole(string $role): Collection
    {
        return $this->getAllNotifications()->filter(function ($notificacion) use ($role) {
            return in_array($role, $notificacion['destinatarios'] ?? []);
        });
    }

    /**
     * Get recent notifications (last N hours)
     *
     * @param int $hours
     * @return Collection
     */
    public function getRecentNotifications(int $hours = 24): Collection
    {
        $cutoffTime = now()->subHours($hours);

        return $this->getAllNotifications()->filter(function ($notificacion) use ($cutoffTime) {
            try {
                $timestamp = \Carbon\Carbon::parse($notificacion['timestamp']);
                return $timestamp->greaterThanOrEqualTo($cutoffTime);
            } catch (\Exception $e) {
                return false;
            }
        });
    }

    /**
     * Clear all notifications (for testing)
     *
     * @return bool
     */
    public function clearAllNotifications(): bool
    {
        try {
            $mockPath = storage_path('app/mocks/notifications.json');

            if (file_exists($mockPath)) {
                return file_put_contents($mockPath, json_encode([], JSON_PRETTY_PRINT)) !== false;
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Error clearing notifications: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notification count for a specific role
     *
     * @param string $role
     * @return int
     */
    public function getNotificationCountForRole(string $role): int
    {
        return $this->getNotificationsForRole($role)->count();
    }

    /**
     * Get alert count by type
     *
     * @param string $tipo
     * @return int
     */
    public function getAlertCountByType(string $tipo): int
    {
        $count = 0;

        foreach ($this->getAllNotifications() as $notificacion) {
            foreach ($notificacion['alertas'] ?? [] as $alerta) {
                if ($alerta['tipo'] === $tipo) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
