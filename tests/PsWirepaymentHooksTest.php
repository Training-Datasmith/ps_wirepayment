<?php

require_once dirname(__FILE__) . '/WirepaymentTestCase.php';

class Ps_WirepaymentHooksTest extends WirepaymentTestCase
{
    public function testGetTemplateVarInfosTotalWithTaxesBoth()
    {
        $module = wirepayment_new_module();
        $infos = $module->getTemplateVarInfos();
        $this->assertSame('100.25 EUR (tax incl.)', $infos['total']);
    }

    public function testGetTemplateVarInfosPlaceholdersWhenEmpty()
    {
        $module = wirepayment_new_module();
        $infos = $module->getTemplateVarInfos();
        $this->assertSame('___________', $infos['bankwireOwner']);
        $this->assertSame('___________', $infos['bankwireDetails']);
        $this->assertSame('___________', $infos['bankwireAddress']);
    }

    public function testGetTemplateVarInfosNl2brOnDetailsAndAddress()
    {
        Configuration::updateValue('BANK_WIRE_DETAILS', "Line1\nLine2");
        Configuration::updateValue('BANK_WIRE_ADDRESS', "Addr1\nAddr2");
        Configuration::updateValue('BANK_WIRE_OWNER', 'Owner');
        $module = wirepayment_new_module();
        $infos = $module->getTemplateVarInfos();
        $this->assertSame('Line1<br />Line2', $infos['bankwireDetails']);
        $this->assertSame('Addr1<br />Addr2', $infos['bankwireAddress']);
    }

    public function testGetTemplateVarInfosReservationCast()
    {
        Configuration::updateValue('BANK_WIRE_RESERVATION_DAYS', '14');
        $module = wirepayment_new_module();
        $infos = $module->getTemplateVarInfos();
        $this->assertSame(14, $infos['bankwireReservationDays']);
        Configuration::updateValue('BANK_WIRE_RESERVATION_DAYS', '1');
        $module = wirepayment_new_module();
        $infos = $module->getTemplateVarInfos();
        $this->assertSame(1, $infos['bankwireReservationDays']);
    }

    public function testGetTemplateVarInfosCustomTextPerLanguage()
    {
        Configuration::updateValue('BANK_WIRE_CUSTOM_TEXT', array(1 => "Text1\nA", 2 => "Texte2\nB"));
        Context::getContext()->language = new Language(2);
        $module = wirepayment_new_module();
        $infos = $module->getTemplateVarInfos();
        $this->assertSame('Texte2<br />B', $infos['bankwireCustomText']);
        $this->assertNotContains('Text1', $infos['bankwireCustomText']);
    }

    public function testHookDisplayPaymentReturnNullWhenInactive()
    {
        $module = wirepayment_new_module(false);
        $result = $module->hookDisplayPaymentReturn(array('order' => new WirepaymentOrder()));
        $this->assertNull($result);
        $this->assertFalse($module->fetchCalled);
    }

    public function testHookDisplayPaymentReturnNullWhenFlagOff()
    {
        foreach (array(false, '', '0') as $flag) {
            wirepayment_reset_all_state();
            Configuration::updateValue(Ps_Wirepayment::FLAG_DISPLAY_PAYMENT_INVITE, $flag);
            $module = wirepayment_new_module();
            $result = $module->hookDisplayPaymentReturn(array('order' => new WirepaymentOrder()));
            $this->assertNull($result);
        }
    }

    public function testHookDisplayPaymentReturnFormatsRemainingTotalInOrderCurrency()
    {
        Configuration::updateValue(Ps_Wirepayment::FLAG_DISPLAY_PAYMENT_INVITE, '1');
        $module = wirepayment_new_module();
        $module->hookDisplayPaymentReturn(array('order' => new WirepaymentOrder()));
        $this->assertSame('75.25 USD', $module->smarty->getTemplateVars('total'));
    }

    public function testHookDisplayPaymentReturnRendersTemplateAndMetadata()
    {
        Configuration::updateValue(Ps_Wirepayment::FLAG_DISPLAY_PAYMENT_INVITE, '1');
        Configuration::updateValue('BANK_WIRE_OWNER', 'Owner');
        $module = wirepayment_new_module();
        $result = $module->hookDisplayPaymentReturn(array('order' => new WirepaymentOrder()));
        $this->assertSame('ok', $module->smarty->getTemplateVars('status'));
        $this->assertSame('XKBKNABJK', $module->smarty->getTemplateVars('reference'));
        $this->assertSame('Test Shop', $module->smarty->getTemplateVars('shop_name'));
        $this->assertSame('pagelink:contact/ssl/', $module->smarty->getTemplateVars('contact_url'));
        $this->assertSame(
            'FETCH:module:ps_wirepayment/views/templates/hook/payment_return.tpl',
            $result
        );
    }
}
