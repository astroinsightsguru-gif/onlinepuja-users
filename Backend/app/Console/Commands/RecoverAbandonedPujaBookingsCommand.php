<?php

namespace App\Console\Commands;

use App\Services\PujaSalesAutomationService;
use Illuminate\Console\Command;

class RecoverAbandonedPujaBookingsCommand extends Command
{
    protected $signature = 'puja:recover-abandoned';

    protected $description = 'Autonomous recovery of unpaid/abandoned puja bookings via respectful WhatsApp reminders';

    public function handle(PujaSalesAutomationService $sales): int
    {
        $this->info("Scanning for abandoned puja bookings (15m-120m window)…");

        $res = $sales->recoverAbandonedBookings();

        $this->info("Attempted: {$res['attempted']} · Recovered/Notified via WhatsApp: {$res['recovered']}");

        return self::SUCCESS;
    }
}
