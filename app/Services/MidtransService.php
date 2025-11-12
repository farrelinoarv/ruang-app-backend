<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MidtransService
{
    public static function createTransaction($params)
    {
        $serverKey = config(key: 'services.midtrans.server_key');
        $snapUrl = config(key: 'services.midtrans.snap_url');

        $response = Http::withBasicAuth(username: $serverKey, password: '')
            ->post(url: $snapUrl, data: $params);

        return $response->json();
    }

    public static function handleCallback($request)
    {
        $serverKey = config('services.midtrans.server_key');
        $signatureKey = hash(
            'sha512',
            $request->order_id .
            $request->status_code .
            $request->gross_amount .
            $serverKey
        );

        if ($signatureKey !== $request->signature_key) {
            abort(403, 'Invalid signature');
        }

        return $request;
    }

    public static function getSnapUrl($token)
    {
        $base = config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v4/redirection/'
            : 'https://app.sandbox.midtrans.com/snap/v4/redirection/';

        return $base . $token;
    }


}
