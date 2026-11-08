<?php

require_once dirname(__FILE__) . '/WirepaymentTestCase.php';

class DirectoryGuardTest extends WirepaymentTestCase
{
    private function assertGuardExitsSilently($relativePath)
    {
        $root = dirname(__DIR__);
        $target = $root . '/' . $relativePath;
        $sentinel = tempnam(sys_get_temp_dir(), 'guard-');
        $this->assertNotFalse($sentinel);
        $runner = dirname(__FILE__) . '/bin/guard-runner.php';
        $env = array(
            'WIREPAYMENT_GUARD_SENTINEL' => $sentinel,
        );
        $php = getenv('PHP_BINARY') ?: 'php';
        $cmd = escapeshellarg($php)
            . ' -d error_reporting=-1 -d display_errors=stderr -d display_startup_errors=1 '
            . escapeshellarg($runner) . ' ' . escapeshellarg($target);
        $descriptors = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w'));
        $process = proc_open($cmd, $descriptors, $pipes, $root, $env);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        wirepayment_assert_no_php_diagnostics($stdout, $stderr);
        $this->assertSame(0, $exit);
        $this->assertSame('entered', file_get_contents($sentinel));
        $this->assertSame('', $stdout);
        $this->assertSame('', $stderr);
        unlink($sentinel);
    }

    public function testRootIndexExitsSilently()
    {
        $this->assertGuardExitsSilently('index.php');
    }

    public function testControllersIndexExitsSilently()
    {
        $this->assertGuardExitsSilently('controllers/index.php');
    }

    public function testControllersFrontIndexExitsSilently()
    {
        $this->assertGuardExitsSilently('controllers/front/index.php');
    }

    public function testTranslationsIndexExitsSilently()
    {
        $this->assertGuardExitsSilently('translations/index.php');
    }

    public function testUpgradeIndexExitsSilently()
    {
        $this->assertGuardExitsSilently('upgrade/index.php');
    }

    public function testViewsIndexExitsSilently()
    {
        $this->assertGuardExitsSilently('views/index.php');
    }

    public function testTemplatesIndexExitsSilently()
    {
        $this->assertGuardExitsSilently('views/templates/index.php');
    }

    public function testHookTemplatesIndexExitsSilently()
    {
        $this->assertGuardExitsSilently('views/templates/hook/index.php');
    }
}
