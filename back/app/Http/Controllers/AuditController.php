<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = strtoupper((string) ($user?->role ?? ''));
        $isAdmin = in_array($role, ['ADMIN', 'ADMINISTRADOR'], true);
        $canAudit = $user?->can('Auditorias') ?? false;
        abort_unless($isAdmin || $canAudit, 403, 'No autorizado');

        $rows = DB::table('audits')
            ->orderByDesc('id')
            ->paginate((int) $request->query('per_page', 30));

        return response()->json($rows);
    }
}
