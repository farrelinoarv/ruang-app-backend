# Eloquent Models Implementation Summary

## ✅ Phase 4: Define Eloquent Models - COMPLETED

All Eloquent models have been successfully implemented with complete relationships, fillable attributes, casts, and helper methods according to the ERD specification.

---

## 📋 Models Overview

### 1. **User Model** (`app/Models/User.php`)

**Traits:**

-   `HasFactory`, `Notifiable`, `HasApiTokens`

**Fillable Attributes:**

-   `name`, `email`, `password`, `role`, `is_verified_civitas`, `phone`, `email_verified_at`

**Casts:**

-   `email_verified_at` → datetime
-   `password` → hashed
-   `is_verified_civitas` → boolean

**Relationships:**

-   `wallet()` → HasOne → Wallet
-   `campaigns()` → HasMany → Campaign
-   `donations()` → HasMany → Donation
-   `notifications()` → HasMany → Notification
-   `campaignVerificationRequests()` → HasMany → CampaignVerificationRequest (reviewed_by)
-   `withdrawalRequests()` → HasMany → WithdrawalRequest (user_id)
-   `reviewedWithdrawalRequests()` → HasMany → WithdrawalRequest (reviewed_by)
-   `updates()` → HasMany → Update

---

### 2. **Category Model** (`app/Models/Category.php`)

**Fillable Attributes:**

-   `name`, `slug`

**Relationships:**

-   `campaigns()` → HasMany → Campaign

---

### 3. **Campaign Model** (`app/Models/Campaign.php`)

**Fillable Attributes:**

-   `user_id`, `category_id`, `title`, `slug`, `description`, `target_amount`, `collected_amount`, `deadline`, `status`, `cover_image`

**Casts:**

-   `target_amount` → decimal:2
-   `collected_amount` → decimal:2
-   `deadline` → date

**Relationships:**

-   `user()` → BelongsTo → User
-   `category()` → BelongsTo → Category
-   `donations()` → HasMany → Donation
-   `verificationRequest()` → HasOne → CampaignVerificationRequest
-   `withdrawalRequests()` → HasMany → WithdrawalRequest
-   `updates()` → HasMany → Update

**Accessor/Attributes:**

-   `progress_percentage` → Calculate campaign progress (0-100%)
-   `is_active` → Check if campaign is approved and not expired

---

### 4. **CampaignVerificationRequest Model** (`app/Models/CampaignVerificationRequest.php`)

**Fillable Attributes:**

-   `campaign_id`, `full_name`, `identity_type`, `identity_number`, `proof_file`, `organization_name`, `verification_status`, `reviewed_by`, `reviewed_at`, `notes`

**Casts:**

-   `reviewed_at` → datetime

**Relationships:**

-   `campaign()` → BelongsTo → Campaign
-   `reviewer()` → BelongsTo → User (reviewed_by)

---

### 5. **Donation Model** (`app/Models/Donation.php`)

**Fillable Attributes:**

-   `campaign_id`, `user_id`, `donor_name`, `amount`, `message`, `payment_method`, `midtrans_order_id`, `midtrans_transaction_id`, `payment_status`, `transaction_ref`

**Casts:**

-   `amount` → decimal:2

**Relationships:**

-   `campaign()` → BelongsTo → Campaign
-   `user()` → BelongsTo → User

**Accessor/Attributes:**

-   `is_success` → Check if payment status is 'success'

---

### 6. **Wallet Model** (`app/Models/Wallet.php`)

**Fillable Attributes:**

-   `user_id`, `balance`, `total_income`, `total_withdrawn`

**Casts:**

-   `balance` → decimal:2
-   `total_income` → decimal:2
-   `total_withdrawn` → decimal:2

**Relationships:**

-   `user()` → BelongsTo → User

**Accessor/Attributes:**

-   `available_balance` → Get available balance for withdrawal

---

### 7. **MasterAccount Model** (`app/Models/MasterAccount.php`)

**Fillable Attributes:**

-   `balance`

**Casts:**

-   `balance` → decimal:2

**Helper Methods:**

-   `getInstance()` → Get singleton instance (ID = 1)
-   `addFunds(float $amount)` → Add funds to master account
-   `deductFunds(float $amount)` → Deduct funds from master account

**Note:** No relationships (standalone model)

---

### 8. **WithdrawalRequest Model** (`app/Models/WithdrawalRequest.php`)

**Fillable Attributes:**

-   `campaign_id`, `user_id`, `requested_amount`, `destination_bank`, `destination_account`, `destination_name`, `reason`, `status`, `reviewed_by`, `reviewed_at`, `payout_id`, `payout_status`, `proof_file`

**Casts:**

-   `requested_amount` → decimal:2
-   `reviewed_at` → datetime

**Relationships:**

-   `campaign()` → BelongsTo → Campaign
-   `user()` → BelongsTo → User (user_id)
-   `reviewer()` → BelongsTo → User (reviewed_by)

**Accessor/Attributes:**

-   `is_approved` → Check if status is 'approved'
-   `is_pending` → Check if status is 'pending'

---

### 9. **Update Model** (`app/Models/Update.php`)

**Fillable Attributes:**

-   `campaign_id`, `user_id`, `title`, `body`, `media_path`

**Relationships:**

-   `campaign()` → BelongsTo → Campaign
-   `user()` → BelongsTo → User

---

### 10. **Notification Model** (`app/Models/Notification.php`)

**Fillable Attributes:**

-   `user_id`, `title`, `message`, `data`, `is_read`, `type`

**Casts:**

-   `data` → array
-   `is_read` → boolean

**Relationships:**

-   `user()` → BelongsTo → User

**Helper Methods:**

-   `markAsRead()` → Mark notification as read
-   `markAsUnread()` → Mark notification as unread

---

## 🔗 Relationship Summary (ERD Implementation)

### User Relationships (1-to-Many & 1-to-1)

✅ 1:1 with Wallets
✅ 1:N with Campaigns
✅ 1:N with Donations
✅ 1:N with Notifications
✅ 1:N with CampaignVerificationRequests (as reviewer)
✅ 1:N with WithdrawalRequests (as requester and reviewer)
✅ 1:N with Updates

### Category Relationships

✅ 1:N with Campaigns

### Campaign Relationships

✅ N:1 with Users
✅ N:1 with Categories
✅ 1:N with Donations
✅ 1:1 with CampaignVerificationRequests
✅ 1:N with WithdrawalRequests
✅ 1:N with Updates

### Other Model Relationships

✅ CampaignVerificationRequest → Campaign, User (reviewer)
✅ Donation → Campaign, User
✅ Wallet → User
✅ WithdrawalRequest → Campaign, User (requester), User (reviewer)
✅ Update → Campaign, User
✅ Notification → User
✅ MasterAccount → No relationships (singleton)

---

## 🎯 Key Features Implemented

### 1. **Proper Type Casting**

-   All decimal fields cast to `decimal:2` for precision
-   Date fields cast to `date`
-   Boolean fields cast to `boolean`
-   JSON fields cast to `array`

### 2. **Laravel Sanctum Integration**

-   `HasApiTokens` trait added to User model for API authentication

### 3. **Helper Methods & Accessors**

-   Campaign progress calculation
-   Campaign active status check
-   Donation success check
-   Withdrawal status checks
-   Notification read/unread methods
-   Master account fund management

### 4. **Mass Assignment Protection**

-   All models have properly defined `$fillable` arrays

### 5. **Factory Support**

-   `HasFactory` trait included where needed for seeding and testing

---

## ✅ Next Steps (Phase 5-9)

Now that all Eloquent models are complete, you can proceed with:

**Phase 5:** Business Logic Core

-   Create `WalletService` for handling money transactions
-   Implement Events: `DonationPaid`, `WithdrawalApproved`
-   Integrate Midtrans sandbox callback

**Phase 6:** API Development

-   Implement all API endpoints listed in the project brief
-   Authentication (register, login, logout)
-   Campaign CRUD
-   Donation flow
-   Wallet & withdrawal
-   Notifications

**Phase 7:** Filament Admin Panel Setup

-   Configure admin panel provider
-   Setup navigation and branding

**Phase 8:** Filament Resources & Features

-   Create admin resources for all models
-   Implement approval workflows
-   Dashboard with statistics

**Phase 9:** API Documentation

-   Document all endpoints
-   Create Postman collection
-   Document Midtrans callback flow

---

## 📝 Usage Examples

### Creating a Campaign with Relationships

```php
$campaign = Campaign::create([
    'user_id' => $user->id,
    'category_id' => $category->id,
    'title' => 'Help Build School Library',
    'slug' => 'help-build-school-library',
    'description' => 'We need your help...',
    'target_amount' => 50000000,
    'deadline' => now()->addMonths(2),
    'status' => 'pending',
    'cover_image' => 'campaigns/school-library.jpg',
]);

// Access relationships
$campaign->user; // Creator
$campaign->category; // Category
$campaign->donations; // All donations
$campaign->verificationRequest; // Verification details
```

### Accessing User's Campaigns and Wallet

```php
$user = User::find(1);

// Get user's campaigns
$campaigns = $user->campaigns;

// Get user's wallet
$wallet = $user->wallet;

// Get user's donations
$donations = $user->donations;

// Get withdrawal requests created by user
$withdrawals = $user->withdrawalRequests;
```

### Working with Campaign Progress

```php
$campaign = Campaign::find(1);

// Get progress percentage (0-100)
$progress = $campaign->progress_percentage;

// Check if campaign is active
if ($campaign->is_active) {
    // Campaign is approved and not expired
}
```

### Managing Notifications

```php
$notification = Notification::find(1);

// Mark as read
$notification->markAsRead();

// Mark as unread
$notification->markAsUnread();

// Get all unread notifications for a user
$unread = $user->notifications()->where('is_read', false)->get();
```

### Master Account Operations

```php
$masterAccount = MasterAccount::getInstance();

// Add funds (from donations)
$masterAccount->addFunds(100000);

// Deduct funds (for withdrawals)
if ($masterAccount->deductFunds(50000)) {
    // Withdrawal successful
} else {
    // Insufficient balance
}
```

---

## 🔍 Model Validation

All models have been checked for:

-   ✅ Correct namespace imports
-   ✅ Proper relationship definitions
-   ✅ Accurate fillable attributes
-   ✅ Appropriate type casting
-   ✅ No syntax errors
-   ✅ Consistent with database migrations

**Status:** All models pass validation with no errors! 🎉

---

Generated on: November 14, 2025
Project: Ruang – Platform Crowdfunding Sosial Civitas UNDIP
