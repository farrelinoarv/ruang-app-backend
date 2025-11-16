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
            Log::info('Processing Midtrans callback', [
                'order_id' => $payload['order_id'] ?? 'MISSING',
                'transaction_status' => $payload['transaction_status'] ?? 'MISSING',
                'transaction_id' => $payload['transaction_id'] ?? 'MISSING',
            ]);

            // Verify signature
            if (!self::verifySignature($payload)) {
                Log::error('Invalid Midtrans signature', [
                    'payload' => $payload,
                    'calculated' => hash(
                        'sha512',
                        ($payload['order_id'] ?? '') .
                        ($payload['status_code'] ?? '') .
                        ($payload['gross_amount'] ?? '') .
                        config('services.midtrans.server_key')
                    ),
                    'received' => $payload['signature_key'] ?? 'MISSING',
                ]);
                return null;
            }

            // Find donation by Midtrans order ID
            $donation = Donation::where('midtrans_order_id', $payload['order_id'])->first();

            if (!$donation) {
                Log::error('Donation not found', ['order_id' => $payload['order_id']]);
                return null;
            }

            Log::info('Donation found, current status: ' . $donation->payment_status);

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
                    Log::info('Payment captured successfully');
                }
            } elseif ($transactionStatus === 'settlement') {
                $donation->update([
                    'payment_status' => 'success',
                    'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                    'payment_method' => $payload['payment_type'] ?? null,
                ]);
                Log::info('Payment settled successfully');
            } elseif ($transactionStatus === 'pending') {
                $donation->update([
                    'payment_status' => 'pending',
                    'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                ]);
                Log::info('Payment still pending');
            } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                $donation->update([
                    'payment_status' => 'failed',
                    'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                ]);
                Log::info('Payment failed: ' . $transactionStatus);
            }

            // Fire event if payment is successful
            if ($donation->payment_status === 'success') {
                event(new DonationPaid($donation));
                Log::info('DonationPaid event fired for donation #' . $donation->id);
            }

            return $donation;
        } catch (\Exception $e) {
            Log::error('Failed to handle Midtrans callback: ' . $e->getMessage(), [
                'payload' => $payload,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
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
        $amount = (int) $donation->amount;

        // Truncate campaign title to 50 characters (Midtrans limit)
        $campaignTitle = $donation->campaign->title;
        if (strlen($campaignTitle) > 37) {
            $campaignTitle = substr($campaignTitle, 0, 37) . '...';
        }
        $itemName = 'Donasi: ' . $campaignTitle;

        return [
            'transaction_details' => [
                'order_id' => $donation->midtrans_order_id,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => substr($donation->donor_name ?? 'Anonymous', 0, 50),
                'email' => $donation->user->email ?? 'anonymous@ruang.id',
                'phone' => $donation->user->phone ?? '081234567890',
            ],
            'item_details' => [
                [
                    'id' => 'DONATION-' . $donation->campaign_id,
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => $itemName,
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

