<?php
  $bodyClass = 'mobile-home-body';
  $extraCss = ['css/mobile-home.css'];
  $hideSidebar = true;
  $hideSidebarToggle = true;
  include './header.php';

  $menuCards = [];
  if ($isGuest) {
    $menuCards[] = ['href' => 'ticket.php', 'label' => 'Open a service request', 'sub' => 'Report an issue or ask for support from this device', 'icon' => 'fa-plus-circle'];
    $menuCards[] = ['href' => 'mytickets.php', 'label' => 'Track this session', 'sub' => 'Review cases opened during this guest session', 'icon' => 'fa-clipboard-list'];
  } elseif ($isClient) {
    $menuCards[] = ['href' => 'ticket.php', 'label' => 'Open a service request', 'sub' => 'Submit a new municipal case', 'icon' => 'fa-plus-circle'];
    $menuCards[] = ['href' => 'mytickets.php', 'label' => 'My service requests', 'sub' => 'See status, staff updates, and case history', 'icon' => 'fa-clipboard-list'];
  } else {
    $menuCards[] = ['href' => 'dashboard.php', 'label' => 'Request board', 'sub' => 'All municipal cases', 'icon' => 'fa-columns'];
    $menuCards[] = ['href' => 'open.php', 'label' => 'Submitted cases', 'sub' => 'Waiting for triage', 'icon' => 'fa-inbox'];
    $menuCards[] = ['href' => 'pending.php', 'label' => 'In progress', 'sub' => 'Under review or active work', 'icon' => 'fa-tools'];
    $menuCards[] = ['href' => 'solved.php', 'label' => 'Resolved', 'sub' => 'Answered or completed', 'icon' => 'fa-check-circle'];
    $menuCards[] = ['href' => 'closed.php', 'label' => 'Closed', 'sub' => 'Archived cases', 'icon' => 'fa-folder'];
    $menuCards[] = ['href' => 'unassigned.php', 'label' => 'Unassigned', 'sub' => 'Needs a responsible staff member', 'icon' => 'fa-user-clock'];
    if (!$isModerator) {
      $menuCards[] = ['href' => 'mytickets.php', 'label' => 'My assigned cases', 'sub' => 'Cases connected to your account', 'icon' => 'fa-user-check'];
    }
  }
?>

<div id="content-wrapper">
  <div class="app-shell">
    <section class="menu-section">
      <div class="section-title">Service requests</div>
      <?php if ($isGuest): ?>
        <div class="alert alert-info">Guest access works on this device only. Add an email and phone number so staff can contact you about the case.</div>
      <?php endif; ?>
      <div class="menu-grid single">
        <?php foreach ($menuCards as $card): ?>
          <a class="menu-card service-card" href="<?php echo htmlspecialchars(appUrl($card['href']), ENT_QUOTES, 'UTF-8'); ?>">
            <span class="menu-icon"><i class="fas <?php echo htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></span>
            <span class="menu-label"><?php echo htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="menu-subtext"><?php echo htmlspecialchars($card['sub'], ENT_QUOTES, 'UTF-8'); ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>

<style>
  .service-card {
    text-align: left;
    display: grid;
    grid-template-columns: auto 1fr;
    grid-template-areas:
      "icon label"
      "icon sub";
    gap: .25rem .85rem;
    align-items: center;
    padding: 1rem 1.1rem;
  }
  .service-card .menu-icon {
    grid-area: icon;
    margin: 0;
  }
  .service-card .menu-label {
    grid-area: label;
  }
  .service-card .menu-subtext {
    grid-area: sub;
    margin-top: 0;
  }
</style>

<?php include './footer.php'; ?>
