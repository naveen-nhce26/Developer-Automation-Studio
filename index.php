
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Developer Automation Studio</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <div class="container">

        <!-- Dashboard Header -->
        <header class="header">
            <h1>Developer Automation Studio</h1>
            <p>Analyze developer files, identify issues and generate reports.</p>
        </header>

        <!-- Analysis Section -->
        <main class="analysis-card">

            <h2>Analyze Your Files</h2>

            <p class="description">
                Upload a LOG, TXT, PHP or JSON file, or paste content below to begin analysis.
                The application will automatically detect the content type.
            </p>

            <form action="analyze.php" method="POST" enctype="multipart/form-data">

                <!-- File Upload Area -->
                <div class="form-group">

                    <label>Upload File</label>

                    <div class="drop-area" id="drop_area">

                        <p>Drag and drop your file here</p>

                        <p>or</p>

                        <label for="log_file" class="browse-button">
                            Browse Files
                        </label>

                        <input
                            type="file"
                            id="log_file"
                            name="log_file"
                            accept=".log,.txt,.php,.json"
                            hidden
                        >

                        <p id="file_name" class="file-name">
                            No file selected
                        </p>

                    </div>

                </div>

                <!-- Divider -->
                <div class="divider">
                    OR
                </div>

                <!-- Paste Content -->
                <div class="form-group">

                    <label for="log_content">Paste Content</label>

                    <textarea
                        id="log_content"
                        name="log_content"
                        placeholder="Paste your LOG, TXT, PHP or JSON content here..."
                    ></textarea>

                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">

                    <button type="submit" id="analyze_button">
                        Analyze File
                    </button>

                    <button type="button" id="clear_button" class="clear-button">
                        Clear
                    </button>

                </div>

            </form>

            <!-- Analysis History Link -->
            <div style="text-align: center; margin-top: 25px;">
                <a href="reports.php">View Analysis History</a>
            </div>

        </main>

    </div>

    <script src="assets/script.js"></script>

</body>
</html>
