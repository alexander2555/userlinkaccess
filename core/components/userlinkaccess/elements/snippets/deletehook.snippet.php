<?php
/**
 * UserLinkAccessDeleteHook
 *
 * Хук для удаления ссылки и пользователя
 *
 * @package userlinkaccess
 *
 * @param int $resourceId ID ресурса
 * @param int $userId     ID пользователя
 */

$resourceId = $scriptProperties['resourceId'] ?? null;
$userId = $scriptProperties['userId'] ?? null;

if (!$resourceId || !$userId) {
    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccessCreateHook] Не переданы обязательные параметры ресурса и пользователя!');
    return false;
}

$modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '[UserLinkAccessDeleteHook] Ссылка удалена для ресурса #' . $resourceId . ', пользователя #' . $userId);
return true;