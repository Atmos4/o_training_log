<?php

$is_admin = check_auth("COACH");
$is_user = check_auth("USER");

$seances = array();
$req_seances = get_db()->query('SELECT * FROM type_seance');
while ($donnees_seances = $req_seances->fetch()){
	$seances[$donnees_seances['id']]=$donnees_seances;
}

$req_runner = get_db()->prepare('SELECT * FROM runners WHERE id=? LIMIT 1');
$req_runner->execute(array($_SESSION['rid']));
$data_runner = array();
if ($data = $req_runner->fetch()){
	$data_runner = $data;
}

if (isset($_SESSION['visit_id'])){
    $req_visit = get_db()->prepare('SELECT * FROM runners WHERE id=? LIMIT 1');
    $req_visit->execute(array($_SESSION['visit_id']));
    $data_visit = array();
    if ($datav = $req_visit->fetch()){
        $data_visit = $datav;
    }
}
?>
<div id="snackbar"><span id = "snackcontent">Some text some message..</span></div>
<div id = "menuNav" class = "topnav">
    <?php if ($is_admin){?>
    <a href="admin.php" class="main"><i class= "fas fa-clipboard-list msides"></i>Athlètes</a>
    <?php if ($_SESSION['rid']==1){?><a href="dev.php"><i class= "fas fa-code msides"></i>Dev</a><?php }else{?>
    <a href="overview.php?reset=1"><i class = "fas fa-calendar-alt msides"></i><span class = "txt">Planning</span></a><?php }?>
    <?php }else{?>
    <a href="overview.php" class="main"><i class= "fas fa-calendar-alt msides"></i>Calendrier</a>
    <a href="stats.php"><i class="fas fa-chart-line msides"></i>Statistiques</a>
    <?php }
    if (isset($_SESSION['visit_id'])){?>
    <div class="dropdown-nav">
        <div class="dropdown-btn">
            <i class= "fas fa-eye msides"></i><?=$data_visit['prenom']?> 
            <i class="fa fa-caret-down msides"></i>
        </div>
        <div class="dropdown-nav-content">
            <a href="overview.php"><i class = "fas fa-calendar-alt msides"></i>Calendrier</a>
            <a href="stats.php"><i class="fas fa-chart-line msides"></i>Statistiques</a>
            <a href="account.php?id=<?=$_SESSION['visit_id']?>"><i class= "fas fa-cog msides"></i>Compte</a>
        </div>
    </div>
    <?php }?>
    <div class="right">
        <a href="account.php"><i class = "fas fa-cog msides"></i><span class = "txt">Mon compte<span></a>
        <a href="export.php"><i class = "fas fa-file-download msides"></i><span class = "txt">Exporter<span></a>
        <a href = "report.php"><i class = "fas fa-bug msides"></i><span class = "txt">Problème</span></a>
        <a href = "logout.php"><i class ="fas fa-power-off msides"></i><span class = "txt">Déconnexion</span></a>
    </div>
    <a href="javascript:void(0);" class="menu" onclick="menu()">
        <i class="fa fa-bars"></i>
    </a>
</div>

<script type="text/javascript">

function menu(){
    var x = document.getElementById("menuNav");
    if (x.className === "topnav") {
        x.className += " responsive";
    } else {
        x.className = "topnav";
    }
}

function toast(message){
    // Get the snackbar DIV
    var x = document.getElementById("snackbar");
    document.getElementById("snackcontent").innerHTML = message;

    // Add the "show" class to DIV
    x.className = "show";

    // After 3 seconds, remove the show class from DIV
    setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
} 
</script>

