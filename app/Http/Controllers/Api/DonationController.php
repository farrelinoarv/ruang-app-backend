<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateDonationRequest;
use App\Models\Campaign;
use App\Models\Donation;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DonationController extends Controller
{
    /**
     * Create new donation and get Midtrans Snap token.
     */
    public function store(CreateDonationRequest $request): JsonResponse
    {
        try {
            $campaign = Campaign::findOrFail($request->campaign_id);

            // Validate campaign is approved and not expired
            if ($campaign->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Kampanye ini belum disetujui atau tidak tersedia.',
                ], 400);
            }

            if ($campaign->deadline < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kampanye ini sudah berakhir.',
                ], 400);
            }

            // Generate unique Midtrans order ID
            $orderId = 'RUANG-' . time() . '-' . Str::random(6);

            // Create donation with pending status
            $donation = Donation::create([
                'campaign_id' => $campaign->id,
                'user_id' => $request->user()->id,
                'donor_name' => $request->input('donor_name') ?? $request->user()->name,
                'is_anonymous' => $request->input('is_anonymous', false),
                'amount' => $request->amount,
                'message' => $request->input('message'),
                'payment_status' => 'pending',
                'midtrans_order_id' => $orderId,
            ]);

            // Generate Midtrans Snap token
            $params = MidtransService::generateDonationParams($donation);
            $snapResponse = MidtransService::createTransaction($params);

            if (!isset($snapResponse['token'])) {
                // Delete donation if Midtrans fails
                $donation->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat transaksi pembayaran.',
                    'error' => $snapResponse['error_messages'] ?? 'Unknown error',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Donasi berhasil dibuat. Silakan lanjutkan pembayaran.',
                'data' => [
                    'donation_id' => $donation->id,
                    'order_id' => $donation->midtrans_order_id,
                    'amount' => (float) $donation->amount,
                    'snap_token' => $snapResponse['token'],
                    'snap_url' => MidtransService::getSnapUrl($snapResponse['token']),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat donasi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's donation history.
     */
    public function myIndex(Request $request): JsonResponse
    {
        $donations = Donation::with(['campaign:id,title,slug,cover_image'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $donations->map(function ($donation) {
                return [
                    'id' => $donation->id,
                    'campaign' => [
                        'id' => $donation->campaign->id,
                        'title' => $donation->campaign->title,
                        'slug' => $donation->campaign->slug,
                        'cover_image' => $donation->campaign->cover_image,
                    ],
                    'amount' => (float) $donation->amount,
                    'message' => $donation->message,
                    'is_anonymous' => $donation->is_anonymous,
                    'payment_status' => $donation->payment_status,
                    'payment_method' => $donation->payment_method,
                    'created_at' => $donation->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ], 200);
    }

    /**
     * Get donation detail.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $donation = Donation::with(['campaign:id,title,slug', 'user:id,name'])
            ->findOrFail($id);

        // Only owner or if donation is successful can view
        if ($donation->user_id !== $request->user()?->id && $donation->payment_status !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melihat donasi ini.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $donation->id,
                'campaign' => [
                    'id' => $donation->campaign->id,
                    'title' => $donation->campaign->title,
                    'slug' => $donation->campaign->slug,
                ],
                'donor_name' => $donation->display_name,
                'amount' => (float) $donation->amount,
                'message' => $donation->message,
                'is_anonymous' => $donation->is_anonymous,
                'payment_status' => $donation->payment_status,
                'payment_method' => $donation->payment_method,
                'created_at' => $donation->created_at->format('Y-m-d H:i:s'),
            ],
        ], 200);
    }

    /**
     * Get public donation list for a campaign (supporters).
     */
    public function getCampaignDonations(string $campaignId): JsonResponse
    {
        $campaign = Campaign::findOrFail($campaignId);

        $donations = Donation::where('campaign_id', $campaign->id)
            ->where('payment_status', 'success')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'campaign' => [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'total_donors' => $donations->count(),
                    'collected_amount' => (float) $campaign->collected_amount,
                ],
                'donations' => $donations->map(function ($donation) {
                    return [
                        'id' => $donation->id,
                        'donor_name' => $donation->display_name,
                        'amount' => (float) $donation->amount,
                        'message' => $donation->message,
                        'created_at' => $donation->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
            ],
        ], 200);
    }
}
