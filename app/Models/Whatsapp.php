<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Whatsapp extends Model
{
    protected $table = 'whatsapp';

    protected $fillable = [
        'name',
        'message',
        'message_type',
        'message_id',
        'from_me',
        'ai_message',
        'audio_url',
        'phone_number',
        'instance_id',
        'api_key',
        'message_datetime',
        'instance',
        'remote_jid',
    ];

    protected function casts(): array
    {
        return [
            'from_me' => 'boolean',
            'ai_message' => 'boolean',
            'message_datetime' => 'immutable_datetime',
        ];
    }

    public function sendToN8n(): Response
    {
        $url = config('services.n8n.whatsapp_webhook_url');

        if (empty($url)) {
            throw new RuntimeException(
                'A URL do webhook do n8n não foi configurada.'
            );
        }

        $response = Http::timeout(15)->post($url, [
            'Name' => $this->name,
            'Message' => $this->message,
            'MessageType' => $this->message_type,
            'MessageId' => $this->message_id,
            'FromMe' => $this->from_me,
            'PhoneNumber' => $this->phone_number,
            'instanceId' => $this->instance_id,
            'DateTime' => $this->message_datetime,
            'Instance' => $this->instance,
            'RemoteJid' => $this->remote_jid,
            'ApiKey' => $this->api_key,
        ]);

        return $response->throw();
    }
}
