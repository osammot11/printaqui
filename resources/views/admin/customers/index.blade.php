@extends('layouts.admin', ['title' => 'Clienti'])

@section('content')
    <div class="top">
        <div class="stack-mid">
            <h2>Clienti</h2>
            <div class="muted">Archivio clienti e storico ordini per email.</div>
        </div>
        <a class="button" href="{{ route('admin.customers.export') }}">Export CSV</a>
    </div>

    <table>
        <thead><tr><th>Email</th><th>Nome</th><th>Telefono</th><th>Ordini</th></tr></thead>
        <tbody>
            @foreach ($customers as $customer)
                <tr>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->first_name }} {{ $customer->last_name }}</td>
                    <td>{{ $customer->phone }}</td>
                    <td>{{ $customer->orders_count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:14px;">{{ $customers->links() }}</div>
@endsection
