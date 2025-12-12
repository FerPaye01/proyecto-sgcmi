<?php

declare(strict_types=1);

namespace App\Http\Controllers\Terrestre;

use App\Http\Controllers\Controller;
use App\Models\DigitalPass;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class DigitalPassController extends Controller
{
    /**
     * Display a listing of digital passes with filters
     */
    public function index(Request $request): View
    {
        $query = DigitalPass::with(['truck', 'creator']);

        // Apply filters
        if ($request->filled('pass_type')) {
            $query->where('pass_type', $request->pass_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('holder_name')) {
            $query->where('holder_name', 'ILIKE', '%' . $request->holder_name . '%');
        }

        if ($request->filled('holder_dni')) {
            $query->where('holder_dni', 'LIKE', '%' . $request->holder_dni . '%');
        }

        if ($request->filled('pass_code')) {
            $query->where('pass_code', 'LIKE', '%' . $request->pass_code . '%');
        }

        // Filter by validity
        if ($request->filled('validity')) {
            switch ($request->validity) {
                case 'valid':
                    $query->valid();
                    break;
                case 'expired':
                    $query->expired();
                    break;
                case 'active':
                    $query->active();
                    break;
            }
        }

        $digitalPasses = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('terrestre.digital-pass.index', compact('digitalPasses'));
    }

    /**
     * Show the form for creating a new digital pass
     */
    public function create(): View
    {
        $trucks = Truck::orderBy('placa')->get();
        
        return view('terrestre.digital-pass.generate', compact('trucks'));
    }

    /**
     * Generate a new digital pass with QR code
     */
    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pass_type' => 'required|in:PERSONAL,VEHICULAR',
            'holder_name' => 'required|string|max:255',
            'holder_dni' => 'required|string|max:20',
            'truck_id' => 'nullable|integer',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
        ]);
        
        // Validate truck exists if provided
        if (!empty($validated['truck_id'])) {
            $truck = Truck::find($validated['truck_id']);
            if (!$truck) {
                return back()->withErrors(['truck_id' => 'El vehículo seleccionado no existe.'])->withInput();
            }
        }

        // Validate truck_id is required for VEHICULAR passes
        if ($validated['pass_type'] === 'VEHICULAR' && empty($validated['truck_id'])) {
            return back()->withErrors(['truck_id' => 'El vehículo es requerido para pases vehiculares.'])->withInput();
        }

        // Validate truck_id is not provided for PERSONAL passes
        if ($validated['pass_type'] === 'PERSONAL') {
            $validated['truck_id'] = null;
        }

        $validated['status'] = 'ACTIVO';
        $validated['created_by'] = Auth::id();

        // The pass_code and qr_code will be generated automatically by the model's boot method
        $digitalPass = DigitalPass::create($validated);

        return redirect()
            ->route('digital-pass.show', $digitalPass)
            ->with('success', 'Pase digital generado exitosamente.');
    }

    /**
     * Display the specified digital pass with QR code
     */
    public function show(DigitalPass $digitalPass): View
    {
        $digitalPass->load(['truck', 'creator', 'accessPermits']);
        
        return view('terrestre.digital-pass.show', compact('digitalPass'));
    }

    /**
     * Show the form for validating a QR code
     */
    public function showValidateForm(): View
    {
        return view('terrestre.digital-pass.validate');
    }

    /**
     * Validate a QR code and check expiration
     */
    public function validatePass(Request $request)
    {
        $validated = $request->validate([
            'pass_code' => 'required|string',
        ]);

        $digitalPass = DigitalPass::where('pass_code', $validated['pass_code'])->first();

        if (!$digitalPass) {
            return response()->json([
                'valid' => false,
                'message' => 'Pase digital no encontrado.',
            ], 404);
        }

        $isValid = $digitalPass->isValid();
        $message = '';

        if ($digitalPass->status === 'REVOCADO') {
            $message = 'El pase digital ha sido revocado.';
        } elseif ($digitalPass->valid_until < now()) {
            $message = 'El pase digital ha expirado.';
        } elseif ($digitalPass->valid_from > now()) {
            $message = 'El pase digital aún no es válido.';
        } elseif ($isValid) {
            $message = 'Pase digital válido.';
        } else {
            $message = 'El pase digital no está activo.';
        }

        return response()->json([
            'valid' => $isValid,
            'message' => $message,
            'pass' => [
                'pass_code' => $digitalPass->pass_code,
                'pass_type' => $digitalPass->pass_type,
                'holder_name' => $digitalPass->holder_name,
                'holder_dni' => $digitalPass->holder_dni,
                'truck_placa' => $digitalPass->truck?->placa,
                'valid_from' => $digitalPass->valid_from->format('Y-m-d H:i'),
                'valid_until' => $digitalPass->valid_until->format('Y-m-d H:i'),
                'status' => $digitalPass->status,
            ],
        ]);
    }

    /**
     * Revoke a digital pass
     */
    public function revoke(DigitalPass $digitalPass): RedirectResponse
    {
        if ($digitalPass->status === 'REVOCADO') {
            return back()->with('warning', 'El pase digital ya está revocado.');
        }

        $digitalPass->revoke();

        return back()->with('success', 'Pase digital revocado exitosamente.');
    }
}
