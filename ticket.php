<?php
include './header.php';
require_once './src/requester.php';
require_once './src/ticket.php';
require_once './src/ticket-event.php';
require_once './src/helper-functions.php';
require_once './src/service-request.php';

$isClient = (($user->role ?? '') === 'client');
$isGuest = (($user->role ?? '') === 'guest');
$teams = Team::findAll();
$categories = service_request_categories();
$urgencyOptions = service_request_urgency_options();
$contactWindows = service_request_contact_windows();

$err = '';
$msg = '';
$prefill = [
    'name' => trim((string)($user->name ?? '')),
    'email' => trim((string)($user->email ?? '')),
    'phone' => trim((string)($user->phone ?? '')),
    'subject' => '',
    'details' => '',
    'category' => 'other',
    'location' => '',
    'urgency' => 'normal',
    'contact_window' => 'anytime',
    'reference_hint' => '',
];

if ($isGuest && $prefill['name'] === '') {
    $prefill['name'] = 'Resident';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    csrf_require_valid_request();

    $prefill['name'] = trim((string)($_POST['name'] ?? ''));
    $prefill['email'] = trim((string)($_POST['email'] ?? ''));
    $prefill['phone'] = trim((string)($_POST['phone'] ?? ''));
    $prefill['subject'] = trim((string)($_POST['subject'] ?? ''));
    $prefill['details'] = trim((string)($_POST['details'] ?? ''));
    $prefill['category'] = trim((string)($_POST['category'] ?? 'other'));
    $prefill['location'] = trim((string)($_POST['location'] ?? ''));
    $prefill['urgency'] = trim((string)($_POST['urgency'] ?? 'normal'));
    $prefill['contact_window'] = trim((string)($_POST['contact_window'] ?? 'anytime'));
    $prefill['reference_hint'] = trim((string)($_POST['reference_hint'] ?? ''));

    if ($prefill['name'] === '') {
        $err = 'Please enter your name.';
    } elseif (!isValidEmail($prefill['email'])) {
        $err = 'Please enter a valid email address.';
    } elseif (!isValidPhone($prefill['phone'])) {
        $err = 'Please enter a valid 10-digit phone number.';
    } elseif ($prefill['subject'] === '') {
        $err = 'Please add a short summary for the case.';
    } elseif ($prefill['details'] === '') {
        $err = 'Please describe the issue in a few clear sentences.';
    } elseif (!isset($categories[$prefill['category']])) {
        $err = 'Please choose a valid service area.';
    } elseif (!isset($urgencyOptions[$prefill['urgency']])) {
        $err = 'Please choose a valid urgency level.';
    } elseif (!isset($contactWindows[$prefill['contact_window']])) {
        $err = 'Please choose a valid contact time.';
    } else {
        try {
            $requester = Requester::findOrCreate([
                'name' => $prefill['name'],
                'email' => $prefill['email'],
                'phone' => $prefill['phone'],
                'user_id' => $isClient ? (int)($user->id ?? 0) : null,
            ]);

            $body = service_request_build_body([
                'category' => $prefill['category'],
                'location' => $prefill['location'],
                'urgency' => $prefill['urgency'],
                'contact_window' => $prefill['contact_window'],
                'reference_hint' => $prefill['reference_hint'],
            ], $prefill['details']);

            $ticket = new Ticket([
                'title' => $prefill['subject'],
                'body' => $body,
                'requester' => $requester->id,
                'team' => service_request_route_team_id($prefill['category'], $teams),
                'team_member' => null,
                'status' => 'open',
                'priority' => service_request_priority_from_urgency($prefill['urgency']),
            ]);
            $ticket->save();

            if ($isGuest) {
                rememberGuestContact($prefill['name'], $prefill['email'], $prefill['phone']);
                rememberGuestTicket($ticket->id);
            }

            try {
                $event = new Event([
                    'ticket' => $ticket->id,
                    'user' => (int)($user->id ?? 0),
                    'body' => 'Case submitted through the citizen service portal.',
                ]);
                $event->save();
            } catch (Throwable $eventError) {
                error_log('Service request event logging failed for case ' . (int)$ticket->id . ': ' . $eventError->getMessage());
            }

            header('Location: ' . appUrl('ticket-details.php?id=' . (int)$ticket->id . '&created=1'));
            exit();
        } catch (Throwable $e) {
            error_log('Service request creation failed for user ' . ($user->id ?? 'unknown') . ': ' . $e->getMessage());
            $err = 'The case could not be submitted right now. Please try again.';
        }
    }
}
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <div class="request-shell">
      <section class="request-hero">
        <div>
          <div class="request-kicker">Citizen Service Request</div>
          <h1>Report an issue or ask for municipal support</h1>
          <p>Use this form for road damage, waste collection, water problems, ward service delays, document follow-up, or any other municipal service concern.</p>
        </div>
        <div class="request-hero-card">
          <div class="request-hero-stat">
            <strong>Structured intake</strong>
            <span>Your case is saved with location, urgency, and service area so it can be reviewed faster.</span>
          </div>
          <div class="request-hero-stat">
            <strong>Human follow-up</strong>
            <span>Municipal staff can assign, update, and resolve the case from the same thread.</span>
          </div>
        </div>
      </section>

      <?php if ($err !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <?php if ($msg !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" class="request-form-card">
        <?php echo csrf_input(); ?>

        <div class="request-section-head">
          <h2>Your contact details</h2>
          <p>We use this to confirm the case and send updates. Guests can still submit without creating an account.</p>
        </div>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="request-name">Full name</label>
            <input type="text" class="form-control" id="request-name" name="name" value="<?php echo htmlspecialchars($prefill['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
          </div>
          <div class="form-group col-md-4">
            <label for="request-email">Email address</label>
            <input type="email" class="form-control" id="request-email" name="email" value="<?php echo htmlspecialchars($prefill['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
          </div>
          <div class="form-group col-md-4">
            <label for="request-phone">Phone number</label>
            <input type="text" class="form-control" id="request-phone" name="phone" value="<?php echo htmlspecialchars($prefill['phone'], ENT_QUOTES, 'UTF-8'); ?>" required>
          </div>
        </div>

        <div class="request-section-head">
          <h2>Case details</h2>
          <p>Tell the municipality what happened, where it happened, and how urgent it is.</p>
        </div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label for="request-category">Service area</label>
            <select class="form-control" id="request-category" name="category" required>
              <?php foreach ($categories as $key => $category): ?>
                <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $prefill['category'] === $key ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($category['label'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-md-6">
            <label for="request-location">Location</label>
            <input type="text" class="form-control" id="request-location" name="location" value="<?php echo htmlspecialchars($prefill['location'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ward, tole, landmark, or office counter">
          </div>
        </div>
        <div class="form-group">
          <label for="request-subject">Short summary</label>
          <input type="text" class="form-control" id="request-subject" name="subject" value="<?php echo htmlspecialchars($prefill['subject'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Example: Street light not working near ward office" required>
        </div>
        <div class="form-group">
          <label for="request-details">Full description</label>
          <textarea class="form-control" id="request-details" name="details" rows="6" placeholder="Explain what residents are facing, how long it has been happening, and what support is needed." required><?php echo htmlspecialchars($prefill['details'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="request-urgency">Urgency</label>
            <select class="form-control" id="request-urgency" name="urgency" required>
              <?php foreach ($urgencyOptions as $key => $label): ?>
                <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $prefill['urgency'] === $key ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-md-4">
            <label for="request-contact-window">Best time to contact you</label>
            <select class="form-control" id="request-contact-window" name="contact_window" required>
              <?php foreach ($contactWindows as $key => $label): ?>
                <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $prefill['contact_window'] === $key ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-md-4">
            <label for="request-reference">Reference note</label>
            <input type="text" class="form-control" id="request-reference" name="reference_hint" value="<?php echo htmlspecialchars($prefill['reference_hint'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Optional file no., ward note, or landmark">
          </div>
        </div>

        <div class="request-actions">
          <a href="<?php echo htmlspecialchars(appUrl('mytickets.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">View my cases</a>
          <button type="submit" name="submit" class="btn btn-primary">Submit case</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  .request-shell {
    max-width: 980px;
    margin: 0 auto;
    display: grid;
    gap: 1rem;
  }
  .request-hero,
  .request-form-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid rgba(18, 46, 77, 0.08);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
  }
  .request-hero {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
    gap: 1.25rem;
    padding: 1.5rem;
  }
  .request-kicker {
    font-size: .8rem;
    text-transform: uppercase;
    letter-spacing: .12em;
    font-weight: 700;
    color: #2f6fed;
    margin-bottom: .5rem;
  }
  .request-hero h1 {
    margin-bottom: .5rem;
    color: #12324d;
  }
  .request-hero p {
    margin-bottom: 0;
    color: #516277;
    line-height: 1.6;
  }
  .request-hero-card {
    border-radius: 18px;
    background: linear-gradient(180deg, #f4f8fb 0%, #eef4f8 100%);
    padding: 1rem;
    display: grid;
    gap: .85rem;
    align-content: start;
  }
  .request-hero-stat strong {
    display: block;
    color: #12324d;
    margin-bottom: .2rem;
  }
  .request-hero-stat span {
    display: block;
    color: #5f7085;
    font-size: .95rem;
    line-height: 1.5;
  }
  .request-form-card {
    padding: 1.5rem;
  }
  .request-section-head {
    margin-bottom: .85rem;
  }
  .request-section-head h2 {
    font-size: 1.1rem;
    margin-bottom: .2rem;
    color: #12324d;
  }
  .request-section-head p {
    margin-bottom: 0;
    color: #66788d;
  }
  .request-form-card .form-control {
    border-radius: 12px;
    min-height: 48px;
  }
  .request-form-card textarea.form-control {
    min-height: 160px;
  }
  .request-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .75rem;
    margin-top: 1rem;
  }
  @media (max-width: 768px) {
    .request-hero {
      grid-template-columns: 1fr;
    }
    .request-actions {
      flex-direction: column;
      align-items: stretch;
    }
    .request-actions .btn {
      width: 100%;
    }
  }
</style>

<?php include './footer.php'; ?>
