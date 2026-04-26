@extends('layouts.admin', ['title' => $category->exists ? 'Modifica categoria' : 'Nuova categoria'])

@section('content')
    <div class="top">
        <div>
            <h2>{{ $category->exists ? 'Modifica categoria' : 'Nuova categoria' }}</h2>
            <div class="muted">Crea e ordina le collezioni mostrate nello storefront.</div>
        </div>
        <a class="button secondary" href="{{ route('admin.categories.index') }}">Lista categorie</a>
    </div>

    <form method="post" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
        @csrf
        @if ($category->exists)
            @method('put')
        @endif

        <div class="panel">
            <div class="form-grid">
                <div class="row">
                    <label>Nome</label>
                    <input name="name" required value="{{ old('name', $category->name) }}" placeholder="T-shirt personalizzate">
                </div>

                <div class="row">
                    <label>Slug</label>
                    <input name="slug" value="{{ old('slug', $category->slug) }}" placeholder="t-shirt-personalizzate">
                    <small class="muted">Se lo lasci vuoto viene creato automaticamente dal nome.</small>
                </div>

                <div class="row">
                    <label>Ordine</label>
                    <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                </div>

                <div class="row">
                    <label>Attiva nello storefront</label>
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->exists ? $category->is_active : true))>
                </div>

                <div class="row full">
                    <label>Descrizione</label>
                    <textarea name="description" rows="5" placeholder="Testo opzionale per raccontare la collezione.">{{ old('description', $category->description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="admin-actions-row">
            <button>Salva categoria</button>

            @if ($category->exists && $category->is_active)
                <button class="button danger" type="submit" form="delete-category-form">Disattiva</button>
            @endif
        </div>
    </form>

    @if ($category->exists && $category->is_active)
        <form id="delete-category-form" method="post" action="{{ route('admin.categories.destroy', $category) }}">
            @csrf
            @method('delete')
        </form>
    @endif
@endsection
