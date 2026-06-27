<x-filament-panels::page>
    <style>
        .fd-stats{
            display:grid;
            grid-template-columns:repeat(6, 1fr);
            gap:16px;
            margin-bottom:30px;
        }
        @media (max-width:1300px){.fd-stats{grid-template-columns:repeat(3, 1fr);}}
        @media (max-width:800px){.fd-stats{grid-template-columns:repeat(2, 1fr);}}
        @media (max-width:550px){.fd-stats{grid-template-columns:1fr;}}
        .fd-stat{
            border:1px solid #e3ddd4;
            border-radius:14px;
            padding:18px;
            background:#fff;
        }
        .fd-stat-label{font-size:12.5px;color:#9b8a7c;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;}
        .fd-stat-value{font-size:30px;font-weight:700;color:#2f2a28;}
        .fd-stat-sub{font-size:12px;color:#7a8fc4;margin-top:4px;}
        .fd-section-title{font-weight:700;color:#2f2a28;font-size:17px;margin:10px 0 12px;}
        .fd-tables{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:24px;
        }
        @media (max-width:900px){.fd-tables{grid-template-columns:1fr;}}
        .fd-table{
            border:1px solid #e3ddd4;
            border-radius:14px;
            overflow:hidden;
            background:#fff;
        }
        .fd-row{
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:10px 16px;
            border-bottom:1px solid #f0eae3;
            font-size:13.5px;
        }
        .fd-row:last-child{border-bottom:none;}
        .fd-row-header{
            background:#f6f0eb;
            font-weight:700;
            color:#6f5f54;
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:.5px;
        }
        .fd-row-name{font-weight:600;color:#2f2a28;}
        .fd-row-count{color:#9b8a7c;font-size:12px;}
        .fd-row-revenue{font-weight:700;color:#2f6b3f;}
        .fd-row-commission{font-size:11.5px;color:#8a5a1f;margin-top:2px;}
        .fd-empty{padding:16px;text-align:center;color:#9b8a7c;font-size:13px;}
    </style>

    <div class="fd-stats">
        <div class="fd-stat">
            <div class="fd-stat-label">Marcações Hoje</div>
            <div class="fd-stat-value">{{ $this->getTodayCount() }}</div>
        </div>
        <div class="fd-stat">
            <div class="fd-stat-label">Receita Hoje</div>
            <div class="fd-stat-value">€ {{ number_format($this->getTodayRevenue(), 2, ',', '.') }}</div>
        </div>
        <div class="fd-stat">
            <div class="fd-stat-label">Marcações da Semana</div>
            <div class="fd-stat-value">{{ $this->getWeekCount() }}</div>
            <div class="fd-stat-sub">{{ $this->getWeekRangeLabel() }}</div>
        </div>
        <div class="fd-stat">
            <div class="fd-stat-label">Receita da Semana</div>
            <div class="fd-stat-value">€ {{ number_format($this->getWeekRevenue(), 2, ',', '.') }}</div>
            <div class="fd-stat-sub">{{ $this->getWeekRangeLabel() }}</div>
        </div>
        <div class="fd-stat">
            <div class="fd-stat-label">Marcações do Mês</div>
            <div class="fd-stat-value">{{ $this->getMonthCount() }}</div>
            <div class="fd-stat-sub">{{ $this->getMonthRangeLabel() }}</div>
        </div>
        <div class="fd-stat">
            <div class="fd-stat-label">Receita do Mês</div>
            <div class="fd-stat-value">€ {{ number_format($this->getMonthRevenue(), 2, ',', '.') }}</div>
            <div class="fd-stat-sub">{{ $this->getMonthRangeLabel() }}</div>
        </div>
    </div>

    <div class="fd-section-title">Receita da Semana — Detalhe</div>
    <div class="fd-tables">
        <div>
            <div class="fd-table">
                <div class="fd-row fd-row-header">
                    <span>Profissional</span>
                    <span>Receita</span>
                </div>
                @forelse ($this->getRevenueByEmployee() as $row)
                    <div class="fd-row">
                        <div>
                            <div class="fd-row-name">{{ $row->name }}</div>
                            <div class="fd-row-count">{{ $row->count }} marcações</div>
                        </div>
                        <div style="text-align:right;">
                            <div class="fd-row-revenue">€ {{ number_format($row->revenue, 2, ',', '.') }}</div>
                            <div class="fd-row-commission">Comissão: € {{ number_format($row->commission, 2, ',', '.') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="fd-empty">Sem marcações esta semana.</div>
                @endforelse
            </div>
        </div>
        <div>
            <div class="fd-table">
                <div class="fd-row fd-row-header">
                    <span>Categoria</span>
                    <span>Receita</span>
                </div>
                @forelse ($this->getRevenueByCategory() as $row)
                    <div class="fd-row">
                        <div>
                            <div class="fd-row-name">{{ $row->name }}</div>
                            <div class="fd-row-count">{{ $row->count }} marcações</div>
                        </div>
                        <div class="fd-row-revenue">€ {{ number_format($row->revenue, 2, ',', '.') }}</div>
                    </div>
                @empty
                    <div class="fd-empty">Sem marcações esta semana.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
