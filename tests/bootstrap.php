<?php

if (!defined('WIREPAYMENT_TEST_ROOT')) {
    define('WIREPAYMENT_TEST_ROOT', dirname(__DIR__));
}

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.6.0');
}
if (!defined('__PS_BASE_URI__')) {
    define('__PS_BASE_URI__', '/shop/');
}
if (!defined('_PS_MODULE_DIR_')) {
    define('_PS_MODULE_DIR_', WIREPAYMENT_TEST_ROOT . '/');
}
if (!defined('_PS_JS_DIR_')) {
    define('_PS_JS_DIR_', '/js/');
}
if (!defined('_PS_CORE_DIR_')) {
    define('_PS_CORE_DIR_', '/prestashop/');
}
if (!defined('_PS_THEME_CSS_DIR_')) {
    define('_PS_THEME_CSS_DIR_', '/themes/classic/assets/css/');
}
if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', '/admin/');
}
if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}

require_once dirname(__FILE__) . '/doubles.php';
require_once WIREPAYMENT_TEST_ROOT . '/ps_wirepayment.php';
require_once WIREPAYMENT_TEST_ROOT . '/controllers/front/payment.php';
require_once WIREPAYMENT_TEST_ROOT . '/controllers/front/validation.php';
require_once dirname(__FILE__) . '/Ps_WirepaymentValidateOrderSpy.php';

function wirepayment_reset_all_state()
{
    WirepaymentTestState::reset();
}
