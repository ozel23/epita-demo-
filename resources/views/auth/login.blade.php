@extends('layouts.app')
@section('title','Masuk')
@section('content')
<div class="auth-shell {{ $errors->any()?'auth-has-error':'' }}">
    <section class="auth-branding" aria-label="Tentang EPITA">
        <x-brand light/>
        <span class="auth-particle particle-one" aria-hidden="true"></span><span class="auth-particle particle-two" aria-hidden="true"></span><span class="auth-particle particle-three" aria-hidden="true"></span>
        <div class="auth-brand-content">
            <div class="auth-eyebrow"><span></span> WORKSPACE OPERASIONAL</div>
            <h2>Satu kendali. <br>Operasional <br><span>lebih terarah.</span></h2>
            <p>Hubungkan alat, tim, dan pekerjaan lapangan dalam satu sistem yang dapat diandalkan.</p>
            <div class="auth-feature"><span><x-icon name="vehicle"/></span><div><strong>Setiap alat, terlacak</strong><small>Aktivitas, BBM, dan kondisi terkini.</small></div></div>
            <div class="auth-feature"><span><x-icon name="check-circle"/></span><div><strong>Setiap proses, terukur</strong><small>Persetujuan dan data yang transparan.</small></div></div>
        </div>
        <div class="auth-brand-footer"><x-icon name="shield"/><span>Akses aman sesuai peran & plant</span></div>
        <svg class="auth-wave wave-back" viewBox="0 0 90 700" preserveAspectRatio="none" aria-hidden="true"><path d="M90 0H55C-15 85 105 160 45 245S-5 370 50 450 5 605 90 700Z"/></svg>
        <svg class="auth-wave wave-front" viewBox="0 0 90 700" preserveAspectRatio="none" aria-hidden="true"><path d="M90 0H75C5 95 100 180 70 265S10 385 65 465 35 615 90 700Z"/></svg>
    </section>
    <section class="auth-form-panel">
        <div class="auth-form-top"><span>SELAMAT DATANG KEMBALI</span><x-icon name="shield"/></div>
        <div class="auth-form-content"><span class="auth-form-emblem"><x-icon name="grid"/></span><h1>Selamat Datang</h1><p>Masuk untuk melanjutkan aktivitas operasional Anda.</p>
            @if($errors->any())<x-alert title="Tidak dapat masuk.">Periksa identitas dan kata sandi, lalu coba kembali.</x-alert>@endif
            <form method="POST" action="{{ route('login') }}" class="login-form" data-loading-label="Memverifikasi akun…">
                @csrf
                <x-input name="login" label="Username atau email" autocomplete="username" :required="true" placeholder="Masukkan username atau email"/>
                <div class="field-group"><label class="form-label" for="password">Kata sandi <span class="required-mark" aria-hidden="true">*</span></label><div class="password-field"><input id="password" type="password" name="password" class="form-control {{ $errors->has('password')?'is-invalid':'' }}" autocomplete="current-password" placeholder="Masukkan kata sandi" required @if($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif><button class="password-toggle" type="button" data-password-toggle="password" aria-label="Tampilkan kata sandi" aria-pressed="false"><x-icon name="eye"/></button></div>@error('password')<div class="invalid-feedback d-block" id="password-error">{{ $message }}</div>@enderror</div>
                <div class="login-options"><label class="remember-option" for="remember"><input type="checkbox" id="remember" name="remember" {{ old('remember')?'checked':'' }}> Ingat saya</label><a href="{{ route('password.request') }}">Lupa kata sandi?</a></div>
                <x-button type="submit" class="login-submit">Masuk <x-icon name="arrow"/></x-button>
            </form>
            <div class="auth-security-note"><x-icon name="shield"/><span>Akun dikelola oleh administrator perusahaan.<br>Hubungi admin jika Anda membutuhkan akses.</span></div>
        </div>
        <div class="auth-form-footer"><span>© {{ now()->year }} EPITA</span><span>Operasional alat & VRA</span></div>
    </section>
</div>
<p class="auth-page-footer">Terhubung dengan pekerjaan. Terarah dalam keputusan.</p>
@endsection
