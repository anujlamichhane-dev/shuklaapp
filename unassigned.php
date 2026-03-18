<?php
require_once './src/i18n.php';
$pageTitle = i18n_t('admin.unassigned_tickets', 'Unassigned Tickets');
include './header.php';

require_once './src/ticket.php';
require_once './src/requester.php';
require_once './src/team.php';
require_once './src/user.php';

$pageHeading = i18n_t('admin.unassigned_tickets', 'Unassigned Tickets');
$ticketModel = new Ticket();
$tickets = $ticketModel->unassigned();

include './ticket-list-view.php';
