@php
    $props = $page['props'] ?? [];
    $situs = $props['situs'] ?? [];
    $seo = $props['seo'] ?? null;
    $hex = fn ($v, $bawaan) => is_string($v) && preg_match('/^#[0-9A-Fa-f]{6}$/', $v) ? $v : $bawaan;
    $warnaUtama = $hex($situs['warna_utama'] ?? null, '#BE185D');
    $warnaPendamping = $hex($situs['warna_pendamping'] ?? null, '#EC4899');
    $namaSitus = $situs['nama_aplikasi'] ?? config('app.name');
    $judul = $seo['judul'] ?? $namaSitus;
    $tema = $appearance ?? 'system';
@endphp
<!DOCTYPE html>
<html lang="id" @class(['dark' => $tema === 'dark']) data-tema="{{ $tema }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light dark">

        {{-- Terapkan tema sebelum render pertama (mencegah kilatan tema & hydration mismatch). --}}
        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
            (function () {
                var tema = document.documentElement.getAttribute('data-tema') || 'system';
                try { tema = localStorage.getItem('appearance') || tema; } catch (e) {}
                var gelap = tema === 'dark' || (tema === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', gelap);
                document.documentElement.style.colorScheme = gelap ? 'dark' : 'light';
            })();
        </script>

        <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
            :root { --merek: {{ $warnaUtama }}; --merek-2: {{ $warnaPendamping }}; }
            html { background-color: #fff7fb; }
            html.dark { background-color: #121013; }
        </style>

        <title>{{ $judul }}</title>
        @if ($seo)
            <meta data-inertia="description" name="description" content="{{ $seo['deskripsi'] }}">
            <meta data-inertia="robots" name="robots" content="{{ $seo['robots'] }}">
            <link data-inertia="canonical" rel="canonical" href="{{ $seo['canonical'] }}">
            <meta data-inertia="og:type" property="og:type" content="{{ $seo['og']['type'] }}">
            <meta data-inertia="og:site_name" property="og:site_name" content="{{ $seo['og']['site_name'] }}">
            <meta data-inertia="og:locale" property="og:locale" content="{{ $seo['og']['locale'] }}">
            <meta data-inertia="og:title" property="og:title" content="{{ $judul }}">
            <meta data-inertia="og:description" property="og:description" content="{{ $seo['deskripsi'] }}">
            <meta data-inertia="og:url" property="og:url" content="{{ $seo['canonical'] }}">
            <meta data-inertia="og:image" property="og:image" content="{{ $seo['og']['image'] }}">
            <meta data-inertia="og:image:alt" property="og:image:alt" content="{{ $seo['og']['image_alt'] }}">
            <meta data-inertia="twitter:card" name="twitter:card" content="{{ $seo['twitter']['card'] }}">
            <meta data-inertia="twitter:title" name="twitter:title" content="{{ $judul }}">
            <meta data-inertia="twitter:description" name="twitter:description" content="{{ $seo['deskripsi'] }}">
            <meta data-inertia="twitter:image" name="twitter:image" content="{{ $seo['og']['image'] }}">
            @foreach (($seo['verifikasi'] ?? []) as $mesin => $kode)
                <meta name="{{ $mesin === 'google' ? 'google-site-verification' : 'msvalidate.01' }}" content="{{ $kode }}">
            @endforeach
            @foreach (($seo['json_ld'] ?? []) as $i => $data)
                <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
            @endforeach
        @else
            <meta data-inertia="robots" name="robots" content="noindex,nofollow">
        @endif

        @if (! empty($situs['favicon']))
            <link rel="icon" href="{{ $situs['favicon'] }}" type="image/png">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @endif
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
