<?php

/**
 * Manual Midtrans Callback Simulator
 *
 * Use this script to simulate Midtrans payment callback when testing locally.
 * This bypasses the need for a public URL (ngrok/expose).
 *
 * Usage:
 * php scripts/simulate_callback.php <order_id> <transaction_id> <status>
 *
 * Example:
 * php scripts/simulate_callback.php RUANG-1731650400-abc123 midtrans-123456 settlement
 *
 * Available statuses:
 * - settlement (payment success)
 * - pending (waiting payment)
 * - deny (payment rejected)
 * - expire (payment expired)
 * - cancel (payment cancelled)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\MidtransService;
use App\Models\Donation;

// Get arguments
$orderId = $argv[1] ?? null;
$transactionId = $argv[2] ?? null;
$status = $argv[3] ?? 'settlement';

if (!$orderId) {
    echo "❌ Error: Order ID is required\n";
    echo "Usage: php scripts/simulate_callback.php <order_id> <transaction_id> <status>\n";
    echo "Example: php scripts/simulate_callback.php RUANG-1731650400-abc123 midtrans-123456 settlement\n";
    exit(1);
}

// Find donation
$donation = Donation::where('midtrans_order_id', $orderId)->first();

if (!$donation) {
    echo "❌ Donation not found with order ID: {$orderId}\n";
    echo "Available donations:\n";
    Donation::latest()->take(5)->get()->each(function ($d) {
        echo "  - Order ID: {$d->midtrans_order_id}, Amount: {$d->amount}, Status: {$d->payment_status}\n";
    });
    exit(1);
}

// Generate valid signature
$serverKey = config('services.midtrans.server_key');
$statusCode = '200';
$grossAmount = number_format((float) $donation->amount, 2, '.', '');

$signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

// Build callback payload
$payload = [
    'transaction_time' => now()->toIso8601String(),
    'transaction_status' => $status,
    'transaction_id' => $transactionId ?? 'simulated-' . uniqid(),
    'status_message' => 'midtrans payment notification',
    'status_code' => $statusCode,
    'signature_key' => $signatureKey,
    'payment_type' => 'gopay',
    'order_id' => $orderId,
    'merchant_id' => 'G123456789',
    'gross_amount' => $grossAmount,
    'fraud_status' => 'accept',
    'currency' => 'IDR',
];

echo "🔄 Simulating Midtrans callback...\n";
echo "   Order ID: {$orderId}\n";
echo "   Transaction ID: {$payload['transaction_id']}\n";
echo "   Status: {$status}\n";
echo "   Amount: Rp " . number_format((float) $donation->amount, 0, ',', '.') . "\n\n";

// Process callback
$result = MidtransService::handleCallback($payload);

if ($result) {
    echo "✅ Callback processed successfully!\n\n";
    echo "Updated donation:\n";
    echo "   Payment Status: {$result->payment_status}\n";
    echo "   Payment Method: {$result->payment_method}\n";
    echo "   Transaction ID: {$result->midtrans_transaction_id}\n";

    if ($result->payment_status === 'success') {
        echo "\n🎉 Payment marked as successful!\n";

        // Check wallet update
        if ($result->user) {
            $wallet = $result->user->wallet;
            echo "   User Wallet - Total Donated: Rp " . number_format($wallet->total_donated ?? 0, 0, ',', '.') . "\n";
        }

        // Check campaign collected amount
        $campaign = $result->campaign;
        echo "   Campaign Collected: Rp " . number_format($campaign->collected_amount, 0, ',', '.') . "\n";
    }
} else {
    echo "❌ Failed to process callback\n";
    exit(1);
}
