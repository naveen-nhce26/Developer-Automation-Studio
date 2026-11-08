<?php

$assetsPath = __DIR__ . '/assets';

echo "<h3>Files found inside assets folder:</h3>";

if (is_dir($assetsPath)) {
    echo "<pre>";
    print_r(scandir($assetsPath));
    echo "</pre>";
} else {
    echo "Assets folder not found!";
}

?>