<?php
  include './header.php';
  require_once './src/requester.php';
  require_once './src/ticket.php';
  require_once './src/ticket-event.php';
  require './src/helper-functions.php';

  $isClient = ($user->role === 'client');

  $err = '';
  $msg = '';

  // Pre-fill fields to avoid undefined notices and improve UX after validation errors
  $prefillName = '';
  $prefillEmail = '';
  $prefillPhone = '';

  // If client is logged in, default to their stored contact info
  if ($isClient) {
      $prefillName = $user->name ?? '';
      $prefillEmail = $user->email ?? '';
      $prefillPhone = $user->phone ?? '';
  }

  # getting teams 
  $sql = "SELECT id, name FROM team ORDER BY name ASC";
  $res = $db->query($sql);
  $teams = [];
  while($row = $res->fetch_object()){
      $teams[] = $row;
  }

  if(isset($_POST['submit'])){
    
      $name = $_POST['name'];
      $email = $_POST['email'];
      $phone = $_POST['phone'];
      $subject = $_POST['subject'];
      $comment = $_POST['comment']; 
      $team = $_POST['team'];
      $priority = $_POST['priority'];

      // keep user input on validation errors
      $prefillName = $name;
      $prefillEmail = $email;
      $prefillPhone = $phone;

      if(strlen($name) < 1) {
          $err = "Please enter requester name";
      } else if(strlen($email) < 1) {
          $err = "Please enter requester email address";
      } else if(!isValidEmail($email)){
          $err = "PLease enter a valid email address";
      } else if(!isValidPhone($phone)){
          $err = "Please enter a valid phone number";
      } else if(strlen($subject) < 1){
          $err = "Please enter subject";
      } else if(strlen($comment) < 1){
          $err = "Please enter comment";
      } else {
        try{
            $db = Database::getInstance();
            $colCheck = $db->query("SHOW COLUMNS FROM requester LIKE 'user_id'");
            if ($colCheck && $colCheck->num_rows === 0) {
                $db->query("ALTER TABLE requester ADD COLUMN user_id INT DEFAULT NULL");
            }

            $existingRequester = Requester::findByEmail($email);
            $savedRequester = $existingRequester;

            if (!$existingRequester) {
                $requester = new Requester([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'user_id' => $user->id ?? null
                ]); //this obj has no id
                
                $savedRequester = $requester->save(); //this obj has the id,because of save();cz it returns an obj
            } else if (($existingRequester->user_id ?? null) === null && isset($user->id)) {
                $db->query("UPDATE requester SET user_id = " . (int)$user->id . " WHERE id = " . (int)$existingRequester->id);
                $existingRequester->user_id = $user->id;
            }
      
            $ticket = new Ticket([
                'title' => $subject,
                'body' => $comment,
                'requester' => $savedRequester->id
            ]); 
      
            $savedTicket = $ticket->save();

            $event = new Event([
                'ticket' => $savedTicket->id, 
                'user' => $user->id, 
                'body' => 'Ticket created'
            ]);
            $event->save();

            $msg = "Ticket generated successfully";
        } catch(Exception $e){
            $err = "Failed to generate ticket";
        }
      }
  }
?>
<div id="content-wrapper">

    <div class="container-fluid">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="#">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">New ticket</li>
        </ol>

        <div class="card mb-3">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <h3 class="mb-0">Create a new ticket</h3>
                        <small class="text-muted">Share the issue and how we can reach you.</small>
                    </div>
                    <div class="mt-2 mt-lg-0">
                        <a href="./mytickets.php" class="btn btn-outline-secondary btn-sm">My tickets</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if(strlen($err) > 1) :?>
                <div class="alert alert-danger text-center my-3" role="alert"> <strong>Failed! </strong> <?php echo $err;?></div>
                <?php endif?>

                <?php if(strlen($msg) > 1) :?>
                <div class="alert alert-success text-center my-3" role="alert"> <strong>Success! </strong> <?php echo $msg;?></div>
                <?php endif?>

                <form method="POST" action="<?php echo $_SERVER['PHP_SELF']?>" class="ticket-form">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="Enter name" value="<?php echo htmlspecialchars($prefillName); ?>" <?php echo $isClient ? 'readonly' : '';?>>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Email</label>
                            <input type="text" name="email" id="email" class="form-control" placeholder="Enter email" value="<?php echo htmlspecialchars($prefillEmail); ?>" <?php echo $isClient ? 'readonly' : '';?>>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="phone">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="Enter phone number" value="<?php echo htmlspecialchars($prefillPhone); ?>">
                        </div>
                    </div>
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="name" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Subject</label>
                        <div class="col-sm-8">
                            <input type="text" name="subject" class="form-control" id="" placeholder="Enter subject">
                        </div>
                    </div>
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="name" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Comment</label>
                        <div class="col-sm-8">
                            <textarea name="comment" class="form-control" id="" placeholder="Enter comment"></textarea>
                        </div>
                    </div>
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="name" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Team</label>
                        <div class="col-sm-8">
                            <select name="team" class="form-control">
                                <option>--select--</option>
                                <?php foreach($teams as $team):?>
                                <option value="<?php echo $team->id?>"> <?php echo $team->name?></option>
                                <?php endforeach?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="name" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Priority</label>
                        <div class="col-sm-8">
                            <select name="priority" class="form-control">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="submit" class="btn btn-lg btn-primary"> Create</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    <!-- /.container-fluid -->

    <!-- Sticky Footer -->
    <footer class="sticky-footer">
        <div class="container my-auto">
            <div class="copyright text-center my-auto">
            <span>Copyright © Synchlab Coding</span>
            </div>
        </div>
    </footer>

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

