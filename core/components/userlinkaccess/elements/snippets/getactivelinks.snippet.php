<?php
/**
 * UserLinkAccessGetActiveLinks
 *
 * Сниппет для вывода активных ссылок
 *
 * @package userlinkaccess
 *
 * @return string HTML для копирования ссылок пользователем
 */

$userId = $modx->user->id;

// Находим активные ссылки, созданные текущим пользователем
$links = $modx->getCollection('userlinkaccess\\UserLinkAccessLink', [
  'created_by' => $userId,
  'is_active' => 1,
]);
$output = '';

if (count($links)) {
    $output .= '
      <div class="card" class="mt-2">
        <div class="card-header fw-bold">
          <span>Активные ссылки</span>
        </div>
        <div id="userlinkaccess-links" class="card-body">
    ';
    foreach($links as $key => $link) {
        $sessionUrl = $modx->makeUrl(
          $modx->resource->get('id'),
          '',
          ['userlinkaccess' => $link->hash],
          'full'
        );
        $link_user = $modx->getObject('modUser', $link->user_id);
        $output .= '
          <label for="userlinkaccess-link-' . $key  . '">
            <span class="badge rounded-pill">' . $link_user->getOne('Profile')->get('fullname')  . '</span>
          </label>
          <div class="input-group mb-2">
            <input id="userlinkaccess-link-' . $key  . '" type="text" class="form-control" value="'. $sessionUrl . '" readonly/>
            <button class="btn btn-primary" type="button" data-target="userlinkaccess-link-' . $key  . '" title="Скопировать ссылку">
              <i class="bi bi-copy"></i>
            </button>
          </div>
        ';
    }
    $output .= '
        </div>
      </div>
    ';
}

return $output;