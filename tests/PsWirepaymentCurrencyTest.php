<?php

require_once dirname(__FILE__) . '/WirepaymentTestCase.php';

class PsWirepaymentCurrencyTest extends WirepaymentTestCase
{
    public function testCheckCurrencyReturnsTrueForMatchingCurrency()
    {
        $module = wirepayment_new_module();
        $cart = new Cart();
        $cart->id_currency = 2;
        $this->assertTrue($module->checkCurrency($cart));
    }

    public function testCheckCurrencyReturnsFalseForUnknownCurrency()
    {
        $module = wirepayment_new_module();
        $cart = new Cart();
        $cart->id_currency = 3;
        $this->assertFalse($module->checkCurrency($cart));
    }

    public function testCheckCurrencyReturnsFalseWhenGetCurrencyNotArray()
    {
        $module = wirepayment_new_module();
        $module->currencies = false;
        $cart = new Cart();
        $this->assertFalse($module->checkCurrency($cart));
    }

    public function testHookPaymentOptionsEmptyWhenInactive()
    {
        $module = wirepayment_new_module(false);
        $result = $module->hookPaymentOptions(array('cart' => new Cart()));
        $this->assertSame(array(), $result);
        $this->assertFalse($module->fetchCalled);
    }

    public function testHookPaymentOptionsEmptyWhenCurrencyRejected()
    {
        $module = wirepayment_new_module();
        $cart = new Cart();
        $cart->id_currency = 3;
        $result = $module->hookPaymentOptions(array('cart' => $cart));
        $this->assertSame(array(), $result);
        $this->assertFalse($module->fetchCalled);
    }

    public function testHookPaymentOptionsBuildsPaymentOption()
    {
        $this->seedBankConfig();
        $module = wirepayment_new_module();
        $cart = new Cart();
        $cart->id_currency = 2;
        $options = $module->hookPaymentOptions(array('cart' => $cart));
        $this->assertCount(1, $options);
        $option = $options[0];
        $this->assertInstanceOf('PrestaShop\\PrestaShop\\Core\\Payment\\PaymentOption', $option);
        $this->assertSame('ps_wirepayment', $option->getModuleName());
        $this->assertSame('Pay by bank wire', $option->getCallToActionText());
        $this->assertSame('modulelink:ps_wirepayment/validation/ssl', $option->getAction());
        $this->assertSame(
            'FETCH:module:ps_wirepayment/views/templates/hook/ps_wirepayment_intro.tpl',
            $option->getAdditionalInformation()
        );
        $this->assertSame('100.25 EUR (tax incl.)', $module->smarty->getTemplateVars('total'));
    }
}
