<?php
session_start();

require_once './src/Database.php';

$db = Database::getInstance();

$err = '';
$msg = '';
$email = '';
$phone = '';

if (isset($_POST['submit'])) {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strlen($email) < 1) {
        $err = 'Please enter email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Please enter a valid email address';
    } elseif (strlen($phone) < 1) {
        $err = 'Please enter phone number';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $err = 'Phone number should contain exactly 10 digits';
    } elseif (strlen($password) < 1) {
        $err = 'Please enter your new password';
    } elseif (strlen($password) <= 8) {
        $err = 'Password must be longer than 8 characters';
    } else {
        $stmt = $db->prepare("SELECT id, phone FROM users WHERE email = ?");
        if ($stmt === false) {
            $err = 'Unable to process request right now';
        } else {
            $stmt->bind_param('s', $email);
            if (!$stmt->execute()) {
                $err = 'Unable to process request right now';
            } else {
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $user = $res->fetch_object();
                    if ($user->phone !== $phone) {
                        $err = 'Phone number does not match our records';
                    } else {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $update = $db->prepare("UPDATE users SET password = ?, last_password = ? WHERE id = ?");
                        if ($update === false) {
                            $err = 'Unable to update password right now';
                        } else {
                            $update->bind_param('ssi', $hashed, $hashed, $user->id);
                            if ($update->execute()) {
                                $msg = 'Password updated successfully. You can now log in.';
                            } else {
                                $err = 'Unable to update password right now';
                            }
                            $update->close();
                        }
                    }
                } else {
                    $err = 'No account found with that email';
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">

  <title>Municipal Service Portal - Reset Password</title>
  <link rel="icon" type="image/png" href="img/shuklagandaki_logo.png">

  <!-- Custom fonts for this template-->
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

  <!-- Custom styles for this template-->
  <link href="css/sb-admin.css" rel="stylesheet">
  <link href="css/mobile-home.css" rel="stylesheet">
  <link href="css/mobile-theme.css" rel="stylesheet">
  <script defer src="js/main.js"></script>
  <style>
    :root {
      --gov-navy: #1f2b4d;
      --gov-red: #2f6fed;
      --gov-sky: #f2f4f8;
      --gov-ink: #1f2b4d;
      --gov-border: rgba(31, 43, 77, 0.12);
    }
    body.auth-page {
      min-height: 100vh;
      margin: 0;
      background: linear-gradient(180deg, #f7f8fc 0%, #eef2f9 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2.5rem 1.25rem;
      color: var(--gov-ink);
      font-family: "Trebuchet MS", "Lucida Sans Unicode", "Lucida Grande", "Lucida Sans", Arial, sans-serif;
    }
    .auth-shell {
      width: 100%;
      max-width: 560px;
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }
    .auth-card { order: 1; }
    .brand-panel { order: 2; }
    .brand-panel {
      background: #fff;
      border: 1px solid var(--gov-border);
      border-radius: 18px;
      padding: 1.75rem;
      box-shadow: 0 15px 45px rgba(15, 23, 42, 0.12);
      position: relative;
      overflow: hidden;
    }
    .brand-panel::before {
      content: "";
      position: absolute;
      inset: 0;
      pointer-events: none;
      background: linear-gradient(135deg, rgba(193, 18, 31, 0.08), rgba(11, 46, 76, 0.12));
      opacity: 0.35;
    }
    .brand-panel > * {
      position: relative;
    }
    .brand-badge {
      display: flex;
      align-items: center;
      gap: 0.85rem;
      margin-bottom: 1rem;
    }
    .brand-badge img {
      width: 72px;
      height: 72px;
      object-fit: contain;
    }
    .brand-kicker {
      font-size: 0.9rem;
      color: var(--gov-ink);
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }
    .brand-name {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--gov-navy);
    }
    .brand-sub {
      color: var(--gov-red);
      font-weight: 700;
    }
    .lead-copy {
      font-size: 1rem;
      line-height: 1.6;
      color: #1f2937;
    }
    .trust-bullets {
      padding-left: 1.2rem;
      margin: 1rem 0 0;
      color: #374151;
    }
    .trust-bullets li + li {
      margin-top: 0.35rem;
    }
    .support-block {
      margin-top: 1.2rem;
      padding: 0.9rem 1rem;
      background: var(--gov-sky);
      border-left: 4px solid var(--gov-navy);
      border-radius: 12px;
      font-size: 0.95rem;
      color: #0b2540;
    }
    .auth-card {
      border: none;
      border-radius: 18px;
      box-shadow: 0 22px 60px rgba(15, 23, 42, 0.18);
      overflow: hidden;
    }
    .auth-card .card-header {
      background: linear-gradient(120deg, var(--gov-navy), #0f3c64, var(--gov-red));
      color: #fff;
      border: none;
      letter-spacing: 0.5px;
    }
    .card-header .card-kicker {
      font-size: 0.85rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      opacity: 0.85;
    }
    .card-header .card-title {
      font-size: 1.4rem;
      font-weight: 700;
      margin: 0.25rem 0 0;
    }
    .card-header .card-subtitle {
      font-size: 0.95rem;
      opacity: 0.92;
    }
    .auth-card .card-body {
      background: #fff;
      color: #212529;
      padding: 1.75rem;
    }
    .input-group-text,
    .toggle-password {
      cursor: pointer;
    }
    .helper-text {
      font-size: 0.85rem;
      color: #6c757d;
    }
    .form-control {
      border-radius: 10px;
      border-color: #d8deea;
    }
    .form-control:focus {
      border-color: var(--gov-navy);
      box-shadow: 0 0 0 0.1rem rgba(11, 46, 76, 0.15);
    }
    .btn-gov-primary {
      background: linear-gradient(120deg, var(--gov-red), #b10f1a);
      border: none;
      border-radius: 12px;
      font-weight: 700;
      padding: 0.75rem;
    }
    .btn-gov-outline {
      border-color: var(--gov-navy);
      color: var(--gov-navy);
      border-radius: 12px;
      font-weight: 600;
    }
    .btn-gov-outline:hover {
      background: var(--gov-navy);
      color: #fff;
    }
    .link-muted {
      color: var(--gov-navy);
      font-weight: 600;
    }
    .status-note {
      font-size: 0.9rem;
      color: #4b5563;
      margin-top: 0.75rem;
    }
  </style>

</head>

<body class="mobile-home-body auth-page">
  <div class="auth-shell">
    <section class="brand-panel">
      <div class="brand-badge">
        <img src="img/shuklagandaki_logo.png" alt="Municipality emblem">
        <div>
          <div class="brand-kicker">Government of Nepal</div>
          <div class="brand-name">Shuklagandaki Municipality</div>
          <div class="brand-sub">Citizen Service Portal</div>
        </div>
      </div>
      <p class="lead-copy">
        Reset your password to continue tracking your municipal services securely.
      </p>
      <ul class="trust-bullets">
        <li>Use the email and phone number you registered with this portal.</li>
        <li>Complete this process on a trusted device and network.</li>
        <li>If your phone number changed, contact your ward office for an update.</li>
      </ul>
      <div class="support-block">
        For urgent help or if you suspect someone else accessed your account, reset immediately and notify your ward helpdesk.
      </div>
    </section>

    <div class="card auth-card mx-auto">
      <div class="card-header text-center">
        <div class="card-kicker">Account Recovery</div>
        <div class="card-title">Reset Password</div>
        <div class="card-subtitle">Verify your details to set a new password.</div>
      </div>
      <div class="card-body">
        <?php if(strlen($err) > 1) :?>
          <div class="alert alert-danger text-center mb-3" role="alert" aria-live="assertive">
            <strong>Request failed:</strong> <?php echo $err;?>
          </div>
        <?php endif?>

        <?php if(strlen($msg) > 1) :?>
          <div class="alert alert-success text-center mb-3" role="alert" aria-live="polite">
            <strong>Success:</strong> <?php echo $msg;?>
          </div>
        <?php endif?>
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
          <div class="form-group">
            <label for="inputEmail">Email address</label>
            <input type="email" id="inputEmail" name="email" class="form-control" placeholder="name@example.com" autofocus="autofocus" value="<?php echo htmlspecialchars($email ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label for="inputPhone">Phone number</label>
            <input type="tel" id="inputPhone" name="phone" class="form-control" placeholder="10-digit mobile number" value="<?php echo htmlspecialchars($phone ?? '') ?>" pattern="\d{10}" inputmode="numeric" required>
            <small class="helper-text">Digits only, exactly 10 digits.</small>
          </div>
          <div class="form-group">
            <label for="inputPassword">New password</label>
            <div class="input-group">
              <input type="password" id="inputPassword" name="password" class="form-control" placeholder="Create a strong password" minlength="9" required>
              <div class="input-group-append">
                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="inputPassword" aria-label="Show password">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <small class="helper-text">Must be longer than 8 characters.</small>
          </div>
          <button type="submit" name="submit" class="btn btn-gov-primary btn-block mb-2">Update password</button>
          <a href="./index.php" class="btn btn-gov-outline btn-block">Back to login</a>
        </form>
        <div class="status-note text-center">
          If you did not request a reset, please ignore this page and contact your ward office.
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap core JavaScript-->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Core plugin JavaScript-->
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
  <script>
    document.querySelectorAll('.toggle-password').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var target = document.getElementById(this.getAttribute('data-target'));
        var icon = this.querySelector('i');
        if (target.type === 'password') {
          target.type = 'text';
          icon.classList.remove('fa-eye');
          icon.classList.add('fa-eye-slash');
          this.setAttribute('aria-label', 'Hide password');
        } else {
          target.type = 'password';
          icon.classList.remove('fa-eye-slash');
          icon.classList.add('fa-eye');
          this.setAttribute('aria-label', 'Show password');
        }
      });
    });
  </script>

</body>

</html>
