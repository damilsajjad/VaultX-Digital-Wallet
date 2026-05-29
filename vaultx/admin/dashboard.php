<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }
require '../db.php';

$admin_user = $_SESSION['admin_user'];
$msg_type   = '';
$msg_text   = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // TOP UP
    if ($action === 'topup') {
        $acc    = trim($_POST['account_no'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        if (!$acc || $amount <= 0) {
            $msg_type = 'error'; $msg_text = 'Enter a valid account number and amount.';
        } else {
            $stmt = $pdo->prepare("CALL sp_topup_balance(?, ?, ?, @s, @m)");
            $stmt->execute([$acc, $amount, $admin_user]);
            $stmt->closeCursor();
            $res = $pdo->query("SELECT @s AS s, @m AS m")->fetch();
            $msg_type = $res['s']; $msg_text = $res['m'];
        }
    }

    // ROLLBACK
    if ($action === 'rollback') {
        $uid = intval($_POST['user_id'] ?? 0);
        if (!$uid) {
            $msg_type = 'error'; $msg_text = 'Invalid user.';
        } else {
            $stmt = $pdo->prepare("CALL sp_rollback_last_transaction(?, ?, @s, @m)");
            $stmt->execute([$uid, $admin_user]);
            $stmt->closeCursor();
            $res = $pdo->query("SELECT @s AS s, @m AS m")->fetch();
            $msg_type = $res['s']; $msg_text = $res['m'];
        }
    }

    // DELETE USER
    if ($action === 'delete_user') {
        $uid = intval($_POST['user_id'] ?? 0);
        if ($uid) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$uid]);
            $msg_type = 'success'; $msg_text = 'User deleted successfully.';
        }
    }
}

// Fetch all users
$users = $pdo->query("SELECT * FROM vw_user_balances ORDER BY created_at DESC")->fetchAll();

// Fetch admin activity log
$activity = $pdo->query("SELECT aa.*, u.full_name, u.account_no FROM admin_activity aa LEFT JOIN users u ON aa.target_user = u.id ORDER BY aa.created_at DESC LIMIT 20")->fetchAll();

// Search
$search_result = null;
$search_query  = '';
if (!empty($_GET['search'])) {
    $search_query = trim($_GET['search']);
    $ss = $pdo->prepare("SELECT * FROM users WHERE account_no = ? OR email LIKE ? OR full_name LIKE ?");
    $ss->execute([$search_query, "%$search_query%", "%$search_query%"]);
    $search_result = $ss->fetchAll();
}

// Stats
$total_users = count($users);
$total_bal   = array_sum(array_column($users, 'balance'));
$total_txns  = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Dashboard — VaultX</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#09090f;--surface:#111118;--card:#16161f;--border:#ffffff0f;
  --gold:#c8a96e;--gold-light:#e2c98a;--gold-dim:#c8a96e22;
  --text:#f0ece4;--muted:#7a7a8c;--danger:#f87171;--success:#4ade80;--warn:#fbbf24;
}
body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;}
body::before{content:'';position:fixed;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");pointer-events:none;z-index:0;opacity:.4;}

nav{position:sticky;top:0;z-index:100;background:var(--bg)ee;backdrop-filter:blur(10px);border-bottom:1px solid var(--border);padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between;}
.logo{font-family:'Syne',sans-serif;font-weight:800;font-size:1.2rem;color:var(--gold);}
.logo span{color:var(--text)}
.admin-badge{background:var(--gold-dim);border:1px solid var(--gold)33;color:var(--gold);font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;padding:.2rem .6rem;border-radius:4px;margin-left:.5rem;}
.nav-right{display:flex;align-items:center;gap:1rem}
.nav-user{font-size:.85rem;color:var(--muted)}
.btn-out{padding:.4rem 1rem;background:transparent;border:1px solid var(--border);border-radius:6px;color:var(--muted);font-size:.8rem;cursor:pointer;text-decoration:none;transition:all .2s;font-family:'DM Sans',sans-serif;}
.btn-out:hover{border-color:var(--danger)55;color:var(--danger)}

.container{max-width:1200px;margin:0 auto;padding:2rem;position:relative;z-index:1;}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem;}
@media(max-width:700px){.stats-grid{grid-template-columns:1fr}}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.25rem;}
.stat-label{font-size:.72rem;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:.4rem}
.stat-val{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;color:var(--gold)}

/* ACTION CARDS */
.actions-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.5rem;}
@media(max-width:700px){.actions-grid{grid-template-columns:1fr}}
.card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:1.5rem;}
.card-title{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin-bottom:1.1rem;display:flex;align-items:center;gap:.5rem;}
.card-title .icon{width:30px;height:30px;border-radius:7px;background:var(--gold-dim);display:flex;align-items:center;justify-content:center;font-size:.9rem;}

.form-group{margin-bottom:.9rem}
label{display:block;font-size:.75rem;font-weight:500;color:var(--muted);margin-bottom:.35rem;letter-spacing:.06em;text-transform:uppercase}
input,select{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:.6rem .9rem;color:var(--text);font-family:'DM Sans',sans-serif;font-size:.9rem;outline:none;transition:border-color .2s;}
input:focus,select:focus{border-color:var(--gold)66}
input::placeholder{color:var(--muted)}
select option{background:var(--surface)}
.btn-action{padding:.6rem 1.4rem;border:none;border-radius:8px;font-family:'Syne',sans-serif;font-size:.9rem;font-weight:700;cursor:pointer;transition:all .2s;}
.btn-gold{background:var(--gold);color:#09090f}
.btn-gold:hover{background:var(--gold-light)}
.btn-warn{background:#fbbf2422;border:1px solid #fbbf2444;color:var(--warn)}
.btn-warn:hover{background:#fbbf2433}

/* ALERT */
.alert{padding:.75rem 1rem;border-radius:8px;font-size:.875rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem;}
.alert-success{background:#4ade8022;border:1px solid #4ade8044;color:var(--success)}
.alert-error{background:#f8717122;border:1px solid #f8717144;color:var(--danger)}

/* SEARCH */
.search-bar{display:flex;gap:.75rem;margin-bottom:1.5rem}
.search-bar input{flex:1}
.btn-search{padding:.6rem 1.25rem;background:var(--gold-dim);border:1px solid var(--gold)33;color:var(--gold);border-radius:8px;font-family:'Syne',sans-serif;font-size:.875rem;font-weight:600;cursor:pointer;}

/* TABLE */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.875rem;}
th{padding:.6rem .75rem;text-align:left;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border);}
td{padding:.7rem .75rem;border-bottom:1px solid var(--border);}
tr:last-child td{border-bottom:none}
tr:hover td{background:var(--surface)55}
.acc-no{font-family:monospace;font-size:.8rem;color:var(--gold)}
.btn-delete{background:#f8717115;border:1px solid #f8717133;color:var(--danger);border-radius:5px;padding:.25rem .6rem;font-size:.75rem;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .2s;}
.btn-delete:hover{background:#f8717130}
.btn-rollback{background:#fbbf2415;border:1px solid #fbbf2433;color:var(--warn);border-radius:5px;padding:.25rem .6rem;font-size:.75rem;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .2s;}
.btn-rollback:hover{background:#fbbf2430}
.section-head{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin:1.75rem 0 1rem;display:flex;align-items:center;gap:.5rem;}
.badge{display:inline-flex;align-items:center;padding:.2rem .55rem;border-radius:4px;font-size:.72rem;font-weight:600;}
.badge-topup{background:#4ade8022;color:var(--success)}
.badge-rollback{background:#fbbf2422;color:var(--warn)}
.badge-delete{background:#f8717122;color:var(--danger)}
</style>
</head>
<body>

<nav>
  <div>
    <span class="logo">Vault<span>X</span></span>
    <span class="admin-badge">Admin</span>
  </div>
  <div class="nav-right">
    <span class="nav-user">👤 <?= htmlspecialchars($admin_user) ?></span>
    <a href="logout.php" class="btn-out">Sign Out</a>
  </div>
</nav>

<div class="container">

  <?php if ($msg_text): ?>
    <div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>">
      <?= $msg_type === 'success' ? '✓' : '✗' ?> <?= htmlspecialchars($msg_text) ?>
    </div>
  <?php endif; ?>

  <!-- STATS -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">Total Users</div>
      <div class="stat-val"><?= $total_users ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Wallet Balance</div>
      <div class="stat-val">PKR <?= number_format($total_bal, 0) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Transactions</div>
      <div class="stat-val"><?= $total_txns ?></div>
    </div>
  </div>

  <!-- ACTIONS -->
  <div class="actions-grid">

    <!-- TOP UP -->
    <div class="card">
      <div class="card-title"><span class="icon">💰</span> Top Up Balance</div>
      <form method="POST">
        <input type="hidden" name="action" value="topup">
        <div class="form-group">
          <label>Account Number</label>
          <input type="text" name="account_no" placeholder="VLT-XXXXXX" required>
        </div>
        <div class="form-group">
          <label>Amount (PKR)</label>
          <input type="number" name="amount" placeholder="0.00" min="1" step="0.01" required>
        </div>
        <button type="submit" class="btn-action btn-gold">Top Up →</button>
      </form>
    </div>

    <!-- ROLLBACK -->
    <div class="card">
      <div class="card-title"><span class="icon">↩</span> Rollback Last Transaction</div>
      <p style="font-size:.85rem;color:var(--muted);margin-bottom:1rem;line-height:1.6">
        Select a user to reverse their most recent successful transaction. This will restore balances for both sender and receiver.
      </p>
      <form method="POST">
        <input type="hidden" name="action" value="rollback">
        <div class="form-group">
          <label>Select User</label>
          <select name="user_id" required>
            <option value="">— Choose a user —</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?> (<?= $u['account_no'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn-action btn-warn" onclick="return confirm('Roll back the last transaction for this user?')">Rollback →</button>
      </form>
    </div>
  </div>

  <!-- SEARCH -->
  <div class="section-head">🔍 Search Users</div>
  <form method="GET" class="search-bar">
    <input type="text" name="search" placeholder="Search by name, email, or account number..." value="<?= htmlspecialchars($search_query) ?>">
    <button type="submit" class="btn-search">Search</button>
  </form>

  <?php if ($search_result !== null): ?>
    <?php if (empty($search_result)): ?>
      <p style="color:var(--muted);font-size:.875rem;margin-bottom:1.5rem">No users found for "<?= htmlspecialchars($search_query) ?>".</p>
    <?php else: ?>
      <div class="card" style="margin-bottom:1.5rem">
        <div class="card-title">Search Results (<?= count($search_result) ?>)</div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Name</th><th>Email</th><th>Account No</th><th>Balance</th></tr></thead>
            <tbody>
              <?php foreach ($search_result as $u): ?>
              <tr>
                <td><?= htmlspecialchars($u['full_name']) ?></td>
                <td style="color:var(--muted)"><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="acc-no"><?= htmlspecialchars($u['account_no']) ?></span></td>
                <td style="color:var(--success)">PKR <?= number_format($u['balance'], 2) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- ALL USERS TABLE -->
  <div class="card" style="margin-bottom:1.5rem">
    <div class="card-title">All Users</div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Name</th><th>Email</th><th>Account No</th><th>Balance</th><th>Joined</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td style="font-weight:500"><?= htmlspecialchars($u['full_name']) ?></td>
            <td style="color:var(--muted);font-size:.82rem"><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="acc-no"><?= htmlspecialchars($u['account_no']) ?></span></td>
            <td style="color:var(--success);font-weight:600">PKR <?= number_format($u['balance'], 2) ?></td>
            <td style="color:var(--muted);font-size:.8rem"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
            <td style="display:flex;gap:.5rem">
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="rollback">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn-rollback" onclick="return confirm('Rollback last txn for <?= addslashes($u['full_name']) ?>?')">↩ Rollback</button>
              </form>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn-delete" onclick="return confirm('Delete <?= addslashes($u['full_name']) ?>? This cannot be undone.')">✕ Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ADMIN ACTIVITY LOG -->
  <div class="card">
    <div class="card-title">Admin Activity Log</div>
    <?php if (empty($activity)): ?>
      <p style="color:var(--muted);font-size:.875rem;text-align:center;padding:1.5rem 0">No admin actions yet.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Action</th><th>Target User</th><th>Amount</th><th>By</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($activity as $a): ?>
          <tr>
            <td>
              <span class="badge badge-<?= strtolower($a['action']) === 'top_up' ? 'topup' : (strtolower($a['action']) === 'rollback' ? 'rollback' : 'delete') ?>">
                <?= htmlspecialchars($a['action']) ?>
              </span>
            </td>
            <td style="font-size:.85rem"><?= htmlspecialchars($a['full_name'] ?? '—') ?> <span style="color:var(--muted)">(<?= $a['account_no'] ?? '—' ?>)</span></td>
            <td style="color:var(--gold)"><?= $a['amount'] ? 'PKR '.number_format($a['amount'],2) : '—' ?></td>
            <td style="color:var(--muted);font-size:.82rem"><?= htmlspecialchars($a['admin_user']) ?></td>
            <td style="color:var(--muted);font-size:.82rem"><?= date('M d, Y H:i', strtotime($a['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>
</body>
</html>
