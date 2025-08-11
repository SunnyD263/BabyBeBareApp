<!DOCTYPE html>
<html lang="cs">
<head>
    <title>BabyBeBare App</title>
    <meta charset="UTF-8">
    <meta name="author" content="Jan Sonbol" />
    <meta name="description" content="BabyBeBare App" />
    <link rel="stylesheet" type="text/css" href="css/style.css" />
    <script
  src="https://code.jquery.com/jquery-3.7.1.min.js"
  integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
  crossorigin="anonymous"></script>
</head>
<body>
    <header>
        <h1>BabyBeBare App</h1>
        <?php require 'navigation.php'; ?>
        <img src="images/logo.jpg" class="responsive"/>
    </header> 
<?php 
session_start();
if (isset($_SESSION)) {session_destroy();}
?>
</body>