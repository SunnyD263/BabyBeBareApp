<!DOCTYPE html>
<html lang="cs">

<head>
    <title>Skenování invetury</title>
    <meta charset="UTF-8">
    <meta name="author" content="Jan Sonbol" />
    <meta name="description" content="Skenování" />
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
if (isset($_GET["Input"])) 
    {  
    GetPNorRef($_GET["Input"]);  
    header("Location: scan.php");   
    exit();           
    }
elseif(isset($_GET["DeleteID"]))
    {
    $ID=$_GET["DeleteID"];
    if (!isset($Connection)) {$Connection = PDOConnect::getInstance('f181433');}
    $SQL = "DELETE FROM scan WHERE ID = :id";
    $stmt = $Connection->execute($SQL, ['id' => $ID]);            
    header("Location: scan.php");    
    exit();
    }
elseif(isset($_GET["DeleteAll"]))
    {
    if (!isset($Connection)) {$Connection = PDOConnect::getInstance('f181433');}
    $SQL = "DELETE FROM scan";
    $stmt = $Connection->execute($SQL);            
    header("Location: scan.php");    
    exit();
    }
elseif(isset($_GET["Select"]))
    {
    if (!isset($Connection)){$Connection =  PDOConnect::getInstance('f181433');}    
    $SQL = "SELECT `name`, `ean`, `productNumber`, `barva`, `velikost` FROM `inventura` WHERE (`ID` = :ID)";
    $params = array(':ID' => $_SESSION['ID']);
    $stmt = $Connection->select($SQL, $params);        
    $data = array('name' => $stmt["rows"][0]["name"], 'ean' => $stmt["rows"][0]["ean"],'productNumber' => $stmt["rows"][0]["productNumber"], 'barva' => $stmt["rows"][0]["barva"], 'velikost' => $stmt["rows"][0]["velikost"],'stock' => 1,'datum' => date('Y-m-d H:i:s') );
    $Connection->insert("scan", $data);
    unset($_SESSION['ID']);
    header("Location: scan.php");    
    exit();
    }
else
    {
    Return_main(); 
    }
}

/******************************************************************************************************************************************************************************/
function GetPNorRef($input) {
    $Input = trim(strval($input));
    if (strlen($Input) < 19)
        {
        if (!isset($Connection)){$Connection = PDOConnect::getInstance('f181433');}
        $SQL = "SELECT `name`, `ean`, `productNumber`, `barva`, `velikost` FROM `inventura` WHERE (`ean` = :EAN)";
        $params = array(':EAN' => $Input);
        $stmt = $Connection->select($SQL, $params);
        $count = $stmt['count'];
        if ($count === false || $count === null || $count === 0)
            {
            $data = array('name' => 'Unknown', 'ean' => $Input,'productNumber' => 'Unknown', 'barva' => 'Unknown', 'velikost' => 'Unknown','stock' => 1,'datum' => date('Y-m-d H:i:s') );
            $Connection->insert("scan", $data);
            }
        elseif ($count === 1)
            {
            $data = array('name' => $stmt["rows"][0]["name"], 'ean' => $stmt["rows"][0]["ean"],'productNumber' => $stmt["rows"][0]["productNumber"], 'barva' => $stmt["rows"][0]["barva"], 'velikost' => $stmt["rows"][0]["velikost"],'stock' => 1,'datum' => date('Y-m-d H:i:s') );
            $Connection->insert("scan", $data);
            }
        else  
            {
            $_SESSION['EAN'] = $Input;
            header("Location: itemslct.php");      
            exit();      
            }
        }
    else
        {
        $_SESSION["Error"] = 'BadFormat';
        header("Location: scan.php");
        exit();
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
                echo '<span class="ErrorMsg">Špatný formát EAN.</span>';
                break;
        }        
    unset($_SESSION["Error"]);
    }

/******************************************************************************************************************************************************************************/
echo "<fieldset>";
echo "<legend>Naskenujte:</legend>";
echo "<form  method='get'>";
echo "<label for='Input' id='Inplbl'>Naskenujte EAN:</label><br>";
echo "<input type='text' id='Input' name='Input' autofocus><br><br>";
echo "<input type='submit' value='Potvrdit'>";
echo "</div>";
echo "<div>"; 
echo "<br>";
echo "<form method='GET' onsubmit='return confirmDelete();'>"; 
echo "<button type='submit' name='DeleteAll' id='DeleteAll' value='Smazat vše'>Smazat vše</button>";
echo "</form>";
echo "<script type='text/javascript'>function confirmDelete() {return confirm('Opravdu chcete smazat všechny záznamy?');}</script>";
echo "</div>";
echo "</fieldset>";
echo "<div>";
if (!isset($Connection)) {$Connection = PDOConnect::getInstance('f181433');}

    $SQL = "SELECT `ID`, `name`, `ean`, `productNumber`, `barva`, `velikost`, `stock`, `datum` FROM `scan` ORDER BY `ID` DESC";
    $stmt = $Connection->select($SQL);

    $count = $stmt['count'];    
    echo "Počet záznamů: " . $count . "<br>";
    if ($count !== false || $count !== null || $count !== 0)
        {
        $rows = $stmt['rows'];    
        $columnNames = ['ID','Produkt','EAN','ProdNum','Barva','Vel.','Počet','Datum'];
        echo '<table border="1" cellspacing="1" cellpadding="3">';
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
            echo    "<button type='submit' name='DeleteID' id='DeleteID' value='".$ButtonID."'>Smazat</button>";
            echo    "</form>";
            echo    "</td>";    
            echo '</tr>';
        }        
        echo '</table>';
        }
echo "</div>";

}
?>
</body>