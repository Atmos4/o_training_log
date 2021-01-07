<?php
include "template/functions.php";

$is_admin = check_auth("COACH");
$is_user = check_auth("USER");

if (isset($_GET)){
	if ($is_admin and isset($_GET['runner'])) $_SESSION['visit_id'] = $_GET['runner'];
	else if (isset($_GET['reset'])) unset($_SESSION['visit_id']);
}

$view = isset($_SESSION['view'])?$_SESSION['view']:"";
if ($is_admin and !isset($_SESSION['visit_id'])){
	$view = "planning";
}

$currentmode=isset($_SESSION['currentmode'])?$_SESSION['currentmode']:"";
if ($currentmode=="planning") $view="planning";

//TODO
if (isset($_SESSION['currentdate'])){
	$now = strtotime($_SESSION['currentdate']);
}else{
	$now = strtotime(date("Y-m-d"));
}
$year = date('Y', $now);

if(isset($_POST) and count($_POST)){
	if (isset($_POST['source']) and $_POST['source']=="add-planning-form"){
		add_planning($_POST);
		header("Location: overview.php");
	}
	if (isset($_POST['source']) and $_POST['source']=="add-form"){
        add_seance($_POST);
        session_write_close();
        header("Location: overview.php");
        exit;
	}
}


$month_names = ["Janvier","Février","Mars","Avril","Mai","Juin","Juillet","Août","Septembre","Octobre","Novembre","Décembre"];
$month_names_medium = ["Jan","Fév","Mar","Avr","Mai","Jun","Jul","Aoû","Sep","Oc","Nov","Déc"];
$month_names_short = ["J","F","M","A","M","J","J","A","S","O","N","D"];


$data_run = get_user(get_rid());
if ($data_run->fail())exit;
else $data_run = $data_run->data;


?>

<!DOCTYPE HTML>
<!--
	Carnet d'entrainement FFCO - Auteur : Atmos4
	Tous droits réservés
-->
<html>
	<head>
		<title>Calendrier</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<link rel="stylesheet" href="assets/css/template.css" />
		<link rel="stylesheet" href="assets/css/overview.css" />
		<link rel="stylesheet" href="assets/css/planningform.css" />
		<link rel="stylesheet" href="assets/css/fontawesome.min.css">
		<link rel="stylesheet" href="assets/css/bootstrap-grid.min.css">
		
		<?php include "template/favicon.php"?>
		
		<script src="assets/js/jquery.min.js"></script>
	</head>
	<body>
		<input type="hidden" id="view" value = "<?=$view?>">
		<?php include "template/menu.php"?>
		<div id="bloc_page">
			<div id = "loader"></div>
			<h1 class = "white">
			<?php if ($is_user or isset($_SESSION['visit_id'])){?>
			<span id = "cal" class = "toggle-planning <?=$currentmode!="planning"?"toggled":""?>">Calendrier</span>
			<?php }?>
			<span id="plan" class = "toggle-planning <?=$currentmode=="planning"?"toggled":""?>">Planning</span>
			</h1>
			<p id="year_bloc" class = "white">
				<span id = "prev"><</span><span id="yearTitle"><?=$year?></span><span id = "next">></span>
				<button id="addbutton" onclick="openModal('addModal')"><i class="fas fa-plus fa-lg msides"></i><span class = "full">Ajouter une séance</span></button>
			</p>
			<ul id="month_list" class = "white">
				<?php for ($m=0;$m<12;$m++){
					if ($m<0)$m+=12;
					echo "<li id = ".($m+1);
					if (($m+1)==date('n',$now))
						echo " class='bold'";
					
					echo "><span class = 'full'>".$month_names[$m]."</span><span class = 'medium'>".$month_names_medium[$m]."</span><span class = 'short'>".$month_names_short[$m]."</span></li>";
				}
				?>
			</ul>
			<section id = "content">
			</section>
		</div>


		<?php include("template/footer.php")?>
		<?php include("modal/planning_modal.php")?>
		<?php include("modal/training_modal.php")?>

		<?php if ($data_run['onboarding'] and $is_user){
			set_onboarding(0,$data_run['id']);
			include "modal/onboarding_modal.php";
		 }?>
		
		<script type="text/javascript" src = "assets/js/strava_sync.js"></script>
        <script type="text/javascript" src = "assets/js/overview.js"></script>

	</body>
</html>