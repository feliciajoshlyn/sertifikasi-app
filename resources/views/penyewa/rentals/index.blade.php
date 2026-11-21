{{-- Lihat Riwayat Sewa view: displays eager-loaded rental bookings, pricing snapshots, and extensions. --}}
@extends('layouts.app')

@section('title', 'Riwayat Sewa — GarageRent')

@section('content')
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold tracking-tight text-slate-900">Rental History</h1>
            <p class="mt-0.5 text-sm text-slate-600">All garage bookings, EV charging fees, and Admin-approved extensions.</p>
        </div>
        <a href="{{ route('penyewa.rentals.create') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Sewa Garasi Baru
        </a>
    </div>

    @if ($rentals->isEmpty())
        <p class="mt-6 rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-600">
            You have no rental bookings yet. Click <strong>Sewa Garasi Baru</strong> to book an available garage.
        </p>
    @else
        <div class="mt-5 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="py-2.5 pr-4">Garage</th>
                        <th class="py-2.5 pr-4">Vehicle</th>
                        <th class="py-2.5 pr-4">Type</th>
                        <th class="py-2.5 pr-4">Period</th>
                        <th class="py-2.5 pr-4">Base</th>
                        <th class="py-2.5 pr-4">EV Charge</th>
                        <th class="py-2.5 pr-4">Total</th>
                        <th class="py-2.5 pr-4">Extensions</th>
                        <th class="py-2.5 text-right">Proof</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($rentals as $rental)
                        <tr>
                            <td class="py-3 pr-4 font-semibold tabular-nums text-slate-900">{{ $rental->garage->code }}</td>
                            <td class="py-3 pr-4 text-slate-700">{{ $rental->vehicle?->brand_model ?? 'Deleted vehicle' }}</td>
                            <td class="py-3 pr-4">
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $rental->rental_type }}</span>
                            </td>
                            <td class="py-3 pr-4 tabular-nums text-slate-600">
                                {{ $rental->start_date->format('Y-m-d') }} &rarr; {{ $rental->end_date->format('Y-m-d') }}
                            </td>
                            <td class="py-3 pr-4 tabular-nums text-slate-700">Rp {{ number_format($rental->base_rental_cost, 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 tabular-nums text-slate-700">Rp {{ number_format($rental->charging_cost, 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 font-semibold tabular-nums text-slate-900">Rp {{ number_format($rental->total_cost, 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 tabular-nums text-slate-600">{{ $rental->extensions->count() }}</td>
                            <td class="py-3 text-right">
                                <a href="{{ asset('storage/' . $rental->payment_proof_path) }}" target="_blank" class="text-xs font-medium text-slate-900 underline underline-offset-4">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
