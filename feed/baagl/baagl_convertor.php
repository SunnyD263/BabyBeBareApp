<!DOCTYPE html>
<html lang="cs">
<head>
    <title>XML Baagl</title>
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
        <?php require 'insert_video.php'; ?>
    </header>
<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['source'])){
        $source = $_POST['source'];
    };

    
    if (!isset($Connection)) {$Connection = PDOConnect::getInstance('import');}

    switch($source) {
        case 'Baagl_inbound':
            $stmt = $Connection->select("SELECT * FROM `baagl_inbound`");
            $targetWH = "Výchozí sklad";
            break;
        case 'Baagl_update':
            $stmt = $Connection->select("SELECT * FROM `baagl`");
            $targetWH = "BAAGL";
            $onlyUpdate = true;
            break;               
        case 'Baagl_import':        
            $stmt = $Connection->select("SELECT * FROM `baagl`");
            $targetWH = "BAAGL";
            break;
        case 'Baagl_pricing':    
            $stmt = $Connection->select("SELECT * FROM `baagl`");    
            $targetWH = "BAAGL";
            $onlyUpdate = true;
            break;
    }
    $count = $stmt['count'] ?? 0;
    $xml = new SimpleXMLElement('<SHOP/>');
    $company = 'BAAGL';
    $counterNew = 0;
    $counterUpd = 0;


    if ($count > 0) {

        $url = 'https://www.babybebare.cz/export/productsComplete.xml?patternId=-5&partnerId=3&hash=0e293cdea7e8d0082ce3c51ceb737307c26ccbf02438a15b1b7d779af9417bf3&manufacturerId=136';

        $shoptet_xml = simplexml_load_file($url);
        if ($shoptet_xml === false) {
        die("Chyba při načítání Shoptet-XML");
        }

        // Naindexuj Shoptet GUID pro rychlé hledání
        $shoptetCODEs = [];
        foreach ($shoptet_xml->SHOPITEM as $item) {
        $shoptetCODEs[] = (string)$item->CODE;
        }

        foreach ($stmt['rows'] as $row) {
            $code = $row["code"];

            if (in_array($code, $shoptetCODEs)) {
                foreach ($shoptet_xml->SHOPITEM as $shopItem) {
                    if ((string)$shopItem->CODE == $code) {
                        $shoptetData = $shopItem;
                        unset($shopItem);
                        break;
                    }
                }
                $whId = 0;
                $changes = false;
                $shortDescription = get_promo($company,(string)$shoptetData->NAME,(string)$shoptetData->CATEGORIES->CATEGORY);
                    $item = $xml->addChild('SHOPITEM');
                    $item->addChild('NAME', htmlspecialchars($shoptetData->NAME));

                    if($shortDescription == null) {
                        $item->addChild('SHORT_DESCRIPTION', htmlspecialchars($shoptetData->SHORT_DESCRIPTION));
                    } else {
                        $item->addChild('SHORT_DESCRIPTION', htmlspecialchars("<p style='text-align: center;'><iframe width='560' height='314' 
                            src=" . $shortDescription ." allowfullscreen='allowfullscreen'></iframe></p>"));
                    };
                    $item->addChild('DESCRIPTION', htmlspecialchars($shoptetData->DESCRIPTION));
                    $item->addChild('MANUFACTURER', htmlspecialchars($shoptetData->MANUFACTURER));
                    $item->addChild('WARRANTY', htmlspecialchars($shoptetData->WARRANTY));
                    $item->addChild('SUPPLIER', htmlspecialchars($shoptetData->SUPPLIER));
                    $item->addChild('ADULT', htmlspecialchars($shoptetData->ADULT));
                    $item->addChild('ITEM_TYPE', htmlspecialchars($shoptetData->ITEM_TYPE));
                    $categories = $item->addChild('CATEGORIES');
                        $category = $categories->addChild('CATEGORY',htmlspecialchars($shoptetData->CATEGORIES->CATEGORY));
                        if (isset($shoptetData->CATEGORIES->DEFAULT_CATEGORY) == false){
                            $defaultCategory = $categories->addChild('DEFAULT_CATEGORY',htmlspecialchars($shoptetData->CATEGORIES->CATEGORY));
                        } else {
                            $defaultCategory = $categories->addChild('DEFAULT_CATEGORY',htmlspecialchars($shoptetData->CATEGORIES->DEFAULT_CATEGORY));
                        }
                    $images = $item->addChild('IMAGES');

                    // První obrázek ze Shoptetu
                    if (!empty($shoptetData->IMAGES->IMAGE[0])) {
                        $images->addChild('IMAGE', htmlspecialchars((string)$shoptetData->IMAGES->IMAGE[0]));
                    }

                    // Další obrázky z databáze (obrazek2 až obrazek20)
                    for ($i = 2; $i <= 20; $i++) {
                        $key = 'obrazek' . $i;
                        if (!empty($row[$key])) {
                            $images->addChild('IMAGE', htmlspecialchars($row[$key]));
                        }
                    }
                    if(isset($shoptetData->INFORMATION_PARAMETERS->INFORMATION_PARAMETER )){
                    $info_parameters = $item->addChild('INFORMATION_PARAMETERS');
                    foreach ($shoptetData->INFORMATION_PARAMETERS->INFORMATION_PARAMETER as $info) {
                            $info_parameter = $info_parameters->addChild('INFORMATION_PARAMETER');
                            $info_parameter->addChild('NAME', $info->NAME);
                            $info_parameter->addChild('VALUE', $info->VALUE);
                        }
                    }
                    $item->addChild('VISIBILITY', htmlspecialchars($shoptetData->VISIBILITY));
                    $item->addChild('SEO_TITLE', htmlspecialchars($shoptetData->SEO_TITLE));
                    $item->addChild('ALLOWS_IPLATBA', $shoptetData->ALLOWS_IPLATBA);
                    $item->addChild('ALLOWS_PAY_ONLINE', $shoptetData->ALLOWS_PAY_ONLINE);
                    $item->addChild('HEUREKA_CATEGORY_ID', $shoptetData->HEUREKA_CATEGORY_ID);
                    $item->addChild('ZBOZI_CATEGORY_ID', $shoptetData->ZBOZI_CATEGORY_ID);
                    $item->addChild('GOOGLE_CATEGORY_ID', $shoptetData->GOOGLE_CATEGORY_ID);
                    $item->addChild('GLAMI_CATEGORY_ID', $shoptetData->GLAMI_CATEGORY_ID);
                    $item->addChild('FREE_SHIPPING', $shoptetData->FREE_SHIPPING);
                    $item->addChild('FREE_BILLING', $shoptetData->FREE_BILLING);
                    $item->addChild('UNIT', htmlspecialchars($shoptetData->UNIT));
                    $item->addChild('CODE', htmlspecialchars($shoptetData->CODE));
                    $item->addChild('EAN', htmlspecialchars($shoptetData->EAN));

                    $logistic = $item->addChild('LOGISTIC');
                    if(isset($shoptetData->LOGISTIC->DEPTH)) {
                        $logistic->addChild('DEPTH', htmlspecialchars($shoptetData->LOGISTIC->DEPTH));
                        };
                    if(isset($shoptetData->LOGISTIC->WIDTH)) {
                        $logistic->addChild('WIDTH', htmlspecialchars($shoptetData->LOGISTIC->WIDTH));
                        }
                    if(isset($shoptetData->LOGISTIC->HEIGHT)) {
                        $logistic->addChild('HEIGHT', htmlspecialchars($shoptetData->LOGISTIC->HEIGHT));
                        }
                    if(isset($shoptetData->LOGISTIC->WEIGHT)) {
                        $logistic->addChild('WEIGHT', htmlspecialchars($shoptetData->LOGISTIC->WEIGHT));
                        }
                        $atypical = $item->addChild('ATYPICAL_PRODUCT');
                        $atypical->addChild('ATYPICAL_SHIPPING', htmlspecialchars($shoptetData->ATYPICAL_PRODUCT->ATYPICAL_SHIPPING));
                        $atypical->addChild('ATYPICAL_BILLING', htmlspecialchars($shoptetData->ATYPICAL_PRODUCT->ATYPICAL_BILLING));

                    $item->addChild('CURRENCY', htmlspecialchars($shoptetData->CURRENCY));
                    $item->addChild('VAT', htmlspecialchars($shoptetData->VAT));
                    
                    if ((float)$shoptetData->STANDARD_PRICE !== (float)$row['cena']) {
                        $item->addChild('STANDARD_PRICE', htmlspecialchars((float)$row['cena']));                    
                        $item->addChild('PRICE_VAT', htmlspecialchars((float)$row['cena']));
                    } else {
                        $item->addChild('PRICE_VAT', htmlspecialchars($shoptetData->PRICE_VAT));
                        $item->addChild('STANDARD_PRICE', htmlspecialchars($shoptetData->PRICE_VAT));
                    }
                    if ((float)$shoptetData->PURCHASE_PRICE !== (float)$row['nakupni_cena']) {
                        $item->addChild('PURCHASE_PRICE', htmlspecialchars((float)$row['nakupni_cena']));
                    } else {
                        $item->addChild('PURCHASE_PRICE', htmlspecialchars($shoptetData->PURCHASE_PRICE));
                    }

                    $stock = $item->addChild('STOCK');
                    $warehouses = $stock->addChild('WAREHOUSES');
                    foreach ($shoptetData->STOCK->WAREHOUSES->WAREHOUSE as $whItem) {
                        $warehouse = $warehouses->addChild('WAREHOUSE');
                        $warehouse->addChild('NAME',htmlspecialchars($whItem->NAME));
                        if($targetWH == $whItem->NAME) {
                            if ($whItem->NAME = 'Výchozí sklad' && $targetWH == 'Výchozí sklad' ) {
                                $warehouse->addChild('VALUE',$shoptetData->STOCK->WAREHOUSES->WAREHOUSE[$whId]->VALUE + $row['stav']); 
                            } else {
                                $warehouse->addChild('VALUE', $row['stav']); 
                            }
                        } else {
                            $warehouse->addChild('VALUE',$shoptetData->STOCK->WAREHOUSES->WAREHOUSE[$whId]->VALUE);              
                        }
                        $warehouse->addChild('LOCATION',htmlspecialchars($whItem->LOCATION)); // prázdné
                        $whId++;
                    }

                    $stock->addChild('MINIMAL_AMOUNT',htmlspecialchars($shoptetData->STOCK->MINIMAL_AMOUNT));
                    $stock->addChild('MAXIMAL_AMOUNT',htmlspecialchars($shoptetData->STOCK->MAXIMAL_AMOUNT));
                    if ($targetWH == 'Výchozí sklad'){
                        $amountCentralWH = (int)$row['stav'] + (int)$shoptetData->STOCK->WAREHOUSES->WAREHOUSE[0]->VALUE;
                    } else {
                        $amountCentralWH = (int)$shoptetData->STOCK->WAREHOUSES->WAREHOUSE[0]->VALUE; 
                    }

                    if ($amountCentralWH > 0 ){
                        $item->addChild('AVAILABILITY_IN_STOCK', 'Skladem na prodejně');            
                        } else {
                        $item->addChild('AVAILABILITY_IN_STOCK', 'Skladem ve skladu e-shopu');
                        }


                    $item->addChild('AVAILABILITY_OUT_OF_STOCK', 'Momentálně nedostupné');
                    $item->addChild('VISIBLE', $shoptetData->VISIBLE);
                    $item->addChild('PRODUCT_NUMBER', htmlspecialchars($shoptetData->PRODUCT_NUMBER));
                    $item->addChild('FIRMY_CZ', $shoptetData->FIRMY_CZ);
                    $item->addChild('HEUREKA_HIDDEN', $shoptetData->HEUREKA_HIDDEN);
                    $item->addChild('HEUREKA_CART_HIDDEN', $shoptetData->HEUREKA_CART_HIDDEN);
                    $item->addChild('ZBOZI_HIDDEN', $shoptetData->ZBOZI_HIDDEN);
                    $item->addChild('ARUKERESO_HIDDEN', $shoptetData->ARUKERESO_HIDDEN);
                    $item->addChild('ARUKERESO_MARKETPLACE_HIDDEN', $shoptetData->ARUKERESO_MARKETPLACE_HIDDEN);
                    $item->addChild('DECIMAL_COUNT',$shoptetData->DECIMAL_COUNT);
                    $item->addChild('NEGATIVE_AMOUNT', $shoptetData->NEGATIVE_AMOUNT);
                    $item->addChild('PRICE_RATIO', $shoptetData->PRICE_RATIO);
                    $item->addChild('MIN_PRICE_RATIO', $shoptetData->MIN_PRICE_RATIO);
                    $item->addChild('ACTION_PRICE',$shoptetData->ACTION_PRICE);        
                    $item->addChild('ACTION_PRICE_FROM',$shoptetData->ACTION_PRICE_FROM);                
                    $item->addChild('ACTION_PRICE_UNTIL',$shoptetData->ACTION_PRICE_UNTIL);
                    $item->addChild('APPLY_LOYALTY_DISCOUNT', $shoptetData->APPLY_LOYALTY_DISCOUNT);
                    $item->addChild('APPLY_VOLUME_DISCOUNT', $shoptetData->APPLY_VOLUME_DISCOUNT);
                    $item->addChild('APPLY_QUANTITY_DISCOUNT', $shoptetData->APPLY_QUANTITY_DISCOUNT);
                    $item->addChild('APPLY_DISCOUNT_COUPON', $shoptetData->APPLY_DISCOUNT_COUPON);
                    $counterUpd++;

                unset($amountCentralWH,$atypical,$categories,$category,$defaultCategory,$i,$images,$info,$info_parameter,$code,
                $info_parameters,$item,$key,$logistic,$shopItem,$shoptetData,$stock,$unit,$whId, $warehouse,$warehouseItem,$warehouses,$whItem);
                }

            else {
                
        if (isset($onlyUpdate)){
            continue;
        }
            if($targetWH == 'Výchozí sklad') {
                echo "Tento produkt " . $row["code"] . " - " .  $row["nazev"] . " chybí v databazi shoptet a je třeba ho do dohrát.<br>";
                echo "Je potřeba nejdřív produkt nahrát a pak znovu pokračovat.<br>";  
                $filePath = 'C:\\feed\\missing_product.txt';
                $line = $row["code"] . PHP_EOL;

                file_put_contents($filePath, $line, FILE_APPEND | LOCK_EX);                               
            } else {
                $item = $xml->addChild('SHOPITEM');
                $item->addChild('NAME', htmlspecialchars($row['nazev']));
                $shortDescription = get_promo($company,$row['nazev'],$row['catName']);
                if($shortDescription == null) {
                    $item->addChild('SHORT_DESCRIPTION', htmlspecialchars(''));
                } else {
                    $item->addChild('SHORT_DESCRIPTION', htmlspecialchars("<p style='text-align: center;'><iframe width='560' height='314' 
                        src=" . $shortDescription ." allowfullscreen='allowfullscreen'></iframe></p>"));
                };

                $item->addChild('DESCRIPTION', htmlspecialchars($row['popis']));
                $item->addChild('MANUFACTURER', htmlspecialchars($company));
                $item->addChild('WARRANTY', '2 roky');
                $item->addChild('SUPPLIER', htmlspecialchars($company));
                $item->addChild('ADULT', 0);
                $item->addChild('ITEM_TYPE', 'product');
                $categories = $item->addChild('CATEGORIES');
                    $category = $categories->addChild('CATEGORY',$row['catName']);
                    $category->addAttribute('id', $row['catId']);
                    $defaultCategory = $categories->addChild('DEFAULT_CATEGORY',$row['catName']);
                    $defaultCategory->addAttribute('id', $row['catId']);

                $images = $item->addChild('IMAGES');

                if (!empty($row['obrazek1'])) {
                    $images->addChild('IMAGE', htmlspecialchars($row['obrazek1']));
                }
                for ($i = 2; $i <= 20; $i++) {
                    $key = 'obrazek' . $i;
                    if (!empty($row[$key])) {
                        $images->addChild('IMAGE', htmlspecialchars($row[$key]));
                    }
                }

                $info_parameters = $item->addChild('INFORMATION_PARAMETERS');
                // Definice hodnot a jednotek
                $dimensions = [
                    'Výška'   => ['value' => $row['vyska'] ?? '', 'unit' => 'cm'],
                    'Šířka'   => ['value' => $row['sirka'] ?? '', 'unit' => 'cm'],
                    'Hloubka' => ['value' => $row['hloubka'] ?? '', 'unit' => 'cm'],
                    'Nosnost' => ['value' => $row['nosnost'] ?? '', 'unit' => 'kg'],
                ];

                foreach ($dimensions as $name => $data) {
                    $value = $data['value'];
                    $unit = $data['unit'];

                    if ($value !== '0.00' && $value !== '0.0' && $value !== '') {
                        $info_parameter = $info_parameters->addChild('INFORMATION_PARAMETER');
                        $info_parameter->addChild('NAME', $name);
                        $info_parameter->addChild('VALUE', $value . ' ' . $unit);
                    }
                }
                if ($row['material'] !== '' ){
                    $info_parameter = $info_parameters->addChild('INFORMATION_PARAMETER');
                    $info_parameter->addChild('NAME', 'Materiál');
                    $info_parameter->addChild('VALUE', $row['material']);
                }
                $item->addChild('VISIBILITY', 'visible');
                $item->addChild('SEO_TITLE', htmlspecialchars($row['nazev']));
                $item->addChild('ALLOWS_IPLATBA', 1);
                $item->addChild('ALLOWS_PAY_ONLINE', 1);
                $item->addChild('UNIT', $row['uom']);
                $item->addChild('CODE', $row['code']);
                $item->addChild('EAN', htmlspecialchars($row['ean']));

                $logistic = $item->addChild('LOGISTIC');
                    $logistic->addChild('WEIGHT', $row['hmotnost']);
                    $logistic->addChild('HEIGHT', $row['vyska']);
                    $logistic->addChild('WIDTH', $row['sirka']);
                    $logistic->addChild('DEPTH', $row['hloubka']);

                    $atypical = $item->addChild('ATYPICAL_PRODUCT');
                    $atypical->addChild('ATYPICAL_SHIPPING', 0);
                    $atypical->addChild('ATYPICAL_BILLING', 0);

                $item->addChild('CURRENCY', $row['mena']);
                $item->addChild('VAT', $row['dph']);
                $item->addChild('PRICE_VAT', $row['cena']);
                $item->addChild('PURCHASE_PRICE', $row['nakupni_cena']);
                $item->addChild('STANDARD_PRICE', $row['cena']);

                $stock = $item->addChild('STOCK');
                    $warehouses = $stock->addChild('WAREHOUSES');
                    $warehouse = $warehouses->addChild('WAREHOUSE');
                    $warehouse->addChild('NAME','Výchozí sklad');
                    $warehouse->addChild('VALUE', 0);
                    $warehouse->addChild('LOCATION'); // prázdné
                    $warehouse = $warehouses->addChild('WAREHOUSE');
                    $warehouse->addChild('NAME',$company);
                    $warehouse->addChild('VALUE', $row['stav']);
                    $warehouse->addChild('LOCATION'); // prázdné
                $stock->addChild('MINIMAL_AMOUNT');
                $stock->addChild('MAXIMAL_AMOUNT');
                $item->addChild('AVAILABILITY_OUT_OF_STOCK', 'Momentálně nedostupné'); 
                $item->addChild('AVAILABILITY_IN_STOCK', 'Skladem ve skladu e-shopu');
                $item->addChild('VISIBLE', 1);
                $item->addChild('PRODUCT_NUMBER', htmlspecialchars($row['code']));
                $item->addChild('FIRMY_CZ', 1);
                $item->addChild('HEUREKA_HIDDEN', 0);
                $item->addChild('HEUREKA_CART_HIDDEN', 0);
                $item->addChild('ZBOZI_HIDDEN', 0);
                $item->addChild('ARUKERESO_HIDDEN', 0);
                $item->addChild('ARUKERESO_MARKETPLACE_HIDDEN', 0);
                $item->addChild('DECIMAL_COUNT', 0);
                $item->addChild('NEGATIVE_AMOUNT', 0);
                $item->addChild('PRICE_RATIO', 1);
                $item->addChild('MIN_PRICE_RATIO', 0);
                $item->addChild('ACTION_PRICE',$row['cena']);        
                $item->addChild('ACTION_PRICE_FROM',htmlspecialchars('1999-01-01'));                
                $item->addChild('ACTION_PRICE_UNTIL',htmlspecialchars('1999-01-01'));
                $item->addChild('APPLY_LOYALTY_DISCOUNT', 1);
                $item->addChild('APPLY_VOLUME_DISCOUNT', 0);
                $item->addChild('APPLY_QUANTITY_DISCOUNT', 1);
                $item->addChild('APPLY_DISCOUNT_COUPON', 0);
                echo "Kategorie - " . $row["catName"] . " pro zboží - " . $row["nazev"] . " - ExtId: " . $row["skupinaZbozi"] . " - Code: " . $row["code"] . " produkt vytvořen.<br>";
                $counterNew++;
                unset($atypical,$categories,$category,$data,$defaultCategory,$dimensions,$i,$images,$info_parameter,$info_parameters,
                $item,$key,$logistic,$name,$stock,$unit,$value,$warehouse,$warehouses);
                }
            }
        }    
        $outputPath = "C:\\feed\\baagl-feed.xml";
        $counter = $counterNew + $counterUpd;
        // Ulož výstupní XML
        if ($counter == 0) {
            if (file_exists($outputPath )) {
                unlink($outputPath );
            } 
            echo "Feed neobsahoval žádný nový produkt<br>";
        } else {
        if ($xml->asXML($outputPath)) {
                echo "Soubor úspěšně uložen do: $outputPath\n <br>";
            } else {
                echo "Nepodařilo se uložit soubor.\n <br>";
            }
        echo "Ve feedu bylo vytvořeno $counterNew nových položek a $counterUpd updatovaných položek.<br>";
        }   
        
    } else {
    echo "Feed neobsahoval žádné záznamy nebo se špatně naimportoval na SQL server.<br>";
    }
unset($Connection,$code,$company,$count,$counter,$counterNew,$counterUpd,$http_response_header,$outputPath,
$row,$shoptetCODEs,$shoptetData,$shoptet_xml,$stmt,$url,$xml,$onlyUpdate);
}
?>
</body>