<nav>
    <ul>
        <li>
            <a href="#">Rezervace</a>
            <ul>
                <li><a href="/BabyBeBareApp/rezervace/import.php">Přehled rezervací</a></li>
                <li><a href="/BabyBeBareApp/rezervace/setup.php">Nastavení rezervaci</a></li>
            </ul>
        </li>
        <li><a href="#">Invenutra</a>
            <ul>
                <li><a href="/BabyBeBareApp/inv/import.php">Import skladové zásoby</a></li>
                <li><a href="/BabyBeBareApp/inv/scan.php">Skenování</a></li>
                <li><a href="/BabyBeBareApp/inv/compare.php">Porovnání</a></li>
            </ul>
        </li>
        <li><a href="#">Feed</a>
            <ul>
                <li><a href="#" onclick="postBaagl(); return false;">Baagl na Shoptet</a></li>
                <li><a href="/BabyBeBareApp/feed/baagl/inbound.php">Naskladnění na Shoptet</a></li>
                <li><a href="/BabyBeBareApp/feed/baagl/missing.php">Dohrání produktu</a></li>
            </ul>
        </li>
        <!-- <li>
            <a href="#">Prodejny</a>
            <ul>
                <li><a href="ShopRec.php?Open=">Příjem prodejny</a></li>
                <li><a href="ShopRecUpload.php">Nahrát prodejny</a></li>
            </ul>
        </li>
        <li>
            <a href="#">NCI</a>
            <ul>
                <li><a href="NCIRec.php?Open=">Příjem VO</a></li>
                <li><a href="NCIRecUpload.php">Nahrát VO</a></li>
            </ul>
        </li>
        <li>
            <a href="#">Vrácené balíky</a>
            <ul>
                <li><a href="ReturnParcel.php?Open=">Vrácené balíky</a></li>
                <li><a href="UnsysParcel.php?Open=">Balíky mimo systém</a></li>
                <li><a href="NonDelivery.php?Open=">Nedoručené balíky-zařízení</a></li>
                <li><a href="TradeIn.php">Trade IN - ECOM</a></li>
                <li><a href="TradeIn_D.php">Trade IN - Shop</a></li>
                <li><a href="SWAP.php">SWAP</a></li>
            </ul>
        </li>
        <li>
            <a href="#">Admin funkce</a>
            <ul>
                <li><a href="#">Problémové balíky</a></li>
                <li><a href="#">DPD chybné balíky</a></li>
                <li><a href="#">Nahrát EAN</a></li>                    
            </ul>                                      
        </li> -->
    </ul>
</nav>
<script>
  function postBaagl() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/BabyBeBareApp/feed/baagl/baagl.php';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'source';
    input.value = 'import';
    form.BabyBeBareAppendChild(input);

    document.body.BabyBeBareAppendChild(form);
    form.submit();
  }
</script>