<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateCampaignRequest;
use App\Http\Requests\Api\UpdateCampaignRequest;
use App\Http\Requests\Api\CreateUpdateRequest;
use App\Models\Campaign;
use App\Models\Update;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    /**
     * Get all approved campaigns (public).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Campaign::with(['user:id,name', 'category:id,name,slug'])
            ->where('status', 'approved')
            ->where('deadline', '>=', now());

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if (in_array($sortBy, ['created_at', 'deadline', 'collected_amount'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $campaigns = $query->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $campaigns->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'slug' => $campaign->slug,
                    'description' => Str::limit($campaign->description, 150),
                    'target_amount' => (float) $campaign->target_amount,
                    'collected_amount' => (float) $campaign->collected_amount,
                    'progress_percentage' => $campaign->progress_percentage,
                    'deadline' => $campaign->deadline->format('Y-m-d'),
                    'cover_image' => $campaign->cover_image,
                    'status' => $campaign->status,
                    'user' => [
                        'id' => $campaign->user->id,
                        'name' => $campaign->user->name,
                    ],
                    'category' => [
                        'id' => $campaign->category->id,
                        'name' => $campaign->category->name,
                        'slug' => $campaign->category->slug,
                    ],
                    'created_at' => $campaign->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
            ],
        ], 200);
    }

    /**
     * Get campaign detail (public).
     */
    public function show(string $id): JsonResponse
    {
        $campaign = Campaign::with(['user:id,name,is_verified_civitas', 'category:id,name,slug'])
            ->findOrFail($id);

        // Only show approved campaigns to public (unless owner)
        if ($campaign->status !== 'approved' && (!auth()->check() || auth()->id() !== $campaign->user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'slug' => $campaign->slug,
                'description' => $campaign->description,
                'target_amount' => (float) $campaign->target_amount,
                'collected_amount' => (float) $campaign->collected_amount,
                'progress_percentage' => $campaign->progress_percentage,
                'deadline' => $campaign->deadline->format('Y-m-d'),
                'cover_image' => $campaign->cover_image,
                'status' => $campaign->status,
                'is_active' => $campaign->is_active,
                'user' => [
                    'id' => $campaign->user->id,
                    'name' => $campaign->user->name,
                    'is_verified_civitas' => $campaign->user->is_verified_civitas,
                ],
                'category' => [
                    'id' => $campaign->category->id,
                    'name' => $campaign->category->name,
                    'slug' => $campaign->category->slug,
                ],
                'created_at' => $campaign->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $campaign->updated_at->format('Y-m-d H:i:s'),
            ],
        ], 200);
    }

    /**
     * Create new campaign.
     */
    public function store(CreateCampaignRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Generate unique slug
            $slug = Str::slug($request->title);
            $originalSlug = $slug;
            $counter = 1;

            while (Campaign::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Handle image upload
            $coverImage = 'default.jpg';
            if ($request->hasFile('cover_image')) {
                $coverImage = $request->file('cover_image')->store('campaigns', 'public');
            }

            // Create campaign
            $campaign = Campaign::create([
                'user_id' => $user->id,
                'category_id' => $request->category_id,
                'title' => $request->title,
                'slug' => $slug,
                'description' => $request->description,
                'target_amount' => $request->target_amount,
                'collected_amount' => 0,
                'deadline' => $request->deadline,
                'status' => 'pending',
                'cover_image' => $coverImage,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign berhasil dibuat. Menunggu persetujuan admin.',
                'data' => [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'slug' => $campaign->slug,
                    'status' => $campaign->status,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat campaign.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's own campaigns.
     */
    public function myIndex(Request $request): JsonResponse
    {
        $campaigns = Campaign::with(['category:id,name,slug'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $campaigns->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'slug' => $campaign->slug,
                    'target_amount' => (float) $campaign->target_amount,
                    'collected_amount' => (float) $campaign->collected_amount,
                    'progress_percentage' => $campaign->progress_percentage,
                    'deadline' => $campaign->deadline->format('Y-m-d'),
                    'status' => $campaign->status,
                    'cover_image' => $campaign->cover_image,
                    'pending_changes' => $campaign->pending_changes,
                    'category' => [
                        'id' => $campaign->category->id,
                        'name' => $campaign->category->name,
                    ],
                    'created_at' => $campaign->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ], 200);
    }

    /**
     * Update campaign.
     */
    public function update(UpdateCampaignRequest $request, string $id): JsonResponse
    {
        try {
            $campaign = Campaign::findOrFail($id);

            // Check ownership
            if ($campaign->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk mengubah campaign ini.',
                ], 403);
            }

            // Check edit permissions based on status
            if ($campaign->status === 'pending') {
                // Handle image upload for pending campaigns
                $updateData = [];

                if ($request->filled('title')) {
                    $updateData['title'] = $request->title;
                }

                if ($request->filled('description')) {
                    $updateData['description'] = $request->description;
                }

                if ($request->filled('category_id')) {
                    $updateData['category_id'] = $request->category_id;
                }

                if ($request->filled('target_amount')) {
                    $updateData['target_amount'] = $request->target_amount;
                }

                if ($request->filled('deadline')) {
                    $updateData['deadline'] = $request->deadline;
                }

                if ($request->hasFile('cover_image')) {
                    // Delete old image if not default
                    if ($campaign->cover_image !== 'default.jpg') {
                        \Storage::disk('public')->delete($campaign->cover_image);
                    }
                    $updateData['cover_image'] = $request->file('cover_image')->store('campaigns', 'public');
                }

                // Direct edit allowed for pending campaigns
                if (!empty($updateData)) {
                    $campaign->update($updateData);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Campaign berhasil diperbarui.',
                    'data' => [
                        'id' => $campaign->id,
                        'title' => $campaign->title,
                        'description' => $campaign->description,
                        'target_amount' => (float) $campaign->target_amount,
                        'deadline' => $campaign->deadline->format('Y-m-d'),
                        'status' => $campaign->status,
                    ],
                ], 200);
            } elseif ($campaign->status === 'approved') {
                // For approved campaigns, only target_amount and deadline can be edited
                // Changes go to pending_changes, status becomes edit_pending
                $pendingChanges = [];

                if ($request->filled('target_amount') && $request->target_amount != $campaign->target_amount) {
                    $pendingChanges['target_amount'] = $request->target_amount;
                }

                if ($request->filled('deadline') && $request->deadline != $campaign->deadline->format('Y-m-d')) {
                    $pendingChanges['deadline'] = $request->deadline;
                }

                if (empty($pendingChanges)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tidak ada perubahan yang diajukan.',
                    ], 400);
                }

                $campaign->update([
                    'pending_changes' => $pendingChanges,
                    'status' => 'edit_pending',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Perubahan campaign telah diajukan. Menunggu persetujuan admin.',
                    'data' => [
                        'id' => $campaign->id,
                        'status' => $campaign->status,
                        'pending_changes' => $pendingChanges,
                    ],
                ], 200);
            } elseif ($campaign->status === 'edit_pending') {
                // Update pending changes
                $pendingChanges = $campaign->pending_changes ?? [];

                if ($request->filled('target_amount')) {
                    $pendingChanges['target_amount'] = $request->target_amount;
                }

                if ($request->filled('deadline')) {
                    $pendingChanges['deadline'] = $request->deadline;
                }

                $campaign->update([
                    'pending_changes' => $pendingChanges,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Perubahan campaign berhasil diperbarui.',
                    'data' => [
                        'id' => $campaign->id,
                        'status' => $campaign->status,
                        'pending_changes' => $pendingChanges,
                    ],
                ], 200);
            } else {
                // Cannot edit rejected or closed campaigns
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign dengan status ' . $campaign->status . ' tidak dapat diubah.',
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui campaign.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete campaign (only pending).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $campaign = Campaign::findOrFail($id);

            // Check ownership
            if ($campaign->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk menghapus campaign ini.',
                ], 403);
            }

            // Only pending campaigns can be deleted
            if ($campaign->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya campaign dengan status pending yang dapat dihapus.',
                ], 403);
            }

            $campaign->delete();

            return response()->json([
                'success' => true,
                'message' => 'Campaign berhasil dihapus.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus campaign.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get campaign updates (public).
     */
    public function getUpdates(string $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        $updates = Update::with(['user:id,name'])
            ->where('campaign_id', $campaign->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $updates->map(function ($update) {
                return [
                    'id' => $update->id,
                    'title' => $update->title,
                    'content' => $update->content,
                    'media_path' => $update->media_path,
                    'user' => [
                        'id' => $update->user->id,
                        'name' => $update->user->name,
                    ],
                    'created_at' => $update->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ], 200);
    }

    /**
     * Post campaign update (owner only).
     */
    public function postUpdate(CreateUpdateRequest $request, string $id): JsonResponse
    {
        try {
            $campaign = Campaign::findOrFail($id);

            // Check ownership
            if ($campaign->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk memposting update pada campaign ini.',
                ], 403);
            }

            // Only approved campaigns can have updates
            if ($campaign->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya campaign yang sudah disetujui yang dapat memposting update.',
                ], 403);
            }

            // Handle media upload
            $mediaPath = null;
            if ($request->hasFile('media')) {
                $mediaPath = $request->file('media')->store('updates', 'public');
            }

            $update = Update::create([
                'campaign_id' => $campaign->id,
                'user_id' => $request->user()->id,
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'media_path' => $mediaPath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Update campaign berhasil diposting.',
                'data' => [
                    'id' => $update->id,
                    'title' => $update->title,
                    'content' => $update->content,
                    'created_at' => $update->created_at->format('Y-m-d H:i:s'),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memposting update.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
