<?php
/**
 * UserLinkAccess Plugin
 *
 * Плагин для обработки временных ссылок и авторизации
 *
 * @package userlinkaccess
 */

// Загружаем класс UserLinkAccess
$corePath = $modx->getOption('core_path') . 'components/userlinkaccess/';
if (file_exists($corePath . 'model/userlinkaccess.class.php')) {
    require_once $corePath . 'model/userlinkaccess.class.php';
} else {
    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess Plugin] Не удалось загрузить класс UserLinkAccess');
    return;
}

$userLinkAccess = new \UserLinkAccess($modx);

switch ($modx->event->name) {
    case 'OnHandleRequest':
        // Проверяем, есть ли параметр userlinkaccess в запросе
        // $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '[UserLinkAccess Plugin] Запрос: ' . print_r($_GET, true));
        if (isset($_GET['userlinkaccess'])) {
            $hash = $_GET['userlinkaccess'];

            // Информация о запросе
            $requestInfo = [
                'IP' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'User-Agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                // 'Referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
                // 'Request Method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                // 'Request Time' => $_SERVER['REQUEST_TIME'] ?? time(),
                // 'Protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'unknown'
            ];
            $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '[UserLinkAccess Plugin] Запрос входа по ссылке: ' . print_r($requestInfo, true));

            // Активируем ссылку и авторизуем пользователя
            $result = $userLinkAccess->activateLink($hash, $requestInfo);

            // Если активация не удалась, перенаправляем на главную страницу
            if (!$result) {
                $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess Plugin] Не удалось активировать ссылку.  Перенаправлен на страницу 401');
                $modx->sendForward($modx->getOption('unauthorized_page'), 'HTTP/1.1 403 Forbidden');
            }
        }
        break;

    case 'OnWebLogout':
        // При выходе пользователя "освобождаем" ссылку, если это временный пользователь
        $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '[UserLinkAccess Plugin] Попытка освобождение ссылки при выходе пользователя...');
        
        $result = $userLinkAccess->releaseLink($modx->user);
        
        if ($result['success']) $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '[UserLinkAccess Plugin] ... ОК. ' . $result['message']);
        else $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess Plugin] ... ошибка: ' . $result['message']);
        break;

    case 'OnCacheUpdate':
        // Очищаем просроченные ссылки и пользователей при обновлении кэша
        $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '[UserLinkAccess Plugin] Проверка устаревших ссылок...');
        $userLinkAccess->cleanupExpiredLinks();
        break;
}