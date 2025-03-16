<?php
/**
 * UserLinkAccessGenerate
 *
 * Сниппет для генерации временной ссылки доступа
 *
 * @package userlinkaccess
 *
 * @param int $resourceId ID ресурса для доступа (необязательный, по умолчанию - текущий ресурс)
 * @param int $lifetime Время жизни ссылки в секундах (необязательный, по умолчанию 3600 - 1 час)
 * @return string Сгенерированная ссылка или сообщение об ошибке
 */

// Загружаем класс UserLinkAccess
$corePath = $modx->getOption('core_path') . 'components/userlinkaccess/';
if (file_exists($corePath . 'model/userlinkaccess.class.php')) {
    require_once $corePath . 'model/userlinkaccess.class.php';
} else {
    return 'Не удалось загрузить класс UserLinkAccess';
}

// Проверяем, авторизован ли пользователь
if (!$modx->user->isAuthenticated()) {
    return 'Для генерации ссылки необходимо быть авторизованным.';
}

// Получаем ID ресурса
$resourceId = (int)$modx->getOption('resourceId', $scriptProperties, $modx->resource->get('id'));
// if (!$resourceId) {
//     return 'Не указан ID ресурса для доступа.';
// }

// Проверяем существование ресурса
$resource = $modx->getObject('MODX\Revolution\modResource', $resourceId);
if (!$resource) {
    return 'Указанный ресурс не существует.';
}

// Получаем время жизни ссылки
$lifetime = (int)$modx->getOption('lifetime', $scriptProperties, 3600);
if ($lifetime <= 0) {
    $lifetime = 3600;
}

// Создаем экземпляр класса UserLinkAccess
$userLinkAccess = new UserLinkAccess($modx);

// Генерируем ссылку
$link = $userLinkAccess->generateLink($resourceId, $lifetime);

if (!$link) {
    return 'Не удалось сгенерировать ссылку.';
}

return $link;