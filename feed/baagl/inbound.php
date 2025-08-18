<!DOCTYPE html>
<html lang="cs">
<head>
    <title>Import Baagl</title>
    <meta charset="UTF-8">
    <meta name="author" content="Jan Sonbol" />
    <meta name="description" content="Inbound" />
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
    </header>
<h3>Načíst položky z HTML souboru</h3>
<form method="post" enctype="multipart/form-data">
    <label for="html_file">Vyber HTML soubor:</label><br>
    <input type="file" id="html_file" name="html_file" accept=".html,.htm" required><br><br>
    <input type="submit" value="Načíst">
</form>

<?php

if (!empty($_FILES['html_file']['tmp_name'])) {
    $html = file_get_contents($_FILES['html_file']['tmp_name']);

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $rows = $xpath->query("//table//tr");
    $items = [];
    $sumQuantity = 0;
    $sumPrice = 0;
    $counter=0;

    foreach ($rows as $row) {
        $cells = $row->getElementsByTagName("td");
        if ($cells->length < 6) continue;

        $rawText = trim($cells->item(0)->nodeValue);
        if (!preg_match('/^([^\s]+)\s+-\s+(.*)$/u', $rawText, $matches)) continue;

        $code = (string)mb_strtoupper($matches[1]);
        $name = $matches[2];

        $purchaseText = trim($cells->item(1)->nodeValue);
        $purchaseValue = 0.0;
        $purchaseCurrency = '';

        if (preg_match('/^([\d\s]+,\d{2})\s*([^\d\s]+)$/u', $purchaseText, $match)) {
            $purchaseValue  = (float) str_replace([',', ' '], ['.', ''], $match[1]);
            $purchaseCurrency= $match[2];
        }


        $qtyText = trim($cells->item(3)->nodeValue);
        $qtyValue = 0;
        $qtyUom = '';

        if (preg_match('/^([\d\s]+)\s*([^\d\s]+)$/u', $qtyText, $match)) {
            $qtyValue = (int) str_replace(' ', '', $match[1]);
            $qtyUom = $match[2];
        }

        $taxText = trim($cells->item(4)->nodeValue);
        $tax = 0;

        if (preg_match('/^([\d\s]+)\s*([^\d\s]+)$/u', $taxText, $match)) {
            $tax = (int) str_replace(' ', '', $match[1]);
        }

        $priceText = trim($cells->item(5)->nodeValue);
        $priceValue = 0.0;
        $priceCurrency = '';

        if (preg_match('/^([\d\s]+,\d{2})\s*([^\d\s]+)$/u', $priceText, $match)) {
            $priceValue = (float) str_replace([',', ' '], ['.', ''], $match[1]);
            $priceCurrency = $match[2];
        }

        $items[] = [
            'code' => $code,
            'name' => $name,
            'uom' => $qtyUom,
            'quantity' => $qtyValue,
            'currency' => $priceCurrency,            
            'price' => $priceValue,
            'purchase' =>  $purchaseValue * ((100+$tax) /100)

        ];

        $sumQuantity += $qtyValue;
        $sumPrice += $priceValue;
        $counter++;
    }


if (!isset($Connection)) {$Connection = PDOConnect::getInstance('import');}

$dropTableSQL = "DROP TABLE IF EXISTS baagl_inbound";

$createTableSQL = 
    "CREATE TABLE baagl_inbound (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50),
    nazev TEXT,
    uom VARCHAR(10),
    stav INT,
    mena VARCHAR(10),
    cena DECIMAL(10,2),
    nakupni_cena DECIMAL(10,2)
)";

$Connection->execute($dropTableSQL);
$Connection->execute($createTableSQL);

foreach ($items as $item) {
    $params = [
        (string)$item["code"],
        (string)$item["name"],
        (string)$item["uom"],
        (int)$item["quantity"],
        (string) $item["currency"],
        (float)$item["price"],
        (float)$item["purchase"]

    ];

    $query = "INSERT INTO baagl_inbound (code, nazev, uom, stav, mena, cena, nakupni_cena) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $result = $Connection->execute($query, $params);
};

    // Shrnutí

    echo '<br><form action="baagl_convertor.php" method="post" style="margin-top: 20px;">';
    echo "<button type='submit' name='source' value='Baagl_inbound' class='btn btn-primary'>Spustit generování</button>";
    echo "</form><br>";
    echo "<h2>Souhrn objednávky</h2>";
    echo "<p><strong>Celkem položek:</strong> $counter<br>";
    echo "<strong>Celkové množství:</strong> $sumQuantity ks<br>";
    echo "<strong>Celková cena s DPH:</strong> " . number_format($sumPrice, 2, ',', ' ') . " Kč</p>";

    echo "<button onclick=\"toggleDetails()\">Zobrazit / Skrýt detaily</button>";
    echo "<div id='orderDetails' style='display:none; margin-top: 10px;'>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>";
    echo "<th>Kód</th><th>Název</th><th>Počet</th><th>UOM</th><th>Nákup s DPH</th><th>Prodej s DPH</th><th>Měna</th>";
    echo "</tr>";

    foreach ($items as $item) {
        echo "<tr><td>{$item['code']}</td><td>{$item['name']}</td><td>{$item['quantity']}</td><td>{$item['uom']}</td><td>" . number_format($item['purchase'], 2, ',', ' ') . "</td><td>" . number_format($item['price'], 2, ',', ' ') . "</td><td>{$item['currency']}</td></tr>";
    }

    echo "<tr><td></td><td><strong>Součet</strong></td><td><strong>$sumQuantity</strong></td><td></td><td><strong>" . number_format($sumPrice, 2, ',', ' ') . "</strong></td><td></td></tr>";
    echo "</table></div>";

}
?>

    <script>
    function toggleDetails() {
        var el = document.getElementById("orderDetails");
        el.style.display = (el.style.display === "none") ? "block" : "none";
    }
    </script>
</body>
</html>
