<?php

require_once dirname(__FILE__) . '/WirepaymentTestCase.php';

class ValidationControllerTest extends WirepaymentTestCase
{
    private function makeController()
    {
        $controller = new Ps_WirepaymentValidationModuleFrontController();
        $controller->module = new Ps_WirepaymentValidateOrderSpy();
        $controller->module->active = true;
        $controller->module->id = 7;
        $controller->module->displayName = 'Wire payment';
        $controller->context->cart = new Cart();

        return $controller;
    }

    public function testRedirectsWhenCustomerMissing()
    {
        $controller = $this->makeController();
        $controller->context->cart->id_customer = 0;
        try {
            $controller->postProcess();
            $this->fail('Expected redirect');
        } catch (WirepaymentTestRedirect $e) {
            $this->assertSame('index.php?controller=order&step=1', $e->url);
        }
        $this->assertSame(array(), PaymentModuleTestRuntime::$validateOrderCalls);
    }

    public function testRedirectsWhenDeliveryAddressMissing()
    {
        $controller = $this->makeController();
        $controller->context->cart->id_address_delivery = 0;
        try {
            $controller->postProcess();
            $this->fail('Expected redirect');
        } catch (WirepaymentTestRedirect $e) {
            $this->assertSame('index.php?controller=order&step=1', $e->url);
        }
    }

    public function testRedirectsWhenInvoiceAddressMissing()
    {
        $controller = $this->makeController();
        $controller->context->cart->id_address_invoice = 0;
        try {
            $controller->postProcess();
            $this->fail('Expected redirect');
        } catch (WirepaymentTestRedirect $e) {
            $this->assertSame('index.php?controller=order&step=1', $e->url);
        }
    }

    public function testRedirectsWhenModuleInactive()
    {
        $controller = $this->makeController();
        $controller->module->active = false;
        try {
            $controller->postProcess();
            $this->fail('Expected redirect');
        } catch (WirepaymentTestRedirect $e) {
            $this->assertSame('index.php?controller=order&step=1', $e->url);
        }
    }

    public function testExitsWhenPaymentMethodUnavailable()
    {
        $result = $this->runPhpChild(dirname(__FILE__) . '/bin/validation-unavailable-runner.php');
        $this->assertSame(0, $result['exit']);
        $this->assertSame('This payment method is not available.', $result['stdout']);
    }

    public function testRedirectsWhenCustomerNotLoaded()
    {
        CustomerTestStore::$customers[12] = array('secure_key' => 'x', 'empty' => true);
        $controller = $this->makeController();
        try {
            $controller->postProcess();
            $this->fail('Expected redirect');
        } catch (WirepaymentTestRedirect $e) {
            $this->assertSame('index.php?controller=order&step=1', $e->url);
        }
    }

    public function testValidateOrderArguments()
    {
        Configuration::updateValue('BANK_WIRE_OWNER', 'Owner');
        Configuration::updateValue('BANK_WIRE_DETAILS', "Det\n2");
        Configuration::updateValue('BANK_WIRE_ADDRESS', "Addr\n3");
        $controller = $this->makeController();
        try {
            $controller->postProcess();
            $this->fail('Expected redirect');
        } catch (WirepaymentTestRedirect $e) {
            $this->assertContains('order-confirmation', $e->url);
        }
        $this->assertCount(1, PaymentModuleTestRuntime::$validateOrderCalls);
        $call = PaymentModuleTestRuntime::$validateOrderCalls[0];
        $this->assertSame(44, $call[0]);
        $this->assertSame(10, (int) $call[1]);
        $this->assertSame(19.9, (float) $call[2]);
        $this->assertSame('Wire payment', $call[3]);
        $this->assertNull($call[4]);
        $this->assertSame('Owner', $call[5]['{bankwire_owner}']);
        $this->assertSame("Det<br />\n2", $call[5]['{bankwire_details}']);
        $this->assertSame("Addr<br />\n3", $call[5]['{bankwire_address}']);
        $this->assertSame(3, (int) $call[6]);
        $this->assertFalse($call[7]);
        $this->assertSame('sec-key-99', $call[8]);
    }

    public function testConfirmationRedirectQuery()
    {
        Configuration::updateValue('BANK_WIRE_OWNER', 'o');
        Configuration::updateValue('BANK_WIRE_DETAILS', 'd');
        Configuration::updateValue('BANK_WIRE_ADDRESS', 'a');
        $controller = $this->makeController();
        try {
            $controller->postProcess();
            $this->fail('Expected redirect');
        } catch (WirepaymentTestRedirect $e) {
            $this->assertSame(
                'index.php?controller=order-confirmation&id_cart=44&id_module=7&id_order=501&key=sec-key-99',
                $e->url
            );
        }
    }
}
