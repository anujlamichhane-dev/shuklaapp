<?php
    include './header.php';
   
    require_once './src/user.php';

    $err = '';
    $msg = '';
    if(($isAdmin || $isCreator) && isset($_POST['update_role'], $_POST['user_id'], $_POST['role'])) {
      $targetId = (int)$_POST['user_id'];
      $newRole = $_POST['role'];
      $allowedRoles = ['member','client','moderator','admin','creator','mayor','deputymayor','spokesperson','chief_officer','info_officer'];
      if(in_array($newRole, $allowedRoles, true)) {
        try {
          User::updateRole($targetId, $newRole);
          $msg = 'Role updated.';
        } catch(Exception $e) {
          $err = 'Could not update role.';
        }
      } else {
        $err = 'Invalid role.';
      }
    }

    $users =  User::findAll();



    

 




?>
<div id="content-wrapper">

  <div class="container-fluid">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="#">Users</a>
      </li>
      <li class="breadcrumb-item active">Overview</li>
    </ol>
    <?php if($isAdmin || $isCreator): ?>
      <a class="btn btn-primary my-3" href="./newuser.php"><i class="fa fa-plus"></i>Create New User</a>
    <?php endif; ?>
    <div class="card mb-3">
            <div class="card-body">
                <?php if($err): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                <?php if($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                <div class="table-responsive users-table">
                    <table class="table table-bordered table-sm" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Created at</th>
                                <?php if($isAdmin || $isCreator): ?>
                                  <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                         <?php foreach($users as $user): ?>
                            <tr>
                                <td data-label="Name"><?php echo $user->name ?></td>
                                <td data-label="Role"><?php echo $user->role ?></td>
                                <td data-label="Email"><?php echo $user->email ?></td>
                                <td data-label="Phone"><?php echo $user->phone ?></td>
                                <?php $date = new DateTime($user->created_at) ?>
                                <td data-label="Created at"><?php echo $date->format('d-m-Y H:i:s') ?></td>
                                <?php if($isAdmin || $isCreator): ?>
                                  <td data-label="Actions">
                                    <form method="POST" action="users.php">
                                      <input type="hidden" name="user_id" value="<?php echo $user->id; ?>">
                                      <select name="role" class="form-control form-control-sm d-inline-block" style="width:auto;">
                                        <?php
                                          $roles = ['member','client','moderator','admin','creator','mayor','deputymayor','spokesperson','chief_officer','info_officer'];
                                          foreach($roles as $r){
                                            $sel = $r === $user->role ? 'selected' : '';
                                            echo "<option value=\"$r\" $sel>$r</option>";
                                          }
                                        ?>
                                      </select>
                                      <button class="btn btn-sm btn-primary" type="submit" name="update_role">Update</button>
                                    </form>
                                  </td>
                                <?php endif; ?>
                            </tr>
                          <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
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
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
  aria-hidden="true">
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
