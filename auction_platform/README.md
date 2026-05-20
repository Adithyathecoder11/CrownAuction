# ⚖ BidMaster — Automated Online Auction Platform
### Academic Project | PHP + MySQL + Vanilla JS

---

## 📁 COMPLETE FOLDER STRUCTURE

```
auction_platform/
├── index.php                          ← Login / Register (homepage)
├── logout.php                         ← User logout
├── admin-secret.php                   ← Hidden admin login (secret key)
├── admin_logout.php                   ← Admin logout
├── database.sql                       ← Complete MySQL schema + sample data
│
├── config/
│   └── database.php                   ← DB config, constants, PDO singleton
│
├── includes/
│   └── session.php                    ← Auth helpers, CSRF, flash messages
│
├── models/
│   ├── UserModel.php                  ← User CRUD, ban/unban, fraud score
│   ├── AuctionModel.php               ← Auction CRUD, filters, auto-close
│   ├── BidModel.php                   ← Bid logic, fraud detection, rate limits
│   └── TransactionModel.php          ← Post-auction transactions + messaging
│
├── controllers/
│   ├── AuthController.php             ← Login, register, logout
│   ├── AuctionController.php          ← Create auction, image upload
│   └── AdminController.php            ← Ban, override, logs
│
├── api/
│   ├── place_bid.php                  ← POST: place a bid (fraud-checked)
│   ├── get_auction.php                ← GET: real-time auction state
│   ├── analytics.php                  ← GET: seller analytics data
│   ├── messages.php                   ← POST/GET: buyer-seller chat
│   └── update_status.php             ← POST: update payment/delivery status
│
├── views/
│   ├── shared/
│   │   ├── header.php                 ← Navbar + CSS design system
│   │   └── footer.php                 ← Footer + live server time
│   │
│   ├── buyer/
│   │   ├── browse.php                 ← Auction grid with filters + countdown
│   │   ├── auction_detail.php         ← Live bidding page
│   │   ├── my_bids.php                ← Bid history & status
│   │   ├── transactions.php           ← Won auctions list
│   │   └── transaction_detail.php     ← Post-auction contact + chat
│   │
│   ├── seller/
│   │   ├── dashboard.php              ← Seller overview + Chart.js trend
│   │   ├── create_auction.php         ← Create auction with image preview
│   │   ├── my_auctions.php            ← All auctions with live timers
│   │   ├── analytics.php              ← Per-auction deep analytics
│   │   └── transactions.php           ← Completed sales list
│   │
│   └── admin/
│       └── dashboard.php              ← Full admin control panel
│
└── public/
    └── images/
        └── uploads/                   ← Uploaded auction item images
```

---

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

---

## 🔑 TEST CREDENTIALS

| Role   | Email                 | Password       |
|--------|-----------------------|----------------|
| Buyer  | buyer@demo.com        | (see note)     |
| Seller | seller@demo.com       | (see note)     |
| Admin  | (use secret key)      | —              |

> ⚠️ The sample data uses a placeholder hash. **Register new accounts** via the homepage for full testing.

### Admin Secret Key:
```
BidMaster_Admin_2024_SecretKey_XYZ
```
Go to: http://localhost/auction_platform/admin-secret.php

---

## 🧪 QUICK TEST FLOW

### Buyer Flow:
1. Register as a **Buyer**
2. Browse auctions at `/views/buyer/browse.php`
3. Click an auction → Place a bid
4. Watch bid history update in real-time (5-second poll)
5. After auction ends → Go to Transactions → Chat with seller

### Seller Flow:
1. Register as a **Seller**
2. Go to Dashboard → Click **+ Create Auction**
3. Fill details, upload image, set 30-min duration
4. Watch your auction go live
5. Monitor bids in My Auctions / Analytics
6. After end → View transaction & contact buyer

### Admin Flow:
1. Go to `/admin-secret.php`
2. Enter secret key: `BidMaster_Admin_2024_SecretKey_XYZ`
3. View all users, auctions, fraud flags
4. Ban a user or override an auction status

---

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

## ⚙️ ARCHITECTURE NOTES

- **MVC Pattern:** Models handle DB, Controllers handle logic, Views handle display
- **PDO Singleton:** Single DB connection shared across all models
- **Anti-Fraud:** Rule-based — not ML — for demo reliability
- **Real-time:** AJAX polling every 5 seconds (no WebSockets needed for demo)
- **Server Time:** All auction times use server timezone (Asia/Kolkata)
- **Admin Isolation:** Admin panel is completely separate from user sessions

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

---

*Built for academic demonstration — BidMaster Auction Platform*
