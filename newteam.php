<?php
require_once './src/i18n.php';
$pageTitle = i18n_t('newteam.title', 'New team');
include './header.php';

require_once './src/team.php';

$err = '';
$msg = '';
$name = '';

if (isset($_POST['submit'])) {
    $name = trim($_POST['name'] ?? '');

    if (strlen($name) < 1) {
        $err = i18n_t('newteam.error.name', 'Please enter team name');
    } else {
        try {
            $team = new Team(['name' => $name]);
            $team->save();
            $msg = i18n_t('newteam.success', 'Team generated successfully');
            $name = '';
        } catch (Exception $e) {
            $err = i18n_t('newteam.error.create_failed', 'Failed to generate team');
        }
    }
}
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="./team.php"><?php echo htmlspecialchars(i18n_t('team.title', 'Team'), ENT_QUOTES, 'UTF-8'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo htmlspecialchars(i18n_t('newteam.title', 'New team'), ENT_QUOTES, 'UTF-8'); ?></li>
    </ol>

    <div class="card mb-3">
      <div class="card-header">
        <h3 class="mb-1"><?php echo htmlspecialchars(i18n_t('newteam.card_title', 'Create a new team'), ENT_QUOTES, 'UTF-8'); ?></h3>
        <small class="text-muted"><?php echo htmlspecialchars(i18n_t('newteam.card_subtitle', 'Create a team before assigning members to it.'), ENT_QUOTES, 'UTF-8'); ?></small>
      </div>
      <div class="card-body">
        <?php if ($err !== ''): ?>
          <div class="alert alert-danger text-center my-3" role="alert"><strong><?php echo htmlspecialchars(i18n_t('common.failed', 'Failed!'), ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($msg !== ''): ?>
          <div class="alert alert-success text-center my-3" role="alert"><strong><?php echo htmlspecialchars(i18n_t('common.success', 'Success!'), ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">
          <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
            <label for="name" class="col-sm-12 col-lg-3 col-md-3 col-form-label"><?php echo htmlspecialchars(i18n_t('newteam.name', 'Name'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="col-sm-9">
              <input type="text" name="name" id="name" class="form-control" placeholder="<?php echo htmlspecialchars(i18n_t('newteam.name', 'Name'), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <div class="text-center">
            <button type="submit" name="submit" class="btn btn-lg btn-primary"><?php echo htmlspecialchars(i18n_t('newteam.create', 'Create'), ENT_QUOTES, 'UTF-8'); ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include './footer.php'; ?>
