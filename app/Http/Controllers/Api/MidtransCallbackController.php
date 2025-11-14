<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransCallbackController extends Controller
{
    /**
     * Handle Midtrans payment notification callback.
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            Log::info('Midtrans callback received', $request->all());

            // Handle the callback
            $donation = MidtransService::handleCallback($request->all());

            if (!$donation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process callback',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Callback processed successfully',
                'data' => [
                    'donation_id' => $donation->id,
                    'payment_status' => $donation->payment_status,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Midtrans callback error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }
}
