<?php

require_once dirname(__FILE__) . '/PaymentOptionDouble.php';

class WirepaymentTestRedirect extends Exception
{
    public $url;

    public function __construct($url)
    {
        $this->url = $url;
        parent::__construct('Redirect: ' . $url);
    }
}

class WirepaymentTestState
{
    public static function reset()
    {
        $_GET = array();
        $_POST = array();
        $_REQUEST = array();
        $_COOKIE = array();
        ConfigurationTestStore::reset();
        ContextTestStore::reset();
        ModuleTestRuntime::reset();
        CurrencyTestStore::reset();
        LanguageTestStore::reset();
        CustomerTestStore::reset();
        HelperFormTestRuntime::reset();
        OrderTestRuntime::reset();
        PaymentModuleTestRuntime::reset();
        ModuleFrontControllerTestRuntime::reset();
    }
}

class ConfigurationTestStore
{
    public static $data = array();
    public static $deleteFailKeys = array();

    public static function reset()
    {
        self::$deleteFailKeys = array();
        self::$data = array(
            'PS_LANG_DEFAULT' => '1',
            'PS_BO_ALLOW_EMPLOYEE_FORM_LANG' => '',
            'PS_SSL_ENABLED' => '1',
            'PS_OS_BANKWIRE' => '10',
            'PS_SHOP_EMAIL' => 'shop@test.example',
        );
    }

    public static function deleteAll()
    {
        self::$data = array();
    }
}

class ContextTestStore
{
    /** @var WirepaymentContext */
    public static $instance;

    public static function reset()
    {
        self::$instance = new WirepaymentContext();
    }

    public static function get()
    {
        if (self::$instance === null) {
            self::reset();
        }

        return self::$instance;
    }
}

class ModuleTestRuntime
{
    public static $constructActive = null;
    public static $parentInstallResult = true;
    public static $parentUninstallResult = true;
    public static $hookLog = array();
    public static $registerHookResults = array();
    public static $unregisterHookResults = array();
    public static $checkPaymentCurrenciesResult = array(array('id_currency' => '1'));
    public static $paymentCurrencies = array(
        array('id_currency' => '1', 'iso_code' => 'EUR'),
        array('id_currency' => '2', 'iso_code' => 'USD'),
    );

    public static function reset()
    {
        self::$constructActive = null;
        Module::setPaymentModules(array(array('name' => 'ps_wirepayment')));
        self::$parentInstallResult = true;
        self::$parentUninstallResult = true;
        self::$hookLog = array();
        self::$registerHookResults = array();
        self::$unregisterHookResults = array();
        self::$checkPaymentCurrenciesResult = array(array('id_currency' => '1'));
        self::$paymentCurrencies = array(
            array('id_currency' => '1', 'iso_code' => 'EUR'),
            array('id_currency' => '2', 'iso_code' => 'USD'),
        );
    }
}

class CurrencyTestStore
{
    public static $rows = array(
        '2' => array('id' => '2', 'iso_code' => 'USD'),
    );

    public static function reset()
    {
        self::$rows = array(
            '2' => array('id' => '2', 'iso_code' => 'USD'),
        );
    }
}

class LanguageTestStore
{
    public static $languages = array(
        array('id_lang' => 1, 'iso_code' => 'en', 'active' => 1),
        array('id_lang' => 2, 'iso_code' => 'fr', 'active' => 0),
    );

    public static function reset()
    {
        self::$languages = array(
            array('id_lang' => 1, 'iso_code' => 'en', 'active' => 1),
            array('id_lang' => 2, 'iso_code' => 'fr', 'active' => 0),
        );
    }
}

class CustomerTestStore
{
    public static $customers = array();

    public static function reset()
    {
        self::$customers = array();
    }
}

class HelperFormTestRuntime
{
    public static $lastInstance;
    public static $lastForms;

    public static function reset()
    {
        self::$lastInstance = null;
        self::$lastForms = null;
    }
}

class OrderTestRuntime
{
    public static function reset()
    {
    }
}

class PaymentModuleTestRuntime
{
    public static $validateOrderCalls = array();
    public static $validateOrderEnabled = true;

    public static function reset()
    {
        self::$validateOrderCalls = array();
        self::$validateOrderEnabled = true;
    }
}

class ModuleFrontControllerTestRuntime
{
    public static $template;
    public static $smartyAssign = array();
    public static $initContentCalled = false;

    public static function reset()
    {
        self::$template = null;
        self::$smartyAssign = array();
        self::$initContentCalled = false;
    }
}

class Shop
{
    public static function isFeatureActive()
    {
        return false;
    }

    public static function getContextShopID($null = true)
    {
        return 1;
    }

    public static function getContextShopGroupID($null = true)
    {
        return 1;
    }
}

class Validate
{
    public static function isUnsignedInt($value)
    {
        return (string) (int) $value === (string) $value && $value < 4294967296 && $value >= 0;
    }

    public static function isLoadedObject($object)
    {
        return is_object($object) && !empty($object->id);
    }

    public static function isConfigName($config_name)
    {
        return (bool) preg_match('/^[a-zA-Z_0-9-]+$/', $config_name);
    }
}

class Tools
{
    public static function redirect($url)
    {
        throw new WirepaymentTestRedirect($url);
    }

    public static function isSubmit($key)
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    public static function getValue($key, $default = false)
    {
        if (array_key_exists($key, $_POST)) {
            return $_POST[$key];
        }
        if (array_key_exists($key, $_GET)) {
            return $_GET[$key];
        }

        return $default;
    }

    public static function getIsset($key)
    {
        return array_key_exists($key, $_POST) || array_key_exists($key, $_GET);
    }

    public static function nl2br($string)
    {
        return str_replace(array("\r\n", "\r", "\n"), '<br />', $string);
    }

    public static function getAdminTokenLite($tab)
    {
        return 'lite-token-' . $tab;
    }

    public static function getShopDomainSsl($protocol = false, $forceWithoutWww = false)
    {
        $domain = 'shop.example';
        if ($forceWithoutWww) {
            $domain = htmlspecialchars($domain);
        }
        if ($protocol && Configuration::get('PS_SSL_ENABLED')) {
            return 'https://' . $domain;
        }

        return $domain;
    }
}

class Configuration
{
    public static function get($key, $idLang = null, $idShopGroup = null, $idShop = null, $default = false)
    {
        if ($idLang !== null && $idLang !== 0) {
            if (isset(ConfigurationTestStore::$data[$key]) && is_array(ConfigurationTestStore::$data[$key])) {
                if (isset(ConfigurationTestStore::$data[$key][$idLang])) {
                    return ConfigurationTestStore::$data[$key][$idLang];
                }

                return $default;
            }
        }
        if (isset(ConfigurationTestStore::$data[$key])) {
            return ConfigurationTestStore::$data[$key];
        }

        return $default;
    }

    public static function getMultiple($keys, $idLang = null, $idShopGroup = null, $idShop = null)
    {
        $results = array();
        foreach ($keys as $key) {
            $results[$key] = self::get($key, $idLang, $idShopGroup, $idShop);
        }

        return $results;
    }

    public static function updateValue($key, $values, $html = false, $idShopGroup = null, $idShop = null)
    {
        if (is_array($values)) {
            if (!isset(ConfigurationTestStore::$data[$key]) || !is_array(ConfigurationTestStore::$data[$key])) {
                ConfigurationTestStore::$data[$key] = array();
            }
            foreach ($values as $lang => $value) {
                ConfigurationTestStore::$data[$key][$lang] = self::normalizeValue($value);
            }
        } else {
            ConfigurationTestStore::$data[$key] = self::normalizeValue($values);
        }

        return true;
    }

    public static function deleteByName($key)
    {
        if (!Validate::isConfigName($key)) {
            return false;
        }
        if (in_array($key, ConfigurationTestStore::$deleteFailKeys, true)) {
            return false;
        }
        if (isset(ConfigurationTestStore::$data[$key])) {
            unset(ConfigurationTestStore::$data[$key]);

            return true;
        }

        return true;
    }

    private static function normalizeValue($value)
    {
        if ($value === true) {
            return '1';
        }
        if ($value === false || $value === null) {
            return '';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $value;
    }
}

class Language
{
    public $id;

    public function __construct($id)
    {
        $this->id = (int) $id;
    }

    public static function getLanguages($active = true)
    {
        if ($active) {
            $out = array();
            foreach (LanguageTestStore::$languages as $lang) {
                if (!empty($lang['active'])) {
                    $out[] = $lang;
                }
            }

            return $out;
        }

        return LanguageTestStore::$languages;
    }
}

class Currency
{
    public $id;
    public $iso_code;

    public function __construct($id)
    {
        $this->id = (int) $id;
        if (isset(CurrencyTestStore::$rows[(string) $id])) {
            $row = CurrencyTestStore::$rows[(string) $id];
            $this->iso_code = $row['iso_code'];
        } else {
            $this->iso_code = 'EUR';
        }
    }

    public static function checkPaymentCurrencies($idModule, $idShop = null)
    {
        if (empty($idModule)) {
            return array();
        }

        return ModuleTestRuntime::$checkPaymentCurrenciesResult;
    }

    public static function getPaymentCurrencies($idModule, $idShop = null)
    {
        return ModuleTestRuntime::$paymentCurrencies;
    }
}

class Cart
{
    const BOTH = 3;
    const ONLY_PRODUCTS = 1;
    const ONLY_DISCOUNTS = 2;
    const BOTH_WITHOUT_SHIPPING = 4;

    public $id = 44;
    public $id_customer = 12;
    public $id_address_delivery = 5;
    public $id_address_invoice = 5;
    public $id_currency = 3;

    public function getOrderTotal($withTaxes = true, $type = Cart::BOTH)
    {
        if ($withTaxes && $type === Cart::BOTH) {
            if ($this->id === 44) {
                return '19.90';
            }

            return '100.25';
        }
        if (!$withTaxes && $type === Cart::BOTH) {
            return '83.00';
        }
        if ($withTaxes && $type === Cart::ONLY_PRODUCTS) {
            return '50.00';
        }

        return '0.00';
    }
}

class Customer
{
    public $id;
    public $secure_key = 'sec-key-99';

    public function __construct($id)
    {
        $this->id = $id;
        if (isset(CustomerTestStore::$customers[(int) $id])) {
            $this->secure_key = CustomerTestStore::$customers[(int) $id]['secure_key'];
            if (isset(CustomerTestStore::$customers[(int) $id]['empty'])) {
                $this->id = null;
            }
        } elseif ((int) $id === 0) {
            $this->id = null;
        }
    }
}

class WirepaymentLocale
{
    public function formatPrice($price, $iso)
    {
        return number_format((float) $price, 2, '.', '') . ' ' . $iso;
    }
}

class WirepaymentLink
{
    public function getModuleLink($module, $controller, $params = array(), $ssl = false)
    {
        $suffix = $ssl ? 'ssl' : 'plain';

        return 'modulelink:' . $module . '/' . $controller . '/' . $suffix;
    }

    public function getPageLink($page, $ssl = false, $idLang = null, $request = null)
    {
        $suffix = $ssl ? 'ssl' : 'plain';
        if ($request !== null && $request !== '') {
            $req = '/' . $request;
        } else {
            $req = '/';
        }

        return 'pagelink:' . $page . '/' . $suffix . $req;
    }

    public function getAdminLink($controller, $withToken = true)
    {
        if ($withToken) {
            return 'adminlink:' . $controller . '/token';
        }

        return 'adminlink:' . $controller . '/notoken';
    }
}

class WirepaymentShop
{
    public $name = 'Test Shop';

    public function getBaseURL()
    {
        return 'https://shop.example/shop/';
    }
}

class WirepaymentSmarty
{
    public $tpl_vars = array();

    public function assign($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->tpl_vars[$k] = $v;
            }
        } else {
            $this->tpl_vars[$key] = $value;
        }
    }

    public function getTemplateVars($name)
    {
        if (isset($this->tpl_vars[$name])) {
            return $this->tpl_vars[$name];
        }

        return null;
    }
}

class WirepaymentAdminController
{
    public function getLanguages()
    {
        return Language::getLanguages(false);
    }
}

class WirepaymentContext
{
    public $cart;
    public $currency;
    public $shop;
    public $link;
    public $language;
    public $smarty;
    public $controller;

    public function __construct()
    {
        $this->cart = new Cart();
        $this->cart->id = 99;
        $this->currency = new Currency(3);
        $this->currency->iso_code = 'EUR';
        $this->shop = new WirepaymentShop();
        $this->link = new WirepaymentLink();
        $this->language = new Language(1);
        $this->smarty = new WirepaymentSmarty();
        $this->controller = new WirepaymentAdminController();
    }

    public function getCurrentLocale()
    {
        return new WirepaymentLocale();
    }
}

class Context
{
    public static function getContext()
    {
        return ContextTestStore::get();
    }
}

class HelperForm
{
    public $show_toolbar;
    public $table;
    public $default_form_language;
    public $allow_employee_form_lang;
    public $id;
    public $identifier;
    public $submit_action;
    public $currentIndex;
    public $token;
    public $tpl_vars;

    public function generateForm($forms)
    {
        HelperFormTestRuntime::$lastInstance = $this;
        HelperFormTestRuntime::$lastForms = $forms;

        return 'FORM';
    }
}

class WirepaymentOrder
{
    public $reference = 'XKBKNABJK';
    public $id_currency = 2;

    public function getOrdersTotalPaid()
    {
        return '100.25';
    }

    public function getTotalPaid()
    {
        return '25.00';
    }
}

class Module
{
    public $id = 7;
    public $name;
    public $tab;
    public $version;
    public $author;
    public $controllers;
    public $active = true;
    public $displayName;
    public $description;
    public $confirmUninstall;
    public $warning;
    public $bootstrap;
    public $ps_versions_compliancy;
    public $currencies;
    public $currencies_mode;
    public $is_eu_compatible;
    public $table = 'module';
    public $identifier = 'id_module';
    public $context;
    public $smarty;

    protected static $paymentModules = array(array('name' => 'ps_wirepayment'));

    public function __construct()
    {
        if (ModuleTestRuntime::$constructActive !== null) {
            $this->active = ModuleTestRuntime::$constructActive;
        }
        $this->context = Context::getContext();
        $this->smarty = $this->context->smarty;
    }

    public static function getPaymentModules()
    {
        return self::$paymentModules;
    }

    public static function setPaymentModules($modules)
    {
        self::$paymentModules = $modules;
    }

    public function registerHook($name)
    {
        ModuleTestRuntime::$hookLog[] = 'register:' . $name;
        if (array_key_exists($name, ModuleTestRuntime::$registerHookResults)) {
            return ModuleTestRuntime::$registerHookResults[$name];
        }

        return true;
    }

    public function unregisterHook($name)
    {
        ModuleTestRuntime::$hookLog[] = 'unregister:' . $name;
        if (array_key_exists($name, ModuleTestRuntime::$unregisterHookResults)) {
            return ModuleTestRuntime::$unregisterHookResults[$name];
        }

        return true;
    }

    public function install()
    {
        return ModuleTestRuntime::$parentInstallResult;
    }

    public function uninstall()
    {
        return ModuleTestRuntime::$parentUninstallResult;
    }

    public function trans($id, $parameters = array(), $domain = null)
    {
        if (!empty($parameters) && is_array($parameters)) {
            return strtr($id, $parameters);
        }

        return $id;
    }

    public function display($file, $template)
    {
        return 'DISPLAY:' . $template;
    }

    public $fetchCalled = false;

    public function fetch($path)
    {
        $this->fetchCalled = true;

        return 'FETCH:' . $path;
    }

    public function displayConfirmation($message)
    {
        return 'CONFIRM:' . $message;
    }

    public function displayError($message)
    {
        return 'ERROR:' . $message;
    }

    public function getPathUri()
    {
        return __PS_BASE_URI__ . 'modules/' . $this->name . '/';
    }
}

class PaymentModule extends Module
{
    public $currentOrder;

    public function getCurrency($current_id_currency = null)
    {
        if (!(int) $current_id_currency) {
            $current_id_currency = Context::getContext()->currency->id;
        }

        if (!$this->currencies) {
            return false;
        }

        if ($this->currencies_mode == 'checkbox') {
            return Currency::getPaymentCurrencies($this->id);
        }

        return false;
    }

    public function validateOrder(
        $id_cart,
        $id_order_state,
        $amount,
        $payment_method,
        $message,
        $extra_vars,
        $currency_special,
        $dont_touch_amount,
        $secure_key
    ) {
        PaymentModuleTestRuntime::$validateOrderCalls[] = func_get_args();
        $this->currentOrder = 501;
    }

    public function getTranslator()
    {
        return $this;
    }
}

class ModuleFrontController
{
    public $context;
    /** @var Ps_Wirepayment */
    public $module;
    public $ssl = false;

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    public function initContent()
    {
        ModuleFrontControllerTestRuntime::$initContentCalled = true;
    }

    public function setTemplate($template)
    {
        ModuleFrontControllerTestRuntime::$template = $template;
    }

    public function getTranslator()
    {
        return $this->module;
    }
}

function wirepayment_new_module($active = true, $id = 7)
{
    ModuleTestRuntime::$constructActive = $active;
    $module = new Ps_Wirepayment();
    ModuleTestRuntime::$constructActive = null;
    $module->active = $active;
    $module->id = $id;

    return $module;
}

function wirepayment_assert_no_php_diagnostics($stdout, $stderr)
{
    $pattern = '/(Notice|Warning|Deprecated|Strict Standards|Parse error|Fatal error|Catchable fatal error|Recoverable fatal error)/i';
    if (preg_match($pattern, $stdout) || preg_match($pattern, $stderr)) {
        PHPUnit_Framework_TestCase::fail('PHP diagnostics in child output: ' . $stdout . $stderr);
    }
}
