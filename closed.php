<?php
require_once './src/i18n.php';
$pageTitle = i18n_t('admin.closed_tickets', 'Closed Tickets');
include './header.php';

require_once './src/ticket.php';
require_once './src/requester.php';
require_once './src/team.php';
require_once './src/user.php';

$pageHeading = i18n_t('admin.closed_tickets', 'Closed Tickets');
$tickets = Ticket::findByStatus('closed');

include './ticket-list-view.php';
