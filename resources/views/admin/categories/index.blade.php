@extends('layouts.admin', ['title' => 'Categorie'])

@section('content')
    <div class="top">
        <div class="stack-mid">
            <h2>Categorie</h2>
            <div class="muted">Gestisci collezioni storefront, ordine di visualizzazione e visibilita pubblica.</div>
        </div>
        <a class="button" href="{{ route('admin.categories.create') }}">Nuova categoria</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Slug</th>
                <th>Prodotti</th>
                <th>Ordine</th>
                <th>Stato</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td>
                        <strong>{{ $category->name }}</strong>
                        @if ($category->description)
                            <div class="muted">{{ \Illuminate\Support\Str::limit($category->description, 90) }}</div>
                        @endif
                    </td>
                    <td><span class="muted">{{ $category->slug }}</span></td>
                    <td>{{ $category->products_count }}</td>
                    <td>{{ $category->sort_order }}</td>
                    <td>{{ $category->is_active ? 'Attiva' : 'Disattivata' }}</td>
                    <td><a class="button secondary" href="{{ route('admin.categories.edit', $category) }}">Modifica</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Nessuna categoria creata.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-row">{{ $categories->links() }}</div>
@endsection
