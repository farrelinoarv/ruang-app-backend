# Section 4: Donation Flow - API Testing Guide

## Overview
This guide covers testing the donation flow with Midtrans payment integration. The flow includes creating donations, processing payments via Midtrans Snap, handling callbacks, and managing donation status.

---

## API Endpoints

### 1. Create Donation (Protected)
**Endpoint:** `POST /api/donations`  
**Auth:** Required (Bearer token)  
**Purpose:** Create a new donation and get Midtrans Snap payment token

**Request Body:**
```json
{
  "campaign_id": 1,
  "amount": 50000,
  "donor_name": "John Doe",
  "is_anonymous": false,
  "message": "Semangat untuk kampanye ini!"
}
```

**Field Validation:**
- `campaign_id` (required): Must exist in campaigns table
- `amount` (required): Minimum Rp 10,000
- `donor_name` (optional): Max 255 characters (defaults to user's name if not provided)
- `is_anonymous` (optional): Boolean (default: false)
- `message` (optional): Max 500 characters

**Success Response (201):**
```json
{
  "success": true,
  "message": "Donasi berhasil dibuat. Silakan lanjutkan pembayaran.",
  "data": {
    "donation_id": 1,
    "order_id": "RUANG-1234567890-AbCdEf",
    "amount": 50000.00,
    "snap_token": "e8c0b823-5f3c-4b1e-b8d7-e0c9b8a7d6e5",
    "snap_url": "https://app.sandbox.midtrans.com/snap/v3/redirection/e8c0b823-5f3c-4b1e-b8d7-e0c9b8a7d6e5"
  }
}
```

**Error Responses:**

Campaign not approved (400):
```json
{
  "success": false,
  "message": "Kampanye ini belum disetujui atau tidak tersedia."
}
```

Campaign expired (400):
```json
{
  "success": false,
  "message": "Kampanye ini sudah berakhir."
}
```

Validation error (422):
```json
{
  "message": "The amount field must be at least 10000.",
  "errors": {
    "amount": ["Jumlah donasi minimal Rp 10.000."]
  }
}
```

Midtrans error (500):
```json
{
  "success": false,
  "message": "Gagal membuat transaksi pembayaran.",
  "error": "Midtrans API error details"
}
```

---

### 2. Get My Donations (Protected)
**Endpoint:** `GET /api/donations/mine`  
**Auth:** Required (Bearer token)  
**Purpose:** Get authenticated user's donation history

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "campaign": {
        "id": 1,
        "title": "Bantu Pendidikan Anak Yatim",
        "slug": "bantu-pendidikan-anak-yatim",
        "cover_image": "https://example.com/storage/campaigns/cover.jpg"
      },
      "amount": 50000.00,
      "message": "Semangat!",
      "is_anonymous": false,
      "payment_status": "success",
      "payment_method": "gopay",
      "created_at": "2025-11-15 14:30:00"
    }
  ]
}
```

**Payment Status Values:**
- `pending`: Payment not completed yet
- `success`: Payment successful
- `failed`: Payment failed
- `expired`: Payment expired (not completed within time limit)

---

### 3. Get Donation Detail (Optional Auth)
**Endpoint:** `GET /api/donations/{id}`  
**Auth:** Optional (Bearer token)  
**Purpose:** Get single donation detail

**Access Rules:**
- Owner can view regardless of status
- Non-owner can only view if `payment_status` is `success`
- Anonymous requests can only view successful donations

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "campaign": {
      "id": 1,
      "title": "Bantu Pendidikan Anak Yatim",
      "slug": "bantu-pendidikan-anak-yatim"
    },
    "donor_name": "John Doe",
    "amount": 50000.00,
    "message": "Semangat!",
    "is_anonymous": false,
    "payment_status": "success",
    "payment_method": "gopay",
    "created_at": "2025-11-15 14:30:00"
  }
}
```

**Anonymous Donor Response:**
If `is_anonymous` is `true`, `donor_name` will be `"Seseorang"`

**Forbidden Response (403):**
```json
{
  "success": false,
  "message": "Anda tidak memiliki akses untuk melihat donasi ini."
}
```

---

### 4. Get Campaign Donations (Public)
**Endpoint:** `GET /api/campaigns/{id}/donations`  
**Auth:** None required  
**Purpose:** Get list of supporters for a campaign (only successful donations)

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "campaign": {
      "id": 1,
      "title": "Bantu Pendidikan Anak Yatim",
      "total_donors": 25,
      "collected_amount": 5000000.00
    },
    "donations": [
      {
        "id": 1,
        "donor_name": "John Doe",
        "amount": 50000.00,
        "message": "Semangat!",
        "created_at": "2025-11-15 14:30:00"
      },
      {
        "id": 2,
        "donor_name": "Seseorang",
        "amount": 100000.00,
        "message": "Semoga bermanfaat",
        "created_at": "2025-11-15 12:15:00"
      }
    ]
  }
}
```

**Note:** Only donations with `payment_status = 'success'` are shown. Anonymous donors appear as "Seseorang".

---

## Midtrans Integration Testing

### Sandbox Environment Setup

**Credentials:**
- Server Key: From `config/services.php` → `midtrans.server_key`
- Client Key: From `config/services.php` → `midtrans.client_key`
- Environment: `sandbox` (set in `config/services.php`)

**Midtrans Snap URL:**
```
https://app.sandbox.midtrans.com/snap/v3/redirection/{snap_token}
```

---

### Testing Payment Flow

#### Step 1: Create Donation
```bash
POST http://localhost:8000/api/donations
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "campaign_id": 1,
  "amount": 50000,
  "message": "Test donation"
}
```

**Expected Result:**
- Donation created with `payment_status: 'pending'`
- Receive `snap_token` and `snap_url`
- No wallet update yet
- No campaign `collected_amount` update yet

---

#### Step 2: Open Snap Payment Page

**Option A: Use `snap_url` from response**
```
https://app.sandbox.midtrans.com/snap/v3/redirection/{snap_token}
```

**Option B: Integrate Snap.js in frontend** (for production)
```html
<script src="https://app.sandbox.midtrans.com/snap/snap.js" 
        data-client-key="YOUR_CLIENT_KEY"></script>
<script>
  snap.pay('SNAP_TOKEN_HERE');
</script>
```

---

#### Step 3: Complete Payment (Sandbox)

**Midtrans Sandbox Test Cards:**

| Payment Method | Test Number | Result |
|----------------|-------------|--------|
| Success | Any number | Success |
| BCA VA | Click "Pay" | Success |
| Gopay | Click "Pay" | Success |
| QRIS | Scan QR | Success |

**Note:** In sandbox, all payments succeed automatically when you click "Pay".

---

#### Step 4: Verify Callback Processing

**Midtrans sends callback to:**
```
POST http://localhost:8000/api/midtrans/callback
```

**Callback Payload Example (Settlement):**
```json
{
  "order_id": "RUANG-1234567890-AbCdEf",
  "transaction_status": "settlement",
  "transaction_id": "abc123",
  "payment_type": "gopay",
  "gross_amount": "50000.00",
  "signature_key": "calculated_hash"
}
```

**Expected Results After Callback:**
1. Donation `payment_status` updated to `'success'`
2. Donation `midtrans_transaction_id` updated
3. Donation `payment_method` updated
4. Campaign `collected_amount` increased by donation amount
5. User `wallet.total_donated` increased by donation amount
6. Event `DonationPaid` fired (if listeners exist)

---

### Callback Signature Verification

**Algorithm:** SHA512 Hash
```php
$signature = hash('sha512', 
    $order_id . 
    $status_code . 
    $gross_amount . 
    $server_key
);
```

**Security:** Midtrans callback handler verifies signature to prevent fraud.

---

### Transaction Status Mapping

**Midtrans Status → Donation Status:**

| Midtrans Status | Donation Status | Campaign/Wallet Updated |
|----------------|-----------------|-------------------------|
| `capture` | `success` | ✅ Yes |
| `settlement` | `success` | ✅ Yes |
| `pending` | `pending` | ❌ No |
| `deny` | `failed` | ❌ No |
| `cancel` | `failed` | ❌ No |
| `expire` | `expired` | ❌ No |

---

## Manual Callback Simulation

If Midtrans callback is not reaching your local server, you can simulate it:

**Using Postman:**
```bash
POST http://localhost:8000/api/midtrans/callback
Content-Type: application/json

{
  "order_id": "RUANG-1234567890-AbCdEf",
  "transaction_status": "settlement",
  "transaction_id": "test-txn-123",
  "payment_type": "gopay",
  "gross_amount": "50000.00",
  "status_code": "200",
  "signature_key": "{calculate_using_algorithm_above}"
}
```

**Signature Calculation Example:**
```php
$serverKey = config('services.midtrans.server_key');
$orderId = 'RUANG-1234567890-AbCdEf';
$statusCode = '200';
$grossAmount = '50000.00';

$signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
```

---

## Testing Scenarios

### Scenario 1: Successful Donation Flow
1. Create donation via API → Get snap token
2. Open Snap URL → Complete payment
3. Wait for callback → Verify status changed to `success`
4. Check campaign `collected_amount` increased
5. Check user `wallet.total_donated` increased

**Expected:** All updates processed correctly.

---

### Scenario 2: Expired Donation
1. Create donation → Get snap token
2. Do NOT complete payment
3. Wait for Midtrans expiration (default: 24 hours in sandbox, configurable)
4. Midtrans sends `expire` callback
5. Donation status → `expired`

**Expected:** No campaign/wallet updates.

---

### Scenario 3: Failed/Cancelled Payment
1. Create donation → Get snap token
2. Open Snap → Click "Cancel" or let it fail
3. Midtrans sends `cancel` or `deny` callback
4. Donation status → `failed`

**Expected:** No campaign/wallet updates.

---

### Scenario 4: Anonymous Donation
1. Create donation with `is_anonymous: true`
2. Complete payment
3. Get campaign donations list → Donor name shows as "Seseorang"
4. Owner views detail → Shows actual name (if owner is the user)

**Expected:** Privacy respected in public views.

---

### Scenario 5: Donation to Expired Campaign
1. Create campaign with `deadline` in past
2. Try to create donation
3. Receive error: "Kampanye ini sudah berakhir."

**Expected:** Donation creation blocked.

---

### Scenario 6: Donation to Unapproved Campaign
1. Create campaign with `status: 'pending'` or `'rejected'`
2. Try to create donation
3. Receive error: "Kampanye ini belum disetujui atau tidak tersedia."

**Expected:** Donation creation blocked.

---

## Admin Panel Features

### DonationResource

**Location:** `/admin/donations`

**Features:**
1. **Table View:**
   - ID, Donor Name, Campaign, Amount, Status, Payment Method, Date
   - Filters: Status, Campaign, Date Range
   - Badge count: Pending donations

2. **Actions:**
   - **Mark as Success:** Manually approve pending/failed donations
     - Updates campaign collected_amount
     - Updates user wallet
   - **Mark as Failed:** Manually fail pending/success donations
     - Rollback campaign/wallet if was success

3. **Pages:**
   - List: View all donations
   - View: See donation details
   - Edit: Modify donation fields
   - Create: Manual donation creation

---

### Dashboard Widgets

**StatsOverviewWidget:**
- Today Donations (total amount today)
- Monthly Donations (total amount this month)
- (Plus existing user/campaign stats)

**LatestDonationsWidget:**
- Table showing 10 latest successful donations
- Columns: ID, Donor, Campaign, Amount, Method, Date

---

## Common Issues & Solutions

### Issue 1: "Midtrans server key not configured"
**Solution:** Check `.env` file:
```env
MIDTRANS_SERVER_KEY=your_server_key_here
MIDTRANS_CLIENT_KEY=your_client_key_here
MIDTRANS_IS_PRODUCTION=false
```

---

### Issue 2: Callback not received
**Causes:**
- Local server not publicly accessible
- Firewall blocking webhook

**Solutions:**
- Use ngrok for local testing:
  ```bash
  ngrok http 8000
  ```
  Then update Midtrans webhook URL in dashboard to `https://your-ngrok-url.ngrok.io/api/midtrans/callback`
- Use manual callback simulation (see above)

---

### Issue 3: Signature verification failed
**Cause:** Incorrect server key or payload modification

**Debug:**
```php
// In MidtransCallbackController
Log::info('Callback received', $request->all());
Log::info('Calculated signature', ['sig' => $calculatedSignature]);
```

---

### Issue 4: Donation created but payment failed
**Solution:** Check donation in admin panel:
1. Go to `/admin/donations`
2. Find pending donation
3. Use "Mark as Success" action if payment was actually completed

---

### Issue 5: Amount not updating in campaign
**Cause:** Callback not processed or event listener missing

**Debug:**
1. Check donation `payment_status` in database
2. If still `pending`, callback not received
3. If `success` but campaign not updated, check `MidtransService::handleCallback()`

---

## Testing Checklist

- [ ] Create donation with valid campaign → Returns snap token
- [ ] Create donation with expired campaign → Returns error
- [ ] Create donation with amount < 10000 → Validation error
- [ ] Complete payment on Snap → Callback updates status
- [ ] Verify campaign collected_amount increased
- [ ] Verify user wallet total_donated increased
- [ ] Test anonymous donation → Name shows as "Seseorang" in public list
- [ ] Test GET /donations/mine → Returns user's donations
- [ ] Test GET /donations/{id} as owner → Shows detail
- [ ] Test GET /donations/{id} as non-owner for pending donation → Forbidden
- [ ] Test GET /campaigns/{id}/donations → Shows only successful donations
- [ ] Test admin panel "Mark as Success" action
- [ ] Test admin panel "Mark as Failed" action with rollback
- [ ] Verify dashboard widgets show correct stats

---

## Next Steps

After completing Section 4 testing:
1. Test complete user journey (register → create campaign → donate → withdraw)
2. Implement Section 5: Withdrawal System (if not yet done)
3. Set up production Midtrans account
4. Configure production webhook URL
5. Test with real payment methods

---

## Support

**Midtrans Documentation:**
- Snap API: https://docs.midtrans.com/en/snap/overview
- Webhook: https://docs.midtrans.com/en/after-payment/http-notification

**Laravel Documentation:**
- Events: https://laravel.com/docs/11.x/events
- Eloquent: https://laravel.com/docs/11.x/eloquent

---

**End of Section 4 Testing Guide**
