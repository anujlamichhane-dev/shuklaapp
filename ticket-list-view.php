<?php
if (!isset($pageHeading, $tickets)) {
    throw new RuntimeException('Ticket list view requires $pageHeading and $tickets.');
}

$emptyMessage = $emptyMessage ?? i18n_t('admin.no_tickets', 'No tickets found.');
$showNewTicketButton = $showNewTicketButton ?? false;
$newTicketHref = $newTicketHref ?? './ticket.php';
$newTicketLabel = $newTicketLabel ?? i18n_t('admin.new_ticket', 'New Ticket');
$allowDelete = $allowDelete ?? false;
$bannerMessage = $bannerMessage ?? '';
$bannerType = $bannerType ?? 'success';
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="./dashboard.php"><?php echo htmlspecialchars(i18n_t('common.dashboard', 'Dashboard'), ENT_QUOTES, 'UTF-8'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8'); ?></li>
    </ol>

    <?php if ($showNewTicketButton): ?>
      <a class="btn btn-primary my-3" href="<?php echo htmlspecialchars($newTicketHref, ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fa fa-plus"></i> <?php echo htmlspecialchars($newTicketLabel, ENT_QUOTES, 'UTF-8'); ?>
      </a>
    <?php endif; ?>

    <?php if ($bannerMessage !== ''): ?>
      <div class="alert alert-<?php echo htmlspecialchars($bannerType, ENT_QUOTES, 'UTF-8'); ?> mb-3"><?php echo htmlspecialchars($bannerMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card mb-3">
      <div class="card-body">
        <?php if (empty($tickets)): ?>
          <div class="alert alert-info mb-0"><?php echo htmlspecialchars($emptyMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover table-mobile-stack" id="dataTable" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th><?php echo htmlspecialchars(i18n_t('admin.subject', 'Subject'), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(i18n_t('admin.requester', 'Requester'), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(i18n_t('admin.team', 'Team'), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(i18n_t('admin.agent', 'Agent'), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(i18n_t('admin.status', 'Status'), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(i18n_t('admin.created_at', 'Created At'), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(i18n_t('admin.action', 'Action'), ENT_QUOTES, 'UTF-8'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($tickets as $ticketItem): ?>
                  <?php
                    $requesterObj = Requester::find($ticketItem->requester);
                    $teamObj = !empty($ticketItem->team) ? Team::find($ticketItem->team) : null;
                    $agentObj = !empty($ticketItem->team_member) ? User::find($ticketItem->team_member) : null;
                    $requesterName = $requesterObj ? $requesterObj->name : i18n_t('common.none', 'None');
                    $teamName = $teamObj ? $teamObj->name : i18n_t('common.none', 'None');
                    $agentName = $agentObj ? $agentObj->name : i18n_t('common.none', 'None');
                    $statusValue = strtolower((string)($ticketItem->status ?? ''));
                    $badgeClass = 'secondary';
                    if ($statusValue === 'open') {
                        $badgeClass = 'danger';
                    } elseif ($statusValue === 'pending') {
                        $badgeClass = 'warning';
                    } elseif ($statusValue === 'solved') {
                        $badgeClass = 'success';
                    } elseif ($statusValue === 'closed') {
                        $badgeClass = 'info';
                    }
                    $date = new DateTime($ticketItem->created_at);
                  ?>
                  <tr>
                    <td data-label="<?php echo htmlspecialchars(i18n_t('admin.subject', 'Subject'), ENT_QUOTES, 'UTF-8'); ?>">
                      <a href="./ticket-details.php?id=<?php echo (int)$ticketItem->id; ?>"><?php echo htmlspecialchars($ticketItem->title, ENT_QUOTES, 'UTF-8'); ?></a>
                    </td>
                    <td data-label="<?php echo htmlspecialchars(i18n_t('admin.requester', 'Requester'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($requesterName, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td data-label="<?php echo htmlspecialchars(i18n_t('admin.team', 'Team'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td data-label="<?php echo htmlspecialchars(i18n_t('admin.agent', 'Agent'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($agentName, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td data-label="<?php echo htmlspecialchars(i18n_t('admin.status', 'Status'), ENT_QUOTES, 'UTF-8'); ?>">
                      <span class="badge badge-<?php echo $badgeClass; ?>"><?php echo htmlspecialchars(i18n_status_label($statusValue), ENT_QUOTES, 'UTF-8'); ?></span>
                    </td>
                    <td data-label="<?php echo htmlspecialchars(i18n_t('admin.created_at', 'Created At'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($date->format('d-m-Y H:i:s'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td data-label="<?php echo htmlspecialchars(i18n_t('admin.action', 'Action'), ENT_QUOTES, 'UTF-8'); ?>" width="120">
                      <div class="btn-group" role="group">
                        <button id="ticketAction<?php echo (int)$ticketItem->id; ?>" type="button" class="btn btn-outline-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <?php echo htmlspecialchars(i18n_t('common.action', 'Action'), ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="ticketAction<?php echo (int)$ticketItem->id; ?>">
                          <a class="dropdown-item" href="./ticket-details.php?id=<?php echo (int)$ticketItem->id; ?>"><?php echo htmlspecialchars(i18n_t('common.view', 'View'), ENT_QUOTES, 'UTF-8'); ?></a>
                          <?php if ($allowDelete): ?>
                            <a class="dropdown-item" href="?del=<?php echo (int)$ticketItem->id; ?>" onclick="return confirm(<?php echo json_encode(i18n_t('admin.ticket_delete_confirm', 'Are you sure you want to delete this ticket?')); ?>)"><?php echo htmlspecialchars(i18n_t('common.delete', 'Delete'), ENT_QUOTES, 'UTF-8'); ?></a>
                          <?php endif; ?>
                        </div>
                      </div>
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
