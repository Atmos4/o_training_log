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
invite_structure($id);
header('Location:../admin.php');
?>