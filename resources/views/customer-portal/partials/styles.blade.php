<style>
    .sanad-page { color: #1f2937; }
    .sanad-header { display:flex; justify-content:space-between; gap:16px; align-items:center; margin-bottom:18px; }
    .sanad-title { font-size:22px; font-weight:700; margin:0; }
    .sanad-muted { color:#6b7280; }
    .sanad-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; }
    .sanad-card { background:#fff; border:1px solid #eef0f5; border-radius:8px; box-shadow:0 10px 30px rgba(31,41,55,.04); }
    .sanad-card-header { padding:16px 18px; border-bottom:1px solid #eef0f5; font-weight:700; }
    .sanad-card-body { padding:18px; }
    .sanad-kpi { font-size:28px; font-weight:800; margin:4px 0 0; }
    .sanad-actions { display:flex; flex-wrap:wrap; gap:10px; }
    .sanad-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; border:0; border-radius:6px; padding:10px 14px; background:#f1533b; color:#fff; font-weight:700; text-decoration:none; min-height:40px; }
    .sanad-btn:hover { color:#fff; opacity:.92; }
    .sanad-btn.secondary { background:#eef2ff; color:#334155; }
    .sanad-btn.ghost { background:#fff; color:#f1533b; border:1px solid #f1533b; }
    .sanad-badge { display:inline-block; padding:5px 9px; border-radius:6px; background:#eef2ff; color:#334155; font-size:12px; font-weight:700; }
    .sanad-badge.warn { background:#fff1f2; color:#e11d48; }
    .sanad-badge.ok { background:#ecfdf5; color:#059669; }
    .sanad-table { width:100%; border-collapse:collapse; }
    .sanad-table th { background:#f1533b; color:#fff; padding:12px; font-weight:700; }
    .sanad-table td { padding:12px; border-bottom:1px solid #eef0f5; vertical-align:middle; }
    .sanad-form-control { width:100%; border:1px solid #e5e7eb; border-radius:6px; padding:10px 12px; min-height:42px; }
    .sanad-progress { height:8px; background:#eef0f5; border-radius:999px; overflow:hidden; }
    .sanad-progress span { display:block; height:100%; background:#f1533b; }
    .sanad-timeline { border-left:2px solid #f1533b; padding-left:16px; }
    .sanad-timeline-item { margin-bottom:16px; position:relative; }
    .sanad-timeline-item:before { content:""; width:10px; height:10px; border-radius:50%; background:#f1533b; position:absolute; left:-22px; top:5px; }
    .sanad-chat-box { max-height:360px; overflow:auto; border:1px solid #eef0f5; border-radius:8px; padding:12px; background:#fafafa; }
    .sanad-chat-message { background:#fff; border:1px solid #eef0f5; border-radius:8px; padding:10px; margin-bottom:10px; }
</style>
