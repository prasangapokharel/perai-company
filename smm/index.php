<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/");
    exit;
}
require_once __DIR__ . '/config/dbconfig.php';

$total_users = 0;
$total_orders = 0;
$total_tickets = 0;

try {
    $r = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($r) $total_users = $r->fetch_assoc()['count'];
} catch (Exception $e) { $total_users = 0; }

try {
    $r = $conn->query("SELECT COUNT(*) as count FROM orders");
    if ($r) $total_orders = $r->fetch_assoc()['count'];
} catch (Exception $e) { $total_orders = 0; }

try {
    $r = $conn->query("SELECT COUNT(*) as count FROM support_tickets");
    if ($r) $total_tickets = $r->fetch_assoc()['count'];
} catch (Exception $e) { $total_tickets = 0; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OkxSmm — World's Most Professional SMM Services</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Figtree:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<style>
/* ─── Reset & Base ──────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --blue:    #3b82f6;
    --blue-d:  #1d4ed8;
    --cyan:    #06b6d4;
    --indigo:  #6366f1;
    --white:   #ffffff;
    --bg:      #04070f;
    --surface: rgba(255,255,255,0.04);
    --border:  rgba(255,255,255,0.08);
    --border-h:rgba(59,130,246,0.45);
    --text:    #f1f5f9;
    --muted:   #94a3b8;
    --r-xl:    20px;
    --r-2xl:   28px;
    --r-3xl:   40px;
    font-size: 16px;
}
html { scroll-behavior: smooth; }
body {
    font-family: 'Figtree', sans-serif;
    background: var(--bg);
    color: var(--text);
    overflow-x: hidden;
    cursor: none;
}

/* ─── Custom Cursor ─────────────────────────────────────────── */
#cursor-dot {
    position: fixed; top: 0; left: 0; z-index: 9999; pointer-events: none;
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--blue); transform: translate(-50%,-50%);
    transition: transform .1s, background .2s;
}
#cursor-ring {
    position: fixed; top: 0; left: 0; z-index: 9998; pointer-events: none;
    width: 40px; height: 40px; border-radius: 50%;
    border: 1.5px solid rgba(59,130,246,.5);
    transform: translate(-50%,-50%);
    transition: transform .35s cubic-bezier(.25,.46,.45,.94), opacity .3s;
}
body:hover #cursor-ring { opacity: 1; }

/* ─── Aurora Background ─────────────────────────────────────── */
#aurora {
    position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden;
}
.orb {
    position: absolute; border-radius: 50%; filter: blur(90px); opacity: .18;
    animation: drift 18s ease-in-out infinite alternate;
}
.orb-1 { width: 900px; height: 900px; background: #1d4ed8; top: -300px; left: -200px; animation-delay: 0s; }
.orb-2 { width: 700px; height: 700px; background: #0e7490; bottom: -200px; right: -200px; animation-delay: -6s; }
.orb-3 { width: 500px; height: 500px; background: #4f46e5; top: 40%; left: 50%; transform: translate(-50%,-50%); animation-delay: -3s; }
@keyframes drift {
    0%   { transform: translate(0,0) scale(1); }
    100% { transform: translate(80px, 60px) scale(1.15); }
}

/* ─── Particle Canvas ───────────────────────────────────────── */
#particles { position: fixed; inset: 0; z-index: 0; pointer-events: none; }

/* ─── Noise Overlay ─────────────────────────────────────────── */
body::after {
    content: ''; position: fixed; inset: 0; z-index: 1; pointer-events: none;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
    opacity: .025;
}

/* ─── Glass Utility ─────────────────────────────────────────── */
.glass {
    background: var(--surface);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid var(--border);
}
.glass-card {
    background: rgba(255,255,255,0.04);
    backdrop-filter: blur(24px) saturate(200%);
    -webkit-backdrop-filter: blur(24px) saturate(200%);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: var(--r-2xl);
    transition: border-color .3s, transform .3s, box-shadow .3s;
}
.glass-card:hover {
    border-color: var(--border-h);
    transform: translateY(-6px);
    box-shadow: 0 30px 80px rgba(59,130,246,.12);
}

/* ─── Scroll Reveal ─────────────────────────────────────────── */
.reveal {
    opacity: 0; transform: translateY(40px);
    transition: opacity .8s cubic-bezier(.25,.46,.45,.94), transform .8s cubic-bezier(.25,.46,.45,.94);
}
.reveal.visible { opacity: 1; transform: none; }
.reveal-delay-1 { transition-delay: .1s; }
.reveal-delay-2 { transition-delay: .2s; }
.reveal-delay-3 { transition-delay: .3s; }
.reveal-delay-4 { transition-delay: .4s; }
.reveal-delay-5 { transition-delay: .5s; }

/* ─── Typography ─────────────────────────────────────────────── */
.font-display { font-family: 'Syne', sans-serif; }
.gradient-text {
    background: linear-gradient(135deg, #60a5fa 0%, #06b6d4 50%, #a78bfa 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.gradient-text-alt {
    background: linear-gradient(90deg, #f0f9ff, #bae6fd, #67e8f9);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* ─── Navigation ─────────────────────────────────────────────── */
header {
    position: fixed; top: 0; width: 100%; z-index: 100;
    padding: 20px 24px;
    transition: padding .4s;
}
header.scrolled { padding: 12px 24px; }
nav {
    max-width: 1280px; margin: 0 auto;
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 28px; border-radius: 20px;
    background: rgba(4,7,15,0.7);
    backdrop-filter: blur(24px) saturate(200%);
    -webkit-backdrop-filter: blur(24px) saturate(200%);
    border: 1px solid rgba(255,255,255,0.08);
    box-shadow: 0 4px 32px rgba(0,0,0,.4);
    transition: background .4s;
}
.nav-logo img { height: 44px; width: auto; }
.nav-links { display: flex; align-items: center; gap: 36px; }
.nav-links a {
    color: var(--muted); font-size: .85rem; font-weight: 600;
    text-decoration: none; letter-spacing: .02em;
    position: relative; transition: color .3s;
}
.nav-links a::after {
    content: ''; position: absolute; bottom: -4px; left: 0;
    width: 0; height: 1.5px; background: var(--blue);
    transition: width .3s cubic-bezier(.25,.46,.45,.94);
}
.nav-links a:hover { color: var(--white); }
.nav-links a:hover::after { width: 100%; }
.nav-cta-outline {
    color: var(--text); font-size: .85rem; font-weight: 600;
    text-decoration: none; padding: 9px 22px; border-radius: 12px;
    border: 1px solid var(--border); transition: border-color .3s, color .3s;
}
.nav-cta-outline:hover { border-color: var(--blue); color: var(--white); }
.nav-cta-fill {
    color: var(--white); font-size: .85rem; font-weight: 700;
    text-decoration: none; padding: 10px 24px; border-radius: 12px;
    background: linear-gradient(135deg, var(--blue), var(--blue-d));
    box-shadow: 0 4px 20px rgba(59,130,246,.35);
    transition: transform .2s, box-shadow .3s;
}
.nav-cta-fill:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(59,130,246,.5); }

/* ─── Mobile Menu ──────────────────────────────────────────── */
.hamburger { display: none; flex-direction: column; gap: 5px; cursor: pointer; padding: 4px; }
.hamburger span {
    display: block; width: 24px; height: 2px;
    background: var(--muted); border-radius: 2px;
    transition: transform .3s, opacity .3s;
}
.mobile-menu {
    display: none; position: fixed; inset: 0; z-index: 99;
    background: rgba(4,7,15,.95);
    backdrop-filter: blur(24px);
    flex-direction: column; align-items: center; justify-content: center; gap: 40px;
}
.mobile-menu.open { display: flex; }
.mobile-menu a {
    color: var(--text); font-family: 'Syne', sans-serif; font-size: 2rem;
    font-weight: 700; text-decoration: none;
    transition: color .2s;
}
.mobile-menu a:hover { color: var(--blue); }

/* ─── Hero ───────────────────────────────────────────────────── */
#home {
    position: relative; z-index: 2;
    min-height: 100vh; display: flex; align-items: center;
    padding: 140px 24px 80px;
}
.hero-inner {
    max-width: 1280px; margin: 0 auto;
    display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center;
}
.hero-badge {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 16px; border-radius: 100px;
    background: rgba(59,130,246,.12); border: 1px solid rgba(59,130,246,.25);
    font-size: .75rem; font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: #93c5fd; margin-bottom: 28px;
}
.hero-badge .pulse-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--blue); animation: pulse 1.8s ease infinite;
}
@keyframes pulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(59,130,246,.7); }
    50% { box-shadow: 0 0 0 8px rgba(59,130,246,0); }
}
.hero-h1 {
    font-family: 'Syne', sans-serif;
    font-size: clamp(3rem, 6vw, 5.5rem);
    font-weight: 800; line-height: 1.05;
    letter-spacing: -.03em; margin-bottom: 24px;
    color: var(--white);
}
.hero-sub {
    font-size: 1.1rem; color: var(--muted); line-height: 1.75;
    max-width: 500px; margin-bottom: 40px;
}
.btn-primary {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 16px 36px; border-radius: 14px; font-size: 1rem; font-weight: 700;
    color: var(--white); text-decoration: none;
    background: linear-gradient(135deg, var(--blue) 0%, var(--blue-d) 100%);
    box-shadow: 0 8px 32px rgba(59,130,246,.4);
    transition: transform .25s, box-shadow .25s;
}
.btn-primary:hover { transform: translateY(-3px); box-shadow: 0 16px 48px rgba(59,130,246,.55); }
.btn-ghost {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 15px 32px; border-radius: 14px; font-size: 1rem; font-weight: 700;
    color: var(--text); text-decoration: none;
    background: rgba(255,255,255,.05); border: 1px solid var(--border);
    transition: background .25s, border-color .25s, transform .25s;
}
.btn-ghost:hover { background: rgba(255,255,255,.09); border-color: rgba(255,255,255,.18); transform: translateY(-3px); }
.hero-btns { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 52px; }

/* Platform pills */
.platforms { display: flex; flex-wrap: wrap; gap: 10px; }
.platform-pill {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 16px; border-radius: 100px;
    background: rgba(255,255,255,.05); border: 1px solid var(--border);
    font-size: .75rem; font-weight: 600; color: var(--muted);
    transition: border-color .3s, color .3s;
}
.platform-pill:hover { border-color: var(--blue); color: var(--white); }
.platform-pill svg { width: 16px; height: 16px; opacity: .7; fill: currentColor; }

/* Hero visual */
.hero-visual { position: relative; display: flex; align-items: center; justify-content: center; }
.hero-glow-ring {
    position: absolute; border-radius: 50%;
    border: 1px solid rgba(59,130,246,.15);
    animation: spin-slow linear infinite;
}
.ring-1 { width: 440px; height: 440px; animation-duration: 25s; }
.ring-2 { width: 560px; height: 560px; animation-duration: 40s; animation-direction: reverse; border-style: dashed; }
@keyframes spin-slow { to { transform: rotate(360deg); } }
.hero-img-wrap {
    position: relative; z-index: 2;
    width: 380px; max-width: 100%;
    animation: float 5s ease-in-out infinite;
}
@keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-18px); } }
.hero-img-wrap img {
    width: 100%; border-radius: var(--r-3xl);
    border: 1px solid rgba(255,255,255,.12);
    box-shadow: 0 40px 120px rgba(0,0,0,.7);
}
/* Floating mini cards on hero image */
.mini-card {
    position: absolute; padding: 12px 16px; border-radius: 16px;
    background: rgba(10,20,40,.8);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(59,130,246,.25);
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 8px 32px rgba(0,0,0,.5);
    animation: float 4s ease-in-out infinite;
}
.mini-card-1 { top: 30px; right: -40px; animation-delay: -.5s; }
.mini-card-2 { bottom: 60px; left: -50px; animation-delay: -2s; }
.mini-card .mi-icon { font-family: 'Material Icons Round'; font-size: 22px; color: var(--blue); }
.mini-card-label { font-size: .72rem; color: var(--muted); }
.mini-card-val { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; color: var(--white); }

/* ─── Stats Bar ──────────────────────────────────────────────── */
.stats-bar {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto 0;
    padding: 0 24px 80px;
}
.stats-grid {
    display: grid; grid-template-columns: repeat(4, 1fr);
    border-radius: var(--r-2xl); overflow: hidden;
    border: 1px solid var(--border);
    background: rgba(255,255,255,.03);
    backdrop-filter: blur(24px);
}
.stat-cell {
    padding: 36px 32px; text-align: center;
    border-right: 1px solid var(--border);
    position: relative; overflow: hidden;
    transition: background .3s;
}
.stat-cell:last-child { border-right: none; }
.stat-cell:hover { background: rgba(59,130,246,.05); }
.stat-cell::before {
    content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);
    width: 60%; height: 1px;
    background: linear-gradient(90deg, transparent, var(--blue), transparent);
    opacity: 0; transition: opacity .3s;
}
.stat-cell:hover::before { opacity: 1; }
.stat-label { font-size: .7rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; }
.stat-val { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; color: var(--white); }
.stat-val.accent { color: var(--blue); }

/* ─── Section Titles ─────────────────────────────────────────── */
.section-tag {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: .7rem; font-weight: 700; letter-spacing: .15em;
    text-transform: uppercase; color: #60a5fa; margin-bottom: 16px;
}
.section-tag::before {
    content: ''; display: block; width: 20px; height: 1px; background: var(--blue);
}
.section-title {
    font-family: 'Syne', sans-serif; font-size: clamp(2rem, 4vw, 3.5rem);
    font-weight: 800; line-height: 1.1; letter-spacing: -.03em; color: var(--white);
    margin-bottom: 20px;
}
.section-sub { font-size: 1.05rem; color: var(--muted); line-height: 1.7; max-width: 520px; }

/* ─── Stats Feature Section ──────────────────────────────────── */
#reliability {
    position: relative; z-index: 2;
    padding: 100px 24px;
    background: linear-gradient(180deg, transparent, rgba(59,130,246,.04), transparent);
}
.rel-inner { max-width: 1280px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center; }
.stat-cards-wrap { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.stat-feat-card {
    padding: 32px; border-radius: var(--r-2xl);
    background: rgba(255,255,255,.04);
    border: 1px solid var(--border);
    transition: all .35s cubic-bezier(.25,.46,.45,.94);
    position: relative; overflow: hidden;
}
.stat-feat-card::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(59,130,246,.08), transparent);
    opacity: 0; transition: opacity .35s;
}
.stat-feat-card:hover { border-color: var(--border-h); transform: translateY(-8px); box-shadow: 0 24px 64px rgba(59,130,246,.15); }
.stat-feat-card:hover::before { opacity: 1; }
.sfc-offset { margin-top: 40px; }
.sfc-icon {
    width: 48px; height: 48px; border-radius: 14px;
    background: rgba(59,130,246,.15); display: flex; align-items: center; justify-content: center;
    margin-bottom: 20px;
}
.sfc-icon .mi { font-family: 'Material Icons Round'; font-size: 24px; color: var(--blue); }
.sfc-num { font-family: 'Syne', sans-serif; font-size: 2.5rem; font-weight: 800; color: var(--white); margin-bottom: 6px; }
.sfc-lbl { font-size: .75rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); }
.check-list { list-style: none; display: flex; flex-direction: column; gap: 16px; margin-top: 36px; }
.check-list li { display: flex; align-items: center; gap: 14px; color: #cbd5e1; font-size: .95rem; font-weight: 500; }
.check-dot {
    flex-shrink: 0; width: 26px; height: 26px; border-radius: 50%;
    background: rgba(59,130,246,.15); border: 1px solid rgba(59,130,246,.3);
    display: flex; align-items: center; justify-content: center;
}
.check-dot .mi { font-family: 'Material Icons Round'; font-size: 14px; color: var(--blue); }
.view-link {
    display: inline-flex; align-items: center; gap: 8px;
    color: var(--blue); font-size: .85rem; font-weight: 700;
    letter-spacing: .08em; text-transform: uppercase; text-decoration: none;
    margin-top: 40px; transition: gap .3s;
}
.view-link:hover { gap: 14px; }
.view-link .mi { font-family: 'Material Icons Round'; font-size: 16px; }

/* ─── Advantages ─────────────────────────────────────────────── */
#features {
    position: relative; z-index: 2; padding: 100px 24px;
}
.adv-grid {
    max-width: 1280px; margin: 60px auto 0;
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;
}
.adv-card {
    padding: 40px 32px; border-radius: var(--r-2xl);
    background: rgba(255,255,255,.04);
    border: 1px solid var(--border);
    position: relative; overflow: hidden;
    transition: all .35s cubic-bezier(.25,.46,.45,.94);
    cursor: default;
}
.adv-card::after {
    content: ''; position: absolute;
    width: 200px; height: 200px; border-radius: 50%;
    background: radial-gradient(circle, rgba(59,130,246,.18), transparent 70%);
    bottom: -80px; right: -80px; opacity: 0;
    transition: opacity .4s, transform .4s;
    transform: scale(.5);
}
.adv-card:hover { border-color: var(--border-h); transform: translateY(-8px); box-shadow: 0 24px 64px rgba(59,130,246,.12); }
.adv-card:hover::after { opacity: 1; transform: scale(1.2); }
.adv-icon-wrap {
    width: 64px; height: 64px; border-radius: 18px;
    background: rgba(59,130,246,.12); border: 1px solid rgba(59,130,246,.2);
    display: flex; align-items: center; justify-content: center; margin-bottom: 24px;
    transition: background .3s;
}
.adv-card:hover .adv-icon-wrap { background: rgba(59,130,246,.22); }
.adv-icon-wrap .mi { font-family: 'Material Icons Round'; font-size: 28px; color: var(--blue); }
.adv-title { font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 700; color: var(--white); margin-bottom: 10px; }
.adv-desc { font-size: .9rem; color: var(--muted); line-height: 1.65; }

/* ─── Services Table ─────────────────────────────────────────── */
#services { position: relative; z-index: 2; padding: 100px 24px; }
.svc-wrap { max-width: 1280px; margin: 0 auto; }
.svc-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; }
.svc-table-wrap {
    border-radius: var(--r-2xl); overflow: hidden;
    border: 1px solid var(--border);
    background: rgba(255,255,255,.03);
    backdrop-filter: blur(20px);
}
table { width: 100%; border-collapse: collapse; }
thead tr { background: rgba(255,255,255,.03); }
thead th {
    padding: 20px 28px; text-align: left;
    font-size: .7rem; font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: var(--muted);
    border-bottom: 1px solid var(--border);
}
tbody tr {
    border-bottom: 1px solid rgba(255,255,255,.04);
    transition: background .25s;
}
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: rgba(59,130,246,.05); }
tbody td { padding: 22px 28px; }
.svc-name { display: flex; align-items: center; gap: 16px; }
.svc-emoji {
    width: 44px; height: 44px; border-radius: 12px;
    background: rgba(59,130,246,.12); border: 1px solid rgba(59,130,246,.18);
    display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;
}
.svc-title { font-size: .95rem; font-weight: 700; color: var(--white); }
.svc-price { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 800; color: var(--blue); }
.svc-minmax { font-size: .85rem; font-weight: 600; color: var(--muted); }
.badge {
    display: inline-block; padding: 5px 14px; border-radius: 100px;
    font-size: .68rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase;
    background: rgba(59,130,246,.12); border: 1px solid rgba(59,130,246,.25); color: #93c5fd;
}
.badge.fast { background: rgba(6,182,212,.1); border-color: rgba(6,182,212,.25); color: #67e8f9; }

/* ─── Blog ───────────────────────────────────────────────────── */
#blog { position: relative; z-index: 2; padding: 100px 24px; }
.blog-grid { max-width: 1280px; margin: 60px auto 0; display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
.blog-card {
    border-radius: var(--r-2xl); overflow: hidden;
    border: 1px solid var(--border);
    background: rgba(255,255,255,.04);
    transition: all .35s cubic-bezier(.25,.46,.45,.94);
}
.blog-card:hover { border-color: var(--border-h); transform: translateY(-8px); box-shadow: 0 24px 64px rgba(59,130,246,.12); }
.blog-thumb {
    height: 200px; position: relative; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
}
.blog-thumb-gradient { position: absolute; inset: 0; }
.blog-thumb-icon { position: relative; z-index: 1; font-family: 'Material Icons Round'; font-size: 72px; color: rgba(255,255,255,.25); }
.blog-tag {
    position: absolute; top: 16px; left: 16px; z-index: 2;
    padding: 4px 12px; border-radius: 100px;
    background: var(--blue); color: var(--white);
    font-size: .65rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase;
}
.blog-body { padding: 28px; }
.blog-title { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; color: var(--white); margin-bottom: 10px; transition: color .25s; }
.blog-card:hover .blog-title { color: #93c5fd; }
.blog-excerpt { font-size: .88rem; color: var(--muted); line-height: 1.65; margin-bottom: 20px; }
.blog-read {
    display: inline-flex; align-items: center; gap: 6px;
    color: var(--blue); font-size: .82rem; font-weight: 700; text-decoration: none;
    transition: gap .3s;
}
.blog-read:hover { gap: 10px; }
.blog-read .mi { font-family: 'Material Icons Round'; font-size: 14px; }

/* ─── CTA Section ────────────────────────────────────────────── */
#cta { position: relative; z-index: 2; padding: 80px 24px 120px; }
.cta-box {
    max-width: 900px; margin: 0 auto;
    padding: 80px 60px; border-radius: var(--r-3xl); text-align: center;
    background: rgba(255,255,255,.04);
    backdrop-filter: blur(24px); border: 1px solid rgba(59,130,246,.2);
    position: relative; overflow: hidden;
    box-shadow: 0 0 120px rgba(59,130,246,.1);
}
.cta-glow {
    position: absolute; width: 500px; height: 500px; border-radius: 50%;
    background: radial-gradient(circle, rgba(59,130,246,.15), transparent 70%);
    top: 50%; left: 50%; transform: translate(-50%,-50%);
    pointer-events: none;
}
.cta-title { font-family: 'Syne', sans-serif; font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; color: var(--white); margin-bottom: 16px; position: relative; z-index: 1; }
.cta-sub { font-size: 1rem; color: var(--muted); line-height: 1.7; margin-bottom: 40px; position: relative; z-index: 1; }
.cta-btns { display: flex; flex-wrap: wrap; justify-content: center; gap: 16px; position: relative; z-index: 1; }

/* ─── Footer ─────────────────────────────────────────────────── */
footer {
    position: relative; z-index: 2;
    border-top: 1px solid var(--border);
    padding: 60px 24px 40px;
}
.footer-inner { max-width: 1280px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; }
.footer-brand { font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 800; color: var(--white); }
.footer-links { display: flex; gap: 28px; flex-wrap: wrap; }
.footer-links a { color: var(--muted); font-size: .85rem; text-decoration: none; transition: color .25s; }
.footer-links a:hover { color: var(--white); }
.footer-copy { color: var(--muted); font-size: .8rem; text-align: center; margin-top: 40px; max-width: 1280px; margin-left: auto; margin-right: auto; padding: 0 24px; }

/* ─── Responsive ─────────────────────────────────────────────── */
@media (max-width: 1024px) {
    .hero-inner { grid-template-columns: 1fr; gap: 60px; }
    .hero-visual { display: none; }
    .rel-inner { grid-template-columns: 1fr; gap: 60px; }
    .adv-grid { grid-template-columns: 1fr 1fr; }
    .blog-grid { grid-template-columns: 1fr 1fr; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .stat-cell:nth-child(2) { border-right: none; }
    .stat-cell { border-bottom: 1px solid var(--border); }
    .stat-cell:nth-child(3), .stat-cell:nth-child(4) { border-bottom: none; }
    .stat-cell:nth-child(4) { border-right: none; }
}
@media (max-width: 768px) {
    .nav-links, .nav-cta-outline { display: none; }
    .hamburger { display: flex; }
    .adv-grid { grid-template-columns: 1fr; }
    .blog-grid { grid-template-columns: 1fr; }
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .stat-cards-wrap { grid-template-columns: 1fr; }
    .sfc-offset { margin-top: 0; }
    .svc-header { flex-direction: column; gap: 20px; align-items: flex-start; }
    .cta-box { padding: 48px 28px; }
    .hero-h1 { font-size: 2.8rem; }
}
@media (max-width: 480px) {
    .stats-grid { grid-template-columns: 1fr; }
    .stat-cell { border-right: none; border-bottom: 1px solid var(--border); }
    .stat-cell:last-child { border-bottom: none; }
    thead th:nth-child(3), tbody td:nth-child(3) { display: none; }
}
</style>
</head>
<body>

<!-- Aurora Background -->
<div id="aurora">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
</div>
<canvas id="particles"></canvas>

<!-- Custom Cursor -->
<div id="cursor-dot"></div>
<div id="cursor-ring"></div>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobile-menu">
    <a href="#home" onclick="closeMobileMenu()">Home</a>
    <a href="#features" onclick="closeMobileMenu()">Features</a>
    <a href="#services" onclick="closeMobileMenu()">Services</a>
    <a href="#blog" onclick="closeMobileMenu()">Blog</a>
    <a href="/api" onclick="closeMobileMenu()">API</a>
    <a href="/login/" onclick="closeMobileMenu()" style="color:var(--blue)">Sign In</a>
    <a href="/register/" onclick="closeMobileMenu()" style="color:var(--blue)">Sign Up</a>
</div>

<!-- ─── Header ─────────────────────────────────────────────────── -->
<header id="site-header">
    <nav>
        <div class="nav-logo"><a href="/"><img src="/assets/logo.png" alt="OkxSmm Logo"></a></div>
        <div class="nav-links">
            <a href="#home">Home</a>
            <a href="#features">Features</a>
            <a href="#services">Services</a>
            <a href="#blog">Blog</a>
            <a href="/api">API</a>
        </div>
        <div style="display:flex;align-items:center;gap:12px">
            <a href="/login/" class="nav-cta-outline">Sign In</a>
            <a href="/register/" class="nav-cta-fill">Sign Up</a>
            <div class="hamburger" id="hamburger" onclick="toggleMobileMenu()">
                <span></span><span></span><span></span>
            </div>
        </div>
    </nav>
</header>

<!-- ─── Hero ──────────────────────────────────────────────────── -->
<main id="home">
    <div class="hero-inner">
        <!-- Left -->
        <div>
            <div class="hero-badge reveal"><span class="pulse-dot"></span> #1 Rated SMM Platform</div>
            <h1 class="hero-h1 reveal reveal-delay-1">
                World's Most<br>
                <span class="gradient-text">Professional</span><br>
                SMM Services
            </h1>
            <p class="hero-sub reveal reveal-delay-2">
                Boost your social presence instantly with our automated services. Trusted by 10k+ influencers and agencies worldwide for lightning-fast delivery and premium quality.
            </p>
            <div class="hero-btns reveal reveal-delay-3">
                <a href="/register/" class="btn-primary">
                    Get Started Now
                    <span class="material-icons-round" style="font-size:20px">arrow_forward</span>
                </a>
                <a href="#services" class="btn-ghost">
                    <span class="material-icons-round" style="font-size:18px">grid_view</span>
                    View Services
                </a>
            </div>
            <div class="reveal reveal-delay-4">
                <p style="font-size:.7rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:12px;">Supported Platforms</p>
                <div class="platforms">
                    <div class="platform-pill">
                        <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        Instagram
                    </div>
                    <div class="platform-pill">
                        <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        Facebook
                    </div>
                    <div class="platform-pill">
                        <svg viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                        YouTube
                    </div>
                    <div class="platform-pill">
                        <svg viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 2.71 3.5 2.38 1.18-.23 2.04-1.17 2.06-2.37.03-2.23.04-4.45.04-6.69 0-.5-.01-1 0-1.5.08-1.6.41-3.19 1.06-4.67.69-1.6 1.66-3.07 2.89-4.33z"/></svg>
                        TikTok
                    </div>
                </div>
            </div>
        </div>
        <!-- Right: Visual -->
        <div class="hero-visual">
            <div class="hero-glow-ring ring-1"></div>
            <div class="hero-glow-ring ring-2"></div>
            <div class="hero-img-wrap">
                <img src="/assets/images/hero-card.jpg" alt="OkxSmm Platform">
                <div class="mini-card mini-card-1">
                    <span class="mi-icon material-icons-round">trending_up</span>
                    <div>
                        <div class="mini-card-label">Growth Rate</div>
                        <div class="mini-card-val">+340%</div>
                    </div>
                </div>
                <div class="mini-card mini-card-2">
                    <span class="mi-icon material-icons-round">bolt</span>
                    <div>
                        <div class="mini-card-label">Delivery</div>
                        <div class="mini-card-val">Instant</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ─── Stats Bar ──────────────────────────────────────────────── -->
<div class="stats-bar">
    <div class="stats-grid reveal">
        <div class="stat-cell">
            <div class="stat-label">Active Users</div>
            <div class="stat-val" data-count="<?php echo $total_users; ?>"><?php echo number_format($total_users); ?>+</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">Total Orders</div>
            <div class="stat-val" data-count="<?php echo $total_orders; ?>"><?php echo number_format($total_orders); ?>+</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">Avg Delivery</div>
            <div class="stat-val">0.4s</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">Satisfaction</div>
            <div class="stat-val accent">99.9%</div>
        </div>
    </div>
</div>

<!-- ─── Reliability Section ───────────────────────────────────── -->
<section id="reliability">
    <div class="rel-inner">
        <!-- Cards -->
        <div class="stat-cards-wrap">
            <div class="stat-feat-card reveal">
                <div class="sfc-icon"><span class="mi material-icons-round">groups</span></div>
                <div class="sfc-num"><?php echo number_format($total_users); ?>+</div>
                <div class="sfc-lbl">Total Users</div>
            </div>
            <div class="stat-feat-card sfc-offset reveal reveal-delay-1">
                <div class="sfc-icon"><span class="mi material-icons-round">confirmation_number</span></div>
                <div class="sfc-num"><?php echo number_format($total_tickets); ?>+</div>
                <div class="sfc-lbl">Support Tickets</div>
            </div>
            <div class="stat-feat-card reveal reveal-delay-2">
                <div class="sfc-icon"><span class="mi material-icons-round">shopping_cart</span></div>
                <div class="sfc-num"><?php echo number_format($total_orders); ?>+</div>
                <div class="sfc-lbl">Orders Completed</div>
            </div>
            <div class="stat-feat-card sfc-offset reveal reveal-delay-3">
                <div class="sfc-icon"><span class="mi material-icons-round">speed</span></div>
                <div class="sfc-num">0.2s</div>
                <div class="sfc-lbl">Avg Response</div>
            </div>
        </div>
        <!-- Text -->
        <div class="reveal reveal-delay-1">
            <span class="section-tag">Why Choose Us</span>
            <h2 class="section-title">Built on <span class="gradient-text">Reliability</span> & Trusted Excellence</h2>
            <p class="section-sub">OkxSmm isn't just another SMM service. We've engineered a platform that balances speed with reliability, ensuring your growth never skips a beat.</p>
            <ul class="check-list">
                <li><div class="check-dot"><span class="mi material-icons-round">check</span></div>99.9% Platform Uptime Guaranteed</li>
                <li><div class="check-dot"><span class="mi material-icons-round">check</span></div>Advanced API Support for Resellers</li>
                <li><div class="check-dot"><span class="mi material-icons-round">check</span></div>Secure Encrypted Payment Gateways</li>
                <li><div class="check-dot"><span class="mi material-icons-round">check</span></div>Real-time Order Tracking Dashboard</li>
            </ul>
            <a href="/services/" class="view-link">
                View All Services <span class="mi material-icons-round">arrow_forward</span>
            </a>
        </div>
    </div>
</section>

<!-- ─── Advantages ────────────────────────────────────────────── -->
<section id="features">
    <div style="max-width:1280px;margin:0 auto">
        <div style="text-align:center" class="reveal">
            <span class="section-tag">Our Advantages</span>
            <h2 class="section-title">Why We Stand <span class="gradient-text">Apart</span></h2>
        </div>
        <div class="adv-grid">
            <div class="adv-card reveal reveal-delay-1">
                <div class="adv-icon-wrap"><span class="mi material-icons-round">lock</span></div>
                <div class="adv-title">Bank-Grade Security</div>
                <div class="adv-desc">Every transaction is protected with AES-256 encryption and multi-layered fraud detection systems running 24/7.</div>
            </div>
            <div class="adv-card reveal reveal-delay-2">
                <div class="adv-icon-wrap"><span class="mi material-icons-round">bolt</span></div>
                <div class="adv-title">Instant Delivery</div>
                <div class="adv-desc">Our proprietary delivery engine processes and begins fulfilling orders within milliseconds of payment confirmation.</div>
            </div>
            <div class="adv-card reveal reveal-delay-3">
                <div class="adv-icon-wrap"><span class="mi material-icons-round">support_agent</span></div>
                <div class="adv-title">24/7 Human Support</div>
                <div class="adv-desc">Real humans, not bots. Our expert team is available around the clock to resolve any issue within minutes.</div>
            </div>
            <div class="adv-card reveal reveal-delay-1">
                <div class="adv-icon-wrap"><span class="mi material-icons-round">local_offer</span></div>
                <div class="adv-title">Best Market Prices</div>
                <div class="adv-desc">We leverage bulk supplier relationships and pass the savings directly to you — the most competitive rates guaranteed.</div>
            </div>
            <div class="adv-card reveal reveal-delay-2">
                <div class="adv-icon-wrap"><span class="mi material-icons-round">stars</span></div>
                <div class="adv-title">Premium Quality</div>
                <div class="adv-desc">Non-drop, high-retention followers, views, and likes from real-looking accounts that pass every algorithm check.</div>
            </div>
            <div class="adv-card reveal reveal-delay-3">
                <div class="adv-icon-wrap"><span class="mi material-icons-round">dashboard</span></div>
                <div class="adv-title">Intuitive Dashboard</div>
                <div class="adv-desc">An elegantly designed control panel that lets you place orders, track progress, and manage funds in seconds.</div>
            </div>
        </div>
    </div>
</section>

<!-- ─── Services Table ────────────────────────────────────────── -->
<section id="services">
    <div class="svc-wrap">
        <div class="svc-header reveal">
            <div>
                <span class="section-tag">Popular Services</span>
                <h2 class="section-title">Best-Selling <span class="gradient-text">Packages</span></h2>
                <p class="section-sub" style="margin-bottom:0">Loved by thousands of influencers, creators, and agencies worldwide.</p>
            </div>
            <a href="/services" class="view-link" style="flex-shrink:0">View All <span class="mi material-icons-round">arrow_forward</span></a>
        </div>
        <div class="svc-table-wrap reveal reveal-delay-1">
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Price / 1k</th>
                        <th>Min / Max</th>
                        <th>Speed</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><div class="svc-name"><div class="svc-emoji">📸</div><span class="svc-title">Instagram Followers [Real]</span></div></td>
                        <td><span class="svc-price">$0.85</span></td>
                        <td><span class="svc-minmax">100 / 100K</span></td>
                        <td><span class="badge">Instant</span></td>
                    </tr>
                    <tr>
                        <td><div class="svc-name"><div class="svc-emoji">▶️</div><span class="svc-title">YouTube Views [Non-Drop]</span></div></td>
                        <td><span class="svc-price">$1.20</span></td>
                        <td><span class="svc-minmax">500 / 1M</span></td>
                        <td><span class="badge fast">Fast</span></td>
                    </tr>
                    <tr>
                        <td><div class="svc-name"><div class="svc-emoji">🎵</div><span class="svc-title">TikTok Likes</span></div></td>
                        <td><span class="svc-price">$0.50</span></td>
                        <td><span class="svc-minmax">50 / 50K</span></td>
                        <td><span class="badge">Instant</span></td>
                    </tr>
                    <tr>
                        <td><div class="svc-name"><div class="svc-emoji">🐦</div><span class="svc-title">Twitter Followers [HQ]</span></div></td>
                        <td><span class="svc-price">$0.95</span></td>
                        <td><span class="svc-minmax">100 / 200K</span></td>
                        <td><span class="badge fast">Fast</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ─── Blog Section ──────────────────────────────────────────── -->
<section id="blog">
    <div style="max-width:1280px;margin:0 auto">
        <div style="text-align:center" class="reveal">
            <span class="section-tag">Latest Insights</span>
            <h2 class="section-title">Read Our <span class="gradient-text">Blog</span></h2>
            <p class="section-sub" style="margin:0 auto">Stay updated with the latest trends in social media growth and digital marketing.</p>
        </div>
        <div class="blog-grid">
            <article class="blog-card reveal reveal-delay-1">
                <div class="blog-thumb">
                    <div class="blog-thumb-gradient" style="background:linear-gradient(135deg,#1e3a8a,#1d4ed8)"></div>
                    <span class="material-icons-round blog-thumb-icon">trending_up</span>
                    <span class="blog-tag">Growth</span>
                </div>
                <div class="blog-body">
                    <h3 class="blog-title">Mastering Instagram Growth in 2025</h3>
                    <p class="blog-excerpt">Unlock the secrets of the algorithm and learn how to use engagement strategies to skyrocket your follower count overnight.</p>
                    <a class="blog-read" href="#">Read More <span class="mi material-icons-round">arrow_forward</span></a>
                </div>
            </article>
            <article class="blog-card reveal reveal-delay-2">
                <div class="blog-thumb">
                    <div class="blog-thumb-gradient" style="background:linear-gradient(135deg,#0c4a6e,#0e7490)"></div>
                    <span class="material-icons-round blog-thumb-icon">campaign</span>
                    <span class="blog-tag">Marketing</span>
                </div>
                <div class="blog-body">
                    <h3 class="blog-title">Social Media & Business Growth</h3>
                    <p class="blog-excerpt">The intersection of business and social presence. How top brands leverage SMM to maintain their influence in competitive markets.</p>
                    <a class="blog-read" href="#">Read More <span class="mi material-icons-round">arrow_forward</span></a>
                </div>
            </article>
            <article class="blog-card reveal reveal-delay-3">
                <div class="blog-thumb">
                    <div class="blog-thumb-gradient" style="background:linear-gradient(135deg,#312e81,#4338ca)"></div>
                    <span class="material-icons-round blog-thumb-icon">auto_awesome</span>
                    <span class="blog-tag">Future</span>
                </div>
                <div class="blog-body">
                    <h3 class="blog-title">The AI-Powered Future of SMM</h3>
                    <p class="blog-excerpt">Discover the next evolution of Social Media Marketing tools and how AI is changing the landscape for influencers worldwide.</p>
                    <a class="blog-read" href="#">Read More <span class="mi material-icons-round">arrow_forward</span></a>
                </div>
            </article>
        </div>
    </div>
</section>

<!-- ─── CTA ───────────────────────────────────────────────────── -->
<section id="cta">
    <div class="cta-box reveal">
        <div class="cta-glow"></div>
        <h2 class="cta-title">Ready to Expand Your Reach?</h2>
        <p class="cta-sub">Join thousands of users who have already unlocked the power of professional social media growth.</p>
        <div class="cta-btns">
            <a href="/register/" class="btn-primary">Create Free Account <span class="material-icons-round" style="font-size:18px">arrow_forward</span></a>
            <a href="/login/" class="btn-ghost">Sign In Now</a>
        </div>
    </div>
</section>

<!-- ─── Footer ────────────────────────────────────────────────── -->
<footer>
    <div class="footer-inner">
        <div class="footer-brand">OkxSmm</div>
        <div class="footer-links">
            <a href="/services/">Services</a>
            <a href="/api">API</a>
            <a href="#blog">Blog</a>
            <a href="/login/">Login</a>
            <a href="/register/">Register</a>
        </div>
    </div>
    <p class="footer-copy">© <?php echo date('Y'); ?> OkxSmm. All rights reserved. World's Most Professional Social Media Marketing Service.</p>
</footer>

<!-- ─── Scripts ───────────────────────────────────────────────── -->
<script>
/* ─ Custom Cursor ───────────────────────────────────────────────── */
const dot  = document.getElementById('cursor-dot');
const ring = document.getElementById('cursor-ring');
let mx = -100, my = -100, rx = -100, ry = -100;
document.addEventListener('mousemove', e => { mx = e.clientX; my = e.clientY; });
(function animCursor() {
    rx += (mx - rx) * .12; ry += (my - ry) * .12;
    dot.style.left  = mx + 'px'; dot.style.top  = my + 'px';
    ring.style.left = rx + 'px'; ring.style.top = ry + 'px';
    requestAnimationFrame(animCursor);
})();
document.querySelectorAll('a,button,.adv-card,.glass-card').forEach(el => {
    el.addEventListener('mouseenter', () => { dot.style.transform = 'translate(-50%,-50%) scale(2.5)'; ring.style.transform = 'translate(-50%,-50%) scale(1.5)'; });
    el.addEventListener('mouseleave', () => { dot.style.transform = 'translate(-50%,-50%) scale(1)';   ring.style.transform = 'translate(-50%,-50%) scale(1)'; });
});

/* ─ Particle Canvas ─────────────────────────────────────────────── */
(function() {
    const canvas = document.getElementById('particles');
    const ctx    = canvas.getContext('2d');
    let W, H, particles;
    const N = 70;
    function resize() { W = canvas.width = innerWidth; H = canvas.height = innerHeight; }
    resize(); addEventListener('resize', resize);
    function mkParticle() {
        return { x: Math.random()*W, y: Math.random()*H,
                 r: Math.random()*1.5+.3,
                 vx: (Math.random()-.5)*.25, vy: (Math.random()-.5)*.25,
                 a: Math.random()*.6+.1 };
    }
    particles = Array.from({length:N}, mkParticle);
    function draw() {
        ctx.clearRect(0,0,W,H);
        particles.forEach(p => {
            p.x += p.vx; p.y += p.vy;
            if (p.x < 0) p.x = W; if (p.x > W) p.x = 0;
            if (p.y < 0) p.y = H; if (p.y > H) p.y = 0;
            ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI*2);
            ctx.fillStyle = `rgba(99,160,255,${p.a})`; ctx.fill();
        });
        // Draw lines between nearby particles
        for (let i=0; i<particles.length; i++) {
            for (let j=i+1; j<particles.length; j++) {
                const dx = particles[i].x-particles[j].x, dy = particles[i].y-particles[j].y;
                const d = Math.sqrt(dx*dx+dy*dy);
                if (d < 130) {
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.strokeStyle = `rgba(59,130,246,${.12*(1-d/130)})`;
                    ctx.lineWidth = .5; ctx.stroke();
                }
            }
        }
        requestAnimationFrame(draw);
    }
    draw();
})();

/* ─ Scroll Reveal ───────────────────────────────────────────────── */
const reveals = document.querySelectorAll('.reveal');
const observer = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); observer.unobserve(e.target); } });
}, { threshold: .12 });
reveals.forEach(el => observer.observe(el));

/* ─ Header Shrink on Scroll ─────────────────────────────────────── */
addEventListener('scroll', () => {
    document.getElementById('site-header').classList.toggle('scrolled', scrollY > 60);
});

/* ─ Mobile Menu ─────────────────────────────────────────────────── */
function toggleMobileMenu() { document.getElementById('mobile-menu').classList.toggle('open'); }
function closeMobileMenu() { document.getElementById('mobile-menu').classList.remove('open'); }

/* ─ Counter Animation ───────────────────────────────────────────── */
function animateCounter(el, target, duration=2000) {
    if (!target || target <= 0) return;
    let start = 0, startTime = null;
    function step(ts) {
        if (!startTime) startTime = ts;
        const progress = Math.min((ts-startTime)/duration, 1);
        const ease = 1 - Math.pow(1-progress, 4);
        el.textContent = Math.floor(ease*target).toLocaleString() + '+';
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}
const counterObs = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            const count = parseInt(e.target.dataset.count);
            if (count) animateCounter(e.target, count);
            counterObs.unobserve(e.target);
        }
    });
}, { threshold: .5 });
document.querySelectorAll('[data-count]').forEach(el => counterObs.observe(el));

/* ─ Tilt Effect on cards ────────────────────────────────────────── */
document.querySelectorAll('.adv-card,.stat-feat-card,.blog-card').forEach(card => {
    card.addEventListener('mousemove', e => {
        const r = card.getBoundingClientRect();
        const x = (e.clientX - r.left) / r.width  - .5;
        const y = (e.clientY - r.top)  / r.height - .5;
        card.style.transform = `perspective(600px) rotateX(${-y*6}deg) rotateY(${x*6}deg) translateY(-8px)`;
    });
    card.addEventListener('mouseleave', () => {
        card.style.transform = '';
    });
});
</script>

</body>
</html>