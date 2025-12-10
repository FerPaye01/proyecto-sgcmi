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
        $filters = $request->query();
        $user = auth()->user();

        // Mapeo de códigos de reporte a métodos del controlador de reportes
        $reportMethods = [
            'r1' => 'r1',
            'r3' => 'r3',
            'r4' => 'r4',
            'r5' => 'r5',
            'r6' => 'r6',
            'r7' => 'r7',
            'r8' => 'r8',
            'r9' => 'r9',
            'r10' => 'r10',
            'r11' => 'r11',
            'r12' => 'r12',
        ];

        if (!isset($reportMethods[$reportCode])) {
            return response()->json(['error' => 'Reporte no encontrado'], 404);
        }

        try {
            $reportController = new \App\Http\Controllers\ReportController();
            $method = $reportMethods[$reportCode];

            // Crear un request con los filtros
            $request = Request::create(
                route("reports.{$reportCode}"),
                'GET',
                $filters
            );

            // Llamar al método del controlador
            $response = $reportController->$method($request);

            // Si es una vista, renderizarla
            if ($response instanceof View) {
                return response()->json([
                    'html' => $response->render(),
                    'success' => true,
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar el reporte: ' . $e->getMessage(),
            ], 500);
        }
    }
}
