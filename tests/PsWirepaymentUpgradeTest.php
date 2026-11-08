<?php

require_once dirname(__FILE__) . '/WirepaymentTestCase.php';

class Ps_WirepaymentUpgradeTest extends WirepaymentTestCase
{
    public function testUpgradeModule211MigratesHook()
    {
        require_once dirname(__DIR__) . '/upgrade/upgrade-2.1.1.php';
        $module = wirepayment_new_module();
        $this->assertTrue(upgrade_module_2_1_1($module));
        $this->assertSame(
            array('unregister:paymentReturn', 'register:displayPaymentReturn'),
            ModuleTestRuntime::$hookLog
        );
    }

    public function testUpgradeModule211ReturnsFalseWhenRegisterFails()
    {
        require_once dirname(__DIR__) . '/upgrade/upgrade-2.1.1.php';
        ModuleTestRuntime::$registerHookResults['displayPaymentReturn'] = false;
        $module = wirepayment_new_module();
        $this->assertFalse(upgrade_module_2_1_1($module));
        $this->assertContains('unregister:paymentReturn', ModuleTestRuntime::$hookLog);
    }
}
