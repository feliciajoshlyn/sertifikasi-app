{{-- RentalCreateView (<<view>>): Sewa Garasi Baru form with inline optional new vehicle registration ($vehicles, $totalVehiclesCount, $availableGarages). --}}
@extends('layouts.app')

@section('title', 'Sewa Garasi Baru — GarageRent')

@section('content')
@php
    $defaultVehicleMode = old('vehicle_mode', $vehicles->isNotEmpty() ? 'existing' : 'new');
@endphp

<div class="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8">
    <h1 class="text-xl font-semibold tracking-tight text-slate-900">Sewa Garasi Baru</h1>
    <p class="mt-1 text-sm text-slate-600">Pilih kendaraan terdaftar atau tambahkan kendaraan baru, tentukan tipe sewa, dan pilih garasi tersedia hari ini.</p>

    <form method="POST" action="{{ route('penyewa.rentals.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
        @csrf

        @if ($vehicles->isNotEmpty())
            <fieldset>
                <legend class="block text-sm font-medium text-slate-700">Kendaraan <span class="text-rose-600" aria-hidden="true">*</span></legend>
                <div class="mt-1.5 grid gap-3 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm hover:border-slate-300 has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
                        <input type="radio" name="vehicle_mode" value="existing"
                            @checked($defaultVehicleMode === 'existing')
                            class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-slate-900">
                        <span class="font-medium text-slate-900">Pilih Kendaraan Terdaftar</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm hover:border-slate-300 has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
                        <input type="radio" name="vehicle_mode" value="new"
                            @checked($defaultVehicleMode === 'new')
                            class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-slate-900">
                        <span class="font-medium text-slate-900">+ Tambah Kendaraan Baru</span>
                    </label>
                </div>
            </fieldset>

            <div id="existing-vehicle-section" class="{{ $defaultVehicleMode === 'existing' ? '' : 'hidden' }}">
                <label for="vehicle_id" class="block text-sm font-medium text-slate-700">Pilih Kendaraan <span class="text-rose-600" aria-hidden="true">*</span></label>
                <select id="vehicle_id" name="vehicle_id"
                    class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
                    @foreach ($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}"
                            data-type="{{ $vehicle->type }}"
                            data-capacity="{{ $vehicle->battery_capacity_kwh ?? 0 }}"
                            @selected((string) old('vehicle_id') === (string) $vehicle->id)>
                            {{ $vehicle->brand_model }} ({{ $vehicle->typeLabel() }}{{ $vehicle->plate_number ? ' — ' . $vehicle->plate_number : '' }})
                        </option>
                    @endforeach
                </select>
            </div>
        @else
            <input type="hidden" name="vehicle_mode" value="new">
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                @if ($totalVehiclesCount > 0)
                    Semua kendaraan terdaftar Anda sedang disewa saat ini. Tambahkan kendaraan baru di bawah untuk menyewa garasi.
                @else
                    Masukkan detail kendaraan Anda di bawah untuk menyewa garasi.
                @endif
            </div>
        @endif

        <div id="new-vehicle-section" class="{{ $defaultVehicleMode === 'new' ? '' : 'hidden' }} space-y-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Detail Kendaraan Baru</p>

            <div>
                <label for="new_vehicle_type" class="block text-sm font-medium text-slate-700">Tipe Kendaraan <span class="text-rose-600" aria-hidden="true">*</span></label>
                <select id="new_vehicle_type" name="new_vehicle_type"
                    class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
                    <option value="sepeda" @selected(old('new_vehicle_type') === 'sepeda')>Sepeda</option>
                    <option value="sepeda_motor" @selected(old('new_vehicle_type') === 'sepeda_motor')>Sepeda Motor</option>
                    <option value="mobil" @selected(old('new_vehicle_type') === 'mobil')>Mobil</option>
                    <option value="mobil_listrik" @selected(old('new_vehicle_type') === 'mobil_listrik')>Mobil Listrik (EV)</option>
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div id="new-plate-wrapper">
                    <label for="new_vehicle_plate" class="block text-sm font-medium text-slate-700">Nomor Plat Kendaraan <span class="text-rose-600" aria-hidden="true">*</span></label>
                    <input id="new_vehicle_plate" name="new_vehicle_plate" type="text" value="{{ old('new_vehicle_plate') }}"
                        placeholder="L 1234 AB"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
                </div>

                <div>
                    <label for="new_vehicle_brand" class="block text-sm font-medium text-slate-700">Merek &amp; Tipe <span class="text-rose-600" aria-hidden="true">*</span></label>
                    <input id="new_vehicle_brand" name="new_vehicle_brand" type="text" value="{{ old('new_vehicle_brand') }}"
                        placeholder="Hyundai Ioniq 5"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
                </div>
            </div>

            <div id="new-battery-wrapper">
                <label for="new_vehicle_battery" class="block text-sm font-medium text-slate-700">Kapasitas Baterai Penuh (kWh) <span class="text-rose-600" aria-hidden="true">*</span></label>
                <input id="new_vehicle_battery" name="new_vehicle_battery" type="number" step="0.01" min="0.01"
                    value="{{ old('new_vehicle_battery') }}" placeholder="80.00"
                    class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="garage_type_preview" class="block text-sm font-medium text-slate-700">Tipe Garasi (Sesuai Kendaraan)</label>
                <input id="garage_type_preview" type="text" readonly
                    class="mt-1.5 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 focus:outline-none">
            </div>

            <div>
                <label for="garage_id" class="block text-sm font-medium text-slate-700">Garasi Tersedia Hari Ini <span class="text-rose-600" aria-hidden="true">*</span></label>
                <select id="garage_id" name="garage_id" required
                    class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
                </select>
            </div>
        </div>

        <fieldset>
            <legend class="block text-sm font-medium text-slate-700">Tipe Sewa <span class="text-rose-600" aria-hidden="true">*</span></legend>
            <div class="mt-1.5 grid gap-3 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3.5 hover:border-slate-300 has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
                    <input type="radio" name="rental_type" value="harian"
                        @checked(old('rental_type', 'harian') === 'harian')
                        class="mt-0.5 h-4 w-4 border-slate-300 text-slate-900 focus:ring-slate-900">
                    <span>
                        <span class="block text-sm font-medium text-slate-900">Harian</span>
                        <span class="block text-xs text-slate-500">1 hingga <span id="max-daily-radio-hint"></span> hari &times; tarif harian</span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3.5 hover:border-slate-300 has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
                    <input type="radio" name="rental_type" value="bulanan"
                        @checked(old('rental_type') === 'bulanan')
                        class="mt-0.5 h-4 w-4 border-slate-300 text-slate-900 focus:ring-slate-900">
                    <span>
                        <span class="block text-sm font-medium text-slate-900">Bulanan</span>
                        <span class="block text-xs text-slate-500">1 bulan penuh (25 &times; tarif harian)</span>
                    </span>
                </label>
            </div>
        </fieldset>

        <div id="daily-duration-section">
            <label for="duration_days" class="block text-sm font-medium text-slate-700">Jumlah Hari (<span id="max-daily-label"></span>) <span class="text-rose-600" aria-hidden="true">*</span></label>
            <input id="duration_days" name="duration_days" type="number" min="1"
                value="{{ old('duration_days', 1) }}" required
                class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm tabular-nums text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
        </div>

        <p id="monthly-duration-hint" class="hidden rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs text-slate-600">
            Sewa bulanan otomatis berlaku untuk 1 bulan kalender penuh (<span id="monthly-days-hint"></span> hari) mulai hari ini dengan tarif 25 &times; tarif harian.
        </p>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="start_date" class="block text-sm font-medium text-slate-700">Tanggal Mulai Sewa</label>
                <input id="start_date" name="start_date" type="date"
                    value="{{ now()->toDateString() }}" readonly
                    class="mt-1.5 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm tabular-nums text-slate-600 focus:outline-none">
            </div>

            <div>
                <label for="end_date_preview" class="block text-sm font-medium text-slate-700">Tanggal Selesai Sewa (Otomatis)</label>
                <input id="end_date_preview" type="date" readonly
                    class="mt-1.5 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm tabular-nums text-slate-600 focus:outline-none">
            </div>
        </div>

        <div id="ev-battery-section" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-4">
            <label for="remaining_battery_kwh" class="block text-sm font-medium text-slate-800">Sisa Baterai Saat Ini (kWh) <span class="text-rose-600" aria-hidden="true">*</span></label>
            <input id="remaining_battery_kwh" name="remaining_battery_kwh" type="number" step="0.01" min="0"
                value="{{ old('remaining_battery_kwh') }}" placeholder="contoh: 20.00"
                class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
            <p id="ev-battery-hint" class="mt-1 text-xs text-slate-600">Biaya pengisian EV dihitung dari (Kapasitas Penuh &minus; Sisa Baterai) &times; Tarif per kWh.</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600">Estimasi Biaya Sewa</span>
                <span id="est-base" class="font-medium tabular-nums text-slate-900">Rp 0</span>
            </div>
            <div class="mt-1.5 flex items-center justify-between text-sm">
                <span class="text-slate-600">Estimasi Pengisian EV</span>
                <span id="est-charge" class="font-medium tabular-nums text-slate-900">Rp 0</span>
            </div>
            <div class="mt-2.5 flex items-center justify-between border-t border-slate-200 pt-2.5 text-base font-semibold">
                <span class="text-slate-900">Total Biaya Sewa</span>
                <span id="est-total" class="tabular-nums text-slate-900">Rp 0</span>
            </div>
        </div>

        <div>
            <label for="payment_proof" class="block text-sm font-medium text-slate-700">Bukti Pelunasan Biaya (JPG, PNG, atau PDF) <span class="text-rose-600" aria-hidden="true">*</span></label>
            <input id="payment_proof" name="payment_proof" type="file" accept=".jpg,.jpeg,.png,.pdf" required
                class="mt-1.5 block w-full text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3.5 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-slate-800">
        </div>

        <button id="submit-btn" type="submit"
            class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/20 disabled:cursor-not-allowed disabled:bg-slate-400">
            Simpan Sewa Garasi
        </button>
    </form>
</div>

<script>
    (function () {
        const vehicleModeRadios = document.querySelectorAll('input[name="vehicle_mode"][type="radio"]');
        const vehicleModeHidden = document.querySelector('input[name="vehicle_mode"][type="hidden"]');
        const existingSection = document.getElementById('existing-vehicle-section');
        const newSection = document.getElementById('new-vehicle-section');
        const vehicleEl = document.getElementById('vehicle_id');

        const newTypeEl = document.getElementById('new_vehicle_type');
        const newPlateWrap = document.getElementById('new-plate-wrapper');
        const newPlateEl = document.getElementById('new_vehicle_plate');
        const newBrandEl = document.getElementById('new_vehicle_brand');
        const newBatteryWrap = document.getElementById('new-battery-wrapper');
        const newBatteryEl = document.getElementById('new_vehicle_battery');

        const garageTypePreview = document.getElementById('garage_type_preview');
        const garageEl = document.getElementById('garage_id');
        const rentalTypeInputs = document.querySelectorAll('input[name="rental_type"]');
        const dailyDurationSection = document.getElementById('daily-duration-section');
        const monthlyDurationHint = document.getElementById('monthly-duration-hint');
        const maxDailyRadioHint = document.getElementById('max-daily-radio-hint');
        const maxDailyLabel = document.getElementById('max-daily-label');
        const monthlyDaysHint = document.getElementById('monthly-days-hint');
        const startEl = document.getElementById('start_date');
        const endPreviewEl = document.getElementById('end_date_preview');
        const daysEl = document.getElementById('duration_days');
        const evSection = document.getElementById('ev-battery-section');
        const batteryEl = document.getElementById('remaining_battery_kwh');
        const estBase = document.getElementById('est-base');
        const estCharge = document.getElementById('est-charge');
        const estTotal = document.getElementById('est-total');
        const submitBtn = document.getElementById('submit-btn');

        if (!garageEl) return;

        const allGarages = @json($availableGarages);
        const initialOldGarageId = @json((string) old('garage_id', ''));
        const vehicleToGarageTypes = {
            sepeda: ['sepeda'],
            sepeda_motor: ['sepeda'],
            mobil: ['mobil'],
            mobil_listrik: ['mobil', 'mobil_listrik']
        };
        const garageTypePreviewLabels = {
            sepeda: 'Garasi Sepeda / Sepeda Motor (S)',
            sepeda_motor: 'Garasi Sepeda / Sepeda Motor (S)',
            mobil: 'Garasi Mobil (C)',
            mobil_listrik: 'Garasi Mobil (C) atau EV (E)'
        };
        const garageOptionLabels = {
            sepeda: 'Sepeda / Sepeda Motor',
            mobil: 'Mobil',
            mobil_listrik: 'Mobil Listrik'
        };

        function formatRp(num) {
            return 'Rp ' + Math.max(0, Math.round(num)).toLocaleString('id-ID');
        }

        function getVehicleMode() {
            const checked = document.querySelector('input[name="vehicle_mode"][type="radio"]:checked');
            if (checked) return checked.value;
            return vehicleModeHidden ? vehicleModeHidden.value : 'new';
        }

        function getSelectedRentalType() {
            const checked = document.querySelector('input[name="rental_type"]:checked');
            return checked ? checked.value : 'harian';
        }

        // Disable inactive vehicle-mode inputs so HTML5 validation and form payloads stay cleanly scoped.
        function syncVehicleModeFields() {
            const isNew = getVehicleMode() === 'new';

            if (existingSection) existingSection.classList.toggle('hidden', isNew);
            if (vehicleEl) {
                vehicleEl.disabled = isNew;
                vehicleEl.required = !isNew;
            }

            newSection.classList.toggle('hidden', !isNew);
            newTypeEl.disabled = !isNew;
            newTypeEl.required = isNew;
            newBrandEl.disabled = !isNew;
            newBrandEl.required = isNew;

            const newType = newTypeEl.value;
            const isBicycle = newType === 'sepeda';
            const isEv = newType === 'mobil_listrik';

            newPlateWrap.classList.toggle('hidden', isBicycle);
            newPlateEl.disabled = !isNew || isBicycle;
            newPlateEl.required = isNew && !isBicycle;
            if (isBicycle) {
                newPlateEl.value = '';
            }

            newBatteryWrap.classList.toggle('hidden', !isEv);
            newBatteryEl.disabled = !isNew || !isEv;
            newBatteryEl.required = isNew && isEv;
        }

        function getActiveVehicleInfo() {
            if (getVehicleMode() === 'new') {
                return {
                    type: newTypeEl.value,
                    capacity: parseFloat(newBatteryEl.value || '0') || 0
                };
            }

            const vOpt = vehicleEl ? vehicleEl.options[vehicleEl.selectedIndex] : null;
            return {
                type: vOpt ? vOpt.dataset.type : '',
                capacity: vOpt ? (parseFloat(vOpt.dataset.capacity || '0') || 0) : 0
            };
        }

        // Format from local date parts instead of toISOString() to avoid UTC timezone off-by-one shifts.
        function formatLocalDate(year, monthIndex, day) {
            const date = new Date(year, monthIndex, day);
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + d;
        }

        // Rebuild <option> nodes from serialized JSON rather than toggling option.hidden (unsupported in Safari).
        function rebuildGarageOptions(preserveSelection) {
            const vehicleInfo = getActiveVehicleInfo();
            const allowedGarageTypes = vehicleToGarageTypes[vehicleInfo.type] || [];

            garageTypePreview.value = garageTypePreviewLabels[vehicleInfo.type] || '';

            const matchingGarages = allGarages.filter((g) => allowedGarageTypes.includes(g.type));
            const currentVal = preserveSelection ? (initialOldGarageId || garageEl.value) : garageEl.value;

            garageEl.innerHTML = '';

            if (matchingGarages.length === 0) {
                const emptyOpt = document.createElement('option');
                emptyOpt.value = '';
                emptyOpt.textContent = 'Tidak ada garasi tersedia untuk tipe kendaraan ini';
                emptyOpt.disabled = true;
                emptyOpt.selected = true;
                garageEl.appendChild(emptyOpt);
                submitBtn.disabled = true;
                return;
            }

            submitBtn.disabled = false;

            matchingGarages.forEach((g, idx) => {
                const opt = document.createElement('option');
                opt.value = String(g.id);
                opt.dataset.type = g.type;
                opt.dataset.rate = String(g.daily_rate);
                opt.dataset.kwh = String(g.kwh_price || 0);
                opt.textContent = g.code + ' (' + (garageOptionLabels[g.type] || g.type) + ') — ' + formatRp(parseFloat(g.daily_rate)) + '/hari';

                if (currentVal && String(g.id) === String(currentVal)) {
                    opt.selected = true;
                } else if (!currentVal && idx === 0) {
                    opt.selected = true;
                }

                garageEl.appendChild(opt);
            });
        }

        // Mirror RentalService calendar-month limits (daysInMonth - 1 / 25x monthly) for live preview only; server enforces final pricing.
        function updateEstimate() {
            const vehicleInfo = getActiveVehicleInfo();
            const gOpt = garageEl.options[garageEl.selectedIndex];
            const rentalType = getSelectedRentalType();

            const isDaily = rentalType === 'harian';
            dailyDurationSection.classList.toggle('hidden', !isDaily);
            monthlyDurationHint.classList.toggle('hidden', isDaily);
            daysEl.disabled = !isDaily;
            daysEl.required = isDaily;

            const parts = (startEl.value || '').split('-').map(Number);
            const now = new Date();
            const startYear = parts.length === 3 ? parts[0] : now.getFullYear();
            const startMonthIdx = parts.length === 3 ? parts[1] - 1 : now.getMonth();
            const startDay = parts.length === 3 ? parts[2] : now.getDate();

            const daysInMonth = new Date(startYear, startMonthIdx + 1, 0).getDate();
            const maxDailyDays = daysInMonth - 1;
            daysEl.max = String(maxDailyDays);
            if (maxDailyRadioHint) maxDailyRadioHint.textContent = String(maxDailyDays);
            if (maxDailyLabel) maxDailyLabel.textContent = 'Maks. ' + maxDailyDays + ' Hari';
            if (monthlyDaysHint) monthlyDaysHint.textContent = String(daysInMonth);

            const rawDays = Math.min(maxDailyDays, Math.max(1, parseInt(daysEl.value || '1', 10)));
            const activeDays = isDaily ? rawDays : daysInMonth;

            endPreviewEl.value = formatLocalDate(startYear, startMonthIdx, startDay + activeDays);

            const hasValidGarage = gOpt && gOpt.value !== '';
            const isEv = Boolean(
                hasValidGarage &&
                vehicleInfo.type === 'mobil_listrik' &&
                gOpt.dataset.type === 'mobil_listrik'
            );

            evSection.classList.toggle('hidden', !isEv);
            batteryEl.disabled = !isEv;
            batteryEl.required = isEv;

            if (!hasValidGarage) {
                estBase.textContent = 'Rp 0';
                estCharge.textContent = 'Rp 0';
                estTotal.textContent = 'Rp 0';
                return;
            }

            const dailyRate = parseFloat(gOpt.dataset.rate || '0');
            const base = isDaily ? (rawDays * dailyRate) : (25 * dailyRate);

            let charge = 0;
            if (isEv) {
                const cap = vehicleInfo.capacity;
                const rem = batteryEl.value === '' ? cap : parseFloat(batteryEl.value);
                const kwhPrice = parseFloat(gOpt.dataset.kwh || '0');
                if (cap > 0 && !Number.isNaN(rem) && rem >= 0 && rem <= cap) {
                    charge = (cap - rem) * kwhPrice;
                }
            }

            estBase.textContent = formatRp(base);
            estCharge.textContent = formatRp(charge);
            estTotal.textContent = formatRp(base + charge);
        }

        vehicleModeRadios.forEach((radio) => {
            radio.addEventListener('change', function () {
                syncVehicleModeFields();
                rebuildGarageOptions(false);
                updateEstimate();
            });
        });

        if (vehicleEl) {
            vehicleEl.addEventListener('change', function () {
                rebuildGarageOptions(false);
                updateEstimate();
            });
        }

        newTypeEl.addEventListener('change', function () {
            syncVehicleModeFields();
            rebuildGarageOptions(false);
            updateEstimate();
        });

        newBatteryEl.addEventListener('input', updateEstimate);

        rentalTypeInputs.forEach((radio) => {
            radio.addEventListener('change', updateEstimate);
        });

        [garageEl, daysEl, batteryEl].forEach((el) => {
            el.addEventListener('change', updateEstimate);
            el.addEventListener('input', updateEstimate);
        });

        syncVehicleModeFields();
        rebuildGarageOptions(true);
        updateEstimate();
    })();
</script>
@endsection
