@extends('layouts.admin', ['title' => 'Report fatturato paesi'])

@section('content')
    <div class="top">
        <div class="stack-mid">
            <h2>Report fatturato per paese</h2>
            <div class="muted">Export giornaliero per paese, pensato per consegne periodiche al commercialista.</div>
        </div>
        <a class="button secondary" href="{{ route('admin.reports.sales-by-country.export', $filters) }}">Scarica CSV</a>
    </div>

    <form class="panel top-margin-large" method="get" action="{{ route('admin.reports.sales-by-country') }}">
        <div class="form-grid">
            <div class="row">
                <label>Dal</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" required>
            </div>

            <div class="row">
                <label>Al</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" required>
            </div>

            <div class="row">
                <label>Valore</label>
                <select name="metric">
                    @foreach ($metrics as $value => $label)
                        <option value="{{ $value }}" @selected($filters['metric'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="top-margin-mid" style="display:flex; gap:10px; flex-wrap:wrap;">
            <button>Genera report</button>
            <a class="button secondary" href="{{ route('admin.reports.sales-by-country') }}">Mese corrente</a>
        </div>
    </form>

    <div class="cards">
        <div class="card">
            <div class="muted">Totale periodo</div>
            <div class="metric">€ {{ number_format($report['total_cents'] / 100, 2, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="muted">Ordini inclusi</div>
            <div class="metric">{{ $report['orders_count'] }}</div>
        </div>
        <div class="card">
            <div class="muted">Paesi</div>
            <div class="metric">{{ count($report['countries']) }}</div>
        </div>
        <div class="card">
            <div class="muted">Metrica</div>
            <div class="metric" style="font-size:22px;">{{ $report['metric_label'] }}</div>
        </div>
    </div>

    <div class="panel top-margin-large">
        <div class="top">
            <div>
                <h2 style="font-size:22px;">Anteprima CSV</h2>
                <div class="muted">Periodo {{ $report['date_from'] }} - {{ $report['date_to'] }}. Le date usano l'incasso Stripe dell'ordine.</div>
            </div>
        </div>

        <div style="overflow:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        @foreach ($report['countries'] as $country)
                            <th>{{ $country }}</th>
                        @endforeach
                        <th>Totale giorno</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['rows'] as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                            @foreach (array_keys($report['countries']) as $country)
                                <td>€ {{ number_format(($row['countries'][$country] ?? 0) / 100, 2, ',', '.') }}</td>
                            @endforeach
                            <td><strong>€ {{ number_format($row['total_cents'] / 100, 2, ',', '.') }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($report['countries']) + 2 }}">Nessun ordine pagato nel periodo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
