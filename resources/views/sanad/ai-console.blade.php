@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $sanadRoleLabels = [
        'admin' => $isAr ? 'مدير النظام' : 'Admin',
        'provider' => $isAr ? 'الشريك' : 'Partner',
        'handyman' => $isAr ? 'الموظف' : 'Employee',
        'user' => $isAr ? 'العميل' : 'Customer',
        'customer' => $isAr ? 'العميل' : 'Customer',
    ];
    $sanadRoleLabel = fn ($role) => $sanadRoleLabels[$role] ?? Str::headline($role ?: 'role');
    $totalKnowledge = $aiSummary['knowledge_items'] ?? $knowledgeItems->total();
    $activeKnowledge = $aiSummary['active_knowledge_items'] ?? $knowledgeItems->where('is_active', 1)->count();
    $chunksCount = $knowledgeItems->sum('chunks_count');
@endphp

<x-master-layout>
<div class="quick-knowledge-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
    <!-- 1. Hero Header Banner -->
    <div class="quick-admin-hero">
        <div class="quick-admin-hero-content">
            <div class="quick-admin-hero-eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                <span>{{ $isAr ? 'إدارة واسترجاع المعرفة الحكومية' : 'Government Knowledge & RAG Index' }}</span>
            </div>
            <h1>{{ $isAr ? 'قاعدة المعرفة والخدمات الحكومية' : 'Government Knowledge Base' }}</h1>
            <p>{{ $isAr ? 'إدارة وتوثيق المقالات واللوائح والأسئلة الشائعة وفهرسة المواقع والمستندات لتقديم إجابات ذكية ودقيقة للمستفيدين.' : 'Manage, index, and scrape government transaction knowledge, FAQs, PDFs, and website sources for automated customer support.' }}</p>
        </div>

        <div class="quick-admin-hero-actions">
            <a href="{{ route('sanad.ai.escalations.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>{{ $isAr ? 'حالات التصعيد' : 'AI Escalations' }}</span>
            </a>
            <a href="{{ route('sanad.chat.workspace') }}" class="quick-admin-hero-btn quick-admin-hero-btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>{{ $isAr ? 'صندوق الوارد الموحد' : 'Unified Inbox' }}</span>
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    <div id="sanadAlertSlot">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:14px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#065f46;font-weight:700;">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:14px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#991b1b;font-weight:700;">
                <i class="fas fa-exclamation-triangle mr-2"></i> <strong>{{ $isAr ? 'خطأ:' : 'Error:' }}</strong>
                <ul class="mb-0 pl-3 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
    </div>

    <!-- 2. KPI Summary Strip -->
    <div class="quick-kpi-grid">
        <!-- Metric 1: Total Knowledge Items -->
        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? 'إجمالي عناصر المعرفة' : 'Total Knowledge Items' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(31,107,255,.1); color: #1f6bff;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ $totalKnowledge }}</div>
            <div class="quick-kpi-sub">
                <b class="quick-trend-up">{{ $totalKnowledge }}</b>
                <span>{{ $isAr ? 'وثيقة ومقال مفهرس' : 'indexed articles' }}</span>
            </div>
        </div>

        <!-- Metric 2: Active Items -->
        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? 'المعرفة المفعلة للاسترجاع' : 'Active In Search' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(16,185,129,.1); color: #10b981;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value" style="color:#10b981;">{{ $activeKnowledge }}</div>
            <div class="quick-kpi-sub">
                <b class="quick-trend-up">{{ $activeKnowledge }}</b>
                <span>{{ $isAr ? 'عنصر جاهز للإجابة' : 'live RAG items' }}</span>
            </div>
        </div>

        <!-- Metric 3: Indexed Chunks -->
        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? 'المقاطع النصية المفهرسة' : 'Indexed Chunks' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(139,92,246,.1); color: #8b5cf6;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ $chunksCount }}</div>
            <div class="quick-kpi-sub">
                <b class="quick-trend-up">{{ $chunksCount }}</b>
                <span>{{ $isAr ? 'مقطع ذكي في مخزن المتجهات' : 'vector chunks' }}</span>
            </div>
        </div>

        <!-- Metric 4: Vector Store Engine -->
        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? 'محرك المتجهات' : 'Vector Store' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(245,158,11,.1); color: #f59e0b;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value" style="font-size:18px;margin-top:6px;">{{ Str::headline(config('sanad.ai.vector_store', 'Qdrant / Postgres')) }}</div>
            <div class="quick-kpi-sub">
                <b class="quick-trend-up">{{ Str::headline(config('sanad.ai.provider', 'OpenAI')) }}</b>
                <span>{{ $isAr ? 'نموذج التضمين' : 'embeddings engine' }}</span>
            </div>
        </div>
    </div>

    <!-- 3. Split Ingestion & Knowledge Directory Grid -->
    <div class="row">
        @if(auth()->check() && auth()->check() && auth()->user()->hasAnyRole(['admin', 'demo_admin']))
            <!-- Left Column: Add / Ingest Knowledge Form -->
            <div class="col-xl-5 col-lg-5 mb-4">
                <div class="quick-card h-100">
                    <div class="quick-card-header" style="border-bottom: 1px solid var(--quick-shell-line); padding-bottom: 14px; margin-bottom: 16px;">
                        <div>
                            <h4 class="quick-card-title" style="font-size: 16px;">{{ $isAr ? 'إضافة واستيراد المعرفة' : 'Add & Ingest Knowledge' }}</h4>
                            <div class="quick-card-sub">{{ $isAr ? 'إدخال محتوى يدوي، رفع ملفات PDF، أو استخراج من مواقع حكومية' : 'Add manual text, upload PDFs, or crawl official web pages' }}</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('sanad.ai.knowledge.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="quick-filter-label">{{ $isAr ? 'عنوان المقال / الموضوع' : 'Topic / Article Title' }}</label>
                            <input type="text" name="title" class="form-control quick-input" placeholder="{{ $isAr ? 'مثال: متطلبات تجديد رخصة القيادة' : 'e.g. Commercial Registration Requirements' }}">
                        </div>

                        <div class="form-group mb-3">
                            <label class="quick-filter-label">{{ $isAr ? 'نطاق الظهور والصلاحيات' : 'Role Visibility Scope' }}</label>
                            <div style="display: flex; flex-wrap: wrap; gap: 10px; padding: 10px 12px; border-radius: 10px; background: color-mix(in srgb, var(--quick-shell-bg) 60%, var(--quick-shell-surface)); border: 1px solid var(--quick-shell-line);">
                                @foreach(config('sanad.document_visibility', []) as $role)
                                    <label style="margin: 0; font-size: 12px; font-weight: 700; color: var(--quick-shell-ink); display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                                        <input type="checkbox" name="visible_to[]" value="{{ $role }}" checked style="cursor: pointer;">
                                        <span>{{ $sanadRoleLabel($role) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Source Type Tabs -->
                        <div class="quick-filter-pills mb-3" role="tablist" style="padding: 4px; border-radius: 12px;">
                            <button type="button" class="active" data-toggle="tab" href="#knowledge-text-tab" role="tab" style="font-size: 11px; padding: 6px 12px;">{{ $isAr ? 'نص يدوي' : 'Manual Text' }}</button>
                            <button type="button" data-toggle="tab" href="#knowledge-pdf-tab" role="tab" style="font-size: 11px; padding: 6px 12px;">{{ $isAr ? 'رفع PDF' : 'PDF' }}</button>
                            <button type="button" data-toggle="tab" href="#knowledge-google-tab" role="tab" style="font-size: 11px; padding: 6px 12px;">{{ $isAr ? 'مستند Google' : 'Docs URL' }}</button>
                            <button type="button" data-toggle="tab" href="#knowledge-web-tab" role="tab" style="font-size: 11px; padding: 6px 12px;">{{ $isAr ? 'موقع إلكتروني' : 'Scraper' }}</button>
                        </div>

                        <div class="tab-content mb-4">
                            <!-- Tab: Manual Text -->
                            <div class="tab-pane fade show active" id="knowledge-text-tab" role="tabpanel">
                                <div class="form-group mb-2">
                                    <label class="quick-filter-label">{{ $isAr ? 'المحتوى والشرح' : 'Knowledge Content' }}</label>
                                    <textarea name="content" class="form-control quick-input" rows="6" placeholder="{{ $isAr ? 'ألصق محتوى الدليل أو المتطلبات والشروط هنا...' : 'Paste knowledge, rules, or procedural steps here...' }}"></textarea>
                                </div>

                                <details style="margin-top: 10px; border-radius: 10px; border: 1px solid var(--quick-shell-line); padding: 8px 12px; background: color-mix(in srgb, var(--quick-shell-bg) 50%, var(--quick-shell-surface));">
                                    <summary style="font-weight: 800; font-size: 12px; color: var(--quick-blue); cursor: pointer;">
                                        {{ $isAr ? 'إدخال النسختين العربية والإنجليزية يدوياً' : 'Enter bilingual versions manually' }}
                                    </summary>
                                    <div class="row mt-2">
                                        <div class="col-md-6 form-group mb-2"><label class="small text-muted font-weight-bold">English Title</label><input type="text" name="title_en" class="form-control quick-input"></div>
                                        <div class="col-md-6 form-group mb-2" dir="rtl"><label class="small text-muted font-weight-bold">العنوان العربي</label><input type="text" name="title_ar" class="form-control quick-input"></div>
                                        <div class="col-md-6 form-group mb-2"><label class="small text-muted font-weight-bold">English Content</label><textarea name="content_en" class="form-control quick-input" rows="4"></textarea></div>
                                        <div class="col-md-6 form-group mb-2" dir="rtl"><label class="small text-muted font-weight-bold">المحتوى العربي</label><textarea name="content_ar" class="form-control quick-input" rows="4"></textarea></div>
                                    </div>
                                </details>
                            </div>

                            <!-- Tab: PDF Upload -->
                            <div class="tab-pane fade" id="knowledge-pdf-tab" role="tabpanel">
                                <div class="quick-file-upload-box">
                                    <input type="file" id="knowledgePdfInput" name="knowledge_pdfs[]" accept="application/pdf,.pdf" multiple class="quick-file-input">
                                    <label for="knowledgePdfInput" class="quick-file-label" style="min-height: 120px; flex-direction: column; justify-content: center; text-align: center;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:28px;height:28px;color:var(--quick-blue);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                                        <strong style="color:var(--quick-shell-ink);font-size:13px;margin-top:6px;">{{ $isAr ? 'اضغط لاختيار ملفات PDF للفهرسة' : 'Click to select PDF files for indexing' }}</strong>
                                        <span class="text-muted small">{{ $isAr ? 'يمكنك تحديد ملف أو أكثر في وقت واحد' : 'Supports multiple files at once' }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Tab: Google Docs -->
                            <div class="tab-pane fade" id="knowledge-google-tab" role="tabpanel">
                                <div class="form-group">
                                    <label class="quick-filter-label">{{ $isAr ? 'رابط مستند Google' : 'Google Docs Share URL' }}</label>
                                    <input type="url" name="google_doc_url" class="form-control quick-input" placeholder="https://docs.google.com/document/d/...">
                                </div>
                            </div>

                            <!-- Tab: Website Scraper -->
                            <div class="tab-pane fade" id="knowledge-web-tab" role="tabpanel">
                                <div class="form-group mb-2">
                                    <label class="quick-filter-label">{{ $isAr ? 'رابط الموقع الرسمي' : 'Official Portal URL' }}</label>
                                    <input type="url" name="website_url" class="form-control quick-input" placeholder="https://www.my.gov.sa/...">
                                </div>
                                <div class="row">
                                    <div class="col-7">
                                        <label class="quick-filter-label">{{ $isAr ? 'نطاق الاستخراج' : 'Crawl Mode' }}</label>
                                        <select name="crawl_mode" class="form-control quick-select">
                                            <option value="single_url">{{ $isAr ? 'رابط واحد' : 'Single URL' }}</option>
                                            <option value="same_domain">{{ $isAr ? 'الموقع بالكامل' : 'Same-domain' }}</option>
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <label class="quick-filter-label">{{ $isAr ? 'أقصى صفحات' : 'Max Pages' }}</label>
                                        <input type="number" name="crawl_page_limit" class="form-control quick-input" min="1" max="50" value="10">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button class="quick-admin-hero-btn quick-admin-hero-btn-primary w-100" type="submit" style="min-height: 44px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>{{ $isAr ? 'حفظ وفهرسة المعرفة' : 'Save & Index Knowledge' }}</span>
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Right Column: Knowledge Repository List -->
        <div class="{{ auth()->check() && auth()->user()->hasAnyRole(['admin', 'demo_admin']) ? 'col-xl-7 col-lg-7' : 'col-12' }} mb-4">
            <div class="quick-card h-100">
                <div class="quick-card-header" style="border-bottom: 1px solid var(--quick-shell-line); padding-bottom: 14px; margin-bottom: 16px;">
                    <div>
                        <h4 class="quick-card-title" style="font-size: 16px;">{{ $isAr ? 'دليل ومكتبة عناصر المعرفة' : 'Official Knowledge Items Repository' }}</h4>
                        <div class="quick-card-sub">{{ $isAr ? 'عرض وتعديل وتدقيق المعرفة المفهرسة في النظام ومراقبة المقاطع' : 'Inspect, edit, and fine-tune indexed RAG entries' }}</div>
                    </div>
                </div>

                <div class="quick-knowledge-list">
                    @forelse($knowledgeItems as $item)
                        @php
                            $itemTitle = $isAr ? ($item->title_ar ?: $item->title) : $item->title;
                            $itemContent = $isAr ? ($item->content_ar ?: $item->content) : $item->content;
                            $cat = $isAr ? ($item->category_ar ?: $item->category ?: 'عام') : ($item->category ?: 'General');
                        @endphp
                        <div class="quick-knowledge-item" id="sanad-knowledge-item-{{ $item->id }}">
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                                <div style="min-width: 0; flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                                        <strong style="color:var(--quick-shell-ink);font-size:14px;">{{ $itemTitle }}</strong>
                                        <span class="quick-order-badge" style="display:inline-block;padding:2px 8px;border-radius:6px;background:rgba(31,107,255,.08);color:var(--quick-blue);font-size:11px;font-weight:700;">
                                            {{ $cat }}
                                        </span>
                                        @if($item->is_active)
                                            <span class="quick-badge quick-badge-success" style="font-size:10px;padding:2px 6px;">{{ $isAr ? 'نشط' : 'Active' }}</span>
                                        @else
                                            <span class="quick-badge quick-badge-neutral" style="font-size:10px;padding:2px 6px;">{{ $isAr ? 'معطل' : 'Inactive' }}</span>
                                        @endif
                                    </div>
                                    <p style="color:var(--quick-shell-muted);font-size:12px;line-height:1.5;margin-bottom:6px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        {{ $itemContent }}
                                    </p>
                                    <div style="display: flex; align-items: center; gap: 12px; font-size: 11px; color: var(--quick-shell-muted); font-weight: 700;">
                                        <span>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;display:inline;margin-inline-end:3px;"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                                            {{ $isAr ? 'المقاطع:' : 'Chunks:' }} {{ $item->chunks_count ?? 0 }}
                                        </span>
                                        @if($item->source_url)
                                            <span class="text-truncate" style="max-width: 200px;">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;display:inline;margin-inline-end:3px;"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                                {{ $item->source_url }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div style="display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;">
                                    <button type="button" class="quick-action-btn quick-action-btn-edit" data-toggle="modal" data-target="#knowledgeModal{{ $item->id }}" title="{{ $isAr ? 'تعديل وتحسين' : 'Fine Tune' }}" style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid var(--quick-shell-line);color:var(--quick-blue);background:var(--quick-shell-surface);cursor:pointer;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    <button type="button" class="quick-action-btn quick-action-btn-delete sanad-knowledge-delete-btn" data-delete-url="{{ route('sanad.ai.knowledge.delete', $item->id) }}" data-item-title="{{ $itemTitle }}" title="{{ $isAr ? 'حذف' : 'Delete' }}" style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid var(--quick-shell-line);color:#ef4444;background:var(--quick-shell-surface);cursor:pointer;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Fine Tune Modal -->
                        <div class="modal fade sanad-document-modal" id="knowledgeModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('sanad.ai.knowledge.update', $item->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title font-weight-bold">{{ $isAr ? 'تحسين وتعديل عنصر المعرفة' : 'Fine Tune Knowledge Item' }}</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group mb-3">
                                                <label class="quick-filter-label">{{ $isAr ? 'العنوان' : 'Title' }}</label>
                                                <input type="text" name="title" class="form-control quick-input" value="{{ $itemTitle }}" required>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label class="quick-filter-label">{{ $isAr ? 'محتوى المعرفة' : 'Knowledge Content' }}</label>
                                                <textarea name="content" class="form-control quick-input" rows="8" required>{{ $itemContent }}</textarea>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label class="quick-filter-label">{{ $isAr ? 'صلاحيات الظهور' : 'Role Visibility' }}</label>
                                                <div style="display:flex;gap:10px;flex-wrap:wrap;padding:8px 12px;border-radius:10px;background:color-mix(in srgb, var(--quick-shell-bg) 60%, var(--quick-shell-surface));border:1px solid var(--quick-shell-line);">
                                                    @foreach(config('sanad.document_visibility', []) as $role)
                                                        <label style="margin:0;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:6px;">
                                                            <input type="checkbox" name="visible_to[]" value="{{ $role }}" {{ in_array($role, $item->visible_to ?: [], true) ? 'checked' : '' }}>
                                                            <span>{{ $sanadRoleLabel($role) }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="activeSwitch{{ $item->id }}" {{ $item->is_active ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="activeSwitch{{ $item->id }}">{{ $isAr ? 'تفعيل في نظام استرجاع الذكاء الاصطناعي (RAG)' : 'Active in retrieval search' }}</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="quick-admin-hero-btn quick-admin-hero-btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                                            <button type="submit" class="quick-admin-hero-btn quick-admin-hero-btn-primary">{{ $isAr ? 'تحديث وفهرسة' : 'Update & Reindex' }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="sanad-empty-state">
                            {{ $isAr ? 'لا توجد عناصر معرفة مضافة حتى الآن. يمكنك إضافة عنصر جديد من النموذج الجانبي.' : 'No knowledge base items found. Add a new item from the sidebar form.' }}
                        </div>
                    @endforelse
                </div>

                @if($knowledgeItems->hasPages())
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $knowledgeItems->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@once
<style>
    .quick-knowledge-page {
        width: 100%;
    }

    .quick-knowledge-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .quick-knowledge-item {
        padding: 16px;
        border-radius: 14px;
        border: 1px solid var(--quick-shell-line);
        background: color-mix(in srgb, var(--quick-shell-bg) 40%, var(--quick-shell-surface));
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .quick-knowledge-item:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(10,22,38,.04);
        border-color: var(--quick-blue);
    }
</style>
@endonce

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.sanad-knowledge-delete-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('data-delete-url');
                const title = this.getAttribute('data-item-title') || 'this item';
                
                if (confirm('{{ $isAr ? "هل أنت متأكد من حذف عنصر المعرفة؟" : "Are you sure you want to delete" }} ' + title + '?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';
                    form.appendChild(csrf);

                    const method = document.createElement('input');
                    method.type = 'hidden';
                    method.name = '_method';
                    method.value = 'DELETE';
                    form.appendChild(method);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
</script>
</x-master-layout>
