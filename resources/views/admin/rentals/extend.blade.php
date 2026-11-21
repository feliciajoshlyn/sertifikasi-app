{{-- AdminExtendView (<<view>>): Admin-only Perpanjang Sewa view ($activeRentals with no maximum day limit). --}}
@extends('layouts.app')

@section('title', 'Perpanjang Sewa — GarageRent')

@section('content')
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
    <h1 class="text-lg font-semibold tracking-tight text-slate-900">Perpanjang Sewa (Admin Only)</h1>
    <p class="mt-1 text-sm text-slate-600">Extend any rental without a maximum day limit using its historical daily rate snapshot.</p>

    @if ($activeRentals->isEmpty())
        <p class="mt-6 rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-600">
            No rental bookings exist yet.
        </p>
    @else
        <div class="mt-5 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="py-2.5 pr-4">Garage</th>
                        <th class="py-2.5 pr-4">Renter</th>
                        <th class="py-2.5 pr-4">Vehicle</th>
                        <th class="py-2.5 pr-4">Current End Date</th>
                        <th class="py-2.5 pr-4">Extensions</th>
                        <th class="py-2.5">Extend Rental</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($activeRentals as $rental)
                        <tr>
                            <td class="py-3 pr-4 font-semibold tabular-nums text-slate-900">{{ $rental->garage->code }}</td>
                            <td class="py-3 pr-4 text-slate-700">{{ $rental->user->name }}</td>
                            <td class="py-3 pr-4 text-slate-700">{{ $rental->vehicle?->brand_model ?? 'Deleted vehicle' }}</td>
                            <td class="py-3 pr-4 tabular-nums text-slate-900">{{ $rental->end_date->format('Y-m-d') }}</td>
                            <td class="py-3 pr-4 tabular-nums text-slate-600">{{ $rental->extensions->count() }}</td>
                            <td class="py-3">
                                <form method="POST" action="{{ route('admin.rentals.extend', $rental) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="date" name="new_end_date"
                                        min="{{ $rental->end_date->copy()->addDay()->toDateString() }}"
                                        required
                                        class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs tabular-nums text-slate-900 focus:border-slate-900 focus:outline-none">
                                    <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required
                                        class="max-w-44 text-xs text-slate-600 file:mr-2 file:rounded-md file:border-0 file:bg-slate-100 file:px-2.5 file:py-1 file:text-xs file:font-medium file:text-slate-800">
                                    <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800">
                                        Extend
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
