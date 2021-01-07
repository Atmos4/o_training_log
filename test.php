<?php
require_once("template/functions.php");

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://www.strava.com/api/v3/oauth/token?code=56915d04b04c6fe04e910d04780e1db4a96a9b01&client_id=26669&client_secret=6fee4c6487ce90d9bcd495824c878c6df9a3d4aa&grant_type=authorization_code",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_HTTPHEADER => array(
    "Accept: */*",
    "Cache-Control: no-cache",
    "Connection: keep-alive",
    "Host: www.strava.com",
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  $json = json_decode($response,true);

  $token = $json["refresh_token"];

  $runner_id = $_SESSION['rid'];

  $req_strava = get_db()->prepare('UPDATE runners SET strava_token = ? WHERE id = ?');
  $req_strava->execute(array($token,$runner_id));
}
?>