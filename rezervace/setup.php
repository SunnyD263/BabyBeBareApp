<!DOCTYPE html>
<html lang="cs">
<head>
    <title>Nastavení rezervací</title>
    <meta charset="UTF-8">
    <meta name="author" content="Jan Sonbol" />
    <meta name="description" content="setup" />
    <link rel="stylesheet" type="text/css" href="../css/rezervace.css" />
    <link rel="stylesheet" type="text/css" href="../css/style.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
        crossorigin="anonymous"></script>
</head>
<body>
    <header>
        <h1>BabyBeBare App</h1>
        <?php require '../navigation.php'; ?>

    </header>

<?php 
require '../SQLconn.php';
If ($_SERVER["REQUEST_METHOD"] == "POST")
    { 
    if (!isset($Connection)) {$Connection = PDOConnect::getInstance('f181433');}
    $SQL = 'DROP TABLE IF EXISTS rzv_setup;';
    $stmt = $Connection->execute($SQL);
    $SQL = 'CREATE TABLE rzv_setup
            (
            id INT AUTO_INCREMENT PRIMARY KEY,
            day VARCHAR(20) NOT NULL,
            time VARCHAR(5) NOT NULL,
            box BOOLEAN NOT NULL DEFAULT FALSE,
            UNIQUE KEY (day, time)
            );';
    $stmt = $Connection->execute($SQL);

    $days = $_POST['days'];  
    foreach ($days as $day => $times) 
        {
        foreach ($times as $time) 
            {
            // Připravíme data pro vložení
            $data = [
                'day' => $day,
                'time' => $time,
                'box' => true // Checkbox byl zaškrtnut
            ];

            // Použijeme metodu insert z třídy PDOConnect
            $query = "INSERT INTO rzv_setup (day, time, box) VALUES (:day, :time, :box) 
                        ON DUPLICATE KEY UPDATE box = VALUES(box)";
            $result = $Connection->execute($query, $data);
            }
        }
    echo "Data byla úspěšně uložena.";    
    exit();           
    }

/******************************************************************************************************************************************************************************/
if (!isset($Connection)) {$Connection = PDOConnect::getInstance('f181433');}
$query = "SELECT day, time FROM rzv_setup WHERE box = TRUE";
$result = $Connection->execute($query);
$data = $result['rows'];

// Převod načtených dat do struktury pro snadnější použití
$checked = [];
foreach ($data as $row) {
    if (!isset($checked[$row['day']])) {
        $checked[$row['day']] = [];
    }
    $checked[$row['day']][] = $row['time'];
}

echo "<title>Výběr dnů a hodin</title>";
echo "<form method='post' action=''>";
echo "<h4>Vyberte dny:</h4>";
echo "<input type='submit' value='Odeslat'>";
echo "<table>";

// Hlavička tabulky
echo "<tr><th>Čas</th>";
$days = ['Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle'];
foreach ($days as $index => $day) {
    echo "<th>$day</th>";
}
echo "</tr>";

// Časové řádky
for ($hour = 7; $hour <= 19; $hour++) {
    for ($minute = 0; $minute < 60; $minute += 15) {
        $time = sprintf('%02d:%02d', $hour, $minute);
        echo "<tr>";
        echo "<td>$time</td>"; // Sloupec pro čas
        foreach ($days as $index => $day) {
            $isChecked = in_array($time, $checked[$day] ?? []) ? 'checked' : '';
            echo "<td><input type='checkbox' id='day_{$index}_time_{$time}' name='days[{$day}][]' value='$time' $isChecked></td>";
        }
        echo "</tr>";
    }
}

echo "</table>";
echo "</form>";
?>

</body>
</html>
