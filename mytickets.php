<?php
require_once './src/i18n.php';
$pageTitle = i18n_t('mytickets.breadcrumb.title', 'My tickets');
include './header.php';

require_once './src/ticket.php';
require_once './src/requester.php';
require_once './src/team.php';
require_once './src/user.php';

$isClient = ($user->role === 'client');
$tickets = $isClient ? Ticket::findByRequesterUserId($user->id) : Ticket::findByMember($user->id);

$statusFilter = '';
if (isset($_GET['status']) && is_string($_GET['status'])) {
    $candidate = strtolower(trim($_GET['status']));
    $allowedStatuses = ['open', 'pending', 'solved', 'closed'];
    if (in_array($candidate, $allowedStatuses, true)) {
        $statusFilter = $candidate;
    }
}

if ($statusFilter !== '') {
    $tickets = array_values(array_filter($tickets, function ($ticket) use ($statusFilter) {
        return isset($ticket->status) && strtolower($ticket->status) === $statusFilter;
    }));
}
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="./dashboard.php"><?php echo htmlspecialchars(i18n_t('mytickets.breadcrumb.dashboard', 'Dashboard'), ENT_QUOTES, 'UTF-8'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo htmlspecialchars(i18n_t('mytickets.breadcrumb.title', 'My tickets'), ENT_QUOTES, 'UTF-8'); ?></li>
    </ol>

    <div class="card mb-3">
      <div class="card-body">
        <?php if (empty($tickets)) : ?>
          <div class="alert alert-info text-center mb-0">
            <?php echo htmlspecialchars(i18n_t('mytickets.empty', 'No tickets yet. Tap New ticket in the menu to create your first request.'), ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php else : ?>
          <div class="table-responsive tickets-table">
            <table class="table table-bordered table-hover table-sm table-mobile-stack" id="dataTable" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th><?php echo htmlspecialchars(i18n_t('mytickets.table.subject', 'Subject'), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(i18n_t('mytickets.table.requester', 'Requester'), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(i18n_t('mytickets.table.team', 'Team'), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(i18n_t('mytickets.table.agent', 'Agent'), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(i18n_t('mytickets.table.status', 'Status'), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(i18n_t('mytickets.table.requested', 'Requested'), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(i18n_t('mytickets.table.action', 'Action'), ENT_QUOTES, 'UTF-8'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($tickets as $ticket) : ?>
                  <?php
                    $requesterObj = Requester::find($ticket->requester);
                    $teamObj = (!empty($ticket->team) && (int)$ticket->team > 0) ? Team::find($ticket->team) : null;
                    $agentObj = !empty($ticket->team_member) ? User::find($ticket->team_member) : null;
                    $date = new DateTime($ticket->created_at);
                  ?>
                  <tr>
                    <td data-label="<?php echo htmlspecialchars(i18n_t('mytickets.table.subject', 'Subject'), ENT_QUOTES, 'UTF-8'); ?>">
                      <a href="./ticket-details.php?id=<?php echo (int)$ticket->id; ?>"><?php echo htmlspecialchars($ticket->title, ENT_QUOTES, 'UTF-8'); ?></a>
                    </td>
                    <td data-label="<?php echo htmlspecialchars(i18n_t('mytickets.table.requester', 'Requester'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($requesterObj ? $requesterObj->name : '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td data-label="<?php echo htmlspecialchars(i18n_t('mytickets.table.team', 'Team'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($teamObj ? $teamObj->name : '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td data-label="<?php echo htmlspecialchars(i18n_t('mytickets.table.agent', 'Agent'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($agentObj ? $agentObj->name : '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td data-label="<?php echo htmlspecialchars(i18n_t('mytickets.table.status', 'Status'), ENT_QUOTES, 'UTF-8'); ?>">
                      <?php
                        $badgeClass = strtolower($ticket->status) === 'solved' ? 'success' : 'secondary';
                      ?>
                      <span class="badge badge-<?php echo $badgeClass; ?>"><?php echo htmlspecialchars(i18n_status_label($ticket->status), ENT_QUOTES, 'UTF-8'); ?></span>
                    </td>
                    <td data-label="<?php echo htmlspecialchars(i18n_t('mytickets.table.requested', 'Requested'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($date->format('d-m-Y H:i'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td data-label="<?php echo htmlspecialchars(i18n_t('mytickets.table.action', 'Action'), ENT_QUOTES, 'UTF-8'); ?>" width="100">
                      <a class="btn btn-outline-primary btn-sm btn-block" href="./ticket-details.php?id=<?php echo (int)$ticket->id; ?>"><?php echo htmlspecialchars(i18n_t('mytickets.table.view', 'View'), ENT_QUOTES, 'UTF-8'); ?></a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include './footer.php'; ?>
