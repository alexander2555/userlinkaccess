<?php
/**
 * UserLinkAccess build script - chunks
 *
 * @package userlinkaccess
 */

use MODX\Revolution\modChunk;

$chunks = [];

$chunk = $modx->newObject(modChunk::class);
$chunk->fromArray([
    'name' => 'UserLinkAccessFormTpl',
    'description' => 'Форма генерации ссылки для UserLinkAccess',
    'content' => file_get_contents($sources['core'] . 'elements/chunks/userlinkaccess.form.tpl'),
    'category' => 0,
], '', true, true);

$chunks[] = $chunk;

return $chunks;
