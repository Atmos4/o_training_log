<?php
require_once("../template/functions.php");


$is_admin = check_auth("COACH");
$is_user = check_auth("USER");

$_SESSION['currentmode']="planning";

$runner_id = get_rid();

if (isset($_GET['id'])){
	$planning_id = $_GET['id'];
}
else{
	die('Erreur : no GET data ');
}

$req= get_db()->prepare('SELECT * 
                    FROM planning
                    WHERE id = ?
                    LIMIT 1');

$req->execute(array($planning_id));
$line = array();
if ($donnees = $req->fetch()){
    $line= $donnees;
} else die('Erreur : pas de planning '.$planning_id);

$seances = array();
$req_seances = get_db()->query('SELECT * FROM type_seance');
while ($donnees_seances = $req_seances->fetch()){
	array_push($seances, $donnees_seances);
}

$votes = get_votes($planning_id)->data;
$my_vote = get_votes($planning_id,$runner_id);

?>

<div id = "planning-form" class="container<?=($line['structure_id']==0 or $is_admin)?" form-editable":""?>">
    <input name="planning-source" type="hidden" value="planning-form-<?=($line['structure_id']==0 or $is_admin)?"edit":"vote"?>">
    <div id="gradient-wrapper" class="row">

        <div class="col-sm-12">
            <input type="text" name = "title" id = "seance_title" class = "big-font std-title" maxlength = "30" value = "<?=$line['titre']!=""?$line['titre']:"Sans titre"?>" disabled>
        </div>

        <div class="col-sm-6">
            <div class="row">  
                <div class="col-sm-12 bordered">
                    <select name = "type_seance" id = "type_seance" class = "big-font std-seance" disabled>
                    <?php foreach ($seances as $si){?>
                        <option value = "<?=$si['id']?>" <?php if ($si['id'] == $line['type']) echo "selected"?>> <?=$si['type']?></option>
                    <?php } ?>
                    </select>
                </div>
                
                <div class="col-sm-12 bordered">          
                    <span class="medium-font" style="font-weight:600">
                    <?php if($line['structure_id']==0){?>

                        <i class = "fa fa-user" style="margin-right : 10px;"></i>Individuel
                    
                    <?php }else{?>
                        <i class = "fa fa-users" style="margin-right : 10px;"></i>Groupe
                    <?php }?>

                    </span>
                </div>
            </div>
        </div>
        

        <div id="form_content" class="col-sm-6 bordered">
            <?php 
            $time = explode(":",$line['duree']);
            $txt = stripslashes($line['details']);?>
            <input name="planning_id" type="hidden" id="planning_id" value="<?= $line['id'] ?>">
            <input name="date" type="hidden" id="datedb" value="<?= $line['date'] ?>">
            <input type = "text" name = "hours" id = "seance-hours" class = "medium-font" maxlength = "3" pattern="[0-9,]+" value ="<?=$time[0]?>" disabled required/>
            <span class = "medium-font">h</span> <input type = "text" name = "min"  id = "seance-min" class = "medium-font" maxlength = "2" pattern="[0-9]+" value="<?=$time[1]?>" disabled required/>
               

            <?php /*<span class = "field">
                <label for = "pload" class = "medium-font">Charge prévue : </label>
                <input name="pload" id = "pload" class = "medium-font" type = "text" maxlength = "5" pattern="[0-9,.]+" value = "<?=$line['load']?>" disabled/><span class = "medium-font"> TRIMP</span>
            </span> */?>
        </div>
        <div class = "col-sm-12">
            <p>Détails :</p>
            <textarea name="txt" class="std-txt" disabled><?=$txt?></textarea>
        </div>

        <?php if ($line['structure_id']==0 or $is_admin){?>

        <div id = "plan-actions" class = "edit-actions">
            <input type = "submit" name = "delete" id ="delete" value="Supprimer" style="display:none"/>
            <div class = "actions-wrapper relative" style="padding:10px">
                <a id="change" class="action-button" onclick="$('#delete').click()">
                    <span class="tooltip destructive">
                        <i class ="fa fa-trash" style="margin-left:10px"></i>Supprimer
                    </span>
                </a>
            </div>
        </div>

        <?php } elseif ($is_user) {?>
        
        <div id = "plan-actions" class="edit-actions col-12">
            <?php 
            $yvote = true;
            $nvote = true;
            if (!$my_vote->fail()) {
                if ($my_vote->data['vote']){?>
                    <span class="info-box" style="color:green"><i class="fa fa-check"></i>J'y vais</span>
                <?php $yvote = false;
                }
                else{?>
                    <span class="info-box" style="color:red"><i class="fa fa-times"></i>Je n'y vais pas</span>
                <?php $nvote = false;
                }
            }
            else{?>
                <span class="info-box" style="color:black"><i class="fa fa-question"></i>Pas encore voté</span>
            <?php }
            if ($yvote){?>
            <input type="submit" name="vote-yes" value="Participer">
            <?php }
            if ($nvote){?>
            <input type="submit" name="vote-no" value="Ne pas participer">
            <?php }?>
        </div>

        <?php }?>
            
    </div>
    <div class="row">

        <?php if (count($votes) > 0){?>
        
        <div class = "collapsible tip-container col-12">
            <span>Liste des inscrits</span>
            <span class = "sign">+</span>
        </div>
        <div class = "collcontent col-12" id = "trcollapse">
            <ul id="vote-list">
                
            <?php
            foreach ($votes as $v){?>
                <li><i class="fa <?=$v['vote']?"fa-check":"fa-times"?>" style="color:<?=$v['vote']?"green":"red"?>"></i><?=$v['nom']?> <?=$v['prenom']?></li>
            <?php }?>
                
            </ul>

        </div>
        
        <?php }?>

    </div>
    <hr/>
</div>