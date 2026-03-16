<?php
  $imageParam = isset($_GET['image']) ? (string)$_GET['image'] : '';
  $titleParam = isset($_GET['title']) ? trim((string)$_GET['title']) : 'Photo Viewer';

  $normalizedImage = ltrim(str_replace('\\', '/', $imageParam), '/');
  $allowedPrefix = 'img/interesting-places/';
  $imagePath = '';

  if ($normalizedImage !== '' && strpos($normalizedImage, $allowedPrefix) === 0) {
    $candidate = realpath(__DIR__ . '/' . $normalizedImage);
    $allowedRoot = realpath(__DIR__ . '/' . $allowedPrefix);
    if ($candidate !== false && $allowedRoot !== false && strpos($candidate, $allowedRoot) === 0 && is_file($candidate)) {
      $imagePath = $normalizedImage;
    }
  }

  if ($imagePath === '') {
    http_response_code(404);
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($titleParam, ENT_QUOTES, 'UTF-8'); ?></title>
  <style>
    :root {
      color-scheme: dark;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: #0c1220;
      color: #fff;
      display: grid;
      grid-template-rows: auto 1fr;
    }

    .viewer-topbar {
      position: sticky;
      top: 0;
      z-index: 2;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      background: rgba(12, 18, 32, 0.92);
      backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .viewer-back {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 42px;
      height: 42px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
      text-decoration: none;
      font-size: 22px;
      line-height: 1;
    }

    .viewer-title {
      font-size: 15px;
      font-weight: 600;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .viewer-stage {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 18px;
      min-height: 0;
    }

    .viewer-stage img {
      width: auto;
      max-width: 100%;
      max-height: calc(100vh - 96px);
      object-fit: contain;
      border-radius: 14px;
      box-shadow: 0 24px 48px rgba(0, 0, 0, 0.35);
      background: rgba(255, 255, 255, 0.04);
    }

    .viewer-empty {
      padding: 24px;
      text-align: center;
      color: rgba(255, 255, 255, 0.82);
    }

    @media (max-width: 600px) {
      .viewer-topbar {
        padding: 12px;
      }

      .viewer-stage {
        padding: 12px;
      }

      .viewer-stage img {
        max-height: calc(100vh - 82px);
        border-radius: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="viewer-topbar">
    <a class="viewer-back" href="javascript:history.back()" aria-label="Go back">&larr;</a>
    <div class="viewer-title"><?php echo htmlspecialchars($titleParam, ENT_QUOTES, 'UTF-8'); ?></div>
  </div>

  <?php if ($imagePath !== ''): ?>
    <div class="viewer-stage">
      <img src="<?php echo htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($titleParam, ENT_QUOTES, 'UTF-8'); ?>">
    </div>
  <?php else: ?>
    <div class="viewer-empty">Photo not found.</div>
  <?php endif; ?>
</body>
</html>
