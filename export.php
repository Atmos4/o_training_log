<?php
require_once("template/functions.php");

$is_user =check_auth("USER");
$is_admin = check_auth("COACH");


if ($is_admin and isset($_SESSION['visit_id'])){
    $runner_id = $_SESSION['visit_id'];
}else{
    $runner_id = $_SESSION['rid'];
}

$xls_err=$db_err="";

$runners = query_db("SELECT * FROM runners WHERE level='USER' ORDER BY nom")->fetchAll();

if (isset($_POST) and count($_POST)){
    if (isset($_POST['db-export'])){
        if ($is_admin){
            header("Location: export/export_db.php");
        }
        else{
            $db_err ="Permissions insuffisantes. Si vous souhaitez obtenir la base de données, contactez un administrateur";
        }
    }
    else if (isset($_POST['xls-export'])) {
        if (empty($_POST['date-start']) or empty($_POST['date-end'])){
            $xls_err="Il manque une date";
        }elseif ($is_admin and empty($_POST['runner'])){
            $xls_err="Pas de coureur sélectionné";
        }
        else {
            $date_s = $_POST['date-start'];
            $date_e = $_POST['date-end'];
            $runner = $is_admin?$_POST['runner']:$_SESSION['rid'];
            $ts = strtotime($date_s);
            $te = strtotime($date_e);
            if ($ts < $te){
                header("Location: export/export_xls.php?start=".$ts."&end=".$te."&id=".$runner);
            }
            else{
                $xls_err = "La date de fin est inférieure à la date de début";
            }
        }
    }
}

?>

<html>
	<head>
		<title>Exporter</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<link rel="stylesheet" href="assets/css/template.css" />
		<link rel="stylesheet" href="assets/css/fontawesome.min.css">
		<link rel="stylesheet" href="assets/css/bootstrap-grid.min.css">

        <?php include "template/favicon.php"?>
		
		<script src="assets/js/jquery.min.js"></script>
	</head>

    <body>
        <?php include "template/menu.php"?>


        <h1>Exporter</h1>
        <div class="container dark">

            <div class="row">
                <div class="<?=$is_admin?"col-sm-6":"col-12"?>">
                    <h2>Exporter vers Excel</h2>
                    <form method="post" action="">

                        <?php if ($is_admin){?>
                        
                        <label for="runner">Coureur : </label>
                        <select name="runner">

                        <?php foreach($runners as $r){?>

                            <option value="<?=$r['id']?>" <?=$r['id']==$runner_id?"selected":""?>><?=$r['nom']?> <?=$r['prenom']?></option>

                        <?php }?>

                        </select>
                        <br/>

                        <?php }?>

                        <label for="date-start">Date de début : </label>
                        <input type = "date" name = "date-start" id = "date" required>
                        <br/>
                        <label for="date-start">Date de fin : </label>
                        <input type = "date" name = "date-end" id = "date" required>
                        <p>
                            <i class="fa fa-lightbulb"></i> <strong>Conseil</strong> : lors de l'import dans Excel, sélectionner 
                            le jeu de caractère <strong>UTF-8</strong> pour avoir le bon encodage de caractère
                        </p>
                        <span class="error"><?=$xls_err?></span>
                        <br/>
                        <input type="submit" name="xls-export" value="Exporter">
                    </form>
                </div>
                <?php if ($is_admin){?>
                <div class="col-sm-6">
                    <h2>Base de données</h2>
                    <p>
                        <i class="fa fa-exclamation-triangle"></i> Pour les développeurs. 
                        La base de données n'est pas anonymisée, donc ne pas la diffuser.
                    </p>
                    
                    <form method="post" action="">
                        <span class="error"><?=$db_err?></span>
                        <br/>
                        <input type="submit" name="db-export" value="Exporter">
                    </form>
                        
    
                </div>
                <?php }?>
            </div>

        </div>

		<?php include "template/footer.php"?>
    </body>