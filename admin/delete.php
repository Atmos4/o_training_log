<?php
require_once("../template/functions.php");

if (!check_auth("COACH"))die("Page non autorisée");
$is_admin = true;

if (!isset($_GET))die ("Format invalide");
else{
    if (isset($_GET['id'])){
        $id = $_GET['id'];
    }
}
if(isset($_POST) and count($_POST)){
    if (isset($_POST['confirm'])){
        delete_account($id);
    }
    header('Location:../admin.php');
}
?>
<html>
	<head>
		<title>Admin</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="../assets/css/template.css"/>
    </head>
    
    <body>
        <h1>Confirmation de suppression</h1>
        <div id = "wrapper">
            <form method = "post">
                <p style="text-align:center">Supprimer le compte ?<input type = "checkbox" name = "confirm" value = "1"><br/>
                <input type = "submit" class = "button" value = "Valider"></p>
            </form>
        </div>
    </body>
</html>
                