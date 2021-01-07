<?php
require_once("../template/functions.php");

$is_admin = check_auth("COACH");
$is_user = check_auth("USER");

$_SESSION['currentmode']="training";

$visit = false;

if ($is_admin and isset($_SESSION['visit_id'])){
    $runner_id = $_SESSION['visit_id'];
    $visit = true;
    
    //---Coureur
    $req_runner = get_db()->prepare('SELECT * FROM runners WHERE id = ? LIMIT 1');
    $req_runner->execute(array($runner_id));
    $data_runner = $req_runner->fetch();
    $structure = $data_runner['structure_id'];
}else {
    $runner_id = $_SESSION['rid'];
    $structure = $_SESSION['structure_id'];
}

unset($_SESSION['view']);

if (isset($_POST['date'])){
    $now = strtotime($_POST['date']);
} else  $now = strtotime('2016-03-18');

$year = date('Y', $now);
$month = date('m', $now);

$_SESSION['currentdate'] = $year.'-'.$month.'-1';

$week_start = date('W',strtotime($year.'-'.$month.'-1'));

$week_end = date('W',strtotime($year.'-'.$month.'-'.date('t',strtotime($year.'-'.$month.'-1'))));


$i=1;
if ($week_start>51){
    $timestamp_start =  strtotime(($year-1).'W'.$week_start.$i);
}
else $timestamp_start =  strtotime($year.'W'.$week_start.$i);

$timestamp_start+=43200;

$date_start = date('Y-m-d',$timestamp_start);

$i=7;
if ($week_end == 1){
    $timestamp_end = strtotime(($year+1).'W'.$week_end.$i);
}
else $timestamp_end = strtotime($year.'W'.$week_end.$i);

$timestamp_end+=43200;
$date_end = date('Y-m-d', $timestamp_end);


//---Séances
$req=get_db()->prepare('SELECT * 
					FROM seances
					WHERE runner_id = ? AND DATE BETWEEN ? AND ? 
					ORDER BY  DATE ASC');
$req->execute(array($runner_id,$date_start,$date_end));
$details = array();
while ($donnees = $req->fetch()){
	if (!isset($details[$donnees['date']])) {
		$details[$donnees['date']] = array();
	}
	if (isset($donnees['type']) and $donnees['type']!=4)
		array_push($details[$donnees['date']], $donnees);
}
//----

//---Planning
if ($structure!=0){
    if ($is_user or $visit){
        $req_planning=get_db()->prepare('SELECT planning.*, votes.vote as vote
                            FROM planning LEFT JOIN (SELECT planning_vote.* FROM planning_vote WHERE planning_vote.runner_id = ?) as votes ON votes.planning_id = planning.id
                            WHERE (planning.runner_id = ? OR planning.structure_id = ?) AND DATE BETWEEN ? AND ? 
                            ORDER BY DATE ASC,id ASC');
        $req_planning->execute(array($runner_id,$runner_id,$structure,$date_start,$date_end));
    }
    else{
        $req_planning=get_db()->prepare('SELECT planning.*
                FROM planning
                WHERE planning.structure_id = ? AND DATE BETWEEN ? AND ? 
                ORDER BY DATE ASC,id ASC');
        $req_planning->execute(array($structure,$date_start,$date_end));
    }
}else{
    $req_planning=get_db()->prepare('SELECT * 
                        FROM planning
                        WHERE runner_id = ? AND DATE BETWEEN ? AND ? 
                        ORDER BY DATE ASC,id ASC');
    $req_planning->execute(array($runner_id,$date_start,$date_end));
}
$plannings = array();
while ($donnees_plan = $req_planning->fetch()){
	if (!isset($plannings[$donnees_plan['date']])) {
		$plannings[$donnees_plan['date']] = array();
	}
	if (isset($donnees_plan['type']) and $donnees_plan['type']!=4)
		array_push($plannings[$donnees_plan['date']], $donnees_plan);
}
//----

//---Etat du jour
$req_days=get_db()->prepare('SELECT days.*
                        FROM days
                        WHERE runner_id = ? AND DATE BETWEEN ? AND ?
					    ORDER BY DATE ASC');
$req_days->execute(array($runner_id,$date_start,$date_end));
$data_days = array();
while ($donnees_days = $req_days->fetch()){
    $data_days[$donnees_days['date']] = $donnees_days;
}
//----

//---Type de séances
$seances = array();
$req_seances = get_db()->query('SELECT * FROM type_seance');
while ($donnees_seances = $req_seances->fetch()){
	$seances[$donnees_seances['id']]=$donnees_seances;
}
//----

//---Type d'états du jour
$states = array();
$req_state = get_db()->query('SELECT * FROM state_day');
while ($donnees_state = $req_state->fetch()){
	$states[$donnees_state['state_id']]= $donnees_state;
}
//----

//---Total du mois
$req_ttmonth = get_db()->prepare('SELECT type, SUM(distance) AS distance, SUM(TIME_TO_SEC(duree)) AS duree
                            FROM seances
                            WHERE runner_id = ? AND YEAR(date) = ? AND MONTH(date) = ? and type <> 4
                            GROUP BY type
                            ORDER BY duree DESC');
$req_ttmonth->execute(array($runner_id,$year,$month));
$month_sums = array();
$monthTime = 0;
while ($donnees_ttmonth = $req_ttmonth->fetch()){
    array_push($month_sums, $donnees_ttmonth);
    $monthTime += $donnees_ttmonth['duree'];
}
//----

$req_ttmonth_intensities = query_db('SELECT SUM(zone1) as i1, SUM(zone2) as i2, SUM(zone3) as i3, SUM(zone4) as i4, SUM(zone5) as i5
                            FROM seances
                            WHERE runner_id = ? and YEAR(date) = ? AND MONTH(date) = ? and type <> 4', array($runner_id, $year, $month));
$ttmonth_i = $req_ttmonth_intensities->fetchAll();

//---Totaux semaines
$req_sem=get_db()->prepare('SELECT WEEK(date,1) as week,type, SUM(distance) AS distance, SUM(TIME_TO_SEC(duree)) AS duree, SUM(deniv) as deniv
        FROM seances 
        WHERE runner_id = ? AND date BETWEEN ? AND ?
        GROUP BY WEEK(date,1), type
        ORDER BY  WEEK(DATE,1) ASC, duree DESC');
$req_sem->execute(array($runner_id,$date_start,$date_end));
$tt_sem = array();
$time_sem = array();
while ($dsem = $req_sem->fetch()){
    if (!isset($tt_sem[$dsem['week']])){
        $tt_sem[$dsem['week']]=array();
        $time_sem[$dsem['week']] = 0;
    }
    array_push($tt_sem[$dsem['week']],$dsem);
    $time_sem[$dsem['week']]+=$dsem['duree'];

}
//----

//---Totaux planif semaine
if ($structure!=0){
    $req_plan_sem=query_db('SELECT WEEK(date,1) as week,type, SUM(TIME_TO_SEC(duree)) AS duree
        FROM planning LEFT JOIN planning_vote ON planning.id = planning_vote.planning_id
        WHERE (planning.runner_id = ? OR (planning.structure_id = ? and planning_vote.runner_id = ? and planning_vote.vote = 1)) AND DATE BETWEEN ? AND ? 
        GROUP BY WEEK(date,1), type
        ORDER BY  WEEK(DATE,1) ASC, 
        duree DESC',
        array($runner_id,$structure,$runner_id,$date_start,$date_end));
}else{
    $req_plan_sem=query_db('SELECT WEEK(date,1) as week,type, SUM(TIME_TO_SEC(duree)) AS duree
        FROM planning 
        WHERE runner_id = ? AND date BETWEEN ? AND ?
        GROUP BY WEEK(date,1), type
        ORDER BY  WEEK(DATE,1) ASC, duree DESC',
        array($runner_id,$date_start,$date_end));
}

$tt_plan_sem = array();
$time_plan_sem = array();
while ($psem = $req_plan_sem->fetch()){
    if (!isset($tt_plan_sem[$psem['week']])){
        $tt_plan_sem[$psem['week']]=array();
        $time_plan_sem[$psem['week']] = 0;
    }
    array_push($tt_plan_sem[$psem['week']],$psem);
    $time_plan_sem[$psem['week']]+=$psem['duree'];

}


//---Commentaires semaine
$req_comm_sem = get_db()->prepare('SELECT * FROM comments WHERE runner_id = ? and date BETWEEN ? and ? ORDER BY date ASC');
$req_comm_sem->execute(array($runner_id,$date_start,$date_end));
$comments_week = array();
while ($cs = $req_comm_sem->fetch()){
    $comments_week[$cs['date']]=$cs;
}

//---Couleurs pour le graphe des intensités
$icolors = array("#cccccc","#d4ff71","#e7e300","#e7b100","#e70000");
$itext = array("Régénération","Aérobie moyenne", "Aérobie haute", "Seuil", "PMA");

$line = array();
?>

<input type = "hidden" id = "strava-token" value = "<?=isset($_SESSION['strava_token'])?$_SESSION['strava_token']:""?>">
<table id= "calendar"  class = "animatefade">
    <thead>
        <tr>
            <th><span class = "full">Lundi</span><span class = "medium">Lun</span><span class = "short">L</span></th>
            <th><span class = "full">Mardi</span><span class = "medium">Mar</span><span class = "short">M</span></th>
            <th><span class = "full">Mercredi</span><span class = "medium">Mer</span><span class = "short">M</span></th>
            <th><span class = "full">Jeudi</span><span class = "medium">Jeu</span><span class = "short">J</span></th>
            <th><span class = "full">Vendredi</span><span class = "medium">Ven</span><span class = "short">V</span></th>
            <th><span class = "full">Samedi</span><span class = "medium">Sam</span><span class = "short">S</span></th>
            <th><span class = "full">Dimanche</span><span class = "medium">Dim</span><span class = "short">D</span></th>
        </tr>
    </thead>
    <tbody>
    <?php 
    $ttMonthKmR = 0;
    $ttMonthKmA = 0;
    $ttMonthM = 0;
    $ttMonthH = 0;
    $t= $timestamp_start;
    $week_tstart=0;
    $week_tend = 0;
    while ($t <= $timestamp_end) {
    
    $day = date('Y-m-d', $t);
    $num_day = date('j', $t);
    $current_month = date('n', $t);
    if (date('N',$t)==1){
        $week_tstart = $t;
        echo "<tr>";
        $ttKmR = 0;//Distance en course à pied
        $ttKmA = 0;//Distance en alternatif
        $ttH = 0;
        $ttM = 0;
        $pttH=0;
        $pttM=0;
        $tti = array(0,0,0,0,0);

        $rpe = array();
        $trimp_sem = 0;
    }
    ?>

            <td id = "<?=$day?>" class = "calcell <?=(($current_month!=$month)?"outofmonth":"")?> <?php
            if((!isset($details[$day])) and ($t<strtotime(date("Y-m-d"))+86400)){?>link" onclick =  "window.location = 'day_view.php?date=<?=$day?>'"
            <?php }else{?>"<?php }?>>
            <?php
            $totalDayM = 0;
            $totalDayH = 0;
            $totalDayKm = 0;
            $rpe_day = 0;
            $trimp_day = 0;
            ?>

                <span class = "date" <?php if ($day == date("Y-m-d")) echo "style='color:rgb(255, 94, 94);font-weight:bold;'";?>><?= $num_day?></span>
                <?php /*if ($t<strtotime(date("Y-m-d"))+86400){if (isset($data_days[$day])){
                    if ($data_days[$day]['fatigue']==0){?>
                        <i class = "fa fa-exclamation-triangle tip warning"><span class = "tipcontent">Données du jour incomplètes</span></i>
                    <?php }}else {?><i class = "fa fa-exclamation-triangle tip warning"><span class = "tipcontent">Pas de données du jour</span></i><?php }}*/?>

                <?php 
                if (isset($plannings[$day])){
                    foreach($plannings[$day] as $plan){
                        if ($plan['structure_id']==0 or (isset($plan['vote']) and $plan['vote'])){
                            $hms = explode(':',$plan['duree']);
                            $pttH += $hms[0];
                            $pttM += $hms[1];
                        }
                    }
                }
                
                if (isset($details[$day]) and is_array($details[$day])){
                        $size = sizeof($details[$day]);?>
                <div class = "resp-button">
                    <?=$size?>
                </div><?php }/*?>
                <a href = "day_view.php?date=<?=$day?>">
                <?php */?>
                <div id = "<?=$day?>" class = "resp-link">
                <?php 
                if (isset($details[$day]) and is_array($details[$day])){
                ?>

                <div <?php if ($size >2) echo "class= small_container";else echo "class = normal_container"; ?>>

                <?php foreach ($details[$day] as $id) {
                    $line = $id;
                    if($line['type'] == 4) {
                        continue;
                    }
                    $time = explode(":",$line['duree']);

                    $totalDayH+=$time[0];
                    $totalDayM+=$time[1];
                    $totalDayKm+=$line['distance'];

                    $ttH += $time[0];
                    $ttM += $time[1];
                    $time_in_min = $time[0]*60 + $time[1];
                    $tti[0]+=isset($line['zone1'])?$line['zone1']:0;
                    $tti[1]+=isset($line['zone2'])?$line['zone2']:0;
                    $tti[2]+=isset($line['zone3'])?$line['zone3']:0;
                    $tti[3]+=isset($line['zone4'])?$line['zone4']:0;
                    $tti[4]+=isset($line['zone5'])?$line['zone5']:0;
                    
                    $rpe_day += get_rpe_load($time_in_min,$line['charge']);
                    $trimp_day += get_cad_trimp($line['zone1'],$line['zone2'],$line['zone3'],$line['zone4'],$line['zone5']);

                    if ($line['type']<4)$ttKmR += $line['distance'];
                    else $ttKmA += $line['distance'];
                    ?>

                    <div onclick=" window.location = 'day_view.php?date=<?=$day?>&id=<?=$line['seance_id']?>'" style="background-color:<?=$seances[$line['type']]['color'];?>;cursor:pointer;"<?php 
                    if ($size >4) echo " class='cell small_cell'";
                    else if ($size >2) echo " class = 'cell medium_cell'";
                    else echo " class ='cell normal_cell'";?>>
                        <img <?php if ($size<5) {?> class = "icone margin" <?php } else { ?> class = "small_icone" <?php }?> src= <?= $seances[$line['type']]['images'] ?> />
                        <?php if($size<3 and $line['title']!="") {?>
                        <span class = "tooltip-up" style="background-color:<?=$seances[$line['type']]['color'];?>"><?=$line['title']?></span>
                        <?php }?>
                        <span <?php if ($size <3) { ?> class = "content"<?php }else{?>class = "tooltip" style="background-color:<?=$seances[$line['type']]['color'];?>"<?php } ?>>
                            <?php if($size>2 and $line['title']!="") {?><?=$line['title']?><br/><?php }?>
                            <?=$time[0] ?>h <?=$time[1]?><br/> <?=$line['distance']?> km
                        </span>
                        <?php if ($line['charge']==0){?>
                            <i class = "fa fa-exclamation-triangle tip warning"><div class = "tipcontent">Remplir la charge d'entrainement</div></i>
                        <?php }?>
                    </div>
                <?php }
                $rpe[date('N',$t)] = $rpe_day;
                ?>
                </div>
                <?php if ($size>1){?>
                <div class = "bottomSum">
                    <span><?= $totalDayH + floor($totalDayM/60)?>h<?= $totalDayM - 60*floor($totalDayM/60)<10?"0":""?><?= $totalDayM - 60*floor($totalDayM/60)?></span>
                    <span><?=$totalDayKm?>km</span>
                </div>
                <?php }?>
                
                <?php }else{?>

                <div class = "small_container">
                    <?php if ($t<strtotime(date("Y-m-d"))+86400){?>
                    <span class="rest">
                    <?php if (isset($data_days[$day])){
                            if ($data_days[$day]['state']!=0){
                                echo $states[$data_days[$day]['state']]['state_name'];
                            }
                            else echo "Repos";
                        }?>
                    </span>
                    <?php }else {
                        if (isset($plannings[$day])){
                            $totalDayM = 0;
                            $totalDayH = 0;
                            foreach($plannings[$day] as $plan){
                                    $hms = explode(':',$plan['duree']);
                                    
                                    if ($plan['structure_id']==0 or (isset($plan['vote']) and $plan['vote'])){
                                    $totalDayH+=$hms[0];
                                    $totalDayM+=$hms[1];
                                    }?>
    
                            <div onclick=" window.location = 'day_view.php?date=<?=$day?>&planning_id=<?=$plan['id']?>'" id="<?=$plan['id']?>" class = "planning_cell" style="background-color:<?=$seances[$plan['type']]['color'];?>
                                <?php if ($plan['structure_id']!=0 and isset($plan['vote'])){ if($plan['vote'])echo "; box-shadow : 0px 0px 30px white;"; else echo "; opacity: 0.2;";}?>" onclick="window.location = 'day_view.php?date=<?=$day?>&planning_id=<?=$plan['id']?>'" data-structure="<?=$plan['structure_id']?>" data-admin="<?=$is_admin?1:0?>">
                                
                                <img id = "<?=$plan['type']?>" class = "icone margin" src= <?= $seances[$plan['type']]['images'] ?>>
                                
                                <span style="background-color:<?=$seances[$plan['type']]['color'];?>" class = "hidden details"><?=stripslashes($plan['details'])?></span>
                                
                                <?php if (($is_user or $visit) and $plan['structure_id']!=0){?>
    
                                <div class = "vote-mask">
                                    <?php if (isset($plan['vote'])){
                                        if ($plan['vote']){?>
    
                                    <i class="fa fa-check"></i>
    
                                        <?php }else{?>
    
                                    <i class="fa fa-times"></i>
    
                                    <?php }}else{?>
    
                                    <i class="fa fa-question"></i>
    
                                    <?php }?>
                                </div>
    
                                <?php }?>
                                
                                <span style="background-color:<?=$seances[$plan['type']]['color'];?>" class = "tooltip">
                                    <?php if ($plan["titre"]!=""){?><span class = "titre"><?=$plan["titre"]?></span><br/><?php }?>
                                    <span class = "hours"><?=$hms[0]?></span>h<span class="min"><?=$hms[1]?></span>
                                </span>
    
                            </div>
                            <?php }}?>
                        
                            <span class = "plus-button-day" data-day = "<?=$day?>"><img src="images/add-white.png"></span>
                        </div>
                        <?php if (isset($plannings[$day]) and count($plannings[$day])>0){?>
                        <div class = "bottomSum">
                            <span><?= $totalDayH + floor($totalDayM/60)?>h<?= $totalDayM - 60*floor($totalDayM/60)<10?"0":""?><?= $totalDayM - 60*floor($totalDayM/60)?></span>
                        </div>
                    <?php }?>
                    
                    </div>
                    <?php }?>
                </div>
                
                <?php }?>
                </div>
            
            </td>
            <?php 
            //End of the week
            if(date('N',$t)==7){
                $week_tend = $t+86400;?>
            <td class="endcell">
                <input type = "hidden" id = "tstart" value = "<?=$week_tstart?>">
                <input type = "hidden" id = "tend" value = "<?=$week_tend?>">
                <span class="total">
                    <?= $ttH + floor($ttM/60)?>h<?= $ttM - 60*floor($ttM/60)<10?"0":""?><?= $ttM - 60*floor($ttM/60)?><?php if (isset($comments_week[date("Y-m-d",$t)])){
                        if ($comments_week[date("Y-m-d",$t)]['coach_content']!=""){?><i class = "fa fa-exclamation-circle tip neutral margin-left"><span class = "tipcontent">Le coach a commenté !</span></i><?php }}?>
                </span>
                
                <?php if ($pttH>0 or $pttM>0){?>
                <span class="total">
                    (<?= $pttH + floor($pttM/60)?>h<?= $pttM - 60*floor($pttM/60)<10?"0":""?><?= $pttM - 60*floor($pttM/60)?>)
                </span>
                <?php }?>
                
                
                <div class = "week-actions">
                    <i class = "fas fa-chevron-down fa-2x details-week"></i>
                    <?php  if (isset($_SESSION['strava_token'])){?>
                    <img class = "sync-week" src="images/sync-white.png">
                    <?php }else{if ($is_admin){?>
                    <i class = "export-week fa fa-download fa-lg" onclick = "location.replace('export/export_xls.php?start=<?=$week_tstart?>&end=<?=$t?>')">                    </i>
                    <?php }}?>
                </div>
            </td>
            </tr>
            <tr class = "week-details-panel">
                <td colspan = "7">
                <div class ="week-panel-wrapper">
                    <?php 
                    $max_hours = 0;
                    if(isset($tt_sem[intval(date('W',$t))]) and isset($tt_plan_sem[intval(date('W',$t))])){
                        $max_hours=max($time_plan_sem[intval(date('W',$t))],$time_sem[intval(date('W',$t))]);
                    }?>
                    <?php if (isset($tt_plan_sem[intval(date('W',$t))])){?>
                    <div class = "bytype-monthgraph">
                    <?php 
                    foreach($tt_plan_sem[intval(date('W',$t))] as $tpweek){
                        $max_hours_plan = $max_hours?$max_hours:$time_plan_sem[intval(date('W',$t))];
                        $totalRatio = $tpweek['duree']/$max_hours_plan;
                        $typeRatio = ($tpweek['duree']/$time_plan_sem[intval(date('W',$t))]);?>
                            <div class = "typecolumn" style = "margin: 2px 0 5px 0;width:<?=($totalRatio*90).'%'?>; height : 50px;background-color: <?=$seances[$tpweek['type']]['color'];?>">
                                
                            <?php if ($typeRatio>0.05){?>
                                <img class = "icone" src= <?= $seances[$tpweek['type']]['images'] ?>>
                            <?php }?>

                                <span class = "tooltipmonth" style = "background-color: <?=$seances[$tpweek['type']]['color'];?>">
                                    <?= $seances[$tpweek['type']]['type'] ?> : 
                                    <?=floor($tpweek['duree']/3600)?>h<?=($tpweek['duree']/60 -60*floor($tpweek['duree']/3600))<10?"0":""?><?=($tpweek['duree']/60 -60*floor($tpweek['duree']/3600))?>
                                     - <?=round($typeRatio*100,1)?>%
                                </span>
                            </div>
                    <?php }?>
                        <span class = "totalmonth"><?= $pttH + floor($pttM/60)?>h<?= $pttM - 60*floor($pttM/60)<10?"0":""?><?= $pttM - 60*floor($pttM/60)?><br/>(Prévu)</span> 
                    </div>
                    <?php }
                    if (isset($tt_sem[intval(date('W',$t))])){
                        $tt_deniv = 0;?>
                    <div class = "bytype-monthgraph">
                    <?php 
                    foreach($tt_sem[intval(date('W',$t))] as $tweek){
                        $tt_deniv += $tweek['deniv'];
                        $max_hours_real = $max_hours?$max_hours:$time_sem[intval(date('W',$t))];
                        $totalRatio = $tweek['duree']/$max_hours_real;
                        $typeRatio = ($tweek['duree']/$time_sem[intval(date('W',$t))]);?>
                            <div class = "typecolumn" style = "margin: 2px 0 5px 0;width:<?=($totalRatio*100).'%'?>; height : 50px;background-color: <?=$seances[$tweek['type']]['color'];?>">
                                
                            <?php if ($typeRatio>0.05){?>
                                <img class = "icone" src= <?= $seances[$tweek['type']]['images'] ?>>
                            <?php }?>

                                <span class = "tooltipmonth" style = "background-color: <?=$seances[$tweek['type']]['color'];?>">
                                    <?= $seances[$tweek['type']]['type'] ?> : 
                                    <?=floor($tweek['duree']/3600)?>h<?=($tweek['duree']/60 -60*floor($tweek['duree']/3600))<10?"0":""?><?=($tweek['duree']/60 -60*floor($tweek['duree']/3600))?>
                                    , <?=round($tweek['distance'],1)?> km - <?=round($typeRatio*100,1)?>%
                                </span>
                            </div>
                    <?php }?>
                    </div>
                    <?php }
                    $isum = $tti[0] + $tti[1] + $tti[2] + $tti[3] + $tti[4];
                    if ($isum!=0){?>
                    <div class = "bytype-monthgraph">
                    <?php for ($i = 0; $i<5; $i++){
                        if ($tti[$i]!=0){?>
                        <div class = "typecolumn" style = "margin: 0 0 5px 0;width:<?=($tti[$i]*100/$isum).'%'?>; height: 50px; background-color: <?=$icolors[$i]?>">
                            <span class = "tooltipmonth" style = "background-color: <?=$icolors[$i]?>"><?=$itext[$i]?> (i<?=$i+1?>) : <?=$tti[$i]?>min, <?=round($tti[$i]*100/$isum,1)?>%</span>
                        </div>
                        
                    <?php }}?>
                    </div>
                    <?php }?>
                    <?php /*<p>Dénivelé : <?=$tt_deniv?>m</p>*/?>
                    <input type="hidden" id = "week-date" value = <?=date("Y-m-d",$t)?>>
                    <textarea id = "week-comment" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="Commentaire de l'athlète" style="width : 48%;height:100px;"><?=isset($comments_week[date("Y-m-d",$t)])?stripslashes($comments_week[date("Y-m-d",$t)]['content']):""?></textarea>
                    <textarea id = "coach-comment" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="Commentaire du coach" style="width : 48%;height:100px;float:right;" <?=$is_admin?"":"disabled"?>><?=isset($comments_week[date("Y-m-d",$t)])?stripslashes($comments_week[date("Y-m-d",$t)]['coach_content']):""?></textarea>
                    <button id = "save-comment" class="<?=isset($_SESSION['visit_id'])?"right":"left"?>" onclick="saveWeekComment($(this));" ><i class = "fa fa-check"></i> Enregistrer</button>
                    <?php if ($is_user){?>
                    <button class = "right" onclick="$(this).siblings('#coach-comment').prop('disabled', false);">Modifier</button>
                    <?php }?>
                    </div>
                </td>
            </tr>

            <?php
                $ttMonthKmR += $ttKmR;
                $ttMonthKmA += $ttKmA;
                $ttMonthM += $ttM;
                $ttMonthH += $ttH;
            }
            ?>

        <?php 
        $t+=86400;
        } ?>
        <tr>
            <td colspan = "8">
                <div class = "totalyear">
                    <span>Total mois :</span>
                    <span><?=floor($monthTime/3600)?>h<?=($monthTime/60 -60*floor($monthTime/3600))<10?"0":""?><?=($monthTime/60 -60*floor($monthTime/3600))?>
                </div>
                <div class = "bytype-monthgraph">
                <?php foreach($month_sums as $tmonth){
                    $typeRatio = ($tmonth['duree']/$monthTime);?>
                    <div class = "typecolumn" style = "width:<?=($typeRatio*100).'%'?>;height: 50px; background-color: <?=$seances[$tmonth['type']]['color'];?>">
                        
                    <?php if ($typeRatio>0.05){?>
                        <img class = "icone margin" src= <?= $seances[$tmonth['type']]['images'] ?>>
                    <?php }?>

                        <span class = "tooltipmonth" style = "background-color: <?=$seances[$tmonth['type']]['color'];?>">
                            <?= $seances[$tmonth['type']]['type'] ?> : 
                            <?=floor($tmonth['duree']/3600)?>h<?=($tmonth['duree']/60 -60*floor($tmonth['duree']/3600))<10?"0":""?><?=($tmonth['duree']/60 -60*floor($tmonth['duree']/3600))?>
                            , <?=round($tmonth['distance'],1)?> km - <?=round($typeRatio*100,1)?>%
                        </span>
                    </div>
                <?php }?>
                </div>
                <?php
                $tti = $ttmonth_i[0];
                $isum = $tti[0] + $tti[1] + $tti[2] + $tti[3] + $tti[4];
                if ($isum!=0){?>
                <div class = "bytype-monthgraph">
                <?php for ($i = 0; $i<5; $i++){
                    if ($tti[$i]!=0){?>
                    <div class = "typecolumn" style = "margin: 0 0 5px 0;width:<?=($tti[$i]*100/$isum).'%'?>; height: 50px; background-color: <?=$icolors[$i]?>">
                        <span class = "tooltipmonth" style = "background-color: <?=$icolors[$i]?>"><?=$itext[$i]?> (i<?=$i+1?>) : <?=$tti[$i]?>min, <?=round($tti[$i]*100/$isum,1)?>%</span>
                    </div>
                    
                <?php }}?>
                </div>
                <?php }?>
            </td>
        </tr>
    </tbody>
</table>