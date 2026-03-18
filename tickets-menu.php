<?php
  require_once './src/i18n.php';
  $bodyClass = 'mobile-home-body';
  $pageTitle = i18n_t('tickets.menu.title', 'Tickets');
  $extraCss = ['css/mobile-home.css'];
  $hideSidebar = true;
  $hideSidebarToggle = true;
  include './header.php';

  $ticketLinks = [];
  if ($isClient) {
    $ticketLinks[] = ['href' => 'ticket.php', 'label' => i18n_t('tickets.new_client', 'New Suggestion / Complaint'), 'icon' => 'fa-plus-circle'];
    $ticketLinks[] = ['href' => 'mytickets.php', 'label' => i18n_t('tickets.my_client', 'My Suggestions / Complaints'), 'icon' => 'fa-award'];
  } else {
    $ticketLinks[] = ['href' => 'dashboard.php', 'label' => i18n_t('tickets.dashboard', 'Dashboard'), 'icon' => 'fa-tachometer-alt'];
    $ticketLinks[] = ['href' => 'ticket.php', 'label' => i18n_t('tickets.create_new', 'Create New Tickets'), 'icon' => 'fa-plus-circle'];
    $ticketLinks[] = ['href' => 'open.php', 'label' => i18n_t('tickets.open', 'Open'), 'icon' => 'fa-lock-open'];
    $ticketLinks[] = ['href' => 'solved.php', 'label' => i18n_t('tickets.solved', 'Solved'), 'icon' => 'fa-anchor'];
    $ticketLinks[] = ['href' => 'closed.php', 'label' => i18n_t('tickets.closed', 'Closed'), 'icon' => 'fa-times-circle'];
    $ticketLinks[] = ['href' => 'pending.php', 'label' => i18n_status_label('pending'), 'icon' => 'fa-adjust'];
    $ticketLinks[] = ['href' => 'unassigned.php', 'label' => i18n_t('tickets.unassigned', 'Unassigned'), 'icon' => 'fa-at'];
    if (!$isModerator) {
      $ticketLinks[] = ['href' => 'mytickets.php', 'label' => i18n_t('tickets.my', 'My Tickets'), 'icon' => 'fa-award'];
    }
  }
?>

<div id="content-wrapper">
  <div class="app-shell">
    <section class="menu-section">
      <div class="section-title"><?php echo htmlspecialchars(i18n_t('tickets.menu.title', 'Tickets'), ENT_QUOTES, 'UTF-8'); ?></div>
      <?php if ($isClient): ?>
        <div class="menu-grid">
          <?php foreach ($ticketLinks as $link): ?>
            <a class="menu-card" href="<?php echo htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8'); ?>">
              <span class="menu-icon"><i class="fas <?php echo htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></span>
              <span class="menu-label"><?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="ticket-menu-card">
          <?php foreach ($ticketLinks as $link): ?>
            <a class="ticket-row" href="<?php echo htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8'); ?>">
              <span class="ticket-icon"><i class="fas <?php echo htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></span>
              <span class="ticket-label"><?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="ticket-arrow"><i class="fas fa-chevron-right"></i></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>

<style>
  .ticket-menu-card {
    background: rgba(20, 23, 30, 0.85);
    border-radius: 16px;
    padding: 6px;
    box-shadow: 0 12px 28px rgba(0,0,0,0.35);
  }
  .ticket-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    color: #e5e7eb;
    text-decoration: none;
  }
  .ticket-row:hover { background: rgba(255,255,255,0.06); }
  .ticket-icon {
    width: 34px; height: 34px;
    display: grid; place-items: center;
    border-radius: 10px;
    background: rgba(59,130,246,0.2);
    color: #7cb3ff;
  }
  .ticket-label { font-weight: 600; }
  .ticket-arrow { margin-left: auto; color: #94a3b8; }
</style>

<?php include './footer.php'; ?>
