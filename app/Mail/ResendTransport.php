<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class ResendTransport extends AbstractTransport
{
    protected ?string $apiKey;

    public function __construct(?string $apiKey)
    {
        parent::__construct();
        $this->apiKey = $apiKey;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        
        $from = $email->getFrom()[0] ?? null;
        $fromFormatted = $from 
            ? ($from->getName() ? "{$from->getName()} <{$from->getAddress()}>" : $from->getAddress()) 
            : config('mail.from.address', 'noreply@majelisku.web.id');
        
        $to = array_map(fn($addr) => $addr->getAddress(), $email->getTo());
        
        $payload = [
            'from' => $fromFormatted,
            'to' => $to,
            'subject' => $email->getSubject(),
            'html' => $email->getHtmlBody() ?: $email->getTextBody(),
            'text' => $email->getTextBody(),
        ];

        if (empty($this->apiKey)) {
            Log::warning('ResendTransport: RESEND_API_KEY belum disetel. Email dicatat di log.', $payload);
            return;
        }

        $options = [];
        if (file_exists('C:/Users/Rizz/AppData/Local/Python/pythoncore-3.15-64/Lib/site-packages/certifi/cacert.pem')) {
            $options['verify'] = 'C:/Users/Rizz/AppData/Local/Python/pythoncore-3.15-64/Lib/site-packages/certifi/cacert.pem';
        }

        $response = Http::withToken($this->apiKey)
            ->withOptions($options)
            ->timeout(15)
            ->post('https://api.resend.com/emails', $payload);

        if (!$response->successful()) {
            Log::error('Resend API send error: ' . $response->body());
            throw new \RuntimeException('Resend API failed: ' . $response->body());
        }
    }

    public function __toString(): string
    {
        return 'resend';
    }
}
