<?php
if (!isset($_GET['id']) || strlen($_GET['id']) < 1 || !ctype_digit($_GET['id'])) {
    header('Location: ./mytickets.php');
    exit();
}
include './header.php';

require_once './src/requester.php';
require_once './src/team.php';
require_once './src/ticket.php';
require_once './src/ticket-event.php';
require_once './src/team-member.php';
require_once './src/comment.php';

$err = '';
$msg = isset($_GET['created']) ? 'Ticket created successfully' : '';
$ticket = Ticket::find($_GET['id']);
if (!$ticket) {
    header('Location: ./mytickets.php');
    exit();
}
//print_r($ticket->team_member);die();
$requesterOwner = Requester::find($ticket->requester);
$isGuest = (($user->role ?? '') === 'guest');
$requesterEmail = strtolower(trim((string)($requesterOwner->email ?? '')));
$sessionEmail = strtolower(trim((string)($user->email ?? '')));
$requesterUserId = isset($requesterOwner->user_id) ? (int)$requesterOwner->user_id : 0;
$sessionUserId = isset($user->id) ? (int)$user->id : 0;
$ownsByUserId = $isClient && $requesterUserId > 0 && $requesterUserId === $sessionUserId;
$ownsByEmail = $requesterEmail !== '' && $requesterEmail === $sessionEmail;
$ownsByGuestSession = $isGuest && guestOwnsTicket($ticket->id);
$isRequesterOwner = $ownsByUserId || ($isClient && $ownsByEmail) || $ownsByGuestSession;
$canManageTicket = !($isClient || $isGuest);
if (($isClient || $isGuest) && !$isRequesterOwner) {
    echo '<script>window.location.href = "./mytickets.php";</script>';
    exit();
}
$teams = Team::findAll();
$commentDraft = '';

function ticketActorName($actorId, $requesterOwner)
{
    $actorId = (int)$actorId;
    if ($actorId > 0) {
        $name = TeamMember::getName($actorId);
        if ($name !== '') {
            return $name;
        }
    }

    $requesterName = trim((string)($requesterOwner->name ?? ''));
    if ($requesterName !== '') {
        return $requesterName;
    }

    return 'Guest';
}

if (isset($_POST['submit'])) {

    $teamMember = isset($_POST["team_member"]) && ctype_digit($_POST["team_member"]) ? (int)$_POST["team_member"] : null;
    $team = isset($_POST["team"]) && ctype_digit($_POST["team"]) ? (int)$_POST["team"] : (int)$ticket->team;
    // print_r($team);die();
    $id = $_GET['id'];
    //print_r($id);die();

    try {
        if ($teamMember === null || $team < 1) {
            throw new Exception('Invalid assignment');
        }

        $ticket = new Ticket([

            'team_member' => $teamMember,
            'title' => $ticket->title,
            'body' => $ticket->body,
            'requester' => $ticket->requester,
            'team' => $team,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
        ]);

        $updateTicket = $ticket->update($id);
        $ticket = $updateTicket;
        // print_r($updateTicket);die();

        $msg = "Ticket assigned successfully";
//hiii
    } catch (Exception $e) {

        $err = "Failed to assigned ticket";

    }
}

if (isset($_POST['comment'])) {

    $commentDraft = trim($_POST['body'] ?? '');

    try {
        if ($commentDraft === '') {
            throw new Exception('Please enter comment');
        }

        $comment = new Comment([
            'ticket-id' => $ticket->id,
            'team-member' => $user->id ?? null,
            'body' => $commentDraft,

        ]);
        $comment->save();
        $msg = "Successfully comment on the ticket";
        $commentDraft = '';

    } catch (Exception $e) {
        $err = $e->getMessage() ?: "Failed to comment on the ticket";
    }

}

$events = Event::findByTicket($ticket->id);
$comments = Comment::findByTicket($ticket->id);
$originalCommentBody = trim((string)($ticket->body ?? ''));
$originalCommentAuthor = trim((string)($requesterOwner->name ?? 'Requester'));
$ticketCreatedAt = !empty($ticket->created_at) ? new DateTime($ticket->created_at) : null;

?>
<div id="content-wrapper">

    <div class="container-fluid">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="#">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Ticket details</li>
        </ol>
        <div class="card mb-3">
            <div class="card-header">
                <div class="row mx-auto">
                    <div>
                        <?php echo $ticket->displayStatusBadge()?>
                        <small class="text-info ml-2"><?php echo htmlspecialchars((string)$ticket->title, ENT_QUOTES, 'UTF-8')?> <span class="text-muted">
                                <?php if ($ticketCreatedAt instanceof DateTime): ?>
                                <?php echo $ticketCreatedAt->format('d-m-Y H:i:s')?>
                                <?php endif; ?>
                            </span></small>
                    </div>
                  
                </div>

            </div>
            <div class="card-body">
                <?php if(strlen($err) > 1) :?>
                <div class="alert alert-danger text-center my-3" role="alert"> <strong>Failed! </strong> <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8');?></div>
                <?php endif?>

                <?php if(strlen($msg) > 1) :?>
                <div class="alert alert-success text-center my-3" role="alert"> <strong>Success! </strong> <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');?></div>
                <?php endif?>

                <?php if($canManageTicket): ?>
                    <form method="post">
                        <div class="col-lg-8 col-md-8 col-sm-12 offset-lg-2 offset-md-2">
                            <div class="form-group row">
                                <label for="team" class="col-sm-3 col-form-label">Team</label>
                                <div class="col-sm-8">
                                    <select class="form-control" id="team-dropdown" name="team"
                                        onchange="getTeamMember(event.target.value)">
                                        <option>--select--</option>
                                        <?php foreach($teams as $team):?>
                                        <option <?php echo $team->id == $ticket->team ? 'selected' : null?> value="<?php echo $team->id?>"><?php echo $team->name?></option>
                                        <?php endforeach?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="assigned" class="col-sm-3 col-form-label">Assigned</label>
                                <div class="col-sm-8">
                                    <select class="form-control"  name="team_member" id="team-member-dropdown">
                                        <option>--select--</option>
                                    </select>
                                </div>
                            </div>
                            <div class="text-center">
                                <button class="btn btn-primary" type="submit" name="submit" >Assign</button>
                            </div>
                        </div>

                    </form>
                <?php endif; ?>

                <?php if ($originalCommentBody !== ''): ?>
                <div class="col-lg-8 col-md-8 col-sm-12 offset-lg-2 offset-md-2 mt-4">
                    <div class="list-group">
                        <div class="list-group-item">
                            <small class="text-muted d-block mb-2">Original request</small>
                            <h6 class="mb-1"><?php echo htmlspecialchars($originalCommentAuthor, ENT_QUOTES, 'UTF-8'); ?></h6>
                            <div class="d-flex w-100 justify-content-between flex-wrap">
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($originalCommentBody, ENT_QUOTES, 'UTF-8')); ?></p>
                                <?php if ($ticketCreatedAt instanceof DateTime): ?>
                                <small><?php echo $ticketCreatedAt->format('d-m-Y H:i:s'); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <form method="POST" action="">
        <div class="form-group row col-lg-8 offset-lg-2 col-md-8 col-sm-12 offset-md-2">
      
            <label for="team" class="col-sm-12 col-lg-3 col-md-3 col-form-label">Comment</label>
            <div class="col-sm-8">
                <textarea class="form-control" name="body"><?php echo htmlspecialchars($commentDraft, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <button type="submit" name="comment" class="btn btn-success mt-2">comment</button>
       </form>
        <div class="form-group row col-lg-8 offset-lg-2 col-md-8 col-sm-12 offset-md-2"style="margin-top:60px">

        <?php if($canManageTicket): ?>
        <form id="formData" class="grid-form"  enctype="multipart/form-data" method="POST">
                            <label for="team"   style="margin-left:180px">Change Ticket Status</label>
                            <div class="col-sm-8">
                       
                            <input type="hidden" autofocus name="id" value="<?php echo $ticket->id ?>">
                                <select class="form-control" id="status" name="status" style="margin-left:170px">
                                 
                                    <option >--select--</option>
                                  
                                    <option value="open">open</option>
                                    <option value="pending">pending</option>
                                    <option value="closed">closed</option>
                                    <option value="solved">solved</option>
              
                                </select>
                            </div>
                            <button type="submit" name="submit" class="btn btn-success" style="margin-top:10px;margin-left:185px">change</button>
                            </form>
                           
                        </div>
                        <div id="msg">
                    </div>
        <?php endif; ?>
        </div>

        <div class="col-lg-12 my-3">
            <div class="list-group">
                <?php foreach($comments as $c):?>
                <a href="#" class="list-group-item list-group-item-action">
                    <h6 class="mb-1"><?php echo htmlspecialchars(ticketActorName($c->team_member, $requesterOwner), ENT_QUOTES, 'UTF-8')?></h6>
                    <div class="d-flex w-100 justify-content-between">
                        
                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($c->body, ENT_QUOTES, 'UTF-8'))?></p>
                        <?php $d = new DateTime($c->created_at)?>
                        <small><?php echo $d->format('d-m-Y H:i:s')?></small>
                    </div>
                </a>
                <?php endforeach?>
            </div>
        </div>

        <div class="col-lg-12 my-3">
            <div class="list-group">
                <?php foreach($events as $e):?>
                <a href="#" class="list-group-item list-group-item-action">
                    <h6 class="mb-1"><?php echo htmlspecialchars(ticketActorName($e->user, $requesterOwner), ENT_QUOTES, 'UTF-8')?></h6>
                    <div class="d-flex w-100 justify-content-between">
                        
                        <p class="mb-1"><?php echo htmlspecialchars($e->body, ENT_QUOTES, 'UTF-8')?></p>
                        <?php $d = new DateTime($e->created_at)?>
                        <small><?php echo $d->format('d-m-Y H:i:s')?></small>
                    </div>
                </a>
                <?php endforeach?>
            </div>
        </div>
    </div>
    <footer class="sticky-footer">
        <div class="container my-auto">
            <div class="copyright text-center my-auto">
            <span>Copyright © Synchlab Coding</span>
            </div>
        </div>
    </footer>

</div>

<?php include './footer.php'?>
<script>

jQuery('#formData').submit(function (e) {
    e.preventDefault();
    var formData = new FormData($(this)[0]);
    jQuery('#msg').html(
        '<div class="flakes-message success" style="text-align:center"><strong>Processing...</strong></div>'
        );

    jQuery.ajax({
        url: './src/update-ticket.php',
        type: 'post',
        dataType: 'text',
        data: formData,
        contentType: false,
        processData: false,
        success: function (res) {
            let result = JSON.parse(res)
            if (result.status == 200) {

                jQuery('#msg').html(
                    '<div class="btn btn-success" style="text-align:center"><strong><span class="fa fa-check"></span> Success!</strong>' +
                    result.msg + '</div>');
                jQuery('#formEvents').trigger("reset");
            } else {

                jQuery('#msg').html(
                    '<div class="btn btn-danger" style="text-align:center"><strong><span class="fa fa-times"></span> Failed!</strong>' +
                    result.msg + '</div>');

            }

        }
    });
});

</script>
