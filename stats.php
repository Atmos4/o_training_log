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
}else $now = strtotime(date("Y-m-d"));
$year = date('o', $now);


$month_names = ["Janvier","Février","Mars","Avril","Mai","Juin","Juillet","Août","Septembre","Octobre","Novembre","Décembre"];
$month_names_medium = ["Jan","Fév","Mar","Avr","Mai","Jun","Jul","Aoû","Sep","Oc","Nov","Déc"];
$month_names_short = ["J","F","M","A","M","J","J","A","S","O","N","D"];

$offset = -2;

if (date('n', $now)>12+$offset) $year++;


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
		<title>Statistiques</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<link rel="stylesheet" href="assets/css/template.css" />
		<link rel="stylesheet" href="assets/css/overview.css" />
		<link rel="stylesheet" href="assets/css/fontawesome.min.css">
		
		<?php include "template/favicon.php"?>
		
		<script src="assets/js/jquery.min.js"></script>
	</head>
	<body>
		<input type="hidden" id="view" value = "<?=$view?>">
		<?php include "template/menu.php"?>
		<div id="wrapper">
			<div id="bloc_page">
			    <div id = "loader"></div>
				<h1 class = "white">Statistiques</h1>
				<p id="year_bloc" class = "white"><span class = "full">Saison </span><span id = "prev"><</span><span id="yearTitle"><?=$year?></span><span id = "next">></span>
					
					<select id="choose-time" onchange="chooseTime()">
						<option value="by_month">Mois</option>
						<option value="by_week">Semaines</option>
					</select>
					<select id="choose-data" onchange="chooseData()">
						<option value="types">Types</option>
						<option value="intens">Intensités</option>
					</select>
				</p>
				<section id = "content">
				</section>
			</div>
		</div>


		<?php include("template/footer.php")?>
		
		<script src="assets/js/Chart.min.js"></script>
		<script src="assets/js/stats.js"></script>

	</body>
</html>