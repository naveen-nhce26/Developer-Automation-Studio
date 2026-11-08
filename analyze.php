<?php
// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method not allowed.");
}

require_once "config/database.php";
require_once "analyzers/LogAnalyzer.php";
require_once "analyzers/TextAnalyzer.php";
require_once "analyzers/PhpAnalyzer.php";
require_once "analyzers/JsonAnalyzer.php";
require_once "analyzers/InputDetector.php";

$connection = getDatabaseConnection();

$fileName = "";
$fileType = "";
$analyzerName = "";
$content = "";
$analysis = [];
$summary = "";
$status = "SUCCESS";

// Check whether a file was uploaded
$// Validate uploaded file structure
$fileUploaded = false;

if (isset($_FILES['log_file'])) {

    $uploadedFile = $_FILES['log_file'];

    if (
        !is_array($uploadedFile) ||
        !isset($uploadedFile['error']) ||
        !is_int($uploadedFile['error'])
    ) {
        http_response_code(400);
        die("Invalid file upload request.");
    }

    if ($uploadedFile['error'] !== UPLOAD_ERR_NO_FILE) {

        if (
            !isset($uploadedFile['name'], $uploadedFile['tmp_name'], $uploadedFile['size']) ||
            !is_string($uploadedFile['name']) ||
            !is_string($uploadedFile['tmp_name']) ||
            !is_int($uploadedFile['size']) ||
            $uploadedFile['size'] < 0
        ) {
            http_response_code(400);
            die("Invalid file upload details.");
        }

        $fileUploaded = true;
    }
}

// Validate pasted content type
$contentPasted = false;

if (isset($_POST['log_content'])) {
    if (!is_string($_POST['log_content'])) {
        http_response_code(400);
        die("Invalid pasted content.");
    }

    $contentPasted = trim($_POST['log_content']) !== "";
}

// Validate input
if ($fileUploaded && $contentPasted) {
    http_response_code(400);
    die("Please upload a file OR paste content, not both.");
}

if (!$fileUploaded && !$contentPasted) {
    http_response_code(400);
    die("Please upload a file or paste content.");
}
// Handle uploaded file
if ($fileUploaded) {

    if ($_FILES['log_file']['error'] !== UPLOAD_ERR_OK) {
        die("File upload failed. Please try again.");
    }

    // Enforce a maximum upload size of 5 MB
    $maxFileSize = 5 * 1024 * 1024;

    if ($_FILES['log_file']['size'] > $maxFileSize) {
        die("File size exceeds the maximum allowed limit of 5 MB.");
    }

    $fileName = basename($_FILES['log_file']['name']);

    $extension = strtolower(
        pathinfo($fileName, PATHINFO_EXTENSION)
    );

    if (!in_array($extension, ['log', 'txt', 'php', 'json'], true)) {
        die("Unsupported file type. Please upload a LOG, TXT, PHP or JSON file.");
    }

    if (!is_uploaded_file($_FILES['log_file']['tmp_name'])) {
        die("Invalid file upload.");
    }

    $content = file_get_contents($_FILES['log_file']['tmp_name']);

    if ($content === false) {
        die("Unable to read the uploaded file.");
    }

} else {

    // Handle pasted content
    $fileName = "Pasted Content";
    $content = $_POST['log_content'];
}

// Check for empty content
if (trim($content) === "") {
    die("The file or pasted content is empty.");
}

// Detect the actual content type. For uploaded files, the extension is
// used only as a fallback when the content itself is not recognizable.
$inputDetector = new InputDetector();
$detection = $inputDetector->detect(
    $content,
    $fileUploaded ? $fileName : null
);

$fileType = $detection['type'];

if ($fileType === "UNKNOWN") {
    die("Unable to determine the content type. Please provide a supported file or content.");
}

// Select the appropriate analyzer
if ($fileType === "LOG") {

    $analyzer = new LogAnalyzer();
    $analyzerName = "LogAnalyzer";

    $analysis = $analyzer->analyze($content);

    $summary = "Log file contains "
        . $analysis['total_lines'] . " lines and "
        . $analysis['total_errors'] . " errors.";

} elseif ($fileType === "TXT") {

    $analyzer = new TextAnalyzer();
    $analyzerName = "TextAnalyzer";

    $analysis = $analyzer->analyze($content);

    $summary = $analysis['summary'];

} elseif ($fileType === "PHP") {

    $analyzer = new PhpAnalyzer();
    $analyzerName = "PhpAnalyzer";

    $analysis = $analyzer->analyze($content);

    $status = $analysis['status'];

    $summary = $analysis['summary'];

} elseif ($fileType === "JSON") {

    $analyzer = new JsonAnalyzer();
    $analyzerName = "JsonAnalyzer";

    $analysis = $analyzer->analyze($content);

    $status = $analysis['status'];

    $summary = $analysis['summary'];
}

// Keep input-detection details with the analyzer findings for report history.
$analysis['input_detection'] = $detection;

// Prepare database error information
$warnings = [];

$errors = [];

if ($status === "FAILED") {
    $errors[] = [
        'message' => $analysis['error'] ?? 'File analysis failed.',
        'line' => $analysis['error_line'] ?? null
    ];
}

// Save report in database
$insertSql = "INSERT INTO analysis_reports
    (file_name, file_type, status, analyzer, summary, findings, warnings, errors)
    VALUES
    (:file_name, :file_type, :status, :analyzer, :summary, :findings, :warnings, :errors)";

$insertStatement = $connection->prepare($insertSql);

$insertStatement->execute([
    ':file_name' => $fileName,
    ':file_type' => $fileType,
    ':status' => $status,
    ':analyzer' => $analyzerName,
    ':summary' => $summary,
    ':findings' => json_encode($analysis, JSON_UNESCAPED_UNICODE),
    ':warnings' => json_encode($warnings, JSON_UNESCAPED_UNICODE),
    ':errors' => json_encode($errors, JSON_UNESCAPED_UNICODE)
]);

// HTML escaping function
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

    <title>Analysis Report | Developer Automation Studio</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="container">

    <header class="header">
        <h1>Analysis Report</h1>

        <p>
            <?php if ($status === "SUCCESS"): ?>
                Your file has been analyzed successfully.
            <?php else: ?>
                Analysis completed with errors.
            <?php endif; ?>
        </p>
    </header>

    <main class="analysis-card">

        <h2>File Information</h2>

        <p><strong>File Name:</strong> <?php echo escapeHTML($fileName); ?></p>
        <p><strong>File Type:</strong> <?php echo escapeHTML($fileType); ?></p>
        <p><strong>Analyzer:</strong> <?php echo escapeHTML($analyzerName); ?></p>
        <p><strong>Status:</strong> <?php echo escapeHTML($status); ?></p>

        <hr>

        <h2>Analysis Summary</h2>

        <p><?php echo escapeHTML($summary); ?></p>

        <h2>Input Detection</h2>
        <p><strong>Detected Type:</strong> <?php echo escapeHTML($detection['type']); ?></p>
        <p><strong>Confidence:</strong> <?php echo escapeHTML($detection['confidence']); ?></p>
        <p><strong>Reason:</strong> <?php echo escapeHTML($detection['reason']); ?></p>

        <?php if ($fileType === "LOG"): ?>

            <div class="report-grid">

                <div class="report-box">
                    <h3><?php echo escapeHTML($analysis['total_lines']); ?></h3>
                    <p>Total Lines</p>
                </div>

                <div class="report-box">
                    <h3><?php echo escapeHTML($analysis['total_errors']); ?></h3>
                    <p>Total Errors</p>
                </div>

                <?php foreach ($analysis['log_levels'] as $level => $count): ?>

                    <div class="report-box">
                        <h3><?php echo escapeHTML($count); ?></h3>
                        <p><?php echo escapeHTML($level); ?> Messages</p>
                    </div>

                <?php endforeach; ?>

            </div>

            <h2>Repeated Errors</h2>

            <?php if (!empty($analysis['repeated_errors'])): ?>

                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Error Message</th>
                            <th>Occurrences</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($analysis['repeated_errors'] as $message => $count): ?>
                            <tr>
                                <td><?php echo escapeHTML($message); ?></td>
                                <td><?php echo escapeHTML($count); ?></td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>

            <?php else: ?>

                <p>No repeated errors found.</p>

            <?php endif; ?>

            <h2>Extracted Timestamps</h2>

            <?php if (!empty($analysis['timestamps'])): ?>

                <table class="report-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($analysis['timestamps'] as $index => $timestamp): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo escapeHTML($timestamp); ?></td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>

            <?php else: ?>

                <p>No timestamps found.</p>

            <?php endif; ?>

        <?php elseif ($fileType === "TXT"): ?>

            <div class="report-grid">

                <div class="report-box">
                    <h3><?php echo escapeHTML($analysis['total_lines']); ?></h3>
                    <p>Total Lines</p>
                </div>

                <div class="report-box">
                    <h3><?php echo escapeHTML($analysis['total_words']); ?></h3>
                    <p>Total Words</p>
                </div>

                <div class="report-box">
                    <h3><?php echo escapeHTML($analysis['total_characters']); ?></h3>
                    <p>Total Characters</p>
                </div>

                <div class="report-box">
                    <h3><?php echo escapeHTML($analysis['total_paragraphs']); ?></h3>
                    <p>Total Paragraphs</p>
                </div>

                <div class="report-box">
                    <h3><?php echo escapeHTML($analysis['non_empty_lines']); ?></h3>
                    <p>Non-empty Lines</p>
                </div>

                <div class="report-box">
                    <h3><?php echo escapeHTML($analysis['empty_lines']); ?></h3>
                    <p>Empty Lines</p>
                </div>

            </div>

        <?php elseif ($fileType === "PHP"): ?>

            <h2>PHP Code Analysis</h2>

            <div class="report-grid">

                <div class="report-box">
                    <h3><?php echo escapeHTML($analysis['total_lines']); ?></h3>
                    <p>Total Lines</p>
                </div>

                <div class="report-box">
                    <h3><?php echo escapeHTML($analysis['total_classes']); ?></h3>
                    <p>Classes</p>
                </div>

                <div class="report-box">
                    <h3><?php echo escapeHTML($analysis['total_interfaces']); ?></h3>
                    <p>Interfaces</p>
                </div>

                <div class="report-box">
                    <h3><?php echo escapeHTML($analysis['total_traits']); ?></h3>
                    <p>Traits</p>
                </div>

                <div class="report-box">
                    <h3><?php echo escapeHTML($analysis['total_functions']); ?></h3>
                    <p>Functions</p>
                </div>

                <div class="report-box">
                    <h3><?php echo escapeHTML($analysis['total_methods']); ?></h3>
                    <p>Methods</p>
                </div>

                <div class="report-box">
                    <h3><?php echo escapeHTML($analysis['total_variables']); ?></h3>
                    <p>Variables</p>
                </div>

            </div>

            <?php if ($status === "FAILED"): ?>

                <h2>Syntax Error</h2>

                <p>
                    <strong>Error:</strong>
                    <?php echo escapeHTML($analysis['error']); ?>
                </p>

                <p>
                    <strong>Error Line:</strong>
                    <?php echo escapeHTML($analysis['error_line']); ?>
                </p>

            <?php else: ?>

                <h2>Classes</h2>

                <?php if (!empty($analysis['classes'])): ?>
                    <ul>
                        <?php foreach ($analysis['classes'] as $class): ?>
                            <li><?php echo escapeHTML($class); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No classes found.</p>
                <?php endif; ?>

                <h2>Interfaces</h2>

                <?php if (!empty($analysis['interfaces'])): ?>
                    <ul>
                        <?php foreach ($analysis['interfaces'] as $interface): ?>
                            <li><?php echo escapeHTML($interface); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No interfaces found.</p>
                <?php endif; ?>

                <h2>Traits</h2>

                <?php if (!empty($analysis['traits'])): ?>
                    <ul>
                        <?php foreach ($analysis['traits'] as $trait): ?>
                            <li><?php echo escapeHTML($trait); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No traits found.</p>
                <?php endif; ?>

                <h2>Functions</h2>

                <?php if (!empty($analysis['functions'])): ?>
                    <ul>
                        <?php foreach ($analysis['functions'] as $function): ?>
                            <li><?php echo escapeHTML($function); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No functions found.</p>
                <?php endif; ?>

                <h2>Methods</h2>

                <?php if (!empty($analysis['methods'])): ?>
                    <ul>
                        <?php foreach ($analysis['methods'] as $method): ?>
                            <li><?php echo escapeHTML($method); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No methods found.</p>
                <?php endif; ?>

                <h2>Dependencies</h2>

                <?php if (!empty($analysis['dependencies'])): ?>

                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Dependency Type</th>
                                <th>Line</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($analysis['dependencies'] as $dependency): ?>
                                <tr>
                                    <td><?php echo escapeHTML($dependency['type']); ?></td>
                                    <td><?php echo escapeHTML($dependency['line']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php else: ?>
                    <p>No dependencies found.</p>
                <?php endif; ?>

                <h2>Potentially Risky Functions</h2>

                <?php if (!empty($analysis['risky_functions'])): ?>

                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Function</th>
                                <th>Line</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($analysis['risky_functions'] as $riskyFunction): ?>
                                <tr>
                                    <td><?php echo escapeHTML($riskyFunction['function']); ?></td>
                                    <td><?php echo escapeHTML($riskyFunction['line']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php else: ?>
                    <p>No potentially risky functions detected.</p>
                <?php endif; ?>

            <?php endif; ?>

        <?php elseif ($fileType === "JSON"): ?>

            <h2>JSON Data Analysis</h2>

            <?php if ($status === "FAILED"): ?>

                <h2>JSON Syntax Error</h2>

                <p>
                    <strong>Error:</strong>
                    <?php echo escapeHTML($analysis['error'] ?? 'Invalid JSON syntax.'); ?>
                </p>

            <?php else: ?>

                <div class="report-grid">

                    <div class="report-box">
                        <h3><?php echo escapeHTML($analysis['total_lines']); ?></h3>
                        <p>Total Lines</p>
                    </div>

                    <div class="report-box">
                        <h3><?php echo escapeHTML($analysis['total_objects']); ?></h3>
                        <p>Objects</p>
                    </div>

                    <div class="report-box">
                        <h3><?php echo escapeHTML($analysis['total_arrays']); ?></h3>
                        <p>Arrays</p>
                    </div>

                    <div class="report-box">
                        <h3><?php echo escapeHTML($analysis['total_properties']); ?></h3>
                        <p>Properties</p>
                    </div>

                    <div class="report-box">
                        <h3><?php echo escapeHTML($analysis['total_strings']); ?></h3>
                        <p>Strings</p>
                    </div>

                    <div class="report-box">
                        <h3><?php echo escapeHTML($analysis['total_numbers']); ?></h3>
                        <p>Numbers</p>
                    </div>

                    <div class="report-box">
                        <h3><?php echo escapeHTML($analysis['total_booleans']); ?></h3>
                        <p>Booleans</p>
                    </div>

                    <div class="report-box">
                        <h3><?php echo escapeHTML($analysis['total_nulls']); ?></h3>
                        <p>Null Values</p>
                    </div>

                    <div class="report-box">
                        <h3><?php echo escapeHTML($analysis['total_values']); ?></h3>
                        <p>Total Values</p>
                    </div>

                </div>

            <?php endif; ?>

        <?php endif; ?>

        <div class="action-buttons">

            <a href="index.php" class="back-button">
                Analyze Another File
            </a>

            <a href="reports.php" class="back-button">
                View Analysis History
            </a>

        </div>

    </main>

</div>

</body>
</html>
