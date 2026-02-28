<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
    .head { margin-bottom: 14px; border-bottom: 2px solid #0f172a; padding-bottom: 8px; }
    .title { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
    .sub { font-size: 10px; color: #334155; }
    .meta { margin-top: 8px; font-size: 10px; }
    .chip { display: inline-block; padding: 2px 8px; border-radius: 12px; background: #e2e8f0; margin-right: 4px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #cbd5e1; padding: 6px; }
    th { background: #0f172a; color: #fff; font-size: 10px; }
    tr:nth-child(even) td { background: #f8fafc; }
    .team-cell { white-space: nowrap; }
    .team-logo { width: 16px; height: 16px; border-radius: 2px; vertical-align: middle; margin-right: 5px; }
  </style>
</head>
<body>
  <div class="head">
    <div class="title">{{ $title }}</div>
    <div class="sub">{{ $campeonato->nombre }} ({{ $campeonato->codigo }})</div>
    <div class="meta">
      Creador: <b>{{ $creator }}</b> |
      Rango: <b>{{ $fechaRango }}</b> |
      Generado: <b>{{ $generatedAt }}</b>
      @if(!empty($fase))
        | Fase: <b>{{ $fase->nombre }}</b>
      @endif
    </div>
  </div>

  @if(!empty($extra))
    <div>
      @foreach($extra as $k => $v)
        <span class="chip">{{ strtoupper((string)$k) }}: {{ $v }}</span>
      @endforeach
    </div>
  @endif

  <table>
    <thead>
      <tr>
        @foreach($headers as $h)
          <th>{{ $h }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $row)
        <tr>
          @foreach($row as $cell)
            <td>
              @if(is_array($cell))
                <span class="team-cell">
                  @if(!empty($cell['image']))
                    <img class="team-logo" src="{{ $cell['image'] }}" alt="">
                  @endif
                  {{ $cell['text'] ?? '' }}
                </span>
              @else
                {{ $cell }}
              @endif
            </td>
          @endforeach
        </tr>
      @empty
        <tr>
          <td colspan="{{ count($headers) }}" style="text-align:center;">Sin datos para este reporte</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
