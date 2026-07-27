<x-filament-panels::page>
@php
    $data            = $this->getData();
    $grandTotal      = array_sum(array_column($data, 'total_value'));
    $grandCommission = array_sum(array_column($data, 'total_commission'));
    $formattedDate   = \Carbon\Carbon::parse($this->selectedDate ?: today())
                         ->translatedFormat('l, d \\de F \\de Y');
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4 no-print">
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Data:</label>
            <input type="date" wire:model.live="selectedDate"
                class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm">
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $formattedDate }}</span>
        </div>
        <button onclick="printPagamentos()"
            class="inline-flex items-center gap-2 bg-amber-700 hover:bg-amber-800 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            🖨️ Imprimir / PDF
        </button>
    </div>

    @forelse ($data as $prof)
    <div class="print-card bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="flex items-start justify-between px-6 py-4 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-800">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $prof['name'] }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $prof['count'] }} {{ $prof['count'] === 1 ? 'marcação' : 'marcações' }}</p>
            </div>
            <div class="text-right ml-4 flex-shrink-0">
                <div class="text-xs text-gray-400 mb-0.5">Total serviços</div>
                <div class="text-base font-semibold text-gray-700 dark:text-gray-200">€ {{ number_format($prof['total_value'], 2, ',', '.') }}</div>
                <div class="text-xs text-gray-400 mt-2 mb-0.5">A receber</div>
                <div class="text-2xl font-bold text-green-700 dark:text-green-400">€ {{ number_format($prof['total_commission'], 2, ',', '.') }}</div>
            </div>
        </div>
        <div class="px-6 py-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wide text-gray-400 border-b border-gray-100 dark:border-gray-700">
                        <th class="text-left pb-2 pr-3">Hora</th>
                        <th class="text-left pb-2 pr-3">Cliente</th>
                        <th class="text-left pb-2 pr-3">Serviço</th>
                        <th class="text-right pb-2 pr-3">Valor</th>
                        <th class="text-right pb-2 pr-3">%</th>
                        <th class="text-right pb-2">Comissão</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @foreach ($prof['breakdown'] as $row)
                    <tr>
                        <td class="py-2 pr-3 font-mono text-gray-600 dark:text-gray-400">{{ \Carbon\Carbon::parse($row['time'])->format('H:i') }}</td>
                        <td class="py-2 pr-3 text-gray-700 dark:text-gray-300">{{ $row['client'] }}</td>
                        <td class="py-2 pr-3 text-gray-900 dark:text-white">{{ $row['service'] }}</td>
                        <td class="py-2 pr-3 text-right text-gray-700">€ {{ number_format($row['price'], 2, ',', '.') }}</td>
                        <td class="py-2 pr-3 text-right"><span class="bg-amber-100 text-amber-800 text-xs px-2 py-0.5 rounded-full">{{ $row['pct'] }}%</span></td>
                        <td class="py-2 text-right font-semibold text-green-700 dark:text-green-400">€ {{ number_format($row['commission'], 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200 dark:border-gray-600">
                        <td colspan="3" class="pt-3 font-semibold text-gray-600">Total ({{ $prof['count'] }} marcações)</td>
                        <td class="pt-3 text-right font-semibold">€ {{ number_format($prof['total_value'], 2, ',', '.') }}</td>
                        <td></td>
                        <td class="pt-3 text-right font-bold text-green-700">€ {{ number_format($prof['total_commission'], 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @empty
    <div class="text-center py-16 text-gray-400">
        <p class="text-lg">Sem marcações confirmadas para {{ $formattedDate }}</p>
    </div>
    @endforelse

    @if (!empty($data))
    <div class="print-summary rounded-xl px-6 py-5 border-2 border-gray-200 bg-gray-50 dark:bg-gray-800">
        <div class="flex flex-wrap justify-between items-center gap-4">
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total serviços</div>
                <div class="font-semibold text-gray-700">€ {{ number_format($grandTotal, 2, ',', '.') }}</div>
            </div>
            <div class="text-right">
                <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total a pagar à equipa</div>
                <div class="text-3xl font-bold text-green-700">€ {{ number_format($grandCommission, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>
    @endif
</div>
</x-filament-panels::page>

<script>
function printPagamentos() {
    var dateInput = document.querySelector('input[type="date"]');
    var date = dateInput ? dateInput.value : '';

    var css = '';
    css += 'body{font-family:Arial,sans-serif;font-size:12px;color:#111;margin:20px}';
    css += 'h1{font-size:18px;margin:0 0 4px}';
    css += '.sub{color:#666;margin-bottom:20px;font-size:13px}';
    css += '.card{border:1px solid #ddd;border-radius:6px;margin-bottom:18px;overflow:hidden;page-break-inside:avoid}';
    css += '.ch{background:#fef3c7;padding:12px 16px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center}';
    css += '.ch h2{margin:0;font-size:14px}';
    css += '.ch .recv{font-size:20px;font-weight:bold;color:#166534}';
    css += '.ch .recv small{display:block;font-size:10px;font-weight:normal;color:#666}';
    css += 'table{width:100%;border-collapse:collapse}';
    css += 'th{background:#f9fafb;text-align:left;padding:7px 10px;font-size:10px;color:#666;border-bottom:1px solid #e5e7eb}';
    css += 'td{padding:6px 10px;border-bottom:1px solid #f0f0f0;font-size:11px}';
    css += 'tfoot td{border-top:2px solid #e5e7eb;border-bottom:none;font-weight:bold}';
    css += '.summary{background:#f3f4f6;border-radius:6px;padding:14px 18px;margin-top:18px;display:flex;justify-content:space-between}';
    css += '.summary .big{font-size:22px;font-weight:bold;color:#166534}';
    css += '.lbl{font-size:10px;color:#666;text-transform:uppercase}';

    var html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Pagamentos - ' + date + '</title>';
    html += '<style>' + css + '</style></head><body>';
    html += '<h1>Pagamentos Di\u00e1rios</h1><div class="sub">' + date + '</div>';

    var cards = document.querySelectorAll('.print-card');
    for (var i = 0; i < cards.length; i++) {
        var card = cards[i];
        var h3 = card.querySelector('h3');
        var name = h3 ? h3.innerText : '';
        var recvEl = card.querySelector('.text-2xl');
        var recv = recvEl ? recvEl.innerText : '';
        var svcEl = card.querySelector('.text-base.font-semibold');
        var svc = svcEl ? svcEl.innerText : '';

        html += '<div class="card">';
        html += '<div class="ch"><h2>' + name + '</h2>';
        html += '<div class="recv"><small>Serv: ' + svc + ' &nbsp;|&nbsp; A receber:</small>' + recv + '</div></div>';
        html += '<table><thead><tr>';
        html += '<th>Hora</th><th>Cliente</th><th>Servi\u00e7o</th><th>Valor</th><th>%</th><th>Comiss\u00e3o</th>';
        html += '</tr></thead><tbody>';

        var rows = card.querySelectorAll('tbody tr');
        for (var r = 0; r < rows.length; r++) {
            var cells = rows[r].querySelectorAll('td');
            html += '<tr>';
            for (var c = 0; c < cells.length; c++) {
                html += '<td>' + cells[c].innerText + '</td>';
            }
            html += '</tr>';
        }
        html += '</tbody>';

        var footCells = card.querySelectorAll('tfoot td');
        if (footCells.length) {
            html += '<tfoot><tr>';
            for (var f = 0; f < footCells.length; f++) {
                html += '<td>' + footCells[f].innerText + '</td>';
            }
            html += '</tr></tfoot>';
        }
        html += '</table></div>';
    }

    var sumEl = document.querySelector('.print-summary');
    if (sumEl) {
        var bigEl = sumEl.querySelector('.text-3xl');
        var svcTotEl = sumEl.querySelector('.font-semibold');
        html += '<div class="summary">';
        html += '<div><div class="lbl">Total servi\u00e7os</div><div>' + (svcTotEl ? svcTotEl.innerText : '') + '</div></div>';
        html += '<div style="text-align:right"><div class="lbl">Total a pagar \u00e0 equipa</div><div class="big">' + (bigEl ? bigEl.innerText : '') + '</div></div>';
        html += '</div>';
    }

    html += '</body></html>';

    var w = window.open('', '_blank');
    if (!w) { alert('Active os popups para este site e tente novamente.'); return; }
    w.document.write(html);
    w.document.close();
    w.focus();
    setTimeout(function() { w.print(); }, 700);
}
</script>
