<?php
require_once __DIR__ . '/config/database.php';

require_once __DIR__ . '/analyzers/LogAnalyzer.php';

$logContent = "
2026-11-07 09:00:01 INFO Application started
2026-11-07 09:00:05 WARNING Memory usage is high
2026-11-07 09:00:10 ERROR Database connection failed
2026-11-07 09:00:15 INFO User logged in
2026-11-07 09:00:20 ERROR Database connection failed
2026-11-07 09:00:25 DEBUG Processing completed
";

$analyzer = new LogAnalyzer();

$result = $analyzer->analyze($logContent);
// Connect to the database
$pdo = getDatabaseConnection();

// Prepare SQL query
$sql = "INSERT INTO analysis_reports
        (file_name, file_type, status, analyzer, summary, findings, warnings, errors)
        VALUES
        (:file_name, :file_type, :status, :analyzer, :summary, :findings, :warnings, :errors)";

$stmt = $pdo->prepare($sql);

// Insert analysis report
$stmt->execute([
    ':file_name' => 'sample.log',
    ':file_type' => 'LOG',
    ':status' => 'SUCCESS',
    ':analyzer' => 'LogAnalyzer',
    ':summary' => 'Log file analyzed successfully.',
    ':findings' => json_encode($result),
    ':warnings' => json_encode([]),
    ':errors' => json_encode([])
]);

echo "<h3>Report saved successfully in database!</h3>";


echo "<h2>Log Analysis Report</h2>";

echo "<pre>";
print_r($result);
echo "</pre>";

?>