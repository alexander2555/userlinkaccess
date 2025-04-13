<?php
/**
 * UserLinkAccess Resolver Script
 *
 * Резольвер для установки пакета UserLinkAccess
 *
 * @package userlinkaccess
 */

use MODX\Revolution\modSystemSetting;

if ($object->xpdo) {
    /** @var \MODX\Revolution\modX $modx */
    $modx =& $object->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            // Создаем таблицу
            $modelPath = $modx->getOption('core_path') . 'components/userlinkaccess/model/';
            $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, 'Загрузка пакета из ' . $modelPath);
            $modx->addPackage('userlinkaccess', MODX_CORE_PATH . 'components/userlinkaccess/model/', null, 'userlinkaccess\\');

            $manager = $modx->getManager();

            $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, 'Создание таблиц...');
            $manager->createObjectContainer('userlinkaccess\\UserLinkAccessLink');

            // Создаем директории, если их нет
            $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, 'Создание директорий ...');
            $coreDir = $modx->getOption('core_path') . 'components/userlinkaccess/';
            $assetsDir = $modx->getOption('assets_path') . 'components/userlinkaccess/';

            $directories = [
                $coreDir,
                $coreDir . 'model/',
                $coreDir . 'processors/',
                $coreDir . 'elements/',
                $coreDir . 'elements/snippets/',
                $coreDir . 'elements/plugins/',
                $coreDir . 'elements/chunks/',
                $coreDir . 'docs/',
                $assetsDir,
                $assetsDir . 'js/',
                $assetsDir . 'css/',
            ];

            foreach ($directories as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }

            // Создаем необходимые системные настройки
            $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, 'Создание системных настроек ...');
            $settings = [
                'check_csrf' => [
                    'value' => '1',
                    'type' => 'combo-boolean',
                    'area' => 'userlinkaccess',
                    'description' => 'Проверять CSRF-токен при AJAX-запросах'
                ],
                'csrf_name' => [
                    'value' => 'csrf-token',
                    'type' => 'textfield',
                    'area' => 'userlinkaccess',
                    'description' => 'Имя CSRF-токена'
                ],
                'default_lifetime' => [
                    'value' => '3600',
                    'type' => 'numberfield',
                    'area' => 'userlinkaccess',
                    'description' => 'Время жизни ссылки по умолчанию (в секундах)'
                ],
                'user_group' => [
                    'value' => 'Clients',
                    'type' => 'textfield',
                    'area' => 'userlinkaccess',
                    'description' => 'Группа пользователей для временного доступа'
                ],
                'context_access' => [
                    'value' => 'web',
                    'type' => 'textfield',
                    'area' => 'userlinkaccess',
                    'description' => 'Контекст доступа'
                ],
                'create_link_hook' => [
                    'value' => 'UserLinkAccessCreateHook',
                    'type' => 'textfield',
                    'area' => 'userlinkaccess',
                    'description' => 'Хук после создания ссылки и пользователя'
                ],
                'delete_link_hook' => [
                    'value' => 'UserLinkAccessDeleteHook',
                    'type' => 'textfield',
                    'area' => 'userlinkaccess',
                    'description' => 'Хук после удаления ссылки и пользователя'
                ]
            ];

            foreach ($settings as $key => $setting) {
                $settingObject = $modx->getObject(modSystemSetting::class, [
                    'key' => 'userlinkaccess_' . $key
                ]);

                if (!$settingObject) {
                    $settingObject = $modx->newObject(modSystemSetting::class);
                    $settingObject->set('key', 'userlinkaccess_' . $key);
                    $settingObject->set('value', $setting['value']);
                    $settingObject->set('xtype', $setting['type']);
                    $settingObject->set('namespace', 'userlinkaccess');
                    $settingObject->set('area', $setting['area']);
                    $settingObject->set('description', $setting['description']);
                    $settingObject->save();
                }
            }

            break;

        case xPDOTransport::ACTION_UNINSTALL:
            // При удалении пакета ничего не делаем с таблицами и настройками
            break;
    }
}

return true;
