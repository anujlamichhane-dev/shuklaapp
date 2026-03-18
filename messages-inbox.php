<?php
require_once './src/i18n.php';
$pageTitle = i18n_t('messages.title', 'Messages');
include './header.php';
require_once './src/Database.php';

if (!$isAdmin && !$isOfficial) {
    header('Location: ./dashboard.php');
    exit();
}

$db = Database::getInstance();

$db->query("CREATE TABLE IF NOT EXISTS messages_replies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  message_id INT NOT NULL,
  replier_role VARCHAR(50) NOT NULL,
  replier_email VARCHAR(191) NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_msg_reply_msg FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$statusMsg = '';
$statusType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_body'], $_POST['message_id'])) {
    $replyBody = trim($_POST['reply_body']);
    $msgId = (int)$_POST['message_id'];
    $uploadDir = __DIR__ . '/data/message_uploads';
    $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'csv', 'txt'];
    $maxBytes = 10 * 1024 * 1024;

    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $hasUpload = isset($_FILES['reply_attachment']) && $_FILES['reply_attachment']['error'] !== UPLOAD_ERR_NO_FILE;
    $replyFilePath = null;
    $replyFileName = null;
    $replyFileType = null;
    $replyFileSize = null;

    $stmt = $db->prepare("SELECT recipient_role FROM messages WHERE id = ?");
    $stmt->bind_param('i', $msgId);
    $stmt->execute();
    $msgResult = $stmt->get_result();
    $msgRow = $msgResult ? $msgResult->fetch_object() : null;
    $stmt->close();

    if (!$msgRow) {
        $statusMsg = i18n_t('messages.reply_not_found', 'Message not found.');
        $statusType = 'danger';
    } elseif (!$isAdmin && $msgRow->recipient_role !== $role) {
        $statusMsg = i18n_t('messages.reply_not_allowed', 'Not authorized to reply to this message.');
        $statusType = 'danger';
    } elseif (strlen($replyBody) < 2) {
        $statusMsg = i18n_t('messages.reply_empty', 'Reply cannot be empty.');
        $statusType = 'danger';
    } elseif ($hasUpload && $_FILES['reply_attachment']['error'] !== UPLOAD_ERR_OK) {
        $statusMsg = i18n_t('messages.reply_upload_failed', 'File upload failed.');
        $statusType = 'danger';
    } elseif ($hasUpload) {
        $fname = $_FILES['reply_attachment']['name'] ?? '';
        $tmp = $_FILES['reply_attachment']['tmp_name'] ?? '';
        $fsize = (int)($_FILES['reply_attachment']['size'] ?? 0);
        $fmime = $_FILES['reply_attachment']['type'] ?? '';
        $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            $statusMsg = i18n_t('messages.reply_unsupported', 'Unsupported attachment type.');
            $statusType = 'danger';
        } elseif ($fsize > $maxBytes) {
            $statusMsg = i18n_t('messages.reply_too_large', 'Attachment too large (max 10MB).');
            $statusType = 'danger';
        } elseif (!is_uploaded_file($tmp)) {
            $statusMsg = i18n_t('messages.reply_invalid_upload', 'Invalid upload.');
            $statusType = 'danger';
        } else {
            $safeSlug = preg_replace('/[^a-zA-Z0-9-_]/', '_', pathinfo($fname, PATHINFO_FILENAME));
            $uniqueName = $safeSlug . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $uploadDir . DIRECTORY_SEPARATOR . $uniqueName;
            if (move_uploaded_file($tmp, $dest)) {
                $replyFilePath = 'data/message_uploads/' . $uniqueName;
                $replyFileName = $fname;
                $replyFileType = $fmime;
                $replyFileSize = $fsize;
            } else {
                $statusMsg = i18n_t('messages.reply_save_failed', 'Could not save the attachment.');
                $statusType = 'danger';
            }
        }
    }

    if ($statusMsg === '') {
        $emailForRole = '';
        switch ($role) {
            case 'mayor':
                $emailForRole = 'mayor@shuklagandakimun.gov.np';
                break;
            case 'deputymayor':
                $emailForRole = 'deputymayor@shuklagandakimun.gov.np';
                break;
            case 'spokesperson':
                $emailForRole = 'ward3shukla@gmail.com';
                break;
            case 'chief_officer':
                $emailForRole = 'sanskritpaudel@gmail.com';
                break;
            case 'info_officer':
                $emailForRole = 'shuklasuchana@gmail.com';
                break;
            default:
                $emailForRole = $user->email;
        }

        $colCheck = $db->query("SHOW COLUMNS FROM messages_replies LIKE 'attachment_path'");
        if ($colCheck && $colCheck->num_rows === 0) {
            $db->query("ALTER TABLE messages_replies
              ADD COLUMN attachment_path VARCHAR(255) DEFAULT NULL,
              ADD COLUMN attachment_name VARCHAR(255) DEFAULT NULL,
              ADD COLUMN attachment_type VARCHAR(100) DEFAULT NULL,
              ADD COLUMN attachment_size INT UNSIGNED DEFAULT NULL");
        }

        if ($replyFileSize === null) {
            $replyFileSize = 0;
        }

        $stmt = $db->prepare("INSERT INTO messages_replies (message_id, replier_role, replier_email, body, attachment_path, attachment_name, attachment_type, attachment_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('issssssi', $msgId, $role, $emailForRole, $replyBody, $replyFilePath, $replyFileName, $replyFileType, $replyFileSize);
            $stmt->execute();
            $stmt->close();
            $statusMsg = i18n_t('messages.reply_saved', 'Reply saved.');
            $statusType = 'success';
        } else {
            $statusMsg = i18n_t('messages.reply_store_failed', 'Could not save reply.');
            $statusType = 'danger';
        }
    }
}

$messages = [];
if ($isAdmin) {
    $result = $db->query("SELECT * FROM messages ORDER BY created_at DESC");
} else {
    $roleKey = $role;
    $stmt = $db->prepare("SELECT * FROM messages WHERE recipient_role = ? ORDER BY created_at DESC");
    $stmt->bind_param('s', $roleKey);
    $stmt->execute();
    $result = $stmt->get_result();
}

$msgIds = [];
if ($result) {
    while ($row = $result->fetch_object()) {
        $messages[] = $row;
        $msgIds[] = $row->id;
    }
}

$repliesByMsg = [];
if (!empty($msgIds)) {
    $idList = implode(',', array_map('intval', $msgIds));
    $replyResult = $db->query("SELECT * FROM messages_replies WHERE message_id IN ($idList) ORDER BY created_at ASC");
    if ($replyResult) {
        while ($replyRow = $replyResult->fetch_object()) {
            $repliesByMsg[$replyRow->message_id][] = $replyRow;
        }
    }
}
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="./dashboard.php"><?php echo htmlspecialchars(i18n_t('common.dashboard', 'Dashboard'), ENT_QUOTES, 'UTF-8'); ?></a></li>
          <li class="breadcrumb-item active"><?php echo htmlspecialchars(i18n_t('messages.title', 'Messages'), ENT_QUOTES, 'UTF-8'); ?></li>
        </ol>

        <?php if ($statusMsg !== ''): ?>
          <div class="alert alert-<?php echo htmlspecialchars($statusType, ENT_QUOTES, 'UTF-8'); ?> mb-3" role="alert">
            <?php echo htmlspecialchars($statusMsg, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <div class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><?php echo htmlspecialchars(i18n_t('messages.inbound', 'Inbound Messages'), ENT_QUOTES, 'UTF-8'); ?></h3>
            <span class="badge badge-primary"><?php echo count($messages); ?> <?php echo htmlspecialchars(i18n_t('messages.total_suffix', 'total'), ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <div class="card-body">
            <?php if (empty($messages)): ?>
              <p class="mb-0"><?php echo htmlspecialchars(i18n_t('messages.empty', 'No messages yet.'), ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else: ?>
              <div class="list-group">
                <?php foreach ($messages as $msg): ?>
                  <div class="list-group-item flex-column align-items-start">
                    <div class="d-flex w-100 justify-content-between">
                      <h5 class="mb-1">
                        <a href="message-view.php?id=<?php echo (int)$msg->id; ?>" target="_blank" rel="noopener">
                          <?php echo htmlspecialchars($msg->subject, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                      </h5>
                      <small><?php echo htmlspecialchars($msg->created_at, ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                    <p class="mb-1">
                      <strong><?php echo htmlspecialchars(i18n_t('messages.from_label', 'From:'), ENT_QUOTES, 'UTF-8'); ?></strong>
                      <?php echo htmlspecialchars($msg->sender_name, ENT_QUOTES, 'UTF-8'); ?>
                      (<?php echo htmlspecialchars($msg->sender_email, ENT_QUOTES, 'UTF-8'); ?>)
                    </p>
                    <p class="mb-2" style="white-space:pre-wrap;"><?php echo nl2br(htmlspecialchars($msg->body, ENT_QUOTES, 'UTF-8')); ?></p>

                    <?php if (!empty($msg->attachment_path)): ?>
                      <p class="mb-2">
                        <strong><?php echo htmlspecialchars(i18n_t('common.attachment', 'Attachment'), ENT_QUOTES, 'UTF-8'); ?>:</strong>
                        <a class="btn btn-sm btn-outline-primary" href="download-attachment.php?id=<?php echo (int)$msg->id; ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(i18n_t('common.download', 'Download'), ENT_QUOTES, 'UTF-8'); ?></a>
                        <a class="btn btn-sm btn-outline-secondary" href="download-attachment.php?id=<?php echo (int)$msg->id; ?>&inline=1" target="_blank" rel="noopener"><?php echo htmlspecialchars(i18n_t('common.view', 'View'), ENT_QUOTES, 'UTF-8'); ?></a>
                        <span class="ml-2">
                          <?php echo htmlspecialchars($msg->attachment_name ?? i18n_t('common.attachment', 'Attachment'), ENT_QUOTES, 'UTF-8'); ?>
                          <?php if (!empty($msg->attachment_type)): ?>
                            <small class="text-muted">(<?php echo htmlspecialchars($msg->attachment_type, ENT_QUOTES, 'UTF-8'); ?>)</small>
                          <?php endif; ?>
                        </span>
                      </p>
                    <?php endif; ?>

                    <?php if (!empty($repliesByMsg[$msg->id])): ?>
                      <div class="pl-3 border-left">
                        <?php foreach ($repliesByMsg[$msg->id] as $rep): ?>
                          <p class="mb-1">
                            <strong><?php echo htmlspecialchars(i18n_role_label($rep->replier_role), ENT_QUOTES, 'UTF-8'); ?>:</strong>
                            <?php echo nl2br(htmlspecialchars($rep->body, ENT_QUOTES, 'UTF-8')); ?>
                            <small class="text-muted"><?php echo htmlspecialchars($rep->created_at, ENT_QUOTES, 'UTF-8'); ?></small>
                            <?php if (!empty($rep->attachment_path)): ?>
                              <br>
                              <a class="btn btn-sm btn-outline-primary" href="download-attachment.php?type=reply&id=<?php echo (int)$rep->id; ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(i18n_t('common.download', 'Download'), ENT_QUOTES, 'UTF-8'); ?></a>
                              <a class="btn btn-sm btn-outline-secondary" href="download-attachment.php?type=reply&id=<?php echo (int)$rep->id; ?>&inline=1" target="_blank" rel="noopener"><?php echo htmlspecialchars(i18n_t('common.view', 'View'), ENT_QUOTES, 'UTF-8'); ?></a>
                              <small class="text-muted ml-1"><?php echo htmlspecialchars($rep->attachment_name ?? i18n_t('common.attachment', 'Attachment'), ENT_QUOTES, 'UTF-8'); ?></small>
                            <?php endif; ?>
                          </p>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>

                    <form class="mt-2" method="POST" enctype="multipart/form-data" action="messages-inbox.php">
                      <input type="hidden" name="message_id" value="<?php echo (int)$msg->id; ?>">
                      <div class="form-group mb-2">
                        <textarea class="form-control" name="reply_body" rows="2" placeholder="<?php echo htmlspecialchars(i18n_t('messages.reply_placeholder', 'Reply...'), ENT_QUOTES, 'UTF-8'); ?>" required></textarea>
                      </div>
                      <div class="form-group mb-2">
                        <label class="d-block mb-1"><?php echo htmlspecialchars(i18n_t('messages.reply_form_attachment', 'Attachment (optional)'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="file" name="reply_attachment" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt">
                        <small class="text-muted d-block"><?php echo htmlspecialchars(i18n_t('messages.reply_form_help', 'Up to 10MB.'), ENT_QUOTES, 'UTF-8'); ?></small>
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary"><?php echo htmlspecialchars(i18n_t('common.reply', 'Reply'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </form>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include './footer.php'; ?>
