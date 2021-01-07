<?php
include "template/functions.php";

$error = "";
$redirect = "";

if (isset($_SESSION["rid"])){
	if ($_SESSION['level']=="COACH") $redirect = "self.location.href='admin.php'";
	else $redirect = "self.location.href='overview.php'";
}

else if(isset($_POST) and count($_POST)) {
	$login = (isset($_POST['login'])) ? $_POST['login'] : '';
	$pass  = (isset($_POST['pass']))  ? $_POST['pass']  : '';
	
	//Requête SQL
	$req = $db->prepare('SELECT * FROM runners WHERE login= ? AND password=SHA1(?) LIMIT 1');
	$req->execute(array($login,$pass));

	//Si l'utilisateur est trouvé dans la DB
	if ($row = $req->fetch()){
		$_SESSION['rid'] = $row['id'];
		$_SESSION['login'] = $row['login'];
		$_SESSION['level'] = $row['level'];
		$_SESSION['nom'] = $row['nom'];
		$_SESSION['prenom'] = $row['prenom'];
		$_SESSION['structure_id'] = $row['structure_id'];
		if (isset($row['strava_token']))
			$_SESSION['strava_token'] = $row['strava_token'];
		setlocale (LC_ALL, "fr_FR");
		$req_visit = $db->prepare('UPDATE runners SET lastvisit = NOW() WHERE id = ?');
		$req_visit->execute(array($row['id']));
		
		if ($_SESSION['level']=="COACH") $redirect = "self.location.href='admin.php'";
		else $redirect = "self.location.href='overview.php'";
	} else {
		$error = "Login ou mot de passe invalide ";
		$_SESSION = array();
		if (isset($_COOKIE[session_name()])) {
	    	setcookie(session_name(), '', time()-42000, '/');
		}
		session_destroy();
	}
}

?>


<!DOCTYPE HTML>
<!--
	Carnet d'entrainement FFCO - Auteur : Atmos4
	Tous droits réservés
-->
<html>
	<head>
		<title>Carnet FFCO</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<?php include "template/favicon.php"?>
		<?php ?>
		<script><?= $redirect ?></script>
		<!--[if lte IE 8]><script src="assets/js/html5shiv.js"></script><![endif]-->
		<link rel="stylesheet" href="assets/css/main.css" />
		<!--[if lte IE 9]><link rel="stylesheet" href="assets/css/ie9.css" /><![endif]-->
		<!--[if lte IE 8]><link rel="stylesheet" href="assets/css/ie8.css" /><![endif]-->
		<noscript><link rel="stylesheet" href="assets/css/noscript.css" /></noscript>
	</head>
	<body class="is-loading">

		<!-- Wrapper -->
			<div id="wrapper">

				<!-- Main -->
					<section id="main">
						<header>
						    <div id = "logos">
						        <img id = "ffco-img" src="images/avatar.jpg" alt="">
						        <img id = "ara-img" src="images/logo-ara.png" alt="">
						    </div>
							
							<h1>Carnet d'entrainement</h1>
							
						</header>
						<hr />
						<form method="post" action="">
							<div class="field">
								<input type="text" name="login" id="name" placeholder="Identifiant" />
							</div>
							<div class="field">
								<input type="password" name="pass" id="pass" placeholder="Mot de passe" />
							</div>
							<ul class="actions">
								<li><input type="submit" name="Submit" value="Valider" class="button"></li>
							</ul>
						</form>
						<a href = "update/update.html">Notes de mise à jour</a>
					</section>

				<!-- Footer -->
					<footer id="footer">
						<ul class="copyright">
							<li>&copy; FFCO</li><li><a href="http://html5up.net">Crédits</a></li>
						</ul>
					</footer>

			</div>

		<!-- Scripts -->
			<!--[if lte IE 8]><script src="assets/js/respond.min.js"></script><![endif]-->
			<script>
				if ('addEventListener' in window) {
					window.addEventListener('load', function() { document.body.className = document.body.className.replace(/\bis-loading\b/, ''); });
					document.body.className += (navigator.userAgent.match(/(MSIE|rv:11\.0)/) ? ' is-ie' : '');
				}
			</script>

	</body>
</html>