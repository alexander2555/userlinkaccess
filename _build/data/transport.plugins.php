<?php
/**
 * UserLinkAccess build script - plugins
 *
 * @package userlinkaccess
 */

use MODX\Revolution\modPlugin;
use MODX\Revolution\modPluginEvent;

$plugins = [];

$plugincode = getSnippetContent($sources['core'] . 'elements/plugins/userlinkaccess.plugin.php');

$plugin = $modx->newObject(modPlugin::class);
$plugin->fromArray([
    'name' => 'UserLinkAccessPlugin',
    'description' => 'Плагин для обработки ссылок временного доступа',
    'plugincode' => $plugincode,
    'category' => 0,
], '', true, true);

// Добавляем события, на которые подписан плагин
$events = [
    'OnHandleRequest',
    'OnWebLogout',
    'OnCacheUpdate'
];

$pluginEvents = [];
foreach ($events as $event) {
    $pluginEvent = $modx->newObject(modPluginEvent::class);
    $pluginEvent->fromArray([
        'event' => $event,
        'priority' => 0,
        'propertyset' => 0,
    ], '', true, true);
    $pluginEvents[] = $pluginEvent;
}

if (!empty($pluginEvents)) {
    $plugin->addMany($pluginEvents);
}
$plugins[] = $plugin;

return $plugins;
