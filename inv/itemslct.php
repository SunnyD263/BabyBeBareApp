<!DOCTYPE html>
<html lang="cs">

<head>
    <title>Výběr produktu</title>
    <meta charset="UTF-8">
    <meta name="author" content="Jan Sonbol" />
    <meta name="description" content="Výběr produktu" />
    <link rel="stylesheet" type="text/css" href="../css/inventura.css" />
    <link rel="stylesheet" type="text/css" href="../css/style.css" />
    <script
        src="https://code.jquery.com/jquery-3.7.1.slim.js"
        integrity="sha256-UgvvN8vBkgO0luPSUl2s8TIlOSYRoGFAX4jlCIm9Adc="
        crossorigin="anonymous">  
    </script>
</head>
<body>
    <header>
        <h1>BabyBeBare App</h1>
        <?php require '../navigation.php'; ?>
    </header>
    <br>
<?php
session_start();
require '../SQLconn.php';

If ($_SERVER["REQUEST_METHOD"] == "GET")
{ 
//Open forms
if(isset($_GET["SelectID"]))
    {
    $_SESSION['ID']=$_GET["SelectID"];
    unset($_SESSION['EAN']);           
    header("Location: scan.php?Select=");    
    exit();
    }
else
    {
    Return_main(); 
    }
}

/******************************************************************************************************************************************************************************/
function Return_main() 
{

    if (isset($_SESSION["Error"])) 
    {   
        switch ($_SESSION["Error"]) 
        {
            case "501":
                echo '<span class="DoneMsg">Záznam byl přidán do databáze.</span>';;
                break;
            case "Scanned":
                echo '<span class="ErrorMsg">Databáze již obsahuje záznam o příjmu tohoto balíku.</span>';
                break;
            case "502":
                echo '<span class="WarningMsg">Špatný nebo chybějící status.</span>';
                break;
            case "503":
                echo '<span class="ErrorMsg">Balík nená avizován pro PMI, naskenujte do -> Balík mimo systém" .</span>';
                break;
            case "BadFormat":
                echo '<span class="ErrorMsg">Špatný formát čísla balíku.</span>';
                break;
        }        
    unset($_SESSION["Error"]);
    }

/******************************************************************************************************************************************************************************/
echo "<div class='TWOtable'>";
    echo "<div class='TWOtableColumn'>";
if (!isset($Connection)) 
    {$Connection = PDOConnect::getInstance('f181433');}

    $SQL = "SELECT `ID`, `name`, `ean`, `productNumber`, `barva`, `velikost` FROM `inventura` WHERE (`ean` = :EAN) ORDER BY `velikost`";
    $params = array(':EAN' =>  $_SESSION['EAN']);
    $stmt = $Connection->select($SQL, $params);

    $count = $stmt['count'];    
    echo "Počet záznamů: " . $count . "<br>";
    if ($count !== false || $count !== null || $count !== 0)
        {
        $rows = $stmt['rows'];    
        $columnNames = ['ID','Produkt','EAN','ProdNum','Barva','Vel.'];
        echo '<table border="2" cellspacing="1" cellpadding="5">';
        echo '<tr>';
        for ($i = 0; $i < count($columnNames); $i++) {
            echo '<th>' . $columnNames[$i] . '</th>';
        }
        echo '</tr>';

        foreach ($rows as $row) {
            $ButtonID = $row["ID"];
            echo '<tr>';
            foreach ($row as $key => $value) 
            {
            echo '<td>' . $value . '</td>';
            }
            echo    "<td>";
            echo    "<form method='GET'>";
            echo    "<button type='submit' name='SelectID' id='SelectID' value='".$ButtonID."'>Vybrat</button>";
            echo    "</form>";
            echo    "</td>";    
            echo '</tr>';
        }
        
        echo '</table>';
        }
    echo "</div>";
echo "</div>";

}
?>
</body>