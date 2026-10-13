<?php

namespace App\Services;

use App\Models\Whatsapp;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class EvolutionService
{
    public function textMessage(array $datas): Whatsapp
    {
        $data = $datas['data'] ?? null;
        $key = is_array($data) ? ($data['key'] ?? null) : null;
        $message = is_array($data) ? ($data['message'] ?? null) : null;

        if (! is_array($data) || ! is_array($key) || ! is_array($message)) {
            throw new InvalidArgumentException('Payload de mensagem da Evolution API inválido.');
        }

        $messageId = $key['id'] ?? null;
        $remoteJid = $key['remoteJid'] ?? null;
        $messageType = $data['messageType'] ?? 'conversation';
        $audioUrl = null;
        switch ($messageType) {
            case 'audioMessage':
                [$text, $audioUrl] = $this->processAudioMessage($data, $message);
                break;
            default:
                $text = $message['conversation'] ?? $message['extendedTextMessage']['text'] ?? null;
                break;
        }

        if (! is_string($messageId) || $messageId === '' || ! is_string($text) || $text === '') {
            throw new InvalidArgumentException('A mensagem recebida não contém id ou texto.');
        }

        $messageTimestamp = $data['messageTimestamp'] ?? null;
        $messageDateTime = is_numeric($messageTimestamp)
            ? CarbonImmutable::createFromTimestampUTC((int) $messageTimestamp)
            : ($datas['date_time'] ?? null);

        $message = Whatsapp::query()->updateOrCreate(
            ['message_id' => $messageId],
            [
                'name' => $data['pushName'] ?? null,
                'message' => $text,
                'message_type' => $messageType,
                'from_me' => (bool) ($key['fromMe'] ?? false),
                'ai_message' => false,
                'audio_url' => $audioUrl,
                'phone_number' => is_string($remoteJid) ? str_replace('@s.whatsapp.net', '', $remoteJid) : null,
                'instance_id' => $data['instanceId'] ?? null,
                'api_key' => $datas['apikey'] ?? null,
                'message_datetime' => $messageDateTime,
                'instance' => $datas['instance'] ?? null,
                'remote_jid' => $remoteJid,
            ],
        );

        $message->sendToN8n();

        return $message;
    }

    public function saveMessage(array $datas): Whatsapp
    {
        $messageId = $datas['MessageId'] ?? null;
        $text = $datas['Message'] ?? null;

        if (! is_string($messageId) || $messageId === '' || ! is_string($text) || $text === '') {
            throw new InvalidArgumentException('A mensagem não contém id ou texto.');
        }

        $remoteJid = $datas['RemoteJid'] ?? null;
        $phoneNumber = $datas['phoneNumber'] ?? null;

        return Whatsapp::query()->updateOrCreate(
            ['message_id' => $messageId],
            [
                'name' => $datas['Name'] ?? null,
                'message' => $text,
                'message_type' => $datas['MessageType'] ?? 'conversation',
                'from_me' => filter_var($datas['FromMe'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'ai_message' => filter_var($datas['AiMessage'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'phone_number' => is_string($phoneNumber) ? $phoneNumber : null,
                'instance_id' => $datas['instanceId'] ?? null,
                'message_datetime' => now(),
                'instance' => $datas['Instance'] ?? null,
                'remote_jid' => is_string($remoteJid) ? $remoteJid : null,
            ],
        );
    }

    public function sendTextMessage(string $remoteJid, string $text): Whatsapp
    {
        $lastMessage = Whatsapp::query()
            ->where('remote_jid', $remoteJid)
            ->latest('created_at')
            ->first();

        if (! $lastMessage?->instance || ! $lastMessage->api_key) {
            throw new RuntimeException('A conversa não possui instância ou API key configurada.');
        }

        $response = Http::timeout(15)
            ->withHeader('apikey', $lastMessage->api_key)
            ->post(rtrim(config('services.evolution.url'), '/').'/message/sendText/'.$lastMessage->instance, [
                'number' => str_replace('@s.whatsapp.net', '', $remoteJid),
                'text' => $text,
            ])
            ->throw();

        $responseData = $response->json();
        $messageId = data_get($responseData, 'key.id')
            ?: data_get($responseData, 'message.key.id')
            ?: (string) Str::uuid();

        return Whatsapp::query()->updateOrCreate(
            ['message_id' => $messageId],
            [
                'name' => 'Você',
                'message' => $text,
                'message_type' => 'conversation',
                'from_me' => true,
                'ai_message' => false,
                'phone_number' => $lastMessage->phone_number,
                'instance_id' => $lastMessage->instance_id,
                'api_key' => $lastMessage->api_key,
                'message_datetime' => now(),
                'instance' => $lastMessage->instance,
                'remote_jid' => $remoteJid,
            ],
        );
    }

    private function processAudioMessage(array $data, array $message): array
    {
        $audio = $message['audioMessage'] ?? [];
        $base64 = $data['base64'] ?? $message['base64'] ?? $audio['base64'] ?? null;
        $mimeType = $audio['mimetype'] ?? $audio['mime_type'] ?? $data['mimetype'] ?? 'audio/ogg';

        if (! is_string($base64) || $base64 === '') {
            throw new InvalidArgumentException('A mensagem de áudio não contém dados base64.');
        }

        $apiKey = config('services.gemini.api_key');
        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('A chave da API do Gemini não foi configurada.');
        }

        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64, 2)[1];
        }

        $audioBinary = base64_decode($base64, true);
        if ($audioBinary === false) {
            throw new InvalidArgumentException('O áudio recebido contém base64 inválido.');
        }

        $extension = match (strtolower($mimeType)) {
            'audio/ogg', 'audio/opus' => 'ogg',
            'audio/mpeg', 'audio/mp3' => 'mp3',
            'audio/wav', 'audio/x-wav' => 'wav',
            'audio/mp4', 'audio/m4a' => 'm4a',
            default => 'bin',
        };
        $path = 'whatsapp/audio/'.($data['key']['id'] ?? Str::uuid()).'.'.$extension;
        Storage::disk('public')->put($path, $audioBinary);

        $response = Http::timeout(60)
            ->withHeaders([
                'x-goog-api-key' => (string) config('services.gemini.api_key'),
                'Content-Type' => 'application/json',
            ])
            ->post(config('services.gemini.url'), [
                'model' => 'gemini-3.5-transcribe',
                'input' => [[
                    'type' => 'audio',
                    'data' => base64_encode($audioBinary),
                    'mime_type' => $mimeType,
                ]],
                'generation_config' => [
                    'transcription_config' => [
                        'language_codes' => ['pt-BR'],
                    ],
                ],
            ])
            ->throw();

        $responseData = $response->json();
        $transcription = $this->extractGeminiText($responseData);

        if (! is_string($transcription) || trim($transcription) === '') {
            $status = data_get($responseData, 'status')
                ?? data_get($responseData, 'interaction.status')
                ?? 'desconhecido';

            throw new RuntimeException(
                'A API do Gemini não retornou uma transcrição válida (status: '.$status.').'
            );
        }

        return [trim($transcription), Storage::disk('public')->url($path)];
    }

    private function extractGeminiText(mixed $value): ?string
    {
        if (! is_array($value)) {
            return null;
        }

        foreach (['output_text', 'text'] as $key) {
            if (isset($value[$key]) && is_string($value[$key]) && trim($value[$key]) !== '') {
                return trim($value[$key]);
            }
        }

        foreach (['interaction', 'output', 'outputs', 'model_output', 'content', 'parts'] as $key) {
            if (! array_key_exists($key, $value)) {
                continue;
            }

            $text = $this->extractGeminiText($value[$key]);
            if ($text !== null) {
                return $text;
            }
        }

        foreach ($value as $item) {
            $text = $this->extractGeminiText($item);
            if ($text !== null) {
                return $text;
            }
        }

        return null;
    }
}
