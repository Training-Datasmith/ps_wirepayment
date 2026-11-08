<?php

$testsDir = __DIR__;
$expected = array(
    'DirectoryGuardTest.php',
    'PaymentControllerTest.php',
    'PsWirepaymentConfigTest.php',
    'PsWirepaymentCurrencyTest.php',
    'PsWirepaymentHooksTest.php',
    'PsWirepaymentInstallTest.php',
    'PsWirepaymentTemplateContractTest.php',
    'PsWirepaymentUpgradeTest.php',
    'ValidationControllerTest.php',
);

$found = array();
foreach (glob($testsDir . '/*Test.php') as $path) {
    $found[] = basename($path);
}
sort($found);
$expectedSorted = $expected;
sort($expectedSorted);

if ($found !== $expectedSorted) {
    fwrite(STDERR, "Test file drift.\nExpected:\n" . implode("\n", $expectedSorted) . "\nFound:\n" . implode("\n", $found) . "\n");
    exit(1);
}

$ordered = $expected;
$bootstrap = $testsDir . '/bootstrap.php';
$workDir = getenv('WIREPAYMENT_WORK_DIR');
if (!$workDir) {
    fwrite(STDERR, "WIREPAYMENT_WORK_DIR not set\n");
    exit(1);
}

function buildXml($suiteName, array $files, $bootstrap)
{
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml .= "<phpunit bootstrap=\"" . htmlspecialchars($bootstrap, ENT_QUOTES, 'UTF-8') . "\"\n";
    $xml .= "         colors=\"false\"\n";
    $xml .= "         convertErrorsToExceptions=\"true\"\n";
    $xml .= "         convertNoticesToExceptions=\"true\"\n";
    $xml .= "         convertWarningsToExceptions=\"true\"\n";
    $xml .= "         beStrictAboutTestsThatDoNotTestAnything=\"true\"\n";
    $xml .= "         beStrictAboutOutputDuringTests=\"true\"\n";
    $xml .= "         backupGlobals=\"false\"\n";
    $xml .= "         backupStaticAttributes=\"false\">\n";
    $xml .= "  <testsuites>\n";
    $xml .= "    <testsuite name=\"" . htmlspecialchars($suiteName, ENT_QUOTES, 'UTF-8') . "\">\n";
    foreach ($files as $file) {
        $xml .= '      <file>' . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . "</file>\n";
    }
    $xml .= "    </testsuite>\n";
    $xml .= "  </testsuites>\n";
    $xml .= "</phpunit>\n";

    return $xml;
}

$orderedFiles = array();
foreach ($ordered as $base) {
    $orderedFiles[] = $testsDir . '/' . $base;
}

file_put_contents($workDir . '/phpunit-ordered.xml', buildXml('module-tests', $orderedFiles, $bootstrap));

$shuffled = $orderedFiles;
if (!function_exists('srand')) {
    fwrite(STDERR, "srand unavailable\n");
    exit(1);
}
srand(20261108);
for ($i = count($shuffled) - 1; $i > 0; $i--) {
    $j = rand(0, $i);
    $tmp = $shuffled[$i];
    $shuffled[$i] = $shuffled[$j];
    $shuffled[$j] = $tmp;
}
file_put_contents($workDir . '/phpunit-shuffled.xml', buildXml('module-tests', $shuffled, $bootstrap));

$expectedTestCount = 69;
$count = 0;
foreach ($orderedFiles as $file) {
    $src = file_get_contents($file);
    if (preg_match_all('/function\s+test[A-Za-z0-9_]*\s*\(/', $src, $m)) {
        $count += count($m[0]);
    }
}
if ($count !== $expectedTestCount) {
    fwrite(STDERR, "Test count drift: expected {$expectedTestCount}, found {$count}\n");
    exit(1);
}

echo "Wrote XML configs; test count {$count}\n";
