<?php
include './header.php';
require_once './src/ticket.php';
require_once './src/requester.php';
require_once './src/team.php';
require_once './src/user.php';

$isClient = ($user->role === 'client');
$tickets = $isClient ? Ticket::findByRequesterUserId($user->id) : Ticket::findByMember($user->id);

$statusFilter = '';
if (isset($_GET['status']) && is_string($_GET['status'])) {
    $candidate = strtolower(trim($_GET['status']));
    $allowedStatuses = ['open', 'pending', 'solved', 'closed'];
    if (in_array($candidate, $allowedStatuses, true)) {
        $statusFilter = $candidate;
    }
}

if ($statusFilter !== '') {
    $tickets = array_values(array_filter($tickets, function ($ticket) use ($statusFilter) {
        return isset($ticket->status) && strtolower($ticket->status) === $statusFilter;
    }));
}


?>
<div id="content-wrapper">

    <div class="container-fluid">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="#"><?php echo htmlspecialchars(i18n_t('mytickets.breadcrumb.dashboard'), ENT_QUOTES, 'UTF-8'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo htmlspecialchars(i18n_t('mytickets.breadcrumb.title'), ENT_QUOTES, 'UTF-8'); ?></li>
    </ol>
        <div class="card mb-3">
            <div class="card-body">
                <?php if (empty($tickets)) : ?>
                    <div class="alert alert-info text-center mb-0">
                        <?php echo htmlspecialchars(i18n_t('mytickets.empty'), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php else : ?>
                <div class="table-responsive tickets-table">
                    <table class="table table-bordered table-hover table-sm table-mobile-stack" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th><?php echo htmlspecialchars(i18n_t('mytickets.table.subject'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(i18n_t('mytickets.table.requester'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(i18n_t('mytickets.table.team'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(i18n_t('mytickets.table.agent'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(i18n_t('mytickets.table.status'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(i18n_t('mytickets.table.requested'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(i18n_t('mytickets.table.action'), ENT_QUOTES, 'UTF-8'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket) : ?>
                                <?php $requesterObj = Requester::find($ticket->requester); ?>
                                <?php $teamObj = (!empty($ticket->team) && (int)$ticket->team > 0) ? Team::find($ticket->team) : null; ?>
                                <?php $agentName = $ticket->team_member ? (User::find($ticket->team_member)->name ?? '') : ''; ?>
                                <?php $date = new DateTime($ticket->created_at); ?>
                                <tr>
                                    <td data-label="<?php echo htmlspecialchars(i18n_t('mytickets.table.subject'), ENT_QUOTES, 'UTF-8'); ?>"><a href="./ticket-details.php?id=<?php echo $ticket->id ?>"><?php echo $ticket->title ?></a></td>
                                    <td data-label="<?php echo htmlspecialchars(i18n_t('mytickets.table.requester'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $requesterObj ? $requesterObj->name : ''; ?></td>
                                    <td data-label="<?php echo htmlspecialchars(i18n_t('mytickets.table.team'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $teamObj ? $teamObj->name : ''; ?></td>
                                    <td data-label="<?php echo htmlspecialchars(i18n_t('mytickets.table.agent'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $agentName; ?></td>
                                    <td data-label="<?php echo htmlspecialchars(i18n_t('mytickets.table.status'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php if ($ticket->status == 'solved') : ?>
                                            <span class="badge badge-success"><?php echo $ticket->status ?></span>
                                        <?php else : ?>
                                            <span class="badge badge-secondary"><?php echo $ticket->status ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="<?php echo htmlspecialchars(i18n_t('mytickets.table.requested'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $date->format('d-m-Y H:i') ?></td>
                                    <td data-label="<?php echo htmlspecialchars(i18n_t('mytickets.table.action'), ENT_QUOTES, 'UTF-8'); ?>" width="100px">
                                        <a class="btn btn-outline-primary btn-sm btn-block" href="./ticket-details.php?id=<?php echo $ticket->id ?>"><?php echo htmlspecialchars(i18n_t('mytickets.table.view'), ENT_QUOTES, 'UTF-8'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>


    </div>
    <!-- /.container-fluid -->



</div>
<!-- /.content-wrapper -->

</div>
<!-- /#wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="./index.php">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Page level plugin JavaScript-->
<script src="vendor/chart.js/Chart.min.js"></script>
<script src="vendor/datatables/jquery.dataTables.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.js"></script>

<!-- Custom scripts for all pages-->
<script src="js/sb-admin.min.js"></script>

<!-- Demo scripts for this page-->
<script src="js/demo/datatables-demo.js"></script>
<script src="js/demo/chart-area-demo.js"></script>

</body>

</html>
