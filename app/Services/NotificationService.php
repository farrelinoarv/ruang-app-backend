<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send notification to a specific user.
     */
    public function sendToUser(User $user, string $title, string $message, array $data = [], string $type = null): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'type' => $type,
            'is_read' => false,
        ]);
    }

    /**
     * Send notification to campaign owner.
     */
    public function sendToCampaignOwner(Campaign $campaign, string $title, string $message, array $data = [], string $type = null): Notification
    {
        return $this->sendToUser($campaign->user, $title, $message, $data, $type);
    }

    /**
     * Send notification to all donors of a campaign.
     */
    public function sendToDonors(Campaign $campaign, string $title, string $message, array $data = [], string $type = null): Collection
    {
        $donors = $campaign->donations()
            ->where('payment_status', 'success')
            ->whereNotNull('user_id')
            ->with('user')
            ->get()
            ->pluck('user')
            ->unique('id');

        $notifications = collect();

        foreach ($donors as $donor) {
            if ($donor) {
                $notifications->push(
                    $this->sendToUser($donor, $title, $message, $data, $type)
                );
            }
        }

        return $notifications;
    }

    /**
     * Send bulk notifications to multiple users.
     */
    public function sendBulk(Collection $users, string $title, string $message, array $data = [], string $type = null): Collection
    {
        $notifications = collect();

        foreach ($users as $user) {
            $notifications->push(
                $this->sendToUser($user, $title, $message, $data, $type)
            );
        }

        return $notifications;
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification): bool
    {
        return $notification->update(['is_read' => true]);
    }

    /**
     * Mark all user notifications as read.
     */
    public function markAllAsRead(User $user): int
    {
        return $user->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Get unread count for user.
     */
    public function getUnreadCount(User $user): int
    {
        return $user->notifications()
            ->where('is_read', false)
            ->count();
    }

    /**
     * Delete old read notifications.
     */
    public function deleteOldNotifications(int $daysOld = 30): int
    {
        return Notification::where('is_read', true)
            ->where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }
}
