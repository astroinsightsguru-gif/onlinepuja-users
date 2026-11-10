<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PujaSalesAutomationService
{
    protected EvolutionWaService $wa;

    public function __construct(EvolutionWaService $wa)
    {
        $this->wa = $wa;
    }

    /**
     * Scan and recover abandoned / unpaid puja bookings via respectful WhatsApp reminders
     * Target: Orders placed 15 to 180 minutes ago that remain in 'pending' status
     */
    public function recoverAbandonedBookings(): array
    {
        $now = Carbon::now();
        $startTime = $now->copy()->subMinutes(180);
        $endTime = $now->copy()->subMinutes(15);

        $attempted = 0;
        $recovered = 0;

        try {
            // Fetch pending orders within recovery window
            $pendingOrders = DB::table('puja_orders as po')
                ->leftJoin('users as u', 'po.user_id', '=', 'u.id')
                ->where('po.puja_order_status', 'pending')
                ->where(function ($q) {
                    $q->whereNull('po.reminder_sent')
                      ->orWhere('po.reminder_sent', 0);
                })
                ->whereBetween('po.created_at', [$startTime, $endTime])
                ->select([
                    'po.id',
                    'po.user_id',
                    'po.puja_id',
                    'po.puja_name',
                    'po.package_name',
                    'po.order_total_price',
                    'po.address_name',
                    'po.address_number',
                    'u.name as user_name',
                    'u.contactNo as user_contact',
                    'po.created_at',
                ])
                ->get();

            $attempted = count($pendingOrders);

            foreach ($pendingOrders as $order) {
                $phone = !empty($order->address_number) ? $order->address_number : $order->user_contact;
                $phone = $this->wa->cleanNumber((string)$phone);

                if (empty($phone) || strlen($phone) < 10) {
                    continue;
                }

                $name = !empty($order->address_name) ? $order->address_name : (!empty($order->user_name) ? $order->user_name : 'भक्त');
                $pujaName = !empty($order->puja_name) ? $order->puja_name : 'वैदिक महापूजा';
                $amount = number_format((float)($order->order_total_price ?? 0));

                $message = "हर हर महादेव {$name} जी! 🙏\n\n" .
                    "OnlinePuja.live पर आपके परिवार के कल्याण हेतु *{$pujaName}* का पवित्र संकल्प फॉर्म प्रारंभ हुआ था।\n\n" .
                    "✨ *विशेष सूचना:*\n" .
                    "आगामी शुभ मुहूर्त में काशी विश्वनाथ के सिद्ध तीर्थ पर आचार्य जी आपके नाम व गोत्र के संकल्प हेतु प्रतीक्षारत हैं।\n\n" .
                    "🕉️ *संकल्प दक्षिणा:* ₹{$amount}\n" .
                    "👉 *अपनी पूजा का संकल्प अभी पूर्ण करें:*\nhttps://onlinepuja.live/pujas\n\n" .
                    "यदि पूजा विधि, सामग्री अथवा दक्षिणा से संबंधित कोई संशय हो, तो हमें तुरंत इसी WhatsApp पर बताएं। हमारे आचार्य आपकी सहायता हेतु उपस्थित हैं। 🪔";

                $dispatch = $this->wa->sendText($phone, $message);

                if (!empty($dispatch['ok']) || !empty($dispatch['key']) || !empty($dispatch['messageTimestamp'])) {
                    $recovered++;

                    // Mark reminder sent
                    DB::table('puja_orders')->where('id', $order->id)->update([
                        'reminder_sent' => 1,
                    ]);

                    // Persist to whatsapp inbox so managers see it
                    $remoteJid = $phone . '@s.whatsapp.net';
                    DB::table('whatsapp_messages')->insert([
                        'thread_jid' => $remoteJid,
                        'direction' => 'outbound',
                        'message' => $message,
                        'status' => 'sent',
                        'is_ai_generated' => true,
                        'timestamp' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('whatsapp_threads')->updateOrInsert(
                        ['remote_jid' => $remoteJid],
                        [
                            'phone' => $phone,
                            'name' => $name,
                            'last_message' => "Auto-Recovery: {$pujaName}",
                            'last_message_at' => $now,
                            'updated_at' => $now,
                        ]
                    );

                    Log::info("Abandoned puja order #{$order->id} recovery dispatched to {$phone}");
                }
            }
        } catch (\Throwable $e) {
            Log::error("PujaSalesAutomationService error: " . $e->getMessage());
        }

        return [
            'attempted' => $attempted,
            'recovered' => $recovered,
        ];
    }
}
