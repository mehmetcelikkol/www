<?php

$db = new SQLite3("logs.sqlite");


/* SON GÖRÜLEN KAYIT */

$seenFile="last_seen.txt";

$lastSeen=0;

if(file_exists($seenFile)){
$lastSeen=(int)file_get_contents($seenFile);
}


$latestId=$db->querySingle("
SELECT MAX(id)
FROM user_logs
");

if(!$latestId){
$latestId=0;
}



/* IP -> ÜLKE */

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

$data=json_decode(
$json,
true
);

$country=
$data['country']
?? "Bilinmiyor";

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




/* ÖZET İSTATİSTİKLER */

$totalIps = $db->querySingle("
SELECT COUNT(DISTINCT ip_address)
FROM user_logs
");

$indexIps = $db->querySingle("
SELECT COUNT(DISTINCT ip_address)
FROM user_logs
WHERE element_info LIKE '%index%'
");

$onlyIndexIps = $db->querySingle("
SELECT COUNT(*)
FROM(

SELECT ip_address
FROM user_logs
GROUP BY ip_address

HAVING

SUM(
CASE
WHEN element_info LIKE '%index%'
THEN 1
ELSE 0
END
)>0

AND

SUM(
CASE
WHEN element_info NOT LIKE '%index%'
THEN 1
ELSE 0
END
)=0

)
");


$continuedIps = $db->querySingle("
SELECT COUNT(*)
FROM(

SELECT ip_address
FROM user_logs
GROUP BY ip_address

HAVING

SUM(
CASE
WHEN element_info LIKE '%index%'
THEN 1
ELSE 0
END
)>0

AND

SUM(
CASE
WHEN element_info NOT LIKE '%index%'
THEN 1
ELSE 0
END
)>0

)
");



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

.summary{
margin-bottom:40px;
}

.summary td{
font-weight:bold;
}

h2{
margin-top:50px;
}

small{
color:#666;
}

.newRow{
background:#fff3b0;
font-weight:bold;
}

</style>

</head>

<body>

<h1>Log Görüntüleyici</h1>

<div class='summary'>

<h2>Özet</h2>

<table>

<tr>
<th>Bilgi</th>
<th>Değer</th>
</tr>

<tr>
<td>Toplam farklı IP</td>
<td>$totalIps</td>
</tr>

<tr>
<td>Index sayfasına giren IP</td>
<td>$indexIps</td>
</tr>

<tr>
<td>Sadece index açıp çıkmış</td>
<td>$onlyIndexIps</td>
</tr>

<tr>
<td>Index → diğer sayfalara geçmiş</td>
<td>$continuedIps</td>
</tr>

</table>

</div>

";



function showTable($db,$tableName,$lastSeen){

$result=$db->query("
SELECT *
FROM $tableName
ORDER BY id DESC
");


echo "<h2>$tableName</h2>";


$firstRow=$result->fetchArray(
SQLITE3_ASSOC
);


if(!$firstRow){

echo "Veri yok";
return;

}


echo "<table>";

echo "<tr>";

foreach($firstRow as $column=>$value){

echo "<th>$column</th>";

}

echo "</tr>";



$rowClass="";

if(
isset($firstRow["id"])
&&
$firstRow["id"]>$lastSeen
){

$rowClass="class='newRow'";

}

echo "<tr $rowClass>";

foreach($firstRow as $column=>$value){

if($column=="ip_address"){

$country=getCountry($value);

echo "<td>"
.htmlspecialchars($value)
."<br><small>"
.htmlspecialchars($country)
."</small></td>";

}else{

echo "<td>"
.htmlspecialchars($value)
."</td>";

}

}

echo "</tr>";




while(
$row=$result->fetchArray(
SQLITE3_ASSOC
)
){

$rowClass="";

if(
isset($row["id"])
&&
$row["id"]>$lastSeen
){

$rowClass="class='newRow'";

}


echo "<tr $rowClass>";

foreach($row as $column=>$value){

if($column=="ip_address"){

$country=getCountry($value);

echo "<td>"
.htmlspecialchars($value)
."<br><small>"
.htmlspecialchars($country)
."</small></td>";

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



showTable(
$db,
"user_logs",
$lastSeen
);

showTable(
$db,
"user_details",
$lastSeen
);



file_put_contents(
$seenFile,
$latestId
);


echo "

</body>
</html>

";

?>