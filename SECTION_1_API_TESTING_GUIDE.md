# Section 1: Authentication & User Management - API Testing Guide

## 🎯 Overview

This guide helps you test the authentication API endpoints and admin panel user management.

---

## 📋 API Endpoints

### Public Endpoints (No Authentication)

#### 1. Register New User

```http
POST http://localhost:8000/api/auth/register
Content-Type: application/json

{
  "name": "Farrel Arvinza",
  "email": "farrel@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "081234567890"
}
```

**Expected Response (201):**

```json
{
    "success": true,
    "message": "Registrasi berhasil. Akun Anda menunggu verifikasi civitas.",
    "data": {
        "user": {
            "id": 1,
            "name": "Farrel Arvinza",
            "email": "farrel@example.com",
            "phone": "081234567890",
            "role": "user",
            "is_verified_civitas": false
        },
        "token": "1|xxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

---

#### 2. Login

```http
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "email": "farrel@example.com",
  "password": "password123"
}
```

**Expected Response (200):**

```json
{
    "success": true,
    "message": "Login berhasil.",
    "data": {
        "user": {
            "id": 1,
            "name": "Farrel Arvinza",
            "email": "farrel@example.com",
            "phone": "081234567890",
            "role": "user",
            "is_verified_civitas": false
        },
        "token": "2|xxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

---

### Protected Endpoints (Require Bearer Token)

#### 3. Get Profile

```http
GET http://localhost:8000/api/auth/profile
Authorization: Bearer {your_token_here}
```

**Expected Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Farrel Arvinza",
        "email": "farrel@example.com",
        "phone": "081234567890",
        "role": "user",
        "is_verified_civitas": false,
        "email_verified_at": null,
        "created_at": "2025-11-14T10:30:00.000000Z"
    }
}
```

---

#### 4. Update Profile

```http
PUT http://localhost:8000/api/auth/profile
Authorization: Bearer {your_token_here}
Content-Type: application/json

{
  "name": "Farrel Updated",
  "phone": "089876543210"
}
```

**Optional: Update password**

```json
{
    "name": "Farrel Updated",
    "phone": "089876543210",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Expected Response (200):**

```json
{
    "success": true,
    "message": "Profil berhasil diperbarui.",
    "data": {
        "id": 1,
        "name": "Farrel Updated",
        "email": "farrel@example.com",
        "phone": "089876543210",
        "role": "user",
        "is_verified_civitas": false
    }
}
```

---

#### 5. Get Wallet Info

```http
GET http://localhost:8000/api/auth/wallet
Authorization: Bearer {your_token_here}
```

**Expected Response (200):**

```json
{
    "success": true,
    "data": {
        "balance": 0,
        "available_balance": 0,
        "total_income": 0,
        "total_withdrawn": 0
    }
}
```

---

#### 6. Logout

```http
POST http://localhost:8000/api/auth/logout
Authorization: Bearer {your_token_here}
```

**Expected Response (200):**

```json
{
    "success": true,
    "message": "Logout berhasil."
}
```

---

## 🔐 Admin Panel Testing

### Access Admin Panel

1. Navigate to: `http://localhost:8000/admin`
2. Create admin user via tinker or database seeder
3. Login with admin credentials

### Admin Features to Test:

#### UserResource Features:

-   ✅ View all users in table
-   ✅ Filter by role (User/Admin)
-   ✅ Filter by civitas status (Verified/Unverified)
-   ✅ Use tabs: All Users, Unverified Civitas, Verified Civitas, Admins
-   ✅ Search by name/email/phone
-   ✅ Click "Verify Civitas" action button on user row
-   ✅ Create new user (optional password)
-   ✅ Edit user details
-   ✅ View user details

#### Dashboard Widget:

-   ✅ See "Total Users" stat
-   ✅ See "Pending Civitas Verification" stat (warning badge)
-   ✅ See "Verified Civitas" stat (success badge)
-   ✅ See "Active Campaigns" stat

---

## 🧪 Testing Workflow

### Complete User Journey:

1. **Register** via API → Get token → User created with `is_verified_civitas = false`
2. **Login** via API → Get fresh token
3. **Get Profile** → Verify data matches
4. **Get Wallet** → Confirm wallet auto-created with 0 balance
5. **Update Profile** → Change name/phone
6. **Admin Panel** → Login as admin
7. **Find User** → Go to User Management → Use "Unverified Civitas" tab
8. **Verify Civitas** → Click "Verify Civitas" action button
9. **API Profile** → Call GET /api/auth/profile again → Verify `is_verified_civitas = true`
10. **Logout** → Token revoked

---

## ⚠️ Error Cases to Test

### Registration Errors:

-   Missing required fields → 422 validation error
-   Duplicate email → 422 "Email sudah terdaftar"
-   Password < 8 chars → 422 validation error
-   Password confirmation mismatch → 422 "Konfirmasi password tidak cocok"

### Login Errors:

-   Wrong credentials → 401 "Email atau password salah"
-   Missing fields → 422 validation error

### Protected Route Errors:

-   No token → 401 Unauthenticated
-   Invalid token → 401 Unauthenticated
-   Revoked token (after logout) → 401 Unauthenticated

---

## 🛠️ Quick Test Commands

### Create Admin User (via Tinker)

```bash
php artisan tinker
```

```php
$admin = \App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@ruang.com',
    'password' => bcrypt('admin123'),
    'role' => 'admin',
    'is_verified_civitas' => true,
    'phone' => '081234567890',
]);

\App\Models\Wallet::create([
    'user_id' => $admin->id,
    'balance' => 0,
    'total_income' => 0,
    'total_withdrawn' => 0,
]);
```

### Check Routes

```bash
php artisan route:list --path=api/auth
```

### Clear Cache

```bash
php artisan optimize:clear
```

---

## ✅ Section 1 Completion Checklist

-   [x] AuthController created with 6 methods
-   [x] RegisterRequest validator with Indonesian messages
-   [x] LoginRequest validator
-   [x] UpdateProfileRequest validator
-   [x] API routes configured (public + protected)
-   [x] UserResource with civitas verification
-   [x] User table filters (role, civitas status)
-   [x] User tabs (All, Unverified, Verified, Admins)
-   [x] Toggle Civitas action button
-   [x] StatsOverviewWidget on dashboard
-   [x] Auto wallet creation on registration

---

## 🚀 Next Steps

After testing Section 1, move to **Section 2: Categories** to set up campaign categories.
