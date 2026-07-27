<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Pagamentos Diários — {{ $formattedDate }}</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:Arial,sans-serif;font-size:12px;color:#111;padding:20px;background:#fff}
        h1{font-size:20px;margin-bottom:4px;color:#1c1917}
        .date{color:#666;margin-bottom:22px;font-size:13px;text-transform:capitalize}
        .card{border:1px solid #e5e7eb;border-radius:6px;margin-bottom:18px;overflow:hidden;page-break-inside:avoid}
        .ch{background:#fef3c7;padding:12px 16px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:flex-start}
        .pname{font-size:14px;font-weight:bold;color:#1c1917}
        .psub{font-size:11px;color:#6b7280;margin-top:2px}
        .amt{text-align:right}
        .amt .lbl{font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px}
        .amt .svc{font-size:13px;font-weight:600;color:#374151;margin-bottom:4px}
        .amt .recv{font-size:20px;font-weight:bold;color:#166534}
        table{width:100%;border-collapse:collapse}
        th{background:#f9fafb;text-align:left;padding:7px 12px;font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #e5e7eb;font-weight:600}
        td{padding:7px 12px;border-bottom:1px solid #f3f4f6;font-size:11px}
        tr:last-child td{border-bottom:none}
        .tfoot td{border-top:2px solid #e5e7eb;border-bottom:none;font-weight:bold;background:#f9fafb}
        .tr{text-align:right}
        .badge{display:inline-block;background:#fef3c7;color:#92400e;font-size:10px;padding:1px 6px;border-radius:9999px;font-weight:600}
        .gr{color:#166534;font-weight:bold}
        .summary{background:#f3f4f6;border-radius:6px;padding:16px 20px;margin-top:22px;display:flex;justify-content:space-between;align-items:center}
        .slbl{font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
        .sval{font-size:14px;font-weight:600;color:#374151}
        .sbig{font-size:26px;font-weight:bold;color:#166534}
        @media print{body{padding:8px}}
    </style>
</head>
<body onload="window.print()">
    <h1>Pagamentos Diários</h1>
    <div class="date">{{ $formattedDate }}</div>

    @forelse ($result as $prof)
    <div class="card">
        <div class="ch">
            <div>
                <div class="pname">{{ $prof['name'] }}</div>
                <div class="psub">{{ $prof['count'] }} {{ $prof['count'] === 1 ? 'marcação' : 'marcações' }} confirmadas/concluídas</div>
            </div>
            <div class="amt">
                <div class="lbl">Total serviços</div>
                <div class="svc">€ {{ number_format($prof['total_value'], 2, ',', '.') }}</div>
                <div class="lbl">A receber</div>
                <div class="recv">€ {{ number_format($prof['total_commission'], 2, ',', '.') }}</div>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Hora</th><th>Cliente</th><th>Serviço</th>
                    <th class="tr">Valor</th><th class="tr">%</th><th class="tr">Comissão</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($prof['breakdown'] as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row['time'])->format('H:i') }}</td>
                    <td>{{ $row['client'] }}</td>
                    <td>{{ $row['service'] }}</td>
                    <td class="tr">€ {{ number_format($row['price'], 2, ',', '.') }}</td>
                    <td class="tr"><span class="badge">{{ $row['pct'] }}%</span></td>
                    <td class="tr gr">€ {{ number_format($row['commission'], 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot">
                    <td colspan="3">Total ({{ $prof['count'] }} marcações)</td>
                    <td class="tr">€ {{ number_format($prof['total_value'], 2, ',', '.') }}</td>
                    <td></td>
                    <td class="tr gr">€ {{ number_format($prof['total_commission'], 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @empty
    <p style="color:#999;text-align:center;padding:40px">Sem marcações confirmadas/concluídas para esta data.</p>
    @endforelse

    @if (!empty($result))
    <div class="summary">
        <div>
            <div class="slbl">Total serviços</div>
            <div class="sval">€ {{ number_format($grandTotal, 2, ',', '.') }}</div>
        </div>
        <div style="text-align:right">
            <div class="slbl">Total a pagar à equipa</div>
            <div class="sbig">€ {{ number_format($grandCommission, 2, ',', '.') }}</div>
        </div>
    </div>
    @endif
</body>
</html>