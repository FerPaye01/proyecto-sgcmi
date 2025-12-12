<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Mostrar el nuevo dashboard con todos los reportes
     */
    public function index()
    {
        // Si no está autenticado, redirigir a login
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        return view('dashboard-new');
    }

    /**
     * Obtener datos de un reporte específico
     */
    public function getReport(Request $request, string $reportCode)
    {
        try {
            $filters = $request->query();
            $user = auth()->user();

            // Validar que el usuario esté autenticado
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Usuario no autenticado',
                ], 401);
            }

            // Mapeo de códigos de reporte a rutas
            $reportRoutes = [
                'r1' => 'reports.r1',
                'r3' => 'reports.r3',
                'r4' => 'reports.r4',
                'r5' => 'reports.r5',
                'r6' => 'reports.r6',
                'r7' => 'reports.r7',
                'r8' => 'reports.r8',
                'r9' => 'reports.r9',
                'r10' => 'reports.r10',
                'r11' => 'reports.r11',
                'r12' => 'reports.r12',
            ];

            if (!isset($reportRoutes[$reportCode])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Reporte no encontrado',
                ], 404);
            }

            // Crear una instancia del ReportController con las dependencias necesarias
            $reportService = app(\App\Services\ReportService::class);
            $reportController = new \App\Http\Controllers\ReportController($reportService);

            // Crear un nuevo request con los filtros y el usuario autenticado
            $newRequest = Request::create(
                route($reportRoutes[$reportCode]),
                'GET',
                $filters
            );
            
            // Establecer el usuario en el request
            $newRequest->setUserResolver(function () use ($user) {
                return $user;
            });

            // Llamar al método correspondiente
            $method = $reportCode;
            $response = $reportController->$method($newRequest);

            // Si es una vista, renderizarla
            if ($response instanceof View) {
                $html = $response->render();
                return response()->json([
                    'success' => true,
                    'html' => $html,
                ]);
            }

            // Si es una respuesta JSON, devolverla directamente
            return $response;

        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Database error in getReport: ' . $e->getMessage(), [
                'report_code' => $reportCode,
                'filters' => $request->query(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error de base de datos. Por favor, verifica los filtros e intenta nuevamente.',
            ], 500);

        } catch (\Exception $e) {
            \Log::error('Error in getReport: ' . $e->getMessage(), [
                'report_code' => $reportCode,
                'filters' => $request->query(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error al generar el reporte: ' . $e->getMessage(),
            ], 500);
        }
    }
}
