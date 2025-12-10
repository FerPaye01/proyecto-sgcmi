<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VesselCall;
use App\Models\Appointment;
use App\Models\GateEvent;
use App\Models\Tramite;

/**
 * Servicio para calcular KPIs individuales del sistema
 * Proporciona métodos para calcular métricas específicas por entidad
 */
class KpiCalculator
{
    /**
     * Calcula el turnaround de una llamada de nave
     * Turnaround = tiempo total desde arribo (ATA) hasta salida (ATD)
     *
     * @param int $vesselCallId
     * @return float|null Turnaround en horas, o null si no hay datos suficientes
     */
    public function calculateTurnaround(int $vesselCallId): ?float
    {
        $vc = VesselCall::find($vesselCallId);

        if (!$vc || !$vc->ata || !$vc->atd) {
            return null;
        }

        $hours = ($vc->atd->timestamp - $vc->ata->timestamp) / 3600;

        return round($hours, 2);
    }

    /**
     * Calcula el tiempo de espera de una cita de camión
     * Tiempo de espera = diferencia entre hora_llegada y primer evento de gate
     *
     * @param int $appointmentId
     * @return float|null Tiempo de espera en horas, o null si no hay datos suficientes
     */
    public function calculateWaitingTime(int $appointmentId): ?float
    {
        $appointment = Appointment::find($appointmentId);

        if (!$appointment || !$appointment->hora_llegada) {
            return null;
        }

        $firstEvent = GateEvent::where('cita_id', $appointmentId)
            ->where('action', 'ENTRADA')
            ->orderBy('event_ts')
            ->first();

        if (!$firstEvent) {
            return null;
        }

        $hours = ($firstEvent->event_ts->timestamp - $appointment->hora_llegada->timestamp) / 3600;

        return round(max(0, $hours), 2); // No puede ser negativo
    }

    /**
     * Calcula el cumplimiento de una cita de camión
     * Clasifica la cita como: A_TIEMPO (±15 min), TARDE (>15 min), NO_SHOW (sin llegada)
     *
     * @param int $appointmentId
     * @return array{clasificacion: string, desvio_min: float|null} Clasificación y desvío en minutos
     */
    public function calculateAppointmentCompliance(int $appointmentId): array
    {
        $appointment = Appointment::find($appointmentId);

        if (!$appointment) {
            return [
                'clasificacion' => 'UNKNOWN',
                'desvio_min' => null,
            ];
        }

        // Si es NO_SHOW o no tiene hora de llegada
        if ($appointment->estado === 'NO_SHOW' || $appointment->hora_llegada === null) {
            return [
                'clasificacion' => 'NO_SHOW',
                'desvio_min' => null,
            ];
        }

        // Calcular desvío en minutos
        $desvioSegundos = $appointment->hora_llegada->timestamp - $appointment->hora_programada->timestamp;
        $desvioMin = $desvioSegundos / 60;

        // Clasificar según desvío
        if (abs($desvioMin) <= 15) {
            $clasificacion = 'A_TIEMPO';
        } else {
            $clasificacion = 'TARDE';
        }

        return [
            'clasificacion' => $clasificacion,
            'desvio_min' => round($desvioMin, 2),
        ];
    }

    /**
     * Calcula el lead time de un trámite aduanero
     * Lead time = tiempo desde inicio hasta aprobación del trámite
     *
     * @param int $tramiteId
     * @return float|null Lead time en horas, o null si el trámite no está aprobado
     */
    public function calculateCustomsLeadTime(int $tramiteId): ?float
    {
        $tramite = Tramite::find($tramiteId);

        if (!$tramite || $tramite->estado !== 'APROBADO' || !$tramite->fecha_fin || !$tramite->fecha_inicio) {
            return null;
        }

        $hours = ($tramite->fecha_fin->timestamp - $tramite->fecha_inicio->timestamp) / 3600;

        return round(max(0, $hours), 2);
    }
}
