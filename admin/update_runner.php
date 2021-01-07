<?php
require_once("../template/functions.php");
$is_admin = check_auth("COACH");
$is_user = check_auth("USER");

if (!isset($_POST) or !is_array($_POST)){
    echo "Erreur: pas de POST";
}
/*$data = explode("_",$_POST['data']);
$login = $data[0];
$nom = $data[1];
$prenom = $data[2];*/

$id = $_POST['id'];
$login = $_POST['login'];
$nom = $_POST['nom'];
$prenom = $_POST['prenom'];

$req = get_db()->prepare('SELECT login FROM runners WHERE login= ? AND id <> ? LIMIT 1');
$req->execute(array($login,$id));
//Si un autre utilisateur est trouvé dans la DB avec le même login
if ($row = $req->fetch()){
    echo "1:Changement impossible, login déjà utilisé";
}
else{
    $req_ur = get_db()->prepare('UPDATE runners SET login = ?, password = SHA1(?), nom = ?, prenom = ? WHERE id = ? LIMIT 1');
    $req_ur->execute(array($login,$login,$nom,$prenom,$id));
    echo "0:Changement effectué";
}
return;
?>

