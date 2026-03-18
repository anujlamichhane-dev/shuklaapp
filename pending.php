<?php
require_once './src/i18n.php';
$pageTitle = i18n_t('admin.pending_tickets', 'Pending Tickets');
include './header.php';

require_once './src/ticket.php';
require_once './src/requester.php';
require_once './src/team.php';
require_once './src/user.php';

$pageHeading = i18n_t('admin.pending_tickets', 'Pending Tickets');
$tickets = Ticket::findByStatus('pending');

include './ticket-list-view.php';
