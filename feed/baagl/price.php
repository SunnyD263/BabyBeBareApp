<?php   
   function getPrice ($purchasePrice,$VAT,$salesMargin) {
        $purchasePriceWithoutVAT = $purchasePrice * (100 - $VAT) / 100;
        $sellPriceWithoutVAT =  $purchasePriceWithoutVAT * $salesMargin;
        $sellPrice = round($sellPriceWithoutVAT * (100 + $VAT) /100,0);

        return [
            'purchasePriceWithoutVAT' => (string)$purchasePriceWithoutVAT,
            'purchasePrice' => (string)$purchasePrice,
            'sellPriceWithoutVAT' => (string)$sellPriceWithoutVAT,
            'sellPrice' => (string)$sellPrice
        ];
    }
?>