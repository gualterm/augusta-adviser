<x-filament-panels::page>
    <div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
        @foreach(['today'=>'Hoje','week'=>'Esta semana','month'=>'Este mês','all'=>'Sempre'] as $key=>$label)
            <button wire:click="setPeriodo('{{ $key }}')"
                style="padding:6px 18px;border-radius:6px;border:1px solid #d1d5db;cursor:pointer;
                       background:{{ $periodo===$key ? '#4f46e5' : '#fff' }};
                       color:{{ $periodo===$key ? '#fff' : '#374151' }};font-weight:500;">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:28px;">
        <div style="padding:14px 20px;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:15px;">Canal de aquisição × Marcações</div>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <thead style="background:#f9fafb;">
                <tr>
                    <th style="text-align:left;padding:10px 16px;color:#6b7280;">Canal</th>
                    <th style="text-align:right;padding:10px 16px;color:#6b7280;">Clientes</th>
                    <th style="text-align:right;padding:10px 16px;color:#6b7280;">Marcações</th>
                    <th style="text-align:right;padding:10px 16px;color:#6b7280;">Activas</th>
                    <th style="text-align:right;padding:10px 16px;color:#6b7280;">Canceladas</th>
                    <th style="text-align:right;padding:10px 16px;color:#6b7280;">Valor total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stats as $row)
                <tr style="border-top:1px solid #f3f4f6;">
                    <td style="padding:10px 16px;font-weight:500;">{{ $row->canal_icon }} {{ $row->canal_label }}</td>
                    <td style="text-align:right;padding:10px 16px;">{{ $row->total_clientes }}</td>
                    <td style="text-align:right;padding:10px 16px;font-weight:600;">{{ $row->total_marcacoes }}</td>
                    <td style="text-align:right;padding:10px 16px;color:#16a34a;">{{ $row->activas }}</td>
                    <td style="text-align:right;padding:10px 16px;color:#dc2626;">{{ $row->canceladas }}</td>
                    <td style="text-align:right;padding:10px 16px;">{{ $row->valor_total }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="padding:20px;text-align:center;color:#9ca3af;">Sem dados</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:15px;">Top 15 clientes por nº de marcações</div>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead style="background:#f9fafb;">
                <tr>
                    <th style="text-align:left;padding:10px 16px;color:#6b7280;">#</th>
                    <th style="text-align:left;padding:10px 16px;color:#6b7280;">Cliente</th>
                    <th style="text-align:left;padding:10px 16px;color:#6b7280;">Canal</th>
                    <th style="text-align:right;padding:10px 16px;color:#6b7280;">Marcações</th>
                    <th style="text-align:right;padding:10px 16px;color:#6b7280;">Canceladas</th>
                    <th style="text-align:left;padding:10px 16px;color:#6b7280;">1ª marcação</th>
                    <th style="text-align:left;padding:10px 16px;color:#6b7280;">Última</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topClientes as $i => $c)
                <tr style="border-top:1px solid #f3f4f6;">
                    <td style="padding:8px 16px;color:#9ca3af;">{{ $i+1 }}</td>
                    <td style="padding:8px 16px;font-weight:500;">{{ $c->name }}</td>
                    <td style="padding:8px 16px;">{{ $c->canal }}</td>
                    <td style="text-align:right;padding:8px 16px;font-weight:600;">{{ $c->total_marcacoes }}</td>
                    <td style="text-align:right;padding:8px 16px;color:#dc2626;">{{ $c->canceladas ?: '—' }}</td>
                    <td style="padding:8px 16px;color:#6b7280;">{{ $c->primeira_marcacao ? \Carbon\Carbon::parse($c->primeira_marcacao)->format('d/m/Y') : '—' }}</td>
                    <td style="padding:8px 16px;color:#6b7280;">{{ $c->ultima_marcacao ? \Carbon\Carbon::parse($c->ultima_marcacao)->format('d/m/Y') : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" style="padding:20px;text-align:center;color:#9ca3af;">Sem dados</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>