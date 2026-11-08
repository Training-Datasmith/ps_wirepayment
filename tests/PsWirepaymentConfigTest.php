<?php

require_once dirname(__FILE__) . '/WirepaymentTestCase.php';

class PsWirepaymentConfigTest extends WirepaymentTestCase
{
    public function testGetContentWithoutSubmitShowsBreakInfosAndForm()
    {
        $module = wirepayment_new_module();
        $html = $module->getContent();
        $this->assertSame('<br />' . 'DISPLAY:infos.tpl' . 'FORM', $html);
    }

    public function testValidSubmitPersistsAllFields()
    {
        $this->postValidConfiguration();
        $module = wirepayment_new_module();
        $module->getContent();
        $this->assertSame('Ada Lovelace', Configuration::get('BANK_WIRE_OWNER'));
        $this->assertSame("GB12BANK\n998877", Configuration::get('BANK_WIRE_DETAILS'));
        $this->assertSame("1 Threadneedle\nLondon", Configuration::get('BANK_WIRE_ADDRESS'));
        $this->assertSame('14', Configuration::get('BANK_WIRE_RESERVATION_DAYS'));
        $this->assertSame('Ship after payment', Configuration::get('BANK_WIRE_CUSTOM_TEXT', 1));
        $this->assertSame('Expedier apres paiement', Configuration::get('BANK_WIRE_CUSTOM_TEXT', 2));
        $this->assertSame('1', Configuration::get(Ps_Wirepayment::FLAG_DISPLAY_PAYMENT_INVITE));
        $fresh = wirepayment_new_module();
        $this->assertSame('Ada Lovelace', $fresh->owner);
    }

    public function testValidSubmitShowsConfirmation()
    {
        $this->postValidConfiguration();
        $module = wirepayment_new_module();
        $html = $module->getContent();
        $this->assertContains('CONFIRM:Settings updated', $html);
        $this->assertNotContains('ERROR:', $html);
    }

    public function testEmptyOwnerShowsError()
    {
        $this->postValidConfiguration();
        $_POST['BANK_WIRE_OWNER'] = '';
        Configuration::updateValue('BANK_WIRE_OWNER', 'seed');
        $module = wirepayment_new_module();
        $html = $module->getContent();
        $this->assertContains('ERROR:Account owner is required.', $html);
        $this->assertSame('seed', Configuration::get('BANK_WIRE_OWNER'));
    }

    public function testEmptyDetailsShowsError()
    {
        $this->postValidConfiguration();
        $_POST['BANK_WIRE_DETAILS'] = '';
        $module = wirepayment_new_module();
        $html = $module->getContent();
        $this->assertContains('ERROR:Account details are required.', $html);
    }

    public function testEmptyAddressShowsError()
    {
        $this->postValidConfiguration();
        $_POST['BANK_WIRE_ADDRESS'] = '';
        $module = wirepayment_new_module();
        $html = $module->getContent();
        $this->assertContains('ERROR:Bank address is required.', $html);
    }

  public function testInvalidReservationDaysRejected()
    {
        foreach (array('-1', '1.5', '7abc') as $bad) {
            wirepayment_reset_all_state();
            $this->postValidConfiguration();
            $_POST['BANK_WIRE_RESERVATION_DAYS'] = $bad;
            Configuration::updateValue('BANK_WIRE_RESERVATION_DAYS', 'seed-days');
            $module = wirepayment_new_module();
            $html = $module->getContent();
            $this->assertContains(
                'The Reservation period is invalid. Please enter a positive integer.',
                $html,
                'Failed for value ' . $bad
            );
            $this->assertSame('seed-days', Configuration::get('BANK_WIRE_RESERVATION_DAYS'));
        }
    }

    public function testPostedZeroAndEmptyStoreZeroReservation()
    {
        foreach (array('0', '') as $posted) {
            wirepayment_reset_all_state();
            $this->postValidConfiguration();
            $_POST['BANK_WIRE_RESERVATION_DAYS'] = $posted;
            $module = wirepayment_new_module();
            $module->getContent();
            $this->assertSame('0', Configuration::get('BANK_WIRE_RESERVATION_DAYS'));
            $fresh = wirepayment_new_module();
            $infos = $fresh->getTemplateVarInfos();
            $this->assertSame(0, $infos['bankwireReservationDays']);
        }
    }

    public function testFlagNotUpdatedOnValidationFailure()
    {
        Configuration::updateValue(Ps_Wirepayment::FLAG_DISPLAY_PAYMENT_INVITE, '1');
        $_POST['btnSubmit'] = '1';
        $_POST['BANK_WIRE_OWNER'] = '';
        $_POST['BANK_WIRE_DETAILS'] = 'd';
        $_POST['BANK_WIRE_ADDRESS'] = 'a';
        $_POST[Ps_Wirepayment::FLAG_DISPLAY_PAYMENT_INVITE] = '0';
        $module = wirepayment_new_module();
        $module->getContent();
        $this->assertSame('1', Configuration::get(Ps_Wirepayment::FLAG_DISPLAY_PAYMENT_INVITE));
    }

    public function testAllRequiredErrorsWhenAllEmpty()
    {
        $_POST['btnSubmit'] = '1';
        $module = wirepayment_new_module();
        $html = $module->getContent();
        $this->assertContains('ERROR:Account owner is required.', $html);
        $this->assertContains('ERROR:Account details are required.', $html);
        $this->assertContains('ERROR:Bank address is required.', $html);
        $this->assertNotContains('CONFIRM:', $html);
    }

    public function testGetConfigFieldsValuesPrefersPostedOwner()
    {
        Configuration::updateValue('BANK_WIRE_OWNER', 'stored');
        $_POST['BANK_WIRE_OWNER'] = 'posted';
        $module = wirepayment_new_module();
        $values = $module->getConfigFieldsValues();
        $this->assertSame('posted', $values['BANK_WIRE_OWNER']);
    }

    public function testCustomTextPartialLanguageUpdate()
    {
        Configuration::updateValue('BANK_WIRE_CUSTOM_TEXT', array(1 => 'Hello', 2 => 'Bonjour'));
        $_POST['BANK_WIRE_CUSTOM_TEXT_1'] = 'Updated';
        $_POST['btnSubmit'] = '1';
        $_POST['BANK_WIRE_OWNER'] = 'o';
        $_POST['BANK_WIRE_DETAILS'] = 'd';
        $_POST['BANK_WIRE_ADDRESS'] = 'a';
        $module = wirepayment_new_module();
        $module->getContent();
        $this->assertSame('Updated', Configuration::get('BANK_WIRE_CUSTOM_TEXT', 1));
        $this->assertSame('Bonjour', Configuration::get('BANK_WIRE_CUSTOM_TEXT', 2));
    }

    public function testGetConfigFieldsValuesCustomTextKeys()
    {
        Configuration::updateValue('BANK_WIRE_CUSTOM_TEXT', array(1 => 'a', 2 => 'b'));
        $module = wirepayment_new_module();
        $values = $module->getConfigFieldsValues();
        $this->assertArrayHasKey(1, $values['BANK_WIRE_CUSTOM_TEXT']);
        $this->assertArrayHasKey(2, $values['BANK_WIRE_CUSTOM_TEXT']);
    }

    public function testRenderFormFieldRequirements()
    {
        $module = wirepayment_new_module();
        $module->renderForm();
        $forms = HelperFormTestRuntime::$lastForms;
        $this->assertTrue($forms[0]['form']['input'][0]['required']);
        $this->assertTrue($forms[0]['form']['input'][1]['required']);
        $this->assertTrue($forms[0]['form']['input'][2]['required']);
        $this->assertArrayNotHasKey('required', $forms[1]['form']['input'][0]);
        $this->assertTrue($forms[1]['form']['input'][1]['lang']);
        $this->assertTrue($forms[1]['form']['input'][2]['is_bool']);
    }

    public function testRenderFormHelperMetadata()
    {
        $module = wirepayment_new_module();
        $module->renderForm();
        $helper = HelperFormTestRuntime::$lastInstance;
        $this->assertSame('btnSubmit', $helper->submit_action);
        $this->assertSame('lite-token-AdminModules', $helper->token);
        $this->assertSame(
            'adminlink:AdminModules/notoken&configure=ps_wirepayment&tab_module=payments_gateways&module_name=ps_wirepayment',
            $helper->currentIndex
        );
        $this->assertSame(0, $helper->allow_employee_form_lang);
        $this->assertSame(1, $helper->default_form_language);
    }
}
