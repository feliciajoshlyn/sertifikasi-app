{{-- Shared application layout: renders role-aware navigation (Admin, Penyewa, Guest) and session/validation alerts. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Garage Rental System')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5 sm:px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 text-sm font-semibold tracking-tight text-slate-900">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-slate-900 text-xs font-bold text-white">GR</span>
                <span>GarageRent</span>
            </a>

            {{-- Mirror role: middleware boundaries by displaying navigation links for the user's active role only. --}}
            @auth
                <nav aria-label="Primary" class="flex flex-wrap items-center gap-1 sm:gap-2">
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.users.index') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request()->routeIs('admin.users.*') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Verifikasi Pendaftar</a>
                        <a href="{{ route('admin.garages.index') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request()->routeIs('admin.garages.*') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Buat Garasi</a>
                        <a href="{{ route('admin.rentals.extend.form') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request()->routeIs('admin.rentals.*') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Perpanjang Sewa</a>
                        <a href="{{ route('admin.reports.index') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request()->routeIs('admin.reports.*') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Lihat Laporan</a>
                    @else
                        <a href="{{ route('penyewa.rentals.create') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request()->routeIs('penyewa.rentals.create') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Sewa Garasi Baru</a>
                        <a href="{{ route('penyewa.rentals.index') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request()->routeIs('penyewa.rentals.index') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Riwayat Sewa</a>
                    @endif

                    <span class="mx-1 hidden h-4 w-px bg-slate-200 sm:inline-block"></span>
                    <span class="hidden text-xs text-slate-500 sm:inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900/15">
                            Logout
                        </button>
                    </form>
                </nav>
            @else
                <nav aria-label="Guest" class="flex items-center gap-2">
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">Login</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-slate-900 px-3.5 py-1.5 text-sm font-medium text-white hover:bg-slate-800">Register</a>
                </nav>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        @if (session('success'))
            <div role="status" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div role="alert" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                <p class="font-medium">Please check the highlighted fields:</p>
                <ul class="mt-1.5 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
