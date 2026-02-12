<?php
  $bodyClass = 'mobile-home-body';
  $extraCss = ['css/mobile-home.css'];
  $hideSidebar = true;
  $hideSidebarToggle = true;
  include './header.php';

  $contactError = '';
  $contactMsg = '';
  $contactsDataPath = __DIR__ . '/data/contacts.json';
  $contactsDataDir = __DIR__ . '/data';
  $contactsImgDir = __DIR__ . '/img/contacts';
  $contactsImgUrl = 'img/contacts';

  $defaultContacts = [
    [
      'id' => 'mayor',
      'photo' => 'img/mayor.png',
      'name' => 'कृष्णराज पण्डित',
      'role' => 'नगर प्रमुख',
      'email' => 'mayor@shuklagandakimun.gov.np',
      'phone' => '9856087111'
    ],
    [
      'id' => 'deputymayor',
      'photo' => 'img/deputymayor.png',
      'name' => 'खुम बहादुर वि.क (राजु)',
      'role' => 'उप- प्रमुख',
      'email' => 'deputymayor@shuklagandakimun.gov.np',
      'phone' => '9856081222'
    ],
    [
      'id' => 'spokesperson',
      'photo' => 'img/spokesperson.png',
      'name' => 'सचिन भुजेल (प्रवक्ता)',
      'role' => 'वडा अध्यक्ष',
      'email' => 'ward3shukla@gmail.com',
      'phone' => '9856087003'
    ],
    [
      'id' => 'chief_officer',
      'photo' => 'img/chief-officer.png',
      'name' => 'शिशिर पौडेल',
      'role' => 'प्रमुख प्रशासनकीय अधिकृत',
      'email' => 'sanskritpaudel@gmail.com',
      'phone' => '9856004111'
    ],
    [
      'id' => 'info_officer',
      'photo' => 'img/info-officer.png',
      'name' => 'अनिल खनाल',
      'role' => 'सूचना अधिकारी',
      'email' => 'shuklasuchana@gmail.com',
      'phone' => '9856043417'
    ]
  ];

  $contacts = $defaultContacts;
  if (is_readable($contactsDataPath)) {
    $decoded = json_decode(file_get_contents($contactsDataPath), true);
    if (is_array($decoded) && !empty($decoded)) {
      $contacts = $decoded;
    }
  }

  $getInitials = function ($name) {
    $name = trim((string)$name);
    if ($name === '') {
      return '';
    }
    if (function_exists('mb_substr')) {
      return mb_substr($name, 0, 2);
    }
    return substr($name, 0, 2);
  };

  $saveContacts = function ($list) use ($contactsDataDir, $contactsDataPath, &$contactError) {
    if (!is_dir($contactsDataDir)) {
      if (!mkdir($contactsDataDir, 0755, true) && !is_dir($contactsDataDir)) {
        $contactError = 'Could not create contacts data folder.';
        return false;
      }
    }
    $payload = json_encode(array_values($list), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($payload === false || file_put_contents($contactsDataPath, $payload) === false) {
      $contactError = 'Could not save contacts.';
      return false;
    }
    return true;
  };

  if ($isCreator && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_dir($contactsImgDir)) {
      if (!mkdir($contactsImgDir, 0755, true) && !is_dir($contactsImgDir)) {
        $contactError = 'Could not create contacts image folder.';
      }
    }

    if (!$contactError && isset($_POST['delete_contact'], $_POST['contact_id'])) {
      $id = trim($_POST['contact_id']);
      $next = [];
      foreach ($contacts as $item) {
        if (($item['id'] ?? '') === $id) {
          $photo = $item['photo'] ?? '';
          if (strpos($photo, $contactsImgUrl . '/') === 0) {
            $candidate = $contactsImgDir . DIRECTORY_SEPARATOR . basename($photo);
            if (is_file($candidate)) {
              @unlink($candidate);
            }
          }
          continue;
        }
        $next[] = $item;
      }
      if ($saveContacts($next)) {
        $contacts = $next;
        $contactMsg = 'Contact deleted.';
      }
    }

    if (!$contactError && isset($_POST['update_contact'], $_POST['contact_id'])) {
      $id = trim($_POST['contact_id']);
      $name = trim($_POST['name'] ?? '');
      $role = trim($_POST['role'] ?? '');
      $email = trim($_POST['email'] ?? '');
      $phone = trim($_POST['phone'] ?? '');

      if ($name === '' || $role === '' || $email === '' || $phone === '') {
        $contactError = 'All fields are required.';
      } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contactError = 'Enter a valid email address.';
      }

      if (!$contactError) {
        $updated = [];
        foreach ($contacts as $item) {
          if (($item['id'] ?? '') === $id) {
            $photoPath = $item['photo'] ?? '';

            if (!empty($_FILES['photo']['name'])) {
              $tmpName = $_FILES['photo']['tmp_name'] ?? '';
              $error = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
              $size = $_FILES['photo']['size'] ?? 0;
              $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
              $maxBytes = 5 * 1024 * 1024;

              if ($error === UPLOAD_ERR_OK && $size <= $maxBytes && is_uploaded_file($tmpName)) {
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExt, true) && @getimagesize($tmpName)) {
                  $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
                  if ($slug === '') {
                    $slug = 'contact';
                  }
                  $filename = $slug . '.' . $ext;
                  $counter = 2;
                  while (file_exists($contactsImgDir . DIRECTORY_SEPARATOR . $filename)) {
                    $filename = $slug . '-' . $counter . '.' . $ext;
                    $counter++;
                  }
                  $destination = $contactsImgDir . DIRECTORY_SEPARATOR . $filename;
                  if (move_uploaded_file($tmpName, $destination)) {
                    if (strpos($photoPath, $contactsImgUrl . '/') === 0) {
                      $old = $contactsImgDir . DIRECTORY_SEPARATOR . basename($photoPath);
                      if (is_file($old)) {
                        @unlink($old);
                      }
                    }
                    $photoPath = $contactsImgUrl . '/' . $filename;
                  }
                }
              }
            }

            $item['name'] = $name;
            $item['role'] = $role;
            $item['email'] = $email;
            $item['phone'] = $phone;
            $item['photo'] = $photoPath;
          }
          $updated[] = $item;
        }

        if ($saveContacts($updated)) {
          $contacts = $updated;
          $contactMsg = 'Contact updated.';
        }
      }
    }

    if (!$contactError && isset($_POST['add_contact'])) {
      $name = trim($_POST['name'] ?? '');
      $role = trim($_POST['role'] ?? '');
      $email = trim($_POST['email'] ?? '');
      $phone = trim($_POST['phone'] ?? '');

      if ($name === '' || $role === '' || $email === '' || $phone === '') {
        $contactError = 'All fields are required.';
      } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contactError = 'Enter a valid email address.';
      } elseif (empty($_FILES['photo']['name'])) {
        $contactError = 'Please upload a photo.';
      }

      $photoPath = '';
      if (!$contactError) {
        $tmpName = $_FILES['photo']['tmp_name'] ?? '';
        $error = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
        $size = $_FILES['photo']['size'] ?? 0;
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        $maxBytes = 5 * 1024 * 1024;

        if ($error !== UPLOAD_ERR_OK || $size > $maxBytes || !is_uploaded_file($tmpName)) {
          $contactError = 'Invalid photo upload.';
        } else {
          $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
          if (!in_array($ext, $allowedExt, true) || !@getimagesize($tmpName)) {
            $contactError = 'Please upload a valid image (JPG, PNG, WebP).';
          } else {
            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
            if ($slug === '') {
              $slug = 'contact';
            }
            $filename = $slug . '.' . $ext;
            $counter = 2;
            while (file_exists($contactsImgDir . DIRECTORY_SEPARATOR . $filename)) {
              $filename = $slug . '-' . $counter . '.' . $ext;
              $counter++;
            }
            $destination = $contactsImgDir . DIRECTORY_SEPARATOR . $filename;
            if (move_uploaded_file($tmpName, $destination)) {
              $photoPath = $contactsImgUrl . '/' . $filename;
            } else {
              $contactError = 'Could not save the photo.';
            }
          }
        }
      }

      if (!$contactError) {
        try {
          $id = bin2hex(random_bytes(6));
        } catch (Exception $e) {
          $id = uniqid();
        }
        $contacts[] = [
          'id' => $id,
          'photo' => $photoPath,
          'name' => $name,
          'role' => $role,
          'email' => $email,
          'phone' => $phone
        ];
        if ($saveContacts($contacts)) {
          $contactMsg = 'Contact added.';
        }
      }
    }
  }
?>

  <div id="content-wrapper">
    <div class="app-shell">
      <section class="menu-section" style="margin-top:4px;">
        <div class="section-title"><?php echo htmlspecialchars(i18n_t('contacts.title'), ENT_QUOTES, 'UTF-8'); ?></div>
        <p class="contact-hint" style="margin-top:2px;"><?php echo htmlspecialchars(i18n_t('contacts.subtitle'), ENT_QUOTES, 'UTF-8'); ?></p>

        <?php if ($contactError): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($contactError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($contactMsg): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($contactMsg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="contact-section" style="box-shadow: 0 12px 30px rgba(24,32,66,0.16);">
          <div class="contact-grid">
            <?php foreach ($contacts as $contact): ?>
              <article class="contact-card">
                <div class="contact-avatar">
                  <img src="<?php echo htmlspecialchars($contact['photo'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($contact['name'], ENT_QUOTES, 'UTF-8'); ?>">
                  <span class="avatar-fallback"><?php echo htmlspecialchars($getInitials($contact['name']), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="contact-meta">
                  <h4><?php echo htmlspecialchars($contact['name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                  <p class="contact-role"><?php echo htmlspecialchars($contact['role'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <p class="contact-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <p class="contact-phone"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($contact['phone'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
              </article>

              <?php if ($isCreator): ?>
                <form class="contact-editor" method="POST" enctype="multipart/form-data">
                  <input type="hidden" name="contact_id" value="<?php echo htmlspecialchars($contact['id'], ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="form-row">
                    <label><?php echo htmlspecialchars(i18n_t('contacts.photo'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="file" name="photo" accept="image/*">
                  </div>
                  <div class="form-row">
                    <label><?php echo htmlspecialchars(i18n_t('contacts.name'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($contact['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                  </div>
                  <div class="form-row">
                    <label><?php echo htmlspecialchars(i18n_t('contacts.role'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="text" name="role" value="<?php echo htmlspecialchars($contact['role'], ENT_QUOTES, 'UTF-8'); ?>" required>
                  </div>
                  <div class="form-row">
                    <label><?php echo htmlspecialchars(i18n_t('contacts.email'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($contact['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                  </div>
                  <div class="form-row">
                    <label><?php echo htmlspecialchars(i18n_t('contacts.phone'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($contact['phone'], ENT_QUOTES, 'UTF-8'); ?>" required>
                  </div>
                  <div class="contact-actions">
                    <button type="submit" class="btn btn-sm btn-primary" name="update_contact"><?php echo htmlspecialchars(i18n_t('contacts.update'), ENT_QUOTES, 'UTF-8'); ?></button>
                    <button type="submit" class="btn btn-sm btn-outline-danger" name="delete_contact"><?php echo htmlspecialchars(i18n_t('contacts.delete'), ENT_QUOTES, 'UTF-8'); ?></button>
                  </div>
                </form>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <p class="contact-hint"><?php echo htmlspecialchars(i18n_t('contacts.photos_loaded'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <?php if ($isCreator): ?>
          <div class="message-card" style="margin-top:16px;">
            <form method="POST" enctype="multipart/form-data">
              <div class="form-row">
                <label><?php echo htmlspecialchars(i18n_t('contacts.photo'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="file" name="photo" accept="image/*" required>
              </div>
              <div class="form-row">
                <label><?php echo htmlspecialchars(i18n_t('contacts.name'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" name="name" required>
              </div>
              <div class="form-row">
                <label><?php echo htmlspecialchars(i18n_t('contacts.role'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" name="role" required>
              </div>
              <div class="form-row">
                <label><?php echo htmlspecialchars(i18n_t('contacts.email'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="email" name="email" required>
              </div>
              <div class="form-row">
                <label><?php echo htmlspecialchars(i18n_t('contacts.phone'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" name="phone" required>
              </div>
              <button type="submit" class="btn btn-primary btn-block" name="add_contact"><?php echo htmlspecialchars(i18n_t('contacts.add'), ENT_QUOTES, 'UTF-8'); ?></button>
            </form>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </div>

<?php include './footer.php'; ?>
