<style>
    .sanad-page { width:100%; max-width:1480px; margin-inline:auto; padding:0 4px 32px; color:var(--quick-shell-ink,#0a1626); }
    .sanad-header { display:flex; justify-content:space-between; gap:24px; align-items:center; margin-bottom:20px; padding:26px; border:1px solid #164e91; border-radius:16px; background:linear-gradient(135deg,#0f2933,#1769ff); color:#fff; box-shadow:0 10px 28px rgba(15,41,51,.12); }
    .sanad-header .sanad-muted { color:rgba(255,255,255,.78); }
    .sanad-title { color:inherit; font-size:clamp(24px,2vw,32px); font-weight:800; margin:0 0 5px; }
    .sanad-muted { color:#6a7c93; }
    .sanad-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px; }
    .sanad-card { min-width:0; background:var(--quick-shell-surface,#fff); color:var(--quick-shell-ink,#0a1626); border:1px solid var(--quick-shell-line,#d8e4f2); border-radius:16px; box-shadow:0 7px 22px rgba(15,41,51,.04); overflow:hidden; }
    .sanad-card-header { padding:17px 19px; border-bottom:1px solid #dce6f2; font-weight:800; }
    .sanad-card-body { padding:19px; }
    .sanad-card-body.row { margin:0; row-gap:12px; }
    .sanad-card-body.row > [class*="col-"] { padding-inline:6px; }
    .sanad-kpi { font-size:28px; font-weight:800; margin:4px 0 0; }
    .sanad-actions { display:flex; flex-wrap:wrap; gap:10px; }
    .sanad-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; border:1px solid #1769ff; border-radius:10px; padding:10px 14px; background:#1769ff; color:#fff; font-weight:700; text-decoration:none; min-height:42px; }
    .sanad-btn:hover { color:#fff; opacity:.92; }
    .sanad-btn.secondary { border-color:rgba(23,105,255,.18); background:#edf4ff; color:#155ee9; }
    .sanad-header .sanad-btn { border-color:#fff; background:#fff; color:#0f2933; }
    .sanad-header .sanad-btn.secondary { border-color:rgba(255,255,255,.3); background:rgba(255,255,255,.12); color:#fff; }
    .sanad-header .badge { display:inline-flex; align-items:center; gap:6px; padding:9px 12px!important; border:1px solid rgba(255,255,255,.22); border-radius:999px; background:rgba(255,255,255,.14)!important; color:#fff!important; white-space:nowrap; }
    .sanad-btn.ghost { background:#fff; color:#1769ff; border:1px solid #1769ff; }
    .sanad-badge { display:inline-block; padding:5px 9px; border-radius:999px; background:#edf4ff; color:#155ee9; font-size:12px; font-weight:700; }
    .sanad-badge.warn { background:#fff1f2; color:#e11d48; }
    .sanad-badge.ok { background:#ecfdf5; color:#059669; }
    .sanad-table { width:100%; border-collapse:collapse; color:inherit; }
    .sanad-table th { background:#f4f7fb; color:#60728a; padding:12px; font-size:11px; font-weight:800; white-space:nowrap; }
    .sanad-table td { padding:12px; border-bottom:1px solid #e2eaf4; vertical-align:middle; }
    .sanad-form-control { width:100%; border:1px solid var(--quick-shell-line,#cedbea); border-radius:10px; padding:10px 12px; min-height:44px; background:var(--quick-shell-surface,#fff); color:var(--quick-shell-ink,#0a1626); transition:border-color .18s ease,box-shadow .18s ease; }
    .sanad-form-control:focus { outline:0; border-color:#1769ff; box-shadow:0 0 0 3px rgba(23,105,255,.13); }
    .sanad-form-control::placeholder { color:#8a9ab0; }
    textarea.sanad-form-control { resize:vertical; }
    .sanad-card a:not(.sanad-btn) { color:#155ee9; }
    .sanad-card .border-bottom { border-color:var(--quick-shell-line,#e2eaf4)!important; }
    .sanad-card small { color:#789; }
    .sanad-card label { color:var(--quick-shell-ink,#0a1626); font-size:12px; font-weight:700; }
    .sanad-page .pagination { flex-wrap:wrap; gap:4px; }
    .sanad-page .page-link { min-width:38px; min-height:38px; display:grid; place-items:center; border-radius:9px!important; border-color:var(--quick-shell-line,#d8e4f2); background:var(--quick-shell-surface,#fff); color:var(--quick-shell-ink,#0a1626); }
    .sanad-page .page-item.active .page-link { border-color:#1769ff; background:#1769ff; color:#fff; }
    .sanad-progress { height:8px; background:#e5edf7; border-radius:999px; overflow:hidden; }
    .sanad-progress span { display:block; height:100%; background:linear-gradient(90deg,#1769ff,#20c5e8); }
    .sanad-timeline { border-inline-start:2px solid #1769ff; padding-inline-start:16px; }
    .sanad-timeline-item { margin-bottom:16px; position:relative; }
    .sanad-timeline-item:before { content:""; width:10px; height:10px; border-radius:50%; background:#1769ff; position:absolute; inset-inline-start:-22px; top:5px; }
    .sanad-chat-box { max-height:360px; overflow:auto; border:1px solid #d8e4f2; border-radius:12px; padding:12px; background:#f8fbff; }
    .sanad-chat-message { background:#fff; border:1px solid #dce6f2; border-radius:10px; padding:10px; margin-bottom:10px; }

    /* Floating AI Assistant Widget */
    .sanad-floating-ai-btn {
        position: fixed;
        bottom: 28px;
        right: 28px;
        z-index: 1050;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        background: linear-gradient(135deg, #1769ff, #0f5ad5);
        color: #fff !important;
        font-weight: 700;
        border-radius: 50px;
        box-shadow: 0 10px 25px rgba(23, 105, 255, 0.28);
        text-decoration: none !important;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .sanad-floating-ai-btn:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 15px 30px rgba(23, 105, 255, 0.34);
    }
    .sanad-floating-ai-icon {
        width: 32px;
        height: 32px;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }

    body.dark .sanad-page,
    body.quick-theme-dark .sanad-page,
    [data-theme="dark"] .sanad-page,
    [data-quick-theme="dark"] .sanad-page { color:#f4f8ff; }
    body.dark .sanad-card,
    body.dark .sanad-chat-message,
    body.quick-theme-dark .sanad-card,
    body.quick-theme-dark .sanad-chat-message,
    [data-theme="dark"] .sanad-card,
    [data-theme="dark"] .sanad-chat-message,
    [data-quick-theme="dark"] .sanad-card,
    [data-quick-theme="dark"] .sanad-chat-message { border-color:#213b58; background:#0d2136; color:#f4f8ff; }
    body.dark .sanad-card-header,
    body.quick-theme-dark .sanad-card-header,
    [data-theme="dark"] .sanad-card-header,
    [data-quick-theme="dark"] .sanad-card-header { border-color:#213b58; }
    body.dark .sanad-table th,
    body.quick-theme-dark .sanad-table th,
    [data-theme="dark"] .sanad-table th,
    [data-quick-theme="dark"] .sanad-table th { background:#10283f; color:#b9c9dc; }
    body.dark .sanad-table td,
    body.quick-theme-dark .sanad-table td,
    [data-theme="dark"] .sanad-table td,
    [data-quick-theme="dark"] .sanad-table td { border-color:#213b58; }
    body.dark .sanad-chat-box,
    body.quick-theme-dark .sanad-chat-box,
    [data-theme="dark"] .sanad-chat-box,
    [data-quick-theme="dark"] .sanad-chat-box { border-color:#213b58; background:#09192a; }
    body.quick-theme-dark .sanad-form-control,
    [data-quick-theme="dark"] .sanad-form-control { border-color:#294563; background:#0a1c2e; color:#f4f8ff; }
    body.quick-theme-dark .sanad-card label,
    [data-quick-theme="dark"] .sanad-card label { color:#dbe8f7; }

    @media (max-width:767.98px) {
        .sanad-page { padding-inline:0; }
        .sanad-header { align-items:stretch; flex-direction:column; padding:21px 18px; border-radius:14px; }
        .sanad-header .sanad-actions { width:100%; }
        .sanad-header .sanad-btn { flex:1 1 100%; }
        .sanad-grid { grid-template-columns:1fr 1fr; gap:9px; }
        .sanad-card-body { padding:15px; }
        .sanad-table { min-width:720px; }
        .sanad-card-body.row > [class*="col-"] { padding-inline:0; }
        .sanad-floating-ai-btn { inset-inline-end:14px; bottom:14px; padding:10px 13px; }
        .sanad-floating-ai-btn span { display:none; }
    }

    @media (max-width:420px) {
        .sanad-grid { grid-template-columns:1fr; }
    }

</style>
