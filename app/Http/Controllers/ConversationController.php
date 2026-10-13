<?php

namespace App\Http\Controllers;

use App\Models\Whatsapp;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function getFinished(string $remoteJid): JsonResponse
    {
        return response()->json(
            ConversationService::finished($remoteJid)
        );
    }

    public function messages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'remote_jid' => ['required', 'string', 'max:100'],
            'before' => ['nullable', 'date'],
        ]);

        $messages = Whatsapp::query()
            ->where('remote_jid', $data['remote_jid'])
            ->whereNotNull('message')
            ->when(
                $data['before'] ?? null,
                fn ($query, $before) => $query->where('created_at', '<', $before),
            )
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->sortBy('created_at')
            ->values()
            ->map(fn (Whatsapp $message) => [
                'name' => $message->name,
                'message' => $message->message,
                'message_type' => $message->message_type,
                'from_me' => $message->from_me,
                'ai_message' => $message->ai_message,
                'audio_url' => $message->audio_url,
                'created_at' => $message->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'messages' => $messages,
            'has_more' => $messages->count() === 10,
        ]);
    }
}
