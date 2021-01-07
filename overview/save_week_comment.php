<?php
require_once("../template/functions.php");

$is_admin = check_auth("COACH");
$is_user = check_auth("USER");

if (!isset($_POST) or !is_array($_POST)){
    echo "Erreur: pas de POST";
    return;
}

$id = isset($_SESSION['visit_id'])?$_SESSION['visit_id']:$_SESSION['rid'];
$date = $_POST['date'];
$comment = addslashes($_POST['comment']);
$coach_comment = addslashes($_POST['coach']);

$req_check_wc = get_db()->prepare('SELECT * FROM comments WHERE runner_id = ? AND date = ? LIMIT 1');
$req_check_wc->execute(array($id,$date));
if ($res = $req_check_wc->fetch()){
    if ($_POST['comment']=="" and $_POST['coach']==""){
        $req_delete_wc = get_db()->prepare('DELETE FROM comments WHERE id = ? LIMIT 1');
        $req_delete_wc->execute(array($res['id']));
    }else{
        $req_update_wc = get_db()->prepare('UPDATE comments SET content = ?,coach_content = ? WHERE id = ? LIMIT 1');
        $req_update_wc->execute(array($comment,$coach_comment,$res['id']));
    }
}
else{
    if ($_POST['comment']!="" or $_POST['coach']!=""){
        $req_insert_wc = get_db()->prepare('INSERT INTO comments(runner_id, date, content, coach_content) VALUES (?,?,?,?)');
        $req_insert_wc->execute(array($id,$date,$comment,$coach_comment));
    }
}
echo "Commentaire enregistré";
return;
?>