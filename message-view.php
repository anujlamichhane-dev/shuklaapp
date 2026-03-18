<?php
  $bodyClass = 'mobile-home-body';
  $extraCss = ['css/mobile-home.css'];
  $hideSidebar = false;
  $hideSidebarToggle = false;
  include './header.php';
  require_once './src/Database.php';

  if (!$isAdmin && !$isOfficial) {
    header('Location: ./dashboard.php');
    exit();
  }

  $msgId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($msgId < 1) {
    header('Location: messages-inbox.php');
    exit();
  }

  $db = Database::getInstance();

  // Fetch message
  $stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
  $stmt->bind_param('i', $msgId);
  $stmt->execute();
  $msgRes = $stmt->get_result();
  $message = $msgRes ? $msgRes->fetch_object() : null;
  $stmt->close();

  if (!$message) {
    header('Location: messages-inbox.php');
    exit();
  }

  if (!$isAdmin && $message->recipient_role !== $role) {
    header('Location: messages-inbox.php');
    exit();
  }

  // Fetch replies
  $replies = [];
  $rStmt = $db->prepare("SELECT * FROM messages_replies WHERE message_id = ? ORDER BY created_at ASC");
  if ($rStmt) {
    $rStmt->bind_param('i', $msgId);
    $rStmt->execute();
    $rRes = $rStmt->get_result();
    while ($row = $rRes->fetch_object()) {
      $replies[] = $row;
    }
    $rStmt->close();
  }
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="messages-inbox.php">Messages</a></li>
          <li class="breadcrumb-item active">Message Detail</li>
        </ol>

        <div class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0"><?php echo htmlspecialchars($message->subject, ENT_QUOTES, 'UTF-8'); ?></h4>
              <small class="text-muted">Received: <?php echo htmlspecialchars($message->created_at, ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
            <a class="btn btn-outline-secondary" href="messages-inbox.php">Back to Inbox</a>
          </div>
          <div class="card-body">
            <p class="mb-1"><strong>From:</strong> <?php echo htmlspecialchars($message->sender_name, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($message->sender_email, ENT_QUOTES, 'UTF-8'); ?>)</p>
            <p class="mb-3" style="white-space:pre-wrap;"><?php echo nl2br(htmlspecialchars($message->body, ENT_QUOTES, 'UTF-8')); ?></p>

            <?php if (!empty($message->attachment_path)): ?>
              <div class="mb-3">
                <strong>Attachment:</strong>
                <a class="btn btn-sm btn-outline-primary" href="download-attachment.php?id=<?php echo (int)$message->id; ?>" target="_blank" rel="noopener">Download</a>
                <a class="btn btn-sm btn-outline-secondary" href="download-attachment.php?id=<?php echo (int)$message->id; ?>&inline=1" target="_blank" rel="noopener">View</a>
                <span class="ml-2"><?php echo htmlspecialchars($message->attachment_name ?? 'file', ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if (!empty($message->attachment_type)): ?>
                  <small class="text-muted">(<?php echo htmlspecialchars($message->attachment_type, ENT_QUOTES, 'UTF-8'); ?>)</small>
                <?php endif; ?>
                <?php
                  $msgMime = strtolower($message->attachment_type ?? '');
                  $isImg = strpos($msgMime, 'image/') === 0;
                  $isPdf = $msgMime === 'application/pdf';
                ?>
                <?php if ($isImg): ?>
                  <div class="mt-2">
                    <img src="download-attachment.php?id=<?php echo (int)$message->id; ?>&inline=1" alt="Attachment preview" style="max-width:100%; height:auto; border:1px solid #333; border-radius:6px;">
                  </div>
                <?php elseif ($isPdf): ?>
                  <div class="mt-2" style="height:500px;">
                    <iframe src="download-attachment.php?id=<?php echo (int)$message->id; ?>&inline=1" style="width:100%; height:100%; border:1px solid #333; border-radius:6px;"></iframe>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($replies)): ?>
              <div class="mb-4">
                <h5>Replies</h5>
                <div class="list-group">
                  <?php foreach ($replies as $rep): ?>
                    <div class="list-group-item">
                      <div class="d-flex justify-content-between">
                        <strong><?php echo htmlspecialchars($rep->replier_role, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small class="text-muted"><?php echo htmlspecialchars($rep->created_at, ENT_QUOTES, 'UTF-8'); ?></small>
                      </div>
                      <div style="white-space:pre-wrap;"><?php echo nl2br(htmlspecialchars($rep->body, ENT_QUOTES, 'UTF-8')); ?></div>
                      <?php if (!empty($rep->attachment_path)): ?>
                        <div class="mt-2">
                          <a class="btn btn-sm btn-outline-primary" href="download-attachment.php?type=reply&id=<?php echo (int)$rep->id; ?>" target="_blank" rel="noopener">Download</a>
                          <a class="btn btn-sm btn-outline-secondary" href="download-attachment.php?type=reply&id=<?php echo (int)$rep->id; ?>&inline=1" target="_blank" rel="noopener">View</a>
                          <small class="text-muted ml-1"><?php echo htmlspecialchars($rep->attachment_name ?? 'attachment', ENT_QUOTES, 'UTF-8'); ?></small>
                          <?php
                            $repMime = strtolower($rep->attachment_type ?? '');
                            $repIsImg = strpos($repMime, 'image/') === 0;
                            $repIsPdf = $repMime === 'application/pdf';
                          ?>
                          <?php if ($repIsImg): ?>
                            <div class="mt-2">
                              <img src="download-attachment.php?type=reply&id=<?php echo (int)$rep->id; ?>&inline=1" alt="Reply attachment preview" style="max-width:100%; height:auto; border:1px solid #333; border-radius:6px;">
                            </div>
                          <?php elseif ($repIsPdf): ?>
                            <div class="mt-2" style="height:400px;">
                              <iframe src="download-attachment.php?type=reply&id=<?php echo (int)$rep->id; ?>&inline=1" style="width:100%; height:100%; border:1px solid #333; border-radius:6px;"></iframe>
                            </div>
                          <?php endif; ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <form class="mt-3" method="POST" enctype="multipart/form-data" action="messages-inbox.php">
              <input type="hidden" name="message_id" value="<?php echo (int)$message->id; ?>">
              <div class="form-group">
                <label for="reply_body">Reply</label>
                <textarea class="form-control" id="reply_body" name="reply_body" rows="3" required></textarea>
              </div>
              <div class="form-group">
                <label for="reply_attachment">Attachment (optional)</label>
                <input type="file" id="reply_attachment" name="reply_attachment" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt">
                <small class="text-muted d-block">Up to 10MB.</small>
              </div>
              <button type="submit" class="btn btn-primary">Send Reply</button>
              <a class="btn btn-secondary ml-2" href="messages-inbox.php">Cancel</a>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include './footer.php'; ?>
