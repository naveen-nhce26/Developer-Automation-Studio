
<?php

require_once __DIR__ . '/analyzers/JsonAnalyzer.php';

// Intentionally invalid JSON (missing comma after "Developer Automation Studio")
$jsonContent = <<<'JSON'
{
    "application": "Developer Automation Studio"
    "version": 1.0,
    "active": true,
    "owner": null
}
JSON;

// Create analyzer
$analyzer = new JsonAnalyzer();

// Analyze invalid JSON
$result = $analyzer->analyze($jsonContent);

// Display result
echo "<pre>";
print_r($result);
echo "</pre>";

?>
