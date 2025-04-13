<?php
/**
 * UserLinkAccess Connector
 *
 * @package userlinkaccess
 */

if (isset($_SERVER['MODX_BASE_PATH'])) {
	define('MODX_BASE_PATH', $_SERVER['MODX_BASE_PATH']);
}
elseif (file_exists(dirname(__FILE__, 2) . '/config.core.php')) {
	define('MODX_BASE_PATH', dirname(__FILE__, 2) . '/');
}
elseif (file_exists(dirname(__FILE__, 3) . '/config.core.php')) {
	define('MODX_BASE_PATH', dirname(__FILE__, 3) . '/');
}
elseif (file_exists(dirname(__FILE__, 4) . '/config.core.php')) {
	define('MODX_BASE_PATH', dirname(__FILE__, 4) . '/');
}
else {
  $modx->log(modX::LOG_LEVEL_ERROR, '[UserLinkAccess connector] Unable to locate core path!');
}
require_once MODX_BASE_PATH . 'config.core.php';
require_once MODX_CORE_PATH . 'vendor/autoload.php';
// require_once MODX_BASE_PATH . 'index.php';

// require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
if (!defined('MODX_BASE_URL')) {
    $modx_base_url= '/';
    define('MODX_BASE_URL', $modx_base_url);
}

define('MODX_API_MODE', true);

$modx = new \MODX\Revolution\modX();
$modx->initialize('web');

// Получение параметров запроса
$modx->getRequest();
$request = $modx->request;
$requestData = $request->parameters['POST'];
// if (empty($requestData)) return false;

$action = $requestData['action'] ?? '';
$csrfToken = $requestData['csrf'] ?? '';

// Проверка CSRF токена
$csrfhelper_path = $modx->getOption('csrfhelper.core_path', null, MODX_CORE_PATH . 'components/csrfhelper/') .'vendor/autoload.php';

if (file_exists($csrfhelper_path)) {
  
  require_once $csrfhelper_path;

  $key = 'user';
  $storage = new \modmore\CSRFHelper\Storage\SessionStorage();
  $csrf = new \modmore\CSRFHelper\Csrf($storage, $modx->getUser());
  
  try {
      $csrf->check($key, $csrfToken);
  } catch (\modmore\CSRFHelper\InvalidTokenException $e) {
      $modx->lexicon->load('csrfhelper:default');
      $error = $modx->lexicon('csrfhelper.error');
      $modx->log(modX::LOG_LEVEL_ERROR, '[csrfhelper] Received an invalid CSRF token. ' . $e->getMessage());
      echo json_encode(['success' => false, 'message' => $error]);
      exit;
  }
} else { // Если CSRFHelper не установлен
    $modx->log(modX::LOG_LEVEL_ERROR, '[UserLinkAccess Connector] CSRFHelper не установлен. Попытка проверить токен через сессию...');
    
    if (!isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
        $modx->log(modX::LOG_LEVEL_ERROR, '[UserLinkAccess Connector] Недействительный CSRF-токен');
        echo json_encode(['success' => false, 'message' => 'Недействительный CSRF-токен']);
        exit;
    }
    // Обновляем токен для следующего запроса
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Устанавливаем путь к процессорам
$processorsPath = MODX_CORE_PATH . 'components/userlinkaccess/processors/';
$processorFile = $processorsPath . 'generate.class.php';
if (!file_exists($processorFile)) {
    $response = ['success' => false, 'message' => 'Процессор не найден'];
}

// Обрабатываем запрос
$requestData = array_map('trim', $requestData);
switch ($action) {
  case 'userlinkaccess/generate':
    // Регистрируем путь к процессорам
    $modx->addPackage('userlinkaccess', $modx->getOption('core_path') . 'components/userlinkaccess/model/');
    try {
        $response = $modx->runProcessor('generate', $requestData, ['processors_path' => $processorsPath]);
        if ($response->isError()) {
            $modx->log(modX::LOG_LEVEL_ERROR, '[UserLinkAccess Connector] Ошибка процессора: ' . $response->getMessage());
            echo json_encode(['success' => false, 'message' => $response->getMessage()]);
            exit;
        }
    } catch (Exception $e) {
        $modx->log(modX::LOG_LEVEL_ERROR, '[UserLinkAccess Connector] Исключение: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Внутренняя ошибка при запуске процессора: ' . $e->getMessage()]);
        exit;
    }

    break;

  default:
    $response = ['success' => false, 'message' => 'Неизвестное действие: ' . $action];
    break;
}

// Если получили объект ответа
if (is_object($response)) {
    $response = $response->getResponse();
}

// Выводим ответ в формате JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;