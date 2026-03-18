<?php
require_once './src/i18n.php';
$pageTitle = i18n_t('admin.all_tickets', 'All Tickets');
include './header.php';

require_once './src/ticket.php';
require_once './src/requester.php';
require_once './src/team.php';
require_once './src/user.php';

$bannerMessage = '';
$bannerType = 'success';

if (isset($_GET['del']) && ctype_digit((string)$_GET['del'])) {
    try {
        Ticket::delete((int)$_GET['del']);
        header('Location: ./dashboard.php?deleted=1');
        exit();
    } catch (Exception $e) {
        $bannerMessage = i18n_t('admin.ticket_delete_failed', 'Failed to delete ticket');
        $bannerType = 'danger';
    }
}

if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $bannerMessage = i18n_t('admin.ticket_deleted', 'Ticket deleted successfully');
}

$pageHeading = i18n_t('admin.all_tickets', 'All Tickets');
$tickets = Ticket::findAll();
$showNewTicketButton = true;
$allowDelete = true;

include './ticket-list-view.php';
