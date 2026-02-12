<?php
  $bodyClass = 'mobile-home-body';
  $extraCss = ['css/mobile-home.css'];
  $hideSidebar = false;
  $hideSidebarToggle = false;
  include './header.php';

  $uploadError = '';
  $uploadMsg = '';
  $sliderDefaults = ['img/pic1.jpg', 'img/pic 2.jpg'];
  $sliderDataPath = __DIR__ . '/data/mobile-slider.json';
  $sliderDataDir = __DIR__ . '/data';
  $sliderDir = __DIR__ . '/img';
  $sliderUrlBase = 'img';

  $sliderItems = [];
  foreach ($sliderDefaults as $path) {
    $sliderItems[] = ['src' => $path, 'caption' => ''];
  }
  if (is_readable($sliderDataPath)) {
    $decoded = json_decode(file_get_contents($sliderDataPath), true);
    if (is_array($decoded)) {
      $clean = [];
      foreach ($decoded as $entry) {
        if (is_string($entry) && $entry !== '') {
          $clean[] = ['src' => $entry, 'caption' => ''];
          continue;
        }
        if (is_array($entry) && !empty($entry['src']) && is_string($entry['src'])) {
          $clean[] = [
            'src' => $entry['src'],
            'caption' => isset($entry['caption']) && is_string($entry['caption']) ? $entry['caption'] : ''
          ];
        }
      }
      if ($clean) {
        $sliderItems = $clean;
      }
    }
  }

  if ($isCreator && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_slide'])) {
    $deleteIndex = isset($_POST['slide_index']) ? (int)$_POST['slide_index'] : -1;
    if ($deleteIndex >= 0 && isset($sliderItems[$deleteIndex])) {
      $deleted = $sliderItems[$deleteIndex];
      array_splice($sliderItems, $deleteIndex, 1);

      if (!is_dir($sliderDataDir)) {
        if (!mkdir($sliderDataDir, 0755, true) && !is_dir($sliderDataDir)) {
          $uploadError = 'Could not create the slider data folder.';
        }
      }

      if (!$uploadError) {
        $payload = json_encode(array_values($sliderItems), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($payload === false || file_put_contents($sliderDataPath, $payload) === false) {
          $uploadError = 'Could not save slider list.';
        } else {
          $uploadMsg = 'Slide deleted.';
        }
      }

      if (!$uploadError && is_string($deleted['src'] ?? '')) {
        $src = $deleted['src'];
        if (strpos($src, 'img/') === 0) {
          $relative = substr($src, 4);
          if ($relative !== '' && strpos($relative, '..') === false) {
            $candidate = __DIR__ . '/img/' . str_replace(['\\', '//'], '/', $relative);
            $imgRoot = realpath(__DIR__ . '/img');
            $target = realpath($candidate);
            $protected = ['pic1.jpg', 'pic 2.jpg'];
            if ($target && $imgRoot && strpos($target, $imgRoot) === 0) {
              if (!in_array(basename($target), $protected, true) && is_file($target)) {
                @unlink($target);
              }
            }
          }
        }
      }
    }
  }

  if ($isCreator && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_slider'])) {
    if (!is_dir($sliderDir)) {
      if (!mkdir($sliderDir, 0755, true) && !is_dir($sliderDir)) {
        $uploadError = 'Could not create the slider image folder.';
      }
    }

    if (!$uploadError && !is_dir($sliderDataDir)) {
      if (!mkdir($sliderDataDir, 0755, true) && !is_dir($sliderDataDir)) {
        $uploadError = 'Could not create the slider data folder.';
      }
    }

    if (!$uploadError) {
      $replaceExisting = isset($_POST['replace_slides']);
      $updatedSlides = $replaceExisting ? [] : $sliderItems;
      $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
      $maxBytes = 5 * 1024 * 1024;
      $captions = [];
      if (!empty($_POST['slider_captions']) && is_string($_POST['slider_captions'])) {
        $lines = preg_split('/\r\n|\r|\n/', trim($_POST['slider_captions']));
        foreach ($lines as $line) {
          $captions[] = trim($line);
        }
      }

      if (!isset($_FILES['slider_photos'])) {
        $uploadError = 'Please choose at least one photo.';
      } else {
        $files = $_FILES['slider_photos'];
        $count = is_array($files['name']) ? count($files['name']) : 0;
        $saved = 0;

        for ($i = 0; $i < $count; $i++) {
          $name = $files['name'][$i] ?? '';
          $tmpName = $files['tmp_name'][$i] ?? '';
          $error = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
          $size = $files['size'][$i] ?? 0;

          if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
          }
          if ($error !== UPLOAD_ERR_OK) {
            $uploadError = 'One or more uploads failed.';
            continue;
          }
          if ($size > $maxBytes) {
            $uploadError = 'Each photo must be under 5MB.';
            continue;
          }
          if (!is_uploaded_file($tmpName)) {
            $uploadError = 'Invalid upload detected.';
            continue;
          }

          $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
          if (!in_array($ext, $allowedExt, true)) {
            $uploadError = 'Only JPG, PNG, or WebP files are allowed.';
            continue;
          }

          $imageInfo = @getimagesize($tmpName);
          if ($imageInfo === false) {
            $uploadError = 'Please upload valid image files.';
            continue;
          }

          $rawCaption = $captions[$saved] ?? '';
          $baseName = $rawCaption !== '' ? $rawCaption : pathinfo($name, PATHINFO_FILENAME);
          $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $baseName), '-'));
          if ($slug === '') {
            $slug = 'slide';
          }
          $filename = $slug . '.' . $ext;
          $counter = 2;
          while (file_exists($sliderDir . DIRECTORY_SEPARATOR . $filename)) {
            $filename = $slug . '-' . $counter . '.' . $ext;
            $counter++;
          }
          $destination = $sliderDir . DIRECTORY_SEPARATOR . $filename;

          if (!move_uploaded_file($tmpName, $destination)) {
            $uploadError = 'Could not save one or more photos.';
            continue;
          }

          $updatedSlides[] = [
            'src' => $sliderUrlBase . '/' . $filename,
            'caption' => $rawCaption
          ];
          $saved++;
        }

        if ($saved > 0 && !$uploadError) {
          $payload = json_encode(array_values($updatedSlides), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
          if ($payload === false || file_put_contents($sliderDataPath, $payload) === false) {
            $uploadError = 'Could not save slider list.';
          } else {
            $sliderItems = $updatedSlides;
            $uploadMsg = $replaceExisting ? 'Slider photos replaced.' : 'Slider photos updated.';
          }
        } elseif ($saved === 0 && !$uploadError) {
          $uploadError = 'Please choose at least one photo.';
        }
      }
    }
  }

  // Build ticket links for the Tickets menu page.
  $ticketLinks = [];
  $infoLinks = [];

  // Inbox count for officials to surface messages at a glance.
  $inboxCount = 0;
  if ($isOfficial || $isAdmin) {
    $hasMessagesTable = false;
    $tblCheck = $db->query("SHOW TABLES LIKE 'messages'");
    if ($tblCheck && $tblCheck->num_rows > 0) {
      $hasMessagesTable = true;
    }

    if ($hasMessagesTable) {
      if ($isAdmin) {
        $cntRes = $db->query("SELECT COUNT(*) AS total FROM messages");
        $row = $cntRes ? $cntRes->fetch_object() : null;
        $inboxCount = (int)($row->total ?? 0);
      } else {
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM messages WHERE recipient_role = ?");
        if ($stmt) {
          $stmt->bind_param('s', $role);
          $stmt->execute();
          $res = $stmt->get_result();
          $row = $res ? $res->fetch_object() : null;
          $inboxCount = (int)($row->total ?? 0);
          $stmt->close();
        }
      }
    }
  }

  if ($isClient) {
    $ticketLinks[] = ['href' => 'ticket.php', 'label' => 'Open New Ticket', 'icon' => 'fa-plus-circle'];
    $ticketLinks[] = ['href' => 'mytickets.php?status=pending', 'label' => 'My Pending Tickets', 'icon' => 'fa-clock'];
  } else {
    $ticketLinks[] = ['href' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'fa-tachometer-alt'];
    $ticketLinks[] = ['href' => 'open.php', 'label' => 'Open', 'icon' => 'fa-lock-open'];
    $ticketLinks[] = ['href' => 'solved.php', 'label' => 'Solved', 'icon' => 'fa-anchor'];
    $ticketLinks[] = ['href' => 'closed.php', 'label' => 'Closed', 'icon' => 'fa-times-circle'];
    $ticketLinks[] = ['href' => 'pending.php', 'label' => 'Pending', 'icon' => 'fa-adjust'];
    $ticketLinks[] = ['href' => 'unassigned.php', 'label' => 'Unassigned', 'icon' => 'fa-at'];

    if (!$isModerator) {
      $ticketLinks[] = ['href' => 'mytickets.php', 'label' => 'My Tickets', 'icon' => 'fa-award'];
    }

    if ($isAdmin) {
      $ticketLinks[] = ['href' => 'team.php', 'label' => 'Teams', 'icon' => 'fa-users'];
      $ticketLinks[] = ['href' => 'users.php', 'label' => 'Users', 'icon' => 'fa-users'];
    }
  }

  $infoLinks[] = ['href' => 'documents-info.php', 'label' => 'Documents Info', 'icon' => 'fa-file-alt'];
  $infoLinks[] = ['href' => 'contacts.php', 'label' => 'Contacts', 'icon' => 'fa-address-book'];

  if ($isClient) {
    $infoLinks[] = ['href' => 'message.php', 'label' => 'Send Message', 'icon' => 'fa-paper-plane'];
    $infoLinks[] = ['href' => 'my-messages.php', 'label' => 'My Messages', 'icon' => 'fa-inbox'];
  } elseif ($isOfficial || $isAdmin) {
    $infoLinks[] = ['href' => 'messages-inbox.php', 'label' => 'Inbox', 'icon' => 'fa-inbox'];
  }

?>

  <div id="content-wrapper">
    <div class="app-shell">
      <section class="hero-card">
        <?php if ($uploadError): ?>
          <div class="alert alert-danger mb-2"><?php echo htmlspecialchars($uploadError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($uploadMsg): ?>
          <div class="alert alert-success mb-2"><?php echo htmlspecialchars($uploadMsg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="hero-slider" data-rotation="6500">
          <?php foreach ($sliderItems as $index => $item): ?>
            <div class="hero-slide <?php echo $index === 0 ? 'is-active' : ''; ?>">
              <img src="<?php echo htmlspecialchars($item['src'], ENT_QUOTES, 'UTF-8'); ?>" alt="Slider photo <?php echo $index + 1; ?>" class="hero-photo">
              <span class="hero-slide-caption" data-caption="<?php echo htmlspecialchars($item['caption'], ENT_QUOTES, 'UTF-8'); ?>"></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="hero-caption-box" data-caption><?php echo htmlspecialchars($sliderItems[0]['caption'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
        <?php if (count($sliderItems) > 1): ?>
          <div class="hero-dots">
            <?php foreach ($sliderItems as $index => $item): ?>
              <button class="dot <?php echo $index === 0 ? 'active' : ''; ?>" type="button" aria-label="Show slide <?php echo $index + 1; ?>"></button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($isCreator): ?>
          <form class="slider-uploader" method="POST" enctype="multipart/form-data">
            <div class="slider-upload-title">Update slider photos</div>
            <div class="slider-upload-row">
              <input type="file" name="slider_photos[]" accept="image/*" multiple required>
              <button class="btn btn-sm btn-primary" type="submit" name="upload_slider">Upload</button>
            </div>
            <label class="slider-upload-label" for="slider-captions">Captions (one per line, in file order)</label>
            <textarea id="slider-captions" name="slider_captions" rows="3" placeholder="Example: Main office opening"></textarea>
            <label class="slider-upload-option">
              <input type="checkbox" name="replace_slides" value="1">
              Replace existing slides
            </label>
            <div class="slider-upload-hint">JPG, PNG, or WebP up to 5MB each. Captions become the filename.</div>
          </form>

          <?php if (!empty($sliderItems)): ?>
            <div class="slider-manager">
              <div class="slider-upload-title">Existing slides</div>
              <?php foreach ($sliderItems as $index => $item): ?>
                <div class="slider-manager-row">
                  <div class="slider-manager-text">
                    <div class="slider-manager-name"><?php echo htmlspecialchars(basename($item['src']), ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php if (!empty($item['caption'])): ?>
                      <div class="slider-manager-caption"><?php echo htmlspecialchars($item['caption'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                  </div>
                  <form method="POST" class="slider-manager-actions">
                    <input type="hidden" name="slide_index" value="<?php echo $index; ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit" name="delete_slide">Delete</button>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </section>

      <section class="poster-links">
        <div class="menu-grid">
          <a class="menu-card poster-card" href="tickets-menu.php">
            <span class="menu-icon"><i class="fas fa-ticket-alt"></i></span>
            <span class="menu-label">सुझाव र गुनासो</span>
            <span class="menu-subtext">टिकट सम्बन्धी सेवा</span>
          </a>
          <a class="menu-card poster-card" href="general-info-menu.php">
            <span class="menu-icon"><i class="fas fa-info-circle"></i></span>
            <span class="menu-label">सामान्य जानकारी</span>
            <span class="menu-subtext">दस्तावेज र सम्पर्क</span>
          </a>
        </div>
      </section>

      <section class="menu-section">
        <div class="section-title"><?php echo htmlspecialchars(i18n_t('home.quick_links'), ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="menu-grid">
          <a class="menu-card" href="tickets-menu.php">
            <span class="menu-icon"><i class="fas fa-ticket-alt"></i></span>
            <span class="menu-label"><?php echo htmlspecialchars(i18n_t('home.tickets'), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="menu-subtext"><?php echo $isClient ? htmlspecialchars(i18n_t('home.tickets.sub'), ENT_QUOTES, 'UTF-8') : 'All ticket views'; ?></span>
          </a>
          <?php if ($isClient): ?>
            <a class="menu-card" href="ticket.php">
              <span class="menu-icon"><i class="fas fa-plus-circle"></i></span>
              <span class="menu-label"><?php echo htmlspecialchars(i18n_t('home.new_ticket'), ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="menu-subtext"><?php echo htmlspecialchars(i18n_t('home.new_ticket.sub'), ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
          <?php endif; ?>
          <a class="menu-card" href="documents-info.php">
            <span class="menu-icon"><i class="fas fa-file-alt"></i></span>
            <span class="menu-label"><?php echo htmlspecialchars(i18n_t('home.documents'), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="menu-subtext"><?php echo htmlspecialchars(i18n_t('home.documents.sub'), ENT_QUOTES, 'UTF-8'); ?></span>
          </a>
          <a class="menu-card" href="contacts.php">
            <span class="menu-icon"><i class="fas fa-address-book"></i></span>
            <span class="menu-label"><?php echo htmlspecialchars(i18n_t('home.contacts'), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="menu-subtext"><?php echo htmlspecialchars(i18n_t('home.contacts.sub'), ENT_QUOTES, 'UTF-8'); ?></span>
          </a>
          <?php if ($isOfficial || $isAdmin): ?>
            <a class="menu-card" href="messages-inbox.php">
              <span class="menu-icon"><i class="fas fa-inbox"></i></span>
              <span class="menu-label">Inbox</span>
              <span class="menu-subtext">
                <?php echo $inboxCount > 0 ? $inboxCount . ' message' . ($inboxCount === 1 ? '' : 's') : 'No messages yet'; ?>
              </span>
            </a>
          <?php endif; ?>
          <?php if ($isClient): ?>
            <a class="menu-card" href="message.php">
              <span class="menu-icon"><i class="fas fa-paper-plane"></i></span>
              <span class="menu-label"><?php echo htmlspecialchars(i18n_t('home.message'), ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="menu-subtext"><?php echo htmlspecialchars(i18n_t('home.message.sub'), ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
            <a class="menu-card" href="my-messages.php">
              <span class="menu-icon"><i class="fas fa-inbox"></i></span>
              <span class="menu-label"><?php echo htmlspecialchars(i18n_t('home.my_messages'), ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="menu-subtext"><?php echo htmlspecialchars(i18n_t('home.my_messages.sub'), ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
          <?php endif; ?>
          <?php if ($isCreator || $isAdmin): ?>
            <a class="menu-card" href="users.php">
              <span class="menu-icon"><i class="fas fa-users-cog"></i></span>
              <span class="menu-label">Users</span>
              <span class="menu-subtext">Manage accounts &amp; roles</span>
            </a>
          <?php endif; ?>
        </div>

      </section>

    </div>
  </div>

<?php include './footer.php'; ?>

<script>
  (function () {
    const slider = document.querySelector('.hero-slider');
    if (!slider) return;

    const slides = Array.from(slider.querySelectorAll('.hero-slide'));
    const dots = Array.from(document.querySelectorAll('.hero-dots .dot'));
    const caption = document.querySelector('.hero-caption-box');

    let activeIndex = 0;
    const rotation = Number(slider.dataset.rotation || 6500);

    const showSlide = (index) => {
      slides.forEach((slide, i) => {
        slide.classList.toggle('is-active', i === index);
      });
      dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
      });
      if (caption) {
        const captionSpan = slides[index].querySelector('[data-caption]');
        caption.textContent = captionSpan ? captionSpan.dataset.caption : '';
      }
      activeIndex = index;
    };

    showSlide(activeIndex);
    if (slides.length < 2) return;

    const nextSlide = () => {
      const nextIndex = (activeIndex + 1) % slides.length;
      showSlide(nextIndex);
    };

    let timer = setInterval(nextSlide, rotation);

    dots.forEach((dot, index) => {
      dot.addEventListener('click', () => {
        showSlide(index);
        clearInterval(timer);
        timer = setInterval(nextSlide, rotation);
      });
    });
  })();

</script>
