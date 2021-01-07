<?php
//Note : ce fichier est utilisé par day_view.php

require_once("../template/functions.php");


$is_admin = check_auth("COACH");
$is_user = check_auth("USER");

$_SESSION['currentmode']="training";

$runner_id= get_rid();

if (isset($_GET['id'])){
	$seance_id = $_GET['id'];
}
else{
	die('Erreur : no GET data ');
}

$user=get_user($runner_id)->data;


$req= get_db()->prepare('SELECT * 
                    FROM seances 
                    WHERE runner_id = ? and seance_id = ?
                    LIMIT 1');

$req->execute(array($runner_id,$seance_id));
$line = array();
if ($donnees = $req->fetch()){
	if (isset($donnees['type']) and $donnees['type']!=4){
		$line= $donnees;
	}
} else die('Erreur : pas de séance '.$seance_id);

$seances = array();
$req_seances = get_db()->query('SELECT * FROM type_seance');
while ($donnees_seances = $req_seances->fetch()){
	array_push($seances, $donnees_seances);
}


$time = explode(":",$line['duree']);
$txt = stripslashes($line['txt']);

$charge = (isset($line['charge']) and $line['charge']>0)?"value = ".$line['charge']:"";
$charge_value = (isset($line['charge']) and $line['charge']>0)?$line['charge']:0;

$time_in_min = $time[0]*60 + $time[1];


//---Couleurs pour le graphe des intensités
$icolors = array("#cccccc","#d4ff71","#e7e300","#e7b100","#e70000");
$itext = array("Régénération","Aérobie moyenne", "Aérobie haute", "Seuil", "PMA");

?>

<div id = "seance-form" class = "form-editable container">
    <input name="seance-source" type="hidden" value="seance-form">
    <div id="gradient-wrapper" class="row">
        <div class="col-sm-12">
            <input type="text" name = "title" id = "seance_title" class = "big-font std-title" value = "<?=$line['title']!=""?$line['title']:"Sans titre"?>" disabled>
        </div>

        <div class="col-sm-6">
            <div class="row">
                <div class="col-sm-12 bordered">
                    <select id="type_seance" name = "type_seance" class = "std-seance big-font" disabled>
                    <?php foreach ($seances as $si){?>
                        <option value = "<?=$si['id']?>" <?php if ($si['id'] == $line['type']) echo "selected"?>> <?=$si['type']?></option>
                    <?php } ?>
                    </select>
                </div>
            </div>
        </div>
        
        <div id="form_content" class="col-sm-6">
            <input name="seance_id" type="hidden" id="seance_id" value="<?= $line['seance_id'] ?>">
            <input name="strava_id" type="hidden" id="strava_id" value="<?= $line['strava_id'] ?>">
            <input name="date" type="hidden" id="datedb" value="<?= $line['date'] ?>">
            <div class = "row">
                <div class="col-sm-12 bordered">
                    <input type = "text" name = "hours" id = "seance-hours" class = "medium-font" maxlength = "3" pattern="[0-9,]+" value ="<?=$time[0]?>" disabled required/>
                    <span class = "medium-font">h</span> <input type = "text" name = "min"  id = "seance-min" class = "medium-font" maxlength = "2" pattern="[0-9]+" value="<?=$time[1]?>" disabled required/>
                </div>
            </div>
        
            <div class = "row">
                <div class="col-sm-12 bordered">
                    <input name="distance" class = "medium-font" id = "distance" type = "text" maxlength = "5" pattern="[0-9,.]+"  value="<?=$line['distance']?>" disabled/><span class = "medium-font"> km</span>
                </div>
            </div>

            <div class = "row">
                <div class="col-sm-12 bordered">
                    <input name="uphill" id = "uphill" class = "medium-font" type = "text" maxlength = "5" pattern="[0-9,.]+" value = "<?=$line['deniv']?>" disabled/><span class = "medium-font"> m+</span>
                </div>
            </div>
        </div>

        <div class="col-sm-12">
            <label for="txt" class = "medium-font">Détails : </label><br/>
                <textarea name="txt" class = "std-txt" disabled><?=$txt?></textarea>
        </div>

        <div id = "seance-actions" class = "edit-actions">
            <input type = "submit" name = "delete" id ="delete" value="Supprimer" style="display:none"/>
            <div class = "actions-wrapper relative" style="padding:10px">
                <a id="change" class="action-button" onclick="$('#delete').click()">
                    <span class="tooltip destructive">
                        <i class ="fa fa-trash" style="margin-left:10px"></i>Supprimer
                    </span>
                </a>
            </div>
        </div>
        
    </div>
    <hr/>
    <h2 class="subtitle">Charge</h2>
    
    <div class ="row justify-content-center">
        <div class = "slideform col-lg-8">
            <div class="slidecontainer">
                <span class="d-none d-sm-block">Facile</span>
                <input type="range" min="1" max="10" class="slider" id="range-seance" <?=$charge?> readonly>
                <span class="d-none d-sm-block">Difficile</span>
            </div>
        </div>
    </div>
    <div class ="row justify-content-center">
        <h3 class = "col-sm-5">Difficulté : <input type="text" name = "charge" id="value-range-seance" class = "sliderlabel medium-font" <?=$charge?> disabled><i class = "fa fa-question-circle tip advice"><span class = "tipcontent">Ressenti juste après la séance, entre 1 et 10</span></i></h3>
        <div class = "col-sm-5 load-infos align-self-center">
            <div>
                RPE<i class = "fa fa-question-circle tip advice"><span class = "tipcontent">Calculé à partir de la difficulté</span></i> : <?=get_rpe_load($time_in_min,$charge_value)?>
            </div>
            <div>
                TRIMP<i class = "fa fa-question-circle tip advice"><span class = "tipcontent">Calculé à partir des intensités</span></i> : <?=get_cad_trimp($line['zone1'],$line['zone2'],$line['zone3'],$line['zone4'],$line['zone5'])?>
            </div>
        </div>
    </div>
    
    <div class = "row" style="margin-top: 20px">
        <?php for ($in = 1; $in<=5;$in++){?>

        <div class="col-lg aln-center">
            <span class="d-lg-block">Intensité <?=$in?><i class = "fa fa-question-circle tip advice"><span class = "tipcontent"><?=$itext[$in-1]?></span></i> :</span>
            <span class="d-lg-block"><input type = "text" name = "hrzone<?=$in?>" class = "std-hours" pattern = "[0-9]+" maxlength="4" placeholder = "0" value = "<?=$line['zone'.$in]!=0?$line['zone'.$in]:""?>" disabled> min</span>
        </div>

        <?php }?>
    </div>
    <?php if ($line['strava_id']){
        $fcmax = $user['fcmax'];
        $fcr = $user['fcr'];
        $fcbis = $fcmax - $fcr;
        $z1limit = intval(round(0.6*$fcbis + $fcr));
        $z2limit = intval(round(0.7*$fcbis + $fcr));
        $z3limit = intval(round(0.8*$fcbis + $fcr));
        $z4limit = intval(round(0.9*$fcbis + $fcr));
        if (!empty($user['z1limit']) and $user['z1limit']<$z2limit and $user['z1limit']>$fcr){
            $z1limit =intval($user['z1limit']);
        }
        if (!empty($user['z2limit']) and $user['z2limit']<$z3limit){
            $z2limit =intval($user['z2limit']);
        }
        if (!empty($user['z3limit']) and $user['z3limit']<$z4limit){
            $z3limit =intval($user['z3limit']);
        }
        if (!empty($user['z4limit']) and $user['z4limit']<$fcmax){
            $z4limit =intval($user['z4limit']);
        }?>
    <hr/>
    <input type="hidden" id="fcmax" name="fcmax" value="<?=$fcmax?>"/>
    <input type="hidden" id="fcrest" name="fcrest" value="<?=$fcr?>"/>
    <input type="hidden" id="hrz1" name="hrz1" value="<?=$z1limit?>"/>
    <input type="hidden" id="hrz2" name="hrz2" value="<?=$z2limit?>"/>
    <input type="hidden" id="hrz3" name="hrz3" value="<?=$z3limit?>"/>
    <input type="hidden" id="hrz4" name="hrz4" value="<?=$z4limit?>"/>
    <div id="hr-graph">
        <canvas id="hr-canvas" style="display: block; height: 200px; width: 735px;position:relative" width="735" height="200" class="chartjs-render-monitor">
        </canvas>
    </div>

    <?php }?>
    <hr/>
</div>



    
