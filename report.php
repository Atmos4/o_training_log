<?php
include("template/functions.php");

$is_user =check_auth("USER");
$is_admin = check_auth("COACH");

if (isset($_POST) and count($_POST)){
	if (isset($_POST['delete-report'])){
		delete_report($_POST['report-id']);
	}
	else{
		report($_POST);
		add_flash(Response::Message("Commentaire envoyé"),"bug");
	}
	redirect("report.php");

}

$mybugs = query_db('SELECT * FROM bug_report WHERE runner_id=?',array($_SESSION['rid']))->fetchAll();
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Signaler un problème</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="assets/css/template.css"/>
		<link rel="stylesheet" href="assets/css/fontawesome.min.css">
		<link rel="stylesheet" href="assets/css/bootstrap-grid.min.css">
		
		<?php include "template/favicon.php"?>
	</head>

	<body>
		
		<?php include("template/menu.php")?>
		<h1 class="white">Signaler un problème</h1>

		<div class="container dark">
			
    		<form method = "post" action="" style="padding-top : 10px;">
    			<p>Décrivez ci-dessous le ou les problèmes rencontrés :</p>
    			<textarea name = "report" style="width:100%; height:100px;"></textarea>
				<input type = "submit" value = "Envoyer">
				<?=display_flash("bug")?>
			</form>

		
		<?php if(count($mybugs)){?>
			<h2 class="aln-center">Mes messages</h2>
                <?php foreach($mybugs as $rep){?>
                <div class="row" style="padding:5px;">
					<div class="col-md-2 aln-center"><?=$rep['date']?></div>
					<div class="col-md-8" style="white-space:pre-wrap;"><?=stripslashes($rep['problem'])?></div>
					<div class="col-md-2 aln-center">
						<form method="post">
							<input type="hidden" name="report-id" value="<?=$rep['id']?>"/>
							<input type="submit" name="delete-report" value="Supprimer"/>
						</form>
					</div>
                </div>


				<div class="row" style="border-top:1px solid grey;padding:5px;margin-bottom:10px;">

				<?php if (isset($rep['reply']) and $rep['reply']!=""){?>

					<div class="col-md-2" style="text-align:right">Réponse : </div>
					<div class="col-md-10" style="white-space:pre-wrap;"><?=$rep['reply']?></div>

				<?php } else {?>

					<div class="col-md-12">Pas de réponse pour l'instant</div>

				<?php }?>
				</div>
                <?php }?>
			
			<?php }?>
		



		</div>
		
		<?php include("template/footer.php")?>

	</body>
</html>