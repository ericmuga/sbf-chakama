<?php

namespace App\Filament\Widgets\Projects;

use App\Models\Project;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CostBreakdownChart extends ChartWidget
{
    public ?Project $record = null;

    protected ?string $heading = 'Cost Source Breakdown';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        if (! $this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        $project = $this->record;

        $directCostsByType = DB::table('project_direct_costs')
            ->where('project_id', $project->id)
            ->where('status', 'posted')
            ->whereNull('deleted_at')
            ->selectRaw('cost_type, SUM(amount) as total')
            ->groupBy('cost_type')
            ->get();

        $purchaseOrdersTotal = (float) DB::table('purchase_headers')
            ->join('purchase_lines', 'purchase_headers.id', '=', 'purchase_lines.purchase_header_id')
            ->where('purchase_headers.project_id', $project->id)
            ->sum('purchase_lines.line_amount');

        $labels = [];
        $data = [];
        $colors = [];

        $typeColors = [
            'petty_cash' => 'rgba(59, 130, 246, 0.7)',
            'mpesa_payment' => 'rgba(34, 197, 94, 0.7)',
            'bank_transfer' => 'rgba(168, 85, 247, 0.7)',
            'cash_withdrawal' => 'rgba(251, 191, 36, 0.7)',
            'other' => 'rgba(156, 163, 175, 0.7)',
        ];

        foreach ($directCostsByType as $row) {
            $labels[] = ucwords(str_replace('_', ' ', $row->cost_type));
            $data[] = (float) $row->total;
            $colors[] = $typeColors[$row->cost_type] ?? 'rgba(156, 163, 175, 0.7)';
        }

        if ($purchaseOrdersTotal > 0) {
            $labels[] = 'Purchase Orders';
            $data[] = $purchaseOrdersTotal;
            $colors[] = 'rgba(251, 146, 60, 0.7)';
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
