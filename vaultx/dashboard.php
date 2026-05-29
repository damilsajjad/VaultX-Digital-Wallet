<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require 'db.php';

$user_id = $_SESSION['user_id'];

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Session timeout: 30 min
if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > 1800) {
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}
$_SESSION['last_active'] = time();

$msg_type = '';
$msg_text = '';
$txn_id_for_receipt = null;

// Handle send money
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    $receiver_acc = trim($_POST['receiver_acc'] ?? '');
    $amount       = floatval($_POST['amount'] ?? 0);
    $note         = trim($_POST['note'] ?? '');

    if (!$receiver_acc || $amount <= 0) {
        $msg_type = 'error';
        $msg_text = 'Please enter a valid account number and amount.';
    } else {
        $stmt = $pdo->prepare("CALL sp_send_money(?, ?, ?, ?, @status, @message, @txn_id)");
        $stmt->execute([$user_id, $receiver_acc, $amount, $note]);
        $stmt->closeCursor();

        $res = $pdo->query("SELECT @status AS s, @message AS m, @txn_id AS t")->fetch();
        $msg_type = $res['s'];
        $msg_text = $res['m'];
        if ($res['s'] === 'success') {
            $txn_id_for_receipt = $res['t'];
        }

        // Refresh user balance
        $stmt2 = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt2->execute([$user_id]);
        $user = $stmt2->fetch();
    }
}

// Fetch transaction history from view
$hist = $pdo->prepare("
    SELECT * FROM vw_transaction_history
    WHERE sender_account = ? OR receiver_account = ?
    ORDER BY created_at DESC LIMIT 20
");
$hist->execute([$user['account_no'], $user['account_no']]);
$transactions = $hist->fetchAll();

// Low balance threshold
$low_balance = $user['balance'] < 500;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Dashboard — VaultX</title>
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

/* NAV */
nav{position:sticky;top:0;z-index:100;background:var(--bg)ee;backdrop-filter:blur(10px);border-bottom:1px solid var(--border);padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between;}
.logo{font-family:'Syne',sans-serif;font-weight:800;font-size:1.2rem;color:var(--gold);}
.logo span{color:var(--text)}
.nav-right{display:flex;align-items:center;gap:1rem}
.greeting{font-size:.875rem;color:var(--muted)}
.greeting strong{color:var(--text)}
.btn-sm{padding:.4rem 1rem;background:transparent;border:1px solid var(--border);border-radius:6px;color:var(--muted);font-size:.8rem;cursor:pointer;text-decoration:none;transition:all .2s;font-family:'DM Sans',sans-serif;}
.btn-sm:hover{border-color:var(--danger)55;color:var(--danger)}

/* LAYOUT */
.container{max-width:1100px;margin:0 auto;padding:2rem;position:relative;z-index:1;}
.grid-top{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;}
@media(max-width:768px){.grid-top{grid-template-columns:1fr}}

/* CARDS */
.card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:1.75rem;}
.card-label{font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:.5rem}

/* BALANCE CARD */
.balance-card{
  background:linear-gradient(135deg,#1a1810,#16161f);
  border:1px solid var(--gold)33;
  position:relative;overflow:hidden;
}
.balance-card::before{
  content:'';position:absolute;top:-60px;right:-60px;
  width:200px;height:200px;border-radius:50%;
  background:radial-gradient(circle,var(--gold-dim),transparent 70%);
}
.balance-amount{font-family:'Syne',sans-serif;font-size:2.5rem;font-weight:800;color:var(--gold);margin:.25rem 0;}
.balance-acc{font-family:monospace;font-size:.85rem;color:var(--muted);margin-top:.5rem;}
.balance-acc strong{color:var(--text)}
.low-warn{display:flex;align-items:center;gap:.4rem;background:#fbbf2422;border:1px solid #fbbf2444;color:var(--warn);padding:.5rem .75rem;border-radius:6px;font-size:.8rem;margin-top:.75rem;}

/* PROFILE CARD */
.profile-row{display:flex;align-items:center;gap:.75rem;margin-bottom:1rem}
.avatar{width:44px;height:44px;border-radius:50%;background:var(--gold-dim);border:2px solid var(--gold)44;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:700;font-size:1.1rem;color:var(--gold);}
.profile-name{font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;}
.profile-email{font-size:.8rem;color:var(--muted)}
.info-row{display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border);font-size:.875rem;}
.info-row:last-child{border-bottom:none}
.info-row .label{color:var(--muted)}
.info-row .val{font-weight:500}

/* SEND FORM */
.send-card{margin-bottom:1.25rem;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
@media(max-width:600px){.form-row{grid-template-columns:1fr}}
.form-group{margin-bottom:1rem}
label{display:block;font-size:.75rem;font-weight:500;color:var(--muted);margin-bottom:.4rem;letter-spacing:.06em;text-transform:uppercase}
input,textarea{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:.65rem .9rem;color:var(--text);font-family:'DM Sans',sans-serif;font-size:.9rem;outline:none;transition:border-color .2s;}
input:focus,textarea:focus{border-color:var(--gold)66}
input::placeholder,textarea::placeholder{color:var(--muted)}
textarea{resize:vertical;min-height:60px}
.btn-send{padding:.7rem 1.75rem;background:var(--gold);color:#09090f;font-family:'Syne',sans-serif;font-size:.95rem;font-weight:700;border:none;border-radius:8px;cursor:pointer;transition:all .2s;}
.btn-send:hover{background:var(--gold-light);transform:translateY(-1px)}

/* ALERTS */
.alert{padding:.75rem 1rem;border-radius:8px;font-size:.875rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem;}
.alert-success{background:#4ade8022;border:1px solid #4ade8044;color:var(--success)}
.alert-error{background:#f8717122;border:1px solid #f8717144;color:var(--danger)}

/* RECEIPT BUTTON */
.btn-receipt{padding:.4rem .9rem;background:transparent;border:1px solid var(--gold)44;color:var(--gold);border-radius:6px;font-size:.78rem;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .2s;text-decoration:none;}
.btn-receipt:hover{background:var(--gold-dim);border-color:var(--gold)}

/* TABLE */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.875rem;}
th{padding:.6rem .75rem;text-align:left;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border);}
td{padding:.7rem .75rem;border-bottom:1px solid var(--border);}
tr:last-child td{border-bottom:none}
tr:hover td{background:var(--surface)55}
.badge{display:inline-flex;align-items:center;padding:.2rem .6rem;border-radius:4px;font-size:.72rem;font-weight:600;letter-spacing:.04em;}
.badge-success{background:#4ade8022;color:var(--success)}
.badge-failed{background:#f8717122;color:var(--danger)}
.badge-rolled_back{background:#fbbf2422;color:var(--warn)}
.txn-type{font-size:.75rem;padding:.15rem .5rem;border-radius:3px;}
.sent{background:#f8717115;color:#f87171}
.received{background:#4ade8015;color:#4ade80}
</style>
</head>
<body>

<nav>
  <div class="logo">Vault<span>X</span></div>
  <div class="nav-right">
    <span class="greeting">Welcome back, <strong><?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?> 👋</strong></span>
    <a href="logout.php" class="btn-sm">Sign Out</a>
  </div>
</nav>

<div class="container">

  <?php if ($msg_text): ?>
    <div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>">
      <?= $msg_type === 'success' ? '✓' : '✗' ?>
      <?= htmlspecialchars($msg_text) ?>
      <?php if ($txn_id_for_receipt): ?>
        &nbsp;&nbsp;<a href="receipt.php?txn=<?= $txn_id_for_receipt ?>" class="btn-receipt" target="_blank">Download Receipt ↓</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- TOP GRID -->
  <div class="grid-top">

    <!-- BALANCE -->
    <div class="card balance-card">
      <div class="card-label">Wallet Balance</div>
      <div class="balance-amount">PKR <?= number_format($user['balance'], 2) ?></div>
      <div class="balance-acc">Account No: <strong><?= htmlspecialchars($user['account_no']) ?></strong></div>
      <?php if ($low_balance): ?>
        <div class="low-warn">⚠ Low balance. Visit admin to top up your wallet.</div>
      <?php endif; ?>
    </div>

    <!-- PROFILE -->
    <div class="card">
      <div class="card-label">Your Profile</div>
      <div class="profile-row">
        <div class="avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
        <div>
          <div class="profile-name"><?= htmlspecialchars($user['full_name']) ?></div>
          <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
        </div>
      </div>
      <div class="info-row"><span class="label">Account No</span><span class="val" style="font-family:monospace;color:var(--gold)"><?= htmlspecialchars($user['account_no']) ?></span></div>
      <div class="info-row"><span class="label">Member Since</span><span class="val"><?= date('M d, Y', strtotime($user['created_at'])) ?></span></div>
      <div class="info-row"><span class="label">Balance</span><span class="val" style="color:var(--success)">PKR <?= number_format($user['balance'], 2) ?></span></div>
    </div>
  </div>

  <!-- SEND MONEY -->
  <div class="card send-card">
    <div class="card-label" style="margin-bottom:1.25rem">Send Money</div>
    <form method="POST">
      <input type="hidden" name="action" value="send">
      <div class="form-row">
        <div class="form-group">
          <label>Receiver Account No</label>
          <input type="text" name="receiver_acc" placeholder="VLT-XXXXXX" maxlength="15" required>
        </div>
        <div class="form-group">
          <label>Amount (PKR) — Max 10,000</label>
          <input type="number" name="amount" placeholder="0.00" min="1" max="10000" step="0.01" required>
        </div>
      </div>
      <div class="form-group">
        <label>Note (Optional)</label>
        <input type="text" name="note" placeholder="e.g. Rent payment, lunch split...">
      </div>
      <button type="submit" class="btn-send">Send Money →</button>
    </form>
  </div>

  <!-- TRANSACTION HISTORY -->
  <div class="card">
    <div class="card-label" style="margin-bottom:1.25rem">Transaction History</div>
    <?php if (empty($transactions)): ?>
      <p style="color:var(--muted);font-size:.875rem;text-align:center;padding:2rem 0">No transactions yet. Send money to get started.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Type</th>
            <th>Amount</th>
            <th>From / To</th>
            <th>Note</th>
            <th>Status</th>
            <th>Date</th>
            <th>Receipt</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $t):
            $is_sent = ($t['sender_account'] === $user['account_no']);
            $counterpart = $is_sent
              ? ($t['receiver_name'] ?? 'Unknown') . ' (' . ($t['receiver_account'] ?? '—') . ')'
              : ($t['sender_name'] ?? 'Unknown') . ' (' . ($t['sender_account'] ?? '—') . ')';
          ?>
          <tr>
            <td style="color:var(--muted)"><?= $t['id'] ?></td>
            <td><span class="txn-type <?= $is_sent ? 'sent' : 'received' ?>"><?= $is_sent ? '↑ Sent' : '↓ Received' ?></span></td>
            <td style="font-weight:600;color:<?= $is_sent ? 'var(--danger)' : 'var(--success)' ?>">
              <?= $is_sent ? '-' : '+' ?>PKR <?= number_format($t['amount'], 2) ?>
            </td>
            <td style="font-size:.82rem"><?= htmlspecialchars($counterpart) ?></td>
            <td style="color:var(--muted);font-size:.82rem"><?= htmlspecialchars($t['note'] ?: '—') ?></td>
            <td><span class="badge badge-<?= $t['status'] ?>"><?= strtoupper(str_replace('_', ' ', $t['status'])) ?></span></td>
            <td style="color:var(--muted);font-size:.82rem"><?= date('M d, Y H:i', strtotime($t['created_at'])) ?></td>
            <td>
              <?php if ($t['status'] === 'success'): ?>
                <a href="receipt.php?txn=<?= $t['id'] ?>" class="btn-receipt" target="_blank">PDF</a>
              <?php else: ?>
                <span style="color:var(--muted);font-size:.78rem">—</span>
              <?php endif; ?>
            </td>
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
