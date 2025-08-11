<!DOCTYPE html>
<html lang="cs">
<head>
    <title>Výsledek inventury</title>
    <meta charset="UTF-8">
    <meta name="author" content="Jan Sonbol" />
    <meta name="description" content="compare" />
    <link rel="stylesheet" type="text/css" href="../css/inventura.css" />
    <link rel="stylesheet" type="text/css" href="../css/style.css" />
    <script
  src="https://code.jquery.com/jquery-3.7.1.min.js"
  integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
  crossorigin="anonymous"></script>
</head>
<body>
    <header>
        <h1>BabyBeBare App</h1>
        <?php require '../navigation.php'; ?>
    </header> 
<?php 
session_start();
require '../SQLconn.php';

echo "<div>";
if (!isset($Connection)) {$Connection = PDOConnect::getInstance('f181433');}
    $SQL = 'DROP TABLE IF EXISTS compare;';
    $stmt = $Connection->execute($SQL);
    $SQL = 'CREATE TABLE compare
            (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255),
                ean VARCHAR(13),
                productNumber VARCHAR(50),
                barva VARCHAR(50),
                velikost INT,
                datum DATETIME,
                scanstock INT,
                invstock INT,
                finalstock INT                
            );';
    $stmt = $Connection->execute($SQL);

    $SQL = "SELECT * FROM `inventura`";
    $stmt = $Connection->select($SQL);
    $rows = $stmt['rows']; 

    foreach($rows as $item)
        {
        $productNumber = $item['productNumber'];
        $velikost = $item['velikost'];
        $SQL = "SELECT sum(`stock`) as stock, max(`datum`) as datum FROM `scan` WHERE `productNumber` = '{$productNumber}' AND `velikost` = '{$velikost}' Group by `productNumber`, `velikost`";
        $stmt = $Connection->select($SQL);
        $invstock = $item["stock"];
        $count = $stmt['count'];
        if ($count === false || $count === null || $count === 0)
            {
            if($invstock !== 0)
                { 
                $finalstock = 0 - $invstock;  
                $data = array('name' => $item["name"], 'ean' => $item["ean"],'productNumber' => $item["productNumber"], 'barva' => $item["barva"], 'velikost' => $item["velikost"],'scanstock' => 0, 'invstock' => $invstock, 'finalstock' => $finalstock);
                $Connection->insert("compare", $data);
                }
            }
        else
            {
            $scanstock = $stmt["rows"][0]["stock"];
            $finalstock = $scanstock - $invstock;
            if($finalstock !== 0 )
                {
                $data = array('name' => $item["name"], 'ean' => $item["ean"],'productNumber' => $item["productNumber"], 'barva' => $item["barva"], 'velikost' => $item["velikost"], 'datum' => $stmt["rows"][0]["datum"] ,'scanstock' => $scanstock, 'invstock' => $invstock, 'finalstock' => $finalstock);
                $Connection->insert("compare", $data);
                }  
            }
        }
    $SQL = "SELECT * FROM `scan` WHERE `productNumber` = 'Unknown'";
    $stmt = $Connection->select($SQL);
    $count = $stmt['count'];
    if ($count !== false || $count !== null || $count !== 0)
        {
        $rows = $stmt['rows']; 
        foreach($rows as $item)
            {
            $data = array('name' => $item["name"], 'ean' => $item["ean"],'productNumber' => $item["productNumber"], 'barva' => $item["barva"], 'velikost' => $item["velikost"], 'datum' => $stmt["rows"][0]["datum"], 'scanstock' => $scanstock, 'invstock' => $invstock, 'finalstock' => $finalstock);
            $Connection->insert("compare", $data);
            }
        }

    $SQL = "SELECT * FROM `compare` ORDER BY `productNumber` ";
    $stmt = $Connection->select($SQL);

    $count = $stmt['count'];    
    echo "Počet záznamů: " . $count . "<br>";
    if ($count !== false || $count !== null || $count !== 0)
        {
        $rows = $stmt['rows'];    
        $columnNames = ['ID','Produkt','EAN','ProdNum','Barva','Vel.','Datum','ScanStock','InvStock','Stav'];
        echo '<table border="2" cellspacing="1" cellpadding="5">';
        echo '<tr>';
        for ($i = 0; $i < count($columnNames); $i++) {
            echo '<th>' . $columnNames[$i] . '</th>';
        }
        echo '</tr>';

        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($row as $key => $value) 
            {
            echo '<td>' . $value . '</td>';
            }
            echo '</tr>';
        }
        
        echo '</table>';
        }
echo "</div>";

?>
</body>