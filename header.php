<?php 
if (!ob_get_level()) {
  ob_start();
}
require_once './src/remember.php';
$secure = remember_cookie_secure();
$cookieDomain = remember_cookie_domain();
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'domain' => $cookieDomain,
  'secure' => $secure,
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();
require_once './src/i18n.php';
require_once './src/Database.php';

$db = Database::getInstance();

if (!isset($_SESSION['logged-in']) || $_SESSION['logged-in'] == false) {
    $rememberUser = remember_login($db, $secure);
    if ($rememberUser) {
        $_SESSION['logged-in'] = true;
        $_SESSION['user'] = $rememberUser;
    }
}
//ini_set('display_errors', 1);
if(!isset($_SESSION['logged-in']) || $_SESSION['logged-in'] == false){
    header('Location: ./index.php');
    exit();
}
$user = $_SESSION['user'];

$role = $user->role ?? 'member';
$officialRoles = ['mayor','deputymayor','spokesperson','chief_officer','info_officer'];
$isAdmin = ($role === 'admin');
$isCreator = ($role === 'creator');
$isModerator = ($role === 'moderator');
$isClient = ($role === 'client');
$isOfficial = in_array($role, $officialRoles, true);
$showBackBtn = false;
$hideSidebar = false;
$hideSidebarToggle = false;
$bodyClass = $bodyClass ?? 'mobile-home-body';
$extraCss = $extraCss ?? [];
if (!is_array($extraCss)) {
  $extraCss = [];
}
if (!in_array('css/mobile-home.css', $extraCss, true)) {
  $extraCss[] = 'css/mobile-home.css';
}
if (!in_array('css/mobile-theme.css', $extraCss, true)) {
  $extraCss[] = 'css/mobile-theme.css';
}

// Simple page-level gates to keep clients and moderators in the allowed areas only.
$currentPage = basename($_SERVER['PHP_SELF']);
$showBackBtn = !in_array($currentPage, ['index.php', 'newuser.php'], true);
$clientAllowedPages = [
  'mobile-home.php',
  'tickets-menu.php',
  'general-info-menu.php',
  'interesting-places.php',
  'municipality-introduction.php',
  'ticket.php',
  'mytickets.php',
  'ticket-details.php',
  'contacts.php',
  'message.php',
  'logout.php',
  'documents-info.php',
  'my-messages.php'
];
if ($isClient && !in_array($currentPage, $clientAllowedPages, true)) {
  header('Location: ./mytickets.php');
  exit();
}

if ($isModerator && $currentPage === 'mytickets.php') {
  header('Location: ./dashboard.php');
  exit();
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
  <meta name="description" content="">
  <meta name="author" content="">

  <title>Shuklagandaki Municipality - Dashboard</title>
  <link rel="icon" type="image/png" href="img/shuklagandaki_logo.png">

  <!-- Custom fonts for this template-->
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

  <!-- Page level plugin CSS-->
  <link href="vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">

  <!-- Custom styles for this template-->
  <link href="css/sb-admin.css" rel="stylesheet">
  <link href="css/theme-purple.css" rel="stylesheet">
  <script defer src="js/main.js?v=<?php echo filemtime(__DIR__ . '/js/main.js'); ?>"></script>
  <?php if (!empty($extraCss) && is_array($extraCss)): ?>
    <?php foreach ($extraCss as $cssPath): ?>
      <link href="<?php echo htmlspecialchars($cssPath, ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <?php endforeach; ?>
  <?php endif; ?>

</head>

<body id="page-top" class="<?php echo isset($bodyClass) ? htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') : ''; ?>">

  <nav class="navbar navbar-expand navbar-dark bg-dark static-top">
    <?php if ($showBackBtn): ?>
      <a class="btn btn-sm btn-outline-light mr-2 d-inline-flex align-items-center btn-home" href="./mobile-home.php" aria-label="Go to home">
        <i class="fas fa-home mr-1"></i>
        <span>To Home</span>
      </a>
    <?php endif; ?>
    <?php if (!$hideSidebarToggle): ?>
      <button class="btn btn-link btn-sm text-white mr-2 mobile-menu-icon" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
        <i class="fas fa-bars"></i>
      </button>
    <?php endif; ?>

    <div class="navbar-brand-placeholder"></div>

    <!-- Navbar Search -->
    <form class="d-none d-md-inline-block form-inline ml-auto mr-0 mr-md-3 my-2 my-md-0">
      <div class="input-group">
        <input type="hidden" class="form-control" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
        <div class="input-group-append">
          <!--<button class="btn btn-primary" type="button">
            <i class="fas fa-search"></i>
          </button>-->
        </div>
      </div>
    </form>

    <!-- Navbar -->
    <ul class="navbar-nav ml-auto ml-md-0">
      <li class="nav-item mr-2">
        <div class="lang-switcher" style="display:flex; gap:6px; align-items:center;">
          <a class="btn btn-sm btn-outline-light" href="<?php echo htmlspecialchars($langUrlEn, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $currentLang === 'en' ? 'style="font-weight:700;"' : ''; ?>><?php echo htmlspecialchars(i18n_t('nav.lang.en'), ENT_QUOTES, 'UTF-8'); ?></a>
          <a class="btn btn-sm btn-outline-light" href="<?php echo htmlspecialchars($langUrlNe, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $currentLang === 'ne' ? 'style="font-weight:700;"' : ''; ?>><?php echo htmlspecialchars(i18n_t('nav.lang.ne'), ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
      </li>
      
      <li class="nav-item dropdown no-arrow">
        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i class="fas fa-user-circle fa-fw"></i>
          <span class="nav-user-name"><?php echo $user->name?></span>
        </a>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
          
          <a class="dropdown-item" href="./logout.php" data-toggle="modal" data-target="#logoutModal">Logout</a>
        </div>
      </li>
    </ul>

  </nav>

  <div id="wrapper">

    <!-- Sidebar -->
    <?php if (!$hideSidebar): ?>
    <ul class="sidebar navbar-nav">
      <li class="sidebar-profile"></li>
      <li class="nav-item active">
        <a class="nav-link" href="./mobile-home.php">
          <i class="fas fa-fw fa-home"></i>
          <span> <?php echo htmlspecialchars(i18n_t('nav.home'), ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
      </li>
      <li class="nav-item active">
        <a class="nav-link" href="./tickets-menu.php">
          <i class="fas fa-fw fa-ticket-alt"></i>
          <span> <?php echo htmlspecialchars(i18n_t('nav.tickets'), ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
      </li>
      <li class="nav-item active">
        <a class="nav-link" href="<?php echo ($isAdmin || $isOfficial) ? './messages-inbox.php' : './message.php'; ?>">
          <i class="fas fa-fw fa-inbox"></i>
          <span> <?php echo htmlspecialchars(i18n_t('nav.messages'), ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
      </li>
      <li class="nav-item active">
        <a class="nav-link" href="./contacts.php">
          <i class="fas fa-fw fa-address-book"></i>
          <span> <?php echo htmlspecialchars(i18n_t('nav.contacts'), ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
      </li>
      <li class="nav-item active sidebar-logout">
        <a class="nav-link" href="./logout.php">
          <i class="fas fa-fw fa-sign-out-alt"></i>
          <span> <?php echo htmlspecialchars(i18n_t('nav.logout'), ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
      </li>
    </ul>
    <?php endif; ?>

    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
    
