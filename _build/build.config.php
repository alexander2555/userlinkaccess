<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_reporting', E_ALL);
  
// Определяем пакет
define('PKG_NAME', 'UserLinkAccess');
define('PKG_NAME_LOWER', strtolower(PKG_NAME));
define('PKG_VERSION', '0.0.1');
define('PKG_RELEASE', 'alpha');

// Определяем пути
if (isset($_SERVER['MODX_BASE_PATH'])) {
	define('MODX_BASE_PATH', $_SERVER['MODX_BASE_PATH']);
}
elseif (file_exists(dirname(__FILE__, 2) . '/core')) {
	define('MODX_BASE_PATH', dirname(__FILE__, 2) . '/');
}
elseif (file_exists(dirname(__FILE__, 3) . '/core')) {
	define('MODX_BASE_PATH', dirname(__FILE__, 3) . '/');
}
elseif (file_exists(dirname(__FILE__, 4) . '/core')) {
	define('MODX_BASE_PATH', dirname(__FILE__, 4) . '/');
}
else {
    die('Ошибка: Не удалось определить MODX_BASE_PATH');
}

if (!file_exists(MODX_BASE_PATH . 'config.core.php')) {
    die('Error: config.core.php not found in ' . MODX_BASE_PATH);
}
require_once MODX_BASE_PATH . 'config.core.php';
if (!defined('MODX_CONFIG_KEY')) {
    define('MODX_CONFIG_KEY', 'config');
}
if (!defined('MODX_CORE_PATH')) {
    define('MODX_CORE_PATH', MODX_BASE_PATH . 'core/');
}
define('MODX_MANAGER_PATH', MODX_BASE_PATH . 'manager/');
define('MODX_CONNECTORS_PATH', MODX_BASE_PATH . 'connectors/');
define('MODX_ASSETS_PATH', MODX_BASE_PATH . 'assets/');

define('MODX_BASE_URL','/modx/');
define('MODX_CORE_URL', MODX_BASE_URL . 'core/');
define('MODX_MANAGER_URL', MODX_BASE_URL . 'manager/');
define('MODX_CONNECTORS_URL', MODX_BASE_URL . 'connectors/');
define('MODX_ASSETS_URL', MODX_BASE_URL . 'assets/');
