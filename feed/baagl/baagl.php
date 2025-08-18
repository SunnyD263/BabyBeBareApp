<!DOCTYPE html>
<html lang="cs">
<head>
    <title>Import Baagl</title>
    <meta charset="UTF-8">
    <meta name="author" content="Jan Sonbol" />
    <meta name="description" content="Import" />
    <link rel="stylesheet" type="text/css" href="../../css/inventura.css" />
    <link rel="stylesheet" type="text/css" href="../../css/style.css" />
    <script
  src="https://code.jquery.com/jquery-3.7.1.min.js"
  integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
  crossorigin="anonymous"></script>
</head>
<body>
    <header>
        <h1>BabyBeBare App</h1>
        <?php require '../../navigation.php'; ?>
        <?php require '../../SQLconn.php'; ?>
        <?php require 'category.php'; ?>
        <?php require 'price.php'; ?>
    </header>
<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['source'])){
        $source = $_POST['source'];
    };

 switch($source)
 {  
    case 'import':
        $sendConstant = 'Baagl_import';
        $url = 'https://xml.pg.cz/cs/token/PIdQAFfp3RWHs3BkHryIOpfE3bftFkqR';
        $xml = simplexml_load_file($url);
        break;
    case 'update':
        $sendConstant = 'Baagl_update';
        $url = 'https://xml.pg.cz/cs/nesklad/token/PIdQAFfp3RWHs3BkHryIOpfE3bftFkqR';
        $xml = simplexml_load_file($url);
        break;
    case 'missing' :
        $url = 'https://xml.pg.cz/cs/nesklad/token/PIdQAFfp3RWHs3BkHryIOpfE3bftFkqR';
        $xmlContent = file_get_contents($url);
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xmlContent);
        $xpath = new DOMXPath($dom);

        $filePath = 'C:\\feed\\missing_product.txt';

        if (file_exists($filePath)) {
            $missingCodes = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($xpath->query('//item') as $itemNode) {
                $regCislo = $itemNode->getElementsByTagName('registracni_cislo')->item(0)->nodeValue ?? '';
                if (!in_array($regCislo, $missingCodes)) {
                    $itemNode->parentNode->removeChild($itemNode);
                }
            }

            // převeď zpět na SimpleXML
            $xml = simplexml_load_string($dom->saveXML());

            // smaž soubor
            unlink($filePath);   
        } else {
            echo "Soubor $filePath neexistuje.";
            $missingCodes = []; // fallback
            exit;
        }
     break;

     default:
        $sendConstant = 'Baagl_pricing';         
        $url = 'https://xml.pg.cz/cs/nesklad/token/PIdQAFfp3RWHs3BkHryIOpfE3bftFkqR';
        $xml = simplexml_load_file($url);
        $priceConvert = (float)str_replace(',', '.', (string)$source);
        break;
    }

$logFile = __DIR__ . '\price.log';
file_put_contents($logFile, '');

    if ($xml === false) {
        die("Chyba při načítání XML");
    }

    if (!isset($Connection)) {$Connection = PDOConnect::getInstance('import');}

    $dropTableSQL = "DROP TABLE IF EXISTS baagl";

    $createTableSQL = 
        "CREATE TABLE baagl (
        id INT AUTO_INCREMENT PRIMARY KEY,
        skupinaID VARCHAR(50),
        skupina VARCHAR(50),
        skupinaZbozi VARCHAR(50),
        code VARCHAR (20),
        catId VARCHAR(50),
        catName VARCHAR(255),
        ean VARCHAR(50),
        nazev TEXT,
        popis TEXT,
        sirka DECIMAL(10,1),
        vyska DECIMAL(10,1),
        hloubka DECIMAL(10,1),
        barva VARCHAR(50),
        baleni VARCHAR(50),
        material VARCHAR(50),
        hmotnost DECIMAL(10,2),
        nosnost DECIMAL(10,1),
        uom VARCHAR(10),
        stav INT,
        stav_po_doplneni INT,
        dph INT,
        mena VARCHAR(10),
        cena DECIMAL(10,2),
        nakupni_cena DECIMAL(10,2),
        dmoc_cena DECIMAL(10,2),
        sleva DECIMAL(10,2),
        obrazek1 TEXT,
        obrazek2 TEXT,
        obrazek3 TEXT,
        obrazek4 TEXT,
        obrazek5 TEXT,
        obrazek6 TEXT,
        obrazek7 TEXT,
        obrazek8 TEXT,
        obrazek9 TEXT,
        obrazek10 TEXT,
        obrazek11 TEXT,
        obrazek12 TEXT,
        obrazek13 TEXT,
        obrazek14 TEXT,
        obrazek15 TEXT,
        obrazek16 TEXT,
        obrazek17 TEXT,
        obrazek18 TEXT,
        obrazek19 TEXT,
        obrazek20 TEXT
    )";

    $Connection->execute($dropTableSQL);
    $Connection->execute($createTableSQL);

    $counter = 0;
    $suffixes = ['-SK', '-EN', '-DE'];
    $blacklistSkupin=['024','043','130','132','133','145','146','147','153','154','169'];
    $ignoredRegNums = file('ignore_regnum.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($xml->items->item as $item) 
    {
        $skupina = null;
        $regnum = (string)mb_strtoupper($item->registracni_cislo, 'UTF-8');
        $extId = (string) $item->skupzbo;

        if (in_array($extId, $blacklistSkupin, true)) {
            continue;
        }

        if (in_array($regnum, $ignoredRegNums, true)) {
        // přeskoč zpracování
        continue;
        }

        foreach ($suffixes as $suffix) {
            if (mb_strpos($regnum, $suffix) !== false) {
                continue 2; // přeskočí celý foreach ($xml->items->item ...)
            }
        }

        foreach ($item->skupiny_zbozi as $skupiny_zbozi) {
            if (isset($skupiny_zbozi->skupina[0])) {
                foreach($skupiny_zbozi->skupina as $skupinaCheck)
                if ((string)$skupinaCheck->primary === 'true') {
                    $skupina = $skupinaCheck;
                    break;
                }
            } else {
                $missId = new SimpleXMLElement('<skupina></skupina>');
                $missId->addChild('id', '999');
                $title = $xml->addChild('title');
                $title->addAttribute('primary', 'true');
                $skupina = $missId;
            }
        }

        if ($skupina == null) {
        echo "Skupina pro zboží " . $item->nazev . "- ExtId: " . $extId . " - Code: " . $regnum . " nenalezena.<br>";
            continue;
        }
        // Načtení obrázků a doplnění na 9 slotů
        $images = [];
        if (isset($item->obrazky->obr)) {
            foreach ($item->obrazky->obr as $obr) {
                $images[] = (string)$obr;
            }
        }
        
        // doplnění na 10 slotů
        while (count($images) < 21) {
            $images[] = null;
        }

        
        //pokud neobsahuje nenaiportuje se
        $category = getCategoryId('Baagl', $extId, $item->nazev);


        if ($category == null) {
            echo "Kategorie pro zboží " . $item->nazev . "- ExtId: " . $extId . " - Code: " . $regnum . " nenalezena.<br>";
            continue;
        }

       

        switch($item->dph){
            case 'high': 
                $dph=21;
                break;
            case 'low':
                $dph=12;
                break;
            default:
                $dph=0;
        }

if(isset($priceConvert) == false) {
$priceConvert = 1.45;
}

//$productPrices = getPrice($item->nakupni_cena,$dph, $priceConvert);

$cena = round((float)str_replace(',', '.', (string)$item->nakupni_cena) * 1.45,0);
$logLine = date('Y-m-d H:i:s') . " | Zboží - " . (string)$item->nazev .
           " Cena - " . $cena .
           " => Doporučená cena - " . (string)$item->dmoc_cena  . PHP_EOL;
file_put_contents($logFile, $logLine, FILE_APPEND);


        $raw = (string)$item->nosnost;
        preg_match('/[\d,.]+/', $raw, $matches);

        $params = [
            (string)$skupina->id,
            (string)$skupina->title,
            (string)$extId,  
            (string)$regnum,        
            (string)$category["shoptet_id"],
            (string)$category["cat_name"],
            (string)$item->ean,
            (string)$item->nazev,
            (string)$item->popis,
            (float)str_replace(',', '.', (string)$item->sirka),
            (float)str_replace(',', '.', (string)$item->vyska),
            (float)str_replace(',', '.', (string)$item->hloubka),
            (string)$item->barva,
            (string)$item->baleni,
            (string)$item->material,
            (float)str_replace(',', '.', (string)$item->hmotnost),
            (float)$number = isset($matches[0]) ? (float)str_replace(',', '.', $matches[0]) : 0.0,
            (string)trim((string)$item->merna_jednotka) ?: 'ks',
            (int)$item->stav,
            (int)$item->stav_po_doplneni,
            (int)$dph,
            (string)$item->mena,
            (float) $cena,
            (float)str_replace(',', '.', (string)$item->nakupni_cena),
            (float)str_replace(',', '.', (string)$item->dmoc_cena),
            (float)str_replace(',', '.', (string)$item->sleva),
            $images[0], $images[1], $images[2], $images[3], $images[4],
            $images[5], $images[6], $images[7], $images[8], $images[9],
            $images[10], $images[11], $images[12], $images[13], $images[14],
            $images[15], $images[16], $images[17], $images[18], $images[19]
        ];

        $query = "
        INSERT INTO baagl (
            skupinaID, skupina,
            skupinaZbozi,code, catId, catName, ean, nazev, popis, sirka, vyska, hloubka,
            barva, baleni, material, hmotnost, nosnost, uom,
            stav, stav_po_doplneni, dph, mena, cena,
            nakupni_cena, dmoc_cena, sleva,
            obrazek1, obrazek2, obrazek3, obrazek4, obrazek5,
            obrazek6, obrazek7, obrazek8, obrazek9, obrazek10,
            obrazek11, obrazek12, obrazek13, obrazek14, obrazek15,
            obrazek16, obrazek17, obrazek18, obrazek19, obrazek20      
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $result = $Connection->execute($query, $params);
    $counter++;
    };
};
echo "Import " . $counter . " položek dokončen.<br>";
unset($allowedIds,$Connection,$dph,$category,$createTableSQL,$dropTableSQL,$images,
      $skupinaCheck,$counter,$url,$xml,$result,$query,$params,$raw,$skupina,$title,$cena,$logFile, $logLine,
      $obr,$item,$ignoredRegNums,$extId,$skupiny_zbozi,$blacklistSkupin,$regnum,$missId,$priceConvert,$suffix);

?>

<form id="redirectForm" action="baagl_convertor.php" method="post">
    <input type="hidden" name="source" value="<?php echo htmlspecialchars($sendConstant, ENT_QUOTES); ?>">
</form>
<script>
    document.getElementById('redirectForm').submit();
</script>
</body>