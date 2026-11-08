<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserModel\Payment;
use Illuminate\Support\Facades\Log;

class PaymentStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:status-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update pending payments older than 5 minutes to failed status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $updatedRows = Payment::where('paymentStatus', 'pending')->where('created_at', '<=', now()->subMinutes(5))
                ->update([
                    'paymentStatus' => 'failed'
                ]);
    
            return self::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Payment status update cron failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
    
            return self::FAILURE;
        }
    }
}
