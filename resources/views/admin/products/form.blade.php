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
                    <input type="file" name="media_images[]" accept=".jpg,.jpeg,.png,.gif,.webp,.avif,.svg,image/*" multiple>
                    <small class="muted">JPG, PNG, GIF, WebP, AVIF o SVG. Max 10MB ciascuna.</small>
                </div>

                <div class="row">
                    <label>Tabella taglie</label>
                    <input type="file" name="size_chart_file" accept=".png,.jpg,.jpeg,.webp,.avif,.pdf">
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

            <div class="admin-variant-color-tools" data-variant-color-toolbar hidden>
                <button class="admin-variant-filter is-active" type="button" data-variant-color-filter="__all">Tutti</button>
                <div class="admin-variant-color-options" data-variant-color-options></div>
            </div>
            <p class="muted admin-variant-active-copy" data-variant-color-copy hidden>Colore selezionato: <strong data-variant-active-color>Tutti</strong></p>

            <div class="variant-list" data-variant-list data-next-index="{{ count($variants) }}">
                <div class="empty-state" data-variant-empty @if(count($variants)) hidden @endif>
                    Nessuna variante creata. Il prodotto restera senza varianti finche non ne aggiungi una.
                </div>

                @foreach ($variants as $index => $variant)
                    <div class="variant-row" data-variant-row data-variant-color="{{ $variant['color'] ?? '' }}" data-variant-hex="{{ $variant['hex_color'] ?? '' }}">
                        <div class="form-grid">
                            <div class="row"><label>Taglia</label><input name="variants[{{ $index }}][size]" value="{{ $variant['size'] ?? '' }}" placeholder="S, M, L..."></div>
                            <div class="row"><label>Colore</label><input name="variants[{{ $index }}][color]" value="{{ $variant['color'] ?? '' }}" placeholder="Nero, Bianco..." data-variant-color-input></div>
                            <div class="row"><label>Hex</label><input name="variants[{{ $index }}][hex_color]" value="{{ $variant['hex_color'] ?? '' }}" placeholder="#111111" data-variant-hex-input></div>
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
                <div class="variant-row" data-variant-row data-variant-color="" data-variant-hex="">
                    <div class="form-grid">
                        <div class="row"><label>Taglia</label><input name="variants[__INDEX__][size]" placeholder="S, M, L..."></div>
                        <div class="row"><label>Colore</label><input name="variants[__INDEX__][color]" placeholder="Nero, Bianco..." data-variant-color-input></div>
                        <div class="row"><label>Hex</label><input name="variants[__INDEX__][hex_color]" placeholder="#111111" data-variant-hex-input></div>
                        <div class="row"><label>Stock</label><input type="number" min="0" name="variants[__INDEX__][stock]" value="1000"></div>
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

    @if ($product->exists)
        <form class="panel admin-danger-zone" method="post" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Confermi di voler eliminare definitivamente questo prodotto? Questa operazione non puo essere annullata.');">
            @csrf
            @method('delete')
            <div class="section-head">
                <div>
                    <h2 style="font-size:22px;">Elimina prodotto</h2>
                    <p class="muted">Questa azione rimuove prodotto, varianti, zone stampa e media caricati. Gli ordini storici restano consultabili con i dati salvati in fase di acquisto.</p>
                </div>
                <button class="button danger" type="submit">Elimina definitivamente</button>
            </div>
            <label class="checkbox-row top-margin-mid">
                <input required type="checkbox" name="delete_product_confirmation" value="1">
                <span>Confermo di voler eliminare definitivamente {{ $product->name }}.</span>
            </label>
        </form>
    @endif

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
                    list.dispatchEvent(new CustomEvent('repeater:added', { detail: { row: list.lastElementChild } }));
                    refreshEmptyState();
                });

                list.addEventListener('click', (event) => {
                    const removeButton = event.target.closest(removeSelector);

                    if (!removeButton) return;

                    removeButton.closest(rowSelector).remove();
                    list.dispatchEvent(new CustomEvent('repeater:removed'));
                    refreshEmptyState();
                });

                refreshEmptyState();
            };

            const setupVariantColorFilter = () => {
                const list = document.querySelector('[data-variant-list]');
                const toolbar = document.querySelector('[data-variant-color-toolbar]');
                const options = document.querySelector('[data-variant-color-options]');
                const copy = document.querySelector('[data-variant-color-copy]');
                const activeColorLabel = document.querySelector('[data-variant-active-color]');
                const allButton = document.querySelector('[data-variant-color-filter="__all"]');

                if (!list || !toolbar || !options || !copy || !activeColorLabel || !allButton) return;

                let activeKey = '__all';

                const rowColor = (row) => row.querySelector('[data-variant-color-input]')?.value.trim() || '';
                const rowHex = (row) => row.querySelector('[data-variant-hex-input]')?.value.trim() || '';
                const normalizeHex = (hex) => /^#[0-9a-f]{6}$/i.test(hex) ? hex : '#f3f4f6';
                const colorKey = (color, hex) => `${color.toLowerCase()}|${hex.toLowerCase()}`;

                const syncRowData = (row) => {
                    row.dataset.variantColor = rowColor(row);
                    row.dataset.variantHex = rowHex(row);
                };

                const colorGroups = () => {
                    const groups = new Map();

                    list.querySelectorAll('[data-variant-row]').forEach((row) => {
                        syncRowData(row);

                        const color = row.dataset.variantColor;
                        if (!color) return;

                        const hex = row.dataset.variantHex || '';
                        const key = colorKey(color, hex);
                        const current = groups.get(key) || { key, color, hex, count: 0 };
                        current.count += 1;
                        groups.set(key, current);
                    });

                    return [...groups.values()];
                };

                const applyFilter = () => {
                    list.querySelectorAll('[data-variant-row]').forEach((row) => {
                        syncRowData(row);

                        const key = colorKey(row.dataset.variantColor || '', row.dataset.variantHex || '');
                        row.hidden = activeKey !== '__all' && key !== activeKey;
                    });
                };

                const setActive = (key, label = 'Tutti') => {
                    activeKey = key;
                    activeColorLabel.textContent = label;

                    allButton.classList.toggle('is-active', activeKey === '__all');
                    options.querySelectorAll('[data-variant-color-filter]').forEach((button) => {
                        button.classList.toggle('is-active', button.dataset.variantColorFilter === activeKey);
                    });

                    applyFilter();
                };

                const renderOptions = () => {
                    const groups = colorGroups();

                    toolbar.hidden = groups.length === 0;
                    copy.hidden = groups.length === 0;
                    options.innerHTML = '';

                    groups.forEach((group) => {
                        const button = document.createElement('button');
                        button.className = 'admin-variant-color-pill';
                        button.type = 'button';
                        button.dataset.variantColorFilter = group.key;
                        button.dataset.variantColorLabel = group.color;
                        button.dataset.variantColorHex = group.hex;
                        button.style.setProperty('--swatch-color', normalizeHex(group.hex));
                        button.setAttribute('aria-label', `Filtra varianti colore ${group.color}`);
                        button.innerHTML = '<span class="admin-variant-color-swatch" aria-hidden="true"></span>';
                        options.appendChild(button);
                    });

                    if (activeKey !== '__all' && !groups.some((group) => group.key === activeKey)) {
                        setActive('__all');
                        return;
                    }

                    setActive(activeKey, activeKey === '__all'
                        ? 'Tutti'
                        : groups.find((group) => group.key === activeKey)?.color || 'Tutti');
                };

                allButton.addEventListener('click', () => setActive('__all'));

                options.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-variant-color-filter]');
                    if (!button) return;

                    setActive(button.dataset.variantColorFilter, button.dataset.variantColorLabel);
                });

                list.addEventListener('input', (event) => {
                    if (!event.target.matches('[data-variant-color-input], [data-variant-hex-input]')) return;

                    renderOptions();
                });

                list.addEventListener('repeater:added', (event) => {
                    const row = event.detail.row;

                    if (activeKey !== '__all') {
                        const activeButton = [...options.querySelectorAll('[data-variant-color-filter]')]
                            .find((button) => button.dataset.variantColorFilter === activeKey);
                        row.querySelector('[data-variant-color-input]').value = activeButton?.dataset.variantColorLabel || '';
                        row.querySelector('[data-variant-hex-input]').value = activeButton?.dataset.variantColorHex || '';
                    }

                    renderOptions();
                });

                list.addEventListener('repeater:removed', renderOptions);

                renderOptions();
            };

            setupRepeater({
                listSelector: '[data-variant-list]',
                templateId: 'variant-row-template',
                addSelector: '[data-add-variant]',
                emptySelector: '[data-variant-empty]',
                rowSelector: '[data-variant-row]',
                removeSelector: '[data-remove-variant]',
            });

            setupVariantColorFilter();

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
