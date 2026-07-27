<x-filament-panels::page>
@php
    $data            = $this->getData();
    $grandTotal      = array_sum(array_column($data, 'total_value'));
    $grandCommission = array_sum(array_column($data, 'total_commission'));
    $formattedDate   = \Carbon\Carbon::parse($this->selectedDate ?: today())
                         ->translatedFormat('l, d \d\e F \d\e Y');
@endphp
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4 no-print">
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Data:</label>
            <input type="date" wire:model.live="selectedDate"
                class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $formattedDate }}</span>
        </div>
        <button onclick="printPagamentos()"
            class="inline-flex items-center gap-2 bg-amber-700 hover:bg-amber-800 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            🖨️ Imprimir / PDF
        </button>
    </div>

    @forelse ($data as $prof)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden print-card">
        <div class="flex items-start justify-between px-6 py-4 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-800">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $prof['name'] }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $prof['count'] }} {{ $prof['count'] === 1 ? 'marcação' : 'marcações' }} &middot; confirmadas/concluídas
                </p>
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
                        <th class="text-left pb-2 pr-3 font-medium">Hora</th>
                        <th class="text-left pb-2 pr-3 font-medium">Cliente</th>
                        <th class="text-left pb-2 pr-3 font-medium">Serviço</th>
                        <th class="text-right pb-2 pr-3 font-medium">Valor</th>
                        <th class="text-right pb-2 pr-3 font-medium">%</th>
                        <th class="text-right pb-2 font-medium">Comissão</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @foreach ($prof['breakdown'] as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="py-2 pr-3 font-mono text-gray-600 dark:text-gray-400">{{ \Carbon\Carbon::parse($row['time'])->format('H:i') }}</td>
                        <td class="py-2 pr-3 text-gray-700 dark:text-gray-300">{{ $row['client'] }}</td>
                        <td class="py-2 pr-3 text-gray-900 dark:text-white">{{ $row['service'] }}</td>
                        <td class="py-2 pr-3 text-right text-gray-700 dark:text-gray-300">€ {{ number_format($row['price'], 2, ',', '.') }}</td>
                        <td class="py-2 pr-3 text-right">
                            <span class="inline-block bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 text-xs font-medium px-2 py-0.5 rounded-full">{{ $row['pct'] }}%</span>
                        </td>
                        <td class="py-2 text-right font-semibold text-green-700 dark:text-green-400">€ {{ number_format($row['commission'], 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200 dark:border-gray-600">
                        <td colspan="3" class="pt-3 font-semibold text-gray-600 dark:text-gray-400">Total ({{ $prof['count'] }} marcações)</td>
                        <td class="pt-3 text-right font-semibold text-gray-800 dark:text-gray-200">€ {{ number_format($prof['total_value'], 2, ',', '.') }}</td>
                        <td></td>
                        <td class="pt-3 text-right font-bold text-base text-green-700 dark:text-green-400">€ {{ number_format($prof['total_commission'], 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @empty
    <div class="text-center py-16 text-gray-400 dark:text-gray-500">
        <div class="text-5xl mb-4">💶</div>
        <p class="text-lg font-medium">Sem marcações confirmadas para {{ $formattedDate }}</p>
    </div>
    @endforelse

    @if (!empty($data))
    <div class="bg-gray-900 dark:bg-gray-700 text-white rounded-xl px-6 py-5 no-print">
        <div class="flex flex-wrap justify-between items-center gap-4">
            <div>
                <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Resumo do dia</div>
                <div class="text-gray-300">Total serviços: <span class="font-semibold text-white ml-1">€ {{ number_format($grandTotal, 2, ',', '.') }}</span></div>
            </div>
            <div class="text-right">
                <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total a pagar à equipa</div>
                <div class="text-3xl font-bold text-green-400">€ {{ number_format($grandCommission, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function printPagamentos() {
    const date = document.querySelector('input[type=date]')?.value || '';
    const cards = document.querySelectorAll('.print-card');
    let html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>Pagamentos Diários - ${date}</title>
    <style>
        body{font-family:Arial,sans-serif;font-size:12px;color:#111;margin:24px}
        h1{font-size:18px;margin-bottom:4px} .date{color:#666;margin-bottom:20px}
        .card{border:1px solid #ddd;border-radius:6px;margin-bottom:16px;page-break-inside:avoid}
        .card-header{background:#fef3c7;padding:10px 14px;display:flex;justify-content:space-between;border-radius:6px 6px 0 0}
        .card-header h2{font-size:14px;margin:0 0 2px} .sub{font-size:11px;color:#666}
        .totals{text-align:right} .total-svc{font-size:11px;color:#444}
        .a-receber{font-size:17px;font-weight:bold;color:#166534}
        table{width:100%;border-collapse:collapse;margin:0}
        th{font-size:10px;text-transform:uppercase;color:#888;border-bottom:1px solid #eee;padding:6px 8px;text-align:left}
        td{padding:5px 8px;border-bottom:1px solid #f5f5f5}
        .r{text-align:right} tfoot td{font-weight:bold;border-top:2px solid #ddd;border-bottom:none}
        .summary{background:#1f2937;color:white;border-radius:6px;padding:12px 16px;display:flex;justify-content:space-between;margin-top:16px}
        .slabel{font-size:10px;color:#9ca3af;text-transform:uppercase;margin-bottom:4px}
        .sval{font-size:20px;font-weight:bold;color:#4ade80}
    </style></head><body>`;
    html += `<h1>Pagamentos Diários</h1><div class="date">${date}</div>`;
    cards.forEach(card => {
        const name = card.querySelector('h3')?.innerText||'';
        const sub  = card.querySelector('p')?.innerText||'';
        const tval = card.querySelector('.text-base.font-semibold')?.innerText||'';
        const trec = card.querySelector('.text-2xl')?.innerText||'';
        const rows = card.querySelectorAll('tbody tr');
        const foot = card.querySelectorAll('tfoot td');
        html += `<div class="card"><div class="card-header">
            <div><h2>${name}</h2><div class="sub">${sub}</div></div>
            <div class="totals"><div class="total-svc">Total serviços: ${tval}</div><div class="a-receber">${trec}</div></div>
        </div><table><thead><tr>
            <th>Hora</th><th>Cliente</th><th>Serviço</th>
            <th class="r">Valor</th><th class="r">%</th><th class="r">Comissão</th>
        </tr></thead><tbody>`;
        rows.forEach(row => {
            const c = row.querySelectorAll('td');
            if(c.length>=6) html+=`<tr><td>${c[0].innerText}</td><td>${c[1].innerText}</td><td>${c[2].innerText}</td><td class="r">${c[3].innerText}</td><td class="r">${c[4].innerText}</td><td class="r">${c[5].innerText}</td></tr>`;
        });
        if(foot.length>=4) html+=`<tr><td colspan="3">${foot[0].innerText}</td><td class="r">${foot[1].innerText}</td><td></td><td class="r">${foot[3].innerText}</td></tr>`;
        html += `</tbody></table></div>`;
    });
    const gt = document.querySelector('.no-print .font-semibold.text-white')?.innerText||'';
    const gc = document.querySelector('.no-print .text-3xl')?.innerText||'';
    if(gt) html+=`<div class="summary"><div><div class="slabel">Total serviços</div><div>${gt}</div></div><div style="text-align:right"><div class="slabel">Total a pagar à equipa</div><div class="sval">${gc}</div></div></div>`;
    html+=`</body></html>`;
    const w=window.open('','_blank');
    w.document.write(html);w.document.close();w.focus();
    setTimeout(()=>w.print(),600);
}
</script>
</x-filament-panels::page>