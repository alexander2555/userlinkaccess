<?php
/**
 * UserLinkAccess build script - snippets
 *
 * @package userlinkaccess
 */

use MODX\Revolution\modSnippet;

$snippets = [];

// Основной сниппет
$snippetcode = getSnippetContent($sources['core'] . 'elements/snippets/userlinkaccess.snippet.php');

$snippet = $modx->newObject(modSnippet::class);
$snippet->fromArray([
    'name' => 'UserLinkAccessSnippet',
    'description' => 'Сниппет для генерации временной ссылки',
    'snippet' => $snippetcode,
    'category' => 0,
], '', true, true);

// Добавляем свойства для сниппета (если есть)
$properties = [
    [
        'name' => 'duration',
        'desc' => 'Длительность доступа в часах',
        'type' => 'textfield',
        'options' => '',
        'value' => '1',
    ],
    [
        'name' => 'userGroup',
        'desc' => 'ID группы пользователей для доступа',
        'type' => 'textfield',
        'options' => '',
        'value' => '1',
    ]
];

$snippet->setProperties($properties);

$snippets[] = $snippet;

// Получаем код для хука создания ссылки
$createHookCode = getSnippetContent($sources['core'] . 'elements/snippets/createhook.snippet.php');

// Создаем сниппет для хука создания ссылки
$createHookSnippet = $modx->newObject(modSnippet::class);
$createHookSnippet->fromArray([
    'name' => 'UserLinkAccessCreateHook',
    'description' => 'Хук, вызываемый при создании временной ссылки',
    'snippet' => $createHookCode,
    'category' => 0,
], '', true, true);

// Получаем код для хука удаления ссылки
$deleteHookCode = getSnippetContent($sources['core'] . 'elements/snippets/deletehook.snippet.php');

// Создаем сниппет для хука удаления ссылки
$deleteHookSnippet = $modx->newObject(modSnippet::class);
$deleteHookSnippet->fromArray([
    'name' => 'UserLinkAccessDeleteHook',
    'description' => 'Хук, вызываемый при удалении временной ссылки',
    'snippet' => $deleteHookCode,
    'category' => 0,
], '', true, true);

// Свойства для хуков
$hookProperties = [
    [
        'name' => 'resourceId',
        'desc' => 'ID ресурса',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
    ],
    [
        'name' => 'userId',
        'desc' => 'ID пользователя',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
    ]
];
$deleteHookSnippet->setProperties($hookProperties);
// Добавляем в параметры для хука сохдания ссылки URL ссылки
$hookProperties[] = [
        'name' => 'url',
        'desc' => 'Ссылка',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
    ];
$createHookSnippet->setProperties($hookProperties);

// Добавляем хуки в массив сниппетов
$snippets[] = $createHookSnippet;
$snippets[] = $deleteHookSnippet;

return $snippets;
