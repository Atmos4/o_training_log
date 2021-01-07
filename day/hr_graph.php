<?php 

require_once "../strava/strava_api.php";

$user = get_rid();

if (isset($_GET) and count($_GET) and isset($_GET['id'])){
    $strava_id = $_GET['id'];
}
else {
  exit;
}

$token = refresh_token($user);

if ($token->fail()){ 
  echo $token->msg;
  exit;
}

$hr_data = hr_data($strava_id,$token->msg);

echo $hr_data->msg;

