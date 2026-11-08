<?php

require_once __DIR__ . '/config/database.php';

try {

    $pdo = getDatabaseConnection();

    $sql = "INSERT INTO analysis_reports
            (file_name, file_type, status, analyzer, summary, findings, warnings, errors)
            VALUES
            (:file_name, :file_type, :status, :analyzer, :summary, :findings, :warnings, :errors)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':file_name' => 'sample.log',
        ':file_type' => 'LOG',
        ':status' => 'SUCCESS',
        ':analyzer' => 'LogAnalyzer',
        ':summary' => 'Sample log file analyzed successfully.',
        ':findings' => json_encode(['total_lines' => 100, 'errors_found' => 5]),
        ':warnings' => json_encode(['warning_count' => 2]),
        ':errors' => json_encode(['error_count' => 5])
    ]);

    echo "Test report inserted successfully!";

} catch (PDOException $e) {

    echo "Error: " . $e->getMessage();

}