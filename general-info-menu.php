<?php
  require_once './src/i18n.php';
  $bodyClass = 'mobile-home-body';
  $pageTitle = i18n_t('generalinfo.title', 'General Information');
  $extraCss = ['css/mobile-home.css'];
  $hideSidebar = true;
  $hideSidebarToggle = true;
  include './header.php';

  $infoCards = [
    [
      'href' => 'contacts.php',
      'label' => i18n_t('generalinfo.contacts', 'Contacts'),
      'sub' => i18n_t('generalinfo.contacts.sub', 'Contact points'),
      'icon' => 'fa-address-book'
    ],
    [
      'href' => 'interesting-places.php',
      'label' => i18n_t('generalinfo.places', 'Interesting Places'),
      'sub' => i18n_t('generalinfo.places.sub', 'Local attractions'),
      'icon' => 'fa-map-marked-alt'
    ],
    [
      'href' => 'documents-info.php',
      'label' => i18n_t('generalinfo.documents', 'Documents Info'),
      'sub' => i18n_t('generalinfo.documents.sub', 'Document information'),
      'icon' => 'fa-file-alt'
    ],
    [
      'href' => 'municipality-introduction.php',
      'label' => i18n_t('generalinfo.introduction', 'Shuklagandaki Municipality Introduction'),
      'sub' => i18n_t('generalinfo.introduction.sub', 'Municipality introduction'),
      'icon' => 'fa-landmark'
    ]
  ];
?>

<div id="content-wrapper">
  <div class="app-shell">
    <section class="menu-section">
      <div class="section-title"><?php echo htmlspecialchars(i18n_t('generalinfo.title', 'General Information'), ENT_QUOTES, 'UTF-8'); ?></div>
      <div class="menu-grid">
        <?php foreach ($infoCards as $card): ?>
          <a class="menu-card" href="<?php echo htmlspecialchars($card['href'], ENT_QUOTES, 'UTF-8'); ?>">
            <span class="menu-icon"><i class="fas <?php echo htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></span>
            <span class="menu-label"><?php echo htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="menu-subtext"><?php echo htmlspecialchars($card['sub'], ENT_QUOTES, 'UTF-8'); ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>

<?php include './footer.php'; ?>
