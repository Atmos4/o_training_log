function stravaAuth(start, end){
    $("#fill-div").html("Authentification...");
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function(){
        if (this.readyState == 4){
            if (this.status == 200) {
                var jsonData = JSON.parse(xmlhttp.responseText);
                var access_token = jsonData.access_token;
                stravaSync(access_token, start, end);
            }else if (this.status>=400 && this.status <600) $("#fill-div").html("Erreur d'authentification : "+xmlhttp.responseText);
        }
    }
    xmlhttp.open("GET","strava/refresh_token",true);
    xmlhttp.send();
}

function stravaSync(token, start, end){
    $("#fill-div").html("Téléchargement des données...");
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function(){
        if (this.readyState == 4){
            if (this.status == 200) {
                saveStravaInDb(xmlhttp.responseText,token);
            }else if (this.status>=400 && this.status <600) $("#fill-div").html("Erreur : "+xmlhttp.responseText);
        }
    }
    xmlhttp.open("GET","https://www.strava.com/api/v3/athlete/activities?before="+end+"&after="+start+"&page=1&per_page=50",true);
    xmlhttp.setRequestHeader("Authorization", "Bearer "+token);
    xmlhttp.send();
}

function saveStravaInDb(data,token){
    $("#fill-div").html("Sauvegarde...");
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function(){
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            if (xmlhttp.responseText!==""){
                var ids =xmlhttp.responseText.split(":");
                var size = ids.length;
                for (var i = 0;i<size-1;i++){
                    var end = (i==size-2);
                    getHrFromGpx(ids[i],token, end);
                }
                
            }
            else location.reload();
        }
    }
    xmlhttp.open("POST","strava/sync_activities.php",true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send("data="+data);
}

function getHrFromGpx(stravaid,token, end){
    $("#fill-div").html("Evaluation de la fréquence cardiaque...");
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.addEventListener("load", function(){
        if (xmlhttp.readyState == 4){
            if (this.status == 200) {
                saveHrZones(stravaid,xmlhttp.response, end);
            }
            else if (this.status >= 400 && this.status < 600) $("#fill-div").html("Echec");
        }
    });
    xmlhttp.open("GET","https://www.strava.com/api/v3/activities/"+stravaid+"/streams?access_token="+token+"&keys=heartrate&key_by_type=true",true);
    xmlhttp.send();
}

function saveHrZones(id,data, end){
    $("#fill-div").html("Calcul des zones de fréquence cardiaque...");
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function(){
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            if (end)location.reload();
        }
    }
    //Ca marche surement pas
    xmlhttp.open("POST","strava/sync_hr.php",true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send("id="+id+"&data="+data);
}