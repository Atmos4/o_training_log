<?php

require_once("../template/functions.php");
require_once("../db/stats.db.php");

$is_admin = check_auth("COACH");
$is_user = check_auth("USER");

$runner_id = get_rid();

$year = '2016';
if (isset($_POST['year'])){
    $year = $_POST['year'];
}

$offset = -2;
if ($offset<0) {
    $year_start = $year-1;
    $year_end = $year;
}
else if  ($offset==0){
    $year_start = $year;
    $year_end = $year;
}
else {
    $year_start = $year;
    $year_end = $year+1;
}
$month_start = $offset>=0?1+$offset:13+$offset;
$month_end = $offset==0?12:$month_start-1;

$date_start = $year_start."-".$month_start."-01";
$date_end = $year_end."-".$month_end."-".cal_days_in_month(CAL_GREGORIAN, $month_end, $year_end);


$week_start = date('W',strtotime($year_start."-".$month_start."-01"));

$week_end = date('W',strtotime($year_end."-".$month_end."-".cal_days_in_month(CAL_GREGORIAN, $month_end, $year_end)));


$i=1;
if ($week_start>51){
    $timestamp_start =  strtotime(($year_start-1).'W'.$week_start.$i);
}
else $timestamp_start =  strtotime($year_start.'W'.$week_start.$i);

$date_start = date('Y-m-d',$timestamp_start);

$i=7;
if ($week_end == 1){
    $timestamp_end = strtotime(($year_end+1).'W'.$week_end.$i);
}
else $timestamp_end = strtotime($year_end.'W'.$week_end.$i);

$date_end = date('Y-m-d', $timestamp_end);

$types = get_types_by_week($runner_id,$date_start,$date_end)->data;
$intensities = get_intensities_by_week($runner_id,$date_start,$date_end)->data;

$weeks = array();
$totaltime = array();
$totaldistance = array();
$maxTime = 0;
$count=0;
foreach ($types as $type){
    if ($type['week']>$count)$count = $type['week'];

    if(!isset($weeks[$type['week']])){
        $weeks[$type['week']]=array();
    }
    if(!isset($totaltime[$type['week']])){
        $totaltime[$type['week']]=0;
    }
    if(!isset($totaldistance[$type['week']])){
        $totaldistance[$type['week']]=0;
    }
    array_push($weeks[$type['week']], $type);
    $totaltime[$type['week']]+=$type['duree'];
    $totaldistance[$type['week']]+=$type['distance'];

	if ($totaltime[$type['week']]>$maxTime)$maxTime = $totaltime[$type['week']];
}

$week_intensities = array();
foreach ($intensities as $intens){
    $week_intensities[$intens['week']] = array(1=>$intens['i1'],2=>$intens['i2'],3=>$intens['i3'],4=>$intens['i4'],5=>$intens['i5']);
}

$req_seances = get_db()->query('SELECT * FROM type_seance');
while ($donnees_seances = $req_seances->fetch()){
    $seances[$donnees_seances['id']]=$donnees_seances;
    
}

$icolors = array("#cccccc","#d4ff71","#e7e300","#e7b100","#e70000");
$itext = array("Régénération","Aérobie moyenne", "Aérobie haute", "Seuil", "PMA");
?>


<div id= "calendar"  class = "animatefade">
<?php 
for ($t=$timestamp_start;$t<=$timestamp_end;$t+=604800){
        $sem = date("W",$t);
        $i = intval($sem);
        $ttTime = 0;
        $ttDist = 0;
        ;?>
        <!-- A CHANGER : title c'est moche -->
    <div class = "weekcell" id = "<?=$i?>">
        <span class = "date"><?=date("d/m", strtotime($year.'W'.$sem.'1'))?> - <?=date("d/m", strtotime($year.'W'.$sem.'7'))?></span>
        <div class= "bytype-monthgraph type-graph small">
            <?php if (isset($totaltime[$i]) and isset($totaldistance[$i])){
                $ttTime = $totaltime[$i];
                $ttDist = $totaldistance[$i];
                foreach($weeks[$i] as $week_data){
                $typeRatio = ($week_data['duree']/$maxTime);?>
                <div class = "typecolumn" style = "width:<?=($typeRatio*80).'%'?>; background-color: <?=$seances[$week_data['type']]['color'];?>">
                    
                <?php if ($typeRatio>0.05){?>
                    <img class = "small_icone" src= <?= $seances[$week_data['type']]['images'] ?>>
                <?php }?>

                    <span class = "tooltipmonth" style = "background-color: <?=$seances[$week_data['type']]['color'];?>">
                        <?= $seances[$week_data['type']]['type'] ?> : 
                        <?=floor($week_data['duree']/3600)?>h<?=($week_data['duree']/60 -60*floor($week_data['duree']/3600))<10?"0":""?><?=($week_data['duree']/60 -60*floor($week_data['duree']/3600))?>
                        , <?=round($week_data['distance'],1)?> km - <?=round($typeRatio*100,1)?>%
                    </span>
                </div>
            <?php }?>
            <span class = "totalmonth"> <?=floor($ttTime/3600)?>h<?=($ttTime/60 -60*floor($ttTime/3600))<10?"0":""?><?=($ttTime/60 -60*floor($ttTime/3600))?></span>  
        </div>
        <div class= "bytype-monthgraph intensity-graph small">
            <?php 
                $maxIntens = $week_intensities[$i][1] + $week_intensities[$i][2] +$week_intensities[$i][3] +$week_intensities[$i][4] +$week_intensities[$i][5];
                foreach ($week_intensities[$i] as $index=>$line){
                    if ($line>0){
                    $typeRatio = $line/$maxIntens?>
                    <div class = "typecolumn" style = "width:<?=($typeRatio*85).'%'?>; background-color: <?=$icolors[$index-1]?>">

                        <span class = "tooltipmonth" style = "background-color: <?=$icolors[$index-1]?>">
                            <?= $itext[$index-1] ?> : 
                            <?=floor($line/60)?>h<?=($line -60*floor($line/60))<10?"0":""?><?=($line -60*floor($line/60))?>
                            , <?=round($typeRatio*100,1)?>%
                        </span>
                    </div>
                <?php 
                }}}?>
        </div>
    </div>
<?php }?>
</div>