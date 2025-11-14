# ✅ Phase 5 Complete: Business Logic Core

## 🎉 Implementation Status: **COMPLETED**

All core business logic services, events, and listeners have been successfully implemented for the Ruang crowdfunding platform.

---

## 📋 What Was Implemented

### 1️⃣ **Core Services (4 Services)**

#### **WalletService** (`app/Services/WalletService.php`)
Handles all wallet and money management operations.

**Methods:**
- `addFunds(User $user, float $amount, string $source)` - Add funds to user wallet
- `deductFunds(User $user, float $amount, string $reason)` - Deduct funds from user wallet
- `transferToMasterAccount(Donation $donation)` - Transfer donation to master account
- `transferFromMasterAccount(WithdrawalRequest $withdrawal)` - Transfer withdrawal to user
- `getBalance(User $user)` - Get user's wallet balance
- `getTransactionHistory(User $user, int $limit)` - Get user's transaction history
- `ensureWalletExists(User $user)` - Create wallet if doesn't exist
- `getMasterAccountBalance()` - Get master account balance

**Features:**
- Database transactions for data consistency
- Error logging
- Automatic wallet creation
- Balance validation

---

#### **NotificationService** (`app/Services/NotificationService.php`)
Manages all system notifications to users.

**Methods:**
- `sendToUser(User $user, string $title, string $message, array $data, string $type)` - Send notification to specific user
- `sendToCampaignOwner(Campaign $campaign, ...)` - Notify campaign owner
- `sendToDonors(Campaign $campaign, ...)` - Notify all campaign donors
- `sendBulk(Collection $users, ...)` - Send bulk notifications
- `markAsRead(Notification $notification)` - Mark notification as read
- `markAllAsRead(User $user)` - Mark all user notifications as read
- `getUnreadCount(User $user)` - Get unread notification count
- `deleteOldNotifications(int $daysOld)` - Clean up old read notifications

**Features:**
- Targeted notifications (user, campaign owner, donors)
- Bulk notification support
- Unique donor detection
- Data payload support for deep linking

---

#### **CampaignService** (`app/Services/CampaignService.php`)
Handles campaign lifecycle and business logic.

**Methods:**
- `approveCampaign(Campaign $campaign, User $admin, string $notes)` - Approve campaign
- `rejectCampaign(Campaign $campaign, User $admin, string $reason)` - Reject campaign
- `closeExpiredCampaigns()` - Auto-close expired campaigns
- `updateCollectedAmount(Campaign $campaign, float $amount)` - Update collected donations
- `isTargetReached(Campaign $campaign)` - Check if target reached
- `getProgressPercentage(Campaign $campaign)` - Calculate progress %
- `getActiveCampaigns()` - Get all active campaigns
- `getPendingCampaigns()` - Get campaigns awaiting approval

**Features:**
- Auto-verify civitas on first approved campaign
- Auto-create wallet for verified civitas
- Update verification requests
- Fire events for approval/rejection
- Database transactions

---

#### **VerificationService** (`app/Services/VerificationService.php`)
Manages civitas verification process.

**Methods:**
- `approveVerification(CampaignVerificationRequest $request, User $admin, string $notes)` - Approve verification
- `rejectVerification(CampaignVerificationRequest $request, User $admin, string $reason)` - Reject verification
- `verifyCivitas(User $user)` - Mark user as verified civitas
- `isVerifiedCivitas(User $user)` - Check verification status
- `getPendingVerifications()` - Get pending verification requests

**Features:**
- Admin review tracking
- Timestamped reviews
- Notes/reason support

---

### 2️⃣ **Events (6 Events)**

All events are located in `app/Events/`

| Event | Properties | Description |
|-------|-----------|-------------|
| `DonationPaid` | `$donation` | Fired when donation payment is successful |
| `WithdrawalApproved` | `$withdrawal`, `$admin` | Fired when admin approves withdrawal |
| `WithdrawalRejected` | `$withdrawal`, `$admin`, `$reason` | Fired when admin rejects withdrawal |
| `CampaignApproved` | `$campaign`, `$admin` | Fired when admin approves campaign |
| `CampaignRejected` | `$campaign`, `$admin`, `$reason` | Fired when admin rejects campaign |
| `UpdatePosted` | `$update` | Fired when campaign owner posts update |

---

### 3️⃣ **Event Listeners (8 Listeners)**

All listeners are located in `app/Listeners/`

#### **For DonationPaid Event:**
1. **UpdateCampaignCollectedAmount** - Updates campaign's collected amount
2. **UpdateMasterAccountBalance** - Transfers donation to master account
3. **SendDonationNotification** - Notifies campaign owner & donor

#### **For WithdrawalApproved Event:**
4. **UpdateWalletBalance** - Transfers funds from master account to user wallet

#### **For WithdrawalApproved & WithdrawalRejected Events:**
5. **SendWithdrawalNotification** - Notifies user about withdrawal status

#### **For CampaignApproved Event:**
6. **SendCampaignNotification** - Notifies campaign owner about approval

#### **For CampaignRejected Event:**
7. **SendCampaignRejectedNotification** - Notifies campaign owner about rejection

#### **For UpdatePosted Event:**
8. **NotifyDonorsOfUpdate** - Notifies all donors about campaign update

**Features:**
- All listeners implement `ShouldQueue` (asynchronous processing)
- Automatic retry on failure
- Comprehensive logging

---

### 4️⃣ **Event Registration**

Events and listeners are registered in `app/Providers/AppServiceProvider.php`:

```php
Event::listen(DonationPaid::class, [
    UpdateCampaignCollectedAmount::class,
    UpdateMasterAccountBalance::class,
    SendDonationNotification::class,
]);

Event::listen(WithdrawalApproved::class, [
    UpdateWalletBalance::class,
    SendWithdrawalNotification::class,
]);

// ... and so on
```

---

### 5️⃣ **Enhanced MidtransService**

**New Methods Added:**
- `verifySignature(array $payload)` - Verify Midtrans callback signature
- `handleCallback(array $payload)` - Process payment callback and update donation
- `generateDonationParams(Donation $donation)` - Generate transaction parameters

**Callback Handling Flow:**
1. Verify signature (SHA512 hash)
2. Find donation by order_id
3. Update donation status based on transaction status:
   - `capture` + `accept` fraud → `success`
   - `settlement` → `success`
   - `pending` → `pending`
   - `deny`/`expire`/`cancel` → `failed`
4. Fire `DonationPaid` event if successful
5. Log everything for debugging

**Supported Payment Methods:**
- GoPay
- ShopeePay
- QRIS
- Bank VA (BCA, BNI, BRI, Permata, Mandiri)

---

### 6️⃣ **MidtransCallbackController**

Created `app/Http/Controllers/Api/MidtransCallbackController.php`

**Endpoint:** `POST /api/midtrans/callback`

**Features:**
- No authentication required (Midtrans webhook)
- Comprehensive error handling
- Logging for debugging
- JSON response

**Response Format:**
```json
{
  "success": true,
  "message": "Callback processed successfully",
  "data": {
    "donation_id": 1,
    "payment_status": "success"
  }
}
```

---

## 🔄 Complete Flow Examples

### **Donation Flow**

```
1. User creates donation → Status: pending
   ↓
2. Get Midtrans Snap token → User pays
   ↓
3. Midtrans sends callback → POST /api/midtrans/callback
   ↓
4. MidtransService verifies signature
   ↓
5. Update donation status → success
   ↓
6. Fire DonationPaid event
   ↓
7. Listeners execute:
   - UpdateCampaignCollectedAmount (campaign.collected_amount++)
   - UpdateMasterAccountBalance (master account balance++)
   - SendDonationNotification (notify owner & donor)
```

---

### **Campaign Approval Flow**

```
1. Admin approves campaign → CampaignService::approveCampaign()
   ↓
2. Update campaign status → approved
   ↓
3. Update verification request → approved
   ↓
4. Check if first campaign → Verify civitas
   ↓
5. Create wallet for verified civitas
   ↓
6. Fire CampaignApproved event
   ↓
7. SendCampaignNotification listener → Notify campaign owner
```

---

### **Withdrawal Flow**

```
1. User requests withdrawal → Status: pending
   ↓
2. Admin reviews in Filament panel
   ↓
3. Admin approves → Fire WithdrawalApproved event
   ↓
4. Listeners execute:
   - UpdateWalletBalance (transfer from master → user wallet)
   - SendWithdrawalNotification (notify user)
```

---

### **Update Posted Flow**

```
1. Campaign owner posts update
   ↓
2. Fire UpdatePosted event
   ↓
3. NotifyDonorsOfUpdate listener → Get all unique donors → Send notifications
```

---

## 📊 Statistics

| Category | Count |
|----------|-------|
| **Services** | 4 |
| **Events** | 6 |
| **Listeners** | 8 |
| **Service Methods** | 30+ |
| **Event Mappings** | 6 |
| **API Routes** | 1 (callback) |

---

## 🎯 Key Features Implemented

### ✅ **Transactional Safety**
- All money operations use database transactions
- Rollback on failure
- Balance validation before deduction

### ✅ **Event-Driven Architecture**
- Decoupled business logic
- Asynchronous processing (queued)
- Easy to extend with new listeners

### ✅ **Comprehensive Logging**
- All critical operations logged
- Error tracking
- Midtrans callback debugging

### ✅ **Signature Verification**
- SHA512 hash verification
- Protection against tampering
- Invalid signature rejection

### ✅ **Notification System**
- Real-time user notifications
- Targeted notifications (owner, donors, specific user)
- Rich data payload for deep linking
- Bulk notification support

### ✅ **Auto-Verification**
- First approved campaign → auto-verify civitas
- Auto-create wallet
- One-time verification

---

## 🧪 Testing Checklist

### **WalletService**
- [ ] Test addFunds with valid amount
- [ ] Test deductFunds with sufficient balance
- [ ] Test deductFunds with insufficient balance (should fail)
- [ ] Test transferToMasterAccount
- [ ] Test transferFromMasterAccount
- [ ] Test ensureWalletExists (creates if not exists)

### **CampaignService**
- [ ] Test approveCampaign (status changes, wallet created, event fired)
- [ ] Test rejectCampaign (status changes, event fired)
- [ ] Test civitas auto-verification on first campaign
- [ ] Test closeExpiredCampaigns

### **NotificationService**
- [ ] Test sendToUser
- [ ] Test sendToCampaignOwner
- [ ] Test sendToDonors (unique donors only)
- [ ] Test markAsRead

### **MidtransService**
- [ ] Test signature verification (valid)
- [ ] Test signature verification (invalid)
- [ ] Test callback handling (success)
- [ ] Test callback handling (failed)
- [ ] Test generateDonationParams

### **Events & Listeners**
- [ ] Test DonationPaid event fires all listeners
- [ ] Test campaign collected amount updated
- [ ] Test master account balance updated
- [ ] Test notifications sent
- [ ] Test WithdrawalApproved event
- [ ] Test wallet balance updated

---

## 🚀 What's Next? (Phase 6-9)

### **Phase 6: API Development** 🌐
Now that business logic is complete, you can build APIs:

**Authentication APIs:**
- POST `/api/register`
- POST `/api/login`
- POST `/api/logout`
- GET `/api/profile`

**Campaign APIs:**
- GET `/api/campaigns` (list active campaigns)
- POST `/api/campaigns` (create campaign)
- GET `/api/campaigns/{id}` (detail)
- PUT `/api/campaigns/{id}` (update)

**Donation APIs:**
- POST `/api/donate` (create donation + get Snap token)
- GET `/api/donations/mine` (user's donations)

**Wallet APIs:**
- GET `/api/wallet` (balance & history)
- POST `/api/withdraw` (request withdrawal)

**Others:**
- GET `/api/categories`
- GET `/api/notifications`
- PUT `/api/notifications/{id}/read`

---

## 💡 Usage Examples

### **Using WalletService**

```php
use App\Services\WalletService;

$walletService = app(WalletService::class);

// Add funds
$walletService->addFunds($user, 100000, 'donation');

// Deduct funds
if ($walletService->deductFunds($user, 50000, 'withdrawal')) {
    // Success
}

// Get balance
$balance = $walletService->getBalance($user);
```

### **Using CampaignService**

```php
use App\Services\CampaignService;

$campaignService = app(CampaignService::class);

// Approve campaign
$campaignService->approveCampaign($campaign, $admin, 'Looks good!');

// Reject campaign
$campaignService->rejectCampaign($campaign, $admin, 'Missing documents');

// Close expired campaigns (can be scheduled)
$count = $campaignService->closeExpiredCampaigns();
```

### **Using NotificationService**

```php
use App\Services\NotificationService;

$notificationService = app(NotificationService::class);

// Send to user
$notificationService->sendToUser(
    $user,
    'Welcome!',
    'Thank you for joining Ruang',
    ['campaign_id' => 1],
    'welcome'
);

// Send to all donors
$notificationService->sendToDonors(
    $campaign,
    'New Update!',
    'Check out the latest progress'
);
```

### **Firing Events Manually**

```php
use App\Events\DonationPaid;

// Fire event (all registered listeners will execute)
event(new DonationPaid($donation));
```

---

## 📝 Configuration Notes

### **Queue Configuration**

Since listeners implement `ShouldQueue`, make sure to run the queue worker:

```bash
php artisan queue:work
```

For development, you can use `sync` driver in `.env`:
```env
QUEUE_CONNECTION=sync
```

For production, use `database` or `redis`:
```env
QUEUE_CONNECTION=database
```

### **Midtrans Callback URL**

Configure in Midtrans dashboard:
```
https://yourdomain.com/api/midtrans/callback
```

For local testing with Midtrans:
- Use ngrok: `ngrok http 8000`
- Set callback URL: `https://your-ngrok-url.ngrok.io/api/midtrans/callback`

---

## ✅ Phase 5 Completion Checklist

- [x] Create WalletService
- [x] Create NotificationService
- [x] Create CampaignService
- [x] Create VerificationService
- [x] Create all 6 Events
- [x] Create all 8 Listeners
- [x] Register events in AppServiceProvider
- [x] Enhance MidtransService
- [x] Create MidtransCallbackController
- [x] Add callback route

---

**Status:** ✅ **PHASE 5 COMPLETED SUCCESSFULLY**

**Date:** November 14, 2025  
**Project:** Ruang – Platform Crowdfunding Sosial Civitas UNDIP  
**Backend:** Laravel 11 + Filament 3.2

**Next Action:** Start Phase 6 - API Development (Authentication, Campaigns, Donations, Wallet)
