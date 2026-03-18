<?php
require_once './src/i18n.php';
$pageTitle = i18n_t('ticketform.title', 'New ticket');
include './header.php';

require_once './src/requester.php';
require_once './src/ticket.php';
require_once './src/ticket-event.php';
require './src/helper-functions.php';

$isClient = ($user->role === 'client');

$err = '';
$msg = '';
$form = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'comment' => '',
    'team' => '',
    'priority' => 'low',
];

if ($isClient) {
    $form['name'] = $user->name ?? '';
    $form['email'] = $user->email ?? '';
    $form['phone'] = $user->phone ?? '';
}

$teams = [];
$teamResult = $db->query("SELECT id, name FROM team ORDER BY name ASC");
while ($row = $teamResult->fetch_object()) {
    $teams[] = $row;
}

if (isset($_POST['submit'])) {
    $form['name'] = trim($_POST['name'] ?? '');
    $form['email'] = trim($_POST['email'] ?? '');
    $form['phone'] = trim($_POST['phone'] ?? '');
    $form['subject'] = trim($_POST['subject'] ?? '');
    $form['comment'] = trim($_POST['comment'] ?? '');
    $form['team'] = trim($_POST['team'] ?? '');
    $form['priority'] = trim($_POST['priority'] ?? 'low');

    if (strlen($form['name']) < 1) {
        $err = i18n_t('ticketform.error.name', 'Please enter requester name');
    } elseif (strlen($form['email']) < 1) {
        $err = i18n_t('ticketform.error.email', 'Please enter requester email address');
    } elseif (!isValidEmail($form['email'])) {
        $err = i18n_t('ticketform.error.valid_email', 'Please enter a valid email address');
    } elseif (!isValidPhone($form['phone'])) {
        $err = i18n_t('ticketform.error.valid_phone', 'Please enter a valid phone number');
    } elseif (strlen($form['subject']) < 1) {
        $err = i18n_t('ticketform.error.subject', 'Please enter subject');
    } elseif (strlen($form['comment']) < 1) {
        $err = i18n_t('ticketform.error.comment', 'Please enter comment');
    } else {
        try {
            $db = Database::getInstance();
            $colCheck = $db->query("SHOW COLUMNS FROM requester LIKE 'user_id'");
            if ($colCheck && $colCheck->num_rows === 0) {
                $db->query("ALTER TABLE requester ADD COLUMN user_id INT DEFAULT NULL");
            }

            $existingRequester = Requester::findByEmail($form['email']);
            $savedRequester = $existingRequester;

            if (!$existingRequester) {
                $requester = new Requester([
                    'name' => $form['name'],
                    'email' => $form['email'],
                    'phone' => $form['phone'],
                    'user_id' => $user->id ?? null,
                ]);
                $savedRequester = $requester->save();
            } elseif (($existingRequester->user_id ?? null) === null && isset($user->id)) {
                $db->query("UPDATE requester SET user_id = " . (int)$user->id . " WHERE id = " . (int)$existingRequester->id);
                $existingRequester->user_id = $user->id;
            }

            $selectedTeam = ctype_digit($form['team']) ? $form['team'] : null;
            $selectedPriority = in_array($form['priority'], ['low', 'medium', 'high'], true) ? $form['priority'] : 'low';

            $ticket = new Ticket([
                'title' => $form['subject'],
                'body' => $form['comment'],
                'requester' => $savedRequester->id,
                'team' => $selectedTeam,
                'priority' => $selectedPriority,
            ]);

            $savedTicket = $ticket->save();

            $event = new Event([
                'ticket' => $savedTicket->id,
                'user' => $user->id,
                'body' => 'ticket.created',
            ]);
            $event->save();

            $msg = i18n_t('ticketform.success', 'Ticket generated successfully');
            if ($isClient) {
                $form['subject'] = '';
                $form['comment'] = '';
                $form['team'] = '';
                $form['priority'] = 'low';
            } else {
                $form = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'comment' => '', 'team' => '', 'priority' => 'low'];
            }
        } catch (Exception $e) {
            $err = i18n_t('ticketform.error.create_failed', 'Failed to generate ticket');
        }
    }
}
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="./dashboard.php"><?php echo htmlspecialchars(i18n_t('common.dashboard', 'Dashboard'), ENT_QUOTES, 'UTF-8'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo htmlspecialchars(i18n_t('ticketform.title', 'New ticket'), ENT_QUOTES, 'UTF-8'); ?></li>
    </ol>

    <div class="card mb-3">
      <div class="card-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
          <div>
            <h3 class="mb-0"><?php echo htmlspecialchars(i18n_t('ticketform.card_title', 'Create a new ticket'), ENT_QUOTES, 'UTF-8'); ?></h3>
            <small class="text-muted"><?php echo htmlspecialchars(i18n_t('ticketform.card_subtitle', 'Share the issue and how we can reach you.'), ENT_QUOTES, 'UTF-8'); ?></small>
          </div>
          <div class="mt-2 mt-lg-0">
            <a href="./mytickets.php" class="btn btn-outline-secondary btn-sm"><?php echo htmlspecialchars(i18n_t('ticketform.my_tickets', 'My tickets'), ENT_QUOTES, 'UTF-8'); ?></a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <?php if ($err !== ''): ?>
          <div class="alert alert-danger text-center my-3" role="alert"><strong><?php echo htmlspecialchars(i18n_t('common.failed', 'Failed!'), ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($msg !== ''): ?>
          <div class="alert alert-success text-center my-3" role="alert"><strong><?php echo htmlspecialchars(i18n_t('common.success', 'Success!'), ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" class="ticket-form">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="name"><?php echo htmlspecialchars(i18n_t('ticketform.name', 'Name'), ENT_QUOTES, 'UTF-8'); ?></label>
              <input type="text" name="name" id="name" class="form-control" placeholder="<?php echo htmlspecialchars(i18n_t('ticketform.name', 'Name'), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isClient ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group col-md-6">
              <label for="email"><?php echo htmlspecialchars(i18n_t('ticketform.email', 'Email'), ENT_QUOTES, 'UTF-8'); ?></label>
              <input type="text" name="email" id="email" class="form-control" placeholder="<?php echo htmlspecialchars(i18n_t('ticketform.email', 'Email'), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isClient ? 'readonly' : ''; ?>>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="phone"><?php echo htmlspecialchars(i18n_t('ticketform.phone', 'Phone'), ENT_QUOTES, 'UTF-8'); ?></label>
              <input type="text" name="phone" id="phone" class="form-control" placeholder="<?php echo htmlspecialchars(i18n_t('ticketform.phone', 'Phone'), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($form['phone'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
            <label for="subject" class="col-sm-12 col-lg-2 col-md-2 col-form-label"><?php echo htmlspecialchars(i18n_t('ticketform.subject', 'Subject'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="col-sm-8">
              <input type="text" name="subject" id="subject" class="form-control" placeholder="<?php echo htmlspecialchars(i18n_t('ticketform.subject', 'Subject'), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($form['subject'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
            <label for="comment" class="col-sm-12 col-lg-2 col-md-2 col-form-label"><?php echo htmlspecialchars(i18n_t('ticketform.comment', 'Comment'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="col-sm-8">
              <textarea name="comment" id="comment" class="form-control" placeholder="<?php echo htmlspecialchars(i18n_t('ticketform.comment', 'Comment'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($form['comment'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
          </div>

          <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
            <label for="team" class="col-sm-12 col-lg-2 col-md-2 col-form-label"><?php echo htmlspecialchars(i18n_t('ticketform.team', 'Team'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="col-sm-8">
              <select name="team" id="team" class="form-control">
                <option value=""><?php echo htmlspecialchars(i18n_t('ticketform.select_team', '--select--'), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php foreach ($teams as $team): ?>
                  <option value="<?php echo (int)$team->id; ?>" <?php echo (string)$team->id === $form['team'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($team->name, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
            <label for="priority" class="col-sm-12 col-lg-2 col-md-2 col-form-label"><?php echo htmlspecialchars(i18n_t('ticketform.priority', 'Priority'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="col-sm-8">
              <select name="priority" id="priority" class="form-control">
                <option value="low" <?php echo $form['priority'] === 'low' ? 'selected' : ''; ?>><?php echo htmlspecialchars(i18n_priority_label('low'), ENT_QUOTES, 'UTF-8'); ?></option>
                <option value="medium" <?php echo $form['priority'] === 'medium' ? 'selected' : ''; ?>><?php echo htmlspecialchars(i18n_priority_label('medium'), ENT_QUOTES, 'UTF-8'); ?></option>
                <option value="high" <?php echo $form['priority'] === 'high' ? 'selected' : ''; ?>><?php echo htmlspecialchars(i18n_priority_label('high'), ENT_QUOTES, 'UTF-8'); ?></option>
              </select>
            </div>
          </div>

          <div class="text-center">
            <button type="submit" name="submit" class="btn btn-lg btn-primary"><?php echo htmlspecialchars(i18n_t('ticketform.create', 'Create'), ENT_QUOTES, 'UTF-8'); ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include './footer.php'; ?>
