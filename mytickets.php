<?php
include './header.php';
require_once './src/ticket.php';
require_once './src/requester.php';
require_once './src/team.php';
require_once './src/team-member.php';
require_once './src/service-request.php';

$isClient = (($user->role ?? '') === 'client');
$isGuest = (($user->role ?? '') === 'guest');

if ($isClient) {
    $tickets = Ticket::findByRequesterUserId($user->id);
    if (empty($tickets) && !empty($user->email)) {
        $tickets = Ticket::findByRequesterEmail($user->email);
    }
} elseif ($isGuest) {
    $tickets = [];
    foreach (array_reverse(getGuestTicketIds()) as $guestTicketId) {
        $ticket = Ticket::find($guestTicketId);
        if ($ticket) {
            $tickets[] = $ticket;
        }
    }
} else {
    $tickets = Ticket::findByMember($user->id);
}

$statusFilter = '';
if (isset($_GET['status']) && is_string($_GET['status'])) {
    $candidate = strtolower(trim($_GET['status']));
    if (isset(service_request_status_options()[$candidate])) {
        $statusFilter = $candidate;
    }
}

if ($statusFilter !== '') {
    $tickets = array_values(array_filter($tickets, function ($ticket) use ($statusFilter) {
        return strtolower(trim((string)($ticket->status ?? ''))) === $statusFilter;
    }));
}
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <div class="list-shell">
      <section class="list-hero">
        <div>
          <div class="list-kicker">Citizen Case Tracker</div>
          <h1><?php echo $isGuest ? 'Session cases' : 'My service requests'; ?></h1>
          <p><?php echo $isGuest ? 'These cases are available for this guest session on this device.' : 'Review current status, assigned team, and updates for your municipal cases.'; ?></p>
        </div>
        <div class="list-actions">
          <a href="<?php echo htmlspecialchars(appUrl('ticket.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Open a new case</a>
        </div>
      </section>

      <?php if (empty($tickets)): ?>
        <div class="alert alert-info mb-0"><?php echo htmlspecialchars(i18n_t('mytickets.empty'), ENT_QUOTES, 'UTF-8'); ?></div>
      <?php else: ?>
        <div class="table-responsive tickets-table list-table-card">
          <table class="table table-bordered table-hover table-sm table-mobile-stack" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>Case</th>
                <th>Service</th>
                <th>Assigned team</th>
                <th>Staff member</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tickets as $ticket): ?>
                <?php
                  $ticketData = service_request_ticket_data($ticket);
                  $teamObj = (!empty($ticket->team) && (int)$ticket->team > 0) ? Team::find($ticket->team) : null;
                  $teamName = trim((string)($teamObj->name ?? 'Not assigned yet'));
                  $agentName = $ticket->team_member ? TeamMember::getName((int)$ticket->team_member) : 'Not assigned yet';
                  $submittedAt = !empty($ticket->created_at) ? new DateTime($ticket->created_at) : null;
                ?>
                <tr>
                  <td data-label="Case">
                    <a href="./ticket-details.php?id=<?php echo (int)$ticket->id; ?>">
                      <?php echo htmlspecialchars($ticketData['case_number'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <div class="small text-muted"><?php echo htmlspecialchars((string)$ticket->title, ENT_QUOTES, 'UTF-8'); ?></div>
                  </td>
                  <td data-label="Service">
                    <?php echo htmlspecialchars($ticketData['category_label'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($ticketData['location'] !== ''): ?>
                      <div class="small text-muted"><?php echo htmlspecialchars($ticketData['location'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                  </td>
                  <td data-label="Assigned team"><?php echo htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="Staff member"><?php echo htmlspecialchars($agentName, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="Status"><?php echo service_request_status_badge((string)$ticket->status); ?></td>
                  <td data-label="Submitted"><?php echo $submittedAt instanceof DateTime ? htmlspecialchars($submittedAt->format('d M Y, H:i'), ENT_QUOTES, 'UTF-8') : ''; ?></td>
                  <td data-label="Action"><a class="btn btn-outline-primary btn-sm btn-block" href="./ticket-details.php?id=<?php echo (int)$ticket->id; ?>">Open case</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
  .list-shell {
    max-width: 1080px;
    margin: 0 auto;
    display: grid;
    gap: 1rem;
  }
  .list-hero,
  .list-table-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid rgba(18, 46, 77, 0.08);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
  }
  .list-hero {
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 1rem;
  }
  .list-kicker {
    text-transform: uppercase;
    letter-spacing: .12em;
    font-size: .8rem;
    font-weight: 700;
    color: #2f6fed;
    margin-bottom: .4rem;
  }
  .list-hero h1 {
    margin-bottom: .35rem;
    color: #12324d;
  }
  .list-hero p {
    margin: 0;
    color: #5f7085;
    line-height: 1.6;
  }
  .list-table-card {
    padding: 1rem;
  }
  @media (max-width: 768px) {
    .list-hero {
      flex-direction: column;
      align-items: stretch;
    }
    .list-actions .btn {
      width: 100%;
    }
  }
</style>

<?php include './footer.php'; ?>
