<?php
/**
 * UserLinkAccess Build Script
 *
 * @package userlinkaccess
 */
 
use MODX\Revolution\Transport\modPackageBuilder;
use MODX\Revolution\modCategory;

$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
set_time_limit(0);

require_once 'build.config.php';
// echo PKG_NAME_LOWER;

// Определяем пути
$root = MODX_BASE_PATH;
$sources = array(
  'root' => MODX_BASE_PATH,
  'build' => MODX_BASE_PATH . '_build/',
  'data' => MODX_BASE_PATH . '_build/data/',
  'resolvers' => MODX_BASE_PATH . '_build/resolvers/',
  'core' => MODX_CORE_PATH . 'components/' . PKG_NAME_LOWER . '/',
  'assets' => MODX_BASE_PATH . 'assets/components/' . PKG_NAME_LOWER . '/',
  'docs' => MODX_CORE_PATH . 'components/' . PKG_NAME_LOWER . '/docs/',
	'model' => MODX_CORE_PATH . 'components/' . PKG_NAME_LOWER . '/model/',
	'schema' => MODX_CORE_PATH . 'components/' . PKG_NAME_LOWER . '/model/schema/',
	'xml' => MODX_CORE_PATH . 'components/' . PKG_NAME_LOWER . '/model/schema/'.PKG_NAME_LOWER.'.mysql.schema.xml',
);

require_once MODX_CORE_PATH . 'vendor/autoload.php';
require_once $sources['build'] . '/includes/functions.php';

$modx = new \MODX\Revolution\modX();
$modx->initialize('mgr');
$modx->setLogLevel(\MODX\Revolution\modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

// Обновляем модель
$modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, 'Строим модель ...');

$manager = $modx->getManager();
$generator = $manager->getGenerator();

// Удаляем старую модель, если присутствует
$mysql_dir = $sources['model'] . PKG_NAME_LOWER;
if (file_exists($mysql_dir)) {
  rrmdir($mysql_dir);
}

// Генерируем новую модель
$generator->parseSchema(
  $sources['xml'],
  $sources['model']
);
$modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, 'Модель построена.');

// $modx->loadClass('transport.modPackageBuilder', '', false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage(PKG_NAME_LOWER, PKG_VERSION, PKG_RELEASE);
$builder->registerNamespace(PKG_NAME_LOWER, false, true, '{core_path}components/' . PKG_NAME_LOWER . '/');

// Создаем категорию
$category = $modx->newObject(modCategory::class);
$category->set('id', 1);
$category->set('category', PKG_NAME);

// Добавляем сниппеты
$snippets = include $sources['data'] . 'transport.snippets.php';
if (is_array($snippets)) {
    $category->addMany($snippets);
    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, 'Добавлено сниппетов: ' . count($snippets));
}

// Добавляем плагины
$plugins = include $sources['data'] . 'transport.plugins.php';
if (is_array($plugins)) {
    $category->addMany($plugins);
    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, 'Добавлено плагинов: ' . count($plugins));
}

// Добавляем чанки
$chunks = include $sources['data'] . 'transport.chunks.php';
if (is_array($chunks)) {
    $category->addMany($chunks);
    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, 'Добавлено чанков: ' . count($chunks));
}

// Создаем транспортный пакет
use xPDOTransport;

$vehicle = $builder->createVehicle($category, [
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::UNIQUE_KEY => 'category',
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
        'Snippets' => [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
        ],
        'Plugins' => [
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                'PluginEvents' => [
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => ['pluginid', 'event'],
                ],
            ],
        ],
        'Chunks' => [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
        ],
    ],
]);

// Добавляем файловые резольверы
$vehicle->resolve('file', [
    'source' => $sources['core'],
    'target' => "return MODX_CORE_PATH . 'components/';",
]);

$vehicle->resolve('file', [
    'source' => $sources['assets'],
    'target' => "return MODX_ASSETS_PATH . 'components/';",
]);

// Добавляем резольвер для создания таблиц
$vehicle->resolve('php', [
    'source' => $sources['resolvers'] . 'userlinkaccess.resolver.php',
]);

$builder->putVehicle($vehicle);

// Упаковываем пакет
$builder->setPackageAttributes(array(
    'license' => file_get_contents($sources['docs'] . 'license.txt'),
    'readme' => file_get_contents($sources['docs'] . 'readme.txt'),
    'changelog' => file_get_contents($sources['docs'] . 'changelog.txt'),
));

$modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, 'Упаковка транспортного пакета...');
$builder->pack();

$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tend = $mtime;
$totalTime = ($tend - $tstart);
$totalTime = sprintf("%2.4f s", $totalTime);

$modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, "Пакет построен за: {$totalTime}");