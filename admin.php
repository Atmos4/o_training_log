<?php
require_once("template/functions.php");

if (!check_auth("COACH"))die("Page non autorisée");
$is_admin = true;

unset($_SESSION['visit_id']);
unset($_SESSION['view']);

$user = get_user(get_rid());
if ($user->fail())exit;
else{
    $data_user = $user->data;
}

if(isset($_POST) and count($_POST)){
	if (isset($_POST['transfers']) and is_array($_POST['transfers'])){
        transfer($_POST);
    }
    if (isset($_POST['source'])){
        if ($_POST['source']=="add-account"){
            add_account($_POST);
        }
    }
    session_write_close();
    header('Location:admin.php');
}

$structure_id = $_SESSION['structure_id'];


$binded_users = array();
if ($_SESSION['rid']==1){
    $req_binded = get_db()->query('SELECT * FROM runners ORDER BY lastvisit DESC');
}else{
    $req_binded = get_db()->prepare('SELECT * FROM runners WHERE structure_id = ? ORDER BY lastvisit DESC');
    $req_binded->execute(array($_SESSION['structure_id']));
}
while($data = $req_binded->fetch()){
    array_push($binded_users, $data);
}

$free_users = array();
if ($structure_id != 0){
    $req_free = get_db()->prepare('SELECT * FROM runners WHERE level="USER" AND structure_id <>? ORDER BY lastvisit DESC');
    $req_free->execute(array($_SESSION['structure_id']));
}else{
     $req_free = get_db()->query('SELECT * FROM runners WHERE level="USER" ORDER BY lastvisit DESC');
}
while($data = $req_free->fetch()){
    array_push($free_users, $data);
}

$coaches = array();
$req_coaches = get_db()->query('SELECT * FROM runners WHERE level= "COACH" ORDER BY nom');
while($coachdata = $req_coaches->fetch()){
    array_push($coaches, $coachdata);
}

$structures = array();
$req_struct = get_db()->query('SELECT * FROM structure');
while($data = $req_struct->fetch()){
    array_push($structures, $data);
}



?>

<!DOCTYPE HTML>
<html>
	<head>
		<title>Admin</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="assets/css/template.css"/>
        <link rel="stylesheet" href="assets/css/admin.css" />
		<link rel="stylesheet" href="assets/css/fontawesome.min.css">
        <?php include "template/favicon.php"?>
	</head>

	<body>
	    <div id="wrapper">
            <?php include("template/menu.php")?>
            <div id = "bloc_page">
                <h1><span id = "adminbutton"><i class = "fas fa-plus"></i> Ajouter un utilisateur</span></h1>
            <?php if ($_SESSION['structure_id']!=0){?>

                <h1><?=$structures[$_SESSION['structure_id']-1]['nom']?></h1>

                <table id="struct-list"> 
                    <tr>
                        <th onclick="sortTable('struct-list',0)">Nom</th>
                        <th onclick="sortTable('struct-list',1)">Prénom</th>
                        <th onclick="sortTable('struct-list',2)" class = "sorted desc">Dernière connexion </th>
                    </tr>

                    <?php foreach($binded_users as $user){
                        if (($user['level']=="USER") and ($user['id']!=1)){
                            $date = $user['lastvisit']; ?>
                    
                    
                    <tr class="clickable" onclick="window.location.href='overview.php?runner=<?=$user['id']?>'">
                        <input type="hidden" id = "id-runner" value = "<?=$user['id']?>">
                        <td><?= $user['nom'] ?></td>
                        <td><?= $user['prenom'] ?></td>
                        <td><?= $date?></td>
                    </tr>
                        

                    <?php }}?>
                </table>
            <?php }?>
                <h1>Tous les athlètes</h1>
                <table id = "list">
                    <thead>
                        <tr>
                            <th onclick="sortTable('list',0)">Nom</th>
                            <th onclick="sortTable('list',1)">Prénom</th>
                            <th onclick="sortTable('list',2)">Structure</th>
                            <th onclick="sortTable('list',3)" class = "sorted desc">Dernière connexion </th>
                            
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($free_users as $user){
                            $date = $user['lastvisit']; ?>
                    
                    <tr class="clickable" onclick="window.location.href='overview.php?runner=<?=$user['id']?>'">
                        <input type="hidden" id = "id-runner" value = "<?=$user['id']?>">
                        <td><?= $user['nom'] ?></td>
                        <td><?= $user['prenom'] ?></td>
                        <td><?= $user['structure_id']>0?$structures[$user['structure_id']-1]['nom']:"Aucune"?></td>
                        <td><?= $date?></td>
                    </tr>

                    <?php }?>
                    </tbody>
                </table>
                
                 <h1>Coachs</h1>
                <table id = "coachs">
                    <thead>
                        <tr>
                            <th onclick="sortTable('coachs',0)" >Nom</th>
                            <th onclick="sortTable('coachs',1)">Prénom</th>
                            <th onclick="sortTable('coachs',2)">Structure</th>
                            <th onclick="sortTable('coachs',3)">Dernière connexion </th>
                            
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($coaches as $coach){
                            $date = $coach['lastvisit']; ?>
                    
                    <tr>
                        <input type="hidden" id = "id-runner" value = "<?=$coach['id']?>">
                        <td><?= $coach['nom'] ?></td>
                        <td><?=$coach['prenom'] ?></td>
                        <td><?= $coach['structure_id']>0?$structures[$coach['structure_id']-1]['nom']:"Aucune"?></td>
                        <td><?= $date?></td>
                    </tr>

                    <?php }?>
                    </tbody>
                </table>


            </div>
        </div>

        <?php if ($data_user['onboarding']){
			set_onboarding(0,$data_user['id']);
			include "modal/onboarding_modal.php";
		 }?>
        
        <?php include("template/footer.php")?>

        <div id="addAccount" class="modal">
            <!-- Modal content -->
            <div class="modal-content animatetop">
                <span id = "closeAdmin">&times;</span>
                <h3>Nouveau compte</h3>
                <form id="addaccount" autocomplete="off" method="post">
                    <input name="source" type="hidden" id="formname" value="add-account">
                    <p class = "field">
                        <label>Structure : </label>
                        <select name = "structure">
                            <option value = "0">Aucune</option>
                        <?php foreach ($structures as $st){ if (($st['id']==$_SESSION['structure_id']) or $_SESSION['rid'] == 1){?>
                            <option value = "<?=$st['id']?>"> <?=$st['nom']?></option>
                        <?php }} ?>
                        </select>
                    </p>

                    <p class = "field">
                        <label>Login : </label>
                        <input type = "text" name = "login" id = "login" maxlength = "15" required>
                    </p>

                    <p class="field">
                        <label>Nom :</label>
                        <input type = "text" name = "lname" id = "lname" required>
                    </p>

                    <p class="field">
                        <label>Prénom :</label>
                        <input type = "text" name = "fname" id = "fname" required>
                    </p>

                    <p class = "centered">
                        <label class = "roundcheck">
                            <input type="radio" name="sexe" value="H">
                            <span>
                                <img src="images/genderm.png" title = "Homme">
                            </span>
                        </label>
                        
                        <label class = "roundcheck">
                            <input type="radio" name="sexe" value="F">
                            <span>
                                <img src="images/genderf.png" title = "Femme">
                            </span>
                        </label>
                        <br/>
                        <?php if ($_SESSION['rid']==1){?>
                        <label class = "roundcheck">
                            <input type = "checkbox" name = "coach" value = "true">
                            <span>
                                <img src = "images/eye.png" title = "Admin">
                            </span>
                        </label>
                        <?php }?>
                    </p>

                    <p class="centered">
                        <input type="submit" name="save" id="button" value = "Valider">
                    </p>
                </form>
                <hr/>
                <h3>Transférer vers la structure</h3>
                <form id = "transferform" method= "post">
                    <?php if ($_SESSION['rid']==1){?>
                    <select name = "structure">
                        <option value = "0">Aucune</option>
                    <?php foreach ($structures as $st){?>
                        <option value = "<?=$st['id']?>"><?=$st['nom']?></option>
                    <?php } ?>
                    </select>
                    <?php } ?>
                    <div class = "dropwrapper">
                        <span>Sélectionnés : 
                        <span class="multiSel">0</span></span>
                        <i class = "dropbutton fas fa-plus"></i>
                        <ul class = "dropdown">
                            <!--TODO-->
                            <?php foreach($free_users as $fuser){?>
                            <li>
                                <label class="switch">
                                    <input type="checkbox" name = "transfers[]" value="<?=$fuser['id']?>" />
                                    <span class="switchslider round"></span>
                                </label>
                                <span><?=$fuser['prenom']?> <?=$fuser['nom']?></span>
                            </li>
                            <?php }?>
                        </ul>
                    </div>
                    <input type = "submit" class = "button" name = "transfer" value = "Transférer">
                </form>

                    
            </div>
        </div>
		<script src="assets/js/jquery.min.js"></script>
        <script type="text/javascript" src = "assets/js/admin.js"></script>
        <script type="text/javascript" src = "assets/js/dropdown.js"></script>
    </body>
</html>

    