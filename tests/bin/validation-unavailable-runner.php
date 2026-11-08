<?php

$root = dirname(dirname(__DIR__));
require_once $root . '/tests/bootstrap.php';

Module::setPaymentModules(array(array('name' => 'cheque')));
ConfigurationTestStore::reset();
CustomerTestStore::$customers[12] = array('secure_key' => 'sec-key-99');

$controller = new Ps_WirepaymentValidationModuleFrontController();
$controller->module = new Ps_WirepaymentValidateOrderSpy();
$controller->module->active = true;
$controller->module->id = 7;
$controller->module->displayName = 'Wire payment';

try {
    $controller->postProcess();
} catch (WirepaymentTestRedirect $e) {
    fwrite(STDERR, 'Unexpected redirect: ' . $e->url);
    exit(1);
}
