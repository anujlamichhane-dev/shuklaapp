<?php
include './header.php';
require_once './src/service-request-board.php';

$mode = 'all';
$config = service_request_admin_page_config($mode);
$tickets = service_request_fetch_board_tickets($mode);
$stats = service_request_board_stats($tickets);
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <div class="board-shell">
      <section class="board-hero">
        <div>
          <div class="board-kicker">Municipal Service Desk</div>
          <h1><?php echo htmlspecialchars($config['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
          <p><?php echo htmlspecialchars($config['subtitle'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="board-actions">
          <a class="btn btn-primary" href="<?php echo htmlspecialchars(appUrl('ticket.php'), ENT_QUOTES, 'UTF-8'); ?>">Create internal case</a>
        </div>
      </section>

      <section class="board-stats">
        <div class="board-stat"><strong><?php echo (int)$stats['total']; ?></strong><span>Total cases</span></div>
        <div class="board-stat"><strong><?php echo (int)$stats['open']; ?></strong><span>Submitted</span></div>
        <div class="board-stat"><strong><?php echo (int)$stats['pending']; ?></strong><span>In progress</span></div>
        <div class="board-stat"><strong><?php echo (int)$stats['solved']; ?></strong><span>Resolved</span></div>
        <div class="board-stat"><strong><?php echo (int)$stats['unassigned']; ?></strong><span>Unassigned</span></div>
      </section>

      <section class="board-table-card">
        <?php echo service_request_render_board($tickets, 'No service requests have been logged yet.'); ?>
      </section>
    </div>
  </div>
</div>

<style>
  .board-shell {
    max-width: 1180px;
    margin: 0 auto;
    display: grid;
    gap: 1rem;
  }
  .board-hero,
  .board-table-card,
  .board-stats {
    background: #fff;
    border-radius: 20px;
    border: 1px solid rgba(18, 46, 77, 0.08);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
  }
  .board-hero {
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 1rem;
  }
  .board-kicker {
    text-transform: uppercase;
    letter-spacing: .12em;
    font-size: .8rem;
    font-weight: 700;
    color: #2f6fed;
    margin-bottom: .4rem;
  }
  .board-hero h1 {
    margin-bottom: .35rem;
    color: #12324d;
  }
  .board-hero p {
    margin: 0;
    color: #5f7085;
  }
  .board-stats {
    padding: 1rem;
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: .75rem;
  }
  .board-stat {
    border-radius: 16px;
    background: #f7fafc;
    padding: .95rem 1rem;
    border: 1px solid rgba(18, 46, 77, 0.08);
  }
  .board-stat strong {
    display: block;
    font-size: 1.4rem;
    color: #12324d;
  }
  .board-stat span {
    color: #6a7d90;
  }
  .board-table-card {
    padding: 1rem;
  }
  @media (max-width: 900px) {
    .board-stats {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  @media (max-width: 768px) {
    .board-hero {
      flex-direction: column;
      align-items: stretch;
    }
    .board-actions .btn {
      width: 100%;
    }
  }
</style>

<?php include './footer.php'; ?>
