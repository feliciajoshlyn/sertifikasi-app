{{-- ReportIndexView (<<view>>): Admin Lihat Laporan view rendering SQL JOIN aggregations ($reportData, $filterType, $year, $month). --}}
@extends('layouts.app')

@section('title', 'Lihat Laporan — GarageRent')

@section('content')
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-lg font-semibold tracking-tight text-slate-900">Rental Reports</h1>
        </div>

        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="filter_type" class="block text-xs font-medium text-slate-600">Report Type</label>
                <select id="filter_type" name="filter_type"
                    class="mt-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 focus:border-slate-900 focus:outline-none">
                    <option value="monthly" @selected($filterType === 'monthly')>Laporan per Bulan</option>
                    <option value="daily" @selected($filterType === 'daily')>Laporan per Hari</option>
                    <option value="garage_type" @selected($filterType === 'garage_type')>Laporan per Tipe Garasi</option>
                    <option value="renter" @selected($filterType === 'renter')>Laporan per Penyewa</option>
                </select>
            </div>

            <div>
                <label for="year" class="block text-xs font-medium text-slate-600">Year</label>
                <input id="year" name="year" type="number" min="2020" max="2035" value="{{ $year }}"
                    class="mt-1 w-24 rounded-lg border border-slate-300 px-3 py-1.5 text-sm tabular-nums text-slate-900 focus:border-slate-900 focus:outline-none">
            </div>

            <div>
                <label for="month" class="block text-xs font-medium text-slate-600">Month</label>
                <input id="month" name="month" type="number" min="1" max="12" value="{{ $month }}"
                    class="mt-1 w-20 rounded-lg border border-slate-300 px-3 py-1.5 text-sm tabular-nums text-slate-900 focus:border-slate-900 focus:outline-none">
            </div>

            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-1.5 text-sm font-medium text-white hover:bg-slate-800">
                Apply
            </button>
        </form>
    </div>

    @if (empty($reportData))
        <p class="mt-6 rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-600">
            No rental data found for the selected report filter.
        </p>
    @else
        <div class="mt-5 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        @if ($filterType === 'monthly')
                            <th class="py-2.5 pr-4">Month</th>
                            <th class="py-2.5 pr-4">Rentals</th>
                            <th class="py-2.5 pr-4">Base Revenue</th>
                            <th class="py-2.5 pr-4">EV Charging</th>
                            <th class="py-2.5 pr-4">Extensions</th>
                            <th class="py-2.5 text-right">Total Revenue</th>
                        @elseif ($filterType === 'daily')
                            <th class="py-2.5 pr-4">Date</th>
                            <th class="py-2.5 pr-4">Rentals</th>
                            <th class="py-2.5 pr-4">Base Revenue</th>
                            <th class="py-2.5 pr-4">EV Charging</th>
                            <th class="py-2.5 pr-4">Extensions</th>
                            <th class="py-2.5 text-right">Total Revenue</th>
                        @elseif ($filterType === 'garage_type')
                            <th class="py-2.5 pr-4">Garage Type</th>
                            <th class="py-2.5 pr-4">Total Rentals</th>
                            <th class="py-2.5 text-right">Total Revenue</th>
                        @else
                            <th class="py-2.5 pr-4">Renter</th>
                            <th class="py-2.5 pr-4">Email</th>
                            <th class="py-2.5 pr-4">Total Rentals</th>
                            <th class="py-2.5 text-right">Total Revenue</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($reportData as $row)
                        <tr>
                            @if ($filterType === 'monthly')
                                <td class="py-3 pr-4 font-medium tabular-nums text-slate-900">{{ $row->month }}</td>
                                <td class="py-3 pr-4 tabular-nums text-slate-700">{{ $row->total_rentals }}</td>
                                <td class="py-3 pr-4 tabular-nums text-slate-700">Rp {{ number_format((float) $row->base_revenue, 0, ',', '.') }}</td>
                                <td class="py-3 pr-4 tabular-nums text-slate-700">Rp {{ number_format((float) $row->charging_revenue, 0, ',', '.') }}</td>
                                <td class="py-3 pr-4 tabular-nums text-slate-700">Rp {{ number_format((float) $row->extension_revenue, 0, ',', '.') }}</td>
                                <td class="py-3 text-right font-semibold tabular-nums text-slate-900">Rp {{ number_format((float) $row->total_revenue, 0, ',', '.') }}</td>
                            @elseif ($filterType === 'daily')
                                <td class="py-3 pr-4 font-medium tabular-nums text-slate-900">{{ $row->rental_date }}</td>
                                <td class="py-3 pr-4 tabular-nums text-slate-700">{{ $row->total_rentals }}</td>
                                <td class="py-3 pr-4 tabular-nums text-slate-700">Rp {{ number_format((float) $row->base_revenue, 0, ',', '.') }}</td>
                                <td class="py-3 pr-4 tabular-nums text-slate-700">Rp {{ number_format((float) $row->charging_revenue, 0, ',', '.') }}</td>
                                <td class="py-3 pr-4 tabular-nums text-slate-700">Rp {{ number_format((float) $row->extension_revenue, 0, ',', '.') }}</td>
                                <td class="py-3 text-right font-semibold tabular-nums text-slate-900">Rp {{ number_format((float) $row->total_revenue, 0, ',', '.') }}</td>
                            @elseif ($filterType === 'garage_type')
                                <td class="py-3 pr-4 font-medium text-slate-900">{{ $row->garage_type }}</td>
                                <td class="py-3 pr-4 tabular-nums text-slate-700">{{ $row->total_rentals }}</td>
                                <td class="py-3 text-right font-semibold tabular-nums text-slate-900">Rp {{ number_format((float) $row->total_revenue, 0, ',', '.') }}</td>
                            @else
                                <td class="py-3 pr-4 font-medium text-slate-900">{{ $row->renter_name }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $row->renter_email }}</td>
                                <td class="py-3 pr-4 tabular-nums text-slate-700">{{ $row->total_rentals }}</td>
                                <td class="py-3 text-right font-semibold tabular-nums text-slate-900">Rp {{ number_format((float) $row->total_revenue, 0, ',', '.') }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
