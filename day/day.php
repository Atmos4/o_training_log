<?php
require_once("../template/functions.php");


$is_admin = check_auth("COACH");
$is_user = check_auth("USER");

if ($is_admin and isset($_SESSION['visit_id'])){
    $runner_id = $_SESSION['visit_id'];
}else $runner_id = $_SESSION['rid'];

if (isset($_GET['date'])){
	$date= $_GET['date'];
}

$req_day=get_db()->prepare('SELECT days.* 
FROM days
WHERE runner_id = ? AND DATE = ?
LIMIT 1');

$req_day->execute(array($runner_id,$date));
$data_day = array();
$hasDay = true;
if ($donnees_day = $req_day->fetch()){
    $data_day = $donnees_day;
} else $hasDay = false;

$states = array();
$req_state = get_db()->query('SELECT * FROM state_day');
while ($donnees_state = $req_state->fetch()){
	array_push($states, $donnees_state);
}

$fatigue =  (isset($data_day['fatigue']) and $data_day['fatigue']>0)?"value = ".$data_day['fatigue']:"";
?>

<div id = "day-form" class="form-editable container">
    <input name="day-source" type="hidden" value="day-form">
    <input name="date" type="hidden" id="datedb" value="<?= $date?>">
    <h2 class="subtitle">Journée</h2>
    <div class = "row">
        <div class = "col-sm-6">
            Etat : 
            <select name = "state" id = "state" class = "small-font" disabled>
            <?php foreach ($states as $sta){?>
                <option value = "<?=$sta['state_id']?>" <?php if (isset($data_day['state']) and ($sta['state_id'] == $data_day['state'])) echo "selected"?>> <?=$sta['state_name']?></option>
            <?php } ?>
            </select>
        </div>
        <?php 
        $resthours = "";
        $restmin = "";
        if (isset($data_day['rest'])){
            if ($data_day['rest'] != "00:00:00"){
                $rest = explode(':',$data_day['rest']);
                $resthours = $rest[0];
                $restmin = $rest[1];
            }
        }?>
        
        <div class="col-sm-6 medium-font">
            Fatigue : <input type = "text" name="fatigue" <?=$fatigue?> id="value-range-day" class = "sliderlabel" readonly>
        </div>
    </div>
    <div class = "row justify-content-center">
        <div class = "slideform col-lg-8">
            <div class="slidecontainer">
                <span class="d-none d-sm-block">Reposé</span>
                <input type="range" min="1" max="10" class="slider" id="range-day" <?=$fatigue?> readonly>
                <span class="d-none d-sm-block">Epuisé</span>
            </div>
        </div>
    </div>
    <div class = "row">
        <div class= "col-sm-12">
            Sommeil : <input type="text" size="2" maxlength="2" style = "text-align:right" pattern="[0-9]+" name="resth" class="rest" placeholder="0" value="<?=$resthours?>" disabled>h
            <input type="text" size="3" maxlength="2" pattern="[0-5][0-9]" name="restmin" class="rest" placeholder="00" value="<?=$restmin?>" disabled>
        </div>
        <p style="margin-left:20px;">Commentaire du jour : <p>
        <div class="col-12">
            <textarea class = "std-txt" name="commentaire" id = "commentaire" disabled><?=isset($data_day['details'])?stripslashes($data_day['details']):""?></textarea>
        </div>
    </div>
</div>
