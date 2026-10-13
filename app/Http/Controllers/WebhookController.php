<?php

namespace App\Http\Controllers;

use App\Services\EvolutionService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        OrderService::handle($request->all());

        return response()->json(['message' => 'Recebido com sucesso']);
    }

    public function evolution(Request $request, EvolutionService $evolutionService)
    {
        Log::info('MENSAGEM RECEBIDA DO EVOLUTION API: ', $request->all());
        $payload = $request->input('body');
        $payload = is_array($payload) ? $payload : $request->all();

        if (($payload['event'] ?? null) !== 'messages.upsert') {
            return response()->json([
                'message' => 'Evento ignorado',
            ]);
        }

        $message = $evolutionService->textMessage($payload);

        return response()->json([
            'message' => 'Mensagem recebida com sucesso',
            'id' => $message->getKey(),
        ]);
    }

    public function saveMessage(Request $request, EvolutionService $evolutionService)
    {
        $message = $evolutionService->saveMessage($request->all());

        return response()->json([
            'message' => 'Mensagem salva com sucesso',
            'id' => $message->getKey(),
        ]);
    }

    public function sendMessage(Request $request, EvolutionService $evolutionService)
    {
        $data = $request->validate([
            'remote_jid' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:4096'],
        ]);

        $message = $evolutionService->sendTextMessage(
            $data['remote_jid'],
            $data['message'],
        );

        return response()->json([
            'message' => 'Mensagem enviada com sucesso',
            'id' => $message->getKey(),
        ]);
    }
}
