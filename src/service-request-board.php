<?php

require_once __DIR__ . '/ticket.php';
require_once __DIR__ . '/requester.php';
require_once __DIR__ . '/team.php';
require_once __DIR__ . '/service-request.php';

function service_request_fetch_board_tickets(string $mode): array
{
    $config = service_request_admin_page_config($mode);
    if (!empty($config['unassigned'])) {
        return (new Ticket())->unassigned();
    }
    if (!empty($config['filter'])) {
        return Ticket::findByStatus($config['filter']);
    }

    return Ticket::findAll();
}

function service_request_board_stats(array $tickets): array
{
    $stats = [
        'total' => count($tickets),
        'open' => 0,
        'pending' => 0,
        'solved' => 0,
        'closed' => 0,
        'unassigned' => 0,
    ];

    foreach ($tickets as $ticket) {
        $status = strtolower(trim((string)($ticket->status ?? '')));
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
        $assignee = trim((string)($ticket->team_member ?? ''));
        if ($assignee === '') {
            $stats['unassigned']++;
        }
    }

    return $stats;
}

function service_request_render_board(array $tickets, string $emptyMessage = 'No service requests found.'): string
{
    if (empty($tickets)) {
        return '<div class="alert alert-info mb-0">' . htmlspecialchars($emptyMessage, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    ob_start();
    ?>
    <div class="table-responsive tickets-table">
      <table class="table table-bordered table-hover table-sm table-mobile-stack" id="dataTable" width="100%" cellspacing="0">
        <thead>
          <tr>
            <th>Case</th>
            <th>Resident</th>
            <th>Service</th>
            <th>Assigned team</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Submitted</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tickets as $ticket): ?>
            <?php
              $ticketData = service_request_ticket_data($ticket);
              $requesterObj = Requester::find($ticket->requester);
              $teamObj = (!empty($ticket->team) && (int)$ticket->team > 0) ? Team::find($ticket->team) : null;
              $residentName = trim((string)($requesterObj->name ?? 'Resident'));
              $teamName = trim((string)($teamObj->name ?? 'Unassigned'));
              $submittedAt = !empty($ticket->created_at) ? new DateTime($ticket->created_at) : null;
            ?>
            <tr>
              <td data-label="Case">
                <a href="./ticket-details.php?id=<?php echo (int)$ticket->id; ?>">
                  <?php echo htmlspecialchars($ticketData['case_number'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <div class="small text-muted"><?php echo htmlspecialchars((string)$ticket->title, ENT_QUOTES, 'UTF-8'); ?></div>
              </td>
              <td data-label="Resident"><?php echo htmlspecialchars($residentName, ENT_QUOTES, 'UTF-8'); ?></td>
              <td data-label="Service">
                <?php echo htmlspecialchars($ticketData['category_label'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if ($ticketData['location'] !== ''): ?>
                  <div class="small text-muted"><?php echo htmlspecialchars($ticketData['location'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
              </td>
              <td data-label="Assigned team"><?php echo htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8'); ?></td>
              <td data-label="Status"><?php echo service_request_status_badge((string)$ticket->status); ?></td>
              <td data-label="Priority"><?php echo service_request_priority_badge((string)$ticket->priority); ?></td>
              <td data-label="Submitted">
                <?php echo $submittedAt instanceof DateTime ? htmlspecialchars($submittedAt->format('d M Y, H:i'), ENT_QUOTES, 'UTF-8') : ''; ?>
              </td>
              <td data-label="Action">
                <a class="btn btn-outline-primary btn-sm btn-block" href="./ticket-details.php?id=<?php echo (int)$ticket->id; ?>">Open case</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php

    return ob_get_clean();
}
