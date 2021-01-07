<?php
include("template/functions.php");

$is_user =check_auth("USER");
$is_admin = check_auth("COACH");

$res = "";

$visit =false;

if(isset($_GET['id']) and $is_admin){
	$rid = $_GET['id'];
	$visit = true;
}
else{
    $rid = $_SESSION['rid'];
}


$runner_dat = get_user($rid);
if ($runner_dat->fail()){
	die('Utilisateur non trouvé');
}
else {
	$runner = $runner_dat->data;
}

if ($runner['level'] == 'COACH' and $visit){
	die('Impossible de modifier la page d\'un admin');
}

if ($visit and $is_user){
	die('Page non autorisée');
}

//POST handling
if(isset($_POST) and isset($_POST['action'])) {
	switch($_POST['action']) {
		case "infos":
			$fc_err = false;
			change_infos($rid,$_POST['nom'],$_POST['prenom'],$_POST['sexe']);
			if ($is_user or $visit){
				$z1 = !empty($_POST['hrz1'])?$_POST['hrz1']:0;
				$z2 = !empty($_POST['hrz2'])?$_POST['hrz2']:0;
				$z3 = !empty($_POST['hrz3'])?$_POST['hrz3']:0;
				$z4 = !empty($_POST['hrz4'])?$_POST['hrz4']:0;
				change_fc($rid,$_POST['fcm'], $_POST['fcr'], $z1, $z2, $z3, $z4);
			}
			add_flash(Response::Message("Profil sauvegardé"), "infos");
			break;
		case "reset-passwd":
			if ($visit and $is_admin){
				$res = reset_password($rid);
				add_flash($res,"password");
			}
			break;
		case "passwd" :
			$res = change_pwd($_POST['oldpasswd'], $_POST['passwd'], $_POST['passwd2']);
			add_flash($res,"password");
			break;
		case "login" :
			$res = change_login($rid,$_SESSION['login'], $_POST['login']);
			add_flash($res,"login");
			break;
		case "structure":
			add_flash(change_structure($rid,$_POST['structure']),"structure");
			break;
		case "strava-disconnect":
			$res = strava_disconnect($rid);
			add_flash($res,"strava");
			break;

	};
	header('Location:account.php'.($visit?'?id='.$rid:''));
	exit;
}

$structures = get_all_structures()->data;

$has_structure = $runner['structure_id']!=0;

?>

<!DOCTYPE HTML>
<html>
	<head>
		<title>Profil - <?=$runner['prenom']?></title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="assets/css/template.css"/>
		<link rel="stylesheet" href="assets/css/account.css" />
		<link rel="stylesheet" href="assets/css/fontawesome.min.css"/>
		<?php include "template/favicon.php"?>
	</head>

	<body>
		
		<div id="wrapper">
			<?php include("template/menu.php")?>
		
			<h1 class="white">Profil - <?=$runner['prenom'].' '.$runner['nom']?></h1>
			<div id="bloc_page">
				<form method="post" action="">
				
				<?=display_flash("infos")?>
				<?=display_flash("password")?>
				<?=display_flash("login")?>
				<?=display_flash("structure")?>
				<?=display_flash("strava")?>

					<input name="action" type="hidden" value="infos" />
			        <div class = "bloc_section">
                        <section>
							<h2>Informations</h2>
							<hr/>
							<p class = "row">
								<label for="nom">Nom : </label>
								<input type="text" name="nom" id="nom" value = "<?=$runner['nom']?>" required/>
							</p>
							<p class = "row">
								<label for="prenom">Prénom :</label>
								<input type="text" name="prenom" id="prenom" value = "<?=$runner['prenom']?>" required/>
                            </p>
							
							<?php $man = $runner['sexe']=='H';?>
							<p class = "row">
                                <label for="fcr">Sexe :</label>
                                <select name="sexe">
                                    <option value="H" <?=$man?"selected":""?>>Masculin</option>
                                    <option value="F" <?=$man?"":"selected"?>>Féminin</option>
                                </select>
							</p>
						</section>
						
						<?php if ($is_user or $visit){
							$fcmax = $runner['fcmax'];
							$fcr = $runner['fcr'];
							$fcmbis = $fcmax-$fcr;
							
							$z1limit = intval(round(0.6*$fcmbis + $fcr));
							$z2limit = intval(round(0.7*$fcmbis + $fcr));
							$z3limit = intval(round(0.8*$fcmbis + $fcr));
							$z4limit = intval(round(0.9*$fcmbis + $fcr));
							$customz1 = $z1limit;
							$customz2 = $z2limit;
							$customz3 = $z3limit;
							$customz4 = $z4limit;
							if (isset($runner['z1limit']) and $runner['z1limit']!=0){
								$customz1 =intval($runner['z1limit']);
							}
							if (isset($runner['z2limit']) and $runner['z2limit']!=0){
								$customz2 =intval($runner['z2limit']);
							}
							if (isset($runner['z3limit']) and $runner['z3limit']!=0){
								$customz3 =intval($runner['z3limit']);
							}
							if (isset($runner['z4limit']) and $runner['z4limit']!=0){
								$customz4 =intval($runner['z4limit']);
							}
							?>
                        <section>
							<h2>Métabolisme</h2>
							<hr/>
							<p class = "row">
								<label for="fcm">FC max :</label>
								<input type="text" pattern="[0-9]+" maxlength="3" name="fcm" class="hr-input" size = "3" value = "<?=$runner['fcmax']?>" required/> bpm
							</p>
							<p class = "row">
								<label for="fcr">FC repos :</label>
								<input type="text" pattern="[0-9]+" maxlength="3" name="fcr" class="hr-input" size = "3" value = "<?=$runner['fcr']?>" required/> bpm
							</p>
							<h3>Limites de zone : 
								<i class = "fa fa-question-circle tip advice">
									<span class = "tipcontent">Pour laisser le calcul par défaut, mettre toutes les valeurs à 0</span>
								</i>
								<?php if ($customz1 > $customz2 or $customz2 > $customz3 or $customz3 > $customz4 or $customz4 > $fcmax){?>
								<i class = "fa fa-exclamation-triangle tip advice">
									<div class = "tipcontent">Valeurs incohérentes</div>
								</i>
								<?php }?>
							</h3>
							<p class = "row">
								<label for="fcr">i1 - i2 :
									<i class = "fa fa-question-circle tip advice">
										<span class = "tipcontent">60% x (FCmax - FCrepos) + FCrepos par défaut</span>
									</i>
								</label>
								<input type="text" pattern="[0-9]+" maxlength="3" name="hrz1" class="hr-input" size = "3" value = "<?=$customz1!=$z1limit?$customz1:""?>" placeholder="<?=$z1limit?>"/> bpm
							</p>
							<p class = "row">
								<label for="fcr">i2 - i3 :
									<i class = "fa fa-question-circle tip advice">
										<span class = "tipcontent">70% x (FCmax - FCrepos) + FCrepos par défaut</span>
									</i>
								</label>
								<input type="text" pattern="[0-9]+" maxlength="3" name="hrz2" class="hr-input" size = "3" value = "<?=$customz2!=$z2limit?$customz2:""?>" placeholder="<?=$z2limit?>"/> bpm
							</p>
							<p class = "row">
								<label for="fcr">i3 - i4 :
									<i class = "fa fa-question-circle tip advice">
										<span class = "tipcontent">80% x (FCmax - FCrepos) + FCrepos par défaut</span>
									</i>
								</label>
								<input type="text" pattern="[0-9]+" maxlength="3" name="hrz3" class="hr-input" size = "3" value = "<?=$customz3!=$z3limit?$customz3:""?>" placeholder="<?=$z3limit?>"/> bpm
							</p>
							<p class = "row">
								<label for="fcr">i4 - i5 :
									<i class = "fa fa-question-circle tip advice">
										<span class = "tipcontent">90% x (FCmax - FCrepos) + FCrepos par défaut</span>
									</i>
								</label>
								<input type="text" pattern="[0-9]+" maxlength="3" name="hrz4" class="hr-input" size = "3" value = "<?=$customz4!=$z4limit?$customz4:""?>" placeholder="<?=$z4limit?>"/> bpm
							</p>
						</section>
						<?php }?>
					<input type="submit" name="Submit" style="margin:auto" value="Enregistrer" class="button">
			        </div>
				</form>

			    <div class = "bloc_section">
					<input type = "hidden" id = "strava-code" value = "<?=isset($_GET['code'])?$_GET['code']:""?>">		

					<section>
						<?php if ($visit){?>

						<form method="post" action="">
							<input name="action" type="hidden" value="reset-passwd" />
							<h2>Mot de passe</h2>
							<input type="submit" name="Submit" value="Réinitialiser" class="button destructive">
							<p>Le mot de passe sera alors similaire à l'identifiant</p>
							
						</form>
						
						<?php }else{?>

						<form method="post" action="">
							<input name="action" type="hidden" value="passwd" />
							<h2>Modifier le mot de passe</h2>
							<hr/>
							<p class = "row">Ancien mot de passe : <input type="password" name="oldpasswd" id="oldpass"  /></p>
							<p class = "row">Nouveau mot de passe : <input type="password" name="passwd" id="pass"/></p>
							<p class = "row">Confirmer : <input type="password" name="passwd2" id="pass2" /></p>
							<input type="submit" value="Valider">
						</form>

						<?php }?>

					</section>

					<section>
						<form method="post" action="">
							<input name="action" type="hidden" value="login" />
							<h2>Modifier l'identifiant</h2>
							<hr/>
							<p>Identifiant actuel : <strong><?=$runner['login']?></strong></p>
							<p class = "row">Nouvel identifiant : <input type="text" name="login" id="name" /></p>
							<input type="submit" name="Submit" value="Valider" class="button">
						</form>
					</section>

					<section>
						<form method="post" action="">
							<input name="action" type="hidden" value="structure" />

							<h2>Structure</h2>
							<hr/>
							<?php if ($is_admin){?>
							<select name="structure">
								<option value="0">Pas de structure</option>

								<?php foreach ($structures as $structure){?>

								<option value="<?=$structure['id']?>" <?=$structure['id']==$runner['structure_id']?"selected":""?>><?=$structure['nom']?></option>

								<?php }?>

							</select>
							<br/>
							<input type="submit" name="Submit" value="Enregistrer" class="button">
						
						</form>



						<?php }else{?>

						<p><?=$has_structure?$structures[$runner['structure_id']-1]['nom']:"Pas de structure"?></p>
						
						<?php }?>
			         </section>
					
					<section>
						<form method="post" action="">
							<h2>Strava</h2>
							<hr/>
								<?php if (isset($runner['strava_token'])){?>
									
								<input name="action" type="hidden" value="strava-disconnect" />
								<p>Déjà lié à un compte Strava</p>
								<input type="submit" name="disconnect" value="Dissocier" class="button destructive">

								<?php }else{?>
									<p>Pas encore lié à un compte Strava</p>
									<?php if ($is_user){?>
									<a id = "strava_button" href="https://www.strava.com/oauth/authorize?client_id=26669&response_type=code&redirect_uri=http://www.carnet-ffco.fr/strava/save_token.php&approval_prompt=force&scope=read_all,activity:read_all">
										<span class = "txt">Se connecter avec </span><img id = "strava_logo" src = "images/strava.png">
									</a>
									<?php }?>
									
								<?php }?>
							</p>
						</form>
					</section>
				</div>
			</div>

		</div>
		
		<?php include("template/footer.php")?>

		<script src="assets/js/jquery.min.js"></script>
	</body>
</html>