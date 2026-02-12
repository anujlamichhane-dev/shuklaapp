<?php
  $bodyClass = 'mobile-home-body';
  $extraCss = ['css/mobile-home.css'];
  $hideSidebar = true;
  $hideSidebarToggle = true;
  include './header.php';
  require_once './src/Database.php';

  $recipients = [
    'mayor' => ['label' => 'कृष्णराज पण्डित (नगर प्रमुख)', 'email' => 'mayor@shuklagandakimun.gov.np'],
    'deputymayor' => ['label' => 'खुम बहादुर बि.क (उप– प्रमुख)', 'email' => 'deputymayor@shuklagandakimun.gov.np'],
    'spokesperson' => ['label' => 'सचिन भुजेल (वडा अध्यक्ष)', 'email' => 'ward3shukla@gmail.com'],
    'chief_officer' => ['label' => 'शिशिर पौडेल (प्रमुख प्रशासनकीय अधिकृत)', 'email' => 'sanskritpaudel@gmail.com'],
    'info_officer' => ['label' => 'अनिल खनाल (सूचना अधिकारी)', 'email' => 'shuklasuchana@gmail.com'],
    'office_info' => ['label' => 'नगर कार्यालय (info@)', 'email' => 'info@shuklagandakimun.gov.np'],
  ];

  $statusMsg = '';
  $statusType = '';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadDir = __DIR__ . '/data/message_uploads';
    $allowedExt = ['pdf','jpg','jpeg','png','gif','webp','doc','docx','ppt','pptx','xls','xlsx','csv','txt'];
    $maxBytes = 10 * 1024 * 1024; // 10MB

    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0755, true);
    }

    $hasUpload = isset($_FILES['attachment']) && ($_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE);
    $uploadPathRel = null;
    $uploadName = null;
    $uploadMime = null;
    $uploadSize = null;

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $recipient = $_POST['recipient'] ?? '';

    if (!isset($recipients[$recipient])) {
      $statusMsg = 'Please choose a recipient.';
      $statusType = 'error';
    } elseif (strlen($name) < 2) {
      $statusMsg = 'Enter your name.';
      $statusType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $statusMsg = 'Enter a valid email address.';
      $statusType = 'error';
    } elseif (strlen($subject) < 3) {
      $statusMsg = 'Subject must be at least 3 characters.';
      $statusType = 'error';
    } elseif (strlen($message) < 10) {
      $statusMsg = 'Message must be at least 10 characters.';
      $statusType = 'error';
    } elseif ($hasUpload && ($_FILES['attachment']['error'] !== UPLOAD_ERR_OK)) {
      $statusMsg = 'File upload failed. Please try again.';
      $statusType = 'error';
    } elseif ($hasUpload) {
      $uploadName = $_FILES['attachment']['name'] ?? '';
      $tmpName = $_FILES['attachment']['tmp_name'] ?? '';
      $uploadSize = (int)($_FILES['attachment']['size'] ?? 0);
      $uploadMime = $_FILES['attachment']['type'] ?? '';
      $ext = strtolower(pathinfo($uploadName, PATHINFO_EXTENSION));

      if (!in_array($ext, $allowedExt, true)) {
        $statusMsg = 'Unsupported file type. Allowed: pdf, images, office docs, txt, csv.';
        $statusType = 'error';
      } elseif ($uploadSize > $maxBytes) {
        $statusMsg = 'File is too large. Max 10MB.';
        $statusType = 'error';
      } elseif (!is_uploaded_file($tmpName)) {
        $statusMsg = 'Invalid upload. Please try again.';
        $statusType = 'error';
      } else {
        $safeSlug = preg_replace('/[^a-zA-Z0-9-_]/', '_', pathinfo($uploadName, PATHINFO_FILENAME));
        $uniqueName = $safeSlug . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $uploadDir . DIRECTORY_SEPARATOR . $uniqueName;
        if (move_uploaded_file($tmpName, $dest)) {
          $uploadPathRel = 'data/message_uploads/' . $uniqueName;
        } else {
          $statusMsg = 'Could not save the uploaded file.';
          $statusType = 'error';
        }
      }
    }

    // Only insert if all validations (including upload) passed
    if (!$statusMsg) {
      // store in database inbox for officials
      $db = Database::getInstance();
      $db->query("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient_role VARCHAR(50) NOT NULL,
        recipient_email VARCHAR(191) NOT NULL,
        sender_user_id INT DEFAULT NULL,
        sender_name VARCHAR(191) NOT NULL,
        sender_email VARCHAR(191) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        attachment_path VARCHAR(255) DEFAULT NULL,
        attachment_name VARCHAR(255) DEFAULT NULL,
        attachment_type VARCHAR(100) DEFAULT NULL,
        attachment_size INT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

      $db->query("CREATE TABLE IF NOT EXISTS messages_replies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        replier_role VARCHAR(50) NOT NULL,
        replier_email VARCHAR(191) NOT NULL,
        body TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_msg_reply_msg FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

      // Ensure attachment columns exist for older deployments
      $colCheck = $db->query("SHOW COLUMNS FROM messages LIKE 'attachment_path'");
      if ($colCheck && $colCheck->num_rows === 0) {
        $db->query("ALTER TABLE messages 
          ADD COLUMN attachment_path VARCHAR(255) DEFAULT NULL,
          ADD COLUMN attachment_name VARCHAR(255) DEFAULT NULL,
          ADD COLUMN attachment_type VARCHAR(100) DEFAULT NULL,
          ADD COLUMN attachment_size INT UNSIGNED DEFAULT NULL");
      }
      // Ensure sender_user_id exists for linking to logged-in users
      $colCheck = $db->query("SHOW COLUMNS FROM messages LIKE 'sender_user_id'");
      if ($colCheck && $colCheck->num_rows === 0) {
        $db->query("ALTER TABLE messages ADD COLUMN sender_user_id INT DEFAULT NULL");
      }

      $stmt = $db->prepare("INSERT INTO messages (recipient_role, recipient_email, sender_user_id, sender_name, sender_email, subject, body, attachment_path, attachment_name, attachment_type, attachment_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      if ($stmt) {
        $senderUserId = $user->id ?? null;
        $stmt->bind_param(
          'ssisssssssi',
          $recipient,
          $recipients[$recipient]['email'],
          $senderUserId,
          $name,
          $email,
          $subject,
          $message,
          $uploadPathRel,
          $uploadName,
          $uploadMime,
          $uploadSize
        );
        $stmt->execute();
        $stmt->close();
      }

      $statusMsg = 'Message sent to official inbox.';
      $statusType = 'success';
    }
  }
?>

  <div id="content-wrapper">
    <div class="app-shell">
      <section class="menu-section" style="margin-top:6px;">
        <div class="section-title"><?php echo htmlspecialchars(i18n_t('message.title'), ENT_QUOTES, 'UTF-8'); ?></div>
        <p class="contact-hint" style="margin-top:2px;"><?php echo htmlspecialchars(i18n_t('message.subtitle'), ENT_QUOTES, 'UTF-8'); ?></p>

        <?php if ($statusMsg): ?>
          <div class="alert alert-<?php echo $statusType === 'success' ? 'success' : 'danger'; ?>" role="alert" style="margin-bottom:12px;">
            <?php echo htmlspecialchars($statusMsg, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <div class="message-card">
          <form method="POST" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-row">
              <label for="recipient"><?php echo htmlspecialchars(i18n_t('message.sendto'), ENT_QUOTES, 'UTF-8'); ?></label>
              <select id="recipient" name="recipient" required>
                <option value=""><?php echo htmlspecialchars(i18n_t('message.select'), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php foreach ($recipients as $roleKey => $data): ?>
                  <option value="<?php echo htmlspecialchars($roleKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($_POST['recipient'] ?? '') === $roleKey) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($data['label'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-row">
              <label for="name"><?php echo htmlspecialchars(i18n_t('message.your_name'), ENT_QUOTES, 'UTF-8'); ?></label>
              <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="form-row">
              <label for="email"><?php echo htmlspecialchars(i18n_t('message.your_email'), ENT_QUOTES, 'UTF-8'); ?></label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="form-row">
              <label for="subject"><?php echo htmlspecialchars(i18n_t('message.subject'), ENT_QUOTES, 'UTF-8'); ?></label>
              <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="form-row">
              <label for="message"><?php echo htmlspecialchars(i18n_t('message.message'), ENT_QUOTES, 'UTF-8'); ?></label>
              <textarea id="message" name="message" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="form-row">
              <label for="attachment"><?php echo htmlspecialchars(i18n_t('message.attachment'), ENT_QUOTES, 'UTF-8'); ?></label>
              <input type="file" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt">
              <small class="text-muted"><?php echo htmlspecialchars(i18n_t('message.attachment_help'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?php echo htmlspecialchars(i18n_t('message.submit'), ENT_QUOTES, 'UTF-8'); ?></button>
          </form>
        </div>
      </section>
    </div>
  </div>

<?php include './footer.php'; ?>
