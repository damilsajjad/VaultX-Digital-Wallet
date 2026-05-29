# VaultX-Digital-Wallet
# VaultX — Digital Wallet System

A full-stack digital wallet web application built with PHP, MySQL, and HTML/CSS.
Developed as an Advanced Database Management course project to demonstrate
real-world implementation of core database concepts.

---

## 🚀 Features

**Customer**
- Sign up & sign in with secure authentication
- Auto-generated unique wallet ID (VLT-XXXXXX)
- View real-time wallet balance
- Send money to other users via account number
- Download PDF receipt for every transaction
- View full transaction history (sent, received, failed, rolled back)
- Low balance warning on dashboard

**Admin**
- Separate admin login panel
- View all registered users and balances
- Search users by name, email, or account number
- Manually top-up a user's balance
- Rollback the latest transaction of any user
- Delete users from the system
- Full admin activity log

---

## 🗄️ Database Concepts Implemented

| Concept | Implementation |
|---|---|
| **Stored Procedures** | `sp_send_money`, `sp_topup_balance`, `sp_rollback_last_transaction` |
| **Transactions** | `START TRANSACTION` / `COMMIT` on every fund transfer |
| **Rollback** | Admin-triggered reversal restoring both sender & receiver balances |
| **Triggers** | `trg_after_txn_insert` (auto-log), `trg_before_balance_update` (prevent negative balance) |
| **Try-Catch** | `DECLARE EXIT HANDLER FOR SQLEXCEPTION` inside all procedures |
| **User Defined Functions** | `fn_has_sufficient_balance()`, `fn_within_limit()`, `fn_generate_account_no()` |
| **Views** | `vw_transaction_history`, `vw_user_balances` |
| **Delete** | Admin deletes users with FK cascade handling |

---

## 🛠️ Tech Stack

- **Frontend** — HTML5, CSS3, Vanilla JS, Google Fonts
- **Backend** — PHP 8.2 with PDO
- **Database** — MySQL / MariaDB
- **PDF Generation** — FPDF v1.86
- **Local Server** — XAMPP (Apache + MySQL)

---

## 📁 Project Structure



vaultx/
├── index.php          # Hero landing page
├── signup.php         # User registration
├── login.php          # User login
├── logout.php         # Session destroy
├── dashboard.php      # Wallet dashboard (balance, send money, history)
├── receipt.php        # PDF receipt generator
├── db.php             # PDO database connection
├── wallet.sql         # Full database schema
├── admin/
│   ├── login.php      # Admin login
│   ├── dashboard.php  # Admin control panel
│   └── logout.php
└── lib/
    └── fpdf.php       # FPDF library (download separately)
______

## ⚙️ Setup Instructions

### Prerequisites
- XAMPP installed (Apache + MySQL)
- PHP 8.x
- FPDF library

### Steps

**1. Clone the repository**
```bash
git clone https://github.com/yourusername/vaultx.git
```

**2. Move to htdocs**

```
C:\xampp\htdocs\vaultx
```

**3. Start XAMPP**
- Open XAMPP Control Panel
- Start **Apache** and **MySQL**

**4. Import the database**
- Open `http://localhost/phpmyadmin`
- Create a new database named `vaultx`
- Click **Import** → select `wallet.sql` → click Go

**5. Add FPDF**
- Download from [fpdf.org](http://www.fpdf.org) (v1.86 ZIP)
- Place `fpdf.php` and the `font/` folder inside `vaultx/lib/`

**6. Run the project**
```
http://localhost/vaultx
```
---

## 🔐 Default Credentials

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `admin123` |
| User | Sign up via the app | Your chosen password |

> Admin panel: `http://localhost/vaultx/admin/login.php`
---

## 📌 Notes

- User balances can only be topped up by the admin (cash-based system)
- Transactions to invalid account numbers are automatically rolled back
- Maximum transaction limit: PKR 10,000 per transfer
- PDF receipts are only generated for successful transactions
- Session timeout: 30 minutes of inactivity
---

## 👤 Author

**Damil Sajjad**
Advanced Database Management Project
