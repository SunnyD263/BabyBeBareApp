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
                <li><a href="#" id="baagl-shoptet" data-source="import">Baagl na Shoptet</a></li>
                <li><a href="#" id="update-shoptet" data-source="update">Aktualizace Shoptet</a></li>
                <li><a href="/BabyBeBareApp/feed/baagl/inbound.php">Naskladnění na Shoptet</a></li>
                <li><a href="/BabyBeBareApp/feed/baagl/missing.php">Dohrání produktu</a></li>
                <li><a href="/BabyBeBareApp/feed/baagl/price_convertor.php">Změna cen</a></li>
            </ul>
        </li>
    </ul>
</nav>
<script>
window.postBaagl = function postBaagl(sourceValue) {
  let form = document.getElementById('baagl-post-form');
  if (!form) {
    form = document.createElement('form');
    form.id = 'baagl-post-form';
    form.method = 'POST';
    form.action = '/BabyBeBareApp/feed/baagl/baagl.php';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'source';
    form.appendChild(input);

    document.body.appendChild(form);
  }

  // nastavíme aktuální hodnotu podle kliknutého odkazu
  form.querySelector('input[name="source"]').value = sourceValue;

  form.submit();
};

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('#baagl-shoptet, #update-shoptet').forEach(link => {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      const source = this.getAttribute('data-source');
      window.postBaagl(source);
    });
  });
});
</script>