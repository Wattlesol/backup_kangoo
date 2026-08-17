<x-master-layout>
@include('customer-portal.partials.styles')
<style>
    .sanad-ai-shell { display:grid; grid-template-columns: minmax(260px, 320px) 1fr; gap:16px; min-height: calc(100vh - 190px); }
    .sanad-ai-shell.context-collapsed { grid-template-columns: 44px 1fr; }
    .sanad-ai-sidebar, .sanad-ai-chat { background:#fff; border:1px solid #eef0f5; border-radius:8px; box-shadow:0 10px 30px rgba(31,41,55,.04); }
    .sanad-ai-sidebar { padding:16px; }
    .sanad-ai-shell.context-collapsed .sanad-ai-sidebar { padding:10px 6px; }
    .sanad-ai-shell.context-collapsed .sanad-ai-sidebar-content { display:none; }
    .sanad-ai-chat { display:flex; flex-direction:column; overflow:hidden; }
    .sanad-ai-thread { flex:1; padding:18px; overflow:auto; background:#f8fafc; }
    .sanad-ai-message { max-width:78%; padding:12px 14px; border-radius:10px; margin-bottom:12px; white-space:pre-line; }
    .sanad-ai-message.user { margin-left:auto; background:#f1533b; color:#fff; }
    .sanad-ai-message.agent { background:#fff; border:1px solid #e5e7eb; }
    .sanad-ai-composer { border-top:1px solid #eef0f5; padding:14px; background:#fff; }
    .sanad-ai-composer form { display:grid; grid-template-columns: minmax(180px, 280px) 1fr auto; gap:10px; align-items:end; }
    .sanad-ai-source { display:inline-block; margin:6px 6px 0 0; padding:4px 7px; border-radius:999px; background:#eef2ff; color:#334155; font-size:11px; }
    .sanad-ai-context-toggle { width:30px; height:30px; border:1px solid #e5e7eb; border-radius:6px; background:#fff; color:#334155; display:inline-flex; align-items:center; justify-content:center; }
    .sanad-ai-context-title { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:12px; }
    .sanad-ai-shell.context-collapsed .sanad-ai-context-title { justify-content:center; margin-bottom:0; }
    .sanad-ai-shell.context-collapsed .sanad-ai-context-label { display:none; }
    @media (max-width: 900px) { .sanad-ai-shell { grid-template-columns:1fr; } .sanad-ai-composer form { grid-template-columns:1fr; } .sanad-ai-message { max-width:100%; } }
</style>
<div class="container-fluid sanad-page">
    <div class="sanad-header">
        <div>
            <h1 class="sanad-title">Sanad AI Assistant</h1>
            <div class="sanad-muted">Ask about services, requirements, request status, next steps, documents, and billing.</div>
        </div>
    </div>

    <div class="sanad-ai-shell" id="sanad-ai-shell">
        <aside class="sanad-ai-sidebar">
            <div class="sanad-ai-context-title">
                <h5 class="font-weight-bold mb-0 sanad-ai-context-label">Request Context</h5>
                <button class="sanad-ai-context-toggle" type="button" id="sanad-ai-context-toggle" title="Toggle request context">
                    <i class="fas fa-angle-left"></i>
                </button>
            </div>
            <div class="sanad-ai-sidebar-content">
                @forelse($requests as $request)
                    <a class="d-block border-bottom py-2 text-decoration-none" href="{{ route('customer-portal.ai', ['booking_id' => $request->id]) }}">
                        <strong>{{ $request->sanad_reference ?? '#'.$request->id }}</strong>
                        <div class="sanad-muted">{{ optional($request->service)->name_en ?? optional($request->service)->name }}</div>
                        <span class="sanad-badge">{{ Str::headline($request->sanad_stage ?? $request->status) }}</span>
                    </a>
                @empty
                    <p class="sanad-muted">No requests yet.</p>
                @endforelse
            </div>
        </aside>

        <section class="sanad-ai-chat">
            <div class="sanad-card-header d-flex justify-content-between align-items-center">
                <span>AI Conversation</span>
            </div>
            <div class="sanad-ai-thread" id="sanad-ai-thread">
                @forelse($interactions->reverse() as $item)
                    <div class="sanad-ai-message user">{{ $item->question }}</div>
                    <div class="sanad-ai-message agent">
                        {{ $item->answer }}
                        @if(data_get($item->metadata, 'sources'))
                            <div>
                                @foreach(data_get($item->metadata, 'sources', []) as $source)
                                    <span class="sanad-ai-source">{{ $source['title'] ?? 'Knowledge' }}</span>
                                @endforeach
                            </div>
                        @endif
                        <div class="mt-2">
                            <span class="sanad-badge {{ $item->requires_escalation ? 'warn' : 'ok' }}">{{ $item->requires_escalation ? 'Human Review' : 'Answered' }}</span>
                            <small class="sanad-muted">{{ round(($item->confidence ?? 0) * 100) }}% confidence · {{ optional($item->created_at)->format('Y-m-d H:i') }}</small>
                        </div>
                        @if($item->status === 'handover_required')
                            <div class="mt-2 d-flex gap-2">
                                <form method="POST" action="{{ route('customer-portal.ai.handover', $item->id) }}">
                                    @csrf
                                    <input type="hidden" name="decision" value="yes">
                                    <button class="btn btn-sm btn-primary" type="submit">Yes</button>
                                </form>
                                <form method="POST" action="{{ route('customer-portal.ai.handover', $item->id) }}">
                                    @csrf
                                    <input type="hidden" name="decision" value="no">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">No</button>
                                </form>
                            </div>
                        @elseif($item->status === 'handover_accepted')
                            <div class="sanad-muted mt-2">Sanad team has been notified.</div>
                        @elseif($item->status === 'handover_declined')
                            <div class="sanad-muted mt-2">No handover requested.</div>
                        @elseif($item->status === 'manual_takeover')
                            <div class="sanad-muted mt-2">A Sanad agent is handling this request now.</div>
                        @endif
                    </div>
                @empty
                    <div class="sanad-ai-message agent">Ask me about a service, required documents, payment status, or what is currently happening on one of your requests.</div>
                @endforelse
            </div>
            <div class="sanad-ai-composer">
                <form method="post" action="{{ route('customer-portal.ai.ask') }}">
                    @csrf
                    <select class="sanad-form-control" name="booking_id">
                        <option value="">No request context</option>
                        @foreach($requests as $request)
                            <option value="{{ $request->id }}" {{ request('booking_id') == $request->id ? 'selected' : '' }}>
                                {{ $request->sanad_reference ?? '#'.$request->id }} - {{ optional($request->service)->name_en ?? optional($request->service)->name }}
                            </option>
                        @endforeach
                    </select>
                    <input class="sanad-form-control" name="question" placeholder="Type your question..." autocomplete="off" required autofocus>
                    <button class="sanad-btn" type="submit">Send</button>
                </form>
            </div>
        </section>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var thread = document.getElementById('sanad-ai-thread');
        if (thread) thread.scrollTop = thread.scrollHeight;
        var shell = document.getElementById('sanad-ai-shell');
        var toggle = document.getElementById('sanad-ai-context-toggle');
        if (shell && toggle) {
            toggle.addEventListener('click', function () {
                shell.classList.toggle('context-collapsed');
                var icon = toggle.querySelector('i');
                if (icon) {
                    icon.className = shell.classList.contains('context-collapsed') ? 'fas fa-angle-right' : 'fas fa-angle-left';
                }
            });
        }
    });
</script>
</x-master-layout>
