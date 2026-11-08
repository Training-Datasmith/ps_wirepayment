<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: revert-proof.php <work-dir-with-phpunit-phar>\n");
    exit(2);
}

$workDir = $argv[1];
$root = dirname(__DIR__);
$phpunit = $workDir . '/phpunit-4.8.36.phar';
if (!is_file($phpunit)) {
    fwrite(STDERR, "Missing phpunit phar in work dir\n");
    exit(2);
}

$exportBase = sys_get_temp_dir() . '/wirepayment-revert-' . getmypid();
if (!mkdir($exportBase, 0700, true) && !is_dir($exportBase)) {
    exit(2);
}

$cases = array(
    array('testRootIndexExitsSilently', 'index.php', 'remove_final_exit'),
    array('testControllersIndexExitsSilently', 'controllers/index.php', 'remove_final_exit'),
    array('testControllersFrontIndexExitsSilently', 'controllers/front/index.php', 'remove_final_exit'),
    array('testTranslationsIndexExitsSilently', 'translations/index.php', 'remove_final_exit'),
    array('testUpgradeIndexExitsSilently', 'upgrade/index.php', 'remove_final_exit'),
    array('testViewsIndexExitsSilently', 'views/index.php', 'remove_final_exit'),
    array('testTemplatesIndexExitsSilently', 'views/templates/index.php', 'remove_final_exit'),
    array('testHookTemplatesIndexExitsSilently', 'views/templates/hook/index.php', 'remove_final_exit'),
    array('testInstallReturnsTrueSetsFlagAndRegistersHooksInOrder', 'ps_wirepayment.php', 'hook_payment_options_typo'),
    array('testInstallReturnsFalseWhenParentInstallFails', 'ps_wirepayment.php', 'parent_install_always_true'),
    array('testInstallReturnsFalseWhenDisplayPaymentReturnRegistrationFails', 'ps_wirepayment.php', 'drop_display_hook_check'),
    array('testInstallReturnsFalseWhenPaymentOptionsRegistrationFails', 'ps_wirepayment.php', 'drop_payment_options_check'),
    array('testInstallDoesNotRegisterPaymentOptionsWhenDisplayPaymentReturnFails', 'ps_wirepayment.php', 'install_register_both'),
    array('testUninstallDeletesAllConfigurationKeysAndReturnsTrue', 'ps_wirepayment.php', 'drop_owner_delete'),
    array('testUninstallReturnsFalseWhenCustomTextDeleteFails', 'tests/doubles.php', 'uninstall_ignore_delete_fail'),
    array('testUninstallReturnsFalseWhenParentUninstallFails', 'ps_wirepayment.php', 'parent_uninstall_always_true'),
    array('testConstructorLoadsNonEmptyConfigurationOntoProperties', 'ps_wirepayment.php', 'drop_owner_assign'),
    array('testConstructorWarningWhenAccountIncomplete', 'ps_wirepayment.php', 'drop_account_warning'),
    array('testConstructorWarningWhenNoCurrencyConfigured', 'ps_wirepayment.php', 'drop_currency_warning'),
    array('testConstructorWarningContainsBothWhenAccountAndCurrencyMissing', 'ps_wirepayment.php', 'currency_overwrites_warning'),
    array('testConstructorNoWarningWhenInactive', 'ps_wirepayment.php', 'drop_active_guard_warnings'),
    array('testExtraMailVarsNl2brOnDetailsAndAddress', 'ps_wirepayment.php', 'mail_vars_no_nl2br'),
    array('testGetContentWithoutSubmitShowsBreakInfosAndForm', 'ps_wirepayment.php', 'drop_getcontent_br'),
    array('testValidSubmitPersistsAllFields', 'ps_wirepayment.php', 'drop_owner_update'),
    array('testValidSubmitShowsConfirmation', 'ps_wirepayment.php', 'drop_confirmation'),
    array('testEmptyOwnerShowsError', 'ps_wirepayment.php', 'drop_owner_validation'),
    array('testEmptyDetailsShowsError', 'ps_wirepayment.php', 'drop_details_validation'),
    array('testEmptyAddressShowsError', 'ps_wirepayment.php', 'drop_address_validation'),
    array('testInvalidReservationDaysRejected', 'ps_wirepayment.php', 'drop_reservation_validation'),
    array('testPostedZeroAndEmptyStoreZeroReservation', 'ps_wirepayment.php', 'reservation_fallback_seven'),
    array('testFlagNotUpdatedOnValidationFailure', 'ps_wirepayment.php', 'flag_in_validation'),
    array('testAllRequiredErrorsWhenAllEmpty', 'ps_wirepayment.php', 'single_owner_error'),
    array('testGetConfigFieldsValuesPrefersPostedOwner', 'ps_wirepayment.php', 'config_owner_stored_only'),
    array('testCustomTextPartialLanguageUpdate', 'ps_wirepayment.php', 'custom_text_all_langs'),
    array('testGetConfigFieldsValuesCustomTextKeys', 'tests/doubles.php', 'languages_active_only'),
    array('testRenderFormFieldRequirements', 'ps_wirepayment.php', 'reservation_required'),
    array('testRenderFormHelperMetadata', 'ps_wirepayment.php', 'submit_action_typo'),
    array('testCheckCurrencyReturnsTrueForMatchingCurrency', 'ps_wirepayment.php', 'check_currency_false'),
    array('testCheckCurrencyReturnsFalseForUnknownCurrency', 'ps_wirepayment.php', 'check_currency_true'),
    array('testCheckCurrencyReturnsFalseWhenGetCurrencyNotArray', 'ps_wirepayment.php', 'drop_is_array_guard'),
    array('testHookPaymentOptionsEmptyWhenInactive', 'ps_wirepayment.php', 'drop_hook_active_guard'),
    array('testHookPaymentOptionsEmptyWhenCurrencyRejected', 'ps_wirepayment.php', 'drop_hook_currency_guard'),
    array('testHookPaymentOptionsBuildsPaymentOption', 'ps_wirepayment.php', 'validation_controller_in_action'),
    array('testGetTemplateVarInfosTotalWithTaxesBoth', 'ps_wirepayment.php', 'total_tax_excl'),
    array('testGetTemplateVarInfosPlaceholdersWhenEmpty', 'ps_wirepayment.php', 'drop_placeholders'),
    array('testGetTemplateVarInfosNl2brOnDetailsAndAddress', 'ps_wirepayment.php', 'template_raw_details'),
    array('testGetTemplateVarInfosReservationCast', 'ps_wirepayment.php', 'drop_reservation_cast'),
    array('testGetTemplateVarInfosCustomTextPerLanguage', 'ps_wirepayment.php', 'custom_text_lang_one'),
    array('testHookDisplayPaymentReturnNullWhenInactive', 'ps_wirepayment.php', 'drop_return_active_guard'),
    array('testHookDisplayPaymentReturnNullWhenFlagOff', 'ps_wirepayment.php', 'drop_return_flag_guard'),
    array('testHookDisplayPaymentReturnFormatsRemainingTotalInOrderCurrency', 'ps_wirepayment.php', 'return_total_gross'),
    array('testHookDisplayPaymentReturnRendersTemplateAndMetadata', 'ps_wirepayment.php', 'return_status_pending'),
    array('testUpgradeModule211MigratesHook', 'upgrade/upgrade-2.1.1.php', 'register_old_hook'),
    array('testUpgradeModule211ReturnsFalseWhenRegisterFails', 'upgrade/upgrade-2.1.1.php', 'upgrade_always_true'),
    array('testInitContentRedirectsWhenCurrencyRejected', 'controllers/front/payment.php', 'payment_redirect_step1'),
    array('testInitContentAssignsTemplateVariables', 'controllers/front/payment.php', 'payment_template_typo'),
    array('testRedirectsWhenCustomerMissing', 'controllers/front/validation.php', 'drop_customer_guard'),
    array('testRedirectsWhenDeliveryAddressMissing', 'controllers/front/validation.php', 'drop_delivery_guard'),
    array('testRedirectsWhenInvoiceAddressMissing', 'controllers/front/validation.php', 'drop_invoice_guard'),
    array('testRedirectsWhenModuleInactive', 'controllers/front/validation.php', 'drop_module_active_guard'),
    array('testExitsWhenPaymentMethodUnavailable', 'controllers/front/validation.php', 'payment_name_cheque'),
    array('testRedirectsWhenCustomerNotLoaded', 'controllers/front/validation.php', 'drop_loaded_object_check'),
    array('testValidateOrderArguments', 'controllers/front/validation.php', 'order_total_products_only'),
    array('testConfirmationRedirectQuery', 'controllers/front/validation.php', 'drop_key_query'),
    array('testIntroTemplateReferencesReservationAndCustomText', 'views/templates/hook/ps_wirepayment_intro.tpl', 'drop_reservation_token'),
    array('testPaymentReturnTemplateReferencesOrderContext', 'views/templates/hook/payment_return.tpl', 'drop_reference_token'),
    array('testPaymentInfosPartialDefinesBankFields', 'views/templates/hook/_partials/payment_infos.tpl', 'drop_owner_token'),
    array('testInfosTemplateExists', 'views/templates/hook/infos.tpl', 'rename_infos_template'),
    array('testConfigXmlAndModuleVersionMatch', 'ps_wirepayment.php', 'version_bump'),
);

function apply_mutation($relativePath, $mutation, $dir)
{
    $path = $dir . '/' . $relativePath;
    if (!is_file($path)) {
        throw new RuntimeException('Missing path for mutation: ' . $relativePath);
    }
    $content = file_get_contents($path);
    switch ($mutation) {
        case 'remove_final_exit':
            $content = preg_replace('/\nexit;\s*$/', "\n", $content);
            break;
        case 'hook_payment_options_typo':
            $content = str_replace("registerHook('paymentOptions')", "registerHook('payment')", $content);
            break;
        case 'parent_install_always_true':
            $content = str_replace('!parent::install()', 'false', $content);
            break;
        case 'drop_display_hook_check':
            $content = preg_replace(
                '/\|\| !\$this->registerHook\(\'displayPaymentReturn\'\)\s*\n/',
                "\n",
                $content
            );
            break;
        case 'drop_payment_options_check':
            $content = preg_replace(
                '/\|\| !\$this->registerHook\(\'paymentOptions\'\)\s*\n/',
                "\n",
                $content
            );
            break;
        case 'install_register_both':
            $content = str_replace(
                "        if (!parent::install()\n            || !\$this->registerHook('displayPaymentReturn')\n            || !\$this->registerHook('paymentOptions')\n        ) {\n            return false;\n        }\n\n        return true;",
                "        \$this->registerHook('displayPaymentReturn');\n        \$this->registerHook('paymentOptions');\n        return parent::install();",
                $content
            );
            break;
        case 'drop_owner_delete':
            $content = str_replace("|| !Configuration::deleteByName('BANK_WIRE_OWNER')", '', $content);
            break;
        case 'uninstall_ignore_delete_fail':
            $content = str_replace(
                "if (in_array(\$key, ConfigurationTestStore::\$deleteFailKeys, true)) {\n            return false;\n        }",
                "/* ignore delete fail */",
                $content
            );
            break;
        case 'parent_uninstall_always_true':
            $content = str_replace('!parent::uninstall()', 'false', $content);
            break;
        case 'drop_owner_assign':
            $content = preg_replace(
                "/if \(!empty\(\$config\['BANK_WIRE_OWNER'\]\)\) \{\n\s*\\\$this->owner = \\\$config\['BANK_WIRE_OWNER'\];\n\s*\}\n/",
                '',
                $content
            );
            break;
        case 'drop_account_warning':
            $content = preg_replace(
                '/if \(\(!isset\(\$this->owner\).*?Modules\.Wirepayment\.Admin\'\);\n\s*\}\n/s',
                '',
                $content,
                1
            );
            break;
        case 'drop_currency_warning':
            $content = preg_replace(
                '/if \(!count\(Currency::checkPaymentCurrencies\(\$this->id\)\) && \$this->active\) \{.*?\}\n/s',
                '',
                $content,
                1
            );
            break;
        case 'currency_overwrites_warning':
            $content = str_replace(
                "            if (!empty(\$this->warning)) {\n                \$this->warning .= '<br />' . \$currencyWarning;\n            } else {\n                \$this->warning = \$currencyWarning;\n            }",
                '            $this->warning = $currencyWarning;',
                $content
            );
            break;
        case 'drop_active_guard_warnings':
            $content = str_replace('&& $this->active', '', $content);
            break;
        case 'mail_vars_no_nl2br':
            $content = str_replace("nl2br(\$this->details ?: '')", '$this->details', $content);
            $content = str_replace("nl2br(\$this->address ?: '')", '$this->address', $content);
            break;
        case 'drop_getcontent_br':
            $content = str_replace("\$this->_html .= '<br />';", '$this->_html .= "";', $content);
            break;
        case 'drop_owner_update':
            $content = str_replace(
                "Configuration::updateValue('BANK_WIRE_OWNER', Tools::getValue('BANK_WIRE_OWNER'));\n",
                '',
                $content
            );
            break;
        case 'drop_confirmation':
            $content = str_replace('$this->_html .= $this->displayConfirmation', '$this->_html .= "" && $this->displayConfirmation', $content);
            break;
        case 'drop_owner_validation':
            $content = preg_replace("/if \(!Tools::getValue\('BANK_WIRE_OWNER'\)\) \{.*?\}\n/s", '', $content, 1);
            break;
        case 'drop_details_validation':
            $content = preg_replace("/if \(!Tools::getValue\('BANK_WIRE_DETAILS'\)\) \{.*?\}\n/s", '', $content, 1);
            break;
        case 'drop_address_validation':
            $content = preg_replace("/if \(!Tools::getValue\('BANK_WIRE_ADDRESS'\)\) \{.*?\}\n/s", '', $content, 1);
            break;
        case 'drop_reservation_validation':
            $content = preg_replace("/\\\$fieldReservationDays = Tools::getValue.*?\}\n\s*\}\n/s", '', $content, 1);
            break;
        case 'reservation_fallback_seven':
            $content = str_replace(
                '        $bankwireReservationDays = $this->reservation_days;',
                '        $bankwireReservationDays = 7;',
                $content
            );
            break;
        case 'flag_in_validation':
            $content = str_replace(
                "        if (Tools::isSubmit('btnSubmit')) {\n            if (!Tools::getValue('BANK_WIRE_DETAILS')) {",
                "        if (Tools::isSubmit('btnSubmit')) {\n            Configuration::updateValue(\n                self::FLAG_DISPLAY_PAYMENT_INVITE,\n                Tools::getValue(self::FLAG_DISPLAY_PAYMENT_INVITE)\n            );\n\n            if (!Tools::getValue('BANK_WIRE_DETAILS')) {",
                $content
            );
            break;
        case 'single_owner_error':
            $content = str_replace(
                '$this->_postValidation();',
                '$this->_postErrors[] = "owner";',
                $content
            );
            break;
        case 'config_owner_stored_only':
            $content = str_replace(
                "'BANK_WIRE_OWNER' => Tools::getValue('BANK_WIRE_OWNER', \$this->owner),",
                "'BANK_WIRE_OWNER' => \$this->owner,",
                $content
            );
            break;
        case 'custom_text_all_langs':
            $content = str_replace(
                'if (Tools::getIsset(\'BANK_WIRE_CUSTOM_TEXT_\' . $lang[\'id_lang\'])) {',
                'if (true) {',
                $content
            );
            break;
        case 'languages_active_only':
            $content = str_replace('return LanguageTestStore::$languages;', 'return Language::getLanguages(true);', $content);
            $content = str_replace('function getLanguages($active = true)', 'function getLanguages($active = true)', $content);
            $content = str_replace(
                "        return LanguageTestStore::\$languages;",
                "        return array(array('id_lang' => 1, 'iso_code' => 'en', 'active' => 1));",
                $content
            );
            break;
        case 'reservation_required':
            $content = str_replace(
                "'name' => 'BANK_WIRE_RESERVATION_DAYS',",
                "'name' => 'BANK_WIRE_RESERVATION_DAYS',\n                        'required' => true,",
                $content
            );
            break;
        case 'submit_action_typo':
            $content = str_replace("\$helper->submit_action = 'btnSubmit';", "\$helper->submit_action = 'submitWire';", $content);
            break;
        case 'check_currency_false':
            $content = preg_replace('/public function checkCurrency.*?\n    \}/s', "public function checkCurrency(\$cart)\n    {\n        return false;\n    }", $content);
            break;
        case 'check_currency_true':
            $content = preg_replace('/public function checkCurrency.*?\n    \}/s', "public function checkCurrency(\$cart)\n    {\n        return true;\n    }", $content);
            break;
        case 'drop_is_array_guard':
            $content = str_replace('if (is_array($currencies_module)) {', 'if (true) {', $content);
            break;
        case 'drop_hook_active_guard':
            $content = str_replace("if (!\$this->active) {\n            return [];\n        }\n\n", '', $content);
            break;
        case 'drop_hook_currency_guard':
            $content = str_replace("if (!\$this->checkCurrency(\$params['cart'])) {\n            return [];\n        }\n\n", '', $content);
            break;
        case 'validation_controller_in_action':
            $content = str_replace(
                "->setAction(\$this->context->link->getModuleLink(\$this->name, 'validation', [], true))",
                "->setAction(\$this->context->link->getModuleLink(\$this->name, 'payment', [], true))",
                $content
            );
            break;
        case 'total_tax_excl':
            $content = str_replace(
                '$cart->getOrderTotal(true, Cart::BOTH)',
                '$cart->getOrderTotal(false, Cart::BOTH)',
                $content
            );
            break;
        case 'drop_placeholders':
            $content = str_replace("\$bankwireOwner = '___________';", '$bankwireOwner = "";', $content);
            break;
        case 'template_raw_details':
            $content = str_replace('$bankwireDetails = Tools::nl2br($this->details);', '$bankwireDetails = $this->details;', $content);
            break;
        case 'drop_reservation_cast':
            $content = str_replace(
                "'bankwireReservationDays' => (int) \$bankwireReservationDays,",
                "'bankwireReservationDays' => \$bankwireReservationDays,",
                $content
            );
            break;
        case 'custom_text_lang_one':
            $content = str_replace(
                "Configuration::get('BANK_WIRE_CUSTOM_TEXT', \$this->context->language->id)",
                "Configuration::get('BANK_WIRE_CUSTOM_TEXT', 1)",
                $content
            );
            break;
        case 'drop_return_active_guard':
            $content = str_replace("if (!\$this->active || !Configuration::get(self::FLAG_DISPLAY_PAYMENT_INVITE)) {", 'if (!Configuration::get(self::FLAG_DISPLAY_PAYMENT_INVITE)) {', $content);
            break;
        case 'drop_return_flag_guard':
            $content = str_replace('!Configuration::get(self::FLAG_DISPLAY_PAYMENT_INVITE)', 'false', $content);
            break;
        case 'return_total_gross':
            $content = str_replace(
                "\$totalToPaid = \$params['order']->getOrdersTotalPaid() - \$params['order']->getTotalPaid();",
                "\$totalToPaid = \$params['order']->getOrdersTotalPaid();",
                $content
            );
            break;
        case 'return_status_pending':
            $content = str_replace("'status' => 'ok',", "'status' => 'pending',", $content);
            break;
        case 'register_old_hook':
            $content = str_replace("registerHook('displayPaymentReturn')", "registerHook('paymentReturn')", $content);
            break;
        case 'upgrade_always_true':
            $content = preg_replace('/return \(bool\) \$module->registerHook.*;/', 'return true;', $content);
            break;
        case 'payment_redirect_step1':
            $content = str_replace("Tools::redirect('index.php?controller=order');", "Tools::redirect('index.php?controller=order&step=1');", $content);
            break;
        case 'payment_template_typo':
            $content = str_replace("setTemplate('payment_execution.tpl');", "setTemplate('payment.tpl');", $content);
            break;
        case 'drop_customer_guard':
            $content = str_replace('$cart->id_customer == 0 || ', '', $content);
            break;
        case 'drop_delivery_guard':
            $content = str_replace('$cart->id_address_delivery == 0 || ', '', $content);
            break;
        case 'drop_invoice_guard':
            $content = str_replace('$cart->id_address_invoice == 0 || ', '', $content);
            break;
        case 'drop_module_active_guard':
            $content = str_replace('!$this->module->active', 'false', $content);
            break;
        case 'payment_name_cheque':
            $content = str_replace("== 'ps_wirepayment'", "== 'cheque'", $content);
            break;
        case 'drop_loaded_object_check':
            $content = preg_replace('/if \(!Validate::isLoadedObject\(\$customer\)\) \{.*?\}\n/s', '', $content, 1);
            break;
        case 'order_total_products_only':
            $content = str_replace('Cart::BOTH', 'Cart::ONLY_PRODUCTS', $content);
            break;
        case 'drop_key_query':
            $content = str_replace("'&key=' . \$customer->secure_key", "''", $content);
            break;
        case 'drop_reservation_token':
            $content = str_replace('bankwireReservationDays', 'bankwireReservationDaysRemoved', $content);
            break;
        case 'drop_reference_token':
            $content = str_replace('reference', 'referenceRemoved', $content);
            break;
        case 'drop_owner_token':
            $content = str_replace('bankwireOwner', 'bankwireOwnerRemoved', $content);
            break;
        case 'rename_infos_template':
            rename($path, dirname($path) . '/infos-old.tpl');
            return;
        case 'version_bump':
            $content = str_replace("=\n        '2.2.1';", "=\n        '2.2.2';", $content);
            $content = str_replace('$this->version = \'2.2.1\';', '$this->version = \'2.2.2\';', $content);
            break;
        default:
            throw new RuntimeException('Unknown mutation ' . $mutation);
    }
    if ($mutation !== 'rename_infos_template') {
        file_put_contents($path, $content);
    }
}

function export_head($dir)
{
    $repo = getenv('WIREPAYMENT_ROOT') ? getenv('WIREPAYMENT_ROOT') : dirname(__DIR__);
    $cmd = 'git -C ' . escapeshellarg($repo) . ' archive HEAD | tar -x -C ' . escapeshellarg($dir);
    exec($cmd, $output, $status);
    if ($status !== 0) {
        throw new RuntimeException('git archive failed: ' . implode("\n", $output));
    }
}

$failed = 0;
$passed = 0;
foreach ($cases as $case) {
    list($test, $rel, $mutation) = $case;
    $dir = $exportBase . '/' . $test;
    if (is_dir($dir)) {
        exec('rm -rf ' . escapeshellarg($dir));
    }
    mkdir($dir, 0700, true);
    export_head($dir);
    try {
        apply_mutation($rel, $mutation, $dir);
    } catch (Exception $e) {
        echo "PROOF FAIL {$test}: mutation error: {$e->getMessage()}\n";
        $failed++;
        continue;
    }
    $bootstrap = '/src/tests/bootstrap.php';
    $xml = $workDir . '/revert-' . $test . '.xml';
    $testsDir = '/src/tests';
    $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<phpunit bootstrap=\"" . htmlspecialchars($bootstrap, ENT_QUOTES) . "\" colors=\"false\"><testsuites><testsuite name=\"revert\"><directory>" . htmlspecialchars($testsDir, ENT_QUOTES) . "</directory></testsuite></testsuites></phpunit>\n";
    file_put_contents($xml, $body);
    $docker = getenv('DOCKER_BIN') ? getenv('DOCKER_BIN') : 'docker';
    $image = getenv('PHP_IMAGE') ? getenv('PHP_IMAGE') : 'php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143';
    $cmd = escapeshellarg($docker) . ' run --rm --network none '
        . '-v ' . escapeshellarg($dir) . ':/src:ro '
        . '-v ' . escapeshellarg($workDir) . ':/work:ro '
        . '-w /src '
        . escapeshellarg($image) . ' '
        . '/usr/local/bin/php -d error_reporting=-1 '
        . '/work/phpunit-4.8.36.phar '
        . '--configuration /work/' . basename($xml)
        . ' --filter ' . escapeshellarg($test) . ' 2>&1';
    $out = shell_exec($cmd);
    if (strpos($out, 'Failed asserting') !== false
        && strpos($out, 'OK (') === false
        && stripos($out, 'Fatal error') === false
        && stripos($out, 'Parse error') === false
        && stripos($out, 'No tests executed') === false) {
        echo "PROOF OK {$test}\n";
        $passed++;
    } else {
        echo "PROOF FAIL {$test}\n{$out}\n";
        $failed++;
    }
}

echo "Revert proofs passed {$passed}/" . count($cases) . "\n";
if ($failed > 0) {
    exit(1);
}
