<?php
//ini_set('display_errors', 1);
require_once './Database.php';
require_once './team-member.php';

$members = TeamMember::findByTeam($_POST['id']);

$data = [];
$seenUsers = [];

foreach($members as $member){
    if (isset($seenUsers[$member->user])) {
        continue;
    }

    $obj = new stdClass;
    $obj->id = $member->user;
    $obj->name = $member::getName($member->user);

    $data[] = $obj;
    $seenUsers[$member->user] = true;
    
}
//print_r($data);die();
echo json_encode($data);
