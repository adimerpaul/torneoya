<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\PedidoDetalle;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Visita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PedidoController extends Controller {
    private array $estadosEditables = ['Creado', 'Pendiente'];

    function recuperarPedido(Request $request) {
        $pedido = Pedido::with('detalles.producto')
            ->where('id', $request->id)
            ->first();
        return response()->json($pedido);
    }

    public function misPedidos(Request $request)
    {
        $user = $request->user();
        $fecha = $request->input('fecha', now()->toDateString());

        $items = Pedido::query()
            ->with(['detalles.producto', 'cliente:id,nombre,codcli,ci', 'user:id,name,role'])
            ->where('tipo_pedido', 'REALIZAR_PEDIDO')
            ->where('user_id', $user->id)
            ->whereDate('fecha', $fecha)
            ->orderBy('hora')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $items,
            'stats' => [
                'total' => $items->count(),
                'creado' => $items->where('estado', 'Creado')->count(),
                'pendiente' => $items->where('estado', 'Pendiente')->count(),
                'enviado' => $items->where('estado', 'Enviado')->count(),
            ],
        ]);
    }

    public function enviar(Request $request, Pedido $pedido)
    {
        $this->authorizePedidoOwnerOrAdmin($request, $pedido);

        if (!$this->isEditable($pedido)) {
            return response()->json(['message' => 'El pedido ya fue enviado y no puede modificarse.'], 422);
        }

        $pedido->estado = 'Enviado';
        $pedido->save();

        return response()->json($pedido->load(['detalles.producto', 'cliente', 'user']));
    }

    public function enviarMisPedidos(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'fecha' => 'nullable|date',
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:pedidos,id',
        ]);

        $fecha = $data['fecha'] ?? now()->toDateString();
        $ids = $data['ids'] ?? [];

        $query = Pedido::query()
            ->where('tipo_pedido', 'REALIZAR_PEDIDO')
            ->where('user_id', $user->id)
            ->whereIn('estado', $this->estadosEditables);

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        } else {
            $query->whereDate('fecha', $fecha);
        }

        $items = $query->get();
        if ($items->isEmpty()) {
            return response()->json(['message' => 'No hay pedidos pendientes para enviar', 'sent' => 0], 200);
        }

        $updated = Pedido::query()
            ->whereIn('id', $items->pluck('id')->all())
            ->update(['estado' => 'Enviado']);

        return response()->json([
            'message' => 'Pedidos enviados correctamente',
            'sent' => $updated,
        ], 200);
    }

    public function update(Request $request, Pedido $pedido) {
        $user = $request->user();
        $isAdmin = strtoupper((string) ($user->role ?? '')) === 'ADMIN';
        $this->authorizePedidoOwnerOrAdmin($request, $pedido);

        $data = $request->validate([
            'estado' => ['sometimes', Rule::in(['Creado', 'Pendiente', 'Enviado', 'Aceptado', 'Anulado'])],
            'tipo_pago' => 'sometimes|nullable|string|in:Contado,QR,Credito,Boleta anterior',
            'facturado' => 'sometimes|nullable|boolean',
            'fecha' => 'sometimes|nullable|date',
            'hora' => 'sometimes|nullable|string|max:50',
            'observaciones' => 'sometimes|nullable|string|max:600',
            'comentario_visita' => 'sometimes|nullable|string|max:600',
            'productos' => 'sometimes|array',
            'productos.*.producto_id' => 'required_with:productos|integer|exists:productos,id',
            'productos.*.cantidad' => 'required_with:productos|numeric|min:0.01',
            'productos.*.precio' => 'required_with:productos|numeric|min:0',
            'productos.*.observacion' => 'nullable|string|max:600',
            'productos.*.detalle_extra' => 'nullable|array',
        ]);

        $estadoDestino = $data['estado'] ?? null;
        $camposEdicion = ['tipo_pago', 'facturado', 'fecha', 'hora', 'observaciones', 'comentario_visita', 'productos'];
        $intentandoEditar = collect($camposEdicion)->contains(fn ($k) => array_key_exists($k, $data));

        if ($estadoDestino !== null) {
            if (in_array($estadoDestino, ['Aceptado', 'Anulado'], true) && !$isAdmin) {
                return response()->json(['message' => 'No autorizado para cambiar a ese estado'], 403);
            }

            if ($estadoDestino === 'Enviado' && !$this->isEditable($pedido)) {
                return response()->json(['message' => 'El pedido ya fue enviado y no puede reenviarse'], 422);
            }
        }

        if ($intentandoEditar && !$this->isEditable($pedido)) {
            return response()->json(['message' => 'El pedido enviado no puede modificarse'], 422);
        }

        DB::beginTransaction();
        try {
            if ($intentandoEditar) {
                $updatePayload = array_intersect_key($data, array_flip(['tipo_pago', 'facturado', 'fecha', 'hora', 'observaciones', 'comentario_visita']));
                if (!empty($updatePayload)) {
                    $pedido->update($updatePayload);
                }

                if (array_key_exists('productos', $data)) {
                    [$total, $contiene] = $this->syncDetalles($pedido, $data['productos'] ?? []);
                    $pedido->update([
                        'total' => $total,
                        'contiene_normal' => $contiene['normal'],
                        'contiene_res' => $contiene['res'],
                        'contiene_cerdo' => $contiene['cerdo'],
                        'contiene_pollo' => $contiene['pollo'],
                    ]);
                }
            }

            if ($estadoDestino !== null) {
                $pedido->estado = $estadoDestino;
                $pedido->save();
            }

            DB::commit();
            return response()->json($pedido->load(['detalles.producto', 'cliente', 'user']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function index(Request $request) {
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;
        $user = $request->user();

        return Pedido::with(['detalles.producto', 'user', 'cliente'])
            ->when($fechaInicio && $fechaFin, function ($q) use ($fechaInicio, $fechaFin) {
                $q->where('fecha', '>=', $fechaInicio)
                    ->where('fecha', '<=', $fechaFin);
            })
            ->when($request->filled('tipo_pedido'), function ($q) use ($request) {
                $q->where('tipo_pedido', $request->tipo_pedido);
            })
            ->when($request->filled('cliente_id'), function ($q) use ($request) {
                $q->where('cliente_id', $request->cliente_id);
            })
            ->when(($request->solo_mios ?? false) && $user, function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->when($request->filled('user'), function ($q) use ($request) {
                $q->where('user_id', $request->user);
            })
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->get();
    }

    public function store(Request $request) {
        DB::beginTransaction();
        try {
            $user = $request->user();
            $tipoPedido = strtoupper((string)($request->tipo_pedido ?? 'REALIZAR_PEDIDO'));
            $isPedido = $tipoPedido === 'REALIZAR_PEDIDO';

            $rules = [
                'tipo_pedido' => 'nullable|string|in:REALIZAR_PEDIDO,RETORNAR,NO_PEDIDO,GENERAR_RUTA',
                'tipo_pago' => 'nullable|string|in:Contado,QR,Credito,Boleta anterior',
                'facturado' => 'nullable|boolean',
                'fecha' => 'nullable|date',
                'hora' => 'nullable|string|max:50',
                'cliente_id' => 'nullable|integer|exists:clientes,id',
                'observaciones' => 'nullable|string|max:600',
                'comentario_visita' => 'nullable|string|max:600',
                'productos' => 'nullable|array',
                'productos.*.producto_id' => 'required_with:productos|integer|exists:productos,id',
                'productos.*.cantidad' => 'required_with:productos|numeric|min:0.01',
                'productos.*.precio' => 'required_with:productos|numeric|min:0',
                'productos.*.observacion' => 'nullable|string|max:600',
                'productos.*.detalle_extra' => 'nullable|array',
            ];
            $data = $request->validate($rules);

            if ($isPedido && empty($data['productos'])) {
                return response()->json(['message' => 'Debe agregar al menos un producto para realizar pedido'], 422);
            }

            if (!$isPedido) {
                $visita = $this->registrarVisita(
                    userId: (int) $user->id,
                    clienteId: isset($data['cliente_id']) ? (int) $data['cliente_id'] : null,
                    tipoVisita: $tipoPedido,
                    comentario: $data['comentario_visita'] ?? ($data['observaciones'] ?? null)
                );

                DB::commit();
                return response()->json([
                    'message' => 'Accion registrada',
                    'visita' => $visita->load('cliente:id,nombre,codcli'),
                ], 201);
            }

            $productos = $data['productos'] ?? [];
            $productoTipos = Producto::query()
                ->whereIn('id', collect($productos)->pluck('producto_id')->values()->all())
                ->pluck('tipo', 'id');

            $contiene = [
                'normal' => false,
                'res' => false,
                'cerdo' => false,
                'pollo' => false,
            ];
            foreach ($productos as $item) {
                $tipo = strtoupper((string) ($productoTipos[$item['producto_id']] ?? 'NORMAL'));
                if ($tipo === 'RES') $contiene['res'] = true;
                elseif ($tipo === 'CERDO') $contiene['cerdo'] = true;
                elseif ($tipo === 'POLLO') $contiene['pollo'] = true;
                else $contiene['normal'] = true;
            }

            $pedido = Pedido::create([
                'user_id' => $user->id,
                'cliente_id' => $data['cliente_id'] ?? null,
                'fecha' => $data['fecha'] ?? now()->format('Y-m-d'),
                'hora' => $data['hora'] ?? null,
                'estado' => $isPedido ? 'Creado' : 'Pendiente',
                'tipo_pago' => $data['tipo_pago'] ?? null,
                'facturado' => (bool) ($data['facturado'] ?? false),
                'tipo_pedido' => $tipoPedido,
                'contiene_normal' => $contiene['normal'],
                'contiene_res' => $contiene['res'],
                'contiene_cerdo' => $contiene['cerdo'],
                'contiene_pollo' => $contiene['pollo'],
                'total' => 0,
                'observaciones' => $data['observaciones'] ?? null,
                'comentario_visita' => $data['comentario_visita'] ?? null,
            ]);

            $total = 0;
            foreach ($productos as $item) {
                $cantidad = (float)$item['cantidad'];
                $precio = (float)$item['precio'];
                $subtotal = $precio * $cantidad;
                if ($cantidad > 0) {
                    $pedido->detalles()->create([
                        'producto_id' => $item['producto_id'],
                        'cantidad' => $cantidad,
                        'precio' => $precio,
                        'total' => $subtotal,
                        'observacion_detalle' => $item['observacion'] ?? null,
                        'detalle_extra' => $item['detalle_extra'] ?? null,
                    ]);
                    $total += $subtotal;
                }
            }

            $pedido->update(['total' => $total]);
            $this->registrarVisita(
                userId: (int) $user->id,
                clienteId: isset($data['cliente_id']) ? (int) $data['cliente_id'] : null,
                tipoVisita: $tipoPedido,
                comentario: $data['comentario_visita'] ?? ($data['observaciones'] ?? null)
            );

            DB::commit();
            return response()->json($pedido->load(['detalles.producto', 'cliente', 'user']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function syncDetalles(Pedido $pedido, array $productos): array
    {
        $productoTipos = Producto::query()
            ->whereIn('id', collect($productos)->pluck('producto_id')->values()->all())
            ->pluck('tipo', 'id');

        $contiene = [
            'normal' => false,
            'res' => false,
            'cerdo' => false,
            'pollo' => false,
        ];

        $pedido->detalles()->delete();
        $total = 0.0;

        foreach ($productos as $item) {
            $cantidad = (float) ($item['cantidad'] ?? 0);
            $precio = (float) ($item['precio'] ?? 0);
            if ($cantidad <= 0) continue;

            $tipo = strtoupper((string) ($productoTipos[$item['producto_id']] ?? 'NORMAL'));
            if ($tipo === 'RES') $contiene['res'] = true;
            elseif ($tipo === 'CERDO') $contiene['cerdo'] = true;
            elseif ($tipo === 'POLLO') $contiene['pollo'] = true;
            else $contiene['normal'] = true;

            $subtotal = $precio * $cantidad;
            $pedido->detalles()->create([
                'producto_id' => $item['producto_id'],
                'cantidad' => $cantidad,
                'precio' => $precio,
                'total' => $subtotal,
                'observacion_detalle' => $item['observacion'] ?? null,
                'detalle_extra' => $item['detalle_extra'] ?? null,
            ]);
            $total += $subtotal;
        }

        return [$total, $contiene];
    }

    private function authorizePedidoOwnerOrAdmin(Request $request, Pedido $pedido): void
    {
        $user = $request->user();
        $isAdmin = strtoupper((string) ($user->role ?? '')) === 'ADMIN';
        $canTotales = method_exists($user, 'can') && $user->can('Mis pedidos totales');
        $isOwner = (int) ($pedido->user_id ?? 0) === (int) ($user->id ?? 0);

        abort_unless($isOwner || $isAdmin || $canTotales, 403, 'No autorizado');
    }

    private function isEditable(Pedido $pedido): bool
    {
        return in_array((string) $pedido->estado, $this->estadosEditables, true);
    }

    private function registrarVisita(int $userId, ?int $clienteId, string $tipoVisita, ?string $comentario): Visita
    {
        return Visita::create([
            'user_id' => $userId,
            'cliente_id' => $clienteId,
            'fecha' => now()->toDateString(),
            'hora' => now()->format('H:i:s'),
            'tipo_visita' => $tipoVisita,
            'comentario' => $comentario ? mb_substr(trim($comentario), 0, 600) : null,
        ]);
    }
}
