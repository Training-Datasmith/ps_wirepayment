<?php

abstract class WirepaymentTestCase extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        wirepayment_reset_all_state();
    }

    protected function tearDown()
    {
        wirepayment_reset_all_state();
    }

    protected function runPhpChild($script, array $args = array())
    {
        $php = getenv('PHP_BINARY');
        if (!$php) {
            $php = 'php';
        }
        $cmd = escapeshellarg($php)
            . ' -d error_reporting=-1 -d display_errors=stderr -d display_startup_errors=1 '
            . escapeshellarg($script);
        foreach ($args as $arg) {
            $cmd .= ' ' . escapeshellarg($arg);
        }
        $descriptors = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));
        $this->assertInternalType('resource', $process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        wirepayment_assert_no_php_diagnostics($stdout, $stderr);

        return array('exit' => $exitCode, 'stdout' => $stdout, 'stderr' => $stderr);
    }

    protected function seedBankConfig()
    {
        Configuration::updateValue('BANK_WIRE_OWNER', 'Ada Lovelace');
        Configuration::updateValue('BANK_WIRE_DETAILS', "GB12BANK\n998877");
        Configuration::updateValue('BANK_WIRE_ADDRESS', "1 Threadneedle\nLondon");
        Configuration::updateValue('BANK_WIRE_RESERVATION_DAYS', '14');
        Configuration::updateValue(Ps_Wirepayment::FLAG_DISPLAY_PAYMENT_INVITE, '1');
    }

    protected function postValidConfiguration()
    {
        $_POST['btnSubmit'] = '1';
        $_POST['BANK_WIRE_OWNER'] = 'Ada Lovelace';
        $_POST['BANK_WIRE_DETAILS'] = "GB12BANK\n998877";
        $_POST['BANK_WIRE_ADDRESS'] = "1 Threadneedle\nLondon";
        $_POST['BANK_WIRE_RESERVATION_DAYS'] = '14';
        $_POST['BANK_WIRE_CUSTOM_TEXT_1'] = 'Ship after payment';
        $_POST['BANK_WIRE_CUSTOM_TEXT_2'] = 'Expedier apres paiement';
        $_POST[Ps_Wirepayment::FLAG_DISPLAY_PAYMENT_INVITE] = '1';
    }
}
