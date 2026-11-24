@extends('pages.growth-os.layout')

@section('growth_content')
@php
    $mode = $mode ?? 'supervised';
    $queue = $queue ?? collect([]);
    $logs = $logs ?? collect([]);
    $stats = $stats ?? [];
    $loops = $loops ?? [];
    $pending_count = $pending_count ?? 0;
    $sent_today = $sent_today ?? 0;
    $sent_count = $sent_count ?? 0;
    $confidence_floor = $confidence_floor ?? 85;
@endphp
<div style="display: flex; flex-direction: column; gap: 20px;">

    {{-- TOP BANNER: Autopilot Status Radar & High-Level Metrics --}}
    <div class="ai-hero-card" style="display: flex; flex-direction: column; gap: 18px; margin-bottom: 0;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <span class="ai-icon-bubble ai-icon-bubble-amber" style="width: 48px; height: 48px; font-size: 24px; box-shadow: 0 0 20px rgba(245, 158, 11, 0.35);">
                    ⚡
                </span>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <h2 style="font-size: 18px; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.01em;">
                            Autopilot Astrology Cockpit
                        </h2>
                        <span class="ai-pill {{ $mode === 'full' ? 'ai-pill-emerald' : ($mode === 'supervised' ? 'ai-pill-amber' : 'ai-pill-rose') }}" style="box-shadow: 0 0 10px rgba(245, 158, 11, 0.2);">
                            <span class="op-pulse-dot {{ $mode === 'full' ? 'op-pulse-dot-emerald' : ($mode === 'supervised' ? 'op-pulse-dot-amber' : 'op-pulse-dot-rose') }}"></span>
                            {{ $mode === 'full' ? 'Autonomous Mode' : ($mode === 'supervised' ? 'Supervised (Human Approval)' : 'Paused') }}
                        </span>
                    </div>
                    <p style="font-size: 12.5px; color: #94a3b8; margin: 4px 0 0 0;">
                        Autonomous Planetary Gochar, Panchang Forecasts, Festival Puja Drips, AI Twin Fallback & Churn Revival
                    </p>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <button type="button" class="ai-btn-gold select-run-loop" data-loop="all">
                    <span>⚡</span> Run All Loops Now
                </button>
                @if($pending_count > 0)
                    <button type="button" class="ai-btn-success select-approve-all" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; border: none; padding: 10px 18px; border-radius: 10px; font-weight: 800; cursor: pointer;">
                        <span>✓</span> Approve All ({{ $pending_count }})
                    </button>
                @endif
            </div>
        </div>

        {{-- 4-Pillar Stat Tiles --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
            <div style="background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; gap: 12px;">
                <div class="ai-icon-bubble ai-icon-bubble-amber" style="width: 36px; height: 36px; font-size: 16px;">⏳</div>
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #fbbf24; text-transform: uppercase; letter-spacing: 0.05em;">Staged for Review</span>
                    <div style="display: flex; align-items: baseline; gap: 6px; margin-top: 2px;">
                        <span style="font-size: 22px; font-weight: 900; color: #ffffff; font-family: monospace;">{{ $pending_count }}</span>
                        <span style="font-size: 11px; color: #fde68a;">awaiting 1-click</span>
                    </div>
                </div>
            </div>

            <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; gap: 12px;">
                <div class="ai-icon-bubble ai-icon-bubble-emerald" style="width: 36px; height: 36px; font-size: 16px;">🚀</div>
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #34d399; text-transform: uppercase; letter-spacing: 0.05em;">Dispatched Today</span>
                    <div style="display: flex; align-items: baseline; gap: 6px; margin-top: 2px;">
                        <span style="font-size: 22px; font-weight: 900; color: #ffffff; font-family: monospace;">{{ $sent_today }}</span>
                        <span style="font-size: 11px; color: #a7f3d0;">devotees reached</span>
                    </div>
                </div>
            </div>

            <div style="background: rgba(59, 130, 246, 0.12); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; gap: 12px;">
                <div class="ai-icon-bubble ai-icon-bubble-blue" style="width: 36px; height: 36px; font-size: 16px;">📬</div>
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #60a5fa; text-transform: uppercase; letter-spacing: 0.05em;">Lifetime Dispatched</span>
                    <div style="display: flex; align-items: baseline; gap: 6px; margin-top: 2px;">
                        <span style="font-size: 22px; font-weight: 900; color: #ffffff; font-family: monospace;">{{ $sent_count }}</span>
                        <span style="font-size: 11px; color: #bfdbfe;">total pushes</span>
                    </div>
                </div>
            </div>

            <div style="background: rgba(244, 63, 94, 0.12); border: 1px solid rgba(244, 63, 94, 0.3); border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; gap: 12px;">
                <div class="ai-icon-bubble ai-icon-bubble-rose" style="width: 36px; height: 36px; font-size: 16px;">🎯</div>
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #fb7185; text-transform: uppercase; letter-spacing: 0.05em;">AI Quality Floor</span>
                    <div style="display: flex; align-items: baseline; gap: 6px; margin-top: 2px;">
                        <span style="font-size: 22px; font-weight: 900; color: #ffffff; font-family: monospace;">{{ $confidence_floor }}%</span>
                        <span style="font-size: 11px; color: #fecdd3;">quality threshold</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 1.5: Online Puja AI Autonomous Agents Fleet --}}
    <div class="ai-box">
        <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>🕉️</span> Online Puja AI — Multi-Agent Autonomous Fleet
                </h3>
                <p style="font-size: 12px; color: #64748b; margin: 3px 0 0 0;">
                    Specialized agents generating daily Panchang reels, 12 Rashi forecasts, SEO blogs, festival drops, and B2B pitches.
                </p>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="ai-btn-gold select-run-all-agents">
                    <span>⚡</span> Run Active Agents Now
                </button>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-top: 16px;">
            @if(isset($agents))
                @foreach($agents as $ag)
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">
                            <span style="font-size: 26px;">{{ $ag->icon }}</span>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span class="ai-pill {{ $ag->is_active ? 'ai-pill-emerald' : 'ai-pill-slate' }}" style="font-size: 10px;">
                                    {{ $ag->is_active ? 'Active' : 'Paused' }}
                                </span>
                                <span class="ai-pill ai-pill-blue" style="font-size: 9.5px; text-transform: uppercase;">
                                    {{ $ag->mode }}
                                </span>
                            </div>
                        </div>

                        <h4 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 10px 0 4px 0;">{{ $ag->name }}</h4>
                        <p style="font-size: 11.5px; color: #64748b; line-height: 1.45; margin: 0;">{{ $ag->role_description }}</p>

                        <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #475569; display: flex; flex-direction: column; gap: 4px;">
                            <div>🕒 Cadence: <strong style="color: #0f172a;">{{ $ag->cron_cadence }}</strong></div>
                            <div>Last Run: <strong style="color: #0f172a;">{{ $ag->last_run_at ? \Carbon\Carbon::parse($ag->last_run_at)->diffForHumans() : 'Never' }}</strong></div>
                        </div>
                    </div>

                    <div style="margin-top: 14px; padding-top: 10px; border-top: 1px solid #e2e8f0; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <button type="button" class="ai-btn-secondary select-toggle-agent" data-id="{{ $ag->id }}" style="font-size: 11px; padding: 7px;">
                            {{ $ag->is_active ? 'Pause' : 'Activate' }}
                        </button>
                        <button type="button" class="ai-btn-gold select-run-agent" data-key="{{ $ag->agent_key }}" style="font-size: 11px; padding: 7px;">
                            ⚡ Run Now
                        </button>
                    </div>
                </div>
                @endforeach
            @endif
        </div>

        @if(isset($agentOutputs) && count($agentOutputs) > 0)
        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
            <h4 style="font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px 0;">
                ✨ Recent Synthesized Outputs (Reels, Graphics & Copy)
            </h4>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach($agentOutputs as $out)
                <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                    <div style="max-width: 750px;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span class="ai-pill ai-pill-purple" style="font-size: 9.5px;">{{ $out->agent->name ?? 'Agent' }}</span>
                            <strong style="font-size: 13px; color: #0f172a;">{{ $out->title }}</strong>
                            <span class="ai-pill {{ $out->status === 'published' ? 'ai-pill-emerald' : 'ai-pill-amber' }}" style="font-size: 9.5px;">
                                {{ ucfirst($out->status) }}
                            </span>
                            @if($out->media_url)
                                <a href="{{ $out->media_url }}" target="_blank" style="color: #2563eb; font-weight: 700; font-size: 11px; text-decoration: none;">
                                    View Media ↗
                                </a>
                            @endif
                        </div>
                        <p style="font-size: 11.5px; color: #64748b; margin: 4px 0 0 0; white-space: pre-line; max-height: 48px; overflow: hidden;">{{ substr($out->content_payload, 0, 160) }}...</p>
                    </div>

                    @if($out->status === 'pending_review')
                    <button type="button" class="ai-btn-success select-approve-output" data-id="{{ $out->id }}" style="font-size: 11px; padding: 6px 14px;">
                        ✓ Approve & Dispatch
                    </button>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- SECTION 2: The 5 Autonomous Autopilot Loops Radar --}}
    <div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
            <h3 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                <span>🛰️</span> 5 Autonomous Astrological Marketing Loops
            </h3>
            <span style="font-size: 11.5px; color: #64748b;">Autonomous triggers execute via Laravel Cron & LiveKit Gateway</span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
            @php
                $loopColors = [
                    'panchang' => 'amber',
                    'transit' => 'purple',
                    'festival' => 'rose',
                    'churn' => 'blue',
                    'twin' => 'emerald',
                ];
            @endphp
            @foreach($loops as $loopKey => $loopData)
            @php
                $colorKey = $loopColors[$loopKey] ?? 'blue';
            @endphp
            <div class="ai-box" style="display: flex; flex-direction: column; justify-content: space-between; margin-bottom: 0; border-top: 3px solid {{ $loopData['enabled'] ? ($colorKey === 'amber' ? '#f59e0b' : ($colorKey === 'purple' ? '#a855f7' : ($colorKey === 'rose' ? '#f43f5e' : ($colorKey === 'emerald' ? '#10b981' : '#3b82f6')))) : '#cbd5e1' }};">
                <div>
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
                        <span class="ai-icon-bubble ai-icon-bubble-{{ $colorKey }}" style="width: 40px; height: 40px; font-size: 20px;">
                            {{ $loopData['icon'] }}
                        </span>
                        <span class="ai-pill {{ $loopData['enabled'] ? 'ai-pill-' . $colorKey : 'ai-pill-slate' }}" style="font-size: 10px;">
                            {{ $loopData['enabled'] ? 'Active' : 'Disabled' }}
                        </span>
                    </div>

                    <h4 style="font-size: 13px; font-weight: 800; color: #0f172a; margin: 10px 0 0 0;">{{ $loopData['name'] }}</h4>
                    <p style="font-size: 11.5px; color: #64748b; line-height: 1.5; margin: 4px 0 0 0;">{{ $loopData['description'] }}</p>

                    <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; gap: 6px; font-size: 10.5px; color: #64748b;">
                        <i data-lucide="clock" style="width: 12px; height: 12px;"></i>
                        <span>{{ $loopData['cadence'] }}</span>
                    </div>
                </div>

                <div style="margin-top: 14px; padding-top: 10px; border-top: 1px solid #f1f5f9;">
                    <button type="button" class="ai-btn-secondary select-run-loop" data-loop="{{ $loopKey }}" style="width: 100%; font-size: 11.5px; padding: 7px 10px; border-radius: 8px; font-weight: 700; cursor: pointer;">
                        Trigger {{ ucfirst($loopKey) }}
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- SECTION 3: Human-in-the-Loop Review Queue (Staged Items) --}}
    <div class="ai-box">
        <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>👁️</span> Human-in-the-Loop Staging Area ({{ $queue->where('status', 'pending')->count() }} Pending)
                </h3>
                <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">
                    Messages staged for 1-click human approval before being pushed to devotee devices.
                </p>
            </div>

            @if($queue->where('status', 'pending')->count() > 0)
                <button type="button" class="ai-btn-success select-approve-all">
                    ✓ Approve All Staged
                </button>
            @endif
        </div>

        @if($queue->isEmpty())
            <div style="padding: 40px 0; text-align: center; color: #64748b;">
                <span style="font-size: 32px; display: block; margin-bottom: 6px;">🧘</span>
                <p style="font-size: 13px; font-weight: 700; color: #0f172a; margin: 0;">Queue is pristine. No pending notifications staged.</p>
                <p style="font-size: 11.5px; color: #94a3b8; margin: 4px 0 0 0;">Click "Run All Loops Now" above to stage today's astrological forecasts.</p>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 14px;">
                @foreach($queue as $item)
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;" id="queue-row-{{ $item->id }}">
                        <div style="max-width: 800px; display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <span class="ai-pill {{ $item->type === 'gochar' ? 'ai-pill-amber' : ($item->type === 'panchang' ? 'ai-pill-blue' : 'ai-pill-rose') }}" style="font-size: 9.5px;">
                                    {{ $item->type }}
                                </span>
                                <strong style="font-size: 13px; color: #0f172a;">{{ $item->title }}</strong>
                                <span class="ai-pill {{ $item->confidence_score >= 90 ? 'ai-pill-emerald' : 'ai-pill-amber' }}" style="font-size: 9.5px; font-family: monospace;">
                                    {{ $item->confidence_score }}% Conf
                                </span>
                                <span style="font-size: 10.5px; color: #64748b;">{{ \Carbon\Carbon::parse($item->created_at)->diffForHumans() }}</span>
                            </div>
                            <p style="font-size: 12px; color: #475569; line-height: 1.5; margin: 0;">{{ $item->message }}</p>
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            @if($item->status === 'pending')
                                <button type="button" class="ai-btn-success select-approve-item" data-id="{{ $item->id }}" style="padding: 6px 12px; font-size: 11.5px;">
                                    ✓ Approve & Send
                                </button>
                                <button type="button" class="ai-btn-danger select-reject-item" data-id="{{ $item->id }}" style="padding: 6px 10px; font-size: 11.5px;">
                                    ✕ Reject
                                </button>
                            @else
                                <span class="ai-pill {{ $item->status === 'sent' ? 'ai-pill-emerald' : 'ai-pill-rose' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- SECTION 4: Autopilot Settings & Guardrails Panel --}}
    <div class="ai-box">
        <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
            <span>⚙️</span> Autopilot Execution Policies & Guardrails
        </h3>

        <form action="{{ route('growth-os.autopilot.save') }}" method="POST" style="display: flex; flex-direction: column; gap: 16px;">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px;">
                {{-- Mode Selection --}}
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Autonomous Operating Mode</label>
                    <select name="autopilot_mode" class="ai-select">
                        <option value="supervised" {{ $mode === 'supervised' ? 'selected' : '' }}>Supervised (Human review before dispatch)</option>
                        <option value="full" {{ $mode === 'full' ? 'selected' : '' }}>Full Autopilot (100% Autonomous instant dispatch)</option>
                        <option value="off" {{ $mode === 'off' ? 'selected' : '' }}>Paused / Maintenance (Halt all autonomous dispatches)</option>
                    </select>
                    <p style="font-size: 11px; color: #64748b; margin-top: 6px; line-height: 1.4;">Supervised mode ensures a human can review every prediction before it reaches devotee phones.</p>
                </div>

                {{-- Confidence Floor --}}
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">AI Confidence Quality Floor (%)</label>
                    <input type="number" min="50" max="100" name="autopilot_confidence_floor" value="{{ $confidence_floor }}" class="ai-input">
                    <p style="font-size: 11px; color: #64748b; margin-top: 6px; line-height: 1.4;">Drafts scoring below this threshold are automatically flagged for manual review.</p>
                </div>
            </div>

            {{-- 5 Loop Toggles --}}
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px;">Autonomous Loop Toggles</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: #1e293b; cursor: pointer;">
                        <input type="checkbox" name="autopilot_gochar_enabled" value="1" {{ !empty($loops['gochar']['enabled']) ? 'checked' : '' }} style="width: 16px; height: 16px; accent-color: #f59e0b;">
                        <span>🪐 Planetary Gochar Drip</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: #1e293b; cursor: pointer;">
                        <input type="checkbox" name="autopilot_panchang_enabled" value="1" {{ !empty($loops['panchang']['enabled']) ? 'checked' : '' }} style="width: 16px; height: 16px; accent-color: #f59e0b;">
                        <span>🕉️ Vedic Panchang Broadcast</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: #1e293b; cursor: pointer;">
                        <input type="checkbox" name="autopilot_festival_enabled" value="1" {{ !empty($loops['festival']['enabled']) ? 'checked' : '' }} style="width: 16px; height: 16px; accent-color: #f59e0b;">
                        <span>🪔 Festival Puja Drips</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: #1e293b; cursor: pointer;">
                        <input type="checkbox" name="autopilot_twin_fallback_enabled" value="1" {{ !empty($loops['twin']['enabled']) ? 'checked' : '' }} style="width: 16px; height: 16px; accent-color: #f59e0b;">
                        <span>🤖 AI Twin Call Fallback</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: #1e293b; cursor: pointer;">
                        <input type="checkbox" name="autopilot_churn_enabled" value="1" {{ !empty($loops['churn']['enabled']) ? 'checked' : '' }} style="width: 16px; height: 16px; accent-color: #f59e0b;">
                        <span>✨ Devotee Churn Revival</span>
                    </label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; padding-top: 4px;">
                <button type="submit" class="ai-btn-dark">
                    Save Autopilot Policies
                </button>
            </div>
        </form>
    </div>

    {{-- SECTION 5: Recent Autopilot Execution Logs --}}
    <div class="ai-box">
        <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
            <span>📋</span> Recent Autonomous Execution Logs (brain_run_log)
        </h3>

        @if($logs->isEmpty())
            <p style="font-size: 12px; color: #94a3b8; text-align: center; padding: 24px 0;">No autopilot runs logged yet.</p>
        @else
            <div style="overflow-x: auto;">
                <table class="ai-table">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Status</th>
                            <th>Summary</th>
                            <th>Items OK</th>
                            <th>Duration</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td style="font-weight: 700; font-family: monospace; color: #0f172a;">{{ $log->task }}</td>
                                <td>
                                    <span class="ai-pill {{ $log->status === 'completed' ? 'ai-pill-emerald' : 'ai-pill-rose' }}" style="font-size: 10px;">
                                        {{ $log->status }}
                                    </span>
                                </td>
                                <td style="color: #475569;">{{ $log->summary }}</td>
                                <td style="font-family: monospace; font-weight: 800; color: #059669;">{{ $log->items_ok }}</td>
                                <td style="font-family: monospace; color: #64748b;">{{ $log->duration_ms }}ms</td>
                                <td style="color: #64748b;">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

{{-- Interactive AJAX Handlers --}}
<script>
(function() {
    function initAutopilot() {
        function runAjaxAction(payload, onSuccess) {
        fetch('{{ route("growth-os.action") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.ok) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(data.output || data.message || 'Action executed successfully!');
                } else {
                    alert('Success: ' + (data.output || data.message || 'Completed'));
                }
                if (onSuccess) onSuccess(data);
                else setTimeout(function() { window.location.reload(); }, 1200);
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error(data.error || 'Action failed');
                } else {
                    alert('Error: ' + (data.error || 'Failed'));
                }
            }
        })
        .catch(function(err) {
            alert('Request error: ' + err.message);
        });
    }

    // Trigger loop button
    document.querySelectorAll('.select-run-loop').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var loop = this.getAttribute('data-loop') || 'all';
            var original = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '⏳ Running…';
            var self = this;
            runAjaxAction({ action: 'autopilot_run_loop', loop: loop }, function() {
                self.disabled = false;
                self.innerHTML = original;
                setTimeout(function() { window.location.reload(); }, 1000);
            });
        });
    });

    // Approve item
    document.querySelectorAll('.select-approve-item').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var row = document.getElementById('queue-row-' + id);
            this.disabled = true;
            this.innerHTML = '⏳ Approving…';
            runAjaxAction({ action: 'autopilot_approve', id: id }, function() {
                if (row) row.remove();
            });
        });
    });

    // Reject item
    document.querySelectorAll('.select-reject-item').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var row = document.getElementById('queue-row-' + id);
            this.disabled = true;
            runAjaxAction({ action: 'autopilot_reject', id: id }, function() {
                if (row) row.remove();
            });
        });
    });

    // Approve all
    document.querySelectorAll('.select-approve-all').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Approve and dispatch all staged notifications to devotees immediately?')) return;
            this.disabled = true;
            this.innerHTML = '⏳ Approving all…';
            runAjaxAction({ action: 'autopilot_approve_all' }, function() {
                setTimeout(function() { window.location.reload(); }, 1200);
            });
        });
    });

    // 🤖 Agent Actions: Run Agent Now
    document.querySelectorAll('.select-run-agent').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var key = this.getAttribute('data-key');
            var orig = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '⏳ Running…';
            var self = this;
            runAjaxAction({ action: 'run_ai_agent', agent: key }, function(data) {
                self.disabled = false;
                self.innerHTML = orig;
                alert(data.message || 'Agent finished execution.');
                setTimeout(function() { window.location.reload(); }, 800);
            });
        });
    });

    // Toggle Agent Active / Paused
    document.querySelectorAll('.select-toggle-agent').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            this.disabled = true;
            runAjaxAction({ action: 'toggle_ai_agent', agent_id: id }, function(data) {
                alert(data.message || 'Agent toggled.');
                window.location.reload();
            });
        });
    });

    // Run All Agents
    document.querySelectorAll('.select-run-all-agents').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Run all active AI agents (Panchang, Rashifal, Blog, Festival, B2B, Retention) now?')) return;
            this.disabled = true;
            this.innerHTML = '⏳ Orchestrating Fleet…';
            runAjaxAction({ action: 'run_ai_agent', agent: 'all' }, function(data) {
                alert(data.message || 'All agents completed cycle.');
                setTimeout(function() { window.location.reload(); }, 1000);
            });
        });
    });

    // Approve Agent Output
    document.querySelectorAll('.select-approve-output').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            this.disabled = true;
            this.innerHTML = '✓ Dispatched';
            runAjaxAction({ action: 'approve_agent_output', output_id: id }, function(data) {
                alert(data.message || 'Output approved & dispatched!');
                setTimeout(function() { window.location.reload(); }, 800);
            });
        });
    });
    }

    if (document.readyState !== 'loading') {
        initAutopilot();
    } else {
        document.addEventListener('DOMContentLoaded', initAutopilot);
    }
})();
</script>
@endsection
