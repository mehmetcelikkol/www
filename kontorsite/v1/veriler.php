<?php

$db = new SQLite3("logs.sqlite");


function getCountry($ip){

$cacheFile="ip_cache.json";

$cache=[];

if(file_exists($cacheFile)){

$cache=json_decode(
file_get_contents($cacheFile),
true
);

if(!$cache){
$cache=[];
}

}

if(isset($cache[$ip])){

return $cache[$ip];

}

$json=@file_get_contents(
"http://ip-api.com/json/".$ip."?fields=country"
);

if($json){

$data=json_decode($json,true);

$country=$data['country'] ?? "Bilinmiyor";

}else{

$country="Bilinmiyor";

}

$cache[$ip]=$country;

file_put_contents(
$cacheFile,
json_encode($cache)
);

return $country;

}



echo "
<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<title>Logs</title>

<style>

body{
font-family:Arial;
padding:20px;
}

table{
border-collapse:collapse;
width:100%;
margin-bottom:40px;
}

th,td{
border:1px solid #ccc;
padding:8px;
text-align:left;
vertical-align:top;
}

th{
background:#eee;
}

h2{
margin-top:40px;
}

small{
color:#666;
}

</style>

</head>
<body>

<h1>Log Görüntüleyici</h1>
";



function showTable($db,$tableName){

$result=$db->query("
SELECT *
FROM $tableName
ORDER BY id DESC
");

echo "<h2>$tableName</h2>";
echo "<table>";

$firstRow=$result->fetchArray(SQLITE3_ASSOC);

if(!$firstRow){

echo "Veri yok";
return;
}

echo "<tr>";

foreach($firstRow as $column=>$value){

echo "<th>$column</th>";

}

echo "</tr>";



echo "<tr>";

foreach($firstRow as $column=>$value){

if($column=="ip_address"){

$country=getCountry($value);

echo "<td>"
.htmlspecialchars($value)
."<br><small>("
.htmlspecialchars($country)
.")</small></td>";

}else{

echo "<td>"
.htmlspecialchars($value)
."</td>";

}

}

echo "</tr>";



while($row=$result->fetchArray(SQLITE3_ASSOC)){

echo "<tr>";

foreach($row as $column=>$value){

if($column=="ip_address"){

$country=getCountry($value);

echo "<td>"
.htmlspecialchars($value)
."<br><small>("
.htmlspecialchars($country)
.")</small></td>";

}else{

echo "<td>"
.htmlspecialchars($value)
."</td>";

}

}

echo "</tr>";

}

echo "</table>";

}



showTable($db,"user_logs");
showTable($db,"user_details");

echo "</body></html>";

?>