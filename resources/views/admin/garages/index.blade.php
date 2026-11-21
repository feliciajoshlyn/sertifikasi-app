{{-- GarageIndexView (<<view>>): Admin Buat Garasi and segmented S/C/E garage units listing ($garages). --}}
@extends('layouts.app')

@section('title', 'Buat Garasi — GarageRent')

@section('content')
<div class="grid items-start gap-6 lg:grid-cols-3">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs lg:col-span-1">
        <h1 class="text-lg font-semibold tracking-tight text-slate-900">Batch Create Garages</h1>
        <p class="mt-1 text-sm text-slate-600">Codes are generated sequentially (S001, C001, E001).</p>

        <form method="POST" action="{{ route('admin.garages.store') }}" class="mt-5 space-y-4">
            @csrf

            <div>
                <label for="type" class="block text-sm font-medium text-slate-700">Garage Type <span class="text-rose-600" aria-hidden="true">*</span></label>
                <select id="type" name="type" required
                    class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
                    <option value="sepeda" @selected(old('type') === 'sepeda')>Sepeda / Sepeda Motor (S)</option>
                    <option value="mobil" @selected(old('type') === 'mobil')>Mobil (C)</option>
                    <option value="mobil_listrik" @selected(old('type') === 'mobil_listrik')>Mobil Listrik / EV (E)</option>
                </select>
            </div>

            <div>
                <label for="count" class="block text-sm font-medium text-slate-700">Quantity <span class="text-rose-600" aria-hidden="true">*</span></label>
                <input id="count" name="count" type="number" min="1" max="50" value="{{ old('count', 1) }}" required
                    class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm tabular-nums text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
            </div>

            <div>
                <label for="daily_rate" class="block text-sm font-medium text-slate-700">Daily Rate (Rp) <span class="text-rose-600" aria-hidden="true">*</span></label>
                <input id="daily_rate" name="daily_rate" type="number" step="1" min="1" value="{{ old('daily_rate', 50000) }}" required
                    class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm tabular-nums text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
            </div>

            <div id="kwh-wrapper">
                <label for="kwh_price" class="block text-sm font-medium text-slate-700">kWh Price (Rp) <span class="text-rose-600" aria-hidden="true">*</span></label>
                <input id="kwh_price" name="kwh_price" type="number" step="1" min="1" value="{{ old('kwh_price', 2500) }}"
                    class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm tabular-nums text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
            </div>

            <button type="submit"
                class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/20">
                Create Garages
            </button>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs lg:col-span-2">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Garage Units</h2>

            <div role="tablist" aria-label="Filter garages by type" class="inline-flex flex-wrap gap-1 rounded-xl bg-slate-100 p-1">
                <button type="button" role="tab" aria-selected="true" data-segment="all"
                    class="segment-tab inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-slate-900 shadow-xs focus:outline-none focus:ring-2 focus:ring-slate-900/15">
                    <span>All</span>
                    <span data-count-for="all" class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] tabular-nums text-slate-700">0</span>
                </button>
                <button type="button" role="tab" aria-selected="false" data-segment="sepeda"
                    class="segment-tab inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
                    <span>S &middot; Sepeda/Motor</span>
                    <span data-count-for="sepeda" class="rounded-md bg-slate-200/70 px-1.5 py-0.5 text-[11px] tabular-nums text-slate-600">0</span>
                </button>
                <button type="button" role="tab" aria-selected="false" data-segment="mobil"
                    class="segment-tab inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
                    <span>C &middot; Mobil</span>
                    <span data-count-for="mobil" class="rounded-md bg-slate-200/70 px-1.5 py-0.5 text-[11px] tabular-nums text-slate-600">0</span>
                </button>
                <button type="button" role="tab" aria-selected="false" data-segment="mobil_listrik"
                    class="segment-tab inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
                    <span>E &middot; EV</span>
                    <span data-count-for="mobil_listrik" class="rounded-md bg-slate-200/70 px-1.5 py-0.5 text-[11px] tabular-nums text-slate-600">0</span>
                </button>
            </div>
        </div>

        @if ($garages->isEmpty())
            <p class="mt-6 rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-600">
                No garages created yet. Use the form on the left to batch-generate garage units.
            </p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="py-2.5 pr-4">Code</th>
                            <th class="py-2.5 pr-4">Type</th>
                            <th class="py-2.5 pr-4">Daily Rate</th>
                            <th class="py-2.5 pr-4">kWh Price</th>
                            <th class="py-2.5 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($garages as $garage)
                            <tr class="garage-row" data-type="{{ $garage->type }}">
                                <td class="py-3 pr-4 font-semibold tabular-nums text-slate-900">{{ $garage->code }}</td>
                                <td class="py-3 pr-4 text-slate-700">{{ $garage->typeLabel() }}</td>
                                <td class="py-3 pr-4 tabular-nums text-slate-700">Rp {{ number_format($garage->daily_rate, 0, ',', '.') }}</td>
                                <td class="py-3 pr-4 tabular-nums text-slate-600">{{ $garage->kwh_price ? 'Rp ' . number_format($garage->kwh_price, 0, ',', '.') : '—' }}</td>
                                <td class="py-3 text-right">
                                    <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $garage->status === 'available' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">
                                        {{ $garage->status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                        <tr id="empty-segment-row" class="hidden">
                            <td colspan="5" class="py-6 text-center text-sm text-slate-500">
                                No garages in this category yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>

<script>
    (function () {
        const typeSelect = document.getElementById('type');
        const kwhWrapper = document.getElementById('kwh-wrapper');
        const kwhInput = document.getElementById('kwh_price');

        function syncKwh() {
            const isEv = typeSelect.value === 'mobil_listrik';
            kwhWrapper.style.display = isEv ? 'block' : 'none';
            kwhInput.disabled = !isEv;
            kwhInput.required = isEv;
        }

        typeSelect.addEventListener('change', syncKwh);
        syncKwh();

        const tabs = document.querySelectorAll('.segment-tab');
        const rows = document.querySelectorAll('.garage-row');
        const emptyRow = document.getElementById('empty-segment-row');

        const counts = { all: rows.length, sepeda: 0, mobil: 0, mobil_listrik: 0 };
        rows.forEach((row) => {
            const type = row.dataset.type;
            if (Object.prototype.hasOwnProperty.call(counts, type)) {
                counts[type] += 1;
            }
        });

        Object.keys(counts).forEach((key) => {
            const badge = document.querySelector('[data-count-for="' + key + '"]');
            if (badge) badge.textContent = String(counts[key]);
        });

        function applySegment(segment) {
            let visibleCount = 0;

            rows.forEach((row) => {
                const match = segment === 'all' || row.dataset.type === segment;
                row.classList.toggle('hidden', !match);
                if (match) visibleCount += 1;
            });

            if (emptyRow) {
                emptyRow.classList.toggle('hidden', visibleCount > 0);
            }

            tabs.forEach((tab) => {
                const isActive = tab.dataset.segment === segment;
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                tab.classList.toggle('bg-white', isActive);
                tab.classList.toggle('text-slate-900', isActive);
                tab.classList.toggle('shadow-xs', isActive);
                tab.classList.toggle('text-slate-600', !isActive);

                const badge = tab.querySelector('[data-count-for]');
                if (badge) {
                    badge.classList.toggle('bg-slate-100', isActive);
                    badge.classList.toggle('text-slate-700', isActive);
                    badge.classList.toggle('bg-slate-200/70', !isActive);
                    badge.classList.toggle('text-slate-600', !isActive);
                }
            });
        }

        tabs.forEach((tab) => {
            tab.addEventListener('click', function () {
                applySegment(tab.dataset.segment);
            });
        });

        applySegment('all');
    })();
</script>
@endsection
