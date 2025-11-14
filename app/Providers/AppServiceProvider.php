<?php

namespace App\Providers;

use App\Events\CampaignApproved;
use App\Events\CampaignRejected;
use App\Events\DonationPaid;
use App\Events\UpdatePosted;
use App\Events\WithdrawalApproved;
use App\Events\WithdrawalRejected;
use App\Listeners\NotifyDonorsOfUpdate;
use App\Listeners\SendCampaignNotification;
use App\Listeners\SendCampaignRejectedNotification;
use App\Listeners\SendDonationNotification;
use App\Listeners\SendWithdrawalNotification;
use App\Listeners\UpdateCampaignCollectedAmount;
use App\Listeners\UpdateMasterAccountBalance;
use App\Listeners\UpdateWalletBalance;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register event listeners
        Event::listen(DonationPaid::class, [
            UpdateCampaignCollectedAmount::class,
            UpdateMasterAccountBalance::class,
            SendDonationNotification::class,
        ]);

        Event::listen(WithdrawalApproved::class, [
            UpdateWalletBalance::class,
            SendWithdrawalNotification::class,
        ]);

        Event::listen(WithdrawalRejected::class, [
            SendWithdrawalNotification::class,
        ]);

        Event::listen(CampaignApproved::class, [
            SendCampaignNotification::class,
        ]);

        Event::listen(CampaignRejected::class, [
            SendCampaignRejectedNotification::class,
        ]);

        Event::listen(UpdatePosted::class, [
            NotifyDonorsOfUpdate::class,
        ]);
    }
}
