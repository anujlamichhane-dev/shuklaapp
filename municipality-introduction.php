<?php
  $bodyClass = 'mobile-home-body';
  $extraCss = ['css/mobile-home.css'];
  include './header.php';

  $dataPath = __DIR__ . '/data/municipality-introduction.json';
  $dataDir = __DIR__ . '/data';
  $imgDir = __DIR__ . '/img/municipality-introduction';
  $videoDir = __DIR__ . '/videos/municipality-introduction';
  $imgUrlBase = 'img/municipality-introduction';
  $videoUrlBase = 'videos/municipality-introduction';

  $saveError = '';
  $saveMsg = '';
  $entries = [];

  if (is_readable($dataPath)) {
    $decoded = json_decode(file_get_contents($dataPath), true);
    if (is_array($decoded)) {
      $entries = $decoded;
    }
  }

  $slugify = function ($value) {
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', (string)$value), '-'));
    return $slug !== '' ? $slug : 'intro';
  };

  $saveEntries = function ($payload) use ($dataDir, $dataPath, &$saveError, &$saveMsg) {
    if (!is_dir($dataDir)) {
      if (!mkdir($dataDir, 0755, true) && !is_dir($dataDir)) {
        $saveError = 'Could not create the data folder.';
        return false;
      }
    }
    $json = json_encode(array_values($payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($dataPath, $json) === false) {
      $saveError = 'Could not save updates.';
      return false;
    }
    $saveMsg = 'Municipality introduction updated.';
    return true;
  };

  $cleanupFiles = function ($paths, $rootDir) {
    if (!is_array($paths) || !is_dir($rootDir)) {
      return;
    }
    $root = realpath($rootDir);
    if ($root === false) {
      return;
    }
    foreach ($paths as $path) {
      if (!is_string($path) || $path === '') {
        continue;
      }
      $relative = str_replace(['\\', '//'], '/', $path);
      $relative = ltrim($relative, '/');
      $candidate = realpath($rootDir . DIRECTORY_SEPARATOR . basename($relative));
      if ($candidate && strpos($candidate, $root) === 0 && is_file($candidate)) {
        @unlink($candidate);
      }
    }
  };

  $getYoutubeEmbed = function ($url) {
    $url = trim((string)$url);
    if ($url === '') {
      return '';
    }
    $parts = parse_url($url);
    if (!is_array($parts)) {
      return '';
    }
    $host = strtolower($parts['host'] ?? '');
    $path = $parts['path'] ?? '';
    if (strpos($host, 'youtu.be') !== false) {
      $id = trim($path, '/');
      return $id !== '' ? 'https://www.youtube.com/embed/' . $id : '';
    }
    if (strpos($host, 'youtube.com') !== false) {
      if (strpos($path, '/embed/') === 0) {
        return 'https://www.youtube.com' . $path;
      }
      $query = [];
      parse_str($parts['query'] ?? '', $query);
      if (!empty($query['v'])) {
        return 'https://www.youtube.com/embed/' . $query['v'];
      }
    }
    return '';
  };

  if ($isCreator && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_intro'])) {
      $title = trim((string)($_POST['intro_title'] ?? ''));
      $description = trim((string)($_POST['intro_description'] ?? ''));
      $videoUrl = trim((string)($_POST['intro_video_url'] ?? ''));
      $images = [];
      $videoFile = '';

      if ($title === '' || $description === '') {
        $saveError = 'Please provide a title and description.';
      } else {
        if (!is_dir($imgDir)) {
          if (!mkdir($imgDir, 0755, true) && !is_dir($imgDir)) {
            $saveError = 'Could not create the image folder.';
          }
        }
        if (!is_dir($videoDir)) {
          if (!mkdir($videoDir, 0755, true) && !is_dir($videoDir)) {
            $saveError = 'Could not create the video folder.';
          }
        }

        if (!$saveError && isset($_FILES['intro_photos'])) {
          $files = $_FILES['intro_photos'];
          $count = is_array($files['name']) ? count($files['name']) : 0;
          $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
          $maxBytes = 5 * 1024 * 1024;
          $slug = $slugify($title);
          $savedIndex = 1;

          for ($i = 0; $i < $count; $i++) {
            $name = $files['name'][$i] ?? '';
            $tmpName = $files['tmp_name'][$i] ?? '';
            $error = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            $size = $files['size'][$i] ?? 0;

            if ($error === UPLOAD_ERR_NO_FILE) {
              continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
              $saveError = 'One or more image uploads failed.';
              continue;
            }
            if ($size > $maxBytes) {
              $saveError = 'Each image must be under 5MB.';
              continue;
            }
            if (!is_uploaded_file($tmpName)) {
              $saveError = 'Invalid image upload detected.';
              continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
              $saveError = 'Only JPG, PNG, or WebP images are allowed.';
              continue;
            }

            if (@getimagesize($tmpName) === false) {
              $saveError = 'Please upload valid image files.';
              continue;
            }

            $filename = $slug . '-' . $savedIndex . '.' . $ext;
            $savedIndex++;
            while (file_exists($imgDir . DIRECTORY_SEPARATOR . $filename)) {
              $filename = $slug . '-' . $savedIndex . '.' . $ext;
              $savedIndex++;
            }

            $destination = $imgDir . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($tmpName, $destination)) {
              $saveError = 'Could not save one or more images.';
              continue;
            }
            $images[] = $imgUrlBase . '/' . $filename;
          }
        }

        if (!$saveError && isset($_FILES['intro_video'])) {
          $file = $_FILES['intro_video'];
          $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
          if ($error !== UPLOAD_ERR_NO_FILE) {
            $allowedVideoExt = ['mp4', 'webm', 'ogg'];
            $maxVideoBytes = 50 * 1024 * 1024;
            $name = $file['name'] ?? '';
            $tmpName = $file['tmp_name'] ?? '';
            $size = $file['size'] ?? 0;

            if ($error !== UPLOAD_ERR_OK) {
              $saveError = 'Video upload failed.';
            } elseif ($size > $maxVideoBytes) {
              $saveError = 'Video must be under 50MB.';
            } elseif (!is_uploaded_file($tmpName)) {
              $saveError = 'Invalid video upload detected.';
            } else {
              $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
              if (!in_array($ext, $allowedVideoExt, true)) {
                $saveError = 'Only MP4, WebM, or OGG videos are allowed.';
              } else {
                $slug = $slugify($title);
                $filename = $slug . '.' . $ext;
                $counter = 2;
                while (file_exists($videoDir . DIRECTORY_SEPARATOR . $filename)) {
                  $filename = $slug . '-' . $counter . '.' . $ext;
                  $counter++;
                }
                $destination = $videoDir . DIRECTORY_SEPARATOR . $filename;
                if (!move_uploaded_file($tmpName, $destination)) {
                  $saveError = 'Could not save the video.';
                } else {
                  $videoFile = $videoUrlBase . '/' . $filename;
                }
              }
            }
          }
        }

        if (!$saveError) {
          if (empty($images) && $videoUrl === '' && $videoFile === '') {
            $saveError = 'Please upload at least one photo or a video.';
          } else {
            $entries[] = [
              'title' => $title,
              'description' => $description,
              'images' => $images,
              'video_url' => $videoUrl,
              'video_file' => $videoFile,
              'created_at' => date('c')
            ];
            $saveEntries($entries);
          }
        }
      }
    }

    if (isset($_POST['delete_intro']) && isset($_POST['intro_index'])) {
      $index = (int)$_POST['intro_index'];
      if (isset($entries[$index])) {
        $entry = $entries[$index];
        if (!empty($entry['images'])) {
          $cleanupFiles($entry['images'], $imgDir);
        }
        if (!empty($entry['video_file'])) {
          $cleanupFiles([$entry['video_file']], $videoDir);
        }
        array_splice($entries, $index, 1);
        $saveEntries($entries);
      }
    }
  }
?>

<div id="content-wrapper">
  <div class="app-shell">
    <section class="docs-hero">
      <div class="section-title">Shuklagandaki Municipality Introduction</div>
      <?php if ($saveError): ?>
        <div class="alert alert-danger mb-2"><?php echo htmlspecialchars($saveError, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <?php if ($saveMsg): ?>
        <div class="alert alert-success mb-2"><?php echo htmlspecialchars($saveMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <p class="docs-subtext">शुक्लागण्डकी नगरपालिका परिचय सामग्री यहाँ देखाइनेछ।</p>
      <?php if ($isCreator): ?>
        <form class="docs-editor" method="POST" enctype="multipart/form-data">
          <div class="docs-editor-title">Add Introduction Entry</div>
          <label>Title</label>
          <input type="text" name="intro_title" placeholder="Title" required>
          <label>Description</label>
          <textarea name="intro_description" rows="3" placeholder="Description" required></textarea>
          <label>Photos</label>
          <input type="file" name="intro_photos[]" accept="image/*" multiple>
          <label>Video URL (optional)</label>
          <input type="url" name="intro_video_url" placeholder="https://www.youtube.com/watch?v=...">
          <label>Or upload video (optional)</label>
          <input type="file" name="intro_video" accept="video/mp4,video/webm,video/ogg">
          <button class="btn btn-sm btn-primary" type="submit" name="add_intro">Add Entry</button>
        </form>
      <?php endif; ?>
    </section>

    <section class="places-section">
      <?php if (empty($entries)): ?>
        <div class="alert alert-info">No introduction entries yet.</div>
      <?php endif; ?>
      <?php foreach ($entries as $index => $entry): ?>
        <article class="place-card">
          <?php if (!empty($entry['images'][0])): ?>
            <div class="place-cover">
              <img src="<?php echo htmlspecialchars($entry['images'][0], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($entry['title'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          <?php endif; ?>
          <div class="place-content">
            <h3 class="place-title"><?php echo htmlspecialchars($entry['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p class="place-desc"><?php echo htmlspecialchars($entry['description'], ENT_QUOTES, 'UTF-8'); ?></p>

            <?php
              $videoFile = isset($entry['video_file']) ? (string)$entry['video_file'] : '';
              $videoUrl = isset($entry['video_url']) ? (string)$entry['video_url'] : '';
              $embedUrl = $videoUrl !== '' ? $getYoutubeEmbed($videoUrl) : '';
            ?>

            <?php if ($videoFile !== ''): ?>
              <div class="place-video">
                <video controls src="<?php echo htmlspecialchars($videoFile, ENT_QUOTES, 'UTF-8'); ?>"></video>
              </div>
            <?php elseif ($embedUrl !== ''): ?>
              <div class="place-video">
                <iframe src="<?php echo htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8'); ?>" allowfullscreen></iframe>
              </div>
            <?php elseif ($videoUrl !== ''): ?>
              <a class="place-video-link" href="<?php echo htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Watch video</a>
            <?php endif; ?>

            <?php if (!empty($entry['images']) && count($entry['images']) > 1): ?>
              <div class="place-thumbs">
                <?php foreach (array_slice($entry['images'], 1) as $image): ?>
                  <div class="place-thumb">
                    <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          <?php if ($isCreator): ?>
            <form class="place-actions" method="POST">
              <input type="hidden" name="intro_index" value="<?php echo $index; ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit" name="delete_intro" onclick="return confirm('Delete this entry?');">Delete</button>
            </form>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </section>
  </div>
</div>

<?php include './footer.php'; ?>
