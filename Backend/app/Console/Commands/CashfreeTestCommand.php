<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\CashfreeService;

class CashfreeTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashfree:test {--amount=1 : Test order amount in INR}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Cashfree credentials and API connectivity by creating a test order';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('============================================');
        $this->info('       CASHFREE PAYMENT GATEWAY TEST        ');
        $this->info('============================================');

        $dbAppId = DB::table('systemflag')->where('name', 'cashfreeAppId')->value('value');
        $dbSecret = DB::table('systemflag')->where('name', 'cashfreeSecretKey')->value('value');
        $dbTest = DB::table('systemflag')->where('name', 'testMode68')->value('value');
        $fg = DB::table('flaggroup')->where('flagGroupName', 'cashfree')->first();

        $envAppId = env('CASHFREE_APP_ID');
        $envSecret = env('CASHFREE_SECRET_KEY');

        $this->info('--- Credential Sources ---');
        $this->line('FlagGroup Active: ' . ($fg && $fg->isActive ? 'YES (1)' : 'NO (0)'));
        $this->line('DB cashfreeAppId:   ' . (!empty($dbAppId) ? substr($dbAppId, 0, 8) . '... (len: ' . strlen($dbAppId) . ')' : 'EMPTY'));
        $this->line('DB cashfreeSecret:  ' . (!empty($dbSecret) ? substr($dbSecret, 0, 8) . '... (len: ' . strlen($dbSecret) . ')' : 'EMPTY'));
        $this->line('DB testMode68:      ' . ($dbTest !== null ? $dbTest : 'NOT SET'));
        $this->line('ENV CASHFREE_APP_ID:     ' . (!empty($envAppId) ? substr($envAppId, 0, 8) . '... (len: ' . strlen($envAppId) . ')' : 'EMPTY'));
        $this->line('ENV CASHFREE_SECRET_KEY: ' . (!empty($envSecret) ? substr($envSecret, 0, 8) . '... (len: ' . strlen($envSecret) . ')' : 'EMPTY'));

        $service = new CashfreeService();

        $this->info("\n--- Cashfree Service Resolution ---");
        $this->line('Resolved App ID:   ' . (!empty($service->getAppId()) ? substr($service->getAppId(), 0, 8) . '... (len: ' . strlen($service->getAppId()) . ')' : 'EMPTY'));
        $this->line('Mode:              ' . ($service->isSandbox() ? 'SANDBOX (Test)' : 'PRODUCTION (Live)'));
        $this->line('Base URL:          ' . $service->getBaseUrl());
        $this->line('Is Configured:     ' . ($service->isConfigured() ? 'YES' : 'NO'));

        if (!$service->isConfigured()) {
            $this->error("\nCashfree is not configured! Please provide the App ID and Secret Key.");
            return self::FAILURE;
        }

        $amount = (float) $this->option('amount');
        $testOrderId = 'TEST_' . time() . '_' . rand(100, 999);
        $testCustomer = [
            'customer_id' => 'CUST_TEST_01',
            'customer_name' => 'OnlinePuja Devotee Test',
            'customer_email' => 'devotee.test@onlinepuja.live',
            'customer_phone' => '9876543210'
        ];
        $returnUrl = 'https://onlinepuja.live/payment-response?paymentOption=cashfree&order_id={order_id}';

        $this->info("\n--- Testing API Order Creation ---");
        $this->line("Target: {$service->getBaseUrl()}/orders");
        $this->line("Test Order ID: {$testOrderId}");
        $this->line("Test Amount: ₹{$amount}");

        try {
            $order = $service->createOrder($testOrderId, $amount, $testCustomer, $returnUrl);

            $this->info("\n[SUCCESS] Cashfree Order Created Successfully!");
            $this->line('CF Order ID:        ' . ($order['cf_order_id'] ?? 'N/A'));
            $this->line('Order Status:       ' . ($order['order_status'] ?? 'N/A'));
            $this->line('Payment Session ID: ' . (isset($order['payment_session_id']) ? substr($order['payment_session_id'], 0, 20) . '...' : 'N/A'));

            $this->info("\n--- Testing Order Fetch Verification ---");
            $fetched = $service->getOrder($testOrderId);
            $this->line('Fetched Status:     ' . ($fetched['order_status'] ?? 'N/A'));
            $this->line('Fetched Amount:     ₹' . ($fetched['order_amount'] ?? 'N/A'));

            $this->info("\n>>> CASHFREE API CONNECTION 100% OPERATIONAL! <<<");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("\n[FAILED] Cashfree Order Creation Failed!");
            $this->error('Error Message: ' . $e->getMessage());

            if (str_contains($e->getMessage(), '401') || str_contains($e->getMessage(), 'authentication') || str_contains($e->getMessage(), 'Credentials')) {
                $this->warn("\nTip: Check if the keys belong to Sandbox vs Production, and whether testMode is set appropriately.");
            }
            return self::FAILURE;
        }
    }
}
