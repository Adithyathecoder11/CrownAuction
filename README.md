👑 CrownAuction
Automated Online Auction Platform with Analytics

## Tech Stack
PHP 8.x | MySQL 8.x | JavaScript | Chart.js | XAMPP

## Features
- Real-time bidding (AJAX polling every 5 seconds)
- 6-rule anti-fraud detection pipeline
- Seller analytics with Chart.js
- Role-based access: Buyer, Seller, Admin
- Race condition prevention (SELECT FOR UPDATE)
- bcrypt + PDO + CSRF + HttpOnly security

## How to Run
1. Install XAMPP
2. Copy project to htdocs/
3. Import database.sql in phpMyAdmin
4. Open localhost/auction_platform


## 🚀 SETUP INSTRUCTIONS (XAMPP / Localhost)

### Step 1 — Install XAMPP
Download and install XAMPP from https://apachefriends.org
Start **Apache** and **MySQL** from the XAMPP Control Panel.

### Step 2 — Place Files
Copy the `auction_platform` folder to:
```
C:\xampp\htdocs\auction_platform\       (Windows)
/Applications/XAMPP/htdocs/auction_platform/  (Mac)
/opt/lampp/htdocs/auction_platform/     (Linux)
```

### Step 3 — Create Database
1. Open http://localhost/phpmyadmin
2. Click **"New"** → Database name: `auction_platform` → Create
3. Click the database → **Import** tab
4. Choose file: `auction_platform/database.sql` → Click **Go**

### Step 4 — Configure Database
Open `config/database.php` and update if needed:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'auction_platform');
define('DB_USER', 'root');
define('DB_PASS', '');           // Your MySQL password (blank for XAMPP default)
```

### Step 5 — Set App URL
In `config/database.php`, confirm:
```php
define('APP_URL', 'http://localhost/auction_platform');
```

### Step 6 — Uploads Folder Permissions
Ensure this folder exists and is writable:
```
auction_platform/public/images/uploads/
```
On Linux/Mac: `chmod 775 public/images/uploads/`

### Step 7 — Access the Application
- **Homepage / Login:** http://localhost/auction_platform/
- **Admin Panel:**      http://localhost/auction_platform/admin-secret.php


## 📊 KEY FEATURES CHECKLIST

| Feature                        | Status |
|-------------------------------|--------|
| Role-based auth (Buyer/Seller/Admin) | ✅ |
| CSRF protection on all forms  | ✅ |
| bcrypt password hashing        | ✅ |
| Session management             | ✅ |
| Secret admin panel             | ✅ |
| Create auction (30min–6hr)     | ✅ |
| Secure image upload + preview  | ✅ |
| Real-time bidding (AJAX poll)  | ✅ |
| Live countdown timer           | ✅ |
| Bid validation (min increment) | ✅ |
| Anti self-bidding              | ✅ |
| Rate limit (3 bids/min)        | ✅ |
| Abnormal jump detection        | ✅ |
| Fraud flagging system          | ✅ |
| Auto auction close             | ✅ |
| Winner declaration             | ✅ |
| Post-auction messaging         | ✅ |
| Seller analytics + Chart.js    | ✅ |
| Bidder leaderboard             | ✅ |
| Admin ban/unban users          | ✅ |
| Admin override auctions        | ✅ |
| Admin audit logs               | ✅ |
| Search & filter auctions       | ✅ |
| Pagination                     | ✅ |
| Responsive design              | ✅ |
| INR currency (₹) throughout    | ✅ |
| Transaction payment/delivery status | ✅ |
| PDO prepared statements        | ✅ |


---

## 🔒 SECURITY MEASURES

1. All DB queries use PDO prepared statements (zero SQL injection)
2. CSRF tokens on every form
3. Session regeneration on login
4. HttpOnly session cookies
5. File upload validation (MIME + extension + size)
6. Role checks on every protected page
7. Admin secret key uses `hash_equals` (timing-safe comparison)
8. XSS prevention via `htmlspecialchars` on all output
