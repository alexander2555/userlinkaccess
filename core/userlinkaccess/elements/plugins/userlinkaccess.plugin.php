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
        if (isset($_GET['userlinkaccess'])) {
            $hash = $_GET['userlinkaccess'];

            // Активируем ссылку и авторизуем пользователя
            $result = $userLinkAccess->activateLink($hash);

            // Если активация не удалась, перенаправляем на главную страницу
            if (!$result) {
              $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess Plugin] Не удалось активировать ссылку.');
              $modx->sendRedirect($modx->makeUrl($modx->getOption('site_start'), '', [], 'full'));
            }
        }
        break;

    case 'OnWebLogout':
        // При выходе пользователя деактивируем ссылку, если это временный пользователь
        // $userLinkAccess->deactivateLink();
        break;

    case 'OnCacheUpdate':
        // Очищаем просроченные ссылки и пользователей при обновлении кэша
        $userLinkAccess->cleanupExpiredLinks();
        break;
}