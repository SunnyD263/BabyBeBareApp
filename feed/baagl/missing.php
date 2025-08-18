<!DOCTYPE html>
<html lang="cs">
<head>
    <title>Import Baagl</title>
    <meta charset="UTF-8">
    <meta name="author" content="Jan Sonbol" />
    <meta name="description" content="Dohrát produkt" />
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
    </header>
<form id="autoPostForm" action="/BabyBeBareApp/feed/baagl/baagl.php" method="post" style="display: none;">
    <input type="hidden" name="source" value="missing">
</form>
<script>
    window.addEventListener('DOMContentLoaded', function () {
        document.getElementById('autoPostForm').submit();
    });
</script>
</body>