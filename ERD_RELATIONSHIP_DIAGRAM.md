# 🗺️ Eloquent Relationships Diagram

## Complete ERD Implementation Map

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              RUANG PLATFORM ERD                                   │
│                         Eloquent Relationships Diagram                            │
└─────────────────────────────────────────────────────────────────────────────────┘

                                    ┌──────────────┐
                            ┌───────┤     USER     ├──────┐
                            │       │   (Civitas)  │      │
                            │       └──────┬───────┘      │
                            │              │              │
                            │              │ 1:1          │
                            │              ▼              │
                            │       ┌──────────────┐      │
                            │       │    WALLET    │      │
                            │       └──────────────┘      │
                            │                             │
                            │ 1:N                         │ 1:N
                            ▼                             ▼
                    ┌──────────────┐              ┌──────────────┐
                    │   CAMPAIGN   │              │   DONATION   │
                    └──────┬───────┘              └──────────────┘
                           │                              │
                           │ N:1                          │ N:1
                           ▼                              │
                    ┌──────────────┐                      │
              ┌─────┤   CATEGORY   │                      │
              │     └──────────────┘                      │
              │                                           │
              │ 1:N                                       │
              │                                           │
              │                                           │
              │     ┌──────────────────────────┐          │
              │     │  CAMPAIGN_VERIFICATION   │          │
              └────▶│       _REQUEST           │◀─────────┘
                    └─────────┬────────────────┘
                              │ N:1 (reviewed_by)
                              ▼
                    ┌──────────────┐
              ┌─────┤     USER     │
              │     │   (Admin)    │
              │     └──────┬───────┘
              │            │
              │            │ 1:N (reviewed_by)
              │            ▼
              │     ┌──────────────────┐
              │     │ WITHDRAWAL       │
              └────▶│    REQUEST       │◀──────┐
                    └──────────────────┘       │
                           │                   │
                           │ N:1               │ N:1
                           ▼                   │
                    ┌──────────────┐           │
                    │   CAMPAIGN   ├───────────┘
                    └──────┬───────┘
                           │
                           │ 1:N
                           ▼
                    ┌──────────────┐
                    │    UPDATE    │
                    │  (Progress)  │
                    └──────┬───────┘
                           │ N:1
                           ▼
                    ┌──────────────┐
                    │     USER     │
                    └──────┬───────┘
                           │
                           │ 1:N
                           ▼
                    ┌──────────────┐
                    │ NOTIFICATION │
                    └──────────────┘

              ┌────────────────────┐
              │   MASTER_ACCOUNT   │  ◀── Singleton (No relationships)
              │   (System Balance) │
              └────────────────────┘
```

---

## 📋 Relationship Legend

| Symbol | Meaning |
|--------|---------|
| `1:1` | One-to-One relationship |
| `1:N` | One-to-Many relationship |
| `N:1` | Many-to-One relationship (BelongsTo) |
| `───▶` | Relationship direction |
| `◀───` | Reverse relationship |

---

## 🔗 Detailed Relationship Matrix

### User (Central Hub)

```
USER
 ├── wallet (1:1)
 ├── campaigns (1:N)
 ├── donations (1:N)
 ├── notifications (1:N)
 ├── campaignVerificationRequests (1:N as reviewer)
 ├── withdrawalRequests (1:N as requester)
 ├── reviewedWithdrawalRequests (1:N as reviewer)
 └── updates (1:N)
```

### Campaign (Core Entity)

```
CAMPAIGN
 ├── user (N:1 - BelongsTo)
 ├── category (N:1 - BelongsTo)
 ├── donations (1:N)
 ├── verificationRequest (1:1)
 ├── withdrawalRequests (1:N)
 └── updates (1:N)
```

### Category

```
CATEGORY
 └── campaigns (1:N)
```

### Donation

```
DONATION
 ├── campaign (N:1 - BelongsTo)
 └── user (N:1 - BelongsTo, nullable)
```

### Wallet

```
WALLET
 └── user (N:1 - BelongsTo)
```

### CampaignVerificationRequest

```
CAMPAIGN_VERIFICATION_REQUEST
 ├── campaign (N:1 - BelongsTo)
 └── reviewer (N:1 - BelongsTo User, nullable)
```

### WithdrawalRequest

```
WITHDRAWAL_REQUEST
 ├── campaign (N:1 - BelongsTo)
 ├── user (N:1 - BelongsTo)
 └── reviewer (N:1 - BelongsTo User, nullable)
```

### Update

```
UPDATE
 ├── campaign (N:1 - BelongsTo)
 └── user (N:1 - BelongsTo)
```

### Notification

```
NOTIFICATION
 └── user (N:1 - BelongsTo)
```

### MasterAccount

```
MASTER_ACCOUNT
 └── (No relationships - Singleton)
```

---

## 🎯 Key Relationship Patterns

### Pattern 1: Creator → Created Entity
```
User ──1:N──> Campaign
User ──1:N──> Update
User ──1:N──> Donation (nullable)
```

### Pattern 2: Admin Review System
```
User (Admin) ──1:N──> CampaignVerificationRequest (reviewed_by)
User (Admin) ──1:N──> WithdrawalRequest (reviewed_by)
```

### Pattern 3: Campaign Ownership
```
Campaign ──1:N──> Donation
Campaign ──1:N──> Update
Campaign ──1:N──> WithdrawalRequest
Campaign ──1:1──> CampaignVerificationRequest
```

### Pattern 4: Categorization
```
Category ──1:N──> Campaign
```

### Pattern 5: Financial Tracking
```
User ──1:1──> Wallet
MasterAccount (standalone)
```

### Pattern 6: User Notifications
```
User ──1:N──> Notification
```

---

## 📊 Relationship Statistics

| Relationship Type | Count |
|-------------------|-------|
| One-to-One (1:1) | 2 |
| One-to-Many (1:N) | 15 |
| Many-to-One (N:1) | 11 |
| **Total** | **28** |

---

## 🔄 Data Flow Examples

### Donation Flow

```
1. User creates Donation
   ├── Links to Campaign
   └── Links to User (or anonymous)

2. Payment successful
   ├── Update Donation.payment_status
   ├── Update Campaign.collected_amount
   ├── Update MasterAccount.balance
   └── Create Notification for Campaign owner
```

### Campaign Verification Flow

```
1. User creates Campaign
   ├── Status: 'pending'
   └── Creates CampaignVerificationRequest

2. Admin reviews
   ├── CampaignVerificationRequest.reviewed_by = Admin
   ├── CampaignVerificationRequest.verification_status = 'approved'
   └── Campaign.status = 'approved'

3. Auto-create Wallet for User
   └── User.is_verified_civitas = true
```

### Withdrawal Flow

```
1. User creates WithdrawalRequest
   ├── Links to Campaign
   ├── Links to User
   └── Status: 'pending'

2. Admin reviews
   ├── WithdrawalRequest.reviewed_by = Admin
   ├── WithdrawalRequest.status = 'approved'
   └── Update Wallet.balance

3. Deduct from MasterAccount
   └── Create Notification for User
```

### Progress Update Flow

```
1. Campaign owner creates Update
   ├── Links to Campaign
   └── Links to User

2. Visible to all campaign viewers
   └── Create Notification for all donors
```

---

## 🗂️ Database Foreign Keys

| Table | Foreign Key | References | Constraint |
|-------|-------------|------------|------------|
| wallets | user_id | users.id | CASCADE |
| campaigns | user_id | users.id | CASCADE |
| campaigns | category_id | categories.id | NULL |
| donations | campaign_id | campaigns.id | CASCADE |
| donations | user_id | users.id | NULL |
| campaign_verification_requests | campaign_id | campaigns.id | CASCADE |
| campaign_verification_requests | reviewed_by | users.id | NULL |
| withdrawal_requests | campaign_id | campaigns.id | CASCADE |
| withdrawal_requests | user_id | users.id | CASCADE |
| withdrawal_requests | reviewed_by | users.id | SET NULL |
| updates | campaign_id | campaigns.id | CASCADE |
| updates | user_id | users.id | CASCADE |
| notifications | user_id | users.id | CASCADE |

---

## 💡 Query Optimization Tips

### Eager Loading Patterns

```php
// Campaign detail with all relations
Campaign::with([
    'user:id,name,email',
    'category:id,name',
    'donations' => fn($q) => $q->where('payment_status', 'success'),
    'verificationRequest',
    'updates.user:id,name',
    'withdrawalRequests.reviewer:id,name'
])->find($id);

// User dashboard with counts
User::withCount([
    'campaigns',
    'donations' => fn($q) => $q->where('payment_status', 'success'),
    'notifications' => fn($q) => $q->where('is_read', false)
])->find($id);
```

### Common Joins

```php
// Campaigns with successful donation totals
Campaign::leftJoin('donations', function($join) {
    $join->on('campaigns.id', '=', 'donations.campaign_id')
         ->where('donations.payment_status', '=', 'success');
})
->selectRaw('campaigns.*, SUM(donations.amount) as total_donations')
->groupBy('campaigns.id')
->get();
```

---

## 🎨 Visualization Key Points

1. **User** is the central entity connecting to most other models
2. **Campaign** is the main business entity with the most relationships
3. **Admin** users have special reviewer relationships
4. **MasterAccount** stands alone as a financial singleton
5. **Notifications** provide real-time updates across the system

---

**Generated:** November 14, 2025  
**Project:** Ruang Platform  
**Version:** Phase 4 Complete
