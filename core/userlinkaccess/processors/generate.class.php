<?php
/**
 * Generate Link Processor
 *
 * Процессор для генерации временной ссылки через AJAX
 *
 * @package userlinkaccess
 */
namespace userlinkaccess\processors;

use \MODX\Revolution\Processors\Processor;

class GenerateProcessor extends Processor {
    /**
     * @return array|string
     */
    public function process() {
        // Проверяем авторизацию
        if (!$this->modx->user->isAuthenticated()) {
            return $this->failure('Для генерации ссылки необходимо быть авторизованным.');
        }

        // Получаем ID ресурса
        $resourceId = (int)$this->getProperty('resourceId');
        if (!$resourceId) {
            return $this->failure('Не указан ID ресурса для доступа.');
        }

        // Проверяем существование ресурса
        $resource = $this->modx->getObject('MODX\Revolution\modResource', $resourceId);
        if (!$resource) {
            return $this->failure('Указанный ресурс не существует.');
        }

        // Получаем время жизни ссылки
        $lifetime = (int)$this->getProperty('lifetime', 3600);
        if ($lifetime <= 0) {
            $lifetime = 3600;
        }

        // Загружаем класс UserLinkAccess
        $corePath = $this->modx->getOption('core_path') . 'components/userlinkaccess/';
        if (file_exists($corePath . 'model/userlinkaccess.class.php')) {
            require_once $corePath . 'model/userlinkaccess.class.php';
        } else {
            return $this->failure('Не удалось загрузить класс UserLinkAccess');
        }

        // Создаем экземпляр класса UserLinkAccess
        $userLinkAccess = new \UserLinkAccess($this->modx);

        // Генерируем ссылку
        $link = $userLinkAccess->generateLink($resourceId, $lifetime);

        if (!$link) {
            return $this->failure('Не удалось сгенерировать ссылку.');
        }

        return $this->success('', ['link' => $link]);
    }
}

return 'userlinkaccess\\processors\\GenerateProcessor';