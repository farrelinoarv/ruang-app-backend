<?php

namespace App\Services;

use App\Events\DonationPaid;
use App\Models\Donation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    /**
     * Create Midtrans transaction and get Snap token.
     */
    public static function createTransaction($params)
    {
        $serverKey = config(key: 'services.midtrans.server_key');
        $snapUrl = config(key: 'services.midtrans.snap_url');

        $response = Http::withBasicAuth(username: $serverKey, password: '')
            ->post(url: $snapUrl, data: $params);

        return $response->json();
    }

    /**
     * Verify Midtrans signature from callback.
     */
    public static function verifySignature(array $payload): bool
    {
        $serverKey = config('services.midtrans.server_key');

        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        $calculatedSignature = hash(
            'sha512',
            $orderId . $statusCode . $grossAmount . $serverKey
        );

        return $calculatedSignature === $signatureKey;
    }

    /**
     * Handle Midtrans callback and update donation status.
     */
    public static function handleCallback(array $payload): ?Donation
    {
        try {
            // Verify signature
            if (!self::verifySignature($payload)) {
                Log::error('Invalid Midtrans signature', ['payload' => $payload]);
                return null;
            }

            // Find donation by Midtrans order ID
            $donation = Donation::where('midtrans_order_id', $payload['order_id'])->first();

            if (!$donation) {
                Log::error('Donation not found', ['order_id' => $payload['order_id']]);
                return null;
            }

            // Update donation based on transaction status
            $transactionStatus = $payload['transaction_status'] ?? '';
            $fraudStatus = $payload['fraud_status'] ?? 'accept';

            if ($transactionStatus === 'capture') {
                if ($fraudStatus === 'accept') {
                    $donation->update([
                        'payment_status' => 'success',
                        'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                        'payment_method' => $payload['payment_type'] ?? null,
                    ]);
                }
            } elseif ($transactionStatus === 'settlement') {
                $donation->update([
                    'payment_status' => 'success',
                    'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                    'payment_method' => $payload['payment_type'] ?? null,
                ]);
            } elseif ($transactionStatus === 'pending') {
                $donation->update([
                    'payment_status' => 'pending',
                    'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                ]);
            } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                $donation->update([
                    'payment_status' => 'failed',
                    'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                ]);
            }

            // Fire event if payment is successful
            if ($donation->payment_status === 'success') {
                event(new DonationPaid($donation));
            }

            return $donation;
        } catch (\Exception $e) {
            Log::error('Failed to handle Midtrans callback: ' . $e->getMessage(), [
                'payload' => $payload,
                'exception' => $e,
            ]);
            return null;
        }
    }

    /**
     * Get Snap payment URL.
     */
    public static function getSnapUrl($token)
    {
        $base = config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v4/redirection/'
            : 'https://app.sandbox.midtrans.com/snap/v4/redirection/';

        return $base . $token;
    }

    /**
     * Generate Midtrans transaction parameters for donation.
     */
    public static function generateDonationParams(Donation $donation): array
    {
        return [
            'transaction_details' => [
                'order_id' => $donation->midtrans_order_id,
                'gross_amount' => (int) $donation->amount,
            ],
            'customer_details' => [
                'first_name' => $donation->donor_name,
                'email' => $donation->user->email ?? 'anonymous@ruang.id',
                'phone' => $donation->user->phone ?? '081234567890',
            ],
            'item_details' => [
                [
                    'id' => $donation->campaign_id,
                    'price' => (int) $donation->amount,
                    'quantity' => 1,
                    'name' => 'Donasi untuk ' . $donation->campaign->title,
                ],
            ],
            'enabled_payments' => [
                'gopay',
                'shopeepay',
                'other_qris',
                'bca_va',
                'bni_va',
                'bri_va',
                'permata_va',
                'echannel',
                'other_va',
            ],
        ];
    }
}

