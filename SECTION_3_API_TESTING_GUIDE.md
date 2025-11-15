# Section 3: Campaign Management - API Testing Guide

## Overview

Complete campaign lifecycle with edit approval workflow, civitas verification, and update posting.

**IMPORTANT:** All image fields (`cover_image` for campaigns, `media` for updates) use **file uploads** (multipart/form-data), not string paths.

---

## File Upload Requirements

### Campaign Cover Image

-   **Field name:** `cover_image`
-   **Type:** File Upload (Image)
-   **Required:** No (optional, defaults to `default.jpg`)
-   **Allowed formats:** jpeg, png, jpg
-   **Max size:** 2MB
-   **Storage:** `storage/app/public/campaigns/`
-   **Access URL:** `http://localhost/storage/campaigns/{filename}`

### Update Media

-   **Field name:** `media`
-   **Type:** File Upload (Image)
-   **Required:** No (optional)
-   **Allowed formats:** jpeg, png, jpg
-   **Max size:** 2MB
-   **Storage:** `storage/app/public/updates/`
-   **Access URL:** `http://localhost/storage/updates/{filename}`

### Storage Setup

Ensure storage link exists:

```bash
php artisan storage:link
```

---

## API Endpoints

### 1. Public Endpoints (No Auth Required)

#### GET /api/campaigns

**Description:** Get paginated list of campaigns with filters

**Query Parameters:**

-   `category_id` (optional): Filter by category
-   `search` (optional): Search in title or description
-   `sort` (optional): `latest`, `ending_soon`, `popular` (default: latest)
-   `page` (optional): Page number (default: 1)

**Example Request:**

```bash
GET http://localhost/api/campaigns?category_id=1&sort=popular&page=1
```

**Expected Response (200):**

```json
{
  "data": [
    {
      "id": 1,
      "user_id": 2,
      "category_id": 1,
      "category": {
        "id": 1,
        "name": "Pendidikan",
        "slug": "pendidikan",
        "icon": "📚"
      },
      "title": "Bantu Mahasiswa Kurang Mampu",
      "slug": "bantu-mahasiswa-kurang-mampu",
      "description": "Kampanye untuk membantu biaya kuliah mahasiswa...",
      "story": "Detail cerita kampanye...",
      "target_amount": 10000000,
      "collected_amount": 2500000,
      "deadline": "2025-12-31",
      "status": "approved",
      "image_path": "/storage/campaigns/image.jpg",
      "created_at": "2025-11-15T10:00:00.000000Z",
      "updated_at": "2025-11-15T12:00:00.000000Z"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

#### GET /api/campaigns/{id}

**Description:** Get campaign detail (approved campaigns are public, others require ownership)

**Example Request:**

```bash
GET http://localhost/api/campaigns/1
```

**Expected Response (200):**

```json
{
    "id": 1,
    "user_id": 2,
    "category_id": 1,
    "category": {
        "id": 1,
        "name": "Pendidikan",
        "slug": "pendidikan",
        "icon": "📚"
    },
    "title": "Bantu Mahasiswa Kurang Mampu",
    "slug": "bantu-mahasiswa-kurang-mampu",
    "description": "Kampanye untuk membantu biaya kuliah mahasiswa...",
    "story": "Detail cerita kampanye...",
    "target_amount": 10000000,
    "collected_amount": 2500000,
    "deadline": "2025-12-31",
    "status": "approved",
    "image_path": "/storage/campaigns/image.jpg",
    "created_at": "2025-11-15T10:00:00.000000Z",
    "updated_at": "2025-11-15T12:00:00.000000Z"
}
```

**Error Response (403) - Non-approved & Not Owner:**

```json
{
    "message": "Kampanye ini belum tersedia untuk publik."
}
```

---

#### GET /api/campaigns/{id}/updates

**Description:** Get list of campaign updates (public)

**Example Request:**

```bash
GET http://localhost/api/campaigns/1/updates
```

**Expected Response (200):**

```json
{
    "data": [
        {
            "id": 1,
            "campaign_id": 1,
            "user_id": 2,
            "title": "Target 25% Tercapai!",
            "content": "Terima kasih atas dukungan teman-teman...",
            "media_path": "/storage/updates/progress.jpg",
            "created_at": "2025-11-16T10:00:00.000000Z"
        }
    ]
}
```

---

### 2. Protected Endpoints (Require Authentication)

**Authentication Header:**

```
Authorization: Bearer {access_token}
```

---

#### POST /api/campaigns

**Description:** Create new campaign (status will be 'pending')

**Content-Type:** `multipart/form-data` (for file upload)

**Request Fields:**

-   `category_id`: (required) Category ID
-   `title`: (required) Campaign title, max 191 chars
-   `description`: (required) Short description
-   `target_amount`: (required) Min 100,000, Max 1,000,000,000
-   `deadline`: (required) Date after today (YYYY-MM-DD)
-   `cover_image`: (optional) Image file (JPEG/PNG/JPG, max 2MB)

**cURL Example:**

```bash
curl -X POST http://localhost/api/campaigns \
  -H "Authorization: Bearer {token}" \
  -F "category_id=1" \
  -F "title=Bantu Korban Bencana Alam" \
  -F "description=Deskripsi singkat kampanye" \
  -F "target_amount=5000000" \
  -F "deadline=2025-12-31" \
  -F "cover_image=@/path/to/image.jpg"
```

**Postman Setup:**

1. Method: POST
2. Body type: `form-data`
3. Add all fields as `Text` type
4. For `cover_image`: Select `File` type and choose image file

**Request Body (JSON format for reference only):**

```json
{
    "category_id": 1,
    "title": "Bantu Korban Bencana Alam",
    "description": "Deskripsi singkat kampanye",
    "target_amount": 5000000,
    "deadline": "2025-12-31",
    "cover_image": "[File Upload]"
}
```

**Validation Rules:**

-   `category_id`: required, exists:categories
-   `title`: required, max:191
-   `description`: required
-   `target_amount`: required, numeric, min:100000, max:1000000000
-   `deadline`: required, date, after:today
-   `cover_image`: nullable, image, mimes:jpeg,png,jpg, max:2048KB

**Expected Response (201):**

```json
{
    "id": 5,
    "user_id": 2,
    "category_id": 1,
    "title": "Bantu Korban Bencana Alam",
    "slug": "bantu-korban-bencana-alam",
    "description": "Deskripsi singkat kampanye",
    "target_amount": 5000000,
    "collected_amount": 0,
    "deadline": "2025-12-31",
    "status": "pending",
    "cover_image": "campaigns/abc123xyz456.jpg",
    "pending_changes": null,
    "created_at": "2025-11-17T10:00:00.000000Z",
    "updated_at": "2025-11-17T10:00:00.000000Z"
}
```

**Note:** The `cover_image` returns the stored path. Access via: `http://localhost/storage/campaigns/abc123xyz456.jpg`

**Error Response (422) - Validation:**

```json
{
    "message": "Kategori harus dipilih. (and 2 more errors)",
    "errors": {
        "category_id": ["Kategori harus dipilih."],
        "target_amount": ["Target minimal adalah Rp 100.000."],
        "deadline": ["Tenggat waktu harus setelah hari ini."]
    }
}
```

**Error Response (422) - Invalid Image:**

```json
{
    "success": false,
    "message": "Validasi gagal.",
    "errors": {
        "cover_image": ["File harus berupa gambar."]
    }
}
```

**Error Response (422) - File Too Large:**

```json
{
    "success": false,
    "message": "Validasi gagal.",
    "errors": {
        "cover_image": ["Ukuran gambar maksimal 2MB."]
    }
}
```

---

#### GET /api/campaigns/mine

**Description:** Get user's own campaigns with pending_changes visible

**Example Request:**

```bash
GET http://localhost/api/campaigns/mine
Authorization: Bearer {token}
```

**Expected Response (200):**

```json
{
  "data": [
    {
      "id": 5,
      "user_id": 2,
      "category_id": 1,
      "category": {...},
      "title": "Bantu Korban Bencana Alam",
      "slug": "bantu-korban-bencana-alam",
      "description": "Deskripsi singkat kampanye",
      "story": "Cerita lengkap tentang kampanye ini...",
      "target_amount": 5000000,
      "collected_amount": 1000000,
      "deadline": "2025-12-31",
      "status": "edit_pending",
      "image_path": "/storage/campaigns/flood-victims.jpg",
      "pending_changes": {
        "target_amount": 7000000,
        "deadline": "2026-01-31"
      },
      "created_at": "2025-11-17T10:00:00.000000Z",
      "updated_at": "2025-11-18T14:00:00.000000Z"
    }
  ]
}
```

---

#### PUT /api/campaigns/{id}

**Description:** Update campaign (smart edit logic based on status)

**Content-Type:** `multipart/form-data` (if uploading image)

**Edit Logic:**

-   **pending**: Direct edit allowed (including cover_image). Old image auto-deleted (except default.jpg)
-   **approved**: Only `target_amount` and `deadline` can be requested. Changes saved to `pending_changes` and status becomes `edit_pending`
-   **edit_pending**: Update `pending_changes` (editable fields only)
-   **rejected/closed**: Not allowed

**cURL Example (Pending Campaign with Image):**

```bash
curl -X POST http://localhost/api/campaigns/5?_method=PUT \
  -H "Authorization: Bearer {token}" \
  -F "title=Updated Title" \
  -F "description=Updated description" \
  -F "target_amount=6000000" \
  -F "deadline=2026-01-15" \
  -F "cover_image=@/path/to/new-image.jpg"
```

**Note:** Laravel doesn't support file uploads with PUT, so use POST with `_method=PUT` parameter.

**Request Body (for pending campaign - JSON reference):**

```json
{
    "category_id": 2,
    "title": "Updated Title",
    "description": "Updated description",
    "target_amount": 6000000,
    "deadline": "2026-01-15",
    "cover_image": "[File Upload]"
}
```

**Request Body (for approved campaign - edit request):**

```json
{
    "target_amount": 7000000,
    "deadline": "2026-02-28"
}
```

**Expected Response (200) - Pending Campaign:**

```json
{
    "id": 5,
    "title": "Updated Title",
    "target_amount": 6000000,
    "deadline": "2026-01-15",
    "status": "pending",
    "pending_changes": null
}
```

**Expected Response (200) - Approved Campaign (Edit Request):**

```json
{
    "id": 3,
    "title": "Original Title",
    "target_amount": 5000000,
    "deadline": "2025-12-31",
    "status": "edit_pending",
    "pending_changes": {
        "target_amount": 7000000,
        "deadline": "2026-02-28"
    }
}
```

**Error Response (403) - Not Owner:**

```json
{
    "message": "Unauthorized."
}
```

**Error Response (403) - Cannot Edit (rejected/closed):**

```json
{
    "message": "Kampanye dengan status rejected/closed tidak dapat diubah."
}
```

---

#### DELETE /api/campaigns/{id}

**Description:** Delete campaign (only if status is 'pending')

**Example Request:**

```bash
DELETE http://localhost/api/campaigns/5
Authorization: Bearer {token}
```

**Expected Response (200):**

```json
{
    "message": "Kampanye berhasil dihapus."
}
```

**Error Response (403) - Not Pending:**

```json
{
    "message": "Hanya kampanye dengan status pending yang dapat dihapus."
}
```

---

#### POST /api/campaigns/{id}/updates

**Description:** Post campaign update (only for approved campaigns, owner only)

**Content-Type:** `multipart/form-data` (for file upload)

**Request Fields:**

-   `title`: (required) Update title, max 191 chars
-   `content`: (required) Update content
-   `media`: (optional) Image file (JPEG/PNG/JPG, max 2MB)

**cURL Example:**

```bash
curl -X POST http://localhost/api/campaigns/1/updates \
  -H "Authorization: Bearer {token}" \
  -F "title=Target 50% Tercapai!" \
  -F "content=Alhamdulillah, dengan dukungan 150 donatur..." \
  -F "media=@/path/to/progress.jpg"
```

**Request Body (JSON reference):**

```json
{
    "title": "Target 50% Tercapai!",
    "content": "Alhamdulillah, dengan dukungan 150 donatur...",
    "media": "[File Upload]"
}
```

**Validation Rules:**

-   `title`: required, max:191
-   `content`: required
-   `media`: nullable, image, mimes:jpeg,png,jpg, max:2048KB

**Expected Response (201):**

```json
{
    "id": 3,
    "campaign_id": 1,
    "user_id": 2,
    "title": "Target 50% Tercapai!",
    "content": "Alhamdulillah, dengan dukungan 150 donatur...",
    "media_path": "updates/xyz789abc.jpg",
    "created_at": "2025-11-18T10:00:00.000000Z",
    "updated_at": "2025-11-18T10:00:00.000000Z"
}
```

**Note:** Access media via: `http://localhost/storage/updates/xyz789abc.jpg`

**Error Response (403) - Not Approved:**

```json
{
    "message": "Hanya kampanye yang sudah disetujui yang bisa diupdate."
}
```

---

## Admin Panel Features

### Campaign Resource (app/Filament/Admin/Resources/CampaignResource.php)

#### Navigation

-   **Icon:** heroicon-o-megaphone
-   **Label:** Campaigns
-   **Group:** Campaign Management
-   **Sort:** 1

#### Table Columns

-   ID
-   Title
-   Owner (user name)
-   Category (badge with color)
-   Target Amount (IDR format)
-   Collected Amount (IDR format)
-   Progress % (with color coding: <30% danger, 30-70% warning, >70% success)
-   Status (badge)
-   Deadline (date format)

#### Filters

-   Status (All, Pending, Approved, Edit Pending, Rejected, Closed)
-   Category

#### Custom Actions

**1. Approve (Visible if pending)**

-   Button: Success color
-   Calls: `CampaignService::approveCampaign($record, auth()->user())`
-   Success notification: "Kampanye berhasil disetujui."

**2. Reject (Visible if pending)**

-   Button: Danger color
-   Form: `notes` textarea
-   Calls: `CampaignService::rejectCampaign($record, auth()->user(), $data['notes'])`
-   Success notification: "Kampanye berhasil ditolak."

**3. Approve Edit (Visible if edit_pending)**

-   Button: Info color
-   Logic:
    ```php
    if ($record->pending_changes) {
        $record->target_amount = $record->pending_changes['target_amount'];
        $record->deadline = $record->pending_changes['deadline'];
        $record->pending_changes = null;
        $record->status = 'approved';
        $record->save();
    }
    ```
-   Success notification: "Perubahan kampanye berhasil disetujui."

**4. Reject Edit (Visible if edit_pending)**

-   Button: Warning color
-   Logic:
    ```php
    $record->pending_changes = null;
    $record->status = 'approved';
    $record->save();
    ```
-   Success notification: "Perubahan kampanye ditolak, status kembali ke approved."

#### Tabs (ListCampaigns.php)

-   **All:** Show total count
-   **Pending:** Warning badge with count
-   **Approved:** Success badge with count
-   **Edit Pending:** Info badge with count
-   **Rejected:** Danger badge with count
-   **Closed:** Gray badge with count

---

### CampaignVerificationRequest Resource

#### Navigation

-   **Icon:** heroicon-o-shield-check
-   **Label:** Civitas Verifications
-   **Group:** Campaign Management
-   **Sort:** 2

#### Table Columns

-   ID
-   Campaign (title)
-   Full Name
-   Identity Type (badge: KTM/KTP/Surat Tugas)
-   Identity Number
-   Organization
-   Status (badge)
-   Reviewed At (date)

#### Actions

**1. Approve**

-   Button: Success color
-   Logic:
    ```php
    $record->verification_status = 'approved';
    $record->reviewed_by = auth()->id();
    $record->reviewed_at = now();
    $record->save();
    ```
-   Success notification: "Verifikasi civitas berhasil disetujui."

**2. Reject**

-   Button: Danger color
-   Form: `notes` textarea
-   Logic:
    ```php
    $record->verification_status = 'rejected';
    $record->reviewed_by = auth()->id();
    $record->reviewed_at = now();
    $record->notes = $data['notes'];
    $record->save();
    ```
-   Success notification: "Verifikasi civitas ditolak."

#### Filter

-   Verification Status (pending, approved, rejected)

---

### Update Resource (Read-Only)

#### Navigation

-   **Icon:** heroicon-o-newspaper
-   **Label:** Campaign Updates
-   **Group:** Campaign Management
-   **Sort:** 4

#### Table Columns

-   ID
-   Campaign (title)
-   Posted By (user name)
-   Title
-   Content (limit 50 chars)
-   Created At (dateTime)

#### Actions

-   **View:** View full update details
-   **Delete:** Remove inappropriate content (with confirmation)

#### Filter

-   Campaign (select dropdown)

**Note:** Updates are created via API by campaign owners only. No create/edit in admin panel.

---

## Complete Testing Workflow

### Scenario 1: Create Campaign → Approve → Edit → Approve Edit

**Step 1: Create Campaign**

```bash
POST http://localhost/api/campaigns
Authorization: Bearer {user_token}

{
  "category_id": 1,
  "title": "Bantu Mahasiswa Kurang Mampu",
  "description": "Kampanye untuk membantu biaya kuliah",
  "story": "Cerita lengkap kampanye...",
  "target_amount": 5000000,
  "deadline": "2025-12-31",
  "image_path": "/storage/campaigns/student-aid.jpg"
}
```

✅ **Expected:** Campaign created with `status: pending`

---

**Step 2: Admin Approves Campaign**

Go to Admin Panel → Campaigns → Click "Approve" button on the pending campaign

✅ **Expected:**

-   Campaign `status` changes to `approved`
-   Campaign visible in public `/api/campaigns` list
-   `approved_by` and `approved_at` set (via CampaignService)
-   CampaignApproved event fired

---

**Step 3: Owner Requests Edit (Increase Target & Extend Deadline)**

```bash
PUT http://localhost/api/campaigns/1
Authorization: Bearer {user_token}

{
  "target_amount": 7000000,
  "deadline": "2026-01-31"
}
```

✅ **Expected:**

-   Original campaign still shows `target_amount: 5000000`, `deadline: 2025-12-31`
-   `status` changes to `edit_pending`
-   `pending_changes`: `{"target_amount": 7000000, "deadline": "2026-01-31"}`
-   Public still sees OLD data

---

**Step 4: Admin Approves Edit**

Go to Admin Panel → Campaigns → Click "Approve Edit" button on edit_pending campaign

✅ **Expected:**

-   `target_amount` updated to 7000000
-   `deadline` updated to 2026-01-31
-   `pending_changes` cleared (null)
-   `status` changes back to `approved`
-   Public now sees NEW data

---

### Scenario 2: Owner Posts Update

**Pre-requisite:** Campaign must have `status: approved`

```bash
POST http://localhost/api/campaigns/1/updates
Authorization: Bearer {user_token}

{
  "title": "Target 30% Tercapai!",
  "content": "Terima kasih kepada 50 donatur yang sudah membantu...",
  "media_path": "/storage/updates/progress-30.jpg"
}
```

✅ **Expected:**

-   Update record created in `updates` table
-   Visible in Admin Panel → Campaign Updates
-   Visible in public `/api/campaigns/1/updates`

---

### Scenario 3: Delete Pending Campaign

```bash
DELETE http://localhost/api/campaigns/5
Authorization: Bearer {user_token}
```

✅ **Expected:** Campaign deleted if status is `pending`

❌ **Expected (if not pending):** Error 403 "Hanya kampanye dengan status pending yang dapat dihapus."

---

### Scenario 4: Admin Rejects Edit

**Step 1:** Owner submits edit request (same as Scenario 1, Step 3)

**Step 2:** Admin Rejects Edit

Go to Admin Panel → Campaigns → Click "Reject Edit" button on edit_pending campaign

✅ **Expected:**

-   `pending_changes` cleared (null)
-   `status` changes back to `approved`
-   Original data preserved (no changes applied)

---

## Validation Error Examples

### Target Amount Too Low

```json
{
    "message": "Target minimal adalah Rp 100.000.",
    "errors": {
        "target_amount": ["Target minimal adalah Rp 100.000."]
    }
}
```

### Target Amount Too High

```json
{
    "message": "Target maksimal adalah Rp 1.000.000.000.",
    "errors": {
        "target_amount": ["Target maksimal adalah Rp 1.000.000.000."]
    }
}
```

### Deadline in the Past

```json
{
    "message": "Tenggat waktu harus setelah hari ini.",
    "errors": {
        "deadline": ["Tenggat waktu harus setelah hari ini."]
    }
}
```

### Category Not Found

```json
{
    "message": "Kategori yang dipilih tidak valid.",
    "errors": {
        "category_id": ["Kategori yang dipilih tidak valid."]
    }
}
```

---

## Database State Examples

### Campaign with Edit Pending

```sql
SELECT id, title, target_amount, deadline, status, pending_changes FROM campaigns WHERE id = 1;
```

**Result:**

```
+----+---------------------------+---------------+------------+--------------+---------------------------------------------+
| id | title                     | target_amount | deadline   | status       | pending_changes                             |
+----+---------------------------+---------------+------------+--------------+---------------------------------------------+
| 1  | Bantu Mahasiswa Kurang... | 5000000       | 2025-12-31 | edit_pending | {"target_amount":7000000,"deadline":"2026..."}|
+----+---------------------------+---------------+------------+--------------+---------------------------------------------+
```

### After Admin Approves Edit

```sql
SELECT id, title, target_amount, deadline, status, pending_changes FROM campaigns WHERE id = 1;
```

**Result:**

```
+----+---------------------------+---------------+------------+----------+-----------------+
| id | title                     | target_amount | deadline   | status   | pending_changes |
+----+---------------------------+---------------+------------+----------+-----------------+
| 1  | Bantu Mahasiswa Kurang... | 7000000       | 2026-01-31 | approved | NULL            |
+----+---------------------------+---------------+------------+----------+-----------------+
```

---

## File Upload Testing Guide

### Frontend Integration Examples

#### React/Vue - FormData Upload

```javascript
const formData = new FormData();
formData.append("category_id", 1);
formData.append("title", "Campaign Title");
formData.append("description", "Description");
formData.append("target_amount", 5000000);
formData.append("deadline", "2025-12-31");
formData.append("cover_image", fileInput.files[0]); // File from input

fetch("http://localhost/api/campaigns", {
    method: "POST",
    headers: {
        Authorization: `Bearer ${token}`,
        // Don't set Content-Type, browser sets it with boundary automatically
    },
    body: formData,
})
    .then((response) => response.json())
    .then((data) => console.log(data));
```

#### Display Uploaded Images

```javascript
// Campaign cover image
const imageUrl = `${baseURL}/storage/${campaign.cover_image}`;
<img src={imageUrl} alt={campaign.title} />;

// Update media
const mediaUrl = `${baseURL}/storage/${update.media_path}`;
<img src={mediaUrl} alt={update.title} />;
```

#### Update Campaign with Image (Use POST with \_method)

```javascript
const formData = new FormData();
formData.append("_method", "PUT"); // Important for Laravel
formData.append("title", "Updated Title");
formData.append("cover_image", fileInput.files[0]);

fetch(`http://localhost/api/campaigns/${id}?_method=PUT`, {
    method: "POST", // Use POST, not PUT
    headers: { Authorization: `Bearer ${token}` },
    body: formData,
});
```

---

### Storage & File Management

#### Storage Structure

```
storage/
  app/
    public/
      campaigns/          # Campaign cover images
        abc123xyz.jpg
        def456uvw.png
      updates/            # Update media images
        ghi789rst.jpg
```

#### Public Access URLs

-   Campaign images: `http://localhost/storage/campaigns/{filename}`
-   Update media: `http://localhost/storage/updates/{filename}`

#### Automatic File Deletion

When updating a pending campaign's `cover_image`:

-   Old image automatically deleted (except `default.jpg`)
-   New image replaces it
-   No manual cleanup needed

---

### Common File Upload Issues

#### Issue: "Uploaded file exceeds maximum size"

**Solution:** Check `php.ini`:

```ini
upload_max_filesize = 10M
post_max_size = 10M
```

Restart web server after changes.

#### Issue: "Storage link not found" or "404 on /storage/"

**Solution:**

```bash
php artisan storage:link
```

Verify `public/storage` symlink exists.

#### Issue: "Cannot update with file (405 Method Not Allowed)"

**Solution:** Use POST with `_method=PUT`:

```bash
curl -X POST "http://localhost/api/campaigns/1?_method=PUT" -F "cover_image=@image.jpg"
```

#### Issue: "Image not displaying"

**Solutions:**

1. Verify storage link: `ls -la public/storage` (Linux) or `dir public\storage` (Windows)
2. Check file permissions on `storage/app/public/`
3. Ensure `APP_URL` in `.env` matches your server URL
4. Check browser console for CORS or 404 errors

---

### File Upload Validation Tests

#### Valid Image Upload

```bash
curl -X POST http://localhost/api/campaigns \
  -H "Authorization: Bearer {token}" \
  -F "category_id=1" \
  -F "title=Test Campaign" \
  -F "description=Description" \
  -F "target_amount=5000000" \
  -F "deadline=2025-12-31" \
  -F "cover_image=@valid-image.jpg"
```

✅ **Expected:** 201 Created, file in `storage/app/public/campaigns/`

#### Image Too Large (>2MB)

```bash
curl -X POST http://localhost/api/campaigns \
  -H "Authorization: Bearer {token}" \
  -F "cover_image=@large-image.jpg"
  ... (other fields)
```

❌ **Expected:** 422 Validation Error

```json
{
    "errors": {
        "cover_image": ["Ukuran gambar maksimal 2MB."]
    }
}
```

#### Invalid File Type (PDF)

```bash
curl -X POST http://localhost/api/campaigns \
  -H "Authorization: Bearer {token}" \
  -F "cover_image=@document.pdf"
  ... (other fields)
```

❌ **Expected:** 422 Validation Error

```json
{
    "errors": {
        "cover_image": ["Format gambar harus jpeg, png, atau jpg."]
    }
}
```

#### No Image (Use Default)

```bash
curl -X POST http://localhost/api/campaigns \
  -H "Authorization: Bearer {token}" \
  -F "category_id=1" \
  ... (no cover_image field)
```

✅ **Expected:** 201 Created with `"cover_image": "default.jpg"`

---

## Key Points to Test

✅ **Campaign Lifecycle:**

1. Create → Status `pending`
2. Admin Approve → Status `approved`, visible publicly
3. Owner Edit Request → Status `edit_pending`, pending_changes populated, public sees old data
4. Admin Approve Edit → Changes applied, status back to `approved`, public sees new data
5. Admin Reject Edit → pending_changes cleared, status back to `approved`, no changes applied

✅ **Edit Restrictions:**

-   Pending campaigns: All fields editable
-   Approved campaigns: Only `target_amount` and `deadline` editable (creates edit request)
-   Edit pending campaigns: Can update `pending_changes` (only target_amount and deadline)
-   Rejected/Closed campaigns: Cannot edit

✅ **Delete Restrictions:**

-   Only `pending` campaigns can be deleted
-   Approved/edit_pending/rejected/closed cannot be deleted

✅ **Update Posting:**

-   Only approved campaigns can post updates
-   Only campaign owner can post updates
-   Updates visible publicly via `/api/campaigns/{id}/updates`

✅ **Civitas Verification:**

-   Admin reviews via CampaignVerificationRequest resource
-   Approve/Reject actions update status and track reviewer

✅ **Admin Panel:**

-   6 status tabs with badge counts
-   4 custom actions (Approve, Reject, Approve Edit, Reject Edit)
-   Progress percentage with color coding
-   Filters by status and category

---

## Checklist Before Moving to Section 4

-   [ ] Test campaign creation with valid data
-   [ ] Test campaign creation with invalid data (validation errors)
-   [ ] Test admin approve campaign
-   [ ] Test admin reject campaign
-   [ ] Test owner edit request on approved campaign
-   [ ] Test admin approve edit
-   [ ] Test admin reject edit
-   [ ] Test update posting on approved campaign
-   [ ] Test update posting on pending campaign (should fail)
-   [ ] Test delete pending campaign
-   [ ] Test delete approved campaign (should fail)
-   [ ] Test public campaign list with filters
-   [ ] Test public campaign detail
-   [ ] Test civitas verification approve/reject
-   [ ] Verify pending_changes JSON structure
-   [ ] Verify all admin panel tabs show correct counts
-   [ ] Verify progress percentage calculations

#### File Upload Tests

-   [ ] Upload valid image (JPEG, PNG, JPG) for campaign
-   [ ] Upload image larger than 2MB (should fail with validation error)
-   [ ] Upload non-image file (PDF, TXT) (should fail)
-   [ ] Create campaign without image (should use default.jpg)
-   [ ] Update pending campaign with new image (old image should be deleted)
-   [ ] Verify uploaded files in `storage/app/public/campaigns/`
-   [ ] Verify files accessible via `/storage/campaigns/{filename}`
-   [ ] Post update with media image
-   [ ] Post update without media (optional field)
-   [ ] Verify update media in `storage/app/public/updates/`
-   [ ] Test image display in admin panel (50x50 thumbnails)
-   [ ] Test Filament image editor feature

---

**Ready for Section 4: Donation Flow** 🚀
