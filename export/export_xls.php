<?php
require_once("../template/functions.php");
$is_admin = check_auth("COACH");
$is_user = check_auth("USER");

if (!isset($_GET['end']) or !isset($_GET['start']) or !isset($_GET['id']))exit;


$runner_id = $_GET['id'];

if ($is_user and $runner_id != $_SESSION['rid'])exit;


$week_data = array();
$req= get_db()->prepare('SELECT seances.*, days.fatigue, type_seance.type as stype
                    FROM seances 
                    LEFT JOIN days ON seances.date = days.date AND seances.runner_id = days.runner_id
                    LEFT JOIN type_seance on seances.type = type_seance.id
                    WHERE seances.runner_id = ? AND seances.DATE BETWEEN ? AND ?
                    ORDER BY DATE ASC');
$req->execute(array($runner_id,date("Y-m-d",$_GET['start']),date("Y-m-d",$_GET['end'])));
while ($data = $req->fetch()){
    array_push($week_data,array(
        'date' => $data['date'],
        'titre' => $data['title'],
        'type' => $data['stype'],
        'duree' => $data['duree'],
        'distance' => number_format ($data['distance'],2),
        'charge' => $data['charge'],
        'i1' => $data['zone1'],
        'i2' => $data['zone2'],
        'i3' => $data['zone3'],
        'i4' => $data['zone4'],
        'i5' => $data['zone5'],
        'fatigue' => $data['fatigue']
    ));
}


$filename = "export_" .$runner_id."_".date("Ymd",$_GET['start'])."_".date("Ymd",$_GET['end']).".xls";

header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Type: application/vnd.ms-excel");

$heading = false;
if(!empty($week_data)){
    foreach($week_data as $row) {
        if(!$heading) {
            echo implode("\t", array_keys($row)) . "\n";
            $heading = true;
        }
        echo implode("\t", array_values($row)) . "\n";
    }
}
exit;
?>