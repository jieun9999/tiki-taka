<?php
$testFile = '/tmp/testfile.txt';
$handle = fopen($testFile, 'w');
if ($handle === false) {
    echo "Cannot write to the directory /tmp";
} else {
    echo "Write permission is granted in /tmp";// Write permission is granted in /tmp 출력됨
    fclose($handle);
    unlink($testFile); // Clean up after the test
}
?>
