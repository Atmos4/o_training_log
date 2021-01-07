<?php
require_once("../template/functions.php");

$is_admin = check_auth("COACH");
$is_user = check_auth("USER");

$_SESSION['currentmode']="planning";

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

//---Type de séances
$seances = array();
$req_seances = get_db()->query('SELECT * FROM type_seance');
while ($donnees_seances = $req_seances->fetch()){
	$seances[$donnees_seances['id']]=$donnees_seances;
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
    $t= $timestamp_start;
    while ($t <= $timestamp_end) {
    
    $day = date('Y-m-d', $t);
    $num_day = date('j', $t);
    $current_month = date('n', $t);
    if (date('N',$t)==1){
        echo "<tr>";
        $pttH = 0;
        $pttM = 0;
    }
    ?>

            <td id = "<?=$day?>" class = "calcell <?=(($current_month!=$month)?"outofmonth":"")?> <?=((!isset($details[$day])) and ($t<strtotime(date("Y-m-d"))+86400))?"link":""?>">

                <span class = "date" <?php if ($day == date("Y-m-d")) echo "style='color:rgb(255, 94, 94);font-weight:bold;'";?>><?= $num_day?></span>

                <div class = "small_container">
                    <span class="rest">
                    <?php if (isset($data_days[$day])){
                            if ($data_days[$day]['state']!=0){
                                echo $states[$data_days[$day]['state']]['state_name'];
                            }
                            else echo "Repos";
                        }?>
                    </span>
                    <?php if (isset($plannings[$day])){
                        $totalDayM = 0;
                        $totalDayH = 0;
                        foreach($plannings[$day] as $plan){
                                $hms = explode(':',$plan['duree']);
                                
                                if ($plan['structure_id']==0 or (isset($plan['vote']) and $plan['vote']) or ($is_admin and !$visit)){
                                $totalDayH+=$hms[0];
                                $totalDayM+=$hms[1];
                                $pttH+=$hms[0];
                                $pttM+=$hms[1];
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
            
            </td>
            <?php if(date('N',$t)==7){
                ?>

            <td class="endcell">
                <span class="total"><?= $pttH + floor($pttM/60)?>h<?= $pttM - 60*floor($pttM/60)<10?"0":""?><?= $pttM - 60*floor($pttM/60)?></span>
            </td>
            
        </tr>
            <?php }
            ?>

        <?php 
        $t+=86400;
        } ?>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </tbody>
</table>