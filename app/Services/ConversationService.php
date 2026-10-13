<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class ConversationService
{
    public static function set(string $remoteJid): void
    {
        Redis::setex("conversation_finished:{$remoteJid}", 300, 'true');
    }

    public static function finished(string $remoteJid): bool
    {
        $finished = Redis::get("conversation_finished:{$remoteJid}");

        if ($finished === 'true') {
            return true;
        }

        return false;
    }
}