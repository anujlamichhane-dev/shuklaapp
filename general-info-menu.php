<?php
  $bodyClass = 'mobile-home-body';
  $extraCss = ['css/mobile-home.css'];
  $hideSidebar = true;
  $hideSidebarToggle = true;
  include './header.php';

  $infoCards = [
    [
      'href' => 'contacts.php',
      'label' => 'Contacts',
      'sub' => 'सम्पर्कहरू',
      'icon' => 'fa-address-book'
    ],
    [
      'href' => 'interesting-places.php',
      'label' => 'Interesting Places',
      'sub' => 'रोचक ठाउँहरू',
      'icon' => 'fa-map-marked-alt'
    ],
    [
      'href' => 'documents-info.php',
      'label' => 'Documents Info',
      'sub' => 'कागजात जानकारी',
      'icon' => 'fa-file-alt'
    ],
    [
      'href' => 'municipality-introduction.php',
      'label' => 'Shuklagandaki Municipality Introduction',
      'sub' => 'शुक्लागण्डकी नगरपालिका परिचय',
      'icon' => 'fa-landmark'
    ]
  ];
?>

<div id="content-wrapper">
  <div class="app-shell">
    <section class="menu-section">
      <div class="section-title">सामान्य जानकारी</div>
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
