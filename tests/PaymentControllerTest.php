<?php

require_once dirname(__FILE__) . '/WirepaymentTestCase.php';

class PaymentControllerTest extends WirepaymentTestCase
{
    public function testInitContentRedirectsWhenCurrencyRejected()
    {
        $controller = new Ps_WirepaymentPaymentModuleFrontController();
        $controller->module = wirepayment_new_module();
        $controller->context->cart->id_currency = 3;
        try {
            $controller->initContent();
            $this->fail('Expected redirect');
        } catch (WirepaymentTestRedirect $e) {
            $this->assertSame('index.php?controller=order', $e->url);
        }
        $this->assertTrue(ModuleFrontControllerTestRuntime::$initContentCalled);
        $this->assertNull(ModuleFrontControllerTestRuntime::$template);
    }

    public function testInitContentAssignsTemplateVariables()
    {
        $controller = new Ps_WirepaymentPaymentModuleFrontController();
        $controller->module = wirepayment_new_module();
        $controller->context->cart->id_currency = 2;
        $controller->initContent();
        $this->assertTrue(ModuleFrontControllerTestRuntime::$initContentCalled);
        $this->assertSame('payment_execution.tpl', ModuleFrontControllerTestRuntime::$template);
        $vars = $controller->context->smarty->tpl_vars;
        $this->assertSame('pagelink:order/ssl/step=3', $vars['back_url']);
        $this->assertSame('modulelink:ps_wirepayment/validation/ssl', $vars['confirm_url']);
        $this->assertSame('100.25 EUR (tax incl.)', $vars['total']);
        $this->assertSame(2, $vars['cust_currency']);
        $this->assertSame(ModuleTestRuntime::$paymentCurrencies, $vars['currencies']);
        $this->assertSame('/shop/modules/ps_wirepayment/', $vars['this_path']);
        $this->assertSame('https://shop.example/shop/modules/ps_wirepayment/', $vars['this_path_ssl']);
    }
}
