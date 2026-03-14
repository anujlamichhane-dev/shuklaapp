<?php
  $bodyClass = 'mobile-home-body';
  $extraCss = ['css/mobile-home.css'];
  $hideSidebar = true;
  $hideSidebarToggle = true;
  include './header.php';
  require_once './src/Database.php';

  $messages = [];
  if ($isClient) {
    $db = Database::getInstance();
    $tblCheck = $db->query("SHOW TABLES LIKE 'messages'");
    if ($tblCheck && $tblCheck->num_rows > 0) {
      $stmt = $db->prepare("SELECT * FROM messages WHERE sender_user_id = ? OR sender_email = ? ORDER BY id DESC");
      if ($stmt) {
        $stmt->bind_param('is', $user->id, $user->email);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_object()) {
          $messages[] = $row;
        }
        $stmt->close();
      }
    }
  }
?>

<div id="content-wrapper">
  <div class="app-shell">
    <section class="menu-section" style="margin-top:6px;">
      <div class="section-title"><?php echo htmlspecialchars(i18n_t('mymessages.title'), ENT_QUOTES, 'UTF-8'); ?></div>
      <?php if (empty($messages)) : ?>
        <div class="alert alert-info text-center mb-0"><?php echo htmlspecialchars(i18n_t('mymessages.empty'), ENT_QUOTES, 'UTF-8'); ?></div>
      <?php else : ?>
        <div class="table-responsive messages-table">
          <table class="table table-bordered table-hover table-sm" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th><?php echo htmlspecialchars(i18n_t('mymessages.recipient'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars(i18n_t('mymessages.recipient_email'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars(i18n_t('mymessages.your_name'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars(i18n_t('mymessages.your_email'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars(i18n_t('mymessages.subject'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars(i18n_t('mymessages.message'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars(i18n_t('mymessages.attachment'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars(i18n_t('mymessages.sent'), ENT_QUOTES, 'UTF-8'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($messages as $msg) : ?>
                <?php $sentAt = !empty($msg->created_at) ? (new DateTime($msg->created_at))->format('d-m-Y H:i') : ''; ?>
                <tr>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('mymessages.recipient'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($msg->recipient_role ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('mymessages.recipient_email'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($msg->recipient_email ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('mymessages.your_name'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($msg->sender_name ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('mymessages.your_email'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($msg->sender_email ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('mymessages.subject'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($msg->subject ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('mymessages.message'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($msg->body ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('mymessages.attachment'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if (!empty($msg->attachment_name)) : ?>
                      <?php echo htmlspecialchars($msg->attachment_name, ENT_QUOTES, 'UTF-8'); ?>
                    <?php else : ?>
                      <?php echo htmlspecialchars(i18n_t('mymessages.none'), ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                  </td>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('mymessages.sent'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($sentAt, ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>

<?php include './footer.php'; ?>
