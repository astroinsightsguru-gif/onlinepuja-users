<?php

namespace App\Console\Commands;

use App\Jobs\SendLiveScheduleReminderJob;
use App\Models\AstrologerModel\LiveAstro;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LiveScheduleReminder extends Command
{
    protected $signature = 'live-schedule:reminder';

    protected $description = 'Send reminders 15 minutes before astrologer live time';

    public function handle(): void
    {
        $timeLowerBound = Carbon::now()->addMinutes(15); 
    
    // Upper bound of the window (end of the reminder window: 16 minutes from now)
    $timeUpperBound = Carbon::now()->addMinutes(16); 
    
    // Format them for database comparison
    $lowerBoundFormatted = $timeLowerBound->toDateTimeString();
    $upperBoundFormatted = $timeUpperBound->toDateTimeString();
     $upcomingLiveSchedule = LiveAstro::whereRaw("CONCAT(schedule_live_date, ' ', schedule_live_time) >= ?", [$lowerBoundFormatted])
        ->whereRaw("CONCAT(schedule_live_date, ' ', schedule_live_time) < ?", [$upperBoundFormatted])
        ->get();
        if ($upcomingLiveSchedule->count() > 0) {
            \Log::info("LiveScheduleReminder: Dispatched reminders for {$upcomingLiveSchedule->count()} upcoming sessions.");
        }
        foreach ($upcomingLiveSchedule as $live) {
            SendLiveScheduleReminderJob::dispatch($live);
        }

        $this->info("Sent reminders for {$upcomingLiveSchedule->count()} live scheduled.");
    }
}
