
<?php

require_once __DIR__ . '/analyzers/InputDetector.php';

$detector = new InputDetector();

$testCases = [

    [
        'name' => 'PHP Content',
        'content' => '<?php echo "Hello World"; ?>',
        'fileName' => null
    ],

    [
        'name' => 'Valid JSON',
        'content' => '{"name":"John","age":25}',
        'fileName' => null
    ],

    [
        'name' => 'JSON Array',
        'content' => '[{"id":1},{"id":2}]',
        'fileName' => null
    ],

    [
        'name' => 'Invalid JSON',
        'content' => '{"name":"John" "age":25}',
        'fileName' => null
    ],

    [
        'name' => 'Log Content',
        'content' => '2026-11-08 10:30:00 ERROR Database connection failed',
        'fileName' => null
    ],

    [
        'name' => 'Plain Text',
        'content' => 'Developer Automation Studio is a file analysis application.',
        'fileName' => null
    ],

    [
        'name' => 'Empty Content',
        'content' => '',
        'fileName' => null
    ],

    [
        'name' => 'TXT File',
        'content' => 'Sample content',
        'fileName' => 'sample.txt'
    ]

];

echo "<h2>Input Detector Test Results</h2>";

echo "<pre>";

foreach ($testCases as $test) {

    $result = $detector->detect(
        $test['content'],
        $test['fileName']
    );

    echo "Test: " . $test['name'] . PHP_EOL;

    echo "Detected Type: "
        . $result['type'] . PHP_EOL;

    echo "Confidence: "
        . $result['confidence'] . PHP_EOL;

    echo "Reason: "
        . $result['reason'] . PHP_EOL;

    echo "----------------------------------"
        . PHP_EOL;
}

echo "</pre>";

?>
