<?php
include("template/functions.php");

$req_reports = get_db()->query('SELECT bug_report.*,runners.prenom as prenom,runners.nom as nom 
								FROM bug_report LEFT JOIN runners on runners.id = bug_report.runner_id 
								ORDER BY date DESC');
$reports = array();
while ($rep = $req_reports->fetch()){
    array_push($reports, $rep);
}


if (isset($_POST) and count($_POST)){
	if (isset($_POST['onboard-all'])){
		set_onboarding(1);
		add_flash(Response::Message("Texte mis à jour"),"control");
	}
	elseif (isset($_POST['delete-report'])){
		delete_report($_POST['report-id']);
	}
	elseif (isset($_POST['save'])) {
		report_reply($_POST['report-id'],$_POST['reply']);
		add_flash(Response::Message("Réponse enregistrée"),"reply");
	}
	redirect("dev.php");
}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Développement</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="assets/css/template.css"/>
		<link rel="stylesheet" href="assets/css/fontawesome.min.css">
		<link rel="stylesheet" href="assets/css/bootstrap-grid.min.css">
		
		<?php include "template/favicon.php"?>
	</head>

	<body>
		<?php include("template/menu.php")?>
		<h1 class="white">Développement</h1>

		<div class="container dark">
			<form method="post" style="padding:10px">Afficher le texte de mise à jour pour tout le monde : 
				<input type="submit" name="onboard-all" value="Afficher"/>
				<?=display_flash("control")?>
			</form>
			
			<h2>Liste des bugs</h2>

			<?php foreach($reports as $rep){?>

			<div class="row" style="padding:10px;">
				<div class="col-md-3"><?=$rep['prenom']?><?=$rep['nom']?></div>
				<div class="col-md-2"><?=$rep['date']?></div>
				<div class="col-md-7" style="white-space:pre-wrap;"><?=stripslashes($rep['problem'])?></div>
				<div class="col-md-12">
					<form method="post">
						<input type="hidden" name="report-id" value="<?=$rep['id']?>"/>
						<div class="row" style="border-top:1px solid grey;padding:10px;">
							<div class="col-md-8">
								<textarea name="reply" style="width:100%; height:100px;" placeholder="Réponse"><?=$rep['reply']?></textarea>
							</div>
							<div class="col-md-4">
								<input type="submit" name="save" value ="Répondre"/>
								<input type="submit" name="delete-report" value="Supprimer"/>
								<?=display_flash("reply")?>
							</div>
						</div>
					</form>
				</div>
			</div>

			<?php }?>

		</div>
		
		<?php include("template/footer.php")?>

	</body>
</html>