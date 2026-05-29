<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
require 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_acc']  = $user['account_no'];
            // Update last seen (session-based)
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Sign In — VaultX</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#09090f;--surface:#111118;--card:#16161f;--border:#ffffff0f;
  --gold:#c8a96e;--gold-light:#e2c98a;--gold-dim:#c8a96e22;
  --text:#f0ece4;--muted:#7a7a8c;--danger:#f87171;--success:#4ade80;
}
body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem;}
body::before{content:'';position:fixed;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");pointer-events:none;z-index:0;opacity:.4;}
.orb{position:fixed;border-radius:50%;filter:blur(100px);pointer-events:none;z-index:0;}
.orb-1{width:500px;height:500px;background:radial-gradient(circle,#c8a96e18,transparent 70%);top:-50px;left:50%;transform:translateX(-50%);}
.card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:2.5rem;width:100%;max-width:400px;position:relative;z-index:1;animation:fadeUp .5s ease both;}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.back{display:inline-flex;align-items:center;gap:.4rem;color:var(--muted);font-size:.85rem;text-decoration:none;margin-bottom:1.5rem;position:relative;z-index:1;transition:color .2s;}
.back:hover{color:var(--gold)}
.logo{font-family:'Syne',sans-serif;font-weight:800;font-size:1.3rem;color:var(--gold);margin-bottom:1.5rem;}
.logo span{color:var(--text)}
h1{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:700;margin-bottom:.35rem}
.sub{color:var(--muted);font-size:.875rem;margin-bottom:2rem}
.form-group{margin-bottom:1.2rem}
label{display:block;font-size:.8rem;font-weight:500;color:var(--muted);margin-bottom:.4rem;letter-spacing:.04em;text-transform:uppercase}
input{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:.7rem 1rem;color:var(--text);font-family:'DM Sans',sans-serif;font-size:.95rem;outline:none;transition:border-color .2s;}
input:focus{border-color:var(--gold)66}
input::placeholder{color:var(--muted)}
.btn{width:100%;padding:.75rem;background:var(--gold);color:#09090f;font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;border:none;border-radius:10px;cursor:pointer;transition:all .2s;margin-top:.5rem;}
.btn:hover{background:var(--gold-light);transform:translateY(-1px)}
.alert{padding:.75rem 1rem;border-radius:8px;font-size:.875rem;margin-bottom:1.25rem}
.alert-error{background:#f8717122;border:1px solid #f8717144;color:var(--danger)}
.divider{text-align:center;color:var(--muted);font-size:.85rem;margin:1.25rem 0}
.link{color:var(--gold);text-decoration:none;font-weight:500}
.link:hover{color:var(--gold-light)}
.admin-link{display:block;text-align:center;margin-top:1rem;font-size:.8rem;color:var(--muted)}
.admin-link a{color:var(--muted);text-decoration:none;border-bottom:1px dashed var(--muted);}
.admin-link a:hover{color:var(--gold)}
</style>
</head>
<body>
<div class="orb orb-1"></div>
<a href="index.php" class="back">← Back to Home</a>
<div class="card">
  <div class="logo">Vault<span>X</span></div>
  <h1>Welcome Back</h1>
  <p class="sub">Sign in to access your wallet dashboard.</p>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="ali@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Your password" required>
    </div>
    <button type="submit" class="btn">Sign In</button>
  </form>

  <div class="divider">Don't have an account? <a href="signup.php" class="link">Sign up</a></div>
  <div class="admin-link"><a href="admin/login.php">Admin Panel →</a></div>
</div>
</body>
</html>
