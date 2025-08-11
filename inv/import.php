<!DOCTYPE html>
<html lang="cs">
<head>
    <title>Import inventury</title>
    <meta charset="UTF-8">
    <meta name="author" content="Jan Sonbol" />
    <meta name="description" content="Import" />
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
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;

session_start();
require '../SQLconn.php';
gc_enable();

$fileUrl = "https://www.babybebare.cz/export/products.xls?patternId=34&partnerId=3&hash=d3ee0093e7699b3c9f6dd88a6a19c048528f3900ad03510f3ff0ea3be9186846";

// Stáhnout soubor
$fileContent = file_get_contents($fileUrl);
if ($fileContent === false) {
    echo '<span class="ErrorMsg">Nepodařilo se stáhnout soubor z URL.</span>';
    exit;
}

// Uložení do dočasného souboru
$tempFile = tempnam(sys_get_temp_dir(), 'products_') . '.xls';
file_put_contents($tempFile, $fileContent);

// Zkontrolujte MIME typ
$mime = mime_content_type($tempFile);

if ($mime == 'application/vnd.ms-excel' || $mime == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
    $reader = $mime == 'application/vnd.ms-excel' ? new Xls() : new Xlsx();
} else {
    echo '<span class="ErrorMsg">Nepodporovaný formát souboru.</span>';
    exit;
}

$reader->setReadDataOnly(true);

try {
    $spreadsheet = $reader->load($tempFile);
    $worksheet = $spreadsheet->getActiveSheet();

    // Získání informací o tabulce
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

    // Inicializace pole pro uložení dat
    $data = [];

    // Načítání dat z tabulky
    for ($row = 2; $row <= $highestRow; ++$row) {
        for ($col = 1; $col <= $highestColumnIndex; ++$col) {
            $data[$row][$col] = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
        }
    }

    if ($row > 2) {
        if (!isset($Connection)) {$Connection = PDOConnect::getInstance('f181433');}
        $SQL = 'DROP TABLE IF EXISTS inventura;';
        $stmt = $Connection->execute($SQL);
        $SQL = 'CREATE TABLE inventura
                (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255),
                    ean VARCHAR(18),
                    productNumber VARCHAR(50),
                    barva VARCHAR(50),
                    velikost INT,
                    stock INT
                );';
        $stmt = $Connection->execute($SQL);

        $dataBatch = [];
        $batchSize = 1000; // Velikost dávky
        $Records = $data; 
        $Counter = 0;  

        for ($row = 2; $row <= $highestRow; ++$row) {
            $rowData = [];
            for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                $rowData[] = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
            }

            // Přidání řádku do dávky
            $dataBatch[] = [
                'name' => $rowData[2] ?? '',
                'ean' => $rowData[3] ?? '',
                'productNumber' => $rowData[4] ?? '',
                'barva' => $rowData[5] ?? '',
                'velikost' => $rowData[6] ?? 0,
                'stock' => $rowData[7] ?? 0
            ];

            if (count($dataBatch) >= $batchSize) {
                $values = [];
                foreach ($dataBatch as $InvRec) {
                    $values[] = sprintf(
                        "(NULL, '%s', '%s', '%s', '%s', %d, %d)",
                        addslashes($InvRec["name"]),
                        addslashes($InvRec["ean"]),
                        addslashes($InvRec["productNumber"]),
                        addslashes($InvRec["barva"]),
                        (int)$InvRec["velikost"],
                        (int)$InvRec["stock"]
                    );
                }
                $sql = "INSERT INTO inventura (id, name, ean, productNumber, barva, velikost, stock) VALUES " . implode(", ", $values);
                $Connection->execute($sql);
                $dataBatch = [];
                unset($values);
                gc_collect_cycles();
            }
        }

        if (!empty($dataBatch)) {
            $values = [];
            foreach ($dataBatch as $InvRec) {
                $values[] = sprintf(
                    "(NULL, '%s', '%s', '%s', '%s', %d, %d)",
                    addslashes($InvRec["name"]),
                    addslashes($InvRec["ean"]),
                    addslashes($InvRec["productNumber"]),
                    addslashes($InvRec["barva"]),
                    (int)$InvRec["velikost"],
                    (int)$InvRec["stock"]
                );
            }
            $sql = "INSERT INTO inventura (id, name, ean, productNumber, barva, velikost, stock) VALUES " . implode(", ", $values);
            $Connection->execute($sql);
            $dataBatch = [];
            unset($values);
            gc_collect_cycles();
        }

        $SQL = "SELECT `ID` FROM `inventura`";
        $stmt = $Connection->select($SQL);
        $count = $stmt['count'];
        echo '<span class="DoneMsg">'. $count . ' záznamů bylo nahráno</span>';
    } else {
        echo '<span class="ErrorMsg">Soubor je prázdný nebo ve špatném formátu.</span>';
    }
} catch (Exception $e) {
    echo '<span class="ErrorMsg">Chyba při načítání souboru: ' . htmlspecialchars($e->getMessage()) . '</span>';
}

session_destroy();


?>
</body>