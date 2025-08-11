<?php
function get_promo($company, $name, $category) {
    $url = __DIR__ . '/insert_video.xml';
    $xml = simplexml_load_file($url);

    foreach ($xml->VIDEO as $item) {
        if ((string)$item->COMPANY == $company && (string)$item->CATEGORY == $category) {
            if ((string)$item->NAME == '') {
                return (string)$item->TAGY;
            }

            if (mb_stripos($name, (string)$item->NAME) !== false) {
                return (string)$item->TAGY;
            }
        }
    }

    return null;
};

?>