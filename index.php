<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['logged-in'])) {
  $_SESSION['logged-in'] = false;
}

require_once './src/Database.php';
require_once './src/i18n.php';

$db = Database::getInstance();

$err = '';

// LOGIN
if (isset($_POST['submit'])) {

  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if (strlen($email) < 1) {
    $err = 'Please enter email address';
  } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = 'Please enter a valid email adddress';
  } else if (strlen($password) < 1) {
    $err = "Please enter your password";
  } else if (strlen($password) <= 8) {
    $err = "Password must be longer than 8 characters";
  } else {

    $stmt = $db->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");

    if ($stmt === false) {
      $err = "Unable to process login right now";
    } else {

      $stmt->bind_param('s', $email);

      if (!$stmt->execute()) {
        $err = "Unable to process login right now";
      } else {

        // ✅ NO get_result() — use bind_result + fetch
        $stmt->bind_result($id, $name, $u_email, $u_password, $role);

        if ($stmt->fetch()) {
          if (password_verify($password, $u_password)) {
            $_SESSION['logged-in'] = true;

            // create user object similar to your old code
            $user = new stdClass();
            $user->id = $id;
            $user->name = $name;
            $user->email = $u_email;
            $user->role = $role;

            $_SESSION['user'] = $user;

            $stmt->close();
            header('Location: ./mobile-home.php');
            exit();
          } else {
            $err = "Wrong username or password";
          }
        } else {
          $err = "No user found";
        }
      }

      $stmt->close();
    }
  }
}
?>

<?php
$currentLang = i18n_lang();
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$parsed = parse_url($requestUri);
$path = $parsed['path'] ?? '';
$query = [];
if (!empty($parsed['query'])) {
  parse_str($parsed['query'], $query);
}
$query['lang'] = 'en';
$langUrlEn = $path . '?' . http_build_query($query);
$query['lang'] = 'ne';
$langUrlNe = $path . '?' . http_build_query($query);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLang, ENT_QUOTES, 'UTF-8'); ?>">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Municipal Service Portal - Login</title>
  <link rel="icon" type="image/png" href="img/shuklagandaki_logo.png">

  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="css/sb-admin.css" rel="stylesheet">
  <link href="css/theme-purple.css" rel="stylesheet">
  <link href="css/mobile-home.css" rel="stylesheet">
  <link href="css/mobile-theme.css" rel="stylesheet">
  <script defer src="js/main.js"></script>

  <style>
    :root { --gov-navy:#1f2b4d; --gov-red:#2f6fed; --gov-sky:#f2f4f8; --gov-ink:#1f2b4d; --gov-border:rgba(31,43,77,.18); }
    body.auth-page{min-height:100vh;margin:0;background:linear-gradient(180deg,#f7f8fc 0%,#eef2f9 100%);display:flex;align-items:center;justify-content:center;padding:2.5rem 1.25rem;color:var(--gov-ink);font-family:"Trebuchet MS","Lucida Sans Unicode","Lucida Grande","Lucida Sans",Arial,sans-serif}
    .auth-shell{width:100%;max-width:560px;display:flex;flex-direction:column;gap:1.5rem}
    .auth-card{order:1}
    .brand-panel{order:2;background:#fff;border:1px solid var(--gov-border);border-radius:18px;padding:1.75rem;box-shadow:0 15px 45px rgba(15,23,42,.12);position:relative;overflow:hidden}
    .brand-panel::before{content:"";position:absolute;inset:0;pointer-events:none;background:linear-gradient(135deg,rgba(193,18,31,.08),rgba(11,46,76,.12));opacity:.35}
    .brand-panel>*{position:relative}
    .brand-badge{display:flex;align-items:center;gap:.85rem;margin-bottom:1rem}
    .brand-badge img{width:72px;height:72px;object-fit:contain}
    .brand-kicker{font-size:.9rem;color:var(--gov-ink);text-transform:uppercase;letter-spacing:.08em}
    .brand-name{font-size:1.25rem;font-weight:700;color:var(--gov-navy)}
    .brand-sub{color:var(--gov-red);font-weight:700}
    .lead-copy{font-size:1rem;line-height:1.6;color:#1f2937}
    .trust-bullets{padding-left:1.2rem;margin:1rem 0 0;color:#374151}
    .trust-bullets li+li{margin-top:.35rem}
    .support-block{margin-top:1.2rem;padding:.9rem 1rem;background:var(--gov-sky);border-left:4px solid var(--gov-navy);border-radius:12px;font-size:.95rem;color:#0b2540}
    .auth-card{border:none;border-radius:18px;box-shadow:0 22px 60px rgba(15,23,42,.18);overflow:hidden}
    .auth-card .card-header{background:linear-gradient(120deg,var(--gov-navy),#0f3c64,var(--gov-red));color:#fff;border:none;letter-spacing:.5px}
    .card-header .card-kicker{font-size:.85rem;letter-spacing:.12em;text-transform:uppercase;opacity:.85}
    .card-header .card-title{font-size:1.4rem;font-weight:700;margin:.25rem 0 0}
    .card-header .card-subtitle{font-size:.95rem;opacity:.92}
    .auth-card .card-body{background:#fff;color:#212529;padding:1.75rem}
    .form-control{border-radius:10px;border-color:#d8deea}
    .form-control:focus{border-color:var(--gov-navy);box-shadow:0 0 0 .1rem rgba(11,46,76,.15)}
    .toggle-password{cursor:pointer}
    .helper-text{font-size:.85rem;color:#6c757d}
    .btn-gov-primary{background:linear-gradient(120deg,var(--gov-red),#b10f1a);border:none;border-radius:12px;font-weight:700;padding:.75rem}
    .btn-gov-outline{border-color:var(--gov-navy);color:var(--gov-navy);border-radius:12px;font-weight:600}
    .btn-gov-outline:hover{background:var(--gov-navy);color:#fff}
    .link-muted{color:var(--gov-navy);font-weight:600}
    .status-note{font-size:.9rem;color:#4b5563;margin-top:.75rem}
    .lang-switcher{position:fixed;top:14px;right:16px;display:inline-flex;background:rgba(15,23,42,.86);border:1px solid rgba(15,23,42,.08);border-radius:999px;padding:4px;z-index:1200;box-shadow:0 10px 26px rgba(15,23,42,.18)}
    .lang-switcher .lang-btn{border:none;background:transparent;color:#e7eef9;padding:6px 12px;border-radius:999px;font-weight:700;letter-spacing:.03em;cursor:pointer;transition:background .2s ease,color .2s ease}
    .lang-switcher .lang-btn.active{background:#0d6efd;color:#fff;box-shadow:0 4px 14px rgba(13,110,253,.35)}
  </style>
</head>

<body class="mobile-home-body auth-page">
  <div class="lang-switcher">
    <a class="lang-btn <?php echo $currentLang === 'en' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($langUrlEn, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(i18n_t('nav.lang.en'), ENT_QUOTES, 'UTF-8'); ?></a>
    <a class="lang-btn <?php echo $currentLang === 'ne' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($langUrlNe, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(i18n_t('nav.lang.ne'), ENT_QUOTES, 'UTF-8'); ?></a>
  </div>

  <div class="auth-shell">
    <section class="brand-panel">
      <div class="brand-badge">
        <img src="img/shuklagandaki_logo.png" alt="Municipality emblem">
        <div>
          <div class="brand-kicker"><?php echo htmlspecialchars(i18n_t('login.brand.kicker'), ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="brand-name"><?php echo htmlspecialchars(i18n_t('login.brand.name'), ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="brand-sub"><?php echo htmlspecialchars(i18n_t('login.brand.sub'), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      </div>
      <p class="lead-copy"><?php echo htmlspecialchars(i18n_t('login.lead'), ENT_QUOTES, 'UTF-8'); ?></p>
      <ul class="trust-bullets">
        <li><?php echo htmlspecialchars(i18n_t('login.bullet1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(i18n_t('login.bullet2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(i18n_t('login.bullet3'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
      <div class="support-block"><?php echo htmlspecialchars(i18n_t('login.support'), ENT_QUOTES, 'UTF-8'); ?></div>
    </section>

    <div class="card auth-card mx-auto">
      <div class="card-header text-center">
        <div class="card-kicker"><?php echo htmlspecialchars(i18n_t('login.header.kicker'), ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="card-title"><?php echo htmlspecialchars(i18n_t('login.header.title'), ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="card-subtitle"><?php echo htmlspecialchars(i18n_t('login.header.subtitle'), ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
      <div class="card-body">
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">
          <div class="form-group">
            <label for="inputEmail"><?php echo htmlspecialchars(i18n_t('login.email.label'), ENT_QUOTES, 'UTF-8'); ?></label>
            <input type="email" id="inputEmail" name="email" class="form-control" placeholder="<?php echo htmlspecialchars(i18n_t('login.email.placeholder'), ENT_QUOTES, 'UTF-8'); ?>" autofocus required>
          </div>

          <div class="form-group">
            <label for="inputPassword"><?php echo htmlspecialchars(i18n_t('login.password.label'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-group">
              <input type="password" id="inputPassword" name="password" class="form-control" placeholder="<?php echo htmlspecialchars(i18n_t('login.password.placeholder'), ENT_QUOTES, 'UTF-8'); ?>" minlength="9" required>
              <div class="input-group-append">
                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="inputPassword">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <small class="helper-text"><?php echo htmlspecialchars(i18n_t('login.password.helper'), ENT_QUOTES, 'UTF-8'); ?></small>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <a class="link-muted" href="./forgot-password.php"><?php echo htmlspecialchars(i18n_t('login.forgot'), ENT_QUOTES, 'UTF-8'); ?></a>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="rememberMe" value="remember-me">
              <label class="form-check-label" for="rememberMe"><?php echo htmlspecialchars(i18n_t('login.remember'), ENT_QUOTES, 'UTF-8'); ?></label>
            </div>
          </div>

          <?php if (strlen($err) > 1) : ?>
            <div class="alert alert-danger text-center mb-3" role="alert">
              <strong>Login failed:</strong> <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <button type="submit" name="submit" class="btn btn-gov-primary btn-block mb-2"><?php echo htmlspecialchars(i18n_t('login.submit'), ENT_QUOTES, 'UTF-8'); ?></button>
          <a href="./new.php" class="btn btn-gov-outline btn-block"><?php echo htmlspecialchars(i18n_t('login.create'), ENT_QUOTES, 'UTF-8'); ?></a>
        </form>

        <div class="status-note text-center"><?php echo htmlspecialchars(i18n_t('login.status'), ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    </div>
  </div>

  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
