<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#143564">
    <meta name="description" content="Sistem Inventory Management gudang tunggal Inixindo Bandung — RBAC berjenjang, masking harga modal, audit trail, dan dashboard analitik real-time.">
    <title>Inventory Management — Inixindo Bandung</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/logo.png') }}">
    <script>document.documentElement.classList.add('js');</script>
    @verbatim
    <style>
        :root {
            --navy: #143564;
            --navy-700: #0f294d;
            --navy-900: #0a1c36;
            --red: #EA2339;
            --sky: #5CAAD5;
            --green: #1F9254;
            --ink: #12213a;
            --muted: #5b6b82;
            --bg: #f6f8fb;
            --card: #ffffff;
            --border: #e6ebf2;
            --ring: rgba(20, 53, 100, .12);
            --shadow: 0 1px 2px rgba(16, 33, 58, .04), 0 12px 32px -12px rgba(16, 33, 58, .18);
            --radius: 18px;
            --font: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --ink: #eaf1fb;
                --muted: #9fb0c9;
                --bg: #0a1626;
                --card: #10233d;
                --border: #1d3350;
                --ring: rgba(92, 170, 213, .18);
                --shadow: 0 1px 2px rgba(0,0,0,.3), 0 18px 40px -18px rgba(0,0,0,.55);
            }
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: var(--font);
            color: var(--ink);
            background: var(--bg);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }
        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }
        .wrap { width: 100%; max-width: 1140px; margin-inline: auto; padding-inline: 24px; }

        /* ---------- Buttons ---------- */
        .btn {
            display: inline-flex; align-items: center; gap: .55rem;
            font-weight: 650; font-size: .98rem; line-height: 1;
            padding: .95rem 1.5rem; border-radius: 999px;
            border: 1px solid transparent; cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
            will-change: transform;
        }
        .btn svg { width: 18px; height: 18px; }
        .btn-primary {
            background: var(--red); color: #fff;
            box-shadow: 0 10px 24px -10px rgba(234, 35, 57, .7);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 16px 30px -12px rgba(234, 35, 57, .75); }
        .btn-ghost {
            background: rgba(255,255,255,.08); color: #fff;
            border-color: rgba(255,255,255,.28);
            backdrop-filter: blur(6px);
        }
        .btn-ghost:hover { transform: translateY(-2px); background: rgba(255,255,255,.16); }
        .btn-solid {
            background: var(--navy); color: #fff;
            box-shadow: 0 10px 24px -12px rgba(20, 53, 100, .8);
        }
        .btn-solid:hover { transform: translateY(-2px); }

        /* ---------- Header ---------- */
        header {
            position: sticky; top: 0; z-index: 50;
            backdrop-filter: saturate(160%) blur(12px);
            background: color-mix(in srgb, var(--bg) 78%, transparent);
            border-bottom: 1px solid var(--border);
        }
        .nav { display: flex; align-items: center; justify-content: space-between; height: 74px; }
        .brand { display: flex; align-items: center; gap: .75rem; }
        .brand img { height: 40px; width: auto; }
        .brand b { font-size: 1.02rem; font-weight: 750; letter-spacing: -.01em; }
        .brand span { display: block; font-size: .74rem; font-weight: 550; color: var(--muted); letter-spacing: .02em; }
        .nav-links { display: flex; align-items: center; gap: 2rem; }
        .nav-links a.link { font-weight: 550; color: var(--muted); font-size: .95rem; transition: color .15s; }
        .nav-links a.link:hover { color: var(--ink); }

        /* ---------- Hero ---------- */
        .hero {
            position: relative; overflow: hidden;
            background:
                radial-gradient(1100px 520px at 82% -8%, rgba(92,170,213,.30), transparent 60%),
                radial-gradient(760px 420px at 8% 12%, rgba(234,35,57,.16), transparent 58%),
                linear-gradient(160deg, var(--navy-900), var(--navy) 55%, var(--navy-700));
            color: #fff;
        }
        .hero::after {
            content: ""; position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
            background-size: 46px 46px;
            mask-image: radial-gradient(circle at 60% 30%, #000, transparent 72%);
            pointer-events: none;
        }
        .hero .wrap { position: relative; z-index: 1; padding-top: 88px; padding-bottom: 104px; }
        .hero-grid { display: grid; grid-template-columns: 1.15fr .85fr; gap: 56px; align-items: center; }
        .eyebrow {
            display: inline-flex; align-items: center; gap: .5rem;
            font-size: .8rem; font-weight: 650; letter-spacing: .04em; text-transform: uppercase;
            color: #cfe2f2; background: rgba(255,255,255,.09);
            border: 1px solid rgba(255,255,255,.18);
            padding: .45rem .85rem; border-radius: 999px; margin-bottom: 22px;
        }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--green); box-shadow: 0 0 0 4px rgba(31,146,84,.25); }
        h1 {
            font-size: clamp(2.3rem, 4.6vw, 3.55rem); line-height: 1.06;
            letter-spacing: -.025em; font-weight: 800; margin: 0 0 20px;
        }
        h1 .accent {
            background: linear-gradient(100deg, #7fc0e6, var(--sky));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .lede { font-size: 1.14rem; color: #d6e2ef; max-width: 560px; margin: 0 0 34px; }
        .hero-cta { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; }
        .hero-note { margin-top: 22px; font-size: .86rem; color: #aec6dd; display: flex; align-items: center; gap: .5rem; }

        /* Hero visual — kartu KPI mock */
        .panel {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.14);
            border-radius: 22px; padding: 20px;
            box-shadow: 0 30px 60px -30px rgba(0,0,0,.6);
            backdrop-filter: blur(8px);
        }
        .panel-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .panel-head span { font-weight: 650; font-size: .95rem; color: #eaf3fb; }
        .chips { display: flex; gap: 6px; }
        .chips i { width: 10px; height: 10px; border-radius: 50%; display: block; opacity: .9; }
        .kpis { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
        .kpi { background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1); border-radius: 14px; padding: 14px; }
        .kpi small { color: #b9cee1; font-size: .74rem; font-weight: 600; letter-spacing: .02em; }
        .kpi b { display: block; font-size: 1.45rem; font-weight: 800; margin-top: 4px; letter-spacing: -.02em; }
        .kpi.k-red b { color: #ff8a97; }
        .kpi.k-sky b { color: #9fd4f0; }
        .kpi.k-green b { color: #7fe0aa; }
        .bars { display: flex; align-items: flex-end; gap: 9px; height: 92px; padding-top: 6px; }
        .bars i { flex: 1; border-radius: 6px 6px 0 0; background: linear-gradient(var(--sky), rgba(92,170,213,.35)); display: block; animation: grow 1s cubic-bezier(.2,.7,.2,1) both; }
        @keyframes grow { from { height: 0 !important; opacity: .2; } }

        /* ---------- Marquee / stat strip ---------- */
        .stripe { border-block: 1px solid var(--border); background: var(--card); }
        .stripe .wrap { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; padding-block: 30px; }
        .stat b { font-size: 1.7rem; font-weight: 800; letter-spacing: -.02em; display: block; }
        .stat b .u { font-size: 1rem; color: var(--muted); font-weight: 700; }
        .stat small { color: var(--muted); font-weight: 550; font-size: .9rem; }

        /* ---------- Sections ---------- */
        section.block { padding-block: 92px; }
        .head { max-width: 680px; margin: 0 auto 56px; text-align: center; }
        .head .eyebrow { color: var(--navy); background: var(--ring); border-color: transparent; }
        @media (prefers-color-scheme: dark) { .head .eyebrow { color: var(--sky); } }
        .head h2 { font-size: clamp(1.8rem, 3.2vw, 2.5rem); letter-spacing: -.025em; font-weight: 800; margin: 0 0 14px; }
        .head p { color: var(--muted); font-size: 1.08rem; margin: 0; }

        .grid { display: grid; gap: 22px; }
        .cols-3 { grid-template-columns: repeat(3, 1fr); }
        .card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 28px;
            box-shadow: var(--shadow);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .card:hover { transform: translateY(-4px); border-color: color-mix(in srgb, var(--navy) 22%, var(--border)); }
        .ico {
            width: 50px; height: 50px; border-radius: 14px;
            display: grid; place-items: center; margin-bottom: 18px;
        }
        .ico svg { width: 25px; height: 25px; stroke-width: 1.9; }
        .i-navy { background: rgba(20,53,100,.1); color: var(--navy); }
        .i-red  { background: rgba(234,35,57,.1); color: var(--red); }
        .i-sky  { background: rgba(92,170,213,.16); color: #2f80ab; }
        .i-green{ background: rgba(31,146,84,.12); color: var(--green); }
        @media (prefers-color-scheme: dark) {
            .i-navy { color: var(--sky); } .i-sky { color: var(--sky); }
        }
        .card h3 { font-size: 1.18rem; font-weight: 720; margin: 0 0 8px; letter-spacing: -.01em; }
        .card p { color: var(--muted); margin: 0; font-size: .97rem; }

        /* ---------- Roles ---------- */
        .roles { background: var(--card); border-block: 1px solid var(--border); }
        .role {
            border: 1px solid var(--border); border-radius: var(--radius);
            padding: 30px; background: var(--bg);
            position: relative; overflow: hidden;
        }
        .role::before { content: ""; position: absolute; inset: 0 0 auto 0; height: 4px; }
        .role.r-staff::before { background: var(--sky); }
        .role.r-manager::before { background: var(--navy); }
        .role.r-admin::before { background: var(--red); }
        .role .tag { font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
        .role h3 { font-size: 1.35rem; font-weight: 780; margin: 6px 0 16px; }
        .role ul { list-style: none; margin: 0; padding: 0; display: grid; gap: 11px; }
        .role li { display: flex; gap: .65rem; align-items: flex-start; font-size: .95rem; color: var(--ink); }
        .role li svg { width: 18px; height: 18px; flex: none; margin-top: 2px; }
        .ok { color: var(--green); }
        .no { color: #c2ccd9; }
        @media (prefers-color-scheme: dark) { .no { color: #40536e; } }

        /* ---------- Tech ---------- */
        .tech { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
        .pill {
            display: inline-flex; align-items: center; gap: .5rem;
            background: var(--card); border: 1px solid var(--border);
            padding: .7rem 1.15rem; border-radius: 999px;
            font-weight: 600; font-size: .92rem; color: var(--ink);
            box-shadow: var(--shadow);
        }
        .pill i { width: 9px; height: 9px; border-radius: 50%; display: block; }

        /* ---------- CTA ---------- */
        .cta-band { padding-block: 40px; }
        .cta-inner {
            position: relative; overflow: hidden;
            background: linear-gradient(135deg, var(--navy-900), var(--navy) 60%, var(--navy-700));
            border-radius: 28px; padding: 64px 48px; text-align: center; color: #fff;
            box-shadow: 0 40px 80px -40px rgba(20,53,100,.7);
        }
        .cta-inner::after {
            content: ""; position: absolute; right: -60px; top: -60px;
            width: 260px; height: 260px; border-radius: 30px; transform: rotate(45deg);
            background: radial-gradient(circle, rgba(92,170,213,.4), transparent 70%);
        }
        .cta-inner h2 { font-size: clamp(1.7rem, 3vw, 2.4rem); font-weight: 800; letter-spacing: -.02em; margin: 0 0 12px; position: relative; }
        .cta-inner p { color: #cfe0ef; margin: 0 0 30px; font-size: 1.06rem; position: relative; }

        /* ---------- Footer ---------- */
        footer { border-top: 1px solid var(--border); background: var(--card); }
        .foot { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding-block: 34px; flex-wrap: wrap; }
        .foot .brand span { color: var(--muted); }
        .foot small { color: var(--muted); font-size: .88rem; }

        /* ---------- Reveal animation (progressive enhancement) ----------
           Konten selalu terlihat tanpa JS. Efek masuk hanya aktif saat JS
           menandai <html class="js">, sehingga tak ada risiko konten hilang. */
        .js .reveal { opacity: 0; transform: translateY(18px); transition: opacity .7s ease, transform .7s ease; }
        .js .reveal.in { opacity: 1; transform: none; }
        @media (prefers-reduced-motion: reduce) {
            .js .reveal { opacity: 1; transform: none; transition: none; }
            .bars i { animation: none; }
            .btn:hover, .card:hover { transform: none; }
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 900px) {
            .hero-grid { grid-template-columns: 1fr; gap: 40px; }
            .hero .wrap { padding-top: 64px; padding-bottom: 72px; }
            .cols-3 { grid-template-columns: 1fr; }
            .stripe .wrap { grid-template-columns: 1fr 1fr; gap: 28px 16px; }
            .nav-links .link { display: none; }
            section.block { padding-block: 64px; }
        }
        @media (max-width: 520px) {
            .brand span { display: none; }
            .cta-inner { padding: 44px 24px; }
            .kpis { grid-template-columns: 1fr; }
        }
    </style>
    @endverbatim
</head>
<body>
    <header>
        <div class="wrap nav">
            <a href="/" class="brand" aria-label="Inixindo Bandung — Inventory Management">
                <img src="{{ asset('images/brand/logo.png') }}" alt="Inixindo Bandung">
                <span class="brand-text">
                    <b>Inventory Management</b>
                    <span>Inixindo Bandung</span>
                </span>
            </a>
            <nav class="nav-links">
                <a href="#fitur" class="link">Fitur</a>
                <a href="#peran" class="link">Peran &amp; Akses</a>
                <a href="#teknologi" class="link">Teknologi</a>
                <a href="{{ url('/admin') }}" class="btn btn-solid">
                    Masuk Panel
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </nav>
        </div>
    </header>

    <!-- HERO -->
    <section class="hero">
        <div class="wrap">
            <div class="hero-grid">
                <div class="hero-copy reveal">
                    <span class="eyebrow"><span class="dot"></span> Gudang Tunggal · Real-time</span>
                    <h1>Kelola stok gudang dengan <span class="accent">akurat &amp; terkendali</span>.</h1>
                    <p class="lede">
                        Sistem inventory internal Inixindo Bandung: pencatatan pergerakan stok yang
                        selalu konsisten, otorisasi berjenjang, penyembunyian harga modal, jejak audit,
                        dan dashboard analitik yang siap ambil keputusan.
                    </p>
                    <div class="hero-cta">
                        <a href="{{ url('/admin') }}" class="btn btn-primary">
                            Buka Dashboard
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                        <a href="#fitur" class="btn btn-ghost">Pelajari fitur</a>
                    </div>
                    <p class="hero-note">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Teruji pada 100.000+ produk — p95 di bawah 500&nbsp;ms.
                    </p>
                </div>

                <div class="hero-visual reveal" aria-hidden="true">
                    <div class="panel">
                        <div class="panel-head">
                            <span>Ringkasan Inventory</span>
                            <div class="chips"><i style="background:#ff5f6d"></i><i style="background:#ffd166"></i><i style="background:#7fe0aa"></i></div>
                        </div>
                        <div class="kpis">
                            <div class="kpi"><small>Total Produk</small><b>100.000</b></div>
                            <div class="kpi k-sky"><small>Unit Stok</small><b>3,4 Jt</b></div>
                            <div class="kpi k-red"><small>Stok Kritis</small><b>128</b></div>
                            <div class="kpi k-green"><small>Fast-moving</small><b>A · 18%</b></div>
                        </div>
                        <div class="bars">
                            <i style="height:42%; animation-delay:.02s"></i>
                            <i style="height:66%; animation-delay:.08s"></i>
                            <i style="height:38%; animation-delay:.14s"></i>
                            <i style="height:82%; animation-delay:.2s"></i>
                            <i style="height:54%; animation-delay:.26s"></i>
                            <i style="height:73%; animation-delay:.32s"></i>
                            <i style="height:48%; animation-delay:.38s"></i>
                            <i style="height:90%; animation-delay:.44s; background:linear-gradient(var(--red), rgba(234,35,57,.4))"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- STAT STRIP -->
    <div class="stripe">
        <div class="wrap">
            <div class="stat reveal"><b>3<span class="u"> peran</span></b><small>RBAC berjenjang via Filament Shield</small></div>
            <div class="stat reveal"><b>100<span class="u">rb+</span></b><small>Produk teruji dalam load test</small></div>
            <div class="stat reveal"><b>&lt;500<span class="u">ms</span></b><small>Latensi p95 di beban tinggi</small></div>
            <div class="stat reveal"><b>0<span class="u"> minus</span></b><small>Stok mustahil bernilai negatif</small></div>
        </div>
    </div>

    <!-- FITUR -->
    <section class="block" id="fitur">
        <div class="wrap">
            <div class="head reveal">
                <span class="eyebrow">Fitur Utama</span>
                <h2>Semua yang dibutuhkan operasional gudang</h2>
                <p>Dibangun di atas Laravel 13 &amp; Filament 5 — fokus pada data yang benar, aman, dan mudah dibaca.</p>
            </div>
            <div class="grid cols-3">
                <div class="card reveal">
                    <div class="ico i-navy"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
                    <h3>Master Data &amp; Supplier</h3>
                    <p>Kelola produk, kategori, dan supplier lengkap dengan relasi produk per-supplier dalam satu alur.</p>
                </div>
                <div class="card reveal">
                    <div class="ico i-sky"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17V9m5 8V5m5 12v-6M3 21h18"/></svg></div>
                    <h3>Pergerakan Stok Konsisten</h3>
                    <p>Tipe masuk, keluar, dan penyesuaian. Stok terkini dihitung otomatis &amp; aman terhadap konkurensi — tak akan minus.</p>
                </div>
                <div class="card reveal">
                    <div class="ico i-red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 9 0 1 0 9 9M12 3v9l6.5 3.8M12 3a9 9 0 0 1 9 9"/></svg></div>
                    <h3>Masking Harga Modal</h3>
                    <p>Harga modal disembunyikan total dari staf — bukan sekadar disamarkan — dan konsisten hingga ke API.</p>
                </div>
                <div class="card reveal">
                    <div class="ico i-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg></div>
                    <h3>Audit Trail</h3>
                    <p>Setiap pergerakan tercatat dengan pelakunya. Penyesuaian stok punya jejak khusus &amp; tab riwayat per produk.</p>
                </div>
                <div class="card reveal">
                    <div class="ico i-navy"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M8 14l3-4 3 3 4-6"/></svg></div>
                    <h3>Dashboard Analitik</h3>
                    <p>KPI, stok per kategori, tren masuk-keluar live, stok kritis, dead stock, dan top fast-moving — Chart.js native.</p>
                </div>
                <div class="card reveal">
                    <div class="ico i-red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10h-10V2Z"/><path d="M12 2v10h10A10 10 0 0 0 12 2Z" opacity=".5"/></svg></div>
                    <h3>Analisis ABC / Pareto</h3>
                    <p>Klasifikasi A-B-C berbasis permintaan nyata — ketahui barang paling laku dan yang paling tak diminati. Klik grafik untuk drill-down.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- PERAN -->
    <section class="roles block" id="peran">
        <div class="wrap">
            <div class="head reveal">
                <span class="eyebrow">Peran &amp; Akses</span>
                <h2>Otorisasi berjenjang yang ditegakkan sampai ke Model</h2>
                <p>Bukan sekadar sembunyikan tombol — aturan berlaku di seluruh jalur (UI, API, kode langsung).</p>
            </div>
            <div class="grid cols-3">
                <div class="role r-staff reveal">
                    <div class="tag">Level 1</div>
                    <h3>Staff</h3>
                    <ul>
                        <li><svg class="ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Lihat &amp; input produk</li>
                        <li><svg class="ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Catat stok masuk / keluar</li>
                        <li><svg class="no" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg> Tanpa harga modal</li>
                        <li><svg class="no" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg> Tanpa penyesuaian &amp; hapus</li>
                    </ul>
                </div>
                <div class="role r-manager reveal">
                    <div class="tag">Level 2</div>
                    <h3>Manager</h3>
                    <ul>
                        <li><svg class="ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Kelola master data &amp; stok</li>
                        <li><svg class="ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Penyesuaian (adjustment) stok</li>
                        <li><svg class="ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Lihat harga modal &amp; analitik biaya</li>
                        <li><svg class="no" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg> Tanpa hapus permanen &amp; kelola role</li>
                    </ul>
                </div>
                <div class="role r-admin reveal">
                    <div class="tag">Level 3</div>
                    <h3>Admin</h3>
                    <ul>
                        <li><svg class="ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Akses penuh seluruh modul</li>
                        <li><svg class="ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Kelola role &amp; permission</li>
                        <li><svg class="ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Hapus permanen data</li>
                        <li><svg class="ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Super admin — melewati semua policy</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- TEKNOLOGI -->
    <section class="block" id="teknologi">
        <div class="wrap">
            <div class="head reveal">
                <span class="eyebrow">Teknologi</span>
                <h2>Fondasi modern &amp; berperforma</h2>
                <p>Stack yang matang, teruji, dan dioptimasi untuk beban tinggi.</p>
            </div>
            <div class="tech reveal">
                <span class="pill"><i style="background:#EA2339"></i> Laravel 13</span>
                <span class="pill"><i style="background:#143564"></i> Filament 5</span>
                <span class="pill"><i style="background:#00758F"></i> MySQL 8</span>
                <span class="pill"><i style="background:#DC382D"></i> Redis</span>
                <span class="pill"><i style="background:#5CAAD5"></i> Laravel Octane</span>
                <span class="pill"><i style="background:#1F9254"></i> Filament Shield RBAC</span>
                <span class="pill"><i style="background:#8b5cf6"></i> Sanctum API</span>
                <span class="pill"><i style="background:#f59e0b"></i> Pest · PHPStan · CI</span>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <div class="cta-band">
        <div class="wrap">
            <div class="cta-inner reveal">
                <h2>Siap mengelola inventory dengan tenang?</h2>
                <p>Masuk ke panel dan mulai dari dashboard ringkasan gudang Anda.</p>
                <a href="{{ url('/admin') }}" class="btn btn-primary">
                    Masuk ke Panel
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="wrap foot">
            <div class="brand">
                <img src="{{ asset('images/brand/logo.png') }}" alt="Inixindo Bandung" style="height:34px">
                <span class="brand-text">
                    <b>Inventory Management</b>
                    <span>Inixindo Bandung</span>
                </span>
            </div>
            <small>&copy; {{ date('Y') }} Inixindo Bandung. Sistem internal manajemen inventory.</small>
        </div>
    </footer>

    <script>
        (function () {
            var els = document.querySelectorAll('.reveal');
            if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                els.forEach(function (el) { el.classList.add('in'); });
                return;
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
                });
            }, { threshold: 0.12 });
            els.forEach(function (el) { io.observe(el); });
        })();
    </script>
</body>
</html>
