<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $block['title'] }} - Reporte de obras</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; line-height: 1.4; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .muted { color: #6b7280; }
        .meta { display: flex; justify-content: space-between; margin: 10px 0 4px; font-size: 10px; color: #6b7280; }
        .divider { border-bottom: 2px solid #e5e7eb; margin: 8px 0 14px; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .summary td { border: 1px solid #e5e7eb; padding: 8px 10px; }
        .summary .label { color: #6b7280; font-size: 10px; }
        .summary .value { font-size: 15px; font-weight: 700; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #111827; color: #fff; text-align: left; padding: 6px 8px; font-size: 10px; }
        table.data td { border: 1px solid #e5e7eb; padding: 5px 8px; }
        table.data tr:nth-child(even) td { background: #f9fafb; }
        .footer { margin-top: 18px; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <h1>Reporte de obras y proyectos</h1>
    <div class="meta">
        <span>{{ $meta['municipality'] }}</span>
        <span>{{ $meta['period'] }}</span>
    </div>
    <div class="meta">
        <span>{{ $block['title'] }}</span>
        <span>Generado: {{ $meta['generated_at'] }}</span>
    </div>
    <div class="divider"></div>

    <table class="summary">
        <tr>
            @foreach ($block['summary'] as $item)
                <td>
                    <div class="label">{{ $item['label'] }}</div>
                    <div class="value">{{ $item['value'] }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    @if (! empty($block['rows']))
        <table class="data">
            <thead>
                <tr>
                    @foreach ($block['headers'] as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($block['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">No hay registros para esta métrica en el período consultado.</p>
    @endif

    <div class="footer">
        Sistema de Gestión Municipal · Módulo de seguimiento de obras y proyectos de infraestructura.
    </div>
</body>
</html>
