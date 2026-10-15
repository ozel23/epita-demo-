<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#114F9D">
    <title>@yield('title','Dashboard') · EPITA</title>
    <link rel="preload" href="{{ asset('assets/fonts/inter-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('assets/bootstrap.min.css') }}?v={{ filemtime(public_path('assets/bootstrap.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}?v={{ filemtime(public_path('assets/app.css')) }}">
</head>
<body>
<a href="#main-content" class="skip-link">Lewati ke konten utama</a>
<div id="navigation-progress" aria-hidden="true"></div>
@auth
@php($unread = auth()->user()->unreadNotifications()->count())
<aside id="sidebar" aria-label="Navigasi utama">
    <div class="sidebar-brand"><x-brand light/></div>
    <div class="sidebar-workspace"><span class="workspace-symbol"><x-icon name="vehicle"/></span><div><strong>Operations Workspace</strong><small>{{ auth()->user()->plants->firstWhere('id',session('active_plant_id'))->name ?? 'Seluruh plant yang diizinkan' }}</small></div></div>
    <nav class="sidebar-nav">
        <div class="nav-caption">RINGKASAN</div>
        <a href="/dashboard" title="Dashboard" class="{{ request()->is('dashboard')?'selected':'' }}" @if(request()->is('dashboard')) aria-current="page" @endif><x-icon name="grid"/><span class="nav-label">Dashboard</span></a>
        <div class="nav-caption">OPERASIONAL</div>
        @can('assignment.manage')<a href="/work-plans" title="Rencana Kerja" class="{{ request()->is('work-plans*')?'selected':'' }}"><x-icon name="calendar"/><span class="nav-label">Rencana Kerja</span></a>@endcan
        <a href="/assignments" title="Penugasan" class="{{ request()->is('assignments*')?'selected':'' }}"><x-icon name="users"/><span class="nav-label">Penugasan</span></a>
        <a href="/daily-reports" title="Laporan Harian" class="{{ request()->is('daily-reports*')?'selected':'' }}"><x-icon name="clipboard"/><span class="nav-label">Laporan Harian</span></a>
        @if(auth()->user()->can('daily-report.verify')||auth()->user()->can('daily-report.approve'))<a href="/approvals" title="Persetujuan" class="{{ request()->is('approvals')?'selected':'' }}"><x-icon name="check-circle"/><span class="nav-label">Persetujuan</span></a>@endif
        <div class="nav-caption">MAINTENANCE</div>
        <a href="/breakdowns" title="Laporan Breakdown" class="{{ request()->is('breakdowns*')?'selected':'' }}"><x-icon name="alert"/><span class="nav-label">Laporan Breakdown</span></a>
        <a href="/maintenance/work-orders" title="Work Order" class="{{ request()->is('maintenance*')?'selected':'' }}"><x-icon name="wrench"/><span class="nav-label">Work Order</span></a>
        @if(auth()->user()->can('sap.import')||auth()->user()->can('sap.reconcile'))<div class="nav-caption">INTEGRASI SAP</div>@can('sap.import')<a href="/sap/imports" title="Import IW47" class="{{ request()->is('sap/imports*')?'selected':'' }}"><x-icon name="upload"/><span class="nav-label">Import IW47</span></a>@endcan @can('sap.reconcile')<a href="/sap/reconciliation" title="Rekonsiliasi" class="{{ request()->is('sap/reconciliation*')?'selected':'' }}"><x-icon name="swap"/><span class="nav-label">Rekonsiliasi</span></a>@endcan @endif
        <div class="nav-caption">ADMINISTRASI</div>
        @can('report.export')<a href="/reports" title="Laporan & Ekspor" class="{{ request()->is('reports*')?'selected':'' }}"><x-icon name="chart"/><span class="nav-label">Laporan & Ekspor</span></a><a href="/background-tasks" title="Proses Latar Belakang" class="{{ request()->is('background-tasks*')?'selected':'' }}"><x-icon name="download"/><span class="nav-label">Proses Latar Belakang</span></a>@endcan
        @can('equipment.view')<a href="/master/equipment" title="Master Data" class="{{ request()->is('master*')?'selected':'' }}"><x-icon name="vehicle"/><span class="nav-label">Master Data</span></a>@endcan
        @can('user.manage')<a href="/users" title="Pengguna & Akses" class="{{ request()->is('users*','roles*')?'selected':'' }}"><x-icon name="shield"/><span class="nav-label">Pengguna & Akses</span></a>@endcan
        @can('audit.view')<a href="/audit-logs" title="Audit Log" class="{{ request()->is('audit-logs*')?'selected':'' }}"><x-icon name="clock"/><span class="nav-label">Audit Log</span></a>@endcan
    </nav>
    <div class="sidebar-footer"><span class="online-dot"></span><span>Workspace aktif<small>Asia/Jakarta · WIB</small></span><x-icon name="shield"/></div>
</aside>
<button type="button" id="sidebar-backdrop" aria-label="Tutup navigasi" hidden></button>
<div class="workspace">
    <header class="topbar">
        <div class="topbar-leading"><button type="button" class="icon-button" id="nav-toggle" aria-label="Buka navigasi" aria-controls="sidebar" aria-expanded="false"><x-icon name="menu"/></button><x-breadcrumb :title="trim($__env->yieldContent('title','Dashboard'))"/></div>
        <div class="topbar-actions"><a class="plant-switch" href="/select-plant"><x-icon name="pin"/><span>{{ auth()->user()->plants->firstWhere('id',session('active_plant_id'))->code ?? 'Semua plant' }}</span><x-icon name="chevron"/></a><a class="notification-button icon-button" href="/notifications" aria-label="Notifikasi, {{ $unread }} belum dibaca"><x-icon name="bell"/>@if($unread)<span class="notification-count">{{ $unread>99?'99+':$unread }}</span>@endif</a><div class="profile-menu"><button type="button" class="profile-trigger" aria-expanded="false" aria-controls="profile-dropdown"><span class="avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><span class="profile-caption"><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(auth()->user()->roles->first()->name??'Pengguna') }}</small></span><x-icon name="chevron"/></button><div class="profile-dropdown" id="profile-dropdown" hidden><a href="/profile"><x-icon name="users"/> Profil & keamanan</a><a href="/select-plant"><x-icon name="pin"/> Ganti plant</a><form method="POST" action="/logout">@csrf<button type="submit"><x-icon name="logout"/> Keluar dari akun</button></form></div></div></div>
    </header>
    <main id="main-content" tabindex="-1">
        @if(session('success'))<x-toast :message="session('success')"/><noscript><x-alert variant="success">{{ session('success') }}</x-alert></noscript>@endif
        @if($errors->any())<x-alert title="Periksa kembali isian Anda."><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>@endif
        @yield('content')
    </main>
    <footer class="page-footer"><span><strong>EPITA</strong> · Sistem Operasional Alat & VRA</span><span>Workspace operasional · {{ now()->format('Y') }}</span></footer>
</div>
@else
<main class="auth-main" id="main-content" tabindex="-1">@yield('content')</main>
@endauth
<noscript><style>@media(max-width:800px){#sidebar{position:static;transform:none;width:100%;max-height:none}.sidebar-nav{display:flex;flex-wrap:wrap;padding:10px}.sidebar-nav a{margin:3px}.nav-caption,.sidebar-workspace,.sidebar-footer,#nav-toggle{display:none}}.profile-menu .profile-dropdown{display:block;position:static;box-shadow:none}.profile-menu .profile-trigger{display:none}</style></noscript><x-confirmation-dialog/>
@if($errors->any())<script type="application/json" id="form-validation">@json($errors->messages())</script>@endif
<script src="{{ asset('assets/bootstrap.bundle.min.js') }}?v={{ filemtime(public_path('assets/bootstrap.bundle.min.js')) }}" defer></script>
<script src="{{ asset('assets/app.js') }}?v={{ filemtime(public_path('assets/app.js')) }}" defer></script>
<script src="{{ asset('assets/motion.js') }}?v={{ filemtime(public_path('assets/motion.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
