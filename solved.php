<?php
require_once './src/i18n.php';
$pageTitle = i18n_t('admin.solved_tickets', 'Solved Tickets');
include './header.php';

require_once './src/ticket.php';
require_once './src/requester.php';
require_once './src/team.php';
require_once './src/user.php';

$pageHeading = i18n_t('admin.solved_tickets', 'Solved Tickets');
$tickets = Ticket::findByStatus('solved');

include './ticket-list-view.php';
