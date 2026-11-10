<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('leads')) {
            Schema::create('leads', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('phone');
                $table->string('email')->nullable();
                $table->string('city')->nullable();
                $table->string('gotra')->nullable();
                $table->string('source')->default('inbound_whatsapp'); // 'whatsapp', 'meta_ads', 'google_search', 'abandoned_cart', 'website', 'referral'
                $table->string('stage')->default('new'); // 'new', 'contacted', 'qualified', 'proposal', 'won', 'lost'
                $table->decimal('deal_value', 10, 2)->default(0.00);
                $table->string('priority')->default('warm'); // 'hot', 'warm', 'cold'
                $table->string('interested_puja')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamp('last_contacted_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_threads')) {
            Schema::create('whatsapp_threads', function (Blueprint $table) {
                $table->id();
                $table->string('remote_jid')->unique(); // e.g. 917007158014@s.whatsapp.net
                $table->string('phone');
                $table->string('name')->nullable();
                $table->string('last_message')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->integer('unread_count')->default(0);
                $table->boolean('is_ai_handling')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_messages')) {
            Schema::create('whatsapp_messages', function (Blueprint $table) {
                $table->id();
                $table->string('thread_jid');
                $table->string('direction'); // 'inbound', 'outbound'
                $table->text('message');
                $table->string('media_url')->nullable();
                $table->string('media_type')->nullable(); // 'image', 'video', 'document', 'audio'
                $table->string('status')->default('sent'); // 'sent', 'delivered', 'read'
                $table->boolean('is_ai_generated')->default(false);
                $table->timestamp('timestamp')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_threads');
        Schema::dropIfExists('leads');
    }
};
