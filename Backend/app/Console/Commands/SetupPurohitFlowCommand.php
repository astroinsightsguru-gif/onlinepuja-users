<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class SetupPurohitFlowCommand extends Command
{
    protected $signature = 'purohit:setup {--seed-order}';
    protected $description = 'Set up schema and directories for Tirtha Purohit Sankalp Video & Prasad Tracking';

    public function handle()
    {
        $this->info("Checking puja_orders schema for Prasad & Sankalp tracking...");

        Schema::table('puja_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('puja_orders', 'prasad_tracking_number')) {
                $table->string('prasad_tracking_number', 100)->nullable()->after('puja_video');
            }
            if (!Schema::hasColumn('puja_orders', 'prasad_courier_name')) {
                $table->string('prasad_courier_name', 100)->nullable()->after('prasad_tracking_number');
            }
            if (!Schema::hasColumn('puja_orders', 'prasad_status')) {
                $table->string('prasad_status', 50)->default('order_placed')->after('prasad_courier_name');
            }
            if (!Schema::hasColumn('puja_orders', 'prasad_dispatched_at')) {
                $table->dateTime('prasad_dispatched_at')->nullable()->after('prasad_status');
            }
            if (!Schema::hasColumn('puja_orders', 'sankalp_gotra')) {
                $table->string('sankalp_gotra', 100)->nullable()->after('prasad_dispatched_at');
            }
            if (!Schema::hasColumn('puja_orders', 'devotee_names')) {
                $table->text('devotee_names')->nullable()->after('sankalp_gotra');
            }
            if (!Schema::hasColumn('puja_orders', 'sankalp_notes')) {
                $table->text('sankalp_notes')->nullable()->after('devotee_names');
            }
        });

        $this->info("Schema update completed successfully.");

        // Ensure storage directory for puja_videos exists
        $videoDir = public_path('puja_videos');
        if (!file_exists($videoDir)) {
            mkdir($videoDir, 0755, true);
            $this->info("Created public/puja_videos directory.");
        } else {
            $this->info("public/puja_videos directory exists.");
        }

        if ($this->option('seed-order')) {
            $existing = \App\Models\PujaOrder::count();
            if ($existing === 0) {
                \App\Models\PujaOrder::create([
                    'astrologer_id' => 1,
                    'user_id' => 1,
                    'puja_id' => 3,
                    'puja_name' => 'Maha Mrityunjaya Puja & Shanti Havan',
                    'package_name' => 'Family Sanctum Special',
                    'package_person' => '4 Members',
                    'address_name' => 'Vikramaditya Sharma',
                    'address_number' => '9876543210',
                    'address_flatno' => 'Flat 402, Shivam Enclave',
                    'address_locality' => 'Assi Ghat Road',
                    'address_city' => 'Varanasi',
                    'address_state' => 'Uttar Pradesh',
                    'address_country' => 'India',
                    'address_pincode' => 221005,
                    'order_price' => 1100,
                    'order_gst_amount' => 0,
                    'order_total_price' => 1100,
                    'payment_type' => 'Cashfree',
                    'puja_order_status' => 'placed',
                    'prasad_status' => 'order_placed',
                    'sankalp_gotra' => 'Kashyap Gotra',
                    'devotee_names' => 'Vikramaditya Sharma, Sunita Sharma, Aryan Sharma',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info("Seeded initial live test Puja Order #1 successfully.");
            } else {
                $this->info("Puja orders already exist, skipping seed.");
            }
        }

        return 0;
    }
}
