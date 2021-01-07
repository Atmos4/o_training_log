<?php
include("template/functions.php");


$is_admin = check_auth("COACH");
$is_user = check_auth("USER");

$runner_id = get_rid();
$user_params = get_user($runner_id)->data;

if (isset($_GET['date'])){
	$date = $_GET['date'];
}
else{
	die('Erreur : aucune date dans la requète HTTP - Recharger ');
}
if (isset($_GET['id'])){
    $sid = $_GET['id'];
}else $sid = 0;

if (isset($_GET['planning_id'])){
    $pid = $_GET['planning_id'];
}else $pid = 0;

$_SESSION['currentdate'] = $date;

setlocale(LC_ALL, 'fr_FR');
$string_date = utf8_encode(strftime('%A %e %B %Y',strtotime($date)));
$num_date = date("d/m/Y",strtotime($date));

if(isset($_POST) and count($_POST)){
	$redirect = "";
	if (isset($_POST['seance-source']) and $_POST['seance-source']=="seance-form"){
			if (isset($_POST['delete'])){
				delete_seance($_POST);
			} else {
				update_seance($_POST);
				$redirect = "&id=".$_POST['seance_id'];
			}
	}
	else if (isset($_POST['planning-source']) and $_POST['planning-source']=="planning-form-edit"){
		if ($line['structure_id']==0 or ($is_admin and $line['structure_id']==$_SESSION['structure_id'])){
			if (isset($_POST['delete'])){
				delete_planning($_POST);
			}
			else {
				update_planning($_POST);
				$redirect = "&planning_id=".$_POST['planning_id'];
			}
		}
	} 
	else if (isset($_POST['planning-source']) and $_POST['planning-source']=="planning-form-vote"){
		add_planning_vote($_POST);
		$redirect = "&planning_id=".$_POST['planning_id'];
	}
	if (isset($_POST['source']) and $_POST['source']=="add-form"){
		add_seance($_POST);
	}

	if (isset($_POST['source']) and $_POST['source']=="add-planning-form"){
		add_planning($_POST);
	}

	if (isset($_POST['day-source']) and $_POST['day-source']=="day-form"){
		update_day($_POST);
	}

	session_write_close();
	header("Location:day_view.php?date=".$date.$redirect, true);
	exit;
}

$structure_id = $user_params['structure_id'];

$seances = array();
$req_seances = get_db()->query('SELECT * FROM type_seance');
while ($donnees_seances = $req_seances->fetch()){
	array_push($seances, $donnees_seances);
}

$activites = get_seances_day($runner_id, $date)->data;

$plannings = get_plannings_day($runner_id,$structure_id,$date)->data;


?>

<!DOCTYPE HTML>
<!--
	Carnet d'entrainement FFCO - Auteur : Atmos4
	Tous droits réservés
-->
<html>
	<head>
		<title><?=$date?></title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<?php include "template/favicon.php"?>

		<link rel="stylesheet" href="assets/css/template.css" />
		<link rel="stylesheet" href="assets/css/day_view.css" />
		<link rel="stylesheet" href="assets/css/fontawesome.min.css">
		<link rel="stylesheet" href="assets/css/bootstrap-grid.min.css">
		
		<script type="text/javascript" src="assets/js/jquery.min.js"></script>
	</head>
	<body>
		<?php include("template/menu.php");?>
		<input type = "hidden" id = "strava-token" value = "<?=isset($_SESSION['strava_token'])?$_SESSION['strava_token']:""?>">
		<input type = "hidden" id = "start-day" value = "<?=strtotime($date)?>">
		<input type = "hidden" id = "end-day" value = "<?=strtotime($date)+86400?>">
		<h1 class = "white container" id = "day-title">
			<a id="backlink" href="overview.php">< Retour</a>
			<span id ="<?=date("Y-m-d",strtotime($date)-86400)?>" class="change-day"><i class = "fas fa-chevron-left"></i></span>
			<span class = "full"><?=$string_date?></span><span class="medium"><?=$num_date?></span><span class="short"><?=$num_date?></span>
			<span id ="<?=date("Y-m-d",strtotime($date)+86400)?>" class="change-day"><i class = "fas fa-chevron-right"></i></span>

			<div class = "actions-wrapper absolute dark" style="top:-22px;right:0">

				<?php if (strtotime($date)<=strtotime(date("Y-m-d"))){?>

				<a id="addbutton" class="action-button" onclick="openModal('addModal')">
					<span class="tooltip">
						<i class="fa fa-plus" style="margin-left:10px"></i>Ajouter une séance
					</span>
				</a>

				<?php }?>

				
				<a id="addplanning"  data-day = "<?=$date?>" class="action-button">
					<span class="tooltip">
						<i class="fa fa-calendar-plus" style="margin-left:10px"></i>Ajouter une planification
					</span>
				</a>
			</div>
		
		</h1>
	
		<div class="container">
		<div id="toptabs" class="row">
		<?php 
		$no_select = ($sid==0 and$pid==0);
		if (count($activites)){
			foreach ($activites as $line) {?>
			<div id = "<?= $line['seance_id']?>" class = "tab training<?=($line['seance_id']==$sid or $no_select)?" selected":""?>" style = "background-color:<?=$seances[$line['type']]['color'];?>">
				<img class = "tab-img" src= <?= $seances[$line['type']]['images'] ?>>
			</div>
		<?php if ($no_select){$no_select=false;}
			}
		}?>
			<div id="fill-div"></div>
			<div id = "loader"></div>
			<?php if (count($plannings)){?>
				<?php foreach ($plannings as $plan) {?>
			<div id = "<?= $plan['id']?>" class = "tab planning<?=($plan['id']==$pid or $no_select)?" selected":""?>" style = "background-color:<?=$seances[$plan['type']]['color'];?>">
				<img class = "tab-img" src= <?= $seances[$plan['type']]['images'] ?>>
			</div>

			<?php if ($no_select){$no_select=false;}}}?>

		</div>
		<div id = "bloc_page" class="container dark">		
			<form autocomplete="off" method="post" id="dayview-form">

				<div class = "actions-wrapper absolute" style="top:0;right:-15px">

					<?php if (isset($_SESSION['strava_token'])){?>

					<a id="sync-div" class="action-button">
						<span class="tooltip">
							<i class="fa fa-sync"></i>Synchroniser
						</span>
					</a>

					<?php }?>

					<a id="change" class="action-button" onclick="activateForm()">
						<span class="tooltip">
							<i class ="fa fa-pen"></i>Modifier
						</span>
					</a>
					<a id="submit" class="action-button" onclick="submitForm()">
						<span class="tooltip">
							<i class ="fa fa-check"></i>Enregister
						</span>
					</a>
					<a id="reset" class="action-button" onclick="location.reload()">
						<span class="tooltip">
							<i class ="fa fa-times" style="margin-left:12px"></i>Annuler
						</span>
					</a>
				</div>

			<?php if(isset($activites[0]) and is_array($activites[0])){?>
				<div id = "seance_content" class="row">
				</div>
			<?php } else {?>
				<div id="seance_content" class="row">
					<p class="col-sm-12 aln-center">
						Pas d'activités
					</p>
				</div>

			<?php }
			if (strtotime($date)<=strtotime(date("Y-m-d"))){?>

				<div id="day_content" class="row">
				</div>

			<?php }?>

			</form>
		</div>
		


		<?php include("template/footer.php")?>
		<?php include("modal/training_modal.php")?>
		<?php include("modal/planning_modal.php")?>
		
		<script type="text/javascript" src="assets/js/Chart.min.js"></script>
		<script type="text/javascript" src="assets/js/chart.util.js"></script>
		<script type="text/javascript" src = "assets/js/strava_sync.js"></script>
		<script type="text/javascript" src = "assets/js/day_view.js"></script>

	</body>
</html>