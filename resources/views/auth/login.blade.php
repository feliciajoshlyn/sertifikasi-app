{{-- Shared Login boundary view for approved Penyewa and Admin accounts (AuthController::showLogin). --}}
@extends('layouts.app')

@section('title', 'Login — GarageRent')

@section('content')
<div class="mx-auto max-w-md">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8">
        <h1 class="text-xl font-semibold tracking-tight text-slate-900">Sign in to your account</h1>
        <p class="mt-1 text-sm text-slate-600">Approved Penyewa and Admin accounts can sign in below.</p>

        <form method="POST" action="{{ route('login.post') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email <span class="text-rose-600" aria-hidden="true">*</span></label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password <span class="text-rose-600" aria-hidden="true">*</span></label>
                <input id="password" name="password" type="password" required
                    class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
            </div>

            <button type="submit"
                class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/20">
                Sign in
            </button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-600">
            New renter?
            <a href="{{ route('register') }}" class="font-medium text-slate-900 underline underline-offset-4 hover:text-slate-700">Register an account</a>
        </p>
    </div>
</div>
@endsection
