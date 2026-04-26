@extends('layouts.admin', ['title' => $product->exists ? 'Modifica prodotto' : 'Nuovo prodotto'])

@section('content')
    <div class="top">
        <div>
            <h2>{{ $product->exists ? 'Modifica prodotto' : 'Nuovo prodotto' }}</h2>
            <div class="muted">Campi base, varianti taglia/colore e zone stampa specifiche.</div>
        </div>
        <a class="button secondary" href="{{ route('admin.products.index') }}">Lista prodotti</a>
    </div>

    <form method="post" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($product->exists)
            @method('put')
        @endif

        <div class="panel">
            <div class="form-grid">
                <div class="row"><label>Nome</label><input name="name" required value="{{ old('name', $product->name) }}"></div>
                <div class="row"><label>SKU</label><input name="sku" required value="{{ old('sku', $product->sku) }}"></div>
                <div class="row">
                    <label>Categoria</label>
                    <select name="category_id">
                        <option value="">Nessuna</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row"><label>Consegna stimata</label><input name="estimated_delivery" value="{{ old('estimated_delivery', $product->estimated_delivery ?: $defaultDeliveryEstimate) }}"></div>
                <div class="row"><label>Prezzo blank</label><input type="number" step="0.01" name="base_price" required value="{{ old('base_price', $product->base_price_cents ? $product->base_price_cents / 100 : '') }}"></div>
                <div class="row"><label>Prezzo scontato</label><input type="number" step="0.01" name="sale_price" value="{{ old('sale_price', $product->sale_price_cents ? $product->sale_price_cents / 100 : '') }}"></div>
                <div class="row"><label>Costo interno</label><input type="number" step="0.01" name="internal_cost" value="{{ old('internal_cost', $product->internal_cost_cents ? $product->internal_cost_cents / 100 : '') }}"></div>
                <div class="row"><label>Attivo</label><input style="width:auto" type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))></div>
                <div class="row" style="grid-column:1/-1"><label>Descrizione</label><textarea name="description" rows="4">{{ old('description', $product->description) }}</textarea></div>
            </div>
        </div>

        <div class="panel" style="margin-top:16px;">
            <div class="section-head">
                <div>
                    <h2 style="font-size:22px;">Media</h2>
                    <p class="muted">Carica gallery prodotto, scegli l'immagine principale e aggiungi la tabella taglie.</p>
                </div>
            </div>

            @php $mediaItems = $product->mediaItems(); @endphp

            @if (count($mediaItems))
                <div class="media-admin-grid">
                    @foreach ($mediaItems as $item)
                        <div class="media-admin-card">
                            <img src="{{ $item['url'] }}" alt="{{ $item['original_name'] }}">
                            <label class="checkbox-row">
                                <input type="radio" name="primary_media_key" value="{{ $item['key'] }}" @checked($item['is_primary'] || (! collect($mediaItems)->contains(fn ($media) => $media['is_primary']) && $loop->first))>
                                <span>Principale</span>
                            </label>
                            <label class="checkbox-row">
                                <input type="checkbox" name="remove_media[]" value="{{ $item['key'] }}">
                                <span>Rimuovi</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">Nessuna immagine caricata. La prima immagine che aggiungi diventera quella principale.</div>
            @endif

            <div class="form-grid top-margin-large">
                <div class="row">
                    <label>Nuove immagini prodotto</label>
                    <input type="file" name="media_images[]" accept="image/*" multiple>
                    <small class="muted">Puoi selezionare piu immagini insieme. Max 10MB ciascuna.</small>
                </div>

                <div class="row">
                    <label>Tabella taglie</label>
                    <input type="file" name="size_chart_file" accept=".png,.jpg,.jpeg,.pdf">
                    @if ($product->sizeChartUrl())
                        <div class="top-margin-small">
                            <a class="button secondary" href="{{ $product->sizeChartUrl() }}" target="_blank" rel="noreferrer">Apri tabella attuale</a>
                        </div>
                        <label class="checkbox-row top-margin-small">
                            <input type="checkbox" name="remove_size_chart" value="1">
                            <span>Rimuovi tabella taglie</span>
                        </label>
                    @endif
                </div>
            </div>
        </div>

        <div class="panel" style="margin-top:16px;">
            <div class="section-head">
                <div>
                    <h2 style="font-size:22px;">Varianti</h2>
                    <p class="muted">Aggiungi solo le combinazioni taglia/colore che vendi davvero.</p>
                </div>
                <button class="button secondary" type="button" data-add-variant>Aggiungi variante</button>
            </div>

            @php $variants = old('variants', $product->exists ? $product->variants->toArray() : []); @endphp

            <div class="variant-list" data-variant-list data-next-index="{{ count($variants) }}">
                <div class="empty-state" data-variant-empty @if(count($variants)) hidden @endif>
                    Nessuna variante creata. Il prodotto restera senza varianti finche non ne aggiungi una.
                </div>

                @foreach ($variants as $index => $variant)
                    <div class="variant-row" data-variant-row>
                        <div class="form-grid">
                            <div class="row"><label>Taglia</label><input name="variants[{{ $index }}][size]" value="{{ $variant['size'] ?? '' }}" placeholder="S, M, L..."></div>
                            <div class="row"><label>Colore</label><input name="variants[{{ $index }}][color]" value="{{ $variant['color'] ?? '' }}" placeholder="Nero, Bianco..."></div>
                            <div class="row"><label>Hex</label><input name="variants[{{ $index }}][hex_color]" value="{{ $variant['hex_color'] ?? '' }}" placeholder="#111111"></div>
                            <div class="row"><label>Stock</label><input type="number" min="0" name="variants[{{ $index }}][stock]" value="{{ $variant['stock'] ?? 0 }}"></div>
                            <div class="row"><label>SKU variante</label><input name="variants[{{ $index }}][sku]" value="{{ $variant['sku'] ?? '' }}" placeholder="Generato se vuoto"></div>
                            <div class="row"><label>Attiva</label><input style="width:auto" type="checkbox" name="variants[{{ $index }}][is_active]" value="1" @checked($variant['is_active'] ?? true)></div>
                            <div class="row variant-row-actions">
                                <button class="button danger" type="button" data-remove-variant>Rimuovi</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <template id="variant-row-template">
                <div class="variant-row" data-variant-row>
                    <div class="form-grid">
                        <div class="row"><label>Taglia</label><input name="variants[__INDEX__][size]" placeholder="S, M, L..."></div>
                        <div class="row"><label>Colore</label><input name="variants[__INDEX__][color]" placeholder="Nero, Bianco..."></div>
                        <div class="row"><label>Hex</label><input name="variants[__INDEX__][hex_color]" placeholder="#111111"></div>
                        <div class="row"><label>Stock</label><input type="number" min="0" name="variants[__INDEX__][stock]" value="0"></div>
                        <div class="row"><label>SKU variante</label><input name="variants[__INDEX__][sku]" placeholder="Generato se vuoto"></div>
                        <div class="row"><label>Attiva</label><input style="width:auto" type="checkbox" name="variants[__INDEX__][is_active]" value="1" checked></div>
                        <div class="row variant-row-actions">
                            <button class="button danger" type="button" data-remove-variant>Rimuovi</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="panel" style="margin-top:16px;">
            <div class="section-head">
                <div>
                    <h2 style="font-size:22px;">Zone stampa</h2>
                    <p class="muted">Aggiungi solo le posizioni di stampa disponibili per questo prodotto.</p>
                </div>
                <button class="button secondary" type="button" data-add-zone>Aggiungi zona</button>
            </div>

            @php
                $hasOldZones = old('print_zones') !== null;
                $zones = old('print_zones', $product->exists ? $product->printZones->toArray() : []);
            @endphp

            <div class="variant-list" data-zone-list data-next-index="{{ count($zones) }}">
                <div class="empty-state" data-zone-empty @if(count($zones)) hidden @endif>
                    Nessuna zona stampa creata. Il prodotto restera acquistabile solo blank finche non ne aggiungi una.
                </div>

                @foreach ($zones as $index => $zone)
                    <div class="variant-row" data-zone-row>
                        <div class="form-grid">
                            <div class="row"><label>Nome zona</label><input name="print_zones[{{ $index }}][name]" value="{{ $zone['name'] ?? '' }}" placeholder="Nome posizione stampa"></div>
                            <div class="row"><label>Prezzo aggiuntivo</label><input type="number" step="0.01" min="0" name="print_zones[{{ $index }}][price]" value="{{ $zone['price'] ?? (($zone['additional_price_cents'] ?? 0) / 100) }}"></div>
                            <div class="row"><label>Attiva</label><input style="width:auto" type="checkbox" name="print_zones[{{ $index }}][is_active]" value="1" @checked($hasOldZones ? isset($zone['is_active']) : ($zone['is_active'] ?? true))></div>
                            <div class="row variant-row-actions">
                                <button class="button danger" type="button" data-remove-zone>Rimuovi</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <template id="zone-row-template">
                <div class="variant-row" data-zone-row>
                    <div class="form-grid">
                        <div class="row"><label>Nome zona</label><input name="print_zones[__INDEX__][name]" placeholder="Nome posizione stampa"></div>
                        <div class="row"><label>Prezzo aggiuntivo</label><input type="number" step="0.01" min="0" name="print_zones[__INDEX__][price]" value="0"></div>
                        <div class="row"><label>Attiva</label><input style="width:auto" type="checkbox" name="print_zones[__INDEX__][is_active]" value="1" checked></div>
                        <div class="row variant-row-actions">
                            <button class="button danger" type="button" data-remove-zone>Rimuovi</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div style="margin-top:16px;"><button>Salva prodotto</button></div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const setupRepeater = ({ listSelector, templateId, addSelector, emptySelector, rowSelector, removeSelector }) => {
                const list = document.querySelector(listSelector);
                const template = document.getElementById(templateId);
                const addButton = document.querySelector(addSelector);
                const emptyState = document.querySelector(emptySelector);

                if (!list || !template || !addButton || !emptyState) return;

                const refreshEmptyState = () => {
                    emptyState.hidden = list.querySelectorAll(rowSelector).length > 0;
                };

                addButton.addEventListener('click', () => {
                    const index = Number(list.dataset.nextIndex || 0);
                    const html = template.innerHTML.replaceAll('__INDEX__', String(index));

                    list.insertAdjacentHTML('beforeend', html);
                    list.dataset.nextIndex = String(index + 1);
                    refreshEmptyState();
                });

                list.addEventListener('click', (event) => {
                    const removeButton = event.target.closest(removeSelector);

                    if (!removeButton) return;

                    removeButton.closest(rowSelector).remove();
                    refreshEmptyState();
                });

                refreshEmptyState();
            };

            setupRepeater({
                listSelector: '[data-variant-list]',
                templateId: 'variant-row-template',
                addSelector: '[data-add-variant]',
                emptySelector: '[data-variant-empty]',
                rowSelector: '[data-variant-row]',
                removeSelector: '[data-remove-variant]',
            });

            setupRepeater({
                listSelector: '[data-zone-list]',
                templateId: 'zone-row-template',
                addSelector: '[data-add-zone]',
                emptySelector: '[data-zone-empty]',
                rowSelector: '[data-zone-row]',
                removeSelector: '[data-remove-zone]',
            });
        });
    </script>
@endsection
