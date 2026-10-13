<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp', function (Blueprint $table) {
            $table->boolean('ai_message')->default(false)->after('from_me');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp', function (Blueprint $table) {
            $table->dropColumn('ai_message');
        });
    }
};
