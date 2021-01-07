<?php 

require_once "../template/functions.php";
require_once "../db/stats.db.php";

$user = get_rid();

if (isset($_GET) and count($_GET) and isset($_GET['date'])){
    $t = intval($_GET['date']);
}
else {
  exit;
}

$date_start_month = date('Y-m',$t).'-01';
$date_end_month = date('Y-m-t',$t);

$date_start = date('Y-m-d',strtotime('Last Monday',strtotime($date_start_month)));
$date_end = date('Y-m-d',strtotime('Next Sunday',strtotime($date_end_month)));

$intensities = get_intensities_by_week($user,$date_start,$date_end)->data;

$fatigues = get_fatigue_by_week($user,$date_start,$date_end)->data;

$trimp_loads = array();
$rpe_loads = array();
$timespans = array();
$week_labels = array();
$fatigue_average = array();

foreach ($intensities as $index=>$intens){
    $week_labels[] = 'Semaine '.$intens['week'];
    $timespans[] = intval($intens['duree'])/60;
    $rpe_loads[] = intval($intens['charge'])/60;
    $trimp_loads[] = round(get_cad_trimp($intens['i1'],$intens['i2'],$intens['i3'],$intens['i4'],$intens['i5']),2);
    $fatigue_average[] = round($fatigues[$index]['fatigue'],2);
}

echo json_encode(array(
    'labels' => $week_labels,
    'rpe' => $rpe_loads,
    'trimp' => $trimp_loads,
    'time' => $timespans,
    'fatigue' => $fatigue_average
));