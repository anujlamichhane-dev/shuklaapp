<?php
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header('Location: ' . appUrl('mytickets.php'));
    exit();
}

include './header.php';
require_once './src/requester.php';
require_once './src/team.php';
require_once './src/ticket.php';
require_once './src/ticket-event.php';
require_once './src/team-member.php';
require_once './src/comment.php';
require_once './src/service-request.php';

$ticketId = (int)$_GET['id'];
$ticket = Ticket::find($ticketId);
if (!$ticket) {
    header('Location: ' . appUrl('mytickets.php'));
    exit();
}

$requesterOwner = Requester::find($ticket->requester);
if (!$requesterOwner) {
    $requesterOwner = (object) ['name' => 'Resident', 'email' => '', 'phone' => ''];
}
$isClient = (($user->role ?? '') === 'client');
$isGuest = (($user->role ?? '') === 'guest');
$canManageTicket = !($isClient || $isGuest);

$requesterEmail = strtolower(trim((string)($requesterOwner->email ?? '')));
$sessionEmail = strtolower(trim((string)($user->email ?? '')));
$requesterUserId = isset($requesterOwner->user_id) ? (int)$requesterOwner->user_id : 0;
$sessionUserId = isset($user->id) ? (int)$user->id : 0;
$ownsByUserId = $isClient && $requesterUserId > 0 && $requesterUserId === $sessionUserId;
$ownsByEmail = $isClient && $requesterEmail !== '' && $requesterEmail === $sessionEmail;
$ownsByGuestSession = $isGuest && guestOwnsTicket($ticket->id);
$isRequesterOwner = $ownsByUserId || $ownsByEmail || $ownsByGuestSession;

if (($isClient || $isGuest) && !$isRequesterOwner) {
    header('Location: ' . appUrl('mytickets.php'));
    exit();
}

$ticketData = service_request_ticket_data($ticket);
$teams = Team::findAll();
$teamMembers = TeamMember::findAll();
$statusOptions = service_request_status_options();
$priorityOptions = service_request_priority_options();
$categoryOptions = service_request_categories();
$comments = Comment::findByTicket($ticket->id);
$events = Event::findByTicket($ticket->id);
$commentDraft = '';
$err = '';
$msg = '';

$redirectToSelf = function (string $flag = '') use ($ticket) {
    $target = 'ticket-details.php?id=' . (int)$ticket->id;
    if ($flag !== '') {
        $target .= '&' . rawurlencode($flag) . '=1';
    }
    header('Location: ' . appUrl($target));
    exit();
};

if (isset($_GET['created'])) {
    $msg = 'Case submitted successfully.';
} elseif (isset($_GET['updated'])) {
    $msg = 'Update added successfully.';
} elseif (isset($_GET['assignment_saved'])) {
    $msg = 'Case ownership updated.';
} elseif (isset($_GET['status_saved'])) {
    $msg = 'Case status updated.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_update'])) {
    csrf_require_valid_request();
    $commentDraft = trim((string)($_POST['body'] ?? ''));

    try {
        if ($commentDraft === '') {
            throw new Exception('Please enter an update before submitting.');
        }

        $comment = new Comment([
            'ticket-id' => $ticket->id,
            'team-member' => $canManageTicket ? (int)($user->id ?? 0) : 0,
            'body' => $commentDraft,
        ]);
        $comment->save();

        try {
            $event = new Event([
                'ticket' => $ticket->id,
                'user' => $canManageTicket ? (int)($user->id ?? 0) : 0,
                'body' => $canManageTicket ? 'Staff posted a case update.' : 'Resident added more details.',
            ]);
            $event->save();
        } catch (Throwable $eventError) {
            error_log('Case update event logging failed for case ' . (int)$ticket->id . ': ' . $eventError->getMessage());
        }

        $redirectToSelf('updated');
    } catch (Throwable $e) {
        $err = $e->getMessage() ?: 'Could not save the update.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManageTicket && isset($_POST['save_assignment'])) {
    csrf_require_valid_request();

    $selectedTeam = isset($_POST['team']) && ctype_digit((string)$_POST['team']) ? (int)$_POST['team'] : null;
    $selectedMember = isset($_POST['team_member']) && ctype_digit((string)$_POST['team_member']) ? (int)$_POST['team_member'] : null;
    $selectedPriority = trim((string)($_POST['priority'] ?? $ticket->priority));

    try {
        if (!isset($priorityOptions[$selectedPriority])) {
            throw new Exception('Please choose a valid case priority.');
        }

        if ($selectedMember !== null) {
            $memberRow = TeamMember::find($selectedMember);
            if (!$memberRow) {
                throw new Exception('Please choose a valid staff member.');
            }
            if ($selectedTeam === null || $selectedTeam < 1) {
                $selectedTeam = (int)$memberRow->team;
            }
        }

        $updatedTicket = new Ticket([
            'team_member' => $selectedMember,
            'title' => $ticket->title,
            'body' => $ticket->body,
            'requester' => $ticket->requester,
            'team' => $selectedTeam,
            'status' => $ticket->status,
            'priority' => $selectedPriority,
        ]);
        $updatedTicket->update($ticket->id);

        try {
            if ((string)$ticket->team !== (string)$selectedTeam) {
                $teamObj = $selectedTeam ? Team::find($selectedTeam) : null;
                (new Event([
                    'ticket' => $ticket->id,
                    'user' => (int)($user->id ?? 0),
                    'body' => 'Routed to ' . trim((string)($teamObj->name ?? 'a new team')) . '.',
                ]))->save();
            }

            if ((string)$ticket->team_member !== (string)$selectedMember) {
                $memberName = $selectedMember ? TeamMember::getName($selectedMember) : 'No staff member assigned';
                (new Event([
                    'ticket' => $ticket->id,
                    'user' => (int)($user->id ?? 0),
                    'body' => $selectedMember ? ('Assigned to ' . $memberName . '.') : 'Assignment cleared.',
                ]))->save();
            }

            if ((string)$ticket->priority !== (string)$selectedPriority) {
                (new Event([
                    'ticket' => $ticket->id,
                    'user' => (int)($user->id ?? 0),
                    'body' => 'Priority changed to ' . service_request_priority_map()[$selectedPriority]['label'] . '.',
                ]))->save();
            }
        } catch (Throwable $eventError) {
            error_log('Case assignment event logging failed for case ' . (int)$ticket->id . ': ' . $eventError->getMessage());
        }

        $redirectToSelf('assignment_saved');
    } catch (Throwable $e) {
        $err = $e->getMessage() ?: 'Could not update case ownership.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManageTicket && isset($_POST['change_status'])) {
    csrf_require_valid_request();
    $nextStatus = trim((string)($_POST['status'] ?? ''));

    try {
        if (!isset($statusOptions[$nextStatus])) {
            throw new Exception('Please choose a valid case status.');
        }

        if ($nextStatus !== (string)$ticket->status) {
            Ticket::changeStatus($ticket->id, $nextStatus);
            try {
                (new Event([
                    'ticket' => $ticket->id,
                    'user' => (int)($user->id ?? 0),
                    'body' => 'Status changed to ' . $statusOptions[$nextStatus] . '.',
                ]))->save();
            } catch (Throwable $eventError) {
                error_log('Case status event logging failed for case ' . (int)$ticket->id . ': ' . $eventError->getMessage());
            }
        }

        $redirectToSelf('status_saved');
    } catch (Throwable $e) {
        $err = $e->getMessage() ?: 'Could not update the case status.';
    }
}

$ticket = Ticket::find($ticketId);
$ticketData = service_request_ticket_data($ticket);
$comments = Comment::findByTicket($ticket->id);
$events = Event::findByTicket($ticket->id);
$selectedTeamId = (int)($ticket->team ?? 0);
$selectedMemberId = (int)($ticket->team_member ?? 0);
$submittedAt = !empty($ticket->created_at) ? new DateTime($ticket->created_at) : null;
$teamName = $selectedTeamId > 0 && ($teamObj = Team::find($selectedTeamId)) ? $teamObj->name : 'Not assigned yet';
$memberName = $selectedMemberId > 0 ? TeamMember::getName($selectedMemberId) : 'Not assigned yet';
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <div class="case-shell">
      <section class="case-hero">
        <div>
          <div class="case-kicker">Case file</div>
          <h1><?php echo htmlspecialchars($ticketData['case_number'], ENT_QUOTES, 'UTF-8'); ?></h1>
          <p class="case-title"><?php echo htmlspecialchars((string)$ticket->title, ENT_QUOTES, 'UTF-8'); ?></p>
          <div class="case-meta-line">
            <?php echo service_request_status_badge((string)$ticket->status); ?>
            <span><?php echo service_request_priority_badge((string)$ticket->priority); ?></span>
            <?php if ($submittedAt instanceof DateTime): ?>
              <span class="text-muted">Submitted <?php echo htmlspecialchars($submittedAt->format('d M Y, H:i'), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="case-hero-panel">
          <strong><?php echo htmlspecialchars($ticketData['status_label'], ENT_QUOTES, 'UTF-8'); ?></strong>
          <span><?php echo htmlspecialchars($ticketData['status_summary'], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </section>

      <?php if ($err !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <?php if ($msg !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <section class="case-grid">
        <article class="case-card">
          <h2>Resident summary</h2>
          <dl class="case-summary-list">
            <div><dt>Name</dt><dd><?php echo htmlspecialchars((string)($requesterOwner->name ?? 'Resident'), ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Email</dt><dd><?php echo htmlspecialchars((string)($requesterOwner->email ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Phone</dt><dd><?php echo htmlspecialchars((string)($requesterOwner->phone ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Preferred contact</dt><dd><?php echo htmlspecialchars($ticketData['contact_window_label'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
          </dl>
        </article>

        <article class="case-card">
          <h2>Service summary</h2>
          <dl class="case-summary-list">
            <div><dt>Service area</dt><dd><?php echo htmlspecialchars($ticketData['category_label'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Location</dt><dd><?php echo htmlspecialchars($ticketData['location'] !== '' ? $ticketData['location'] : 'Not provided', ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Urgency</dt><dd><?php echo htmlspecialchars($ticketData['urgency_label'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Reference</dt><dd><?php echo htmlspecialchars($ticketData['reference_hint'] !== '' ? $ticketData['reference_hint'] : 'None', ENT_QUOTES, 'UTF-8'); ?></dd></div>
          </dl>
        </article>

        <article class="case-card">
          <h2>Assignment</h2>
          <dl class="case-summary-list">
            <div><dt>Team</dt><dd><?php echo htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Staff member</dt><dd><?php echo htmlspecialchars($memberName, ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Priority</dt><dd><?php echo htmlspecialchars($ticketData['priority_label'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Status</dt><dd><?php echo htmlspecialchars($ticketData['status_label'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
          </dl>
        </article>
      </section>

      <section class="case-card">
        <h2>Resident description</h2>
        <p class="case-description"><?php echo nl2br(htmlspecialchars($ticketData['details'], ENT_QUOTES, 'UTF-8')); ?></p>
      </section>

      <?php if ($canManageTicket): ?>
        <section class="case-grid">
          <article class="case-card">
            <h2>Routing and ownership</h2>
            <form method="POST">
              <?php echo csrf_input(); ?>
              <div class="form-group">
                <label for="case-team">Team</label>
                <select class="form-control" id="case-team" name="team">
                  <option value="">Not assigned</option>
                  <?php foreach ($teams as $team): ?>
                    <option value="<?php echo (int)$team->id; ?>" <?php echo $selectedTeamId === (int)$team->id ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars((string)$team->name, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label for="case-member">Staff member</label>
                <select class="form-control" id="case-member" name="team_member">
                  <option value="">Not assigned</option>
                  <?php foreach ($teamMembers as $member): ?>
                    <?php
                      $memberTeam = !empty($member->team) ? Team::find((int)$member->team) : null;
                      $memberLabel = TeamMember::getName((int)$member->id);
                      if ($memberLabel === '' && !empty($member->user)) {
                          $memberLabel = TeamMember::getName((int)$member->user);
                      }
                      $memberTeamName = trim((string)($memberTeam->name ?? ''));
                    ?>
                    <option value="<?php echo (int)$member->id; ?>" <?php echo $selectedMemberId === (int)$member->id ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars(trim($memberLabel . ($memberTeamName !== '' ? ' - ' . $memberTeamName : '')), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label for="case-priority">Priority</label>
                <select class="form-control" id="case-priority" name="priority">
                  <?php foreach ($priorityOptions as $priorityValue => $priorityLabel): ?>
                    <option value="<?php echo htmlspecialchars($priorityValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string)$ticket->priority === $priorityValue ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($priorityLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button type="submit" name="save_assignment" class="btn btn-primary">Save ownership</button>
            </form>
          </article>

          <article class="case-card">
            <h2>Case status</h2>
            <form method="POST">
              <?php echo csrf_input(); ?>
              <div class="form-group">
                <label for="case-status">Current status</label>
                <select class="form-control" id="case-status" name="status">
                  <?php foreach ($statusOptions as $statusValue => $statusLabel): ?>
                    <option value="<?php echo htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string)$ticket->status === $statusValue ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button type="submit" name="change_status" class="btn btn-outline-primary">Update status</button>
            </form>
          </article>
        </section>
      <?php endif; ?>

      <section class="case-card">
        <h2><?php echo $canManageTicket ? 'Post a case update' : 'Add more details'; ?></h2>
        <form method="POST">
          <?php echo csrf_input(); ?>
          <div class="form-group">
            <textarea class="form-control" name="body" rows="5" placeholder="<?php echo $canManageTicket ? 'Write a clear status note for the resident or the internal team.' : 'Share any new details that will help staff understand the situation.'; ?>"><?php echo htmlspecialchars($commentDraft, ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>
          <button type="submit" name="add_update" class="btn btn-primary"><?php echo $canManageTicket ? 'Post update' : 'Send update'; ?></button>
        </form>
      </section>

      <section class="case-grid">
        <article class="case-card">
          <h2>Conversation</h2>
          <div class="case-feed">
            <div class="case-feed-item">
              <strong><?php echo htmlspecialchars((string)($requesterOwner->name ?? 'Resident'), ENT_QUOTES, 'UTF-8'); ?></strong>
              <p><?php echo nl2br(htmlspecialchars($ticketData['details'], ENT_QUOTES, 'UTF-8')); ?></p>
              <?php if ($submittedAt instanceof DateTime): ?>
                <span><?php echo htmlspecialchars($submittedAt->format('d M Y, H:i'), ENT_QUOTES, 'UTF-8'); ?></span>
              <?php endif; ?>
            </div>
            <?php foreach ($comments as $comment): ?>
              <?php $commentDate = !empty($comment->created_at) ? new DateTime($comment->created_at) : null; ?>
              <div class="case-feed-item">
                <strong><?php echo htmlspecialchars(service_request_actor_name((int)$comment->team_member, $requesterOwner), ENT_QUOTES, 'UTF-8'); ?></strong>
                <p><?php echo nl2br(htmlspecialchars((string)$comment->body, ENT_QUOTES, 'UTF-8')); ?></p>
                <?php if ($commentDate instanceof DateTime): ?>
                  <span><?php echo htmlspecialchars($commentDate->format('d M Y, H:i'), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </article>

        <article class="case-card">
          <h2>Activity log</h2>
          <div class="case-feed">
            <?php if (empty($events)): ?>
              <div class="case-feed-item">
                <strong>System</strong>
                <p>No staff activity has been logged yet.</p>
              </div>
            <?php else: ?>
              <?php foreach ($events as $event): ?>
                <?php $eventDate = !empty($event->created_at) ? new DateTime($event->created_at) : null; ?>
                <div class="case-feed-item">
                  <strong><?php echo htmlspecialchars(service_request_actor_name((int)$event->user, $requesterOwner), ENT_QUOTES, 'UTF-8'); ?></strong>
                  <p><?php echo htmlspecialchars((string)$event->body, ENT_QUOTES, 'UTF-8'); ?></p>
                  <?php if ($eventDate instanceof DateTime): ?>
                    <span><?php echo htmlspecialchars($eventDate->format('d M Y, H:i'), ENT_QUOTES, 'UTF-8'); ?></span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </article>
      </section>
    </div>
  </div>
</div>

<style>
  .case-shell {
    max-width: 1080px;
    margin: 0 auto;
    display: grid;
    gap: 1rem;
  }
  .case-hero,
  .case-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid rgba(18, 46, 77, 0.08);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
  }
  .case-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.8fr) minmax(250px, 1fr);
    gap: 1rem;
    padding: 1.5rem;
  }
  .case-kicker {
    text-transform: uppercase;
    letter-spacing: .12em;
    font-size: .8rem;
    font-weight: 700;
    color: #2f6fed;
    margin-bottom: .4rem;
  }
  .case-title {
    font-size: 1.15rem;
    color: #516277;
    margin-bottom: .75rem;
  }
  .case-meta-line {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    align-items: center;
  }
  .case-hero-panel {
    border-radius: 18px;
    background: linear-gradient(180deg, #f4f8fb 0%, #eef4f8 100%);
    padding: 1rem;
    display: grid;
    gap: .35rem;
    align-content: start;
  }
  .case-hero-panel strong {
    color: #12324d;
  }
  .case-hero-panel span {
    color: #5f7085;
    line-height: 1.5;
  }
  .case-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
  }
  .case-grid .case-card {
    padding: 1.25rem;
  }
  .case-card {
    padding: 1.25rem;
  }
  .case-card h2 {
    font-size: 1.05rem;
    margin-bottom: .9rem;
    color: #12324d;
  }
  .case-summary-list {
    display: grid;
    gap: .8rem;
    margin: 0;
  }
  .case-summary-list div {
    display: grid;
    gap: .15rem;
  }
  .case-summary-list dt {
    font-size: .8rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #71839a;
  }
  .case-summary-list dd {
    margin: 0;
    color: #18324a;
    font-weight: 600;
  }
  .case-description {
    margin: 0;
    color: #435569;
    line-height: 1.7;
  }
  .case-feed {
    display: grid;
    gap: .75rem;
  }
  .case-feed-item {
    border-radius: 16px;
    background: #f7fafc;
    padding: 1rem;
    border: 1px solid rgba(18, 46, 77, 0.08);
  }
  .case-feed-item strong {
    display: block;
    color: #12324d;
    margin-bottom: .35rem;
  }
  .case-feed-item p {
    margin-bottom: .35rem;
    color: #46586c;
    line-height: 1.6;
  }
  .case-feed-item span {
    display: block;
    font-size: .9rem;
    color: #71839a;
  }
  .case-card .form-control {
    border-radius: 12px;
  }
  .case-card textarea.form-control {
    min-height: 150px;
  }
  @media (max-width: 992px) {
    .case-grid {
      grid-template-columns: 1fr;
    }
    .case-hero {
      grid-template-columns: 1fr;
    }
  }
</style>

<?php include './footer.php'; ?>
