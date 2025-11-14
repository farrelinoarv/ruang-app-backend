# Eloquent Relationship Quick Reference

## 🔗 How to Use Relationships

### User Model

```php
use App\Models\User;

$user = User::find(1);

// 1:1 Relationship
$user->wallet; // Get user's wallet

// 1:N Relationships
$user->campaigns; // Get all campaigns created by user
$user->donations; // Get all donations made by user
$user->notifications; // Get all notifications
$user->updates; // Get all updates posted by user
$user->withdrawalRequests; // Get withdrawal requests created by user
$user->reviewedWithdrawalRequests; // Get withdrawal requests reviewed by user (as admin)
$user->campaignVerificationRequests; // Get verification requests reviewed by user (as admin)
```

### Campaign Model

```php
use App\Models\Campaign;

$campaign = Campaign::find(1);

// N:1 Relationships (BelongsTo)
$campaign->user; // Get campaign creator
$campaign->category; // Get campaign category

// 1:N Relationships
$campaign->donations; // Get all donations to this campaign
$campaign->withdrawalRequests; // Get all withdrawal requests
$campaign->updates; // Get all updates/progress posts

// 1:1 Relationship
$campaign->verificationRequest; // Get verification request details

// Accessors
$campaign->progress_percentage; // Get progress (0-100)
$campaign->is_active; // Check if campaign is active
```

### Category Model

```php
use App\Models\Category;

$category = Category::find(1);

// 1:N Relationship
$category->campaigns; // Get all campaigns in this category
```

### Donation Model

```php
use App\Models\Donation;

$donation = Donation::find(1);

// N:1 Relationships (BelongsTo)
$donation->campaign; // Get campaign that was donated to
$donation->user; // Get donor user (nullable for anonymous)

// Accessor
$donation->is_success; // Check if payment is successful
```

### Wallet Model

```php
use App\Models\Wallet;

$wallet = Wallet::find(1);

// N:1 Relationship (BelongsTo)
$wallet->user; // Get wallet owner

// Accessor
$wallet->available_balance; // Get available balance
```

### WithdrawalRequest Model

```php
use App\Models\WithdrawalRequest;

$withdrawal = WithdrawalRequest::find(1);

// N:1 Relationships (BelongsTo)
$withdrawal->campaign; // Get related campaign
$withdrawal->user; // Get user who requested withdrawal
$withdrawal->reviewer; // Get admin who reviewed (nullable)

// Accessors
$withdrawal->is_approved; // Check if approved
$withdrawal->is_pending; // Check if pending
```

### CampaignVerificationRequest Model

```php
use App\Models\CampaignVerificationRequest;

$verification = CampaignVerificationRequest::find(1);

// N:1 Relationships (BelongsTo)
$verification->campaign; // Get related campaign
$verification->reviewer; // Get admin who reviewed (nullable)
```

### Update Model

```php
use App\Models\Update;

$update = Update::find(1);

// N:1 Relationships (BelongsTo)
$update->campaign; // Get related campaign
$update->user; // Get user who created the update
```

### Notification Model

```php
use App\Models\Notification;

$notification = Notification::find(1);

// N:1 Relationship (BelongsTo)
$notification->user; // Get notification recipient

// Helper Methods
$notification->markAsRead();
$notification->markAsUnread();
```

### MasterAccount Model

```php
use App\Models\MasterAccount;

// Get singleton instance
$masterAccount = MasterAccount::getInstance();

// Helper Methods
$masterAccount->addFunds(1000000); // Add funds
$masterAccount->deductFunds(500000); // Deduct funds (returns bool)

// Check balance
$balance = $masterAccount->balance;
```

---

## 📊 Common Query Patterns

### Get Active Campaigns with Creator and Category

```php
$campaigns = Campaign::with(['user', 'category'])
    ->where('status', 'approved')
    ->where('deadline', '>=', now())
    ->get();
```

### Get Campaign with All Relationships

```php
$campaign = Campaign::with([
    'user',
    'category',
    'donations' => function ($query) {
        $query->where('payment_status', 'success');
    },
    'verificationRequest',
    'updates',
    'withdrawalRequests'
])->find($id);
```

### Get User's Successful Donations

```php
$donations = $user->donations()
    ->where('payment_status', 'success')
    ->with('campaign')
    ->latest()
    ->get();
```

### Get Campaign's Total Successful Donations

```php
$total = $campaign->donations()
    ->where('payment_status', 'success')
    ->sum('amount');
```

### Get Unread Notifications for User

```php
$unread = $user->notifications()
    ->where('is_read', false)
    ->latest()
    ->get();
```

### Get Pending Campaigns for Admin Review

```php
$pending = Campaign::with(['user', 'verificationRequest'])
    ->where('status', 'pending')
    ->latest()
    ->get();
```

### Get User's Campaign with Total Donations

```php
$campaigns = $user->campaigns()
    ->withCount(['donations' => function ($query) {
        $query->where('payment_status', 'success');
    }])
    ->withSum(['donations' => function ($query) {
        $query->where('payment_status', 'success');
    }], 'amount')
    ->get();
```

### Get Top Donors for a Campaign

```php
$topDonors = $campaign->donations()
    ->where('payment_status', 'success')
    ->with('user')
    ->orderBy('amount', 'desc')
    ->limit(10)
    ->get();
```

### Get Pending Withdrawal Requests for Admin

```php
$pendingWithdrawals = WithdrawalRequest::with(['user', 'campaign'])
    ->where('status', 'pending')
    ->latest()
    ->get();
```

### Get Category with Campaign Count

```php
$categories = Category::withCount('campaigns')
    ->having('campaigns_count', '>', 0)
    ->get();
```

---

## 🎯 Eager Loading Best Practices

### Avoid N+1 Problem

❌ **Bad (N+1 queries):**

```php
$campaigns = Campaign::all();
foreach ($campaigns as $campaign) {
    echo $campaign->user->name; // N+1 problem!
}
```

✅ **Good (Eager loading):**

```php
$campaigns = Campaign::with('user')->get();
foreach ($campaigns as $campaign) {
    echo $campaign->user->name; // Single query
}
```

### Multiple Relationships

```php
$campaign = Campaign::with([
    'user:id,name,email',
    'category:id,name',
    'donations' => function ($query) {
        $query->where('payment_status', 'success')
              ->latest()
              ->limit(10);
    }
])->find($id);
```

### Nested Relationships

```php
$users = User::with([
    'campaigns.category',
    'campaigns.donations' => function ($query) {
        $query->where('payment_status', 'success');
    }
])->get();
```

---

## 🔄 Creating Related Models

### Create Campaign with Verification Request

```php
$campaign = Campaign::create([
    'user_id' => auth()->id(),
    'category_id' => 1,
    'title' => 'My Campaign',
    'slug' => 'my-campaign',
    // ... other fields
]);

$campaign->verificationRequest()->create([
    'full_name' => 'John Doe',
    'identity_type' => 'mahasiswa',
    'identity_number' => '123456789',
    'proof_file' => 'path/to/file.pdf',
    'verification_status' => 'pending',
]);
```

### Create Donation for Campaign

```php
$donation = $campaign->donations()->create([
    'user_id' => auth()->id(), // or null for anonymous
    'donor_name' => 'Anonymous',
    'amount' => 100000,
    'message' => 'Good luck!',
    'payment_status' => 'pending',
    'midtrans_order_id' => 'ORDER-123',
]);
```

### Create Notification for User

```php
$user->notifications()->create([
    'title' => 'Campaign Approved',
    'message' => 'Your campaign has been approved!',
    'type' => 'campaign_approved',
    'data' => ['campaign_id' => $campaign->id],
]);
```

### Create Update for Campaign

```php
$campaign->updates()->create([
    'user_id' => auth()->id(),
    'title' => 'Progress Update',
    'body' => 'We have reached 50% of our goal!',
    'media_path' => 'updates/photo.jpg',
]);
```

---

## 📈 Aggregation Queries

### Count Relationships

```php
// Count campaigns per user
$user->campaigns()->count();

// Count successful donations
$campaign->donations()->where('payment_status', 'success')->count();
```

### Sum Amounts

```php
// Total donation amount for campaign
$total = $campaign->donations()
    ->where('payment_status', 'success')
    ->sum('amount');

// User's total income
$income = $user->wallet->total_income;
```

### Average Donation

```php
$average = $campaign->donations()
    ->where('payment_status', 'success')
    ->avg('amount');
```

---

## 🔍 Filtering and Scopes

You can add local scopes to models for common queries:

### Example: Campaign Model

```php
// In Campaign.php
public function scopeActive($query)
{
    return $query->where('status', 'approved')
                 ->where('deadline', '>=', now());
}

public function scopePending($query)
{
    return $query->where('status', 'pending');
}

// Usage:
$activeCampaigns = Campaign::active()->get();
$pendingCampaigns = Campaign::pending()->get();
```

---

## 💡 Tips & Tricks

1. **Always eager load** when you know you'll need relationships
2. **Use `with()` method** to prevent N+1 queries
3. **Select specific columns** to reduce memory usage: `User::select('id', 'name')->get()`
4. **Use `exists()` or `doesntExist()`** instead of `count() > 0` for better performance
5. **Cache expensive queries** like category lists or top campaigns
6. **Use database transactions** when creating multiple related records

---

Generated on: November 14, 2025
