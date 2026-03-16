<?php
  $bodyClass = 'mobile-home-body';
  $extraCss = ['css/mobile-home.css'];
  include './header.php';

  $defaultProcesses = [
    [
      'title' => 'Birth Certificate Registration',
      'summary' => 'Register a birth and obtain an official certificate.',
      'documents' => [
        'Hospital/health post birth record',
        'Parents citizenship copy',
        'Marriage registration copy (if available)',
        'Ward recommendation letter'
      ],
      'timing' => 'Sun-Fri: 10:00 AM to 5:00 PM',
      'window' => '',
      'attachments' => []
    ],
    [
      'title' => 'Marriage Registration',
      'summary' => 'Register a marriage and receive the official record.',
      'documents' => [
        'Both parties citizenship copies',
        'Passport-size photos (2 each)',
        'Ward recommendation letter',
        'Witness citizenship copy (1)'
      ],
      'timing' => 'Sun-Fri: 10:00 AM to 4:30 PM',
      'window' => '',
      'attachments' => []
    ],
    [
      'title' => 'Property/House Tax Clearance',
      'summary' => 'Apply for tax clearance for property or house.',
      'documents' => [
        'Property ownership proof',
        'Last tax payment receipt',
        'Citizenship copy',
        'Ward recommendation letter'
      ],
      'timing' => 'Sun-Fri: 10:00 AM to 5:00 PM',
      'window' => '',
      'attachments' => []
    ],
    [
      'title' => 'Business Registration / Renewal',
      'summary' => 'Register a new business or renew existing registration.',
      'documents' => [
        'Business application form',
        'Citizenship copy',
        'Lease agreement or ownership proof',
        'Ward recommendation letter'
      ],
      'timing' => 'Sun-Fri: 10:00 AM to 4:00 PM',
      'window' => '',
      'attachments' => []
    ],
    [
      'title' => 'Recommendations / Letters',
      'summary' => 'General recommendation letters (residence, character, etc.).',
      'documents' => [
        'Citizenship copy',
        'Proof of residence',
        'Application request letter'
      ],
      'timing' => 'Sun-Fri: 10:00 AM to 4:30 PM',
      'window' => '',
      'attachments' => []
    ]
  ];

  $defaultData = [
    'office_hours_title' => 'General Office Hours',
    'office_hours_detail' => 'Sunday - Friday: 10:00 AM to 5:00 PM',
    'office_hours_note' => '(Closed on Saturdays and public holidays)',
    'processes' => $defaultProcesses
  ];

  $dataPath = __DIR__ . '/data/documents-info.json';
  $dataDir = __DIR__ . '/data';
  $uploadsDir = __DIR__ . '/data/documents-info-uploads';
  $uploadsPathPrefix = 'data/documents-info-uploads';
  $saveError = '';
  $saveMsg = '';
  $maxAttachmentsPerProcess = 8;
  $maxAttachmentBytes = 10 * 1024 * 1024;

  $slugify = function ($value) {
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', (string)$value), '-'));
    return $slug !== '' ? $slug : 'document-process';
  };

  $parseDocuments = function ($raw) {
    if (!is_string($raw)) {
      return [];
    }
    $lines = preg_split('/\r\n|\r|\n/', trim($raw));
    $docs = [];
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line !== '') {
        $docs[] = $line;
      }
    }
    return $docs;
  };

  $normalizeRelativePath = function ($path) {
    $path = str_replace('\\', '/', (string)$path);
    return ltrim($path, '/');
  };

  $inferAttachmentKind = function ($filename, $mime = '') {
    $mime = strtolower((string)$mime);
    $ext = strtolower(pathinfo((string)$filename, PATHINFO_EXTENSION));
    if (strpos($mime, 'image/') === 0 || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
      return 'image';
    }
    if ($mime === 'application/pdf' || $ext === 'pdf') {
      return 'pdf';
    }
    return 'file';
  };

  $buildAttachment = function ($path, $name = '', $mime = '') use ($normalizeRelativePath, $inferAttachmentKind) {
    $path = $normalizeRelativePath($path);
    $name = trim((string)$name);
    if ($name === '') {
      $name = basename($path);
    }
    $mime = trim((string)$mime);
    return [
      'path' => $path,
      'name' => $name,
      'mime' => $mime,
      'kind' => $inferAttachmentKind($name, $mime)
    ];
  };

  $normalizeAttachments = function ($attachments) use ($buildAttachment) {
    if (!is_array($attachments)) {
      return [];
    }
    $normalized = [];
    foreach ($attachments as $attachment) {
      if (is_string($attachment) && trim($attachment) !== '') {
        $normalized[] = $buildAttachment($attachment);
        continue;
      }
      if (!is_array($attachment)) {
        continue;
      }
      $path = $attachment['path'] ?? ($attachment['file'] ?? '');
      if (!is_string($path) || trim($path) === '') {
        continue;
      }
      $normalized[] = $buildAttachment(
        $path,
        (string)($attachment['name'] ?? ($attachment['filename'] ?? '')),
        (string)($attachment['mime'] ?? ($attachment['type'] ?? ''))
      );
    }
    return array_values($normalized);
  };

  $cleanupFiles = function ($attachments) use ($uploadsDir, $normalizeRelativePath) {
    if (!is_array($attachments) || !is_dir($uploadsDir)) {
      return;
    }
    $root = realpath($uploadsDir);
    if ($root === false) {
      return;
    }
    foreach ($attachments as $attachment) {
      $path = '';
      if (is_array($attachment)) {
        $path = (string)($attachment['path'] ?? '');
      } elseif (is_string($attachment)) {
        $path = $attachment;
      }
      if ($path === '') {
        continue;
      }
      $candidate = realpath(__DIR__ . '/' . $normalizeRelativePath($path));
      if ($candidate && strpos($candidate, $root) === 0 && is_file($candidate)) {
        @unlink($candidate);
      }
    }
  };

  $attachmentUrl = function ($attachment, $inline = false) {
    $query = ['path' => (string)($attachment['path'] ?? '')];
    if ($inline) {
      $query['inline'] = '1';
    }
    return 'download-docs-asset.php?' . http_build_query($query);
  };

  $saveData = function ($payload) use ($dataDir, $dataPath, &$saveError, &$saveMsg) {
    if (!is_dir($dataDir)) {
      if (!mkdir($dataDir, 0755, true) && !is_dir($dataDir)) {
        $saveError = 'Could not create the data folder.';
        return false;
      }
    }
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false || file_put_contents($dataPath, $json) === false) {
      $saveError = 'Could not save updates.';
      return false;
    }
    $saveMsg = 'Documents info updated.';
    return true;
  };

  $data = $defaultData;
  if (is_readable($dataPath)) {
    $decoded = json_decode(file_get_contents($dataPath), true);
    if (is_array($decoded)) {
      $data = array_merge($defaultData, $decoded);
      if (!isset($data['processes']) || !is_array($data['processes'])) {
        $data['processes'] = $defaultProcesses;
      }
    }
  }

  $data['processes'] = array_values(array_map(function ($process) use ($normalizeAttachments) {
    $process = is_array($process) ? $process : [];
    return [
      'title' => trim((string)($process['title'] ?? '')),
      'summary' => trim((string)($process['summary'] ?? '')),
      'documents' => isset($process['documents']) && is_array($process['documents']) ? array_values(array_filter(array_map('trim', $process['documents']), 'strlen')) : [],
      'timing' => trim((string)($process['timing'] ?? '')),
      'window' => trim((string)($process['window'] ?? '')),
      'attachments' => $normalizeAttachments($process['attachments'] ?? [])
    ];
  }, $data['processes']));

  $handleAttachmentUploads = function ($fieldName, $title, $existingCount = 0) use (
    $uploadsDir,
    $uploadsPathPrefix,
    $slugify,
    $cleanupFiles,
    $buildAttachment,
    &$saveError,
    $maxAttachmentsPerProcess,
    $maxAttachmentBytes
  ) {
    if (!isset($_FILES[$fieldName])) {
      return [];
    }

    $files = $_FILES[$fieldName];
    $names = $files['name'] ?? [];
    $tmpNames = $files['tmp_name'] ?? [];
    $errors = $files['error'] ?? [];
    $sizes = $files['size'] ?? [];

    if (!is_array($names)) {
      $names = [$names];
      $tmpNames = [$tmpNames];
      $errors = [$errors];
      $sizes = [$sizes];
    }

    $allowedImageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedExt = array_merge($allowedImageExt, ['pdf']);
    $saved = [];
    $slug = $slugify($title);
    $sequence = max(1, (int)$existingCount + 1);

    if (!is_dir($uploadsDir)) {
      if (!mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
        $saveError = 'Could not create the uploads folder.';
        return [];
      }
    }

    for ($i = 0; $i < count($names); $i++) {
      $originalName = trim((string)($names[$i] ?? ''));
      $tmpName = (string)($tmpNames[$i] ?? '');
      $error = (int)($errors[$i] ?? UPLOAD_ERR_NO_FILE);
      $size = (int)($sizes[$i] ?? 0);

      if ($error === UPLOAD_ERR_NO_FILE || $originalName === '') {
        continue;
      }

      if (($existingCount + count($saved)) >= $maxAttachmentsPerProcess) {
        $saveError = 'You can upload up to ' . $maxAttachmentsPerProcess . ' files per process.';
        break;
      }
      if ($error !== UPLOAD_ERR_OK) {
        $saveError = 'One or more files could not be uploaded.';
        break;
      }
      if ($size > $maxAttachmentBytes) {
        $saveError = 'Each uploaded file must be under 10MB.';
        break;
      }
      if (!is_uploaded_file($tmpName)) {
        $saveError = 'Invalid file upload detected.';
        break;
      }

      $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
      if (!in_array($ext, $allowedExt, true)) {
        $saveError = 'Only PDF, JPG, PNG, GIF, or WebP files are allowed.';
        break;
      }

      $mime = '';
      if (in_array($ext, $allowedImageExt, true)) {
        $imageInfo = @getimagesize($tmpName);
        if ($imageInfo === false) {
          $saveError = 'Please upload valid image files.';
          break;
        }
        $mime = (string)($imageInfo['mime'] ?? ('image/' . $ext));
      } else {
        $header = (string)file_get_contents($tmpName, false, null, 0, 4);
        $detectedMime = '';
        if (function_exists('finfo_open')) {
          $finfo = finfo_open(FILEINFO_MIME_TYPE);
          if ($finfo) {
            $detectedMime = (string)finfo_file($finfo, $tmpName);
            finfo_close($finfo);
          }
        }
        if ($header !== '%PDF' || ($detectedMime !== '' && strpos($detectedMime, 'application/pdf') !== 0)) {
          $saveError = 'Please upload valid PDF files.';
          break;
        }
        $mime = 'application/pdf';
      }

      $filename = $slug . '-attachment-' . $sequence . '.' . $ext;
      $sequence++;
      while (file_exists($uploadsDir . DIRECTORY_SEPARATOR . $filename)) {
        $filename = $slug . '-attachment-' . $sequence . '.' . $ext;
        $sequence++;
      }

      $destination = $uploadsDir . DIRECTORY_SEPARATOR . $filename;
      if (!move_uploaded_file($tmpName, $destination)) {
        $saveError = 'Could not save one or more uploaded files.';
        break;
      }

      $saved[] = $buildAttachment($uploadsPathPrefix . '/' . $filename, basename($originalName), $mime);
    }

    if ($saveError !== '') {
      $cleanupFiles($saved);
      return [];
    }

    return $saved;
  };

  if ($isCreator && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_office_hours'])) {
      $data['office_hours_title'] = trim((string)($_POST['office_hours_title'] ?? ''));
      $data['office_hours_detail'] = trim((string)($_POST['office_hours_detail'] ?? ''));
      $data['office_hours_note'] = trim((string)($_POST['office_hours_note'] ?? ''));
      $saveData($data);
    }

    if (isset($_POST['add_process'])) {
      $title = trim((string)($_POST['process_title'] ?? ''));
      $summary = trim((string)($_POST['process_summary'] ?? ''));
      $timing = trim((string)($_POST['process_timing'] ?? ''));
      $window = trim((string)($_POST['process_window'] ?? ''));
      $documents = $parseDocuments($_POST['process_documents'] ?? '');

      if ($title === '' || $summary === '' || $timing === '' || empty($documents)) {
        $saveError = 'Please fill in title, summary, timing, and documents.';
      } else {
        $attachments = $handleAttachmentUploads('process_attachments', $title, 0);
        if (!$saveError) {
          $data['processes'][] = [
            'title' => $title,
            'summary' => $summary,
            'documents' => $documents,
            'timing' => $timing,
            'window' => $window,
            'attachments' => $attachments
          ];
          if (!$saveData($data)) {
            $cleanupFiles($attachments);
          }
        }
      }
    }

    if (isset($_POST['update_process'], $_POST['process_index'])) {
      $index = (int)$_POST['process_index'];
      if (isset($data['processes'][$index])) {
        $title = trim((string)($_POST['edit_title'] ?? ''));
        $summary = trim((string)($_POST['edit_summary'] ?? ''));
        $timing = trim((string)($_POST['edit_timing'] ?? ''));
        $window = trim((string)($_POST['edit_window'] ?? ''));
        $documents = $parseDocuments($_POST['edit_documents'] ?? '');

        if ($title === '' || $summary === '' || $timing === '' || empty($documents)) {
          $saveError = 'Please fill in title, summary, timing, and documents.';
        } else {
          $currentProcess = $data['processes'][$index];
          $existingAttachments = $normalizeAttachments($currentProcess['attachments'] ?? []);
          $removePaths = isset($_POST['remove_attachments']) && is_array($_POST['remove_attachments']) ? $_POST['remove_attachments'] : [];
          $removePaths = array_map($normalizeRelativePath, $removePaths);
          $keptAttachments = [];
          $removedAttachments = [];

          foreach ($existingAttachments as $attachment) {
            if (in_array($attachment['path'], $removePaths, true)) {
              $removedAttachments[] = $attachment;
            } else {
              $keptAttachments[] = $attachment;
            }
          }

          $newAttachments = $handleAttachmentUploads('edit_attachments', $title, count($keptAttachments));
          if (!$saveError) {
            $data['processes'][$index] = [
              'title' => $title,
              'summary' => $summary,
              'documents' => $documents,
              'timing' => $timing,
              'window' => $window,
              'attachments' => array_values(array_merge($keptAttachments, $newAttachments))
            ];

            if ($saveData($data)) {
              $cleanupFiles($removedAttachments);
            } else {
              $cleanupFiles($newAttachments);
              $data['processes'][$index] = $currentProcess;
            }
          }
        }
      }
    }

    if (isset($_POST['delete_process'], $_POST['process_index'])) {
      $index = (int)$_POST['process_index'];
      if (isset($data['processes'][$index])) {
        $attachmentsToDelete = $normalizeAttachments($data['processes'][$index]['attachments'] ?? []);
        array_splice($data['processes'], $index, 1);
        if ($saveData($data)) {
          $cleanupFiles($attachmentsToDelete);
        }
      }
    }
  }
?>

<div id="content-wrapper">
  <div class="app-shell">
    <section class="docs-hero">
      <div class="section-title"><?php echo htmlspecialchars(i18n_t('docs.title'), ENT_QUOTES, 'UTF-8'); ?></div>
      <?php if ($saveError): ?>
        <div class="alert alert-danger mb-2"><?php echo htmlspecialchars($saveError, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <?php if ($saveMsg): ?>
        <div class="alert alert-success mb-2"><?php echo htmlspecialchars($saveMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <p class="docs-subtext">
        <?php echo htmlspecialchars(i18n_t('docs.subtitle'), ENT_QUOTES, 'UTF-8'); ?>
      </p>
      <div class="docs-timebox">
        <div class="docs-timebox-title"><?php echo htmlspecialchars($data['office_hours_title'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="docs-timebox-detail"><?php echo htmlspecialchars($data['office_hours_detail'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="docs-timebox-note"><?php echo htmlspecialchars($data['office_hours_note'], ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
      <?php if ($isCreator): ?>
        <form class="docs-editor" method="POST">
          <div class="docs-editor-title"><?php echo htmlspecialchars(i18n_t('docs.update_hours'), ENT_QUOTES, 'UTF-8'); ?></div>
          <label><?php echo htmlspecialchars(i18n_t('docs.process_title'), ENT_QUOTES, 'UTF-8'); ?></label>
          <input type="text" name="office_hours_title" value="<?php echo htmlspecialchars($data['office_hours_title'], ENT_QUOTES, 'UTF-8'); ?>" required>
          <label><?php echo htmlspecialchars(i18n_t('docs.process_timing'), ENT_QUOTES, 'UTF-8'); ?></label>
          <input type="text" name="office_hours_detail" value="<?php echo htmlspecialchars($data['office_hours_detail'], ENT_QUOTES, 'UTF-8'); ?>" required>
          <label>Note</label>
          <input type="text" name="office_hours_note" value="<?php echo htmlspecialchars($data['office_hours_note'], ENT_QUOTES, 'UTF-8'); ?>">
          <button class="btn btn-sm btn-primary" type="submit" name="update_office_hours"><?php echo htmlspecialchars(i18n_t('docs.save'), ENT_QUOTES, 'UTF-8'); ?></button>
        </form>
      <?php endif; ?>
    </section>

    <section class="docs-section">
      <?php if ($isCreator): ?>
        <form class="docs-editor" method="POST" enctype="multipart/form-data">
          <div class="docs-editor-title"><?php echo htmlspecialchars(i18n_t('docs.add_process'), ENT_QUOTES, 'UTF-8'); ?></div>
          <label><?php echo htmlspecialchars(i18n_t('docs.process_title'), ENT_QUOTES, 'UTF-8'); ?></label>
          <input type="text" name="process_title" placeholder="Process title" required>
          <label><?php echo htmlspecialchars(i18n_t('docs.process_summary'), ENT_QUOTES, 'UTF-8'); ?></label>
          <textarea name="process_summary" rows="2" placeholder="What this process does" required></textarea>
          <label><?php echo htmlspecialchars(i18n_t('docs.process_documents'), ENT_QUOTES, 'UTF-8'); ?></label>
          <textarea name="process_documents" rows="4" placeholder="Document 1&#10;Document 2&#10;Document 3" required></textarea>
          <label><?php echo htmlspecialchars(i18n_t('docs.process_attachments'), ENT_QUOTES, 'UTF-8'); ?></label>
          <div class="docs-upload-scroll">
            <input type="file" name="process_attachments[]" accept=".pdf,image/*" multiple>
          </div>
          <div class="docs-input-hint"><?php echo htmlspecialchars(i18n_t('docs.attachments_help'), ENT_QUOTES, 'UTF-8'); ?></div>
          <label><?php echo htmlspecialchars(i18n_t('docs.process_window'), ENT_QUOTES, 'UTF-8'); ?></label>
          <input type="text" name="process_window" placeholder="Window number (optional)">
          <label><?php echo htmlspecialchars(i18n_t('docs.process_timing'), ENT_QUOTES, 'UTF-8'); ?></label>
          <input type="text" name="process_timing" placeholder="Sun-Fri: 10:00 AM to 5:00 PM" required>
          <button class="btn btn-sm btn-primary" type="submit" name="add_process"><?php echo htmlspecialchars(i18n_t('docs.add'), ENT_QUOTES, 'UTF-8'); ?></button>
        </form>
      <?php endif; ?>
      <div class="docs-grid">
        <?php foreach ($data['processes'] as $index => $process): ?>
          <?php $attachments = isset($process['attachments']) && is_array($process['attachments']) ? $process['attachments'] : []; ?>
          <article class="docs-card">
            <div class="docs-card-head">
              <h3><?php echo htmlspecialchars($process['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <span class="docs-badge"><?php echo htmlspecialchars(i18n_t('docs.process'), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <p class="docs-summary"><?php echo htmlspecialchars($process['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="docs-block">
              <div class="docs-label"><?php echo htmlspecialchars(i18n_t('docs.required_documents'), ENT_QUOTES, 'UTF-8'); ?></div>
              <ul>
                <?php foreach ($process['documents'] as $doc): ?>
                  <li><?php echo htmlspecialchars($doc, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <?php if (!empty($attachments)): ?>
              <div class="docs-block docs-attachments">
                <div class="docs-label"><?php echo htmlspecialchars(i18n_t('docs.attachments'), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="docs-attachment-grid">
                  <?php foreach ($attachments as $attachment): ?>
                    <?php
                      $attachmentName = trim((string)($attachment['name'] ?? ''));
                      if ($attachmentName === '') {
                        $attachmentName = basename((string)($attachment['path'] ?? ''));
                      }
                      $kind = (string)($attachment['kind'] ?? 'file');
                      $inlineUrl = $attachmentUrl($attachment, true);
                      $downloadUrl = $attachmentUrl($attachment, false);
                    ?>
                    <div class="docs-attachment-card docs-attachment-<?php echo htmlspecialchars($kind, ENT_QUOTES, 'UTF-8'); ?>">
                      <a class="docs-attachment-preview" href="<?php echo htmlspecialchars($inlineUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                        <?php if ($kind === 'image'): ?>
                          <img src="<?php echo htmlspecialchars($inlineUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($attachmentName, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php else: ?>
                          <span class="docs-attachment-icon"><i class="fas fa-file-pdf"></i></span>
                        <?php endif; ?>
                      </a>
                      <div class="docs-attachment-body">
                        <div class="docs-attachment-name"><?php echo htmlspecialchars($attachmentName, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="docs-attachment-actions">
                          <a href="<?php echo htmlspecialchars($inlineUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(i18n_t('docs.view_attachment'), ENT_QUOTES, 'UTF-8'); ?></a>
                          <a href="<?php echo htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(i18n_t('docs.download_attachment'), ENT_QUOTES, 'UTF-8'); ?></a>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
            <div class="docs-block docs-timing">
              <div class="docs-label"><?php echo htmlspecialchars(i18n_t('docs.office_timing'), ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="docs-time"><?php echo htmlspecialchars($process['timing'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="docs-block docs-window">
              <div class="docs-label"><?php echo htmlspecialchars(i18n_t('docs.window_number'), ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="docs-time"><?php echo htmlspecialchars($process['window'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <?php if ($isCreator): ?>
              <form class="docs-editor docs-editor-inline" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="process_index" value="<?php echo $index; ?>">
                <label><?php echo htmlspecialchars(i18n_t('docs.process_title'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" name="edit_title" value="<?php echo htmlspecialchars($process['title'], ENT_QUOTES, 'UTF-8'); ?>" required>
                <label><?php echo htmlspecialchars(i18n_t('docs.process_summary'), ENT_QUOTES, 'UTF-8'); ?></label>
                <textarea name="edit_summary" rows="2" required><?php echo htmlspecialchars($process['summary'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                <label><?php echo htmlspecialchars(i18n_t('docs.process_documents'), ENT_QUOTES, 'UTF-8'); ?></label>
                <textarea name="edit_documents" rows="4" required><?php echo htmlspecialchars(implode("\n", $process['documents']), ENT_QUOTES, 'UTF-8'); ?></textarea>
                <?php if (!empty($attachments)): ?>
                  <div class="docs-existing-files">
                    <div class="docs-input-hint"><?php echo htmlspecialchars(i18n_t('docs.current_attachments'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php foreach ($attachments as $attachment): ?>
                      <?php
                        $attachmentName = trim((string)($attachment['name'] ?? ''));
                        if ($attachmentName === '') {
                          $attachmentName = basename((string)($attachment['path'] ?? ''));
                        }
                      ?>
                      <label class="docs-attachment-toggle">
                        <input type="checkbox" name="remove_attachments[]" value="<?php echo htmlspecialchars((string)$attachment['path'], ENT_QUOTES, 'UTF-8'); ?>">
                        <span><?php echo htmlspecialchars($attachmentName, ENT_QUOTES, 'UTF-8'); ?></span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
                <label><?php echo htmlspecialchars(i18n_t('docs.process_attachments'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="docs-upload-scroll">
                  <input type="file" name="edit_attachments[]" accept=".pdf,image/*" multiple>
                </div>
                <div class="docs-input-hint"><?php echo htmlspecialchars(i18n_t('docs.attachments_help'), ENT_QUOTES, 'UTF-8'); ?></div>
                <label><?php echo htmlspecialchars(i18n_t('docs.process_window'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" name="edit_window" value="<?php echo htmlspecialchars($process['window'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <label><?php echo htmlspecialchars(i18n_t('docs.process_timing'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" name="edit_timing" value="<?php echo htmlspecialchars($process['timing'], ENT_QUOTES, 'UTF-8'); ?>" required>
                <div class="docs-editor-actions">
                  <button class="btn btn-sm btn-primary" type="submit" name="update_process"><?php echo htmlspecialchars(i18n_t('docs.save'), ENT_QUOTES, 'UTF-8'); ?></button>
                  <button class="btn btn-sm btn-outline-danger" type="submit" name="delete_process" onclick="return confirm('Delete this process?');"><?php echo htmlspecialchars(i18n_t('docs.delete'), ENT_QUOTES, 'UTF-8'); ?></button>
                </div>
              </form>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>

<?php include './footer.php'; ?>
