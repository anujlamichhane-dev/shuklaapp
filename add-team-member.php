<?php
require_once './src/i18n.php';
$pageTitle = i18n_t('addmember.title', 'Add New Member');
include './header.php';

if (!isset($_GET['team-id']) || !ctype_digit((string)$_GET['team-id'])) {
    echo '<script>history.back()</script>';
    exit();
}

require_once './src/user.php';
require_once './src/team-member.php';

$allusers = User::findAll();
$err = '';
$msg = '';
$selectedUser = 'none';

if (isset($_POST['submit'])) {
    $selectedUser = (string)($_POST['id'] ?? 'none');
    $teamid = (int)$_GET['team-id'];

    if ($selectedUser === 'none' || !ctype_digit($selectedUser)) {
        $err = i18n_t('addmember.error.select_user', 'Please select user');
    } else {
        try {
            $teamMember = new TeamMember([
                'id' => $selectedUser,
                'team-id' => $teamid,
            ]);
            $teamMember->save();
            $msg = i18n_t('addmember.success', 'Member added successfully');
            $selectedUser = 'none';
        } catch (Exception $e) {
            $err = i18n_t('addmember.error.create_failed', 'Failed to add member');
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
      <li class="breadcrumb-item active"><?php echo htmlspecialchars(i18n_t('addmember.title', 'Add New Member'), ENT_QUOTES, 'UTF-8'); ?></li>
    </ol>

    <div class="card mb-3">
      <div class="card-header">
        <h3 class="mb-1"><?php echo htmlspecialchars(i18n_t('addmember.card_title', 'Add a member to the team'), ENT_QUOTES, 'UTF-8'); ?></h3>
        <small class="text-muted"><?php echo htmlspecialchars(i18n_t('addmember.card_subtitle', 'Choose a user account to add to this team.'), ENT_QUOTES, 'UTF-8'); ?></small>
      </div>
      <div class="card-body">
        <?php if ($err !== ''): ?>
          <div class="alert alert-danger text-center my-3" role="alert"><strong><?php echo htmlspecialchars(i18n_t('common.failed', 'Failed!'), ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($msg !== ''): ?>
          <div class="alert alert-success text-center my-3" role="alert"><strong><?php echo htmlspecialchars(i18n_t('common.success', 'Success!'), ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
          <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
            <label for="team-user-id" class="col-sm-12 col-lg-3 col-md-3 col-form-label"><?php echo htmlspecialchars(i18n_t('addmember.user', 'User'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="col-sm-9">
              <select name="id" id="team-user-id" class="form-control">
                <option value="none"><?php echo htmlspecialchars(i18n_t('addmember.select', '--select--'), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php foreach ($allusers as $listedUser): ?>
                  <option value="<?php echo (int)$listedUser->id; ?>" <?php echo (string)$listedUser->id === $selectedUser ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($listedUser->name, ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="text-center">
            <button type="submit" name="submit" class="btn btn-lg btn-primary"><?php echo htmlspecialchars(i18n_t('addmember.create', 'Create'), ENT_QUOTES, 'UTF-8'); ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include './footer.php'; ?>
