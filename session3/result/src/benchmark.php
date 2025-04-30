<?php

function benchmark($pdo = null)
{
    $max_increments = 1000000;
    $start = microtime(true);

    // Rechenintensive Operation
    $sum = 0;
    for ($i = 0; $i < $max_increments; $i++) {
        $sum += sqrt($i);
    }

    // Durchlaufen eines großen Arrays
    $largeArray = range(1, $max_increments);
    $arraySum = 0;
    foreach ($largeArray as $value) {
        $arraySum += $value;
    }

    // String-Operationen
    $str = "";
    for ($i = 0; $i < $max_increments; $i++) {
        $str .= "a";
    }

    // JSON-Operationen
    $data = ['key' => 'value', 'numbers' => range(1, 100)];
    for ($i = 0; $i < $max_increments; $i++) {
        json_decode(json_encode($data), true);
    }

    // Datei-Operationen
    file_put_contents("benchmark_test.txt", str_repeat("Hello World\n", 100000));
    file_get_contents("benchmark_test.txt");
    unlink("benchmark_test.txt");

//    // Optional: Datenbank-Operationen
//    if ($pdo) {
//        $pdo->exec("CREATE TABLE IF NOT EXISTS benchmark (id INT AUTO_INCREMENT PRIMARY KEY, data TEXT)");
//        $stmt = $pdo->prepare("INSERT INTO benchmark (data) VALUES (:data)");
//        for ($i = 0; $i < 1000; $i++) {
//            $stmt->execute(['data' => 'Test Data']);
//        }
//        $stmt = $pdo->query("SELECT COUNT(*) FROM benchmark");
//        $stmt->fetchColumn();
//    }

    $end = microtime(true);
    echo "Execution time: " . ($end - $start) . " seconds\n";
}
