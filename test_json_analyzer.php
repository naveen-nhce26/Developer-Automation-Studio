
<?php

require_once __DIR__ . '/analyzers/JsonAnalyzer.php';

// Sample JSON content
$jsonContent = <<<'JSON'
{
    "application": "Developer Automation Studio",
    "version": 1.0,
    "active": true,
    "maintenanceMode": false,
    "owner": null,
    "database": {
        "host": "localhost",
        "port": 3307,
        "connected": true,
        "tables": [
            "analysis_reports",
            "users",
            "settings"
        ]
    },
    "analyzers": [
        {
            "name": "LogAnalyzer",
            "enabled": true,
            "supportedExtensions": [".log"],
            "metrics": {
                "errors": 12,
                "warnings": 4,
                "info": 35
            }
        },
        {
            "name": "TextAnalyzer",
            "enabled": true,
            "supportedExtensions": [".txt"],
            "metrics": {
                "words": 250,
                "characters": 1400
            }
        },
        {
            "name": "PhpAnalyzer",
            "enabled": true,
            "supportedExtensions": [".php"],
            "metrics": {
                "classes": 3,
                "functions": 8,
                "methods": 12
            }
        }
    ],
    "recentReports": [],
    "threshold": 0,
    "notes": "Sample JSON file for testing nested structures."
}
JSON;

// Create analyzer object
$analyzer = new JsonAnalyzer();

// Analyze JSON content
$result = $analyzer->analyze($jsonContent);

// Display results
echo "<pre>";
print_r($result);
echo "</pre>";

?>
