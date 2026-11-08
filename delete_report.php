
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

$reportId = (int) $_POST['id'];

try {

    $connection = getDatabaseConnection();

    // Delete only the selected report
    $sql = "DELETE FROM analysis_reports WHERE id = :id";

    $stmt = $connection->prepare($sql);

    $stmt->execute([
        ':id' => $reportId
    ]);

    if ($stmt->rowCount() === 0) {
        die("Report not found or already deleted.");
    }

    // Redirect to analysis history
    header("Location: reports.php?deleted=1");
    exit;

} catch (PDOException $e) {

    error_log("Report deletion failed: " . $e->getMessage());

    http_response_code(500);
    die("Unable to delete the report. Please try again.");

}
