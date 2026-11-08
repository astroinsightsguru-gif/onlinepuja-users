@extends('pages.growth-os.layout')

@section('growth_content')
@php
    $ideas = $ideas ?? collect([]);
    $drafted = $drafted ?? collect([]);
    $criticFailed = $criticFailed ?? collect([]);
    $approved = $approved ?? collect([]);
    $published = $published ?? collect([]);
@endphp
<div class="space-y-4">

    {{-- Top Action & Info Bar --}}
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="file-text" class="w-4 h-4 text-amber-500"></i> Autonomous Content Pipeline & Critic
                    </h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 uppercase tracking-wider">SEO Engine</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Keyword Research <span class="text-slate-300 font-bold mx-1">→</span> AI Draft Generation <span class="text-slate-300 font-bold mx-1">→</span> Vedic Critic Linting <span class="text-slate-300 font-bold mx-1">→</span> 1-Click Live Blog Publishing.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button id="btn-pipeline-generate" class="btn btn-sm bg-slate-900 hover:bg-slate-800 border-0 text-white font-semibold text-xs py-2 px-3.5 rounded-lg shadow-2xs flex items-center gap-1.5 transition">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-400"></i> Generate AI Content Draft
                </button>
            </div>
        </div>

        {{-- Pipeline Overview Metrics (High-Signal Colorful Tiles) --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-4 pt-3.5 border-t border-slate-100 text-center">
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 transition hover:shadow-sm">
                <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider block">Planned Ideas</span>
                <span class="text-xl font-black text-slate-900 mt-1 block font-mono">{{ $ideas->count() }}</span>
            </div>
            <div class="p-3 bg-blue-50/70 rounded-xl border border-blue-200 transition hover:shadow-sm">
                <span class="text-[10px] font-extrabold text-blue-700 uppercase tracking-wider block">AI Drafted</span>
                <span class="text-xl font-black text-blue-900 mt-1 block font-mono">{{ $drafted->count() }}</span>
            </div>
            <div class="p-3 bg-rose-50/70 rounded-xl border border-rose-200 transition hover:shadow-sm">
                <span class="text-[10px] font-extrabold text-rose-700 uppercase tracking-wider block">Critic Review</span>
                <span class="text-xl font-black text-rose-900 mt-1 block font-mono">{{ $criticFailed->count() }}</span>
            </div>
            <div class="p-3 bg-amber-50/70 rounded-xl border border-amber-200 transition hover:shadow-sm">
                <span class="text-[10px] font-extrabold text-amber-700 uppercase tracking-wider block">Approved</span>
                <span class="text-xl font-black text-amber-900 mt-1 block font-mono">{{ $approved->count() }}</span>
            </div>
            <div class="p-3 bg-emerald-50/70 rounded-xl border border-emerald-200 col-span-2 sm:col-span-1 transition hover:shadow-sm">
                <span class="text-[10px] font-extrabold text-emerald-700 uppercase tracking-wider block">Live on Blog</span>
                <span class="text-xl font-black text-emerald-900 mt-1 block font-mono">{{ $published->count() }}</span>
            </div>
        </div>
    </div>

    <style>
        #kanban-container::-webkit-scrollbar {
            height: 8px;
        }
        #kanban-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 8px;
        }
        #kanban-container::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 8px;
        }
        #kanban-container::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
    </style>

    {{-- KANBAN CONTROLS & VISIBILITY AID --}}
    <div class="flex items-center justify-between px-1 mb-2">
        <span class="text-xs text-slate-500 font-semibold flex items-center gap-1.5">
            <i data-lucide="move-horizontal" class="w-3.5 h-3.5 text-amber-500"></i>
            <span>5-Stage Pipeline: <strong class="text-slate-800">Ideas â†’ Drafts â†’ Critic Lint â†’ Approved â†’ Live Blog</strong></span>
        </span>
        <div class="flex items-center gap-1.5">
            <button type="button" onclick="document.getElementById('kanban-container').scrollBy({left: -320, behavior: 'smooth'})" class="p-1.5 px-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 shadow-2xs text-xs font-bold transition flex items-center gap-1">
                <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i> Prev Stage
            </button>
            <button type="button" onclick="document.getElementById('kanban-container').scrollBy({left: 320, behavior: 'smooth'})" class="p-1.5 px-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 shadow-2xs text-xs font-bold transition flex items-center gap-1">
                Next Stage <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    </div>

    {{-- KANBAN LANES (Responsive Horizontal Scroll Container with Visible Styled Scrollbar) --}}
    <div id="kanban-container" class="overflow-x-auto pb-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3.5 min-w-[1100px]">

            {{-- Column 1: Ideas --}}
            <div class="bg-slate-50/70 p-3 rounded-2xl border border-slate-200/80 flex flex-col min-h-[500px]">
                <div class="flex justify-between items-center pb-2.5 border-b border-slate-200/80 mb-3 px-1">
                    <span class="font-bold text-xs text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> 💡 Ideas & Keywords
                    </span>
                    <span class="px-2 py-0.5 bg-white text-slate-700 font-bold rounded-full text-[10px] border border-slate-200 shadow-2xs">{{ $ideas->count() }}</span>
                </div>
                <div class="space-y-2.5 flex-1 overflow-y-auto max-h-[620px] pr-0.5">
                    @forelse($ideas as $item)
                        <div class="bg-white p-3 rounded-xl shadow-2xs border border-slate-200/80 hover:border-slate-300 transition-all text-xs group">
                            <div class="font-bold text-slate-900 leading-snug">{{ $item->title }}</div>
                            <div class="flex justify-between items-center text-[10px] text-slate-400 mt-2.5 pt-2 border-t border-slate-100">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 font-medium text-slate-600 uppercase">{{ $item->pillar ?: 'General' }}</span>
                                <button class="text-rose-500 hover:text-rose-700 font-bold btn-change-status p-1" data-id="{{ $item->id }}" data-status="deleted" title="Delete Idea">✕</button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-slate-400 text-xs italic">
                            No ideas currently in queue.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Column 2: Drafted --}}
            <div class="bg-blue-50/40 p-3 rounded-2xl border border-blue-100 flex flex-col min-h-[500px]">
                <div class="flex justify-between items-center pb-2.5 border-b border-blue-200/70 mb-3 px-1">
                    <span class="font-bold text-xs text-blue-900 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span> ✍️ AI Drafts
                    </span>
                    <span class="px-2 py-0.5 bg-white text-blue-800 font-bold rounded-full text-[10px] border border-blue-200 shadow-2xs">{{ $drafted->count() }}</span>
                </div>
                <div class="space-y-2.5 flex-1 overflow-y-auto max-h-[620px] pr-0.5">
                    @forelse($drafted as $item)
                        <div class="bg-white p-3 rounded-xl shadow-2xs border border-blue-100 hover:border-blue-300 transition-all text-xs">
                            <div class="font-bold text-slate-900 leading-snug">{{ $item->title }}</div>
                            <div class="text-[10px] text-slate-400 mt-1.5">
                                {{ \Carbon\Carbon::parse($item->created_at)->diffForHumans() }}
                            </div>
                            <div class="flex gap-1.5 mt-2.5 pt-2 border-t border-slate-100">
                                <button class="flex-1 py-1 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[10px] btn-preview-article" data-item="{{ json_encode($item) }}">View Draft</button>
                                <button class="flex-1 py-1 px-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[10px] btn-change-status" data-id="{{ $item->id }}" data-status="approved">✓ Approve</button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-slate-400 text-xs italic">
                            No drafts awaiting review.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Column 3: Critic Review --}}
            <div class="bg-rose-50/40 p-3 rounded-2xl border border-rose-100 flex flex-col min-h-[500px]">
                <div class="flex justify-between items-center pb-2.5 border-b border-rose-200/70 mb-3 px-1">
                    <span class="font-bold text-xs text-rose-900 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span> ⚠️ Critic Lint
                    </span>
                    <span class="px-2 py-0.5 bg-white text-rose-800 font-bold rounded-full text-[10px] border border-rose-200 shadow-2xs">{{ $criticFailed->count() }}</span>
                </div>
                <div class="space-y-2.5 flex-1 overflow-y-auto max-h-[620px] pr-0.5">
                    @forelse($criticFailed as $item)
                        <div class="bg-white p-3 rounded-xl shadow-2xs border border-rose-200 hover:border-rose-300 transition-all text-xs">
                            <div class="font-bold text-slate-900 leading-snug">{{ $item->title }}</div>
                            @php
                                $itemPayload = json_decode((string) ($item->payload ?? ''), true);
                                $criticFeedback = is_array($itemPayload) ? ($itemPayload['critic_feedback'] ?? ($itemPayload['critic_note'] ?? null)) : null;
                            @endphp
                            @if($criticFeedback)
                                <div class="text-[10px] text-rose-700 mt-1.5 bg-rose-50/80 p-2 rounded-lg border border-rose-100 leading-relaxed font-mono">
                                    <strong>Lint:</strong> {{ Str::limit($criticFeedback, 70) }}
                                </div>
                            @endif
                            <div class="flex gap-1.5 mt-2.5 pt-2 border-t border-slate-100">
                                <button class="flex-1 py-1 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[10px] btn-preview-article" data-item="{{ json_encode($item) }}">Inspect</button>
                                <button class="flex-1 py-1 px-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-[10px] btn-change-status" data-id="{{ $item->id }}" data-status="approved">Override</button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-emerald-600 font-medium text-xs">
                            ✓ All drafts pass Vedic Critic quality standards!
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Column 4: Approved --}}
            <div class="bg-amber-50/40 p-3 rounded-2xl border border-amber-100 flex flex-col min-h-[500px]">
                <div class="flex justify-between items-center pb-2.5 border-b border-amber-200/70 mb-3 px-1">
                    <span class="font-bold text-xs text-amber-900 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> ✅ Ready to Publish
                    </span>
                    <span class="px-2 py-0.5 bg-white text-amber-800 font-bold rounded-full text-[10px] border border-amber-200 shadow-2xs">{{ $approved->count() }}</span>
                </div>
                <div class="space-y-2.5 flex-1 overflow-y-auto max-h-[620px] pr-0.5">
                    @forelse($approved as $item)
                        <div class="bg-white p-3 rounded-xl shadow-2xs border border-amber-200 hover:border-amber-300 transition-all text-xs">
                            <div class="font-bold text-slate-900 leading-snug">{{ $item->title }}</div>
                            <div class="text-[10px] text-slate-400 mt-1">
                                Pillar: <strong class="text-slate-600">{{ $item->pillar ?: 'General' }}</strong>
                            </div>
                            <div class="flex gap-1.5 mt-2.5 pt-2 border-t border-slate-100">
                                <button class="flex-1 py-1 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[10px] btn-preview-article" data-item="{{ json_encode($item) }}">Review</button>
                                <button class="flex-1 py-1 px-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-[10px] btn-change-status" data-id="{{ $item->id }}" data-status="published">Publish Now</button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-slate-400 text-xs italic">
                            No articles waiting for publishing.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Column 5: Published --}}
            <div class="bg-emerald-50/40 p-3 rounded-2xl border border-emerald-100 flex flex-col min-h-[500px]">
                <div class="flex justify-between items-center pb-2.5 border-b border-emerald-200/70 mb-3 px-1">
                    <span class="font-bold text-xs text-emerald-900 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> 🚀 Live on Blog
                    </span>
                    <span class="px-2 py-0.5 bg-white text-emerald-800 font-bold rounded-full text-[10px] border border-emerald-200 shadow-2xs">{{ $published->count() }}</span>
                </div>
                <div class="space-y-2.5 flex-1 overflow-y-auto max-h-[620px] pr-0.5">
                    @forelse($published as $item)
                        @php
                            $payload = json_decode((string) ($item->payload ?? ''), true);
                            $slug = is_array($payload) ? ($payload['slug'] ?? null) : null;
                        @endphp
                        <div class="bg-white p-3 rounded-xl shadow-2xs border border-emerald-100 hover:border-emerald-300 transition-all text-xs">
                            <div class="font-bold text-slate-900 leading-snug">{{ $item->title }}</div>
                            <div class="text-[10px] text-emerald-700 font-medium mt-1 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Indexed & Live
                            </div>
                            <div class="flex gap-1.5 mt-2.5 pt-2 border-t border-slate-100">
                                <button class="flex-1 py-1 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[10px] btn-preview-article" data-item="{{ json_encode($item) }}">Read Article</button>
                                @if($slug)
                                    <a href="/blog/{{ $slug }}" target="_blank" class="p-1 px-2 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-[10px] font-bold flex items-center gap-1">
                                        <i data-lucide="external-link" class="w-3 h-3"></i> URL
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-slate-400 text-xs italic">
                            Published articles will appear here.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ARTICLE PREVIEW MODAL --}}
<div id="article-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display:none;">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden shadow-2xl border border-slate-200">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center">
            <h4 id="modal-title" class="font-bold text-sm flex items-center gap-2">
                <i data-lucide="file-text" class="w-4 h-4 text-amber-400"></i> Article Preview
            </h4>
            <button id="modal-close" class="text-white hover:text-amber-400 text-base font-bold cursor-pointer">✕</button>
        </div>
        <div class="p-5 overflow-y-auto flex-1 space-y-3.5 text-xs">
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div class="text-slate-500 font-medium text-[11px]">Slug & Meta Description:</div>
                <div id="modal-slug" class="font-mono text-slate-900 font-bold mt-0.5"></div>
                <div id="modal-meta" class="text-slate-600 italic mt-1 leading-relaxed"></div>
            </div>
            <div>
                <div class="font-bold text-slate-800 text-xs mb-2">HTML Article Content:</div>
                <div id="modal-content" class="prose prose-sm max-w-none text-slate-700 border border-slate-200 p-4 rounded-xl bg-white"></div>
            </div>
        </div>
        <div class="p-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
            <button id="modal-close-btn" class="btn btn-sm btn-outline-secondary text-xs px-4 py-1.5 bg-white">Close</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Generate AI Content Draft Action
    var btnGen = document.getElementById('btn-pipeline-generate');
    if (btnGen) {
        btnGen.addEventListener('click', function() {
            var b = this;
            var originalHtml = b.innerHTML;
            b.disabled = true;
            b.innerHTML = '<span class="animate-spin inline-block mr-1">⏳</span> Drafting Article via AI…';

            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ action: 'generate_content', limit: 2 })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                b.disabled = false;
                b.innerHTML = originalHtml;
                if (typeof lucide !== 'undefined') lucide.createIcons();
                if (d.ok) {
                    window.showGrowthNotification('success', d.output || 'Draft created successfully!');
                    setTimeout(function() { window.location.reload(); }, 1200);
                } else {
                    window.showGrowthNotification('error', d.error || 'Draft generation failed');
                }
            })
            .catch(function(err) {
                b.disabled = false;
                b.innerHTML = originalHtml;
                if (typeof lucide !== 'undefined') lucide.createIcons();
                window.showGrowthNotification('error', err.message || 'Draft generation network error');
            });
        });
    }

    // 2. Status Change (Approve, Publish, Delete)
    document.querySelectorAll('.btn-change-status').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var status = this.getAttribute('data-status');
            var card = this.closest('.bg-white');
            var origHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '…';

            fetch('{{ url("growth-os/content") }}/' + id + '/status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ status: status })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.ok) {
                    window.showGrowthNotification('success', d.message || 'Updated status');
                    setTimeout(function() { window.location.reload(); }, 800);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                    window.showGrowthNotification('error', d.error || 'Update failed');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
                window.showGrowthNotification('error', err.message || 'Status update network error');
            });
        });
    });

    // 3. Modal Preview
    var modal = document.getElementById('article-modal');
    var modalClose = document.getElementById('modal-close');
    var modalCloseBtn = document.getElementById('modal-close-btn');

    document.querySelectorAll('.btn-preview-article').forEach(function(btn) {
        btn.addEventListener('click', function() {
            try {
                var item = JSON.parse(this.getAttribute('data-item'));
                var payload = typeof item.payload === 'string' ? JSON.parse(item.payload) : (item.payload || {});

                document.getElementById('modal-title').textContent = item.title || 'Article Preview';
                document.getElementById('modal-slug').textContent = '/' + (payload.slug || item.pillar || 'article');
                document.getElementById('modal-meta').textContent = payload.meta_description || 'No meta description provided.';
                document.getElementById('modal-content').innerHTML = payload.html || '<p class="text-slate-400 italic">No HTML draft content available for this idea.</p>';

                modal.style.display = 'flex';
            } catch (e) {
                console.error('Preview error', e);
            }
        });
    });

    if (modalClose) modalClose.addEventListener('click', function() { modal.style.display = 'none'; });
    if (modalCloseBtn) modalCloseBtn.addEventListener('click', function() { modal.style.display = 'none'; });
});
</script>
@endsection
