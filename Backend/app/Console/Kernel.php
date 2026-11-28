<?php

namespace App\Console;

use App\Http\Controllers\Admin\HoroscopeController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;


class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // COMMAND: Puja Reminders (Every Minute)
        $schedule
            ->command('puja:send-reminders')
            ->everyMinute()
            ->name('puja_send_reminders')
            ->withoutOverlapping();

        // COMMAND: Update Payment Status (Every Minute)
        $schedule
            ->command('payment:status-update')
            ->everyMinute()
            ->name('payment_status_update')
            ->withoutOverlapping();

        // 🧠 BRAIN: Live AI model sync (twice daily)
        $schedule->command('ai:sync-models')
            ->twiceDaily(2, 14)
            ->name('ai_model_sync')
            ->withoutOverlapping();

        // 🧠 BRAIN: Daily traffic snapshot into goals
        $schedule->command('goal:snapshot')
            ->dailyAt('23:40')
            ->name('goal_snapshot')
            ->withoutOverlapping();

        // 🧠 BRAIN: Closed-Loop Growth & Goal Achieving Engine
        $schedule->command('brain:growth --remediate')
            ->dailyAt('23:45')
            ->name('growth_engine_audit')
            ->withoutOverlapping();

        // 🧠 SELF-RESEARCHING: keyword tri-source sweep (weekly)
        $schedule->command('seo:research')
            ->weeklyOn(0, '02:00')
            ->name('seo_research')
            ->withoutOverlapping();

        // 🧠 SELF-GROWING: Content ramp - advance daily target gated by keyword headroom (daily 04:30 AM)
        $schedule->command('content:ramp')
            ->dailyAt('04:30')
            ->name('content_ramp')
            ->withoutOverlapping();

        // 🧠 SELF-GROWING: AI content drafts (3 slots: morning 04:45, midday 11:30, evening 17:30)
        $schedule->command('content:generate --limit=2')
            ->dailyAt('04:45')
            ->name('content_generate_morning')
            ->withoutOverlapping();

        $schedule->command('content:generate --limit=2')
            ->dailyAt('11:30')
            ->name('content_generate_midday')
            ->withoutOverlapping();

        $schedule->command('content:generate --limit=2')
            ->dailyAt('17:30')
            ->name('content_generate_evening')
            ->withoutOverlapping();

        // 🚀 AUTONOMOUS BLOG PUBLISHING: Publish approved articles (3-6 blogs/day safely spaced)
        // Morning Slot (07:00 AM)
        $schedule->command('content:publish --auto --limit=2')
            ->dailyAt('07:00')
            ->name('content_publish_morning')
            ->withoutOverlapping();

        // Midday Slot (01:00 PM)
        $schedule->command('content:publish --auto --limit=2')
            ->dailyAt('13:00')
            ->name('content_publish_midday')
            ->withoutOverlapping();

        // Evening Slot (07:00 PM)
        $schedule->command('content:publish --auto --limit=2')
            ->dailyAt('19:00')
            ->name('content_publish_evening')
            ->withoutOverlapping();

        // 📱 BLOG SOCIAL FANOUT: Promote published articles to Facebook & Instagram with Dual-App CTAs
        // Morning Slot Fanout (07:20 AM)
        $schedule->command('social:plan-blogs --limit=2 --horizon=14')
            ->dailyAt('07:20')
            ->name('social_plan_blogs_morning')
            ->withoutOverlapping();

        // Midday Slot Fanout (01:20 PM)
        $schedule->command('social:plan-blogs --limit=2 --horizon=14')
            ->dailyAt('13:20')
            ->name('social_plan_blogs_midday')
            ->withoutOverlapping();

        // Evening Slot Fanout (07:20 PM)
        $schedule->command('social:plan-blogs --limit=2 --horizon=14')
            ->dailyAt('19:20')
            ->name('social_plan_blogs_evening')
            ->withoutOverlapping();

        // 💰 REVENUE & SALES: Recover Abandoned Puja Bookings via Respectful WhatsApp Sequence (T+15m / T+2h)
        $schedule->command('puja:recover-abandoned')
            ->everyFifteenMinutes()
            ->name('puja_recover_abandoned')
            ->withoutOverlapping();

        // 🪔 HINDU FESTIVAL CAMPAIGN RADAR: Multi-Channel Orchestration for Upcoming Indian Festivals (Daily 06:30 AM)
        $schedule->command('festivals:campaign-radar --channels=whatsapp,email,push')
            ->dailyAt('06:30')
            ->name('festivals_campaign_radar')
            ->withoutOverlapping();

        // 🧠 SELF-HEALING: nightly technical scan
        $schedule->command('seo:scan')
            ->dailyAt('03:10')
            ->name('seo_scan')
            ->withoutOverlapping();

        // 🧠 SELF-HEALING: God Mode auto-fixes (weekly, capped, revertable)
        $schedule->command('seo:fix --god --limit=10')
            ->weeklyOn(0, '03:40')
            ->name('seo_god_fix')
            ->withoutOverlapping();

        // 🗓️ CALL: Daily Horoscope Generation (12:10 AM)
        $schedule
            ->call(function () {
                app(HoroscopeController::class)->generateDailyHorscope();
            })
            ->dailyAt('00:10')
            ->name('horoscope_generate_daily')
            ->withoutOverlapping();

        // CALL: Weekly Horoscope Generation (Monday, 12:30 AM)
        // NOTE: This task and the one below are identical in timing. Ensure this is intentional.
        $schedule
            ->call(function () {
                app(HoroscopeController::class)->generateWeeklyHorscope();
            })
            ->weeklyOn(1, '00:30')
            ->name('horoscope_generate_weekly')
            ->withoutOverlapping();

        // 📅 CALL: Yearly Horoscope Generation (Monday, 12:30 AM)
        // NOTE: This runs at the same time as weekly horoscope generation.
        $schedule
            ->call(function () {
                app(HoroscopeController::class)->generateYearlyHorscope();
            })
            ->weeklyOn(1, '00:30')
            ->name('horoscope_generate_yearly')
            ->withoutOverlapping();

        // COMMAND: Scheduled Notifications (Every Minute)
        $schedule
            ->command('notifications:send-scheduled')
            ->everyMinute()
            ->name('notifications_send_scheduled')
            ->withoutOverlapping();

        // COMMAND: Live Schedule Reminders (Every Minute)
        $schedule
            ->command('live-schedule:reminder')
            ->everyMinute()
            ->name('live_schedule_reminder')
            ->withoutOverlapping();

        // COMMAND: Call/Chat Deletion (Every Five Minutes)
        $schedule
            ->command('call-chat:delete')
            ->everyFiveMinutes()
            ->name('call_chat_delete')
            ->withoutOverlapping();

        // COMMAND: Reset Astro Free/Paid Status (Daily)
        $schedule
            ->command('reset:astro-free-paid')
            ->daily()
            ->name('reset_astro_free_paid')
            ->withoutOverlapping();

         // COMMAND: Refund Expired Scheduled Call Appointments (Daily)
        $schedule->command('appointments:refund-expired')
            ->daily()
            ->name('refund_expired_appointments')
            ->withoutOverlapping();

        // CALL: Internal Webhook Trigger (Every Minute)
        $schedule
            ->command('queue:work --stop-when-empty')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // ⚡ AUTOPILOT ENGINE: Run 5 Self-Driving Loops (Every 15 Minutes)
        $schedule->command('autopilot:run --loop=all')
            ->everyFifteenMinutes()
            ->name('autopilot_run_all')
            ->withoutOverlapping();

        // 📱 SOCIAL MEDIA: Multi-Channel Publishing Queue (Every 15 Minutes)
        $schedule->command('social:publish')
            ->everyFifteenMinutes()
            ->name('social_publish_queue')
            ->withoutOverlapping();

        // 🕉️ FESTIVAL SCANNER: Scan upcoming Hindu festivals and queue content plans (Daily 00:30 AM)
        $schedule->command('social:schedule --days=60')
            ->dailyAt('00:30')
            ->name('social_schedule_festivals')
            ->withoutOverlapping();

        // 🌺 SOCIAL CONTENT FANOUT: Turn approved plans into queued posts (Daily 01:00 AM)
        $schedule->command('social:plan')
            ->dailyAt('01:00')
            ->name('social_plan_fanout')
            ->withoutOverlapping();

        // 🧘 ASTROLOGER CO-BRANDING: Generate Transit Insights for Platform Astrologers (Daily 06:00 AM)
        $schedule->command('social:plan-astrologers')
            ->dailyAt('06:00')
            ->name('social_plan_astrologers')
            ->withoutOverlapping();

        // 🤖 ONLINE PUJA AI AUTONOMOUS FLEET: Automated Daily Synthesis
        $schedule->command('agent:run panchang')
            ->dailyAt('04:00')
            ->name('agent_run_panchang')
            ->withoutOverlapping();

        $schedule->command('agent:run rashifal')
            ->dailyAt('04:30')
            ->name('agent_run_rashifal')
            ->withoutOverlapping();

        // 🎬 DAILY YOUTUBE SHORTS & INSTAGRAM REELS: Auto-compile 9:16 vertical short and queue (Daily 05:00 AM)
        $schedule->command('video:generate-rashi-shorts --queue')
            ->dailyAt('05:00')
            ->name('video_generate_rashi_shorts_daily')
            ->withoutOverlapping();

        $schedule->command('agent:run seo_blog')
            ->dailyAt('06:00')
            ->name('agent_run_seo_blog')
            ->withoutOverlapping();

        $schedule->command('agent:run festival')
            ->dailyAt('00:00')
            ->name('agent_run_festival')
            ->withoutOverlapping();

        // 🌐 PROGRAMMATIC SEO: Generate XML Sitemaps & Ping Google/Bing (Twice Daily: 02:00 AM & 20:00 PM)
        $schedule->command('sitemap:generate --ping')
            ->dailyAt('02:00')
            ->name('sitemap_generate_night')
            ->withoutOverlapping();

        $schedule->command('sitemap:generate --ping')
            ->dailyAt('20:00')
            ->name('sitemap_generate_evening')
            ->withoutOverlapping();

        // 🕉️ WHATSAPP DEVOTIONAL ENGINE: Daily Hindi Panchang & 12-Rashi Broadcast (Daily 04:00 AM IST - Brahma Muhurta)
        $schedule->command('whatsapp:daily-panchang-broadcast --dispatch')
            ->dailyAt('04:00')
            ->name('whatsapp_daily_panchang_broadcast')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
