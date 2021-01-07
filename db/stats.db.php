<?php
require_once("../template/functions.php");

function get_intensities_by_month($runner_id,$date_s,$date_e){
    $req = query_db('SELECT MONTH(date) as mois, SUM(zone1) as i1, SUM(zone2) as i2, SUM(zone3) as i3,SUM(zone4) as i4,SUM(zone5) as i5 
        FROM seances
        WHERE runner_id = ? AND date BETWEEN ? AND ? and type <> 4
        GROUP BY MONTH(date)',
        array($runner_id,$date_s,$date_e));

    return Response::New($req->fetchAll());
}

function get_types_by_month($runner_id,$date_s,$date_e){
    $req=query_db('SELECT MONTH(date) as mois,type, SUM(distance) AS distance, SUM(TIME_TO_SEC(duree)) AS duree 
            FROM seances
            WHERE runner_id = ? AND date BETWEEN ? AND ? and type <> 4
            GROUP BY type,MONTH(date)
            ORDER BY duree DESC',
            array($runner_id,$date_s,$date_e));
    
    return Response::New($req->fetchAll());
}

function get_intensities_by_year($runner_id,$date_s,$date_e){
    $req = query_db('SELECT SUM(zone1) as i1, SUM(zone2) as i2, SUM(zone3) as i3,SUM(zone4) as i4,SUM(zone5) as i5 
        FROM seances
        WHERE runner_id = ? AND date BETWEEN ? AND ? and type <> 4',
        array($runner_id,$date_s,$date_e));

    return Response::New($req->fetchAll()[0]);
}

function get_intensities_by_week($runner_id,$date_s,$date_e){
    $req = query_db('SELECT WEEK(date,3) as week,SUM(TIME_TO_SEC(duree)) as duree, SUM(charge * TIME_TO_SEC(duree)) as charge,
            SUM(zone1) as i1, SUM(zone2) as i2, SUM(zone3) as i3,SUM(zone4) as i4,SUM(zone5) as i5 
        FROM seances
        WHERE runner_id = ? AND date BETWEEN ? AND ? and type <> 4
        GROUP BY WEEK(date,3)',
        array($runner_id,$date_s,$date_e));

    return Response::New($req->fetchAll());
}

function get_types_by_week($runner_id,$date_s,$date_e){
    $req=query_db('SELECT WEEK(date,3) as week,type, SUM(distance) AS distance, SUM(TIME_TO_SEC(duree)) AS duree 
                        FROM seances
                        WHERE runner_id = ? AND date BETWEEN ? AND ? and type <> 4
                        GROUP BY type,WEEK(date,3)
                        ORDER BY duree DESC',
                        array($runner_id,$date_s,$date_e));
                        
    return Response::New($req->fetchAll());
}

function get_fatigue_by_week($runner_id,$date_s,$date_e){
    $req=query_db('SELECT WEEK(date,3) as week, AVG(fatigue) as fatigue
                        FROM days
                        WHERE runner_id = ? AND date BETWEEN ? AND ?
                        GROUP BY WEEK(date,3)',
                        array($runner_id,$date_s,$date_e));
                        
    return Response::New($req->fetchAll());
}
