<?php
require_once './src/i18n.php';
$pageTitle = i18n_t('newuser.title', 'New user');
include './header.php';

require_once './src/user.php';
require './src/helper-functions.php';

$err = '';
$msg = '';
$form = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'role' => 'member',
];

if (isset($_POST['submit'])) {
    $form['name'] = trim($_POST['name'] ?? '');
    $form['email'] = trim($_POST['email'] ?? '');
    $form['phone'] = trim($_POST['phone'] ?? '');
    $form['role'] = trim($_POST['role'] ?? 'member');
    $password = $_POST['password'] ?? '';
    $confirmPass = $_POST['confirm-password'] ?? '';

    if (strlen($form['name']) < 1) {
        $err = i18n_t('newuser.error.name', 'Please enter user name');
    } elseif (strlen($form['email']) < 1) {
        $err = i18n_t('newuser.error.email', 'Please enter email');
    } elseif (!isValidEmail($form['email'])) {
        $err = i18n_t('newuser.error.valid_email', 'Please enter a valid email');
    } elseif (strlen($form['phone']) < 1) {
        $err = i18n_t('newuser.error.phone', 'Please enter phone number');
    } elseif (!isValidPhone($form['phone'])) {
        $err = i18n_t('newuser.error.valid_phone', 'Please enter a valid phone number');
    } elseif (strlen($password) < 1) {
        $err = i18n_t('newuser.error.password', 'Please enter a password');
    } elseif (strlen($password) < 8) {
        $err = i18n_t('newuser.error.password_short', 'Password should be at least 8 characters');
    } elseif ($password !== $confirmPass) {
        $err = i18n_t('newuser.error.password_match', 'Passwords do not match');
    } else {
        $allowedRoles = ['member', 'client', 'moderator', 'admin', 'creator', 'mayor', 'deputymayor', 'spokesperson', 'chief_officer', 'info_officer'];
        $chosenRole = in_array($form['role'], $allowedRoles, true) ? $form['role'] : 'member';

        try {
            $newUser = new User([
                'name' => $form['name'],
                'email' => $form['email'],
                'phone' => $form['phone'],
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $chosenRole,
                'last_password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $newUser->save();
            $msg = i18n_t('newuser.success', 'User created successfully');
            $form = ['name' => '', 'email' => '', 'phone' => '', 'role' => 'member'];
        } catch (Exception $e) {
            $err = i18n_t('newuser.error.create_failed', 'Unable to create user');
        }
    }
}

$roles = ['member', 'client', 'moderator', 'admin', 'creator', 'mayor', 'deputymayor', 'spokesperson', 'chief_officer', 'info_officer'];
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="./dashboard.php"><?php echo htmlspecialchars(i18n_t('common.dashboard', 'Dashboard'), ENT_QUOTES, 'UTF-8'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo htmlspecialchars(i18n_t('newuser.title', 'New user'), ENT_QUOTES, 'UTF-8'); ?></li>
    </ol>

    <div class="card mb-3">
      <div class="card-header">
        <h3 class="mb-1"><?php echo htmlspecialchars(i18n_t('newuser.card_title', 'Create a new user'), ENT_QUOTES, 'UTF-8'); ?></h3>
        <small class="text-muted"><?php echo htmlspecialchars(i18n_t('newuser.card_subtitle', 'Add a user account and assign an access role.'), ENT_QUOTES, 'UTF-8'); ?></small>
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
            <label for="name" class="col-sm-12 col-lg-3 col-md-3 col-form-label"><?php echo htmlspecialchars(i18n_t('newuser.name', 'Name'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="col-sm-9">
              <input type="text" name="name" id="name" class="form-control" placeholder="<?php echo htmlspecialchars(i18n_t('newuser.name', 'Name'), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
            <label for="email" class="col-sm-12 col-lg-3 col-md-3 col-form-label"><?php echo htmlspecialchars(i18n_t('newuser.email', 'Email'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="col-sm-9">
              <input type="text" name="email" id="email" class="form-control" placeholder="<?php echo htmlspecialchars(i18n_t('newuser.email', 'Email'), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
            <label for="phone" class="col-sm-12 col-lg-3 col-md-3 col-form-label"><?php echo htmlspecialchars(i18n_t('newuser.phone', 'Phone'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="col-sm-9">
              <input type="text" name="phone" id="phone" class="form-control" placeholder="<?php echo htmlspecialchars(i18n_t('newuser.phone', 'Phone'), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($form['phone'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
            <label for="password" class="col-sm-12 col-lg-3 col-md-3 col-form-label"><?php echo htmlspecialchars(i18n_t('newuser.password', 'Password'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="col-sm-9">
              <input type="password" name="password" id="password" class="form-control" placeholder="<?php echo htmlspecialchars(i18n_t('newuser.password', 'Password'), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
            <label for="confirm-password" class="col-sm-12 col-lg-3 col-md-3 col-form-label"><?php echo htmlspecialchars(i18n_t('newuser.confirm_password', 'Confirm Password'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="col-sm-9">
              <input type="password" name="confirm-password" id="confirm-password" class="form-control" placeholder="<?php echo htmlspecialchars(i18n_t('newuser.confirm_password', 'Confirm Password'), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <?php if ($isAdmin): ?>
            <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
              <label for="role" class="col-sm-12 col-lg-3 col-md-3 col-form-label"><?php echo htmlspecialchars(i18n_t('newuser.role', 'Role'), ENT_QUOTES, 'UTF-8'); ?></label>
              <div class="col-sm-9">
                <select name="role" id="role" class="form-control">
                  <?php foreach ($roles as $roleValue): ?>
                    <option value="<?php echo htmlspecialchars($roleValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $roleValue === $form['role'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars(i18n_role_label($roleValue), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          <?php endif; ?>

          <div class="text-center">
            <button type="submit" name="submit" class="btn btn-lg btn-primary"><?php echo htmlspecialchars(i18n_t('newuser.create', 'Create'), ENT_QUOTES, 'UTF-8'); ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include './footer.php'; ?>
