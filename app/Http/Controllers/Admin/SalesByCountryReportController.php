<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SalesByCountryReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalesByCountryReportController extends Controller
{
    public function index(Request $request, SalesByCountryReport $reports)
    {
        $filters = $this->filters($request);
        $report = $reports->build($filters['date_from'], $filters['date_to'], $filters['metric']);

        return view('admin.reports.sales-by-country', [
            'filters' => $filters,
            'metrics' => SalesByCountryReport::METRICS,
            'report' => $report,
        ]);
    }

    public function export(Request $request, SalesByCountryReport $reports)
    {
        $filters = $this->filters($request);
        $report = $reports->build($filters['date_from'], $filters['date_to'], $filters['metric']);
        $filename = 'fatturato-paesi-'.$report['date_from'].'-'.$report['date_to'].'-'.$report['metric'].'.csv';

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');
            $countries = array_keys($report['countries']);

            fputcsv($handle, array_merge(
                ['data'],
                array_values($report['countries']),
                ['totale_giorno']
            ), ';');

            foreach ($report['rows'] as $row) {
                $values = [$row['date']];

                foreach ($countries as $country) {
                    $values[] = $this->csvEuro($row['countries'][$country] ?? 0);
                }

                $values[] = $this->csvEuro($row['total_cents']);

                fputcsv($handle, $values, ';');
            }

            fputcsv($handle, array_merge(
                ['totale_periodo'],
                array_map(fn ($country) => $this->csvEuro($this->countryTotal($report, $country)), $countries),
                [$this->csvEuro($report['total_cents'])]
            ), ';');

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filters(Request $request): array
    {
        $defaults = [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
            'metric' => 'gross',
        ];

        if (! $request->hasAny(['date_from', 'date_to', 'metric'])) {
            return $defaults;
        }

        return array_merge($defaults, $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'metric' => ['required', Rule::in(array_keys(SalesByCountryReport::METRICS))],
        ]));
    }

    private function countryTotal(array $report, string $country): int
    {
        return collect($report['rows'])->sum(fn ($row) => $row['countries'][$country] ?? 0);
    }

    private function csvEuro(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '');
    }
}
