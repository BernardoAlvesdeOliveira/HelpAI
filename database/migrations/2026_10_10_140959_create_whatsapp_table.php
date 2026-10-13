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
        Schema::create('whatsapp', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->longText('message')->nullable();
            $table->string('message_type')->nullable();
            $table->string('message_id')->unique();
            $table->boolean('from_me')->default(false);
            $table->string('phone_number', 20)->nullable();

            $table->uuid('instance_id')->nullable();
            $table->string('api_key')->nullable();

            $table->timestampTz('message_datetime')->nullable();

            $table->string('instance')->nullable();
            $table->string('remote_jid')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp');
    }
};
