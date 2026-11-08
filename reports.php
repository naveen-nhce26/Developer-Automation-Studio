
<?php

session_start();

require_once "config/database.php";

$connection = getDatabaseConnection();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

// Fetch saved reports
$sql = "SELECT
            id,
            file_name,
            file_type,
            status,
            analyzer,
            summary,
            analyzed_at
        FROM analysis_reports
        ORDER BY analyzed_at DESC";

$stmt = $connection->prepare($sql);
$stmt->execute();

$reports = $stmt->fetchAll();

// Safely display database values
function escapeHTML($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Analysis History | Developer Automation Studio</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="container">

    <header class="header">
        <h1>Analysis History</h1>
        <p>View and manage all previously generated analysis reports.</p>
    </header>

    <main class="analysis-card">

        <div class="action-buttons">

            <h2>Saved Reports</h2>

            <a href="index.php" class="back-button">
                Analyze New File
            </a>

        </div>

        <?php if (isset($_GET['deleted']) && $_GET['deleted'] === '1'): ?>

            <p style="color: green;">
                Report deleted successfully.
            </p>

        <?php endif; ?>

        <p class="description">
            Total reports:
            <strong><?php echo count($reports); ?></strong>
        </p>

        <?php if (!empty($reports)): ?>

            <div style="overflow-x: auto;">

                <table class="report-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>File Name</th>
                            <th>File Type</th>
                            <th>Status</th>
                            <th>Analyzer</th>
                            <th>Summary</th>
                            <th>Analyzed At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($reports as $report): ?>

                            <tr>

                                <td>
                                    <?php echo escapeHTML($report['id']); ?>
                                </td>

                                <td>
                                    <?php echo escapeHTML($report['file_name']); ?>
                                </td>

                                <td>
                                    <?php echo escapeHTML($report['file_type']); ?>
                                </td>

                                <td>
                                    <?php echo escapeHTML($report['status']); ?>
                                </td>

                                <td>
                                    <?php echo escapeHTML($report['analyzer']); ?>
                                </td>

                                <td>
                                    <?php echo escapeHTML($report['summary']); ?>
                                </td>

                                <td>
                                    <?php echo escapeHTML($report['analyzed_at']); ?>
                                </td>

                                <td>

                                    <a href="view_report.php?id=<?php echo urlencode($report['id']); ?>">
                                        View
                                    </a>

                                    <form
                                        action="delete_report.php"
                                        method="POST"
                                        style="display: inline;"
                                        onsubmit="return confirm('Are you sure you want to delete this report? This action cannot be undone.');"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?php echo escapeHTML($report['id']); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo escapeHTML($csrfToken); ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="clear-button"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p class="description">
                No analysis reports found. Analyze a file to see its history here.
            </p>

        <?php endif; ?>

    </main>

</div>

</body>
</html>
