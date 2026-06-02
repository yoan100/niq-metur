<?php
require __DIR__ . '/db.php';
session_start();

// Изход
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

// Вход с ПИН
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin'])) {
    if (hash_equals(PIN_CODE, (string)$_POST['pin'])) {
        $_SESSION['niauth'] = true;
        header('Location: index.php'); // PRG срещу повторно изпращане
        exit;
    }
    $error = 'Грешен ПИН код. Опитай пак.';
}

$authed = !empty($_SESSION['niauth']);
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#8ecae6">
<title>НияМетър</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;600;700&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🗺️</text></svg>">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= $authed ? 'is-app' : 'is-login' ?>">

<?php if (!$authed): ?>
<!-- ================= ЕКРАН ЗА ВХОД ================= -->
<main class="login">
  <div class="login-card">
    <div class="lock">🔒</div>
    <h1 class="brand">НияМетър</h1>
    <p class="tagline">Картата на добрите дела</p>

    <form method="post" id="pinForm" autocomplete="off">
      <input type="hidden" name="pin" id="pin">
      <div class="dots" id="dots">
        <span></span><span></span><span></span><span></span>
      </div>
      <?php if ($error): ?>
        <p class="err"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
      <div class="keypad" id="keypad">
        <?php foreach ([1,2,3,4,5,6,7,8,9] as $n): ?>
          <button type="button" data-k="<?= $n ?>"><?= $n ?></button>
        <?php endforeach; ?>
        <button type="button" class="ghost" data-k="del">⌫</button>
        <button type="button" data-k="0">0</button>
        <button type="button" class="ghost" data-k="ok">✓</button>
      </div>
      <p class="hint">Въведи ПИН код, за да отбелязваш постъпки</p>
    </form>
  </div>
</main>
<script>
(function () {
  const pin = document.getElementById('pin');
  const dots = document.getElementById('dots').children;
  const form = document.getElementById('pinForm');
  let buf = '';
  function paint() {
    for (let i = 0; i < 4; i++) dots[i].classList.toggle('on', i < buf.length);
  }
  function submitIf() { if (buf.length === 4) { pin.value = buf; form.submit(); } }
  document.getElementById('keypad').addEventListener('click', e => {
    const k = e.target.closest('button'); if (!k) return;
    const v = k.dataset.k;
    if (v === 'del') buf = buf.slice(0, -1);
    else if (v === 'ok') return submitIf();
    else if (buf.length < 4) buf += v;
    paint();
    if (buf.length === 4) setTimeout(submitIf, 120);
  });
  window.addEventListener('keydown', e => {
    if (/^[0-9]$/.test(e.key) && buf.length < 4) { buf += e.key; paint(); if (buf.length===4) setTimeout(submitIf,120); }
    else if (e.key === 'Backspace') { buf = buf.slice(0, -1); paint(); }
    else if (e.key === 'Enter') submitIf();
  });
})();
</script>

<?php else: ?>
<!-- ================= ОСНОВЕН ЕКРАН ================= -->
<header class="topbar">
  <div class="logo">🗺️ <span>НияМетър</span></div>
  <a class="logout" href="?logout=1">Изход</a>
</header>

<main id="app" class="app">
  <div class="loading">Зареждане…</div>
</main>

<!-- Шаблон: модал за постъпка -->
<div class="modal-root" id="modalRoot" hidden></div>

<script src="assets/app.js"></script>
<?php endif; ?>

</body>
</html>
