<?php
  $bodyClass = 'mobile-home-body';
  $extraCss = ['css/mobile-home.css'];
  include './header.php';

  $defaultData = [
    'office_hours_title' => 'General Office Hogurs',
    'office_hours_detail' => 'Sunday - Friday: 10:00 AM to 5:00 PM',
    'office_hours_note' => '(Closed on Saturdays and public holidays)',
    'processes' => [
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
      'window' => ''
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
      'window' => ''
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
      'window' => ''
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
      'window' => ''
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
      'window' => ''
    ]
    ]
  ];

  $dataPath = __DIR__ . '/data/documents-info.json';
  $dataDir = __DIR__ . '/data';
  $saveError = '';
  $saveMsg = '';

  $data = $defaultData;
  if (is_readable($dataPath)) {
    $decoded = json_decode(file_get_contents($dataPath), true);
    if (is_array($decoded)) {
      $data = array_merge($defaultData, $decoded);
      if (!isset($data['processes']) || !is_array($data['processes'])) {
        $data['processes'] = $defaultData['processes'];
      }
    }
  }

  $parseDocuments = function ($raw) {
    if (!is_string($raw)) return [];
    $lines = preg_split('/\r\n|\r|\n/', trim($raw));
    $docs = [];
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line !== '') $docs[] = $line;
    }
    return $docs;
  };

  $saveData = function ($payload) use ($dataDir, $dataPath, &$saveError, &$saveMsg) {
    if (!is_dir($dataDir)) {
      if (!mkdir($dataDir, 0755, true) && !is_dir($dataDir)) {
        $saveError = 'Could not create the data folder.';
        return false;
      }
    }
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($dataPath, $json) === false) {
      $saveError = 'Could not save updates.';
      return false;
    }
    $saveMsg = 'Documents info updated.';
    return true;
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
        $data['processes'][] = [
          'title' => $title,
          'summary' => $summary,
          'documents' => $documents,
          'timing' => $timing,
          'window' => $window
        ];
        $saveData($data);
      }
    }

    if (isset($_POST['update_process']) && isset($_POST['process_index'])) {
      $index = (int)$_POST['process_index'];
      if (isset($data['processes'][$index])) {
        $data['processes'][$index]['title'] = trim((string)($_POST['edit_title'] ?? ''));
        $data['processes'][$index]['summary'] = trim((string)($_POST['edit_summary'] ?? ''));
        $data['processes'][$index]['timing'] = trim((string)($_POST['edit_timing'] ?? ''));
        $data['processes'][$index]['window'] = trim((string)($_POST['edit_window'] ?? ''));
        $data['processes'][$index]['documents'] = $parseDocuments($_POST['edit_documents'] ?? '');
        $saveData($data);
      }
    }

    if (isset($_POST['delete_process']) && isset($_POST['process_index'])) {
      $index = (int)$_POST['process_index'];
      if (isset($data['processes'][$index])) {
        array_splice($data['processes'], $index, 1);
        $saveData($data);
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
        <form class="docs-editor" method="POST">
          <div class="docs-editor-title"><?php echo htmlspecialchars(i18n_t('docs.add_process'), ENT_QUOTES, 'UTF-8'); ?></div>
          <label><?php echo htmlspecialchars(i18n_t('docs.process_title'), ENT_QUOTES, 'UTF-8'); ?></label>
          <input type="text" name="process_title" placeholder="Process title" required>
          <label><?php echo htmlspecialchars(i18n_t('docs.process_summary'), ENT_QUOTES, 'UTF-8'); ?></label>
          <textarea name="process_summary" rows="2" placeholder="What this process does" required></textarea>
          <label><?php echo htmlspecialchars(i18n_t('docs.process_documents'), ENT_QUOTES, 'UTF-8'); ?></label>
          <textarea name="process_documents" rows="4" placeholder="Document 1&#10;Document 2&#10;Document 3" required></textarea>
          <label><?php echo htmlspecialchars(i18n_t('docs.process_window'), ENT_QUOTES, 'UTF-8'); ?></label>
          <input type="text" name="process_window" placeholder="Window number (optional)">
          <label><?php echo htmlspecialchars(i18n_t('docs.process_timing'), ENT_QUOTES, 'UTF-8'); ?></label>
          <input type="text" name="process_timing" placeholder="Sun-Fri: 10:00 AM to 5:00 PM" required>
          <button class="btn btn-sm btn-primary" type="submit" name="add_process"><?php echo htmlspecialchars(i18n_t('docs.add'), ENT_QUOTES, 'UTF-8'); ?></button>
        </form>
      <?php endif; ?>
      <div class="docs-grid">
        <?php foreach ($data['processes'] as $index => $process): ?>
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
            <div class="docs-block docs-timing">
              <div class="docs-label"><?php echo htmlspecialchars(i18n_t('docs.office_timing'), ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="docs-time"><?php echo htmlspecialchars($process['timing'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="docs-block docs-window">
              <div class="docs-label"><?php echo htmlspecialchars(i18n_t('docs.window_number'), ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="docs-time"><?php echo htmlspecialchars($process['window'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <?php if ($isCreator): ?>
              <form class="docs-editor docs-editor-inline" method="POST">
                <input type="hidden" name="process_index" value="<?php echo $index; ?>">
                <label><?php echo htmlspecialchars(i18n_t('docs.process_title'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" name="edit_title" value="<?php echo htmlspecialchars($process['title'], ENT_QUOTES, 'UTF-8'); ?>" required>
                <label><?php echo htmlspecialchars(i18n_t('docs.process_summary'), ENT_QUOTES, 'UTF-8'); ?></label>
                <textarea name="edit_summary" rows="2" required><?php echo htmlspecialchars($process['summary'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                <label><?php echo htmlspecialchars(i18n_t('docs.process_documents'), ENT_QUOTES, 'UTF-8'); ?></label>
                <textarea name="edit_documents" rows="4" required><?php echo htmlspecialchars(implode("\n", $process['documents']), ENT_QUOTES, 'UTF-8'); ?></textarea>
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
