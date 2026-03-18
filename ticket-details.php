<?php
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header('Location: ./mytickets.php');
    exit();
}

require_once './src/i18n.php';
$pageTitle = i18n_t('ticketdetails.title', 'Ticket details');
include './header.php';

require_once './src/requester.php';
require_once './src/team.php';
require_once './src/ticket.php';
require_once './src/ticket-event.php';
require_once './src/team-member.php';
require_once './src/comment.php';

$err = '';
$msg = '';
$ticket = Ticket::find((int)$_GET['id']);

if (!$ticket) {
    header('Location: ./mytickets.php');
    exit();
}

$requesterOwner = Requester::find($ticket->requester);
$isClientOwner = ($isClient && $requesterOwner && $requesterOwner->email === $user->email);
if ($isClient && !$isClientOwner) {
    header('Location: ./mytickets.php');
    exit();
}

$teams = Team::findAll();
$events = Event::findByTicket($ticket->id);
$comments = Comment::findByTicket($ticket->id);

if (isset($_POST['submit']) && !$isClient) {
    $teamMemberId = $_POST['team_member'] ?? '';
    $teamValue = $_POST['team'] ?? $ticket->team;

    try {
        $updatedTicket = new Ticket([
            'team_member' => $teamMemberId,
            'title' => $ticket->title,
            'body' => $ticket->body,
            'requester' => $ticket->requester,
            'team' => ctype_digit((string)$teamValue) ? $teamValue : $ticket->team,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
        ]);

        $ticket = $updatedTicket->update($ticket->id);
        $msg = i18n_t('ticketdetails.assigned_success', 'Ticket assigned successfully');
    } catch (Exception $e) {
        $err = i18n_t('ticketdetails.assigned_failed', 'Failed to assign ticket');
    }
}

if (isset($_POST['comment'])) {
    $body = trim($_POST['body'] ?? '');

    try {
        $comment = new Comment([
            'ticket-id' => $ticket->id,
            'team-member' => $ticket->team_member,
            'body' => $body,
        ]);
        $comment->save();
        $msg = i18n_t('ticketdetails.comment_success', 'Comment saved successfully');
        $comments = Comment::findByTicket($ticket->id);
    } catch (Exception $e) {
        $err = i18n_t('ticketdetails.comment_failed', 'Failed to comment on the ticket');
    }
}

$assignedUser = !empty($ticket->team_member) ? TeamMember::getName($ticket->team_member) : i18n_t('common.none', 'None');
$teamName = !empty($ticket->team) && Team::find($ticket->team) ? Team::find($ticket->team)->name : i18n_t('common.none', 'None');
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="./dashboard.php"><?php echo htmlspecialchars(i18n_t('common.dashboard', 'Dashboard'), ENT_QUOTES, 'UTF-8'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo htmlspecialchars(i18n_t('ticketdetails.title', 'Ticket details'), ENT_QUOTES, 'UTF-8'); ?></li>
    </ol>

    <div class="card mb-3">
      <div class="card-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
          <div>
            <?php echo $ticket->displayStatusBadge(); ?>
            <small class="text-info ml-2">
              <?php echo htmlspecialchars($ticket->title, ENT_QUOTES, 'UTF-8'); ?>
              <span class="text-muted">
                <?php $createdDate = new DateTime($ticket->created_at ?? 'now'); ?>
                <?php echo htmlspecialchars($createdDate->format('d-m-Y H:i:s'), ENT_QUOTES, 'UTF-8'); ?>
              </span>
            </small>
          </div>
          <small class="text-muted"><?php echo htmlspecialchars(i18n_t('ticketform.priority', 'Priority'), ENT_QUOTES, 'UTF-8'); ?>: <?php echo htmlspecialchars(i18n_priority_label($ticket->priority), ENT_QUOTES, 'UTF-8'); ?></small>
        </div>
      </div>
      <div class="card-body">
        <?php if ($err !== ''): ?>
          <div class="alert alert-danger text-center my-3" role="alert"><strong><?php echo htmlspecialchars(i18n_t('common.failed', 'Failed!'), ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($msg !== ''): ?>
          <div class="alert alert-success text-center my-3" role="alert"><strong><?php echo htmlspecialchars(i18n_t('common.success', 'Success!'), ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="row">
          <div class="col-lg-8">
            <dl class="row mb-0">
              <dt class="col-sm-3"><?php echo htmlspecialchars(i18n_t('common.requester', 'Requester'), ENT_QUOTES, 'UTF-8'); ?></dt>
              <dd class="col-sm-9"><?php echo htmlspecialchars($requesterOwner ? $requesterOwner->name : i18n_t('common.none', 'None'), ENT_QUOTES, 'UTF-8'); ?></dd>

              <dt class="col-sm-3"><?php echo htmlspecialchars(i18n_t('ticketdetails.assign_team', 'Team'), ENT_QUOTES, 'UTF-8'); ?></dt>
              <dd class="col-sm-9"><?php echo htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8'); ?></dd>

              <dt class="col-sm-3"><?php echo htmlspecialchars(i18n_t('ticketdetails.assigned', 'Assigned'), ENT_QUOTES, 'UTF-8'); ?></dt>
              <dd class="col-sm-9"><?php echo htmlspecialchars($assignedUser, ENT_QUOTES, 'UTF-8'); ?></dd>

              <dt class="col-sm-3"><?php echo htmlspecialchars(i18n_t('ticketform.comment', 'Comment'), ENT_QUOTES, 'UTF-8'); ?></dt>
              <dd class="col-sm-9" style="white-space:pre-wrap;"><?php echo nl2br(htmlspecialchars($ticket->body, ENT_QUOTES, 'UTF-8')); ?></dd>
            </dl>
          </div>
        </div>
      </div>
    </div>

    <?php if (!$isClient): ?>
      <div class="card mb-3">
        <div class="card-body">
          <form method="post">
            <div class="form-group row">
              <label for="team-dropdown" class="col-sm-3 col-form-label"><?php echo htmlspecialchars(i18n_t('ticketdetails.assign_team', 'Team'), ENT_QUOTES, 'UTF-8'); ?></label>
              <div class="col-sm-8">
                <select class="form-control" id="team-dropdown" name="team" onchange="getTeamMember(event.target.value)">
                  <option value=""><?php echo htmlspecialchars(i18n_t('ticketdetails.select', '--select--'), ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php foreach ($teams as $team): ?>
                    <option value="<?php echo (int)$team->id; ?>" <?php echo (string)$team->id === (string)$ticket->team ? 'selected' : ''; ?>><?php echo htmlspecialchars($team->name, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="form-group row">
              <label for="team-member-dropdown" class="col-sm-3 col-form-label"><?php echo htmlspecialchars(i18n_t('ticketdetails.assigned', 'Assigned'), ENT_QUOTES, 'UTF-8'); ?></label>
              <div class="col-sm-8">
                <select class="form-control" name="team_member" id="team-member-dropdown">
                  <option value="none"><?php echo htmlspecialchars(i18n_t('ticketdetails.select', '--select--'), ENT_QUOTES, 'UTF-8'); ?></option>
                </select>
              </div>
            </div>
            <div class="text-center">
              <button class="btn btn-primary" type="submit" name="submit"><?php echo htmlspecialchars(i18n_t('ticketdetails.assign', 'Assign'), ENT_QUOTES, 'UTF-8'); ?></button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="card mb-3">
      <div class="card-body">
        <form method="POST" action="">
          <div class="form-group row">
            <label for="ticket-comment-body" class="col-sm-2 col-form-label"><?php echo htmlspecialchars(i18n_t('ticketdetails.comment', 'Comment'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="col-sm-8">
              <textarea class="form-control" id="ticket-comment-body" name="body"></textarea>
            </div>
          </div>
          <div class="text-center">
            <button type="submit" name="comment" class="btn btn-success"><?php echo htmlspecialchars(i18n_t('ticketdetails.comment_button', 'Comment'), ENT_QUOTES, 'UTF-8'); ?></button>
          </div>
        </form>
      </div>
    </div>

    <?php if (!$isClient): ?>
      <div class="card mb-3">
        <div class="card-body">
          <form id="formData" enctype="multipart/form-data" method="POST">
            <label for="status" class="d-block"><?php echo htmlspecialchars(i18n_t('ticketdetails.change_status', 'Change Ticket Status'), ENT_QUOTES, 'UTF-8'); ?></label>
            <input type="hidden" name="id" value="<?php echo (int)$ticket->id; ?>">
            <div class="form-row align-items-end">
              <div class="col-md-6">
                <select class="form-control" id="status" name="status">
                  <option value=""><?php echo htmlspecialchars(i18n_t('ticketdetails.select', '--select--'), ENT_QUOTES, 'UTF-8'); ?></option>
                  <option value="open"><?php echo htmlspecialchars(i18n_status_label('open'), ENT_QUOTES, 'UTF-8'); ?></option>
                  <option value="pending"><?php echo htmlspecialchars(i18n_status_label('pending'), ENT_QUOTES, 'UTF-8'); ?></option>
                  <option value="closed"><?php echo htmlspecialchars(i18n_status_label('closed'), ENT_QUOTES, 'UTF-8'); ?></option>
                  <option value="solved"><?php echo htmlspecialchars(i18n_status_label('solved'), ENT_QUOTES, 'UTF-8'); ?></option>
                </select>
              </div>
              <div class="col-md-auto mt-2 mt-md-0">
                <button type="submit" name="submit" class="btn btn-success"><?php echo htmlspecialchars(i18n_t('ticketdetails.change', 'Change'), ENT_QUOTES, 'UTF-8'); ?></button>
              </div>
            </div>
          </form>
          <div id="msg" class="mt-3"></div>
        </div>
      </div>
    <?php endif; ?>

    <div class="card mb-3">
      <div class="card-header"><?php echo htmlspecialchars(i18n_t('ticketdetails.comments_title', 'Comments'), ENT_QUOTES, 'UTF-8'); ?></div>
      <div class="card-body">
        <?php if (empty($comments)): ?>
          <p class="mb-0"><?php echo htmlspecialchars(i18n_t('ticketdetails.no_comments', 'No comments yet.'), ENT_QUOTES, 'UTF-8'); ?></p>
        <?php else: ?>
          <div class="list-group">
            <?php foreach ($comments as $commentItem): ?>
              <div class="list-group-item">
                <h6 class="mb-1"><?php echo htmlspecialchars(!empty($commentItem->team_member) ? TeamMember::getName($commentItem->team_member) : i18n_t('common.none', 'None'), ENT_QUOTES, 'UTF-8'); ?></h6>
                <div class="d-flex w-100 justify-content-between">
                  <p class="mb-1"><?php echo htmlspecialchars($commentItem->body, ENT_QUOTES, 'UTF-8'); ?></p>
                  <?php $commentDate = new DateTime($commentItem->created_at); ?>
                  <small><?php echo htmlspecialchars($commentDate->format('d-m-Y H:i:s'), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><?php echo htmlspecialchars(i18n_t('ticketdetails.history_title', 'History'), ENT_QUOTES, 'UTF-8'); ?></div>
      <div class="card-body">
        <?php if (empty($events)): ?>
          <p class="mb-0"><?php echo htmlspecialchars(i18n_t('ticketdetails.no_history', 'No ticket history yet.'), ENT_QUOTES, 'UTF-8'); ?></p>
        <?php else: ?>
          <div class="list-group">
            <?php foreach ($events as $eventItem): ?>
              <?php
                $eventText = $eventItem->body;
                if ($eventText === 'ticket.created' || strtolower($eventText) === 'ticket created') {
                    $eventText = i18n_t('ticketdetails.event_created', 'Ticket created');
                }
              ?>
              <div class="list-group-item">
                <h6 class="mb-1"><?php echo htmlspecialchars(!empty($eventItem->user) ? TeamMember::getName($eventItem->user) : i18n_t('common.none', 'None'), ENT_QUOTES, 'UTF-8'); ?></h6>
                <div class="d-flex w-100 justify-content-between">
                  <p class="mb-1"><?php echo htmlspecialchars($eventText, ENT_QUOTES, 'UTF-8'); ?></p>
                  <?php $eventDate = new DateTime($eventItem->created_at); ?>
                  <small><?php echo htmlspecialchars($eventDate->format('d-m-Y H:i:s'), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include './footer.php'; ?>
<script>
  (function () {
    const selectedTeam = <?php echo json_encode((string)$ticket->team); ?>;
    const selectedMember = <?php echo json_encode((string)$ticket->team_member); ?>;

    if (selectedTeam) {
      getTeamMember(selectedTeam);
      setTimeout(function () {
        const dropdown = document.getElementById('team-member-dropdown');
        if (dropdown && selectedMember) {
          dropdown.value = selectedMember;
        }
      }, 250);
    }

    jQuery('#formData').submit(function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      jQuery('#msg').html(
        '<div class="alert alert-info text-center mb-0"><strong><?php echo addslashes(i18n_t('common.processing', 'Processing...')); ?></strong></div>'
      );

      jQuery.ajax({
        url: './src/update-ticket.php',
        type: 'post',
        dataType: 'text',
        data: formData,
        contentType: false,
        processData: false,
        success: function (res) {
          const result = JSON.parse(res);
          if (result.status === 200) {
            jQuery('#msg').html(
              '<div class="alert alert-success text-center mb-0"><strong><?php echo addslashes(i18n_t('common.success', 'Success!')); ?></strong> ' + result.msg + '</div>'
            );
          } else {
            jQuery('#msg').html(
              '<div class="alert alert-danger text-center mb-0"><strong><?php echo addslashes(i18n_t('common.failed', 'Failed!')); ?></strong> ' + result.msg + '</div>'
            );
          }
        }
      });
    });
  })();
</script>
