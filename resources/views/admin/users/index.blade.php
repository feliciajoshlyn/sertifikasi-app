{{-- Verifikasi Pendaftar view: lists Penyewa registrations (pending first) and locks status changes once approved. --}}
@extends('layouts.app')

@section('title', 'Verifikasi Pendaftar — GarageRent')

@section('content')
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
    <h1 class="text-lg font-semibold tracking-tight text-slate-900">Verifikasi Pendaftar (KTP Review)</h1>
    <p class="mt-1 text-sm text-slate-600">Approve or reject Penyewa accounts after verifying their 16-digit NIK.</p>

    @if ($renters->isEmpty())
        <p class="mt-6 rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-600">
            No Penyewa registrations found.
        </p>
    @else
        <div class="mt-5 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="py-2.5 pr-4">NIK</th>
                        <th class="py-2.5 pr-4">Name</th>
                        <th class="py-2.5 pr-4">Phone</th>
                        <th class="py-2.5 pr-4">Email</th>
                        <th class="py-2.5 pr-4">Status</th>
                        <th class="py-2.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($renters as $renter)
                        <tr>
                            <td class="py-3 pr-4 font-mono text-xs tabular-nums text-slate-800">{{ $renter->nik ?? '—' }}</td>
                            <td class="py-3 pr-4 font-medium text-slate-900">{{ $renter->name }}</td>
                            <td class="py-3 pr-4 tabular-nums text-slate-600">{{ $renter->phone ?? '—' }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $renter->email }}</td>
                            <td class="py-3 pr-4">
                                <span class="rounded-md px-2 py-0.5 text-xs font-medium
                                    {{ $renter->status === 'approved' ? 'bg-emerald-50 text-emerald-700' : ($renter->status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-800') }}">
                                    {{ $renter->status }}
                                </span>
                            </td>
                            <td class="py-3 text-right">
                                @if ($renter->status === 'approved')
                                    <span class="text-xs text-slate-400">Verified</span>
                                @else
                                    <form method="POST" action="{{ route('admin.users.status', $renter) }}" class="inline-flex gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" name="status" value="approved"
                                            class="rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-emerald-700">
                                            Approve
                                        </button>
                                        @if ($renter->status === 'pending')
                                            <button type="submit" name="status" value="rejected"
                                                class="rounded-md bg-rose-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-rose-700">
                                                Reject
                                            </button>
                                        @endif
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
