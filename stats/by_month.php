<?php

require_once("../template/functions.php");
require_once("../db/stats.db.php");

$is_admin = check_auth("COACH");
$is_user = check_auth("USER");

if ($is_admin and isset($_SESSION['visit_id'])){
    $runner_id = $_SESSION['visit_id'];
}else $runner_id = $_SESSION['rid'];

$_SESSION['view'] = "month";

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

//----Max des stats----//

$max_intensities = get_intensities_by_week($runner_id,$date_start,$date_end)->data;
$max_fatigues = get_fatigue_by_week($runner_id,$date_start,$date_end)->data;

$trimp_loads = array();
$rpe_loads = array();
$fatigue_average = array();
$timespans = array();

foreach ($max_intensities as $index=>$intens){
    $rpe_loads[] = intval($intens['charge'])/60;
    $trimp_loads[] = round(get_cad_trimp($intens['i1'],$intens['i2'],$intens['i3'],$intens['i4'],$intens['i5']),2);
    $fatigue_average[] = round($max_fatigues[$index]['fatigue'],2);
    $timespans[] = intval($intens['duree'])/60;
}

$max_trimp= max($trimp_loads);
$max_rpe= max($rpe_loads);
$max_fatigue= max($fatigue_average);
$max_timespans = max($timespans);

//-----TOTAL PAR MOIS-----
$intensities = get_intensities_by_month($runner_id,$date_start,$date_end)->data;
$types = get_types_by_month($runner_id,$date_start,$date_end)->data;

$months = array();
$totaltime = array();
$totaldistance = array();
$maxTime = 0;
foreach ($types as $type){
    if(!isset($months[$type['mois']])){
        $months[$type['mois']]=array();
    }
    if(!isset($totaltime[$type['mois']])){
        $totaltime[$type['mois']]=0;
    }
    if(!isset($totaldistance[$type['mois']])){
        $totaldistance[$type['mois']]=0;
    }
    array_push($months[$type['mois']], $type);
    $totaltime[$type['mois']]+=$type['duree'];
    $totaldistance[$type['mois']]+=$type['distance'];

	if ($totaltime[$type['mois']]>$maxTime)$maxTime = $totaltime[$type['mois']];
}

$month_intensities = array();
foreach($intensities as $intens){
    $month_intensities[$intens['mois']] = array(1=>$intens['i1'],2=>$intens['i2'],3=>$intens['i3'],4=>$intens['i4'],5=>$intens['i5']);
}
//--------------
//------TOTAL SUR LA SAISON-----
$req_year=get_db()->prepare('SELECT type, SUM(distance) AS distance, SUM(TIME_TO_SEC(duree)) AS duree 
					FROM seances
					WHERE runner_id = ? AND date BETWEEN ? AND ? and type <> 4
                    GROUP BY type
                    ORDER BY duree DESC');

$req_year->execute(array($runner_id,$date_start,$date_end));
$year_sums = array();
while ($donnees_year = $req_year->fetch()){
    array_push($year_sums, $donnees_year);
}

$year_intensities = get_intensities_by_year($runner_id,$date_start,$date_end)->data;

//--------------
//------DAYS----
$req_days=get_db()->prepare('SELECT DAY(date) as day,MONTH(date) as month, type, distance, TIME_TO_SEC(duree) as duree, charge
					FROM seances
                    WHERE runner_id = ? AND date BETWEEN ? AND ? and type <> 4
                    ORDER BY date');

$req_days->execute(array($runner_id,$date_start, $date_end));
$month_days = array();
$maxtime_day = array();
while ($donnees = $req_days->fetch()){
	if (isset($donnees['type']) and $donnees['type']!=4){
        if (!isset($month_days[$donnees['month']])){
            $month_days[$donnees['month']]=array();
            $maxtime_day[$donnees['month']] = 0;
        }
        if (!isset($month_days[$donnees['month']][$donnees['day']])){
            $month_days[$donnees['month']][$donnees['day']]=array();
            $maxtime_tmp = 0;
        }
        array_push( $month_days[$donnees['month']][$donnees['day']], $donnees);
        $maxtime_tmp+=$donnees['duree'];
        if ($maxtime_tmp>$maxtime_day[$donnees['month']]){
            $maxtime_day[$donnees['month']] = $maxtime_tmp;
        }
    }
}
//-----------

$req_seances = get_db()->query('SELECT * FROM type_seance');
while ($donnees_seances = $req_seances->fetch()){
    $seances[$donnees_seances['id']]=$donnees_seances;
    
}


$icolors = array("#cccccc","#d4ff71","#e7e300","#e7b100","#e70000");
$itext = array("Régénération","Aérobie moyenne", "Aérobie haute", "Seuil", "PMA");

$month_names = ["Janvier","Février","Mars","Avril","Mai","Juin","Juillet","Août","Septembre","Octobre","Novembre","Décembre"];
$month_names_medium = ["Jan","Fév","Mar","Avr","Mai","Jun","Jul","Aoû","Sep","Oc","Nov","Déc"];
$month_names_short = [1,2,3,4,5,6,7,8,9,10,11,12];
?>


<div id= "calendar"  class = "animatefade">
    <input type="hidden" id="max_trimp" value="<?=$max_trimp?>">
    <input type="hidden" id="max_rpe" value="<?=$max_rpe?>">
    <input type="hidden" id="max_fatigue" value="<?=$max_fatigue?>">
    <input type="hidden" id="max_time" value="<?=$max_timespans?>">
<?php
    $yearTime = 0;
    for ($mon=$month_start;$mon<=$month_start+11;$mon++){
        $tmp_year = $year_start;
        if ($mon>12){
            $i = $mon-12;
            $tmp_year++;
        }
        else $i=$mon;
        $ttTime = 0;
        $ttDist = 0;
        ;?>

    <div class = "monthcell">
        <span class = 'date full'><?=$month_names[$i-1]?></span>
        <span class = 'date medium'><?=$month_names_medium[$i-1]?></span>
        <span class = 'date short'><?=$month_names_short[$i-1]?></span>

        <div class= "bytype-monthgraph type-graph large">
                
            <?php if (isset($totaltime[$i]) and isset($totaldistance[$i])){
            $ttTime = $totaltime[$i];
            $yearTime +=$ttTime;
            $ttDist = $totaldistance[$i];
                
                foreach($months[$i] as $month_data){
                $typeRatio = ($month_data['duree']/$maxTime);?>
                <div class = "typecolumn" style = "width:<?=($typeRatio*85).'%'?>; background-color: <?=$seances[$month_data['type']]['color'];?>">
                    
                <?php if ($typeRatio>0.1){?>
                    <img class = "icone margin" src= <?= $seances[$month_data['type']]['images'] ?>>
                <?php }?>

                    <span class = "tooltipmonth" style = "background-color: <?=$seances[$month_data['type']]['color'];?>">
                        <?= $seances[$month_data['type']]['type'] ?> : 
                        <?=floor($month_data['duree']/3600)?>h<?=($month_data['duree']/60 -60*floor($month_data['duree']/3600))<10?"0":""?><?=($month_data['duree']/60 -60*floor($month_data['duree']/3600))?>
                        , <?=round($month_data['distance'],1)?> km - <?=round(($month_data['duree']/$totaltime[$i])*100,1)?>%
                    </span>
                </div>
            <?php }?>
            <span class = "totalmonth"> <?=floor($ttTime/3600)?>h<?=($ttTime/60 -60*floor($ttTime/3600))<10?"0":""?><?=($ttTime/60 -60*floor($ttTime/3600))?></span>  
        </div>
        <div class= "bytype-monthgraph intensity-graph large">
            <?php 
                $maxIntens = $month_intensities[$i][1] + $month_intensities[$i][2] +$month_intensities[$i][3] +$month_intensities[$i][4] +$month_intensities[$i][5];
                foreach ($month_intensities[$i] as $index=>$line){
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
        <div class = "monthcollapse-wrapper">
            <canvas id="monthgraph-canvas-<?=$i?>" data-date="<?=strtotime($tmp_year.'-'.$i.'-01')?>" data-state="empty" style="display: block; height: 200px; width: 735px;position:relative" width="735" height="200" class="chartjs-render-monitor">
            </canvas>
        </div>
    </div>
<?php }?>
    <div class = "totalyear">
        <span>Total saison :</span>
        <span><?=floor($yearTime/3600)?>h<?=($yearTime/60 -60*floor($yearTime/3600))<10?"0":""?><?=($yearTime/60 -60*floor($yearTime/3600))?>
    </div>
    <div class = "bytype-monthgraph type-graph large">
    <?php foreach($year_sums as $tyear){
        $typeRatio = ($tyear['duree']/$yearTime);?>
        <div class = "typecolumn" style = "width:<?=($typeRatio*100).'%'?>; background-color: <?=$seances[$tyear['type']]['color'];?>">
            
        <?php if ($typeRatio>0.05){?>
            <img class = "icone" src= <?= $seances[$tyear['type']]['images'] ?>>
        <?php }?>

            <span class = "tooltipmonth" style = "background-color: <?=$seances[$tyear['type']]['color'];?>">
                <?= $seances[$tyear['type']]['type'] ?> : 
                <?=floor($tyear['duree']/3600)?>h<?=($tyear['duree']/60 -60*floor($tyear['duree']/3600))<10?"0":""?><?=($tyear['duree']/60 -60*floor($tyear['duree']/3600))?>
                , <?=round($tyear['distance'],1)?> km - <?=round($typeRatio*100,1)?>%
            </span>
        </div>
    <?php }?>
    </div>
    <div class = "bytype-monthgraph intensity-graph large">
    <?php
        $maxIntens = $year_intensities['i1']+$year_intensities['i2']+$year_intensities['i3']+$year_intensities['i4']+$year_intensities['i5'];
        for($index=1;$index<=5;$index++){
            $line = $year_intensities['i'.$index];
            $typeRatio = $line/$maxIntens;?>
            
        <div class = "typecolumn" style = "width:<?=($typeRatio*100).'%'?>; background-color: <?=$icolors[$index-1]?>">
            <span class = "tooltipmonth" style = "background-color: <?=$icolors[$index-1]?>">
                <?= $itext[$index-1] ?> : 
                <?=floor($line/60)?>h<?=($line -60*floor($line/60))<10?"0":""?><?=($line -60*floor($line/60))?>
                , <?=round($typeRatio*100,1)?>%
            </span>
        </div>

    <?php }?>
    </div>
</div>