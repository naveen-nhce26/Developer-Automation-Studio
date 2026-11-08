
<?php

session_start();

require_once "config/database.php";

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method not allowed.");
}

// Validate report ID
if (!isset($_POST['id']) || !ctype_digit($_POST['id'])) {
    die("Invalid report ID.");
}

// Validate CSRF token
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    die("Invalid security token. Please try again.");
}

// Read report ID and notes
$reportId = (int) $_POST['id'];
$notes = trim($_POST['notes'] ?? '');

// Validate notes length
if (strlen($notes) > 10000) {
    die("Notes cannot exceed 10,000 bytes.");
}

try {

    $connection = getDatabaseConnection();

    // Check whether the report exists
    $checkSql = "SELECT id FROM analysis_reports WHERE id = :id";

    $checkStatement = $connection->prepare($checkSql);

    $checkStatement->execute([
        ':id' => $reportId
    ]);

    if (!$checkStatement->fetch()) {
        die("Report not found.");
    }

    // Update report notes
    $updateSql = "UPDATE analysis_reports
                  SET notes = :notes
                  WHERE id = :id";

    $updateStatement = $connection->prepare($updateSql);

    $updateStatement->execute([
        ':notes' => $notes,
        ':id' => $reportId
    ]);

    // Redirect to the updated report
    header("Location: view_report.php?id=" . $reportId . "&updated=1");
    exit;

} catch (PDOException $e) {

    error_log("Report update failed: " . $e->getMessage());

    http_response_code(500);
    die("Unable to update report notes. Please try again.");

}
