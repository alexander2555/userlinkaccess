<?php
/**
 * UserLinkAccessCreateHook
 *
 * Хук для создания ссылки и пользователя
 *
 * @package userlinkaccess
 *
 * @param int     $resourceId ID ресурса
 * @param int     $userId     ID пользователя
 * @param string  $url        Ссылка
 */

$resourceId = $scriptProperties['resourceId'] ?? null;
$userId = $scriptProperties['userId'] ?? null;
$url = $scriptProperties['url'] ?? '';

if (!$resourceId || !$userId) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[UserLinkAccessCreateHook] Не переданы обязательные параметры ресурса и пользователя!');
    return false;
}

$modx->log(modX::LOG_LEVEL_INFO, '[UserLinkAccessCreateHook] Ссылка создана для ресурса #' . $resourceId . ', пользователя #' . $userId, ', URL ', . $url);
return true;