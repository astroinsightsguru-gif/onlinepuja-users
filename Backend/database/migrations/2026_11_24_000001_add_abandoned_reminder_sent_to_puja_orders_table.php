<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('puja_orders') && !Schema::hasColumn('puja_orders', 'abandoned_reminder_sent')) {
            Schema::table('puja_orders', function (Blueprint $table) {
                $table->tinyInteger('abandoned_reminder_sent')->default(0)->after('reminder_sent');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('puja_orders') && Schema::hasColumn('puja_orders', 'abandoned_reminder_sent')) {
            Schema::table('puja_orders', function (Blueprint $table) {
                $table->dropColumn('abandoned_reminder_sent');
            });
        }
    }
};
