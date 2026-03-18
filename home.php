<?php
require_once './src/i18n.php';
$currentLang = i18n_lang();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="Shuklagandaki Municipality citizen service highlights">
  <meta name="author" content="">
  <title>Shuklagandaki Municipality - Home</title>
  <link rel="icon" type="image/png" href="img/shuklagandaki_logo.png">
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="css/sb-admin.css" rel="stylesheet" type="text/css">
  <link href="css/mobile-home.css" rel="stylesheet">
  <link href="css/mobile-theme.css" rel="stylesheet">
  <link href="css/home.css" rel="stylesheet">
  <script defer src="js/main.js?v=<?php echo filemtime(__DIR__ . '/js/main.js'); ?>"></script>
</head>
<body class="mobile-home-body home-body">
  <div class="home-wrapper">
    <main class="flex-fill d-flex align-items-center">
      <div id="featureCarousel" class="carousel slide carousel-fade w-100" data-ride="carousel" data-interval="4800">
        <ol class="carousel-indicators">
          <li data-target="#featureCarousel" data-slide-to="0" class="active"></li>
          <li data-target="#featureCarousel" data-slide-to="1"></li>
          <li data-target="#featureCarousel" data-slide-to="2"></li>
        </ol>
        <div class="carousel-inner">
          <div class="carousel-item active">
            <div class="slide-illustration">
              <div class="accent accent-folder"><i class="fas fa-folder-open"></i></div>
              <div class="accent accent-letter"><i class="fas fa-envelope-open-text"></i></div>
              <div class="mobile-frame">
                <div class="mobile-notch"></div>
                <div class="mobile-icon gradient-gold">
                  <i class="fas fa-file-alt"></i>
                </div>
              </div>
            </div>
            <h2 class="slide-title"><?php echo htmlspecialchars(i18n_t('landing.slide1.title', 'Recommendations'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="slide-text"><?php echo htmlspecialchars(i18n_t('landing.slide1.text', 'Access municipal recommendation and certificate services directly from your mobile phone.'), ENT_QUOTES, 'UTF-8'); ?></p>
          </div>

          <div class="carousel-item">
            <div class="slide-illustration">
              <div class="accent accent-warning"><i class="fas fa-exclamation-triangle"></i></div>
              <div class="accent accent-firstaid"><i class="fas fa-briefcase-medical"></i></div>
              <div class="mobile-frame">
                <div class="mobile-notch"></div>
                <div class="mobile-icon gradient-red">
                  <i class="fas fa-bullhorn"></i>
                </div>
              </div>
            </div>
            <h2 class="slide-title"><?php echo htmlspecialchars(i18n_t('landing.slide2.title', 'Emergency Contacts'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="slide-text"><?php echo htmlspecialchars(i18n_t('landing.slide2.text', 'Reach important local support and emergency contacts quickly when you need them.'), ENT_QUOTES, 'UTF-8'); ?></p>
          </div>

          <div class="carousel-item">
            <div class="slide-illustration">
              <div class="accent accent-chat"><i class="fas fa-comments"></i></div>
              <div class="accent accent-document"><i class="fas fa-id-card"></i></div>
              <div class="mobile-frame">
                <div class="mobile-notch"></div>
                <div class="mobile-icon gradient-green">
                  <i class="fas fa-vote-yea"></i>
                </div>
              </div>
            </div>
            <h2 class="slide-title"><?php echo htmlspecialchars(i18n_t('landing.slide3.title', 'Complaints'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="slide-text"><?php echo htmlspecialchars(i18n_t('landing.slide3.text', 'Submit complaints or requests safely and track them more easily.'), ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
        </div>

        <a class="carousel-control-prev" href="#featureCarousel" role="button" data-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="sr-only"><?php echo htmlspecialchars(i18n_t('landing.prev', 'Previous'), ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <a class="carousel-control-next" href="#featureCarousel" role="button" data-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="sr-only"><?php echo htmlspecialchars(i18n_t('landing.next', 'Next'), ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
      </div>
    </main>

    <footer class="d-flex justify-content-between align-items-center mt-4">
      <div class="d-flex align-items-center">
        <div class="home-indicator"></div>
      </div>
      <a class="btn btn-primary shadow start-button" href="index.php">
        <?php echo htmlspecialchars(i18n_t('landing.start', 'Get Started'), ENT_QUOTES, 'UTF-8'); ?>
        <span class="ml-2"><i class="fas fa-arrow-right"></i></span>
      </a>
    </footer>
  </div>

  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
</body>
</html>
