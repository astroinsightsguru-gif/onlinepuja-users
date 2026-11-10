<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetupCashfreeGatewayCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashfree:setup {--force : Re-create or reset flags}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Cashfree Payment Gateway in flaggroup and systemflag for Admin UI & Checkout';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('--- Setting up Cashfree Payment Gateway ---');

        // Check if Cashfree flaggroup exists
        $group = DB::table('flaggroup')->where('flagGroupName', 'cashfree')->first();

        if (!$group) {
            $maxId = DB::table('flaggroup')->max('id');
            $newId = max(68, $maxId + 1);

            $this->info("Creating Cashfree flaggroup with ID: {$newId} under parentFlagGroupId = 2");

            DB::table('flaggroup')->insert([
                'id' => $newId,
                'flagGroupName' => 'cashfree',
                'parentFlagGroupId' => 2, // Payment gateways group
                'isActive' => 1,
                'viewenable' => 1,
                'description' => 'Cashfree Payment Gateway (Cards, UPI, Netbanking, Wallets)'
            ]);

            $groupId = $newId;
        } else {
            $groupId = $group->id;
            $this->info("Found existing Cashfree flaggroup ID: {$groupId}");

            DB::table('flaggroup')->where('id', $groupId)->update([
                'parentFlagGroupId' => 2,
                'viewenable' => 1,
                'description' => 'Cashfree Payment Gateway (Cards, UPI, Netbanking, Wallets)'
            ]);
        }

        // Define system flags required for Cashfree
        $flags = [
            [
                'name' => 'cashfreeAppId',
                'displayName' => 'Cashfree Client ID / App ID',
                'description' => 'Your Cashfree API App ID from Cashfree Merchant Dashboard',
                'valueType' => 'Text',
                'defaultValue' => env('CASHFREE_APP_ID', ''),
            ],
            [
                'name' => 'cashfreeSecretKey',
                'displayName' => 'Cashfree Secret Key',
                'description' => 'Your Cashfree Secret Key from Cashfree Merchant Dashboard',
                'valueType' => 'Text',
                'defaultValue' => env('CASHFREE_SECRET_KEY', ''),
            ],
            [
                'name' => "testMode{$groupId}",
                'displayName' => 'Cashfree Sandbox Mode (1 for Test, 0 for Live)',
                'description' => 'Set 1 for Sandbox (sandbox.cashfree.com), 0 for Production (api.cashfree.com)',
                'valueType' => 'Text',
                'defaultValue' => env('CASHFREE_IS_SANDBOX', true) ? '1' : '0',
            ],
            [
                'name' => "currency{$groupId}",
                'displayName' => 'Cashfree Currency',
                'description' => 'Default currency code (INR)',
                'valueType' => 'Text',
                'defaultValue' => 'INR',
            ],
            [
                'name' => "currencySymbol{$groupId}",
                'displayName' => 'Cashfree Currency Symbol',
                'description' => 'Currency symbol (₹)',
                'valueType' => 'Text',
                'defaultValue' => '₹',
            ],
        ];

        foreach ($flags as $f) {
            $existing = DB::table('systemflag')->where('name', $f['name'])->first();

            if (!$existing) {
                DB::table('systemflag')->insert([
                    'name' => $f['name'],
                    'value' => $f['defaultValue'],
                    'displayName' => $f['displayName'],
                    'description' => $f['description'],
                    'valueType' => $f['valueType'],
                    'flagGroupId' => $groupId,
                    'isActive' => 1
                ]);
                $this->info("Inserted systemflag: {$f['name']}");
            } else {
                $this->info("Systemflag exists: {$f['name']}");
                if ($this->option('force')) {
                    DB::table('systemflag')->where('name', $f['name'])->update([
                        'displayName' => $f['displayName'],
                        'description' => $f['description'],
                        'flagGroupId' => $groupId,
                        'isActive' => 1
                    ]);
                    $this->info("Updated systemflag: {$f['name']}");
                }
            }
        }

        $this->info('Cashfree setup complete! Successfully registered in flaggroup and systemflag.');
        return self::SUCCESS;
    }
}
