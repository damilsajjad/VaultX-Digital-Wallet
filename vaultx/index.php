<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>VaultX by Damil Sajjad — Digital Wallet</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#09090f;
  --surface:#111118;
  --card:#16161f;
  --border:#ffffff0f;
  --gold:#c8a96e;
  --gold-light:#e2c98a;
  --gold-dim:#c8a96e30;
  --text:#f0ece4;
  --muted:#7a7a8c;
  --success:#4ade80;
  --danger:#f87171;
}
html{scroll-behavior:smooth}
body{
  background:var(--bg);
  color:var(--text);
  font-family:'DM Sans',sans-serif;
  min-height:100vh;
  overflow-x:hidden;
}

/* ── NOISE TEXTURE ── */
body::before{
  content:'';
  position:fixed;inset:0;
  background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events:none;z-index:0;opacity:.4;
}

/* ── NAV ── */
nav{
  position:fixed;top:0;left:0;right:0;
  display:flex;align-items:center;justify-content:space-between;
  padding:1.25rem 3rem;
  background:linear-gradient(to bottom,#09090fdd,transparent);
  z-index:100;
}
.logo{
  font-family:'Syne',sans-serif;
  font-weight:800;font-size:1.4rem;
  color:var(--gold);letter-spacing:.04em;
}
.logo span{color:var(--text)}
.nav-actions{display:flex;gap:.75rem}
.btn{
  display:inline-flex;align-items:center;justify-content:center;
  padding:.55rem 1.4rem;border-radius:8px;
  font-family:'DM Sans',sans-serif;font-size:.875rem;font-weight:500;
  cursor:pointer;transition:all .2s ease;text-decoration:none;border:none;
}
.btn-ghost{
  background:transparent;
  border:1px solid var(--border);
  color:var(--text);
}
.btn-ghost:hover{border-color:var(--gold);color:var(--gold)}
.btn-gold{
  background:var(--gold);color:#09090f;font-weight:600;
}
.btn-gold:hover{background:var(--gold-light);transform:translateY(-1px)}

/* ── HERO ── */
.hero{
  position:relative;
  min-height:100vh;
  display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  text-align:center;
  padding:8rem 2rem 4rem;
  overflow:hidden;
}

/* Glowing orbs */
.orb{
  position:absolute;border-radius:50%;
  filter:blur(80px);pointer-events:none;
}
.orb-1{
  width:600px;height:600px;
  background:radial-gradient(circle,#c8a96e22,transparent 70%);
  top:-100px;left:50%;transform:translateX(-50%);
  animation:float 8s ease-in-out infinite;
}
.orb-2{
  width:400px;height:400px;
  background:radial-gradient(circle,#6e8ec822,transparent 70%);
  bottom:10%;left:10%;
  animation:float 10s ease-in-out infinite reverse;
}
.orb-3{
  width:300px;height:300px;
  background:radial-gradient(circle,#c86e9322,transparent 70%);
  bottom:20%;right:10%;
  animation:float 12s ease-in-out infinite;
}

@keyframes float{
  0%,100%{transform:translateY(0) translateX(-50%)}
  50%{transform:translateY(-30px) translateX(-50%)}
}
.orb-2{animation:float2 10s ease-in-out infinite reverse}
.orb-3{animation:float3 12s ease-in-out infinite}
@keyframes float2{0%,100%{transform:translateY(0)}50%{transform:translateY(-20px)}}
@keyframes float3{0%,100%{transform:translateY(0)}50%{transform:translateY(20px)}}

.hero-badge{
  display:inline-flex;align-items:center;gap:.5rem;
  background:var(--gold-dim);border:1px solid var(--gold)44;
  color:var(--gold);font-size:.75rem;font-weight:500;letter-spacing:.08em;text-transform:uppercase;
  padding:.35rem 1rem;border-radius:999px;margin-bottom:2rem;
  animation:fadeUp .6s ease both;
}
.hero-badge::before{content:'●';font-size:.5rem;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}

.hero-title{
  font-family:'Syne',sans-serif;
  font-size:clamp(3rem,8vw,6.5rem);
  font-weight:800;line-height:1;
  letter-spacing:-.02em;
  animation:fadeUp .7s .1s ease both;
}
.hero-title .accent{
  background:linear-gradient(135deg,var(--gold),var(--gold-light),#fff8e7);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
  background-clip:text;
}
.hero-sub{
  max-width:520px;
  font-size:1.1rem;color:var(--muted);line-height:1.7;
  margin:1.5rem auto 2.5rem;
  animation:fadeUp .7s .2s ease both;
}
.hero-actions{
  display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;
  animation:fadeUp .7s .3s ease both;
}
.btn-lg{padding:.75rem 2rem;font-size:1rem;border-radius:10px}

/* account number preview */
.account-preview{
  display:inline-flex;align-items:center;gap:.5rem;
  background:var(--card);border:1px solid var(--border);
  border-radius:8px;padding:.5rem 1rem;
  font-family:monospace;font-size:.85rem;color:var(--gold);
  margin-top:2rem;
  animation:fadeUp .7s .4s ease both;
}
.account-preview span{color:var(--muted);font-family:'DM Sans',sans-serif;font-size:.75rem}

@keyframes fadeUp{
  from{opacity:0;transform:translateY(20px)}
  to{opacity:1;transform:translateY(0)}
}

/* ── FEATURES ── */
.features{
  padding:5rem 2rem;
  max-width:1100px;margin:0 auto;
  position:relative;z-index:1;
}
.section-label{
  text-align:center;
  font-size:.75rem;letter-spacing:.12em;text-transform:uppercase;
  color:var(--gold);margin-bottom:.75rem;
}
.section-title{
  text-align:center;
  font-family:'Syne',sans-serif;font-size:2.2rem;font-weight:700;
  margin-bottom:3.5rem;
}
.features-grid{
  display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
  gap:1.25rem;
}
.feature-card{
  background:var(--card);border:1px solid var(--border);
  border-radius:16px;padding:1.75rem;
  transition:border-color .2s,transform .2s;
  position:relative;overflow:hidden;
}
.feature-card::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,var(--gold-dim),transparent);
  opacity:0;transition:opacity .3s;
}
.feature-card:hover{border-color:var(--gold)55;transform:translateY(-4px)}
.feature-card:hover::before{opacity:1}
.feature-icon{
  width:44px;height:44px;border-radius:10px;
  background:var(--gold-dim);border:1px solid var(--gold)33;
  display:flex;align-items:center;justify-content:center;
  font-size:1.3rem;margin-bottom:1.1rem;position:relative;z-index:1;
}
.feature-card h3{
  font-family:'Syne',sans-serif;font-size:1.05rem;font-weight:700;
  margin-bottom:.5rem;position:relative;z-index:1;
}
.feature-card p{
  font-size:.875rem;color:var(--muted);line-height:1.65;
  position:relative;z-index:1;
}

/* ── HOW IT WORKS ── */
.how{
  padding:5rem 2rem;
  background:var(--surface);
  border-top:1px solid var(--border);
  border-bottom:1px solid var(--border);
}
.how-inner{max-width:900px;margin:0 auto}
.steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:2rem;margin-top:3rem}
.step{text-align:center}
.step-num{
  font-family:'Syne',sans-serif;font-size:3rem;font-weight:800;
  color:var(--gold-dim);line-height:1;margin-bottom:.75rem;
  background:linear-gradient(135deg,var(--gold)44,transparent);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.step h4{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin-bottom:.4rem}
.step p{font-size:.85rem;color:var(--muted);line-height:1.6}

/* ── STATS ── */
.stats{
  padding:4rem 2rem;
  max-width:900px;margin:0 auto;
  display:grid;grid-template-columns:repeat(3,1fr);
  gap:2rem;text-align:center;
  position:relative;z-index:1;
}
.stat-val{
  font-family:'Syne',sans-serif;font-size:2.5rem;font-weight:800;
  color:var(--gold);
}
.stat-label{font-size:.85rem;color:var(--muted);margin-top:.25rem}

/* ── FOOTER ── */
footer{
  border-top:1px solid var(--border);
  padding:2rem 3rem;
  display:flex;align-items:center;justify-content:space-between;
  position:relative;z-index:1;
}
footer p{font-size:.8rem;color:var(--muted)}

/* ── SCROLLBAR ── */
::-webkit-scrollbar{width:4px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:var(--gold)44;border-radius:2px}
</style>
</head>
<body>

<nav>
  <div class="logo">Vault<span>X</span></div>
  <div class="nav-actions">
    <a href="login.php" class="btn btn-ghost">Sign In</a>
    <a href="signup.php" class="btn btn-gold">Get Started</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="orb orb-3"></div>

  <div class="hero-badge">● Secure Digital Wallet</div>

  <h1 class="hero-title">
    Your Money.<br>
    <span class="accent">Your Vault.</span>
  </h1>

  <p class="hero-sub">
    VaultX is a fast, transparent digital wallet. Send money instantly,
    track every transaction, and stay in control — all in one place.
  </p>

  <div class="hero-actions">
    <a href="signup.php" class="btn btn-gold btn-lg">Create Free Account</a>
    <a href="login.php" class="btn btn-ghost btn-lg">Sign In</a>
  </div>

  <div class="account-preview">
    <span>Your account ID:</span> VLT-XXXXXX
  </div>
</section>

<!-- STATS -->
<div class="stats">
  <div>
    <div class="stat-val">10K+</div>
    <div class="stat-label">Transactions Processed</div>
  </div>
  <div>
    <div class="stat-val">100%</div>
    <div class="stat-label">Secured & Transparent</div>
  </div>
  <div>
    <div class="stat-val">0 Fees</div>
    <div class="stat-label">On Every Transfer</div>
  </div>
</div>

<!-- FEATURES -->
<section class="features">
  <div class="section-label">What We Offer</div>
  <h2 class="section-title">Built for simplicity.<br>Designed for trust.</h2>
  <div class="features-grid">
    <div class="feature-card">
      <div class="feature-icon">⚡</div>
      <h3>Instant Transfers</h3>
      <p>Send money to any VaultX account in seconds using their unique account number. Real-time balance updates, zero delays.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🔐</div>
      <h3>Auto Account Number</h3>
      <p>Every user receives a unique VLT-XXXXXX identifier on signup. No manual setup. Your identity is protected and verified.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🧾</div>
      <h3>PDF Receipts</h3>
      <p>Download a detailed PDF receipt for every successful transaction — perfect for record keeping and proof of payment.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">📊</div>
      <h3>Full History</h3>
      <p>View all your past transactions on your dashboard — sent, received, failed, and rolled back — with dates and notes.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🛡️</div>
      <h3>Admin Oversight</h3>
      <p>A trusted admin manages top-ups and can roll back the latest transaction if something goes wrong. Full transparency.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🚫</div>
      <h3>Smart Validation</h3>
      <p>Sending to an invalid account? VaultX auto-rolls back the transaction and notifies you instantly. No money lost.</p>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="how">
  <div class="how-inner">
    <div class="section-label">How It Works</div>
    <h2 class="section-title">Three steps to get started</h2>
    <div class="steps">
      <div class="step">
        <div class="step-num">01</div>
        <h4>Create Account</h4>
        <p>Sign up with your name and email. Get your unique VLT account number instantly.</p>
      </div>
      <div class="step">
        <div class="step-num">02</div>
        <h4>Load Balance</h4>
        <p>Visit the admin with cash. They'll top up your wallet manually for full transparency.</p>
      </div>
      <div class="step">
        <div class="step-num">03</div>
        <h4>Send & Track</h4>
        <p>Transfer funds using account numbers. Download receipts. View your full history.</p>
      </div>
    </div>
  </div>
</section>

<footer>
  <div class="logo">Vault<span>X</span></div>
  <p>© 2026 VaultX. All rights reserved. (Advanced Database Management Project by Damil Sajjad)</p>
</footer>

</body>
</html>
