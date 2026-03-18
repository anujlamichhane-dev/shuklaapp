<?php
require_once './src/i18n.php';
$pageTitle = i18n_t('admin.open_tickets', 'Open Tickets');
include './header.php';

require_once './src/ticket.php';
require_once './src/requester.php';
require_once './src/team.php';
require_once './src/user.php';

$pageHeading = i18n_t('admin.open_tickets', 'Open Tickets');
$tickets = Ticket::findByStatus('open');

include './ticket-list-view.php';
