<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Alert;
use App\Models\VesselCall;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }
    /**
     * Genera el reporte R1: Programación vs Ejecución
     * Compara tiempos programados (ETA/ETB) con tiempos reales (ATA/ATB/ATD)
     *
     * @param array<string, mixed> $filters
     * @return array{data: Collection, kpis: array<string, float>}
     */
    public function generateR1(array $filters): array
    {
        $query = VesselCall::with(['vessel', 'berth'])
            ->whereNotNull('ata');

        // Aplicar filtros
        if (isset($filters['fecha_desde'])) {
            $query->where('ata', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta'])) {
            $query->where('ata', '<=', $filters['fecha_hasta']);
        }

        if (isset($filters['berth_id'])) {
            $query->where('berth_id', $filters['berth_id']);
        }

        if (isset($filters['vessel_id'])) {
            $query->where('vessel_id', $filters['vessel_id']);
        }

        $data = $query->get();

        $kpis = $this->calculateR1Kpis($data);

        return [
            'data' => $data,
            'kpis' => $kpis,
        ];
    }

    /**
     * Calcula los KPIs del reporte R1
     *
     * @param Collection $data
     * @return array<string, float>
     */
    private function calculateR1Kpis(Collection $data): array
    {
        $total = $data->count();

        if ($total === 0) {
            return [
                'puntualidad_arribo' => 0.0,
                'demora_eta_ata_min' => 0.0,
                'demora_etb_atb_min' => 0.0,
                'cumplimiento_ventana' => 0.0,
            ];
        }

        // Calcular puntualidad de arribo (±1 hora)
        $puntual = $data->filter(function ($vc) {
            if ($vc->eta === null || $vc->ata === null) {
                return false;
            }
            $diffSeconds = abs($vc->ata->timestamp - $vc->eta->timestamp);
            return $diffSeconds <= 3600; // ±1 hora
        })->count();

        // Calcular demoras ETA-ATA en minutos
        $demorasEta = $data->map(function ($vc) {
            if ($vc->eta === null || $vc->ata === null) {
                return null;
            }
            return ($vc->ata->timestamp - $vc->eta->timestamp) / 60;
        })->filter(fn($value) => $value !== null);

        // Calcular demoras ETB-ATB en minutos
        $demorasEtb = $data->filter(fn($vc) => $vc->etb !== null && $vc->atb !== null)
            ->map(function ($vc) {
                return ($vc->atb->timestamp - $vc->etb->timestamp) / 60;
            });

        return [
            'puntualidad_arribo' => round(($puntual / $total) * 100, 2),
            'demora_eta_ata_min' => round($demorasEta->avg() ?? 0.0, 2),
            'demora_etb_atb_min' => round($demorasEtb->avg() ?? 0.0, 2),
            'cumplimiento_ventana' => round(($puntual / $total) * 100, 2),
        ];
    }

    /**
     * Genera el reporte R3: Utilización de Muelles
     * Calcula la utilización horaria de cada muelle basándose en ATB-ATD
     *
     * @param array<string, mixed> $filters
     * @return array{data: Collection, kpis: array<string, mixed>, utilizacion_por_franja: array<string, array<string, float>>}
     */
    public function generateR3(array $filters): array
    {
        $query = VesselCall::with(['vessel', 'berth'])
            ->whereNotNull('atb')
            ->whereNotNull('atd');

        // Aplicar filtros
        if (isset($filters['fecha_desde'])) {
            $query->where('atb', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta'])) {
            $query->where('atd', '<=', $filters['fecha_hasta']);
        }

        if (isset($filters['berth_id'])) {
            $query->where('berth_id', $filters['berth_id']);
        }

        $data = $query->orderBy('atb')->get();

        // Configuración de franjas horarias (por defecto 1 hora)
        $franjaHoras = $filters['franja_horas'] ?? 1;

        $utilizacionPorFranja = $this->calculateUtilizacionPorFranja($data, $franjaHoras, $filters);
        $kpis = $this->calculateR3Kpis($data, $utilizacionPorFranja, $filters);

        return [
            'data' => $data,
            'kpis' => $kpis,
            'utilizacion_por_franja' => $utilizacionPorFranja,
        ];
    }

    /**
     * Calcula la utilización por franja horaria para cada muelle
     *
     * @param Collection $data
     * @param int $franjaHoras
     * @param array<string, mixed> $filters
     * @return array<string, array<string, float>>
     */
    private function calculateUtilizacionPorFranja(Collection $data, int $franjaHoras, array $filters): array
    {
        if ($data->isEmpty()) {
            return [];
        }

        // Determinar rango de fechas
        $fechaDesde = isset($filters['fecha_desde']) 
            ? new \DateTime($filters['fecha_desde']) 
            : $data->min('atb');
        $fechaHasta = isset($filters['fecha_hasta']) 
            ? new \DateTime($filters['fecha_hasta']) 
            : $data->max('atd');

        // Agrupar por muelle
        $porMuelle = $data->groupBy('berth_id');
        $resultado = [];

        foreach ($porMuelle as $berthId => $llamadas) {
            $berthName = $llamadas->first()->berth->name ?? "Muelle {$berthId}";
            $resultado[$berthName] = [];

            // Generar franjas horarias
            $franjas = $this->generarFranjas($fechaDesde, $fechaHasta, $franjaHoras);

            foreach ($franjas as $franja) {
                $horasOcupadas = 0;

                foreach ($llamadas as $llamada) {
                    $atb = $llamada->atb;
                    $atd = $llamada->atd;

                    // Calcular solapamiento con la franja
                    $inicioFranja = $franja['inicio'];
                    $finFranja = $franja['fin'];

                    $inicioSolapamiento = max($atb->getTimestamp(), $inicioFranja->getTimestamp());
                    $finSolapamiento = min($atd->getTimestamp(), $finFranja->getTimestamp());

                    if ($inicioSolapamiento < $finSolapamiento) {
                        $horasOcupadas += ($finSolapamiento - $inicioSolapamiento) / 3600;
                    }
                }

                $horasTotalesFranja = $franjaHoras;
                $utilizacion = min(100, ($horasOcupadas / $horasTotalesFranja) * 100);

                $resultado[$berthName][$franja['label']] = round($utilizacion, 2);
            }
        }

        return $resultado;
    }

    /**
     * Genera franjas horarias entre dos fechas
     *
     * @param \DateTime $inicio
     * @param \DateTime $fin
     * @param int $franjaHoras
     * @return array<int, array{inicio: \DateTime, fin: \DateTime, label: string}>
     */
    private function generarFranjas(\DateTime $inicio, \DateTime $fin, int $franjaHoras): array
    {
        $franjas = [];
        $actual = clone $inicio;
        $actual->setTime((int)$actual->format('H'), 0, 0);

        while ($actual < $fin) {
            $siguienteFranja = clone $actual;
            $siguienteFranja->modify("+{$franjaHoras} hours");

            $franjas[] = [
                'inicio' => clone $actual,
                'fin' => clone $siguienteFranja,
                'label' => $actual->format('Y-m-d H:i'),
            ];

            $actual = $siguienteFranja;
        }

        return $franjas;
    }

    /**
     * Calcula los KPIs del reporte R3
     *
     * @param Collection $data
     * @param array<string, array<string, float>> $utilizacionPorFranja
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function calculateR3Kpis(Collection $data, array $utilizacionPorFranja, array $filters): array
    {
        if ($data->isEmpty()) {
            return [
                'utilizacion_promedio' => 0.0,
                'conflictos_ventana' => 0,
                'horas_ociosas' => 0.0,
                'utilizacion_maxima' => 0.0,
            ];
        }

        // Calcular utilización promedio
        $todasUtilizaciones = [];
        foreach ($utilizacionPorFranja as $muelle => $franjas) {
            $todasUtilizaciones = array_merge($todasUtilizaciones, array_values($franjas));
        }
        $utilizacionPromedio = count($todasUtilizaciones) > 0 
            ? array_sum($todasUtilizaciones) / count($todasUtilizaciones) 
            : 0.0;

        // Detectar conflictos de ventana (solapamientos)
        $conflictos = $this->detectarConflictos($data);

        // Calcular horas ociosas (franjas con utilización < 10%)
        $horasOciosas = 0;
        $franjaHoras = $filters['franja_horas'] ?? 1;
        foreach ($todasUtilizaciones as $utilizacion) {
            if ($utilizacion < 10) {
                $horasOciosas += $franjaHoras;
            }
        }

        // Utilización máxima
        $utilizacionMaxima = count($todasUtilizaciones) > 0 ? max($todasUtilizaciones) : 0.0;

        return [
            'utilizacion_promedio' => round($utilizacionPromedio, 2),
            'conflictos_ventana' => $conflictos,
            'horas_ociosas' => round($horasOciosas, 2),
            'utilizacion_maxima' => round($utilizacionMaxima, 2),
        ];
    }

    /**
     * Detecta conflictos de ventana (solapamientos de naves en el mismo muelle)
     *
     * @param Collection $data
     * @return int
     */
    private function detectarConflictos(Collection $data): int
    {
        $conflictos = 0;
        $porMuelle = $data->groupBy('berth_id');

        foreach ($porMuelle as $llamadas) {
            $llamadasArray = $llamadas->sortBy('atb')->values();

            for ($i = 0; $i < $llamadasArray->count() - 1; $i++) {
                $actual = $llamadasArray[$i];
                $siguiente = $llamadasArray[$i + 1];

                // Verificar solapamiento: ATD de actual > ATB de siguiente
                if ($actual->atd && $siguiente->atb && $actual->atd > $siguiente->atb) {
                    $conflictos++;
                }
            }
        }

        return $conflictos;
    }

    /**
     * Genera el reporte R6: Productividad de Gates
     * Calcula la productividad de cada gate basándose en eventos de entrada/salida
     *
     * @param array<string, mixed> $filters
     * @return array{data: Collection, kpis: array<string, mixed>, productividad_por_hora: array<string, array<string, mixed>>}
     */
    public function generateR6(array $filters): array
    {
        $query = \App\Models\GateEvent::with(['gate', 'truck'])
            ->orderBy('event_ts');

        // Aplicar filtros
        if (isset($filters['fecha_desde'])) {
            $query->where('event_ts', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta'])) {
            $query->where('event_ts', '<=', $filters['fecha_hasta']);
        }

        if (isset($filters['gate_id'])) {
            $query->where('gate_id', $filters['gate_id']);
        }

        $data = $query->get();

        $productividadPorHora = $this->calculateProductividadPorHora($data);
        $kpis = $this->calculateR6Kpis($data, $productividadPorHora, $filters);

        return [
            'data' => $data,
            'kpis' => $kpis,
            'productividad_por_hora' => $productividadPorHora,
        ];
    }

    /**
     * Calcula la productividad por hora del día para cada gate
     *
     * @param Collection $data
     * @return array<string, array<string, mixed>>
     */
    private function calculateProductividadPorHora(Collection $data): array
    {
        if ($data->isEmpty()) {
            return [];
        }

        // Agrupar por gate
        $porGate = $data->groupBy('gate_id');
        $resultado = [];

        foreach ($porGate as $gateId => $eventos) {
            $gateName = $eventos->first()->gate->name ?? "Gate {$gateId}";
            $resultado[$gateName] = [];

            // Agrupar eventos por hora del día (0-23)
            for ($hora = 0; $hora < 24; $hora++) {
                $eventosHora = $eventos->filter(function ($evento) use ($hora) {
                    return (int)$evento->event_ts->format('H') === $hora;
                });

                $entradas = $eventosHora->where('action', 'ENTRADA')->count();
                $salidas = $eventosHora->where('action', 'SALIDA')->count();
                $totalVehiculos = $entradas; // Contamos por entradas

                $resultado[$gateName][sprintf('%02d:00', $hora)] = [
                    'veh_x_hora' => $totalVehiculos,
                    'entradas' => $entradas,
                    'salidas' => $salidas,
                ];
            }
        }

        return $resultado;
    }

    /**
     * Calcula los KPIs del reporte R6
     *
     * @param Collection $data
     * @param array<string, array<string, mixed>> $productividadPorHora
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function calculateR6Kpis(Collection $data, array $productividadPorHora, array $filters): array
    {
        if ($data->isEmpty()) {
            return [
                'veh_x_hora' => 0.0,
                'tiempo_ciclo_min' => 0.0,
                'picos_vs_capacidad' => 0.0,
                'horas_pico' => [],
            ];
        }

        // Calcular veh_x_hora promedio
        $totalVehiculos = 0;
        $totalHoras = 0;
        foreach ($productividadPorHora as $gate => $horas) {
            foreach ($horas as $hora => $datos) {
                $totalVehiculos += $datos['veh_x_hora'];
                if ($datos['veh_x_hora'] > 0) {
                    $totalHoras++;
                }
            }
        }
        $vehXHoraPromedio = $totalHoras > 0 ? $totalVehiculos / $totalHoras : 0.0;

        // Calcular tiempo_ciclo_min (entrada → salida)
        $tiemposCiclo = $this->calculateTiemposCiclo($data);
        $tiempoCicloPromedio = $tiemposCiclo->avg() ?? 0.0;

        // Identificar horas pico (> 80% capacidad teórica)
        // Capacidad teórica: asumimos 10 vehículos por hora como máximo
        $capacidadTeorica = $filters['capacidad_teorica'] ?? 10;
        $umbralPico = $capacidadTeorica * 0.8;
        $horasPico = [];

        foreach ($productividadPorHora as $gate => $horas) {
            foreach ($horas as $hora => $datos) {
                if ($datos['veh_x_hora'] > $umbralPico) {
                    $horasPico[] = [
                        'gate' => $gate,
                        'hora' => $hora,
                        'vehiculos' => $datos['veh_x_hora'],
                        'porcentaje' => round(($datos['veh_x_hora'] / $capacidadTeorica) * 100, 2),
                    ];
                }
            }
        }

        // Calcular picos_vs_capacidad (porcentaje de horas que son pico)
        $totalHorasConActividad = 0;
        foreach ($productividadPorHora as $gate => $horas) {
            foreach ($horas as $hora => $datos) {
                if ($datos['veh_x_hora'] > 0) {
                    $totalHorasConActividad++;
                }
            }
        }
        $picosVsCapacidad = $totalHorasConActividad > 0 
            ? (count($horasPico) / $totalHorasConActividad) * 100 
            : 0.0;

        return [
            'veh_x_hora' => round($vehXHoraPromedio, 2),
            'tiempo_ciclo_min' => round($tiempoCicloPromedio, 2),
            'picos_vs_capacidad' => round($picosVsCapacidad, 2),
            'horas_pico' => $horasPico,
        ];
    }

    /**
     * Calcula los tiempos de ciclo (entrada → salida) para cada camión
     *
     * @param Collection $data
     * @return Collection
     */
    private function calculateTiemposCiclo(Collection $data): Collection
    {
        $tiemposCiclo = collect();

        // Agrupar eventos por camión
        $porCamion = $data->groupBy('truck_id');

        foreach ($porCamion as $truckId => $eventos) {
            // Ordenar por timestamp
            $eventosOrdenados = $eventos->sortBy('event_ts')->values();

            // Buscar pares entrada-salida consecutivos
            $i = 0;
            while ($i < $eventosOrdenados->count() - 1) {
                $actual = $eventosOrdenados[$i];
                
                if ($actual->action === 'ENTRADA') {
                    // Buscar la siguiente salida para este camión
                    for ($j = $i + 1; $j < $eventosOrdenados->count(); $j++) {
                        $siguiente = $eventosOrdenados[$j];
                        
                        if ($siguiente->action === 'SALIDA') {
                            // Calcular tiempo de ciclo en minutos
                            $tiempoCiclo = ($siguiente->event_ts->timestamp - $actual->event_ts->timestamp) / 60;
                            $tiemposCiclo->push($tiempoCiclo);
                            $i = $j; // Saltar al evento de salida para continuar desde ahí
                            break;
                        } elseif ($siguiente->action === 'ENTRADA') {
                            // Si encontramos otra entrada antes de una salida, esta entrada no tiene salida
                            break;
                        }
                    }
                }
                $i++;
            }
        }

        return $tiemposCiclo;
    }

    /**
     * Genera el reporte R4: Tiempo de Espera de Camiones
     * Calcula el tiempo de espera desde hora_llegada hasta el primer evento de gate
     * Aplica scoping por empresa para TRANSPORTISTA
     *
     * @param array<string, mixed> $filters
     * @param \App\Models\User|null $user
     * @return array{data: Collection, kpis: array<string, mixed>}
     */
    public function generateR4(array $filters, ?\App\Models\User $user = null): array
    {
        $query = \App\Models\Appointment::with(['truck', 'company', 'vesselCall', 'gateEvents'])
            ->whereNotNull('hora_llegada')
            ->whereIn('estado', ['ATENDIDA', 'CONFIRMADA']);

        // Aplicar scoping por empresa si el usuario es TRANSPORTISTA
        if ($user !== null) {
            $query = ScopingService::applyCompanyScope($query, $user);
        }

        // Aplicar filtros
        if (isset($filters['fecha_desde'])) {
            $query->where('hora_llegada', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta'])) {
            $query->where('hora_llegada', '<=', $filters['fecha_hasta']);
        }

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        $data = $query->orderBy('hora_llegada')->get();

        // Calcular tiempo de espera para cada cita
        $dataConEspera = $data->map(function ($appointment) {
            $primerEvento = $appointment->gateEvents()
                ->orderBy('event_ts')
                ->first();

            if ($primerEvento && $appointment->hora_llegada) {
                $esperaHoras = ($primerEvento->event_ts->timestamp - $appointment->hora_llegada->timestamp) / 3600;
                $appointment->espera_horas = max(0, $esperaHoras); // No puede ser negativo
            } else {
                $appointment->espera_horas = null;
            }

            return $appointment;
        });

        $kpis = $this->calculateR4Kpis($dataConEspera);

        return [
            'data' => $dataConEspera,
            'kpis' => $kpis,
        ];
    }

    /**
     * Calcula los KPIs del reporte R4
     *
     * @param Collection $data
     * @return array<string, mixed>
     */
    private function calculateR4Kpis(Collection $data): array
    {
        $citasConEspera = $data->filter(fn($cita) => $cita->espera_horas !== null);
        $total = $citasConEspera->count();

        if ($total === 0) {
            return [
                'espera_promedio_h' => 0.0,
                'pct_gt_6h' => 0.0,
                'citas_atendidas' => 0,
            ];
        }

        $esperaPromedio = $citasConEspera->avg('espera_horas');
        $citasGt6h = $citasConEspera->filter(fn($cita) => $cita->espera_horas > 6)->count();
        $pctGt6h = ($citasGt6h / $total) * 100;

        return [
            'espera_promedio_h' => round($esperaPromedio, 2),
            'pct_gt_6h' => round($pctGt6h, 2),
            'citas_atendidas' => $total,
        ];
    }

    /**
     * Genera el reporte R5: Cumplimiento de Citas
     * Clasifica citas como: A tiempo (±15 min), Tarde (>15 min), No Show (sin llegada)
     * Aplica scoping por empresa para TRANSPORTISTA
     *
     * @param array<string, mixed> $filters
     * @param \App\Models\User|null $user
     * @return array{data: Collection, kpis: array<string, mixed>, ranking: Collection|null}
     */
    public function generateR5(array $filters, ?\App\Models\User $user = null): array
    {
        $query = \App\Models\Appointment::with(['truck', 'company', 'vesselCall'])
            ->whereIn('estado', ['ATENDIDA', 'CONFIRMADA', 'NO_SHOW']);

        // Aplicar scoping por empresa si el usuario es TRANSPORTISTA
        if ($user !== null) {
            $query = ScopingService::applyCompanyScope($query, $user);
        }

        // Aplicar filtros
        if (isset($filters['fecha_desde'])) {
            $query->where('hora_programada', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta'])) {
            $query->where('hora_programada', '<=', $filters['fecha_hasta']);
        }

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        $data = $query->orderBy('hora_programada')->get();

        // Clasificar cada cita
        $dataConClasificacion = $data->map(function ($appointment) {
            if ($appointment->estado === 'NO_SHOW' || $appointment->hora_llegada === null) {
                $appointment->clasificacion = 'NO_SHOW';
                $appointment->desvio_min = null;
            } else {
                $desvioSegundos = $appointment->hora_llegada->timestamp - $appointment->hora_programada->timestamp;
                $desvioMin = $desvioSegundos / 60;
                $appointment->desvio_min = $desvioMin;

                if (abs($desvioMin) <= 15) {
                    $appointment->clasificacion = 'A_TIEMPO';
                } else {
                    $appointment->clasificacion = 'TARDE';
                }
            }

            return $appointment;
        });

        $kpis = $this->calculateR5Kpis($dataConClasificacion);

        // Calcular ranking de empresas (solo si el usuario NO es TRANSPORTISTA)
        $ranking = null;
        if ($user === null || !$user->hasRole('TRANSPORTISTA')) {
            $ranking = $this->calculateRankingEmpresas($filters);
        }

        return [
            'data' => $dataConClasificacion,
            'kpis' => $kpis,
            'ranking' => $ranking,
        ];
    }

    /**
     * Calcula los KPIs del reporte R5
     *
     * @param Collection $data
     * @return array<string, mixed>
     */
    private function calculateR5Kpis(Collection $data): array
    {
        $total = $data->count();

        if ($total === 0) {
            return [
                'pct_no_show' => 0.0,
                'pct_tarde' => 0.0,
                'desvio_medio_min' => 0.0,
                'total_citas' => 0,
            ];
        }

        $noShow = $data->where('clasificacion', 'NO_SHOW')->count();
        $tarde = $data->where('clasificacion', 'TARDE')->count();
        $desvios = $data->filter(fn($cita) => $cita->desvio_min !== null)->pluck('desvio_min');

        return [
            'pct_no_show' => round(($noShow / $total) * 100, 2),
            'pct_tarde' => round(($tarde / $total) * 100, 2),
            'desvio_medio_min' => round($desvios->avg() ?? 0.0, 2),
            'total_citas' => $total,
        ];
    }

    /**
     * Calcula el ranking de empresas por cumplimiento de citas
     *
     * @param array<string, mixed> $filters
     * @return Collection
     */
    private function calculateRankingEmpresas(array $filters): Collection
    {
        $query = \App\Models\Appointment::with('company')
            ->whereIn('estado', ['ATENDIDA', 'CONFIRMADA', 'NO_SHOW']);

        // Aplicar filtros de fecha
        if (isset($filters['fecha_desde'])) {
            $query->where('hora_programada', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta'])) {
            $query->where('hora_programada', '<=', $filters['fecha_hasta']);
        }

        $citas = $query->get();

        // Agrupar por empresa
        $porEmpresa = $citas->groupBy('company_id');

        $ranking = $porEmpresa->map(function ($citasEmpresa, $companyId) {
            $total = $citasEmpresa->count();
            $noShow = $citasEmpresa->filter(fn($c) => $c->estado === 'NO_SHOW' || $c->hora_llegada === null)->count();
            
            $aTiempo = $citasEmpresa->filter(function ($c) {
                if ($c->hora_llegada === null) {
                    return false;
                }
                $desvioMin = ($c->hora_llegada->timestamp - $c->hora_programada->timestamp) / 60;
                return abs($desvioMin) <= 15;
            })->count();

            $pctCumplimiento = $total > 0 ? (($aTiempo / $total) * 100) : 0;
            $pctNoShow = $total > 0 ? (($noShow / $total) * 100) : 0;

            return [
                'company_id' => $companyId,
                'company_name' => $citasEmpresa->first()->company->name ?? "Empresa {$companyId}",
                'total_citas' => $total,
                'a_tiempo' => $aTiempo,
                'no_show' => $noShow,
                'pct_cumplimiento' => round($pctCumplimiento, 2),
                'pct_no_show' => round($pctNoShow, 2),
            ];
        })->sortByDesc('pct_cumplimiento')->values();

        return $ranking;
    }

    /**
     * Genera el reporte R7: Estado de Trámites por Nave
     * Muestra el estado de trámites agrupados por llamada de nave
     * Calcula KPIs: pct_completos_pre_arribo, lead_time_h
     *
     * @param array<string, mixed> $filters
     * @return array{data: Collection, kpis: array<string, mixed>, por_nave: Collection}
     */
    public function generateR7(array $filters): array
    {
        $query = \App\Models\Tramite::with(['vesselCall.vessel', 'entidad', 'events'])
            ->orderBy('fecha_inicio');

        // Aplicar filtros
        if (isset($filters['fecha_desde'])) {
            $query->where('fecha_inicio', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta'])) {
            $query->where('fecha_inicio', '<=', $filters['fecha_hasta']);
        }

        if (isset($filters['vessel_id'])) {
            $query->whereHas('vesselCall', function ($q) use ($filters) {
                $q->where('vessel_id', $filters['vessel_id']);
            });
        }

        if (isset($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (isset($filters['entidad_id'])) {
            $query->where('entidad_id', $filters['entidad_id']);
        }

        $data = $query->get();

        // Calcular lead time para cada trámite
        $dataConLeadTime = $data->map(function ($tramite) {
            if ($tramite->estado === 'APROBADO' && $tramite->fecha_fin && $tramite->fecha_inicio) {
                $leadTimeHoras = ($tramite->fecha_fin->timestamp - $tramite->fecha_inicio->timestamp) / 3600;
                $tramite->lead_time_h = max(0, $leadTimeHoras);
            } else {
                $tramite->lead_time_h = null;
            }

            // Determinar si el trámite está bloqueando operación
            $tramite->bloquea_operacion = in_array($tramite->estado, ['INICIADO', 'EN_REVISION', 'OBSERVADO']);

            return $tramite;
        });

        // Agrupar por nave
        $porNave = $this->agruparTramitesPorNave($dataConLeadTime);

        $kpis = $this->calculateR7Kpis($dataConLeadTime);

        return [
            'data' => $dataConLeadTime,
            'kpis' => $kpis,
            'por_nave' => $porNave,
        ];
    }

    /**
     * Agrupa trámites por llamada de nave
     *
     * @param Collection $tramites
     * @return Collection
     */
    private function agruparTramitesPorNave(Collection $tramites): Collection
    {
        $porVesselCall = $tramites->groupBy('vessel_call_id');

        return $porVesselCall->map(function ($tramitesNave, $vesselCallId) {
            $vesselCall = $tramitesNave->first()->vesselCall;
            $total = $tramitesNave->count();
            $aprobados = $tramitesNave->where('estado', 'APROBADO')->count();
            $pendientes = $tramitesNave->whereIn('estado', ['INICIADO', 'EN_REVISION', 'OBSERVADO'])->count();
            $rechazados = $tramitesNave->where('estado', 'RECHAZADO')->count();

            // Verificar si hay trámites completados antes del arribo
            $completosPreArribo = 0;
            if ($vesselCall && $vesselCall->ata) {
                $completosPreArribo = $tramitesNave->filter(function ($t) use ($vesselCall) {
                    return $t->estado === 'APROBADO' 
                        && $t->fecha_fin 
                        && $t->fecha_fin < $vesselCall->ata;
                })->count();
            }

            return [
                'vessel_call_id' => $vesselCallId,
                'vessel_name' => $vesselCall->vessel->name ?? 'N/A',
                'viaje_id' => $vesselCall->viaje_id ?? 'N/A',
                'eta' => $vesselCall->eta ?? null,
                'ata' => $vesselCall->ata ?? null,
                'total_tramites' => $total,
                'aprobados' => $aprobados,
                'pendientes' => $pendientes,
                'rechazados' => $rechazados,
                'completos_pre_arribo' => $completosPreArribo,
                'pct_completos' => $total > 0 ? round(($aprobados / $total) * 100, 2) : 0,
                'bloquea_operacion' => $pendientes > 0,
            ];
        })->values();
    }

    /**
     * Calcula los KPIs del reporte R7
     *
     * @param Collection $data
     * @return array<string, mixed>
     */
    private function calculateR7Kpis(Collection $data): array
    {
        $total = $data->count();

        if ($total === 0) {
            return [
                'pct_completos_pre_arribo' => 0.0,
                'lead_time_h' => 0.0,
                'total_tramites' => 0,
                'aprobados' => 0,
                'pendientes' => 0,
                'rechazados' => 0,
            ];
        }

        // Calcular trámites completados antes del arribo
        $completosPreArribo = $data->filter(function ($tramite) {
            if ($tramite->estado !== 'APROBADO' || !$tramite->fecha_fin) {
                return false;
            }

            $vesselCall = $tramite->vesselCall;
            if (!$vesselCall || !$vesselCall->ata) {
                return false;
            }

            return $tramite->fecha_fin < $vesselCall->ata;
        })->count();

        $pctCompletosPreArribo = $total > 0 ? ($completosPreArribo / $total) * 100 : 0;

        // Calcular lead time promedio (solo para trámites aprobados)
        $tramitesAprobados = $data->filter(fn($t) => $t->lead_time_h !== null);
        $leadTimePromedio = $tramitesAprobados->avg('lead_time_h') ?? 0.0;

        // Contadores por estado
        $aprobados = $data->where('estado', 'APROBADO')->count();
        $pendientes = $data->whereIn('estado', ['INICIADO', 'EN_REVISION', 'OBSERVADO'])->count();
        $rechazados = $data->where('estado', 'RECHAZADO')->count();

        return [
            'pct_completos_pre_arribo' => round($pctCompletosPreArribo, 2),
            'lead_time_h' => round($leadTimePromedio, 2),
            'total_tramites' => $total,
            'aprobados' => $aprobados,
            'pendientes' => $pendientes,
            'rechazados' => $rechazados,
        ];
    }

    /**
     * Genera el reporte R8: Tiempo de Despacho por Régimen
     * Calcula percentiles de tiempo de despacho (p50, p90) y porcentaje fuera de umbral
     * Agrupa por régimen aduanero (IMPORTACION, EXPORTACION, TRANSITO)
     *
     * @param array<string, mixed> $filters
     * @return array{data: Collection, kpis: array<string, mixed>, por_regimen: Collection}
     */
    public function generateR8(array $filters): array
    {
        $query = \App\Models\Tramite::with(['vesselCall.vessel', 'entidad'])
            ->where('estado', 'APROBADO')
            ->whereNotNull('fecha_fin')
            ->orderBy('fecha_inicio');

        // Aplicar filtros
        if (isset($filters['fecha_desde'])) {
            $query->where('fecha_inicio', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta'])) {
            $query->where('fecha_inicio', '<=', $filters['fecha_hasta']);
        }

        if (isset($filters['regimen'])) {
            $query->where('regimen', $filters['regimen']);
        }

        if (isset($filters['entidad_id'])) {
            $query->where('entidad_id', $filters['entidad_id']);
        }

        $data = $query->get();

        // Calcular tiempo de despacho para cada trámite
        $dataConTiempoDespacho = $data->map(function ($tramite) {
            $tiempoHoras = ($tramite->fecha_fin->timestamp - $tramite->fecha_inicio->timestamp) / 3600;
            $tramite->tiempo_despacho_h = max(0, $tiempoHoras);
            return $tramite;
        });

        // Agrupar por régimen
        $porRegimen = $this->agruparTramitesPorRegimen($dataConTiempoDespacho, $filters);

        $kpis = $this->calculateR8Kpis($dataConTiempoDespacho, $filters);

        return [
            'data' => $dataConTiempoDespacho,
            'kpis' => $kpis,
            'por_regimen' => $porRegimen,
        ];
    }

    /**
     * Agrupa trámites por régimen aduanero y calcula estadísticas
     *
     * @param Collection $tramites
     * @param array<string, mixed> $filters
     * @return Collection
     */
    private function agruparTramitesPorRegimen(Collection $tramites, array $filters): Collection
    {
        $porRegimen = $tramites->groupBy('regimen');
        $umbralHoras = $filters['umbral_horas'] ?? 24; // Umbral por defecto: 24 horas

        return $porRegimen->map(function ($tramitesRegimen, $regimen) use ($umbralHoras) {
            $tiempos = $tramitesRegimen->pluck('tiempo_despacho_h')->sort()->values();
            $total = $tiempos->count();

            if ($total === 0) {
                return [
                    'regimen' => $regimen,
                    'total' => 0,
                    'p50_horas' => 0.0,
                    'p90_horas' => 0.0,
                    'promedio_horas' => 0.0,
                    'fuera_umbral' => 0,
                    'fuera_umbral_pct' => 0.0,
                ];
            }

            // Calcular percentiles
            $p50 = $this->calculatePercentile($tiempos, 50);
            $p90 = $this->calculatePercentile($tiempos, 90);
            $promedio = $tiempos->avg();

            // Calcular trámites fuera de umbral
            $fueraUmbral = $tramitesRegimen->filter(fn($t) => $t->tiempo_despacho_h > $umbralHoras)->count();
            $fueraUmbralPct = ($fueraUmbral / $total) * 100;

            return [
                'regimen' => $regimen,
                'total' => $total,
                'p50_horas' => round($p50, 2),
                'p90_horas' => round($p90, 2),
                'promedio_horas' => round($promedio, 2),
                'fuera_umbral' => $fueraUmbral,
                'fuera_umbral_pct' => round($fueraUmbralPct, 2),
            ];
        })->values();
    }

    /**
     * Calcula los KPIs del reporte R8
     *
     * @param Collection $data
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function calculateR8Kpis(Collection $data, array $filters): array
    {
        $total = $data->count();

        if ($total === 0) {
            return [
                'p50_horas' => 0.0,
                'p90_horas' => 0.0,
                'promedio_horas' => 0.0,
                'fuera_umbral_pct' => 0.0,
                'total_tramites' => 0,
            ];
        }

        // Obtener todos los tiempos de despacho
        $tiempos = $data->pluck('tiempo_despacho_h')->sort()->values();

        // Calcular percentiles
        $p50 = $this->calculatePercentile($tiempos, 50);
        $p90 = $this->calculatePercentile($tiempos, 90);
        $promedio = $tiempos->avg();

        // Calcular trámites fuera de umbral
        $umbralHoras = $filters['umbral_horas'] ?? 24; // Umbral por defecto: 24 horas
        $fueraUmbral = $data->filter(fn($t) => $t->tiempo_despacho_h > $umbralHoras)->count();
        $fueraUmbralPct = ($fueraUmbral / $total) * 100;

        return [
            'p50_horas' => round($p50, 2),
            'p90_horas' => round($p90, 2),
            'promedio_horas' => round($promedio, 2),
            'fuera_umbral_pct' => round($fueraUmbralPct, 2),
            'total_tramites' => $total,
            'fuera_umbral' => $fueraUmbral,
            'umbral_horas' => $umbralHoras,
        ];
    }

    /**
     * Calcula un percentil de una colección de valores ordenados
     *
     * @param Collection $values Colección de valores ordenados
     * @param int $percentile Percentil a calcular (0-100)
     * @return float
     */
    private function calculatePercentile(Collection $values, int $percentile): float
    {
        $count = $values->count();

        if ($count === 0) {
            return 0.0;
        }

        if ($count === 1) {
            return $values->first();
        }

        // Calcular índice del percentil
        $index = ($percentile / 100) * ($count - 1);
        $lowerIndex = (int)floor($index);
        $upperIndex = (int)ceil($index);

        // Si el índice es exacto, retornar ese valor
        if ($lowerIndex === $upperIndex) {
            return $values->get($lowerIndex);
        }

        // Interpolar entre los dos valores
        $lowerValue = $values->get($lowerIndex);
        $upperValue = $values->get($upperIndex);
        $fraction = $index - $lowerIndex;

        return $lowerValue + ($fraction * ($upperValue - $lowerValue));
    }

    /**
     * Genera el reporte R9: Incidencias de Documentación
     * Analiza rechazos, reprocesamientos y tiempos de subsanación de trámites
     * Identifica problemas documentales y tiempos de corrección
     *
     * @param array<string, mixed> $filters
     * @return array{data: Collection, kpis: array<string, mixed>, por_entidad: Collection}
     */
    public function generateR9(array $filters): array
    {
        $query = \App\Models\Tramite::with(['vesselCall.vessel', 'entidad', 'events'])
            ->orderBy('fecha_inicio');

        // Aplicar filtros
        if (isset($filters['fecha_desde'])) {
            $query->where('fecha_inicio', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta'])) {
            $query->where('fecha_inicio', '<=', $filters['fecha_hasta']);
        }

        if (isset($filters['regimen'])) {
            $query->where('regimen', $filters['regimen']);
        }

        if (isset($filters['entidad_id'])) {
            $query->where('entidad_id', $filters['entidad_id']);
        }

        $data = $query->get();

        // Analizar cada trámite para detectar incidencias
        $dataConIncidencias = $data->map(function ($tramite) {
            $analisis = $this->analizarIncidenciasTramite($tramite);
            $tramite->tiene_rechazo = $analisis['tiene_rechazo'];
            $tramite->tiene_reproceso = $analisis['tiene_reproceso'];
            $tramite->tiempo_subsanacion_h = $analisis['tiempo_subsanacion_h'];
            $tramite->num_observaciones = $analisis['num_observaciones'];
            return $tramite;
        });

        // Agrupar por entidad
        $porEntidad = $this->agruparIncidenciasPorEntidad($dataConIncidencias);

        $kpis = $this->calculateR9Kpis($dataConIncidencias);

        return [
            'data' => $dataConIncidencias,
            'kpis' => $kpis,
            'por_entidad' => $porEntidad,
        ];
    }

    /**
     * Analiza las incidencias de un trámite individual
     *
     * @param Tramite $tramite
     * @return array{tiene_rechazo: bool, tiene_reproceso: bool, tiempo_subsanacion_h: float|null, num_observaciones: int}
     */
    private function analizarIncidenciasTramite(\App\Models\Tramite $tramite): array
    {
        $tieneRechazo = $tramite->estado === 'RECHAZADO';
        $tieneReproceso = false;
        $tiempoSubsanacion = null;
        $numObservaciones = 0;

        // Obtener eventos ordenados por timestamp
        $eventos = $tramite->events()->orderBy('event_ts')->get();

        if ($eventos->isEmpty()) {
            return [
                'tiene_rechazo' => $tieneRechazo,
                'tiene_reproceso' => false,
                'tiempo_subsanacion_h' => null,
                'num_observaciones' => 0,
            ];
        }

        // Analizar secuencia de eventos para detectar reprocesamientos y subsanaciones
        $tiemposSubsanacion = [];
        $estadoAnterior = null;

        foreach ($eventos as $evento) {
            // Contar observaciones
            if ($evento->estado === 'OBSERVADO') {
                $numObservaciones++;
            }

            // Detectar reproceso: cuando un trámite vuelve a EN_REVISION después de estar OBSERVADO
            if ($estadoAnterior === 'OBSERVADO' && $evento->estado === 'EN_REVISION') {
                $tieneReproceso = true;
            }

            // Calcular tiempo de subsanación: tiempo desde OBSERVADO hasta el siguiente cambio de estado
            if ($estadoAnterior === 'OBSERVADO' && $evento->estado !== 'OBSERVADO') {
                // Buscar el evento anterior de OBSERVADO
                $eventoObservado = $eventos->where('estado', 'OBSERVADO')
                    ->where('event_ts', '<', $evento->event_ts)
                    ->sortByDesc('event_ts')
                    ->first();

                if ($eventoObservado) {
                    $tiempoHoras = ($evento->event_ts->timestamp - $eventoObservado->event_ts->timestamp) / 3600;
                    $tiemposSubsanacion[] = max(0, $tiempoHoras);
                }
            }

            $estadoAnterior = $evento->estado;
        }

        // Calcular tiempo promedio de subsanación si hay datos
        if (!empty($tiemposSubsanacion)) {
            $tiempoSubsanacion = array_sum($tiemposSubsanacion) / count($tiemposSubsanacion);
        }

        return [
            'tiene_rechazo' => $tieneRechazo,
            'tiene_reproceso' => $tieneReproceso,
            'tiempo_subsanacion_h' => $tiempoSubsanacion,
            'num_observaciones' => $numObservaciones,
        ];
    }

    /**
     * Agrupa incidencias por entidad aduanera
     *
     * @param Collection $tramites
     * @return Collection
     */
    private function agruparIncidenciasPorEntidad(Collection $tramites): Collection
    {
        $porEntidad = $tramites->groupBy('entidad_id');

        return $porEntidad->map(function ($tramitesEntidad, $entidadId) {
            $total = $tramitesEntidad->count();
            $rechazos = $tramitesEntidad->where('tiene_rechazo', true)->count();
            $reprocesamientos = $tramitesEntidad->where('tiene_reproceso', true)->count();
            
            // Calcular tiempo promedio de subsanación
            $tiemposSubsanacion = $tramitesEntidad
                ->filter(fn($t) => $t->tiempo_subsanacion_h !== null)
                ->pluck('tiempo_subsanacion_h');
            
            $tiempoSubsanacionPromedio = $tiemposSubsanacion->avg() ?? 0.0;

            // Calcular total de observaciones
            $totalObservaciones = $tramitesEntidad->sum('num_observaciones');

            $entidadName = $tramitesEntidad->first()->entidad->name ?? "Entidad {$entidadId}";

            return [
                'entidad_id' => $entidadId,
                'entidad_name' => $entidadName,
                'total_tramites' => $total,
                'rechazos' => $rechazos,
                'reprocesos' => $reprocesamientos,
                'observaciones' => $totalObservaciones,
                'tiempo_subsanacion_promedio_h' => round($tiempoSubsanacionPromedio, 2),
                'pct_rechazos' => $total > 0 ? round(($rechazos / $total) * 100, 2) : 0.0,
                'pct_reprocesos' => $total > 0 ? round(($reprocesamientos / $total) * 100, 2) : 0.0,
            ];
        })->sortByDesc('rechazos')->values();
    }

    /**
     * Calcula los KPIs del reporte R9
     *
     * @param Collection $data
     * @return array<string, mixed>
     */
    private function calculateR9Kpis(Collection $data): array
    {
        $total = $data->count();

        if ($total === 0) {
            return [
                'rechazos' => 0,
                'reprocesos' => 0,
                'tiempo_subsanacion_promedio_h' => 0.0,
                'total_tramites' => 0,
                'pct_rechazos' => 0.0,
                'pct_reprocesos' => 0.0,
                'total_observaciones' => 0,
            ];
        }

        // Contar rechazos y reprocesamientos
        $rechazos = $data->where('tiene_rechazo', true)->count();
        $reprocesamientos = $data->where('tiene_reproceso', true)->count();

        // Calcular tiempo promedio de subsanación
        $tiemposSubsanacion = $data
            ->filter(fn($t) => $t->tiempo_subsanacion_h !== null)
            ->pluck('tiempo_subsanacion_h');
        
        $tiempoSubsanacionPromedio = $tiemposSubsanacion->avg() ?? 0.0;

        // Contar total de observaciones
        $totalObservaciones = $data->sum('num_observaciones');

        return [
            'rechazos' => $rechazos,
            'reprocesos' => $reprocesamientos,
            'tiempo_subsanacion_promedio_h' => round($tiempoSubsanacionPromedio, 2),
            'total_tramites' => $total,
            'pct_rechazos' => round(($rechazos / $total) * 100, 2),
            'pct_reprocesos' => round(($reprocesamientos / $total) * 100, 2),
            'total_observaciones' => $totalObservaciones,
        ];
    }

    /**
     * Genera el reporte R10: Panel de KPIs Ejecutivo
     * Muestra KPIs consolidados del sistema con comparativa de periodo anterior
     * KPIs: turnaround, espera_camion, cumpl_citas, tramites_ok
     *
     * @param array<string, mixed> $filters
     * @return array{kpis: array<string, array<string, mixed>>, periodo_actual: array<string, string>, periodo_anterior: array<string, string>}
     */
    public function generateR10(array $filters): array
    {
        // Determinar periodos
        $periodos = $this->calcularPeriodos($filters);
        $periodoActual = $periodos['actual'];
        $periodoAnterior = $periodos['anterior'];

        // Calcular KPIs para periodo actual
        $kpisActuales = $this->calcularKpisConsolidados($periodoActual);

        // Calcular KPIs para periodo anterior
        $kpisAnteriores = $this->calcularKpisConsolidados($periodoAnterior);

        // Calcular tendencias y comparativas
        $kpisConTendencia = $this->calcularTendencias($kpisActuales, $kpisAnteriores, $filters);

        return [
            'kpis' => $kpisConTendencia,
            'periodo_actual' => $periodoActual,
            'periodo_anterior' => $periodoAnterior,
        ];
    }

    /**
     * Calcula los periodos actual y anterior basándose en los filtros
     *
     * @param array<string, mixed> $filters
     * @return array{actual: array<string, string>, anterior: array<string, string>}
     */
    private function calcularPeriodos(array $filters): array
    {
        // Si se especifican fechas, usar esas
        if (isset($filters['fecha_desde']) && isset($filters['fecha_hasta'])) {
            $fechaDesde = new \DateTime($filters['fecha_desde']);
            $fechaHasta = new \DateTime($filters['fecha_hasta']);
        } else {
            // Por defecto: últimos 30 días
            $fechaHasta = new \DateTime();
            $fechaDesde = (clone $fechaHasta)->modify('-30 days');
        }

        // Calcular duración del periodo
        $duracionDias = $fechaDesde->diff($fechaHasta)->days;

        // Periodo anterior: mismo número de días antes del periodo actual
        $fechaAntDesde = (clone $fechaDesde)->modify("-{$duracionDias} days");
        $fechaAntHasta = clone $fechaDesde;

        return [
            'actual' => [
                'fecha_desde' => $fechaDesde->format('Y-m-d H:i:s'),
                'fecha_hasta' => $fechaHasta->format('Y-m-d H:i:s'),
            ],
            'anterior' => [
                'fecha_desde' => $fechaAntDesde->format('Y-m-d H:i:s'),
                'fecha_hasta' => $fechaAntHasta->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Calcula los KPIs consolidados para un periodo específico
     *
     * @param array<string, string> $periodo
     * @return array<string, float>
     */
    private function calcularKpisConsolidados(array $periodo): array
    {
        // KPI 1: Turnaround promedio (horas)
        $turnaroundPromedio = $this->calcularTurnaroundPromedio($periodo);

        // KPI 2: Espera de camión promedio (horas)
        $esperaCamionPromedio = $this->calcularEsperaCamionPromedio($periodo);

        // KPI 3: Cumplimiento de citas (%)
        $cumplimientoCitas = $this->calcularCumplimientoCitas($periodo);

        // KPI 4: Trámites OK (%)
        $tramitesOk = $this->calcularTramitesOk($periodo);

        return [
            'turnaround' => $turnaroundPromedio,
            'espera_camion' => $esperaCamionPromedio,
            'cumpl_citas' => $cumplimientoCitas,
            'tramites_ok' => $tramitesOk,
        ];
    }

    /**
     * Calcula el turnaround promedio para un periodo
     *
     * @param array<string, string> $periodo
     * @return float
     */
    private function calcularTurnaroundPromedio(array $periodo): float
    {
        $vesselCalls = VesselCall::whereNotNull('ata')
            ->whereNotNull('atd')
            ->where('ata', '>=', $periodo['fecha_desde'])
            ->where('ata', '<=', $periodo['fecha_hasta'])
            ->get();

        if ($vesselCalls->isEmpty()) {
            return 0.0;
        }

        $turnarounds = $vesselCalls->map(function ($vc) {
            return ($vc->atd->timestamp - $vc->ata->timestamp) / 3600;
        });

        return round($turnarounds->avg(), 2);
    }

    /**
     * Calcula el tiempo de espera promedio de camiones para un periodo
     *
     * @param array<string, string> $periodo
     * @return float
     */
    private function calcularEsperaCamionPromedio(array $periodo): float
    {
        $appointments = \App\Models\Appointment::with('gateEvents')
            ->whereNotNull('hora_llegada')
            ->whereIn('estado', ['ATENDIDA', 'CONFIRMADA'])
            ->where('hora_llegada', '>=', $periodo['fecha_desde'])
            ->where('hora_llegada', '<=', $periodo['fecha_hasta'])
            ->get();

        if ($appointments->isEmpty()) {
            return 0.0;
        }

        $esperas = $appointments->map(function ($appointment) {
            $primerEvento = $appointment->gateEvents()
                ->where('action', 'ENTRADA')
                ->orderBy('event_ts')
                ->first();

            if ($primerEvento && $appointment->hora_llegada) {
                $esperaHoras = ($primerEvento->event_ts->timestamp - $appointment->hora_llegada->timestamp) / 3600;
                return max(0, $esperaHoras);
            }

            return null;
        })->filter(fn($espera) => $espera !== null);

        if ($esperas->isEmpty()) {
            return 0.0;
        }

        return round($esperas->avg(), 2);
    }

    /**
     * Calcula el porcentaje de cumplimiento de citas para un periodo
     *
     * @param array<string, string> $periodo
     * @return float
     */
    private function calcularCumplimientoCitas(array $periodo): float
    {
        $appointments = \App\Models\Appointment::whereIn('estado', ['ATENDIDA', 'CONFIRMADA', 'NO_SHOW'])
            ->where('hora_programada', '>=', $periodo['fecha_desde'])
            ->where('hora_programada', '<=', $periodo['fecha_hasta'])
            ->get();

        if ($appointments->isEmpty()) {
            return 0.0;
        }

        $total = $appointments->count();
        $aTiempo = $appointments->filter(function ($appointment) {
            if ($appointment->estado === 'NO_SHOW' || $appointment->hora_llegada === null) {
                return false;
            }

            $desvioMin = ($appointment->hora_llegada->timestamp - $appointment->hora_programada->timestamp) / 60;
            return abs($desvioMin) <= 15;
        })->count();

        return round(($aTiempo / $total) * 100, 2);
    }

    /**
     * Calcula el porcentaje de trámites aprobados para un periodo
     *
     * @param array<string, string> $periodo
     * @return float
     */
    private function calcularTramitesOk(array $periodo): float
    {
        $tramites = \App\Models\Tramite::where('fecha_inicio', '>=', $periodo['fecha_desde'])
            ->where('fecha_inicio', '<=', $periodo['fecha_hasta'])
            ->get();

        if ($tramites->isEmpty()) {
            return 0.0;
        }

        $total = $tramites->count();
        $aprobados = $tramites->where('estado', 'APROBADO')->count();

        return round(($aprobados / $total) * 100, 2);
    }

    /**
     * Calcula tendencias comparando KPIs actuales con anteriores
     *
     * @param array<string, float> $kpisActuales
     * @param array<string, float> $kpisAnteriores
     * @param array<string, mixed> $filters
     * @return array<string, array<string, mixed>>
     */
    private function calcularTendencias(array $kpisActuales, array $kpisAnteriores, array $filters): array
    {
        // Metas por defecto (pueden ser configurables)
        $metas = [
            'turnaround' => $filters['meta_turnaround'] ?? 48.0, // 48 horas
            'espera_camion' => $filters['meta_espera_camion'] ?? 2.0, // 2 horas
            'cumpl_citas' => $filters['meta_cumpl_citas'] ?? 85.0, // 85%
            'tramites_ok' => $filters['meta_tramites_ok'] ?? 90.0, // 90%
        ];

        $resultado = [];

        foreach ($kpisActuales as $kpi => $valorActual) {
            $valorAnterior = $kpisAnteriores[$kpi] ?? 0.0;
            $meta = $metas[$kpi] ?? 0.0;

            // Calcular diferencia y tendencia
            $diferencia = $valorActual - $valorAnterior;
            
            // Determinar si la tendencia es positiva o negativa
            // Para turnaround y espera_camion: menor es mejor (↓ es positivo)
            // Para cumpl_citas y tramites_ok: mayor es mejor (↑ es positivo)
            $tendenciaPositiva = false;
            if (in_array($kpi, ['turnaround', 'espera_camion'])) {
                $tendenciaPositiva = $diferencia < 0;
            } else {
                $tendenciaPositiva = $diferencia > 0;
            }

            // Símbolo de tendencia
            if ($diferencia > 0) {
                $simboloTendencia = '↑';
            } elseif ($diferencia < 0) {
                $simboloTendencia = '↓';
            } else {
                $simboloTendencia = '→';
            }

            // Calcular porcentaje de cambio
            $pctCambio = 0.0;
            if ($valorAnterior != 0) {
                $pctCambio = (($valorActual - $valorAnterior) / abs($valorAnterior)) * 100;
            }

            // Determinar si se cumple la meta
            $cumpleMeta = false;
            if (in_array($kpi, ['turnaround', 'espera_camion'])) {
                $cumpleMeta = $valorActual <= $meta;
            } else {
                $cumpleMeta = $valorActual >= $meta;
            }

            $resultado[$kpi] = [
                'valor_actual' => $valorActual,
                'valor_anterior' => $valorAnterior,
                'meta' => $meta,
                'diferencia' => round($diferencia, 2),
                'pct_cambio' => round($pctCambio, 2),
                'tendencia' => $simboloTendencia,
                'tendencia_positiva' => $tendenciaPositiva,
                'cumple_meta' => $cumpleMeta,
            ];
        }

        return $resultado;
    }

    /**
     * Genera el reporte R11: Alertas Tempranas
     * Detecta condiciones de riesgo operacional y genera alertas
     * Alertas: congestión de muelles (utilización > 85%), acumulación de camiones (espera > 4h promedio)
     *
     * @param array<string, mixed> $filters
     * @return array{alertas: Collection, kpis: array<string, mixed>, estado_general: string}
     */
    public function generateR11(array $filters): array
    {
        // Determinar periodo (por defecto: últimas 24 horas)
        $periodo = $this->calcularPeriodoR11($filters);

        // Detectar alertas de congestión de muelles
        $alertasCongestión = $this->detectarAlertasCongestión($periodo, $filters);

        // Detectar alertas de acumulación de camiones
        $alertasAcumulación = $this->detectarAlertasAcumulación($periodo, $filters);

        // Combinar todas las alertas
        $todasLasAlertas = $alertasCongestión->merge($alertasAcumulación);

        // Persistir alertas a la base de datos
        $this->persistirAlertas($todasLasAlertas);

        // Calcular KPIs de alertas
        $kpis = $this->calculateR11Kpis($todasLasAlertas, $periodo);

        // Determinar estado general del sistema
        $estadoGeneral = $this->determinarEstadoGeneral($todasLasAlertas);

        // Enviar notificaciones mock
        $this->enviarNotificacionesMock($todasLasAlertas);

        return [
            'alertas' => $todasLasAlertas,
            'kpis' => $kpis,
            'estado_general' => $estadoGeneral,
        ];
    }

    /**
     * Calcula el periodo para el reporte R11
     *
     * @param array<string, mixed> $filters
     * @return array<string, string>
     */
    private function calcularPeriodoR11(array $filters): array
    {
        // Si se especifican fechas, usar esas
        if (isset($filters['fecha_desde']) && isset($filters['fecha_hasta'])) {
            $fechaDesde = \Carbon\Carbon::parse($filters['fecha_desde']);
            $fechaHasta = \Carbon\Carbon::parse($filters['fecha_hasta']);
        } else {
            // Por defecto: últimas 24 horas
            $fechaHasta = now();
            $fechaDesde = now()->subHours(24);
        }

        return [
            'fecha_desde' => $fechaDesde->format('Y-m-d H:i:s'),
            'fecha_hasta' => $fechaHasta->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Detecta alertas de congestión de muelles
     * Umbral: utilización > 85%
     *
     * @param array<string, string> $periodo
     * @param array<string, mixed> $filters
     * @return Collection
     */
    private function detectarAlertasCongestión(array $periodo, array $filters): Collection
    {
        $alertas = collect();
        $umbralCongestión = $filters['umbral_congestión'] ?? 85.0; // 85% por defecto

        // Obtener llamadas de naves en el periodo
        $vesselCalls = VesselCall::with(['vessel', 'berth'])
            ->whereNotNull('atb')
            ->where('atb', '>=', $periodo['fecha_desde'])
            ->where('atb', '<=', $periodo['fecha_hasta'])
            ->get();

        if ($vesselCalls->isEmpty()) {
            return $alertas;
        }

        // Agrupar por muelle
        $porMuelle = $vesselCalls->groupBy('berth_id');

        foreach ($porMuelle as $berthId => $llamadas) {
            // Calcular utilización actual del muelle
            $utilizacion = $this->calcularUtilizaciónMuelleActual($llamadas);

            if ($utilizacion > $umbralCongestión) {
                $berthName = $llamadas->first()->berth->name ?? "Muelle {$berthId}";

                // Determinar nivel de alerta
                $nivel = $this->determinarNivelAlerta($utilizacion, $umbralCongestión);

                $alertas->push([
                    'id' => "CONGESTION_BERTH_{$berthId}",
                    'tipo' => 'CONGESTIÓN_MUELLE',
                    'nivel' => $nivel,
                    'muelle_id' => $berthId,
                    'muelle_nombre' => $berthName,
                    'valor' => round($utilizacion, 2),
                    'umbral' => $umbralCongestión,
                    'unidad' => '%',
                    'descripción' => "Congestión en {$berthName}: utilización al {$utilizacion}%",
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'acciones_recomendadas' => [
                        'Revisar programación de naves',
                        'Considerar redistribución a otros muelles',
                        'Aumentar recursos de operación',
                    ],
                ]);
            }
        }

        return $alertas;
    }

    /**
     * Calcula la utilización actual de un muelle basándose en llamadas activas
     *
     * @param Collection $llamadas
     * @return float
     */
    private function calcularUtilizaciónMuelleActual(Collection $llamadas): float
    {
        if ($llamadas->isEmpty()) {
            return 0.0;
        }

        $ahora = now();
        $llamadasActivas = $llamadas->filter(function ($llamada) use ($ahora) {
            // Una llamada está activa si: ATB <= ahora <= ATD (o ATD es null)
            if ($llamada->atb === null) {
                return false;
            }

            $atbTime = $llamada->atb->timestamp;
            $ahoraTime = $ahora->timestamp;

            if ($llamada->atd === null) {
                // Si no tiene ATD, consideramos que está activa si ATB <= ahora
                return $atbTime <= $ahoraTime;
            }

            $atdTime = $llamada->atd->timestamp;
            return $atbTime <= $ahoraTime && $ahoraTime <= $atdTime;
        });

        // Calcular utilización como porcentaje de llamadas activas vs total
        $utilizacion = ($llamadasActivas->count() / $llamadas->count()) * 100;

        return $utilizacion;
    }

    /**
     * Detecta alertas de acumulación de camiones
     * Umbral: espera promedio > 4 horas
     *
     * @param array<string, string> $periodo
     * @param array<string, mixed> $filters
     * @return Collection
     */
    private function detectarAlertasAcumulación(array $periodo, array $filters): Collection
    {
        $alertas = collect();
        $umbralAcumulación = $filters['umbral_acumulación'] ?? 4.0; // 4 horas por defecto

        // Obtener citas en el periodo
        $appointments = \App\Models\Appointment::with(['company', 'gateEvents'])
            ->whereNotNull('hora_llegada')
            ->whereIn('estado', ['ATENDIDA', 'CONFIRMADA', 'PROGRAMADA'])
            ->where('hora_llegada', '>=', $periodo['fecha_desde'])
            ->where('hora_llegada', '<=', $periodo['fecha_hasta'])
            ->get();

        if ($appointments->isEmpty()) {
            return $alertas;
        }

        // Calcular tiempo de espera promedio
        $esperasPromedio = $this->calcularEsperaPromedioPorEmpresa($appointments);

        foreach ($esperasPromedio as $companyId => $datos) {
            if ($datos['espera_promedio'] > $umbralAcumulación) {
                // Determinar nivel de alerta
                $nivel = $this->determinarNivelAlerta($datos['espera_promedio'], $umbralAcumulación);

                $alertas->push([
                    'id' => "ACUMULACION_COMPANY_{$companyId}",
                    'tipo' => 'ACUMULACIÓN_CAMIONES',
                    'nivel' => $nivel,
                    'empresa_id' => $companyId,
                    'empresa_nombre' => $datos['company_name'],
                    'valor' => round($datos['espera_promedio'], 2),
                    'umbral' => $umbralAcumulación,
                    'unidad' => 'h',
                    'descripción' => "Acumulación de camiones en {$datos['company_name']}: espera promedio {$datos['espera_promedio']}h",
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'citas_afectadas' => $datos['total_citas'],
                    'acciones_recomendadas' => [
                        'Aumentar capacidad de gates',
                        'Contactar a la empresa transportista',
                        'Revisar programación de citas',
                        'Considerar turnos adicionales',
                    ],
                ]);
            }
        }

        return $alertas;
    }

    /**
     * Calcula la espera promedio por empresa
     *
     * @param Collection $appointments
     * @return array<int, array<string, mixed>>
     */
    private function calcularEsperaPromedioPorEmpresa(Collection $appointments): array
    {
        $porEmpresa = $appointments->groupBy('company_id');
        $resultado = [];

        foreach ($porEmpresa as $companyId => $citasEmpresa) {
            $esperas = $citasEmpresa->map(function ($appointment) {
                // Acceder a la relación como propiedad (ya fue eager loaded)
                $gateEvents = $appointment->gateEvents;
                
                if ($gateEvents && $gateEvents->isNotEmpty()) {
                    $primerEvento = $gateEvents
                        ->where('action', 'ENTRADA')
                        ->sortBy('event_ts')
                        ->first();

                    if ($primerEvento && $appointment->hora_llegada) {
                        $esperaHoras = ($primerEvento->event_ts->timestamp - $appointment->hora_llegada->timestamp) / 3600;
                        return max(0, $esperaHoras);
                    }
                }

                return null;
            })->filter(fn($espera) => $espera !== null);

            if ($esperas->isNotEmpty()) {
                $resultado[$companyId] = [
                    'company_name' => $citasEmpresa->first()->company->name ?? "Empresa {$companyId}",
                    'espera_promedio' => $esperas->avg(),
                    'espera_máxima' => $esperas->max(),
                    'espera_mínima' => $esperas->min(),
                    'total_citas' => $citasEmpresa->count(),
                ];
            }
        }

        return $resultado;
    }

    /**
     * Determina el nivel de alerta basándose en el valor y el umbral
     * Niveles: VERDE (< umbral), AMARILLO (umbral a 1.5x umbral), ROJO (> 1.5x umbral)
     *
     * @param float $valor
     * @param float $umbral
     * @return string
     */
    private function determinarNivelAlerta(float $valor, float $umbral): string
    {
        if ($valor < $umbral) {
            return 'VERDE';
        } elseif ($valor <= $umbral * 1.5) {
            return 'AMARILLO';
        } else {
            return 'ROJO';
        }
    }

    /**
     * Calcula los KPIs del reporte R11
     *
     * @param Collection $alertas
     * @param array<string, string> $periodo
     * @return array<string, mixed>
     */
    private function calculateR11Kpis(Collection $alertas, array $periodo): array
    {
        $totalAlertas = $alertas->count();
        $alertasRojas = $alertas->where('nivel', 'ROJO')->count();
        $alertasAmarillas = $alertas->where('nivel', 'AMARILLO')->count();
        $alertasVerdes = $alertas->where('nivel', 'VERDE')->count();

        $alertasCongestión = $alertas->where('tipo', 'CONGESTIÓN_MUELLE')->count();
        $alertasAcumulación = $alertas->where('tipo', 'ACUMULACIÓN_CAMIONES')->count();

        return [
            'total_alertas' => $totalAlertas,
            'alertas_rojas' => $alertasRojas,
            'alertas_amarillas' => $alertasAmarillas,
            'alertas_verdes' => $alertasVerdes,
            'alertas_congestión' => $alertasCongestión,
            'alertas_acumulación' => $alertasAcumulación,
            'pct_alertas_críticas' => $totalAlertas > 0 ? round(($alertasRojas / $totalAlertas) * 100, 2) : 0.0,
        ];
    }

    /**
     * Determina el estado general del sistema basándose en las alertas
     *
     * @param Collection $alertas
     * @return string
     */
    private function determinarEstadoGeneral(Collection $alertas): string
    {
        if ($alertas->isEmpty()) {
            return 'VERDE';
        }

        $alertasRojas = $alertas->where('nivel', 'ROJO')->count();
        $alertasAmarillas = $alertas->where('nivel', 'AMARILLO')->count();

        if ($alertasRojas > 0) {
            return 'ROJO';
        } elseif ($alertasAmarillas > 0) {
            return 'AMARILLO';
        } else {
            return 'VERDE';
        }
    }

    /**
     * Persiste las alertas a la base de datos
     * Crea nuevas alertas o actualiza las existentes
     *
     * @param Collection $alertas
     * @return void
     */
    private function persistirAlertas(Collection $alertas): void
    {
        foreach ($alertas as $alerta) {
            // Determinar entity_type y entity_id basándose en el tipo de alerta
            $entityType = null;
            $entityId = null;

            if ($alerta['tipo'] === 'CONGESTIÓN_MUELLE') {
                $entityType = 'berth';
                $entityId = $alerta['muelle_id'] ?? null;
            } elseif ($alerta['tipo'] === 'ACUMULACIÓN_CAMIONES') {
                $entityType = 'company';
                $entityId = $alerta['empresa_id'] ?? null;
            }

            // Buscar si la alerta ya existe
            $existingAlert = Alert::where('alert_id', $alerta['id'])->first();

            if ($existingAlert) {
                // Actualizar alerta existente
                $existingAlert->update([
                    'nivel' => $alerta['nivel'],
                    'valor' => $alerta['valor'],
                    'umbral' => $alerta['umbral'],
                    'descripción' => $alerta['descripción'],
                    'acciones_recomendadas' => $alerta['acciones_recomendadas'],
                    'citas_afectadas' => $alerta['citas_afectadas'] ?? null,
                    'detected_at' => now(),
                    'estado' => 'ACTIVA',
                ]);
            } else {
                // Crear nueva alerta
                Alert::create([
                    'alert_id' => $alerta['id'],
                    'tipo' => $alerta['tipo'],
                    'nivel' => $alerta['nivel'],
                    'entity_id' => $entityId,
                    'entity_type' => $entityType,
                    'entity_name' => $alerta['muelle_nombre'] ?? $alerta['empresa_nombre'] ?? null,
                    'valor' => $alerta['valor'],
                    'umbral' => $alerta['umbral'],
                    'unidad' => $alerta['unidad'],
                    'descripción' => $alerta['descripción'],
                    'acciones_recomendadas' => $alerta['acciones_recomendadas'],
                    'citas_afectadas' => $alerta['citas_afectadas'] ?? null,
                    'detected_at' => now(),
                    'estado' => 'ACTIVA',
                ]);
            }
        }
    }

    /**
     * Envía notificaciones mock a roles operacionales
     * Guarda las notificaciones en storage/app/mocks/notifications.json
     *
     * @param Collection $alertas
     * @return void
     */
    private function enviarNotificacionesMock(Collection $alertas): void
    {
        $this->notificationService->sendPushNotifications($alertas);
    }
    /**
     * Genera el reporte R12: Cumplimiento de SLAs
     * Muestra el cumplimiento de SLAs por actor (empresa, entidad)
     * Calcula: pct_cumplimiento, incumplimientos, penalidades
     *
     * @param array<string, mixed> $filters
     * @return array{data: Collection, kpis: array<string, mixed>, por_actor: Collection}
     */
    public function generateR12(array $filters): array
    {
        // Determinar periodo
        $periodo = $this->calcularPeriodoR12($filters);

        // Obtener definiciones de SLAs
        $slaDefinitions = \App\Models\SlaDefinition::all();

        if ($slaDefinitions->isEmpty()) {
            // Si no hay SLAs definidos, retornar estructura vacía
            return [
                'data' => collect(),
                'kpis' => $this->calculateR12KpisVacios(),
                'por_actor' => collect(),
            ];
        }

        // Obtener actores (empresas y entidades)
        $actores = \App\Models\Actor::all();

        // Calcular cumplimiento de SLAs para cada actor
        $cumplimientosPorActor = $this->calcularCumplimientoSLAsPorActor($slaDefinitions, $actores, $periodo, $filters);

        // Calcular KPIs consolidados
        $kpis = $this->calculateR12Kpis($cumplimientosPorActor);

        return [
            'data' => $cumplimientosPorActor,
            'kpis' => $kpis,
            'por_actor' => $cumplimientosPorActor,
        ];
    }

    /**
     * Calcula el periodo para el reporte R12
     *
     * @param array<string, mixed> $filters
     * @return array<string, string>
     */
    private function calcularPeriodoR12(array $filters): array
    {
        // Si se especifican fechas, usar esas
        if (isset($filters['fecha_desde']) && isset($filters['fecha_hasta'])) {
            $fechaDesde = \Carbon\Carbon::parse($filters['fecha_desde']);
            $fechaHasta = \Carbon\Carbon::parse($filters['fecha_hasta']);
        } else {
            // Por defecto: últimos 30 días
            $fechaHasta = now();
            $fechaDesde = now()->subDays(30);
        }

        return [
            'fecha_desde' => $fechaDesde->format('Y-m-d H:i:s'),
            'fecha_hasta' => $fechaHasta->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Calcula el cumplimiento de SLAs para cada actor
     *
     * @param Collection $slaDefinitions
     * @param Collection $actores
     * @param array<string, string> $periodo
     * @param array<string, mixed> $filters
     * @return Collection
     */
    private function calcularCumplimientoSLAsPorActor(Collection $slaDefinitions, Collection $actores, array $periodo, array $filters): Collection
    {
        $resultado = collect();

        foreach ($actores as $actor) {
            $cumplimientosActor = [];
            $totalIncumplimientos = 0;
            $totalPenalidades = 0.0;

            foreach ($slaDefinitions as $sla) {
                // Calcular valor del SLA para este actor en el periodo
                $valor = $this->calcularValorSLA($sla, $actor, $periodo);

                // Determinar si cumple el SLA
                $cumple = $this->verificarCumplimientoSLA($valor, $sla->umbral, $sla->comparador);

                if (!$cumple) {
                    $totalIncumplimientos++;
                    // Calcular penalidad (porcentaje de exceso sobre el umbral)
                    $penalidad = $this->calcularPenalidad($valor, $sla->umbral, $sla->comparador);
                    $totalPenalidades += $penalidad;
                }

                $cumplimientosActor[] = [
                    'sla_id' => $sla->id,
                    'sla_code' => $sla->code,
                    'sla_name' => $sla->name,
                    'valor' => round($valor, 2),
                    'umbral' => $sla->umbral,
                    'comparador' => $sla->comparador,
                    'cumple' => $cumple,
                    'penalidad' => round($penalidad, 2),
                ];
            }

            // Calcular porcentaje de cumplimiento
            $totalSLAs = count($cumplimientosActor);
            $cumplidos = collect($cumplimientosActor)->where('cumple', true)->count();
            $pctCumplimiento = $totalSLAs > 0 ? ($cumplidos / $totalSLAs) * 100 : 0.0;

            $resultado->push([
                'actor_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_tipo' => $actor->tipo,
                'ref_table' => $actor->ref_table,
                'ref_id' => $actor->ref_id,
                'slas' => $cumplimientosActor,
                'total_slas' => $totalSLAs,
                'slas_cumplidos' => $cumplidos,
                'slas_incumplidos' => $totalIncumplimientos,
                'pct_cumplimiento' => round($pctCumplimiento, 2),
                'penalidades_totales' => round($totalPenalidades, 2),
                'estado' => $pctCumplimiento >= 90 ? 'EXCELENTE' : ($pctCumplimiento >= 75 ? 'BUENO' : ($pctCumplimiento >= 50 ? 'REGULAR' : 'CRÍTICO')),
            ]);
        }

        return $resultado->sortByDesc('pct_cumplimiento')->values();
    }

    /**
     * Calcula el valor del SLA para un actor específico en un periodo
     * Basándose en el código del SLA, obtiene el valor correspondiente
     *
     * @param \App\Models\SlaDefinition $sla
     * @param \App\Models\Actor $actor
     * @param array<string, string> $periodo
     * @return float
     */
    private function calcularValorSLA(\App\Models\SlaDefinition $sla, \App\Models\Actor $actor, array $periodo): float
    {
        // Mapeo de SLAs a métodos de cálculo
        $slaCode = $sla->code;

        switch ($slaCode) {
            case 'TURNAROUND_48H':
                return $this->calcularTurnaroundPromedioPorActor($actor, $periodo);

            case 'ESPERA_CAMION_2H':
                return $this->calcularEsperaCamionPromedioPorActor($actor, $periodo);

            case 'TRAMITE_DESPACHO_24H':
                return $this->calcularTramiteDespachoPromedioPorActor($actor, $periodo);

            default:
                return 0.0;
        }
    }

    /**
     * Calcula el turnaround promedio para un actor específico
     *
     * @param \App\Models\Actor $actor
     * @param array<string, string> $periodo
     * @return float
     */
    private function calcularTurnaroundPromedioPorActor(\App\Models\Actor $actor, array $periodo): float
    {
        // El turnaround es relevante para actores de tipo TRANSPORTISTA (empresas)
        if ($actor->tipo !== 'TRANSPORTISTA') {
            return 0.0;
        }

        // Obtener llamadas de naves en el periodo
        $vesselCalls = VesselCall::whereNotNull('ata')
            ->whereNotNull('atd')
            ->where('ata', '>=', $periodo['fecha_desde'])
            ->where('ata', '<=', $periodo['fecha_hasta'])
            ->get();

        if ($vesselCalls->isEmpty()) {
            return 0.0;
        }

        $turnarounds = $vesselCalls->map(function ($vc) {
            return ($vc->atd->timestamp - $vc->ata->timestamp) / 3600;
        });

        return round($turnarounds->avg(), 2);
    }

    /**
     * Calcula la espera de camión promedio para un actor específico
     *
     * @param \App\Models\Actor $actor
     * @param array<string, string> $periodo
     * @return float
     */
    private function calcularEsperaCamionPromedioPorActor(\App\Models\Actor $actor, array $periodo): float
    {
        // La espera de camión es relevante para actores de tipo TRANSPORTISTA (empresas)
        if ($actor->tipo !== 'TRANSPORTISTA') {
            return 0.0;
        }

        // Obtener citas de la empresa en el periodo
        $appointments = \App\Models\Appointment::with('gateEvents')
            ->where('company_id', $actor->ref_id)
            ->whereNotNull('hora_llegada')
            ->whereIn('estado', ['ATENDIDA', 'CONFIRMADA'])
            ->where('hora_llegada', '>=', $periodo['fecha_desde'])
            ->where('hora_llegada', '<=', $periodo['fecha_hasta'])
            ->get();

        if ($appointments->isEmpty()) {
            return 0.0;
        }

        $esperas = $appointments->map(function ($appointment) {
            $primerEvento = $appointment->gateEvents()
                ->where('action', 'ENTRADA')
                ->orderBy('event_ts')
                ->first();

            if ($primerEvento && $appointment->hora_llegada) {
                $esperaHoras = ($primerEvento->event_ts->timestamp - $appointment->hora_llegada->timestamp) / 3600;
                return max(0, $esperaHoras);
            }

            return null;
        })->filter(fn($espera) => $espera !== null);

        if ($esperas->isEmpty()) {
            return 0.0;
        }

        return round($esperas->avg(), 2);
    }

    /**
     * Calcula el tiempo de despacho de trámites promedio para un actor específico
     *
     * @param \App\Models\Actor $actor
     * @param array<string, string> $periodo
     * @return float
     */
    private function calcularTramiteDespachoPromedioPorActor(\App\Models\Actor $actor, array $periodo): float
    {
        // El despacho de trámites es relevante para actores de tipo ENTIDAD_ADUANA
        if ($actor->tipo !== 'ENTIDAD_ADUANA') {
            return 0.0;
        }

        // Obtener trámites de la entidad en el periodo
        $tramites = \App\Models\Tramite::where('entidad_id', $actor->ref_id)
            ->where('estado', 'APROBADO')
            ->whereNotNull('fecha_fin')
            ->where('fecha_inicio', '>=', $periodo['fecha_desde'])
            ->where('fecha_inicio', '<=', $periodo['fecha_hasta'])
            ->get();

        if ($tramites->isEmpty()) {
            return 0.0;
        }

        $tiemposDespacho = $tramites->map(function ($tramite) {
            return ($tramite->fecha_fin->timestamp - $tramite->fecha_inicio->timestamp) / 3600;
        });

        return round($tiemposDespacho->avg(), 2);
    }

    /**
     * Verifica si un valor cumple con el SLA basándose en el comparador
     *
     * @param float $valor
     * @param float $umbral
     * @param string $comparador
     * @return bool
     */
    private function verificarCumplimientoSLA(float $valor, float $umbral, string $comparador): bool
    {
        return match ($comparador) {
            '<' => $valor < $umbral,
            '<=' => $valor <= $umbral,
            '>' => $valor > $umbral,
            '>=' => $valor >= $umbral,
            '=' => $valor === $umbral,
            default => false,
        };
    }

    /**
     * Calcula la penalidad por incumplimiento de SLA
     * Penalidad = porcentaje de exceso sobre el umbral
     *
     * @param float $valor
     * @param float $umbral
     * @param string $comparador
     * @return float
     */
    private function calcularPenalidad(float $valor, float $umbral, string $comparador): float
    {
        // Si cumple el SLA, no hay penalidad
        if ($this->verificarCumplimientoSLA($valor, $umbral, $comparador)) {
            return 0.0;
        }

        // Calcular porcentaje de exceso
        if ($umbral === 0.0) {
            return 0.0;
        }

        $exceso = abs($valor - $umbral);
        $pctExceso = ($exceso / $umbral) * 100;

        return min($pctExceso, 100.0); // Máximo 100% de penalidad
    }

    /**
     * Calcula los KPIs del reporte R12
     *
     * @param Collection $cumplimientosPorActor
     * @return array<string, mixed>
     */
    private function calculateR12Kpis(Collection $cumplimientosPorActor): array
    {
        if ($cumplimientosPorActor->isEmpty()) {
            return $this->calculateR12KpisVacios();
        }

        $totalActores = $cumplimientosPorActor->count();
        $actoresExcelentes = $cumplimientosPorActor->where('estado', 'EXCELENTE')->count();
        $actoresBuenos = $cumplimientosPorActor->where('estado', 'BUENO')->count();
        $actoresRegulares = $cumplimientosPorActor->where('estado', 'REGULAR')->count();
        $actoresCríticos = $cumplimientosPorActor->where('estado', 'CRÍTICO')->count();

        $pctCumplimientoPromedio = $cumplimientosPorActor->avg('pct_cumplimiento');
        $penalidades = $cumplimientosPorActor->sum('penalidades_totales');
        $totalIncumplimientos = $cumplimientosPorActor->sum('slas_incumplidos');

        return [
            'total_actores' => $totalActores,
            'actores_excelentes' => $actoresExcelentes,
            'actores_buenos' => $actoresBuenos,
            'actores_regulares' => $actoresRegulares,
            'actores_críticos' => $actoresCríticos,
            'pct_cumplimiento_promedio' => round($pctCumplimientoPromedio, 2),
            'penalidades_totales' => round($penalidades, 2),
            'total_incumplimientos' => $totalIncumplimientos,
            'pct_actores_excelentes' => $totalActores > 0 ? round(($actoresExcelentes / $totalActores) * 100, 2) : 0.0,
            'pct_actores_críticos' => $totalActores > 0 ? round(($actoresCríticos / $totalActores) * 100, 2) : 0.0,
        ];
    }

    /**
     * Retorna estructura de KPIs vacía cuando no hay datos
     *
     * @return array<string, mixed>
     */
    private function calculateR12KpisVacios(): array
    {
        return [
            'total_actores' => 0,
            'actores_excelentes' => 0,
            'actores_buenos' => 0,
            'actores_regulares' => 0,
            'actores_críticos' => 0,
            'pct_cumplimiento_promedio' => 0.0,
            'penalidades_totales' => 0.0,
            'total_incumplimientos' => 0,
            'pct_actores_excelentes' => 0.0,
            'pct_actores_críticos' => 0.0,
        ];
    }
}