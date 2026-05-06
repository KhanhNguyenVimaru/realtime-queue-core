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
        Schema::table('event_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'event_logs_user_id_created_at_index');
            $table->index(['event_id', 'created_at'], 'event_logs_event_id_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropIndex('event_logs_user_id_created_at_index');
            $table->dropIndex('event_logs_event_id_created_at_index');
        });
    }
};
