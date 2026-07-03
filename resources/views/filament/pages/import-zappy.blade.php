<x-filament-panels::page>
<style>
    .zi-intro{background:#f5f0eb;border:1px solid #e8ddd6;border-radius:10px;padding:14px 18px;margin-bottom:20px;font-size:13.5px;color:#4a3d33;line-height:1.5;}
    .zi-upload{background:#fff;border:1px solid #e8e2db;border-radius:12px;padding:20px;margin-bottom:20px;}
    .zi-upload input[type=file]{display:block;margin:10px 0;}
    .zi-btn{padding:8px 18px;border-radius:8px;border:none;font-weight:600;font-size:13.5px;cursor:pointer;}
    .zi-btn-primary{background:#c9a07a;color:#fff;}
    .zi-btn-primary:hover{background:#b88d66;}
    .zi-btn-success{background:#2f6b3f;color:#fff;}
    .zi-btn-success:hover{background:#255530;}
    .zi-btn[disabled]{opacity:.5;cursor:not-allowed;}

    .zi-summary{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px;}
    .zi-stat{border-radius:10px;padding:8px 14px;font-size:13px;font-weight:600;min-width:110px;text-align:center;}
    .zi-stat small{display:block;font-weight:400;font-size:11px;opacity:.8;margin-top:2px;}
    .zi-stat-ok{background:#e8f3ea;color:#2f6b3f;}
    .zi-stat-dup{background:#eef1f5;color:#4a5568;}
    .zi-stat-warn{background:#fff8e6;color:#8a5c00;}
    .zi-stat-err{background:#fef2f2;color:#c0392b;}

    .zi-table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden;font-size:12.5px;}
    .zi-table th{background:#c9a07a;color:#fff;text-align:left;padding:8px 10px;font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;}
    .zi-table td{padding:7px 10px;border-bottom:1px solid #efe9e2;vertical-align:top;}
    .zi-badge{display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;}
    .zi-badge-nova{background:#e8f3ea;color:#2f6b3f;}
    .zi-badge-duplicada{background:#eef1f5;color:#4a5568;}
    .zi-badge-ignorada{background:#f3f0eb;color:#8a7a6a;}
    .zi-badge-erro{background:#fef2f2;color:#c0392b;}
    .zi-note{color:#8a7a6a;font-size:11.5px;}
</style>

<div class="zi-intro">
    Faz aqui o upload do ficheiro de marcações exportado do Zappy (relatório "reports"). O sistema mostra primeiro uma pré-visualização — nada é gravado até clicares em "Confirmar importação". As marcações que já existem na agenda (incluindo as que já vieram automaticamente da Odisseias) são sempre ignoradas, por isso é seguro repetir isto com exports cada vez mais recentes.
</div>

<div class="zi-upload">
    <label style="font-weight:600;font-size:13.5px;">Ficheiro do Zappy (.csv)</label>
    <input type="file" wire:model="file" accept=".csv">

    <div wire:loading wire:target="file" style="font-size:12.5px;color:#9b8a7c;">A carregar ficheiro…</div>
    @error('file') <div style="color:#c0392b;font-size:12.5px;margin-top:4px;">{{ $message }}</div> @enderror

    <div style="margin-top:14px;">
        <button type="button" class="zi-btn zi-btn-primary" wire:click="analisar" wire:loading.attr="disabled" wire:target="analisar">
            <span wire:loading.remove wire:target="analisar">Analisar ficheiro</span>
            <span wire:loading wire:target="analisar">A analisar…</span>
        </button>
    </div>
</div>

@if($summary)
    <div class="zi-summary">
        <div class="zi-stat zi-stat-ok">{{ $summary['novas'] }}<small>Novas (a criar)</small></div>
        <div class="zi-stat zi-stat-dup">{{ $summary['duplicadas'] }}<small>Já existentes</small></div>
        <div class="zi-stat zi-stat-ok">{{ $summary['clientes_novos'] }}<small>Clientes novos</small></div>
        <div class="zi-stat zi-stat-dup">{{ $summary['ignoradas_estado'] }}<small>Ignoradas (estado)</small></div>
        <div class="zi-stat zi-stat-warn">{{ $summary['sem_servico'] }}<small>Sem serviço mapeado</small></div>
        <div class="zi-stat zi-stat-warn">{{ $summary['sem_profissional'] }}<small>Sem profissional</small></div>
        @if(($summary['sem_posto'] ?? 0) > 0)
            <div class="zi-stat zi-stat-err">{{ $summary['sem_posto'] }}<small>Sem posto</small></div>
        @endif
        @if(($summary['erros'] ?? 0) > 0)
            <div class="zi-stat zi-stat-err">{{ $summary['erros'] }}<small>Erros</small></div>
        @endif
    </div>

    @if($summary['novas'] > 0 && $analyzedPath)
        <div style="margin-bottom:20px;">
            <button type="button" class="zi-btn zi-btn-success"
                wire:click="confirmarImportacao"
                wire:confirm="Vou criar {{ $summary['novas'] }} marcações novas e {{ $summary['clientes_novos'] }} clientes novos. Confirmas?"
                wire:loading.attr="disabled" wire:target="confirmarImportacao">
                <span wire:loading.remove wire:target="confirmarImportacao">Confirmar importação ({{ $summary['novas'] }} marcações)</span>
                <span wire:loading wire:target="confirmarImportacao">A importar…</span>
            </button>
        </div>
    @endif

    @if($previewRows)
        <table class="zi-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Serviço</th>
                    <th>Profissional</th>
                    <th>Data</th>
                    <th>Hora</th>
                    <th>Preço</th>
                    <th>Estado</th>
                    <th>Nota</th>
                </tr>
            </thead>
            <tbody>
                @foreach($previewRows as $row)
                    <tr>
                        <td>{{ $row['client'] }}</td>
                        <td>{{ $row['service'] }}</td>
                        <td>{{ $row['employee'] }}</td>
                        <td>{{ $row['date'] ?? '—' }}</td>
                        <td>{{ $row['time'] ?? '—' }}</td>
                        <td>{{ $row['price'] ?? '—' }}</td>
                        <td><span class="zi-badge zi-badge-{{ $row['status'] }}">{{ ucfirst($row['status']) }}</span></td>
                        <td class="zi-note">{{ $row['note'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endif
</x-filament-panels::page>
