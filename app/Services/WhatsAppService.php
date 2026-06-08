<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function send(?string $phone, string $message): bool
    {
        $target = $this->formatPhoneNumber($phone);

        if (! $target) {
            Log::warning('WhatsApp tidak dikirim karena nomor tujuan kosong.');
            return false;
        }

        $token = config('services.whatsapp.token');
        $url = config('services.whatsapp.url');

        if (! $token || ! $url) {
            Log::warning('WhatsApp Gateway belum dikonfigurasi. Cek WHATSAPP_GATEWAY_TOKEN dan WHATSAPP_GATEWAY_URL di .env.');
            return false;
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders(['Authorization' => $token])
                ->asForm()
                ->post($url, [
                    'target' => $target,
                    'message' => $message,
                ]);

            if (! $response->successful()) {
                Log::error('Gagal mengirim WhatsApp via gateway.', [
                    'target' => $target,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Error saat mengirim WhatsApp via gateway.', [
                'target' => $target,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function formatPhoneNumber(?string $phone): ?string
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);

        if ($phone === '') {
            return null;
        }

        if (str_starts_with($phone, '0')) {
            return config('services.whatsapp.country_code', '62') . substr($phone, 1);
        }

        return $phone;
    }
}
