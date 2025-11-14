# Section 2: Categories - API Testing Guide

## 🎯 Overview
Simple category management for campaign classification. Categories are managed by admin and publicly accessible via API.

---

## 📋 API Endpoints

### Public Endpoint (No Authentication)

#### 1. Get All Categories
```http
GET http://localhost:8000/api/categories
```

**Expected Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Pendidikan",
      "slug": "pendidikan",
      "campaigns_count": 12
    },
    {
      "id": 2,
      "name": "Kesehatan",
      "slug": "kesehatan",
      "campaigns_count": 8
    },
    {
      "id": 3,
      "name": "Bencana Alam",
      "slug": "bencana-alam",
      "campaigns_count": 5
    },
    {
      "id": 4,
      "name": "Kemanusiaan",
      "slug": "kemanusiaan",
      "campaigns_count": 3
    }
  ]
}
```

**Features:**
- ✅ Returns all categories sorted alphabetically
- ✅ Includes campaign count for each category
- ✅ Public endpoint (no authentication required)

---

## 🔐 Admin Panel Features

### Access Admin Panel
Navigate to: `http://localhost:8000/admin/categories`

### CategoryResource Features:

#### Table View:
- ✅ ID, Name, Slug columns
- ✅ Campaign count badge (green)
- ✅ Search by name/slug
- ✅ Sortable columns
- ✅ View/Edit/Delete actions
- ✅ Bulk delete with confirmation

#### Create/Edit Form:
- ✅ **Category Name** - Auto-generates slug on blur
- ✅ **Slug** - Auto-filled, can be customized, unique validation
- ✅ Real-time slug generation (only on create)
- ✅ Clean section layout

#### Validation:
- ✅ Name required, max 191 chars
- ✅ Slug required, unique, max 191 chars

---

## 🧪 Testing Workflow

### Complete Category Journey:

1. **Admin Panel** → Login as admin
2. **Create Categories** → Go to Campaign Management → Categories
3. **Add Category** → Click "New Category"
   - Name: "Pendidikan"
   - Slug: Auto-filled as "pendidikan"
4. **Create More** → Add multiple categories (Kesehatan, Bencana Alam, Kemanusiaan)
5. **API Test** → Call `GET /api/categories`
6. **Verify Response** → Check all categories appear with campaign counts
7. **Edit Category** → Update name, verify slug updates
8. **Delete Category** → Try to delete (will fail if has campaigns due to foreign key)

---

## 📊 Sample Categories for Testing

Create these categories in admin panel:

| Name | Slug | Description |
|------|------|-------------|
| Pendidikan | pendidikan | Beasiswa, sekolah, dll |
| Kesehatan | kesehatan | Pengobatan, operasi |
| Bencana Alam | bencana-alam | Gempa, banjir, dll |
| Kemanusiaan | kemanusiaan | Bantuan sosial |
| Lingkungan | lingkungan | Pelestarian alam |
| Teknologi | teknologi | Inovasi, startup |

---

## ⚠️ Important Notes

### Database Constraints:
- Categories with campaigns **cannot be deleted** (foreign key constraint)
- Delete campaigns first, then category
- Or use soft deletes (optional future enhancement)

### Slug Behavior:
- Auto-generated from name on create (live update on blur)
- Can be manually edited before saving
- Must be unique across all categories
- Lowercase, hyphenated format

---

## ✅ Section 2 Completion Checklist

- [x] CategoryController with index method
- [x] GET /api/categories public route
- [x] CategoryResource with auto-slug generation
- [x] Campaign count column
- [x] Search, sort, filter capabilities
- [x] View, Edit, Delete actions
- [x] Unique slug validation
- [x] Navigation group: Campaign Management

---

## 🚀 Next Steps
After testing Section 2, move to **Section 3: Campaign Management** (the big one!).
