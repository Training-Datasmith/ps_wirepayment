<?php

require_once dirname(__FILE__) . '/WirepaymentTestCase.php';

class PsWirepaymentTemplateContractTest extends WirepaymentTestCase
{
    public function testIntroTemplateReferencesReservationAndCustomText()
    {
        $path = dirname(__DIR__) . '/views/templates/hook/ps_wirepayment_intro.tpl';
        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertContains('bankwireReservationDays', $content);
        $this->assertContains('bankwireCustomText', $content);
        $this->assertContains('payment_infos.tpl', $content);
    }

    public function testPaymentReturnTemplateReferencesOrderContext()
    {
        $path = dirname(__DIR__) . '/views/templates/hook/payment_return.tpl';
        $content = file_get_contents($path);
        $this->assertContains('shop_name', $content);
        $this->assertContains('reference', $content);
        $this->assertContains('contact_url', $content);
        $this->assertContains('payment_infos.tpl', $content);
    }

    public function testPaymentInfosPartialDefinesBankFields()
    {
        $path = dirname(__DIR__) . '/views/templates/hook/_partials/payment_infos.tpl';
        $content = file_get_contents($path);
        $this->assertContains('{$total}', $content);
        $this->assertContains('bankwireOwner', $content);
        $this->assertContains('bankwireDetails', $content);
        $this->assertContains('bankwireAddress', $content);
    }

    public function testInfosTemplateExists()
    {
        $this->assertFileExists(dirname(__DIR__) . '/views/templates/hook/infos.tpl');
    }

    public function testConfigXmlAndModuleVersionMatch()
    {
        $xml = file_get_contents(dirname(__DIR__) . '/config.xml');
        $this->assertContains('<version><![CDATA[2.2.1]]></version>', $xml);
        $this->assertContains('<name>ps_wirepayment</name>', $xml);
        $module = wirepayment_new_module();
        $this->assertSame('2.2.1', $module->version);
    }
}
