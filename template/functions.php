<?php
init();

/**
 * Initialises database and PHP session. 
 * 
 * **Is needed for any page to work !**
 */
function init(){
	//DB CONFIG
	$db_config = load_config();
	if (!count($db_config)){
		die('Pas de fichier de configuration');
	}
    
    global $db;
    //Database init
    try {
    	$db = new PDO('mysql:host='.$db_config['host'].';dbname='.$db_config['name'].';charset=utf8', $db_config['login'], $db_config['password']);
    } catch(Exception $e) {
            die('Erreur : '.$e->getMessage());
    }

    //Session init
    session_start();

    //Time zone
    date_default_timezone_set ("Europe/Paris");
}

/**
 * WARNING: a config.php file is necessary
 */
function load_config(){

	if (file_exists('config.php')){
		include 'config.php';
	}
	elseif (file_exists('../config.php')){
		include '../config.php';
	}
	else{
		return array();
	}
	return ['host'=>$db_host,'name'=>$db_name,'login'=>$db_login,'password'=>$db_password];
}

/**
 * Returns the database interaction PDO object
 */
function get_db(){
	return $GLOBALS['db'];
}

/**
 * Response wrapper
 */
class Response{
    /** Response state flag */
    public $success = true;
    /** Response data */
    public $data = "";
    /** Response message */
    public $msg = "";

    public function __construct() {
    }

    /**
     * Factory : Success 
     */
    public static function New($dat) {
        $instance = new self();
        $instance->data = $dat;
        return $instance;
    }

    /**
     * Factory : message
     */
    public static function Message($dat) {
        $instance = new self();
        $instance->msg = $dat;
        return $instance;
    }

    /**
     * Factory : error
     */
    public static function Error($msg = "") {
        $instance = new self();
        $instance->success = false;
        $instance->msg = $msg;
        return $instance;
    }

    /**
     * Magic function toString() -> cast to string.
     */
    public function __toString(){
        return $this->msg;
    }

    /**
     * Returns opposite of success
     */
    public function fail(){
        return !$this->success;
    }

    /** Set $msg variable, allows decorator pattern
     * @return Response $this object (decorator pattern)
     */
    public function setMessage($msg){
        $this->msg = $msg;
        return $this;
    }

}

/**
 * Flash messages
 */

/**
 * Append a flash message to the flash array
 */
function add_flash($msg, $key){
	if (isset($_SESSION['flash']) and is_array($_SESSION['flash'])){
		$_SESSION['flash'][$key] = $msg;
	}
	else{
		$_SESSION['flash'] = array($key => $msg);
	}
}


/**
 * Returns and resets the flash array
 */
function display_flash($key){
	if (isset($_SESSION['flash']) and is_array($_SESSION['flash']) and isset($_SESSION['flash'][$key])){
		$msg = $_SESSION['flash'][$key];
		$ret = "<span class = ".($msg->fail()?"error":"info").">".$msg."</span>";
		unset($_SESSION['flash'][$key]);
	}
	else{
		$ret = "";
	}
	return $ret;
}

function redirect($url){
	header("Location: ".$url);
	exit;
}

function query_db($sql, $args = null){
    if ($args){
        $reqex = get_db()->prepare($sql);
        $reqex->execute($args);
        return $reqex;
    }
    else {
        $req_query = get_db()->query($sql);
        return $req_query;
    }
}

//RegExp num
function check_num($data = 0) {
	if(is_numeric($data) or $data == '') {
		return $data;
	}
	die("format de donnée invalide: '".$data."'");
}

//RegExp durée
function check_duree($data) {
	if(preg_match("/(\d+:\d\d|^$)/", $data)) {
		return $data;
	}
	die("format de donnée invalide: '".$data."'");
}

//RegExp varchar
function check_varchar($data = "") {
	if(!preg_match("/[\w\d\s]*/", $data)) {
		die("format de donnée invalide");
	}
	return $data;
}

function secure_input($data){
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}

//Vérifier l'authentification de la session
function check_auth($level) {
	if (!isset($_SESSION['rid'])) {
		header("Location: /");
	}
	if (isset($_SESSION['level'])) {
		$ulevel = check_varchar($_SESSION['level']);
		if ($level != $ulevel) {
			return false;
		}
	} else {
		die("Page non authorisée");
	}
	return true;
}

function logout(){
	session_destroy();
	header("Location: /");
}



function strava_api_call($uri,$post_args = null,$auth_token = "",$content_type = ""){
	$post = is_array($post_args);

	$header = array(
		"Accept: */*",
		"Cache-Control: no-cache",
		"Connection: keep-alive",
		"Host: www.strava.com",
	);
	if ($auth_token !=""){
		array_push($header,"Authorization: Bearer ".$auth_token);
	}
	if ($content_type!=""){
		array_push($header,"Content-Type: ".$content_type);
	}
	$curl = curl_init();
	if ($post){
		$opt = array(
			CURLOPT_URL => "https://www.strava.com/api/v3/".$uri,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => $post,
			CURLOPT_POSTFIELDS => $post_args,
			CURLOPT_HTTPHEADER => $header,
		);
	}
	else{
		$opt = array(
			CURLOPT_URL => "https://www.strava.com/api/v3/".$uri,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => $header,
		);
	}

	curl_setopt_array($curl, $opt);

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err){
		return Response::Error($err);
	} else {
		return Response::New($response);
	}
}

function get_strava_token($code, $type){
	$args = array(
		'code' => $code,
		'client_id' => 26669,
		'client_secret'=> '6fee4c6487ce90d9bcd495824c878c6df9a3d4aa',
		'grant_type'=>$type
	);
	
	$response = strava_api_call("oauth/token", true, $args);

	return $response;
}

function get_hr_data(){
	$curl = curl_init();

	curl_setopt_array($curl, array(
	CURLOPT_URL => "https://www.strava.com/api/v3/activities/2198687709/streams?keys=heartrate,time&key_by_type=true",
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => array(
		"Authorization: Bearer 96d96a03c15d003952eafe5b1e03cc03b1b6cbc2",
		"Content-Type: application/json",
		"Host: www.strava.com"
	),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
		echo "cURL Error #:" . $err;
	} else {
		echo $response;
	}
}

//Conversion de temps en secondes
function time_to_sec($time) {
	if (!preg_match("/\d+:\d+:\d+/", $time)) {
		return 0;
		//die("time_to_sec: donnee invalide : >$time <");
	}
	$tmp = explode(":", $time);
	$val = $tmp[0] * 60 + $tmp[1];
	return $val;
}

//Création de la chaine de caractère pour le temps
function time_to_string($hours,$min){
	return ($hours<10?"0".$hours:$hours).":".($min<10?"0".$min:$min);
}


function change_infos($id, $nom, $prenom, $sexe){
	query_db('UPDATE runners SET nom= ?, prenom = ?, sexe=? WHERE id=? LIMIT 1',array($nom, $prenom, $sexe, $id));
}

function change_fc($id,$fcm, $fcr, $z1, $z2, $z3, $z4){
	query_db('UPDATE runners SET fcmax= ?, fcr = ?, z1limit = ?, z2limit = ?, z3limit = ?, z4limit = ? WHERE id=? LIMIT 1',array($fcm, $fcr, $z1, $z2, $z3, $z4, $id));
}

//Changement de mot de passe
function change_pwd($old, $new, $new2) {
	if (strlen($new)<4){
		return Response::Error("Le nouveau mot de passe n'est pas assez long");
	}
	if (sha1($new) != sha1($new2)) {
		return Response::Error("Les mots de passes sont différents !");
	}
	$pass  = check_varchar($old);
	$npass = check_varchar($new);
	$req = get_db()->prepare('SELECT * FROM runners WHERE id= ? AND password=SHA1(?) LIMIT 1');
	$req->execute(array( $_SESSION['rid'], $pass));

	//Si l'utilisateur est trouvé dans la DB
	if ($req->fetch()){
		$req = get_db()->prepare('UPDATE runners SET password=SHA1(?) WHERE id=? LIMIT 1');
		$req->execute(array($npass, $_SESSION['rid']));
		return Response::Message("Changement effectué.");
	} else {
		return Response::Error("L'ancien mot de passe est incorrect !");
	}
}

function reset_password($id){
    $req_id = query_db('SELECT * FROM runners WHERE id = ? LIMIT 1', array($id));
	$req_id->execute(array($id));
	if ($ret = $req_id->fetch()){
	    query_db('UPDATE runners SET password=SHA1(?) WHERE id=? LIMIT 1',array($ret['login'],$id));
		return Response::Message('Mot de passe réinitialisé');
	}
	else{
		return Response::Error('Erreur');
	}
}

//Mise à jour du login
function change_login($id,$old, $new) {
	if (strlen($new)<4){
		return Response::Error("Le nouveau login n'est pas assez long");
	}
	if (sha1($old) == sha1($new)) {
		return Response::Error("Gros malin ! Le nouveau login est identique à l'ancien.");
	}
	$login  = check_varchar($new);
	$req = query_db('SELECT login FROM runners WHERE login= ? LIMIT 1',array($login));

	//Si un autre utilisateur est trouvé dans la DB avec le même login
	if ($req->fetch()){
		return Response::Error("Ce login est déjà utilisé");
	} else {
		$req = query_db('UPDATE runners SET login=? WHERE id=? LIMIT 1',array($login, $id));
		return Response::Message("Changement effectué");
	}
}

function strava_disconnect($id){
	query_db('UPDATE runners SET strava_token = NULL WHERE id= ? LIMIT 1',array($id));
	return Response::Message("Déconnecté de Strava");
}

function add_account($post){
	$login = $post['login'];
	$prenom = $post['fname'];
	$nom = $post['lname'];
	$sexe = isset($post['sexe'])?$post['sexe']:"H";
	$level = isset($post['coach'])?"COACH":"USER";
	$structure = $post['structure'];
	$req_double = get_db()->prepare('SELECT * FROM runners WHERE login = ? LIMIT 1');
	$req_double->execute(array($login));
	if ($req_double->fetch())return "Ce login existe déjà";

	$req = get_db()->prepare('INSERT INTO runners(login,password,nom, prenom, sexe, level,structure_id) VALUES (?,SHA1(?),?,?,?,?,?)');
	$req->execute(array($login, $login,$nom,$prenom,$sexe,$level,$structure));
	return "Compte ajouté";
}

function delete_account($id){
    if ($id != $_SESSION['rid']){
    	$req = get_db()->prepare('DELETE FROM runners WHERE id = ? LIMIT 1');
    	$req->execute(array($id));
    }
}

function transfer($post){
	$structure = isset($post['structure'])?$post['structure']:$_SESSION['structure_id'];
	foreach($post['transfers'] as $transid){
		$req = get_db()->prepare('UPDATE runners SET structure_id =  ? WHERE id = ? LIMIT 1');
		$req->execute(array($structure,$transid));
	}
}

//STRUCTURE
function get_all_structures(){
	$req_struct = get_db()->query('SELECT * FROM structure');
	return Response::New($req_struct->fetchAll());
}

function change_structure($id, $structure){
	query_db('UPDATE runners SET structure_id =  ? WHERE id = ? LIMIT 1',array($structure,$id));
}

function reset_structure($id){
	change_structure($id,0);
}


function update_day($post){
	$runner_id = get_rid();
	if (isset($post['date'])){
		$date = $post['date'];
		$fatigue = isset($post['fatigue'])?(($post['fatigue']!="")?$post['fatigue']:0):0;
		$state = isset($post['state'])?(($post['state']!="")?$post['state']:0):0;

		$rest_hours = isset($post['resth'])?(($post['resth']!="")?$post['resth']:0):0;
		$rest_min = isset($post['restmin'])?(($post['restmin']!="")?$post['restmin']:0):0;
		$rest = time_to_string($rest_hours, $rest_min);

		$comment = (isset($post['commentaire'])) ? addslashes(check_varchar($post['commentaire'])) : "";

		$req_find_day = get_db()->prepare('SELECT * FROM days WHERE runner_id = ? AND date = ? LIMIT 1');
		$req_find_day->execute(array($runner_id,$date));
		if ($day = $req_find_day->fetch()){
			$day_id = $day['id'];
			$req_up_day = get_db()->prepare('UPDATE days SET fatigue= ?, state = ?, rest = ?, details = ? WHERE id = ?');
			$req_up_day->execute(array($fatigue, $state,$rest, $comment, $day_id));
			return;
		}
		else{
			$req_add_day = get_db()->prepare('INSERT INTO days (runner_id,date, fatigue,state,rest, details) VALUES(?,?,?,?,?,?)');
			$req_add_day->execute(array($runner_id,$date,$fatigue, $state,$rest, $comment));
			return;
		}
	}
}

//Grosse fonction pour mettre à jour les données à partir de day_view. Essentiellement du SQL
function update_seance($post) {
	if (isset($post['type_seance']) and isset($post['date'])){
		$seance_id=$post['seance_id'];
		$title=isset($post['title'])?$post['title']:"";
		$type = $post['type_seance'];
		$datedb = $post['date'];
		$dist =  (isset($post['distance']))  ? ($post['distance']!=""?$post['distance']:0): 0;
		$hours = (isset($post['hours'])) ? ($post['hours']!=""?$post['hours'] : 0):0;
		$minutes = (isset($post['min'])) ? ($post['min']!=""?$post['min'] : 0):0;
		$deniv = (isset($post['uphill'])) ? ($post['uphill']!=""?$post['uphill'] : 0):0;
		$charge = (isset($post['charge']))? (($post['charge']!="")?$post['charge']:0): 0;

		$z1 = (isset($post['hrzone1']))? (($post['hrzone1']!="")?$post['hrzone1']:0): 0;
		$z2 = (isset($post['hrzone2']))? (($post['hrzone2']!="")?$post['hrzone2']:0): 0;
		$z3 = (isset($post['hrzone3']))? (($post['hrzone3']!="")?$post['hrzone3']:0): 0;
		$z4 = (isset($post['hrzone4']))? (($post['hrzone4']!="")?$post['hrzone4']:0): 0;
		$z5 = (isset($post['hrzone5']))? (($post['hrzone5']!="")?$post['hrzone5']:0): 0;
		
		if(!($hours>0 or $minutes>0)) {
			echo " invalid";
			$req_del = get_db()->prepare('DELETE FROM seances WHERE seance_id=?');
			$req_del->execute(array($seance_id));
			return;
		}
		$duree = time_to_string($hours,$minutes);
		$txt = (isset($post['txt'])) ? addslashes(check_varchar($post['txt'])) : "";
	
		if ($seance_id){
			$req_up_seance = get_db()->prepare('UPDATE seances 
											SET type = ?, duree = ?, distance = ?, deniv = ?,title=?, txt = ?, charge = ?, zone1=?, zone2=?, zone3=?, zone4=?, zone5=? 
											WHERE seance_id = ? LIMIT 1');
			$req_up_seance->execute(array($type,$duree,$dist,$deniv,$title,$txt,$charge, $z1, $z2, $z3, $z4, $z5,$seance_id));
		}
	}
}

function delete_seance($post){
    if (isset($post['seance_id'])){
        $seance_id=$post['seance_id'];
        $req_delete = get_db()->prepare('DELETE FROM seances WHERE seance_id = ? LIMIT 1');
        $req_delete->execute(array($seance_id));
    }
}

function add_seance($post){
	if (isset($post['type_seance']) and isset($post['date'])){
		$type = $post['type_seance'];
		$title=isset($post['title'])?($post['title']!=""?$post['title']:""):"";
		$datedb = $post['date'];
		$dist =  (isset($post['distance']))  ? ($post['distance']!=""?$post['distance']:0): 0;
		$hours = (isset($post['hours'])) ? ($post['hours']!=""?$post['hours'] : 0):0;
		$minutes = (isset($post['min'])) ? ($post['min']!=""?$post['min'] : 0):0;
		$deniv = (isset($post['uphill'])) ? ($post['uphill']!=""?$post['uphill'] : 0):0;
		$charge = (isset($post['charge']))?(($post['charge']!="")?$post['charge']:0): 0;

		$z1 = (isset($post['hrzone1']))? (($post['hrzone1']!="")?$post['hrzone1']:0): 0;
		$z2 = (isset($post['hrzone2']))? (($post['hrzone2']!="")?$post['hrzone2']:0): 0;
		$z3 = (isset($post['hrzone3']))? (($post['hrzone3']!="")?$post['hrzone3']:0): 0;
		$z4 = (isset($post['hrzone4']))? (($post['hrzone4']!="")?$post['hrzone4']:0): 0;
		$z5 = (isset($post['hrzone5']))? (($post['hrzone5']!="")?$post['hrzone5']:0): 0;

		if(!($hours>0 or $minutes>0)) {
			return;
		}
		$duree = time_to_string($hours, $minutes);

		$txt = (isset($post['txt'])) ? addslashes(check_varchar($post['txt'])) : "";
		
		$req_add_seance = get_db()->prepare('INSERT INTO seances (runner_id,type,date, duree, distance,deniv,title, txt, charge, zone1, zone2, zone3, zone4, zone5) 
										VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
		$req_add_seance->execute(array($_SESSION['rid'],$type,$datedb,$duree,$dist,$deniv,$title,$txt, $charge, $z1, $z2, $z3, $z4, $z5));
	}
}

function get_seances_day($runner_id,$date){
	$req = query_db('SELECT seances.* 
		FROM seances
		WHERE runner_id = ? AND DATE = ? 
		ORDER BY  DATE ASC,  seance_id ASC',
		array($runner_id,$date));
	return Response::New($req->fetchAll());
}

function get_plannings_day($runner_id, $structure_id,$date){
	if ($structure_id){
		$req = query_db('SELECT * FROM planning 
						WHERE (runner_id = ? or structure_id = ?) AND date = ? 
						ORDER BY  DATE ASC,  id ASC',
			array($runner_id,$structure_id,$date));
	}
	else{
		$req = query_db('SELECT * FROM planning 
						WHERE runner_id = ? AND date = ? 
						ORDER BY  DATE ASC,  id ASC',
			array($runner_id,$date));
	}
	return Response::New($req->fetchAll());
}

function delete_planning($post){
	$req_check = get_db()->prepare('SELECT * FROM planning WHERE id = ? LIMIT 1');
	$req_check->execute(array($post['planning_id']));
	if ($data = $req_check->fetch()){
		if ($data['structure_id']==0){
			$req_delplanning = get_db()->prepare('DELETE FROM planning WHERE id = ? LIMIT 1');
			$req_delplanning->execute(array($post['planning_id']));
		}
		else{
			if ($_SESSION['level']=="COACH"){
				query_db('DELETE FROM planning_vote WHERE planning_id = ?',array($post['planning_id']));
				query_db('DELETE FROM planning WHERE id = ? LIMIT 1',array($post['planning_id']));
			}
		}
	}
}

function update_planning($post){
	if (isset($_SESSION['visit_id']))$rid = $_SESSION['visit_id'];
	else $rid = $_SESSION['rid'];
	$type = $post['type_seance'];
	$datedb = $post['date'];
	$hours = (isset($post['hours'])) ? $post['hours'] : 0;
	$minutes = (isset($post['min'])) ? $post['min'] : 0;
	$titre = (isset($post['title'])) ? $post['title'] : "";
	if(!($hours>0 or $minutes>0)) {
		return;
	}
	$duree = time_to_string($hours, $minutes);

	$txt = (isset($post['txt'])) ? addslashes(check_varchar($post['txt'])) : "";
	$req_check = get_db()->prepare('SELECT * FROM planning WHERE id = ? LIMIT 1');
	$req_check->execute(array($post['planning_id']));
	if ($data = $req_check->fetch()){
		if ($data['structure_id']==0 or $_SESSION['level']=="COACH"){
			$req_updateplanning = get_db()->prepare('UPDATE planning SET runner_id = ?,type = ?,date=?,titre=?, duree=?, details=? WHERE id = ?');
			$req_updateplanning->execute(array($rid,$type,$datedb,$titre,$duree,$txt,$post['planning_id']));
		}
	}
}

function add_planning($post){
	if (isset($_SESSION['visit_id'])){
		$runner = $_SESSION['visit_id'];
	}
	else{
		$runner = $_SESSION['rid'];
	} 

	if (isset($post['type_seance']) and isset($post['date'])){
		
		$type = $post['type_seance'];
		$datedb = $post['date'];
		$hours = (isset($post['hours'])) ? $post['hours'] : 0;
		$minutes = (isset($post['min'])) ? $post['min'] : 0;
		$titre = (isset($post['title'])) ? $post['title'] : "";
		$structure = isset($post['iscommon'])?$_SESSION['structure_id']:"0";
		if(!($hours>0 or $minutes>0)) {
			return;
		}
		$duree = time_to_string($hours, $minutes);
		$txt = (isset($post['txt'])) ? addslashes(check_varchar($post['txt'])) : "";

		if (isset($post['multiadd']) and is_array($post['multiadd'])){
			foreach ($post['multiadd'] as $addid){
				query_db('INSERT INTO planning (structure_id,runner_id,type,date,titre, duree, details) VALUES (?,?,?,?,?,?,?)',array(0,$addid,$type,$datedb,$titre,$duree,$txt));
			}
		}
		else{
			query_db('INSERT INTO planning (structure_id,runner_id,type,date,titre, duree, details) VALUES (?,?,?,?,?,?,?)',array($structure,$runner,$type,$datedb,$titre,$duree,$txt));
		}
	}
}

function add_planning_vote($post){
	$planning_id = $post['planning_id'];
	
	if (check_auth("COACH") and isset($_SESSION['visit_id'])){
		$runner_id = $_SESSION['visit_id'];
	}else $runner_id = $_SESSION['rid'];

	delete_votes($planning_id,$runner_id);
	if (isset($_POST['vote-yes'])){
		planning_vote($planning_id, $runner_id,1);
	}
	else if (isset($_POST['vote-no'])){
		planning_vote($planning_id, $runner_id,0);
	}
}

function planning_vote($planning_id, $runner_id, $vote, $vote_id = 0){
	if ($vote_id){
		query_db('UPDATE planning_vote SET runner_id = ?, planning_id = ?, vote = ? WHERE id = ?', array($runner_id,$planning_id,$vote,$vote_id));
	}
	else{
		query_db('INSERT INTO planning_vote(runner_id,planning_id,vote) VALUES(?,?,?)',array($runner_id,$planning_id,$vote));
	}
}

function get_votes($planning_id, $runner_id = 0){
	if ($runner_id){
		$query = query_db('SELECT planning_vote.*,runners.nom AS nom, runners.prenom AS prenom FROM planning_vote LEFT JOIN runners ON planning_vote.runner_id = runners.id WHERE planning_id = ? AND runner_id = ? LIMIT 1',array($planning_id, $runner_id));
		if ($result = $query->fetch()){
			return Response::New($result);
		} else {
			return Response::Error("No vote");
		}
	} else {
		$query = query_db('SELECT planning_vote.*,runners.nom AS nom, runners.prenom AS prenom FROM planning_vote LEFT JOIN runners ON planning_vote.runner_id = runners.id WHERE planning_id = ?',array($planning_id));
		return Response::New($query->fetchAll());
	}
}

function delete_votes($planning_id,$runner_id = 0){
	if ($runner_id){
		query_db('DELETE FROM planning_vote WHERE planning_id = ? and runner_id = ?',array($planning_id, $runner_id));
	} else {
		query_db('DELETE FROM planning_vote WHERE planning_id = ?',array($planning_id));
	}
}

function report($post){
    $id = $_SESSION['rid'];
    $text = isset($post['report'])?addslashes($post['report']):"";
    
    query_db('INSERT INTO bug_report(runner_id, date, problem,reply) VALUES(?,CURDATE(),?,?)',array($id,$text,""));
}

function report_reply($bug_id,$reply){
	query_db('UPDATE bug_report SET reply = ? WHERE id=? LIMIT 1', array($reply,$bug_id));
}

function delete_report($id){
	query_db('DELETE FROM bug_report WHERE id = ? LIMIT 1',array($id));
}

function add_user($login, $nom, $prenom, $sexe) {
	$sql = get_db()->prepare('INSERT INTO runners (login, password, nom, prenom, sexe) VALUES (?,SHA1(?),?,?,?)');
	$sql->execute(array($login, $login, $nom, $prenom, $sexe));
}

function update_user($login, $nom, $prenom, $sexe, $userid, $password) {
	$sql = get_db()->prepare('UPDATE runners SET login=?, nom=?, prenom=?, sexe=? WHERE id=? LIMIT 1');
	$sql->execute(array($login, $nom, $prenom, $sexe, $userid));
	
	if ($password != '') {
		$sql = get_db()->prepare('UPDATE runners SET password=SHA1(?) WHERE id=? LIMIT 1');
		$sql->execute(array($login, $userid));
	}
}

function get_user($id){
	$sql = get_db()->prepare('SELECT * FROM runners WHERE id=? LIMIT 1');
	$sql->execute(array($id));
	if ($data = $sql->fetch()){
		return Response::New($data);
	}else{
		return Response::Error("Utilisateur non trouvé");
	}
}

function get_rid(){
	if (isset($_SESSION['visit_id'])){
		return $_SESSION['visit_id'];
	}
	else{
		return $_SESSION['rid'];
	} 
}

function set_onboarding($val,$id=0){
	if ($id){
		query_db('UPDATE runners SET onboarding = ? WHERE id = ? LIMIT 1',array($val,$id));
	}else{
		query_db('UPDATE runners SET onboarding = ?',array($val));
	}
}


//RPE

/**
 * Calcule le RPE
 */
function get_rpe_load($duree,$load){
	return $duree*$load;
}

/**
 * Calcule le TRIMP d'Edward
 */
function get_edwards_trimp($i1,$i2,$i3,$i4,$i5){
	return $i1 + $i2*2 + $i3*3 + $i4*4 + $i5*5;
}


/**
 * Calcule le TRIMP CAD
 */
function get_cad_trimp($i1,$i2,$i3,$i4,$i5){
	return $i1*0.82 + $i2*1.12 + $i3*2.08 + $i4*3.42 + $i5*5.89;
}

?>