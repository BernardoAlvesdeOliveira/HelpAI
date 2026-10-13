<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp', function (Blueprint $table) {
            $table->text('audio_url')->nullable()->after('ai_message');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp', function (Blueprint $table) {
            $table->dropColumn('audio_url');
        });
    }
};
