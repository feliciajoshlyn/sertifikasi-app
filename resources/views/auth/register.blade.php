{{-- Registrasi Akun view: collects 16-digit KTP NIK and creates pending Penyewa accounts awaiting Admin verification. --}}
@extends('layouts.app')

@section('title', 'Register — GarageRent')

@section('content')
<div class="mx-auto max-w-md">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8">
        <h1 class="text-xl font-semibold tracking-tight text-slate-900">Register as Penyewa</h1>
        <p class="mt-1 text-sm text-slate-600">Submit your 16-digit KTP NIK. An Admin will verify your account before login.</p>

        <form method="POST" action="{{ route('register.post') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="nik" class="block text-sm font-medium text-slate-700">NIK (16 digits) <span class="text-rose-600" aria-hidden="true">*</span></label>
                <input id="nik" name="nik" type="text" inputmode="numeric" maxlength="16" value="{{ old('nik') }}" required
                    placeholder="3578010101900001"
                    class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm tabular-nums text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Full Name <span class="text-rose-600" aria-hidden="true">*</span></label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required
                    class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700">Phone Number <span class="text-rose-600" aria-hidden="true">*</span></label>
                <input id="phone" name="phone" type="text" value="{{ old('phone') }}" required
                    class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm tabular-nums text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email <span class="text-rose-600" aria-hidden="true">*</span></label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                    class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password <span class="text-rose-600" aria-hidden="true">*</span></label>
                <input id="password" name="password" type="password" required
                    class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm Password <span class="text-rose-600" aria-hidden="true">*</span></label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
            </div>

            <button type="submit"
                class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/20">
                Submit Registration
            </button>
        </form>
    </div>
</div>
@endsection
