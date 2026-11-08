<?php

require_once dirname(__FILE__) . '/WirepaymentTestCase.php';

class PsWirepaymentInstallTest extends WirepaymentTestCase
{
    public function testInstallReturnsTrueSetsFlagAndRegistersHooksInOrder()
    {
        $module = wirepayment_new_module();
        $this->assertTrue($module->install());
        $this->assertSame('1', Configuration::get(Ps_Wirepayment::FLAG_DISPLAY_PAYMENT_INVITE));
        $this->assertSame(
            array('register:displayPaymentReturn', 'register:paymentOptions'),
            ModuleTestRuntime::$hookLog
        );
    }

    public function testInstallReturnsFalseWhenParentInstallFails()
    {
        ModuleTestRuntime::$parentInstallResult = false;
        $module = wirepayment_new_module();
        $this->assertFalse($module->install());
        $this->assertSame(array(), ModuleTestRuntime::$hookLog);
    }

    public function testInstallReturnsFalseWhenDisplayPaymentReturnRegistrationFails()
    {
        ModuleTestRuntime::$registerHookResults['displayPaymentReturn'] = false;
        $module = wirepayment_new_module();
        $this->assertFalse($module->install());
    }

    public function testInstallReturnsFalseWhenPaymentOptionsRegistrationFails()
    {
        ModuleTestRuntime::$registerHookResults['paymentOptions'] = false;
        $module = wirepayment_new_module();
        $this->assertFalse($module->install());
    }

    public function testInstallDoesNotRegisterPaymentOptionsWhenDisplayPaymentReturnFails()
    {
        ModuleTestRuntime::$registerHookResults['displayPaymentReturn'] = false;
        $module = wirepayment_new_module();
        $module->install();
        $this->assertSame(array('register:displayPaymentReturn'), ModuleTestRuntime::$hookLog);
    }

    public function testUninstallDeletesAllConfigurationKeysAndReturnsTrue()
    {
        Configuration::updateValue('BANK_WIRE_CUSTOM_TEXT', array(1 => 'A'));
        Configuration::updateValue('BANK_WIRE_DETAILS', 'd');
        Configuration::updateValue('BANK_WIRE_OWNER', 'o');
        Configuration::updateValue('BANK_WIRE_ADDRESS', 'a');
        Configuration::updateValue('BANK_WIRE_RESERVATION_DAYS', '3');
        Configuration::updateValue(Ps_Wirepayment::FLAG_DISPLAY_PAYMENT_INVITE, '1');
        $module = wirepayment_new_module();
        $this->assertTrue($module->uninstall());
        $this->assertFalse(Configuration::get('BANK_WIRE_OWNER'));
        $this->assertFalse(Configuration::get('BANK_WIRE_DETAILS'));
        $this->assertFalse(Configuration::get('BANK_WIRE_ADDRESS'));
        $this->assertFalse(Configuration::get('BANK_WIRE_RESERVATION_DAYS'));
        $this->assertFalse(Configuration::get('BANK_WIRE_CUSTOM_TEXT'));
        $this->assertFalse(Configuration::get(Ps_Wirepayment::FLAG_DISPLAY_PAYMENT_INVITE));
        $this->assertSame('shop@test.example', Configuration::get('PS_SHOP_EMAIL'));
    }

    public function testUninstallReturnsFalseWhenCustomTextDeleteFails()
    {
        Configuration::updateValue('BANK_WIRE_CUSTOM_TEXT', array(1 => 'A'));
        Configuration::updateValue('BANK_WIRE_OWNER', 'seed-owner');
        ConfigurationTestStore::$deleteFailKeys = array('BANK_WIRE_CUSTOM_TEXT');
        $module = wirepayment_new_module();
        $this->assertFalse($module->uninstall());
        $this->assertSame('seed-owner', Configuration::get('BANK_WIRE_OWNER'));
    }

    public function testUninstallReturnsFalseWhenParentUninstallFails()
    {
        ModuleTestRuntime::$parentUninstallResult = false;
        Configuration::updateValue('BANK_WIRE_OWNER', 'o');
        $module = wirepayment_new_module();
        $this->assertFalse($module->uninstall());
    }

    public function testConstructorLoadsNonEmptyConfigurationOntoProperties()
    {
        Configuration::updateValue('BANK_WIRE_OWNER', 'Ada');
        Configuration::updateValue('BANK_WIRE_DETAILS', 'GB12');
        Configuration::updateValue('BANK_WIRE_ADDRESS', 'London');
        Configuration::updateValue('BANK_WIRE_RESERVATION_DAYS', '14');
        $module = wirepayment_new_module();
        $this->assertSame('Ada', $module->owner);
        $this->assertSame('GB12', $module->details);
        $this->assertSame('London', $module->address);
        $this->assertSame('14', $module->reservation_days);
    }

    public function testConstructorWarningWhenAccountIncomplete()
    {
        ModuleTestRuntime::$checkPaymentCurrenciesResult = array(array('id_currency' => '1'));
        $module = wirepayment_new_module(true);
        $this->assertSame(
            'Account owner and account details must be configured before using this module.',
            $module->warning
        );
    }

    public function testConstructorWarningWhenNoCurrencyConfigured()
    {
        Configuration::updateValue('BANK_WIRE_OWNER', 'Ada');
        Configuration::updateValue('BANK_WIRE_DETAILS', 'GB12');
        Configuration::updateValue('BANK_WIRE_ADDRESS', 'London');
        ModuleTestRuntime::$checkPaymentCurrenciesResult = array();
        $module = wirepayment_new_module(true);
        $this->assertSame('No currency has been set for this module.', $module->warning);
    }

    public function testConstructorWarningContainsBothWhenAccountAndCurrencyMissing()
    {
        ModuleTestRuntime::$checkPaymentCurrenciesResult = array();
        $module = wirepayment_new_module(true);
        $this->assertContains(
            'Account owner and account details must be configured before using this module.',
            $module->warning
        );
        $this->assertContains('No currency has been set for this module.', $module->warning);
        $this->assertContains('<br />', $module->warning);
    }

    public function testConstructorNoWarningWhenInactive()
    {
        ModuleTestRuntime::$checkPaymentCurrenciesResult = array();
        $module = wirepayment_new_module(false);
        $this->assertSame('', (string) $module->warning);
    }

    public function testExtraMailVarsNl2brOnDetailsAndAddress()
    {
        Configuration::updateValue('BANK_WIRE_OWNER', 'Owner');
        Configuration::updateValue('BANK_WIRE_DETAILS', "GB12BANK\n998877");
        Configuration::updateValue('BANK_WIRE_ADDRESS', "Line1\nLine2");
        $module = wirepayment_new_module();
        $this->assertSame('Owner', $module->extra_mail_vars['{bankwire_owner}']);
        $this->assertSame("GB12BANK<br />\n998877", $module->extra_mail_vars['{bankwire_details}']);
        $this->assertSame("Line1<br />\nLine2", $module->extra_mail_vars['{bankwire_address}']);
    }
}
