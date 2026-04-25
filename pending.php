<?php
include './header.php';
require_once './src/service-request-board.php';

$mode = 'pending';
$config = service_request_admin_page_config($mode);
$tickets = service_request_fetch_board_tickets($mode);
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <div class="board-shell narrow">
      <section class="board-hero">
        <div>
          <div class="board-kicker">Municipal Service Desk</div>
          <h1><?php echo htmlspecialchars($config['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
          <p><?php echo htmlspecialchars($config['subtitle'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      </section>
      <section class="board-table-card">
        <?php echo service_request_render_board($tickets, 'No cases are marked in progress right now.'); ?>
      </section>
    </div>
  </div>
</div>
<style>
  .board-shell.narrow { max-width: 1180px; margin: 0 auto; display: grid; gap: 1rem; }
  .board-hero, .board-table-card { background: #fff; border-radius: 20px; border: 1px solid rgba(18,46,77,.08); box-shadow: 0 18px 40px rgba(15,23,42,.08); }
  .board-hero { padding: 1.5rem; }
  .board-kicker { text-transform: uppercase; letter-spacing: .12em; font-size: .8rem; font-weight: 700; color: #2f6fed; margin-bottom: .4rem; }
  .board-hero h1 { margin-bottom: .35rem; color: #12324d; }
  .board-hero p { margin: 0; color: #5f7085; }
  .board-table-card { padding: 1rem; }
</style>
<?php include './footer.php'; ?>
