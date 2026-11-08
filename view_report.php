
<?php

session_start();

require_once "config/database.php";

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate report ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    http_response_code(400);
    die("Invalid report ID.");
}

$reportId = (int) $_GET['id'];

try {

    $connection = getDatabaseConnection();

    $sql = "SELECT * FROM analysis_reports WHERE id = :id";

    $stmt = $connection->prepare($sql);

    $stmt->execute([
        ':id' => $reportId
    ]);

    $report = $stmt->fetch();

    if (!$report) {
        http_response_code(404);
        die("Report not found.");
    }

} catch (PDOException $e) {

    error_log("Report retrieval failed: " . $e->getMessage());

    http_response_code(500);
    die("Unable to retrieve report.");

}

// Decode JSON fields
$findings = json_decode($report['findings'] ?? '[]', true) ?? [];
$warnings = json_decode($report['warnings'] ?? '[]', true) ?? [];
$errors = json_decode($report['errors'] ?? '[]', true) ?? [];

// Escape HTML output
function escapeHTML($value): string
{
    if (is_array($value) || is_object($value)) {
        $value = json_encode($value);
    }

    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Check whether notes exist
$notesExist = trim($report['notes'] ?? '') !== '';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Report Details | Developer Automation Studio</title>

    <link rel="stylesheet" href="assets/style.css">

</head>

<body class="report-page">

<div class="container report-container">

    <!-- PAGE HEADER -->

    <header class="report-header">

        <div>

            <p class="eyebrow">DEVELOPER AUTOMATION STUDIO</p>

            <h1>Report Details</h1>

            <p class="report-subtitle">
                Detailed analysis results and report management.
            </p>

        </div>

        <span class="status-badge status-<?= strtolower(escapeHTML($report['status'])) ?>">
            <?= escapeHTML($report['status']) ?>
        </span>

    </header>


    <!-- SUCCESS MESSAGE -->

    <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>

        <div class="success-message">
            <strong>Success!</strong>
            Developer notes updated successfully.
        </div>

    <?php endif; ?>


    <!-- REPORT INFORMATION -->

    <section class="report-card">

        <div class="section-heading">

            <div>
                <h2>Report Information</h2>
                <p>Overview of the analyzed file.</p>
            </div>

            <span class="report-id">
                Report #<?= escapeHTML($report['id']) ?>
            </span>

        </div>

        <div class="report-info-grid">

            <div class="info-item">
                <span>File Name</span>
                <strong><?= escapeHTML($report['file_name']) ?></strong>
            </div>

            <div class="info-item">
                <span>File Type</span>
                <strong><?= escapeHTML($report['file_type']) ?></strong>
            </div>

            <div class="info-item">
                <span>Analyzer</span>
                <strong><?= escapeHTML($report['analyzer']) ?></strong>
            </div>

            <div class="info-item">
                <span>Status</span>
                <strong><?= escapeHTML($report['status']) ?></strong>
            </div>

            <div class="info-item">
                <span>Analyzed At</span>
                <strong><?= escapeHTML($report['analyzed_at']) ?></strong>
            </div>

        </div>

        <div class="summary-box">

            <span>Analysis Summary</span>

            <p>
                <?= escapeHTML($report['summary'] ?? 'No summary available.') ?>
            </p>

        </div>

    </section>


    <!-- LOG ANALYSIS FINDINGS -->

    <?php if ($report['file_type'] === 'LOG' && !empty($findings)): ?>

        <section class="report-card">

            <div class="section-heading">

                <div>
                    <h2>Log Analysis Findings</h2>
                    <p>Results extracted from the log file.</p>
                </div>

            </div>

            <div class="report-grid">

                <div class="report-box">
                    <h3><?= escapeHTML($findings['total_lines'] ?? 0) ?></h3>
                    <p>Total Lines</p>
                </div>

                <div class="report-box">
                    <h3><?= escapeHTML($findings['total_errors'] ?? 0) ?></h3>
                    <p>Total Errors</p>
                </div>

            </div>


            <?php if (!empty($findings['log_levels'])): ?>

                <div class="findings-section">

                    <h3>Log Levels</h3>

                    <div class="log-level-grid">

                        <?php foreach ($findings['log_levels'] as $level => $count): ?>

                            <div class="log-level-item">

                                <span><?= escapeHTML($level) ?></span>

                                <strong><?= escapeHTML($count) ?></strong>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>


            <?php if (!empty($findings['repeated_errors'])): ?>

                <div class="findings-section">

                    <h3>Repeated Errors</h3>

                    <ul class="findings-list">

                        <?php foreach ($findings['repeated_errors'] as $message => $count): ?>

                            <li>
                                <span><?= escapeHTML($message) ?></span>
                                <strong><?= escapeHTML($count) ?> times</strong>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <?php if (!empty($findings['timestamps'])): ?>

                <div class="findings-section">

                    <h3>Timestamps</h3>

                    <ul class="timestamp-list">

                        <?php foreach ($findings['timestamps'] as $timestamp): ?>

                            <li><?= escapeHTML($timestamp) ?></li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>

        </section>

    <?php endif; ?>


    <!-- TXT ANALYSIS FINDINGS -->

    <?php if ($report['file_type'] === 'TXT' && !empty($findings)): ?>

        <section class="report-card">

            <div class="section-heading">

                <div>
                    <h2>Text Analysis Findings</h2>
                    <p>Text statistics extracted from the uploaded file.</p>
                </div>

            </div>

            <div class="report-grid">

                <div class="report-box">
                    <h3><?= escapeHTML($findings['total_lines'] ?? 0) ?></h3>
                    <p>Total Lines</p>
                </div>

                <div class="report-box">
                    <h3><?= escapeHTML($findings['total_words'] ?? 0) ?></h3>
                    <p>Total Words</p>
                </div>

                <div class="report-box">
                    <h3><?= escapeHTML($findings['total_characters'] ?? 0) ?></h3>
                    <p>Total Characters</p>
                </div>

                <div class="report-box">
                    <h3><?= escapeHTML($findings['total_paragraphs'] ?? 0) ?></h3>
                    <p>Total Paragraphs</p>
                </div>

                <div class="report-box">
                    <h3><?= escapeHTML($findings['non_empty_lines'] ?? 0) ?></h3>
                    <p>Non-empty Lines</p>
                </div>

                <div class="report-box">
                    <h3><?= escapeHTML($findings['empty_lines'] ?? 0) ?></h3>
                    <p>Empty Lines</p>
                </div>

            </div>

        </section>

    <?php endif; ?>


    <!-- WARNINGS -->

    <?php if (!empty($warnings)): ?>

        <section class="report-card">

            <h2>Warnings</h2>

            <ul class="message-list warning-list">

                <?php foreach ($warnings as $warning): ?>

                    <li><?= escapeHTML($warning) ?></li>

                <?php endforeach; ?>

            </ul>

        </section>

    <?php endif; ?>


    <!-- ERRORS -->

    <?php if (!empty($errors)): ?>

        <section class="report-card">

            <h2>Errors</h2>

            <ul class="message-list error-list">

                <?php foreach ($errors as $error): ?>

                    <li><?= escapeHTML($error) ?></li>

                <?php endforeach; ?>

            </ul>

        </section>

    <?php endif; ?>


    <!-- DEVELOPER NOTES -->

    <section class="report-card notes-card">

        <div class="section-heading">

            <div>

                <h2>Developer Notes</h2>

                <p>
                    Record observations, root causes, debugging details,
                    and follow-up actions for this report.
                </p>

            </div>

            <span class="notes-icon">✎</span>

        </div>


        <!-- DISPLAY SAVED NOTES -->

        <div id="saved_notes_section">

            <?php if ($notesExist): ?>

                <div class="saved-notes-box">

                    <span class="saved-notes-label">SAVED NOTES</span>

                    <div class="saved-notes-content"><?= nl2br(escapeHTML($report['notes'])) ?></div>

                </div>

                <div class="notes-footer">

                    <span class="notes-hint">
                        Your notes are saved with this report.
                    </span>

                    <button
                        type="button"
                        id="edit_notes_button"
                        class="save-notes-button"
                    >
                        ✎ Edit Notes
                    </button>

                </div>

            <?php else: ?>

                <div class="empty-notes-box">

                    <p>No developer notes have been added yet.</p>

                    <span>Add your observations or follow-up actions.</span>

                </div>

                <div class="notes-footer">

                    <span class="notes-hint">
                        Add notes to document your findings.
                    </span>

                    <button
                        type="button"
                        id="edit_notes_button"
                        class="save-notes-button"
                    >
                        + Add Notes
                    </button>

                </div>

            <?php endif; ?>

        </div>


        <!-- EDIT NOTES FORM -->

        <div id="edit_notes_section" style="display: none;">

            <form action="update_report.php" method="POST">

                <input
                    type="hidden"
                    name="id"
                    value="<?= escapeHTML($report['id']) ?>"
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapeHTML($_SESSION['csrf_token']) ?>"
                >

                <label for="notes" class="notes-label">
                    Edit Your Notes
                </label>

                <textarea
                    id="notes"
                    name="notes"
                    class="notes-textarea"
                    rows="7"
                    maxlength="10000"
                    placeholder="Write your observations, possible causes, debugging steps, or action items here..."
                ><?= escapeHTML($report['notes'] ?? '') ?></textarea>

                <div class="notes-footer">

                    <button
                        type="button"
                        id="cancel_edit_button"
                        class="cancel-notes-button"
                    >
                        Cancel
                    </button>

                    <button type="submit" class="save-notes-button">
                        Save Notes
                    </button>

                </div>

            </form>

        </div>

    </section>


    <!-- NAVIGATION -->

    <div class="report-navigation">

        <a href="reports.php" class="back-button">
            ← Back to Analysis History
        </a>

        <a href="index.php" class="secondary-link">
            Analyze New File
        </a>

    </div>

</div>


<!-- NOTES EDITING JAVASCRIPT -->

<script>

document.addEventListener("DOMContentLoaded", function () {

    const savedSection = document.getElementById("saved_notes_section");

    const editSection = document.getElementById("edit_notes_section");

    const editButton = document.getElementById("edit_notes_button");

    const cancelButton = document.getElementById("cancel_edit_button");

    // Show the editing form
    editButton.addEventListener("click", function () {

        savedSection.style.display = "none";

        editSection.style.display = "block";

    });

    // Cancel editing
    cancelButton.addEventListener("click", function () {

        editSection.style.display = "none";

        savedSection.style.display = "block";

    });

});

</script>

</body>
</html>
