<?php
  include './header.php';
  require_once './src/Database.php';

  if (!$isAdmin && !$isOfficial) {
    header('Location: ./dashboard.php');
    exit();
  }

  $db = Database::getInstance();

  // create replies table if missing (safety)
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
  $statusType = '';

  // handle reply submit
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_body'], $_POST['message_id'])) {
    $replyBody = trim($_POST['reply_body']);
    $msgId = (int)$_POST['message_id'];
    $uploadDir = __DIR__ . '/data/message_uploads';
    $allowedExt = ['pdf','jpg','jpeg','png','gif','webp','doc','docx','ppt','pptx','xls','xlsx','csv','txt'];
    $maxBytes = 10 * 1024 * 1024; // 10MB
    if (!is_dir($uploadDir)) {
      @mkdir($uploadDir, 0755, true);
    }
    $hasUpload = isset($_FILES['reply_attachment']) && $_FILES['reply_attachment']['error'] !== UPLOAD_ERR_NO_FILE;
    $replyFilePath = null;
    $replyFileName = null;
    $replyFileType = null;
    $replyFileSize = null;

    // fetch message to check permission
    $stmt = $db->prepare("SELECT recipient_role FROM messages WHERE id = ?");
    $stmt->bind_param('i', $msgId);
    $stmt->execute();
    $msgResult = $stmt->get_result();
    $msgRow = $msgResult ? $msgResult->fetch_object() : null;
    $stmt->close();

    if (!$msgRow) {
      $statusMsg = 'Message not found.';
      $statusType = 'error';
    } elseif (!$isAdmin && $msgRow->recipient_role !== $role) {
      $statusMsg = 'Not authorized to reply to this message.';
      $statusType = 'error';
    } elseif (strlen($replyBody) < 2) {
      $statusMsg = 'Reply cannot be empty.';
      $statusType = 'error';
    } elseif ($hasUpload && $_FILES['reply_attachment']['error'] !== UPLOAD_ERR_OK) {
      $statusMsg = 'File upload failed.';
      $statusType = 'error';
    } elseif ($hasUpload) {
      $fname = $_FILES['reply_attachment']['name'] ?? '';
      $tmp = $_FILES['reply_attachment']['tmp_name'] ?? '';
      $fsize = (int)($_FILES['reply_attachment']['size'] ?? 0);
      $fmime = $_FILES['reply_attachment']['type'] ?? '';
      $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));

      if (!in_array($ext, $allowedExt, true)) {
        $statusMsg = 'Unsupported attachment type.';
        $statusType = 'error';
      } elseif ($fsize > $maxBytes) {
        $statusMsg = 'Attachment too large (max 10MB).';
        $statusType = 'error';
      } elseif (!is_uploaded_file($tmp)) {
        $statusMsg = 'Invalid upload.';
        $statusType = 'error';
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
          $statusMsg = 'Could not save the attachment.';
          $statusType = 'error';
        }
      }
    } else {
      $emailForRole = '';
      switch ($role) {
        case 'mayor': $emailForRole = 'mayor@shuklagandakimun.gov.np'; break;
        case 'deputymayor': $emailForRole = 'deputymayor@shuklagandakimun.gov.np'; break;
        case 'spokesperson': $emailForRole = 'ward3shukla@gmail.com'; break;
        case 'chief_officer': $emailForRole = 'sanskritpaudel@gmail.com'; break;
        case 'info_officer': $emailForRole = 'shuklasuchana@gmail.com'; break;
        default: $emailForRole = $user->email;
      }

      // Ensure attachment columns exist
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
        $statusMsg = 'Reply saved.';
        $statusType = 'success';
      } else {
        $statusMsg = 'Could not save reply.';
        $statusType = 'error';
      }
    }
  }

  // fetch messages and replies
  $messages = [];
  if ($isAdmin) {
    $sql = "SELECT * FROM messages ORDER BY created_at DESC";
    $result = $db->query($sql);
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
    $rRes = $db->query("SELECT * FROM messages_replies WHERE message_id IN ($idList) ORDER BY created_at ASC");
    if ($rRes) {
      while ($r = $rRes->fetch_object()) {
        $repliesByMsg[$r->message_id][] = $r;
      }
    }
  }
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
          <li class="breadcrumb-item active">Messages</li>
        </ol>

        <?php if ($statusMsg): ?>
          <div class="alert alert-<?php echo $statusType === 'success' ? 'success' : 'danger'; ?> mb-3" role="alert">
            <?php echo htmlspecialchars($statusMsg, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <div class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Inbound Messages</h3>
            <span class="badge badge-primary"><?php echo count($messages); ?> total</span>
          </div>
          <div class="card-body">
            <?php if (empty($messages)): ?>
              <p>No messages yet.</p>
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
                    <p class="mb-1"><strong>From:</strong> <?php echo htmlspecialchars($msg->sender_name, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($msg->sender_email, ENT_QUOTES, 'UTF-8'); ?>)</p>
                    <p class="mb-2" style="white-space:pre-wrap;"><?php echo nl2br(htmlspecialchars($msg->body, ENT_QUOTES, 'UTF-8')); ?></p>
                    <?php if (!empty($msg->attachment_path)): ?>
                      <p class="mb-2">
                        <strong>Attachment:</strong>
                        <a class="btn btn-sm btn-outline-primary" href="download-attachment.php?id=<?php echo (int)$msg->id; ?>" target="_blank" rel="noopener">
                          Download
                        </a>
                        <a class="btn btn-sm btn-outline-secondary" href="download-attachment.php?id=<?php echo (int)$msg->id; ?>&inline=1" target="_blank" rel="noopener">
                          View
                        </a>
                        <span class="ml-2">
                          <?php echo htmlspecialchars($msg->attachment_name ?? 'file', ENT_QUOTES, 'UTF-8'); ?>
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
                            <strong><?php echo htmlspecialchars($rep->replier_role, ENT_QUOTES, 'UTF-8'); ?>:</strong>
                            <?php echo nl2br(htmlspecialchars($rep->body, ENT_QUOTES, 'UTF-8')); ?>
                            <small class="text-muted"><?php echo htmlspecialchars($rep->created_at, ENT_QUOTES, 'UTF-8'); ?></small>
                            <?php if (!empty($rep->attachment_path)): ?>
                              <br>
                              <a class="btn btn-sm btn-outline-primary" href="download-attachment.php?type=reply&id=<?php echo (int)$rep->id; ?>" target="_blank" rel="noopener">Download</a>
                              <a class="btn btn-sm btn-outline-secondary" href="download-attachment.php?type=reply&id=<?php echo (int)$rep->id; ?>&inline=1" target="_blank" rel="noopener">View</a>
                              <small class="text-muted ml-1"><?php echo htmlspecialchars($rep->attachment_name ?? 'attachment', ENT_QUOTES, 'UTF-8'); ?></small>
                            <?php endif; ?>
                          </p>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                    <form class="mt-2" method="POST" enctype="multipart/form-data" action="messages-inbox.php">
                      <input type="hidden" name="message_id" value="<?php echo (int)$msg->id; ?>">
                      <div class="form-group mb-2">
                        <textarea class="form-control" name="reply_body" rows="2" placeholder="Reply..." required></textarea>
                      </div>
                      <div class="form-group mb-2">
                        <input type="file" name="reply_attachment" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt">
                        <small class="text-muted">Optional attachment (max 10MB).</small>
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">Reply</button>
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
