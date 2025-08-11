<!DOCTYPE html>
<html lang="cs">
<head>
    <title>Import Baagl</title>
    <meta charset="UTF-8">
    <meta name="author" content="Jan Sonbol" />
    <meta name="description" content="Pricing" />
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
<h3>Zadat hodnotu</h3>
<form id="userForm" method="post" onsubmit="postBaagl(event)">
    <label for="user_value">Zadej hodnotu:</label><br>
    <input type="text" id="user_value" name="user_value" required><br><br>
    <input type="submit" value="Odeslat">
</form>

<script>
function postBaagl(event) {
    event.preventDefault(); // zabrání standardnímu odeslání formuláře

    const userValue = document.getElementById('user_value').value;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/app/feed/baagl/baagl.php';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'source';
    input.value = userValue; // pošle hodnotu z pole
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
}
</script>
</body>
</html>