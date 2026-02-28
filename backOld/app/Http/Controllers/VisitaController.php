<?php

namespace App\Http\Controllers;

use App\Models\Visita;
use Illuminate\Http\Request;

class VisitaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $soloMios = (bool) $request->input('solo_mios', true);
        $allDays = (bool) $request->input('all_days', true);
        $latestPerCliente = (bool) $request->input('latest_per_cliente', true);
        $fecha = $request->input('fecha', now()->toDateString());

        $query = Visita::query()
            ->with(['cliente:id,nombre,codcli,direccion,telefono,latitud,longitud'])
            ->when($soloMios && $user, function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->when(!$allDays, function ($q) use ($fecha) {
                $q->whereDate('fecha', $fecha);
            })
            ->when($request->filled('cliente_id'), function ($q) use ($request) {
                $q->where('cliente_id', (int) $request->input('cliente_id'));
            })
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('id');

        if ($request->boolean('paginate', false) && !$latestPerCliente) {
            return response()->json($query->paginate((int) $request->input('per_page', 100)));
        }

        $items = $query->get();

        if ($latestPerCliente) {
            $items = $items->unique('cliente_id')->values();
        }

        return response()->json($items);
    }
}
