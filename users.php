<?php
require_once './src/i18n.php';
$pageTitle = i18n_t('users.title', 'Users');
include './header.php';

require_once './src/user.php';

$err = '';
$msg = '';

if (($isAdmin || $isCreator) && isset($_POST['update_role'], $_POST['user_id'], $_POST['role'])) {
    $targetId = (int)$_POST['user_id'];
    $newRole = (string)$_POST['role'];
    $allowedRoles = ['member', 'client', 'moderator', 'admin', 'creator', 'mayor', 'deputymayor', 'spokesperson', 'chief_officer', 'info_officer'];
    if (in_array($newRole, $allowedRoles, true)) {
        try {
            User::updateRole($targetId, $newRole);
            $msg = i18n_t('users.role_updated', 'Role updated.');
        } catch (Exception $e) {
            $err = i18n_t('users.role_update_failed', 'Could not update role.');
        }
    } else {
        $err = i18n_t('users.invalid_role', 'Invalid role.');
    }
}

$users = User::findAll();
$roles = ['member', 'client', 'moderator', 'admin', 'creator', 'mayor', 'deputymayor', 'spokesperson', 'chief_officer', 'info_officer'];
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="./users.php"><?php echo htmlspecialchars(i18n_t('users.title', 'Users'), ENT_QUOTES, 'UTF-8'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo htmlspecialchars(i18n_t('common.overview', 'Overview'), ENT_QUOTES, 'UTF-8'); ?></li>
    </ol>

    <?php if ($isAdmin || $isCreator): ?>
      <a class="btn btn-primary my-3" href="./newuser.php"><i class="fa fa-plus"></i> <?php echo htmlspecialchars(i18n_t('users.create_new', 'Create New User'), ENT_QUOTES, 'UTF-8'); ?></a>
    <?php endif; ?>

    <div class="card mb-3">
      <div class="card-body">
        <?php if ($err !== ''): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($msg !== ''): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="table-responsive users-table">
          <table class="table table-bordered table-sm table-mobile-stack" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th><?php echo htmlspecialchars(i18n_t('common.name', 'Name'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars(i18n_t('common.role', 'Role'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars(i18n_t('common.email', 'Email'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars(i18n_t('common.phone', 'Phone'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars(i18n_t('common.created_on', 'Created at'), ENT_QUOTES, 'UTF-8'); ?></th>
                <?php if ($isAdmin || $isCreator): ?>
                  <th><?php echo htmlspecialchars(i18n_t('common.actions', 'Actions'), ENT_QUOTES, 'UTF-8'); ?></th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $listedUser): ?>
                <?php $date = new DateTime($listedUser->created_at); ?>
                <tr>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('common.name', 'Name'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($listedUser->name, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('common.role', 'Role'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(i18n_role_label($listedUser->role), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('common.email', 'Email'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($listedUser->email, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('common.phone', 'Phone'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($listedUser->phone, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('common.created_on', 'Created at'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($date->format('d-m-Y H:i:s'), ENT_QUOTES, 'UTF-8'); ?></td>
                  <?php if ($isAdmin || $isCreator): ?>
                    <td data-label="<?php echo htmlspecialchars(i18n_t('common.actions', 'Actions'), ENT_QUOTES, 'UTF-8'); ?>">
                      <form method="POST" action="users.php" class="form-inline">
                        <input type="hidden" name="user_id" value="<?php echo (int)$listedUser->id; ?>">
                        <select name="role" class="form-control form-control-sm d-inline-block mr-2" style="width:auto;">
                          <?php foreach ($roles as $roleValue): ?>
                            <option value="<?php echo htmlspecialchars($roleValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $roleValue === $listedUser->role ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars(i18n_role_label($roleValue), ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm btn-primary" type="submit" name="update_role"><?php echo htmlspecialchars(i18n_t('common.update', 'Update'), ENT_QUOTES, 'UTF-8'); ?></button>
                      </form>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include './footer.php'; ?>
