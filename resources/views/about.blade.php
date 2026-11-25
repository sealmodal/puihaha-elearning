<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>About — Puihaha Learning</title>
    @include('partials.brand-head')
</head>

<body class="antialiased min-h-screen flex flex-col">
    <header class="ph-topnav">
        <div class="ph-container px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-3">
            <a href="{{ route('landing') }}" class="shrink-0 no-underline">
                @include('partials.brand-logo')
            </a>

            <div class="flex items-center gap-2 sm:gap-3 text-xs sm:text-sm font-medium">
                <a href="{{ route('about') }}" class="ph-nav-link">About</a>
                @auth
                    <a href="{{ route('portal.home') }}" class="ph-nav-link">Courses</a>
                    <a href="{{ route('dashboard') }}" class="ph-btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="ph-nav-pill">Log in</a>
                    <a href="{{ route('register') }}" class="ph-btn-primary">Register</a>
                @endauth
            </div>
        </div>
    </header>
    <div class="lg:col-span-5">
        <div style="height: 180px;"
            class="rounded-2xl overflow-hidden border border-slate-700/80 bg-slate-900/80 flex items-center justify-center">
            <img src="https://images.pexels.com/photos/4778662/pexels-photo-4778662.jpeg" alt="Puihaha Learning Hero"
                style="height: 180px; width: 100%; object-fit: cover;">
        </div>
    </div>



    <main class="flex-1 ph-container max-w-4xl px-4 sm:px-6 lg:px-8 py-10">
        <div class="ph-card p-6 sm:p-8 space-y-6">
            <div class="space-y-2">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">Platform
                    Overview</span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">About Puihaha Learning</h1>
                <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                    Puihaha Learning pairs structured courses with a Membership Management and Transaction Processing
                    System. Members register under Student, Regular, Senior Citizen, or Corporate categories and unlock
                    courses once payment is approved.
                </p>
            </div>

            <div class="ph-grid-2 text-sm">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="font-bold text-slate-900">Basic — 1 Course</div>
                    <p class="text-xs text-slate-600 mt-1">Enroll in up to 1 active course at a time.</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="font-bold text-slate-900">Silver — 3 Courses</div>
                    <p class="text-xs text-slate-600 mt-1">Enroll in up to 3 active courses at a time.</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="font-bold text-slate-900">Gold — 5 Courses</div>
                    <p class="text-xs text-slate-600 mt-1">Enroll in up to 5 active courses at a time.</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="font-bold text-slate-900">Platinum — All Courses</div>
                    <p class="text-xs text-slate-600 mt-1">Unlimited course enrollments across the catalog.</p>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t border-slate-200 py-4 px-5 text-xs text-slate-500">
        <div class="ph-container flex flex-col sm:flex-row items-center justify-between gap-2">
            <span><strong>Puihaha Learning</strong> — E-Learning &amp; Membership TPS</span>
            <span>Basic (1) · Silver (3) · Gold (5) · Platinum (All)</span>
        </div>
    </footer>
</body>

</html>