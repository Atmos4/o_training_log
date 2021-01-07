<?php
require_once("../template/functions.php");
$is_admin = check_auth("COACH");

if (!$is_admin)exit;

//-- TABLES
$tables = array();
$queryTables = query_db('SHOW TABLES');

while($row = $queryTables->fetch()) 
{ 
    $tables[] = $row[0]; 
}
$content = "";

foreach($tables as $table)
{
    $result         =   query_db('SELECT * FROM '.$table);
    $fields_amount  =   $result->columnCount();  
    $rows_num       =   $result->rowCount();
    $res            =   query_db('SHOW CREATE TABLE '.$table); 
    $TableMLine     =   $res->fetch();
    $content        .= "\n\n".$TableMLine[1].";\n\n";

    for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter=0) 
    {
        while($row = $result->fetch())  
        { //when started (and every after 100 command cycle):
            if ($st_counter%100 == 0 || $st_counter == 0 )  
            {
                    $content .= "\nINSERT INTO `".$table."` VALUES";
            }
            $content .= "\n(";
            for($j=0; $j<$fields_amount; $j++)  
            { 
                $row[$j] = str_replace("\n","\\n", addslashes($row[$j]) ); 
                if (isset($row[$j]))
                {
                    $content .= '"'.$row[$j].'"' ; 
                }
                else 
                {   
                    $content .= '""';
                }     
                if ($j<($fields_amount-1))
                {
                        $content.= ',';
                }      
            }
            $content .=")";
            //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
            if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num) 
            {   
                $content .= ";";
            } 
            else 
            {
                $content .= ",";
            } 
            $st_counter=$st_counter+1;
        }
    } $content .="\n\n\n";
}
//$backup_name = $backup_name ? $backup_name : $name."___(".date('H-i-s')."_".date('d-m-Y').")__rand".rand(1,11111111).".sql";
$backup_name = "carnet_ffco_".date("Y-m-d").".sql";
header('Content-Type: application/octet-stream');   
header("Content-Transfer-Encoding: Binary"); 
header("Content-disposition: attachment; filename=\"".$backup_name."\"");  
echo $content; exit;
?>
