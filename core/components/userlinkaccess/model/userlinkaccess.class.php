<?php
/**
 * UserLinkAccess
 *
 * Система генерации временных ссылок доступа для MODX 3
 *
 * @package userlinkaccess
 */

/** Класс UserLinkAccess */
class UserLinkAccess {
    /** @var \MODX\Revolution\modX $modx */
    public $modx;
    /** @var array $config */
    public $config = [];

    /**
     * Конструктор класса UserLinkAccess
     *
     * @param \MODX\Revolution\modX $modx Объект MODX
     * @param array $config Массив конфигурации
     */
    function __construct(\MODX\Revolution\modX &$modx, array $config = []) {
        $this->modx = $modx;
        $this->config = array_merge([
            'basePath' => $modx->getOption('core_path') . 'components/userlinkaccess/',
            'corePath' => $modx->getOption('core_path') . 'components/userlinkaccess/',
            'modelPath' => $modx->getOption('core_path') . 'components/userlinkaccess/model/',
            'processorsPath' => $modx->getOption('core_path') . 'components/userlinkaccess/processors/',
            'elementsPath' => $modx->getOption('core_path') . 'components/userlinkaccess/elements/',
            'templatesPath' => $modx->getOption('core_path') . 'components/userlinkaccess/elements/templates/',
            'assetsPath' => $modx->getOption('assets_path') . 'components/userlinkaccess/',
            'assetsUrl' => $modx->getOption('assets_url') . 'components/userlinkaccess/',
            'jsUrl' => $modx->getOption('assets_url') . 'components/userlinkaccess/js/',
            'cssUrl' => $modx->getOption('assets_url') . 'components/userlinkaccess/css/',
            'connectorUrl' => $modx->getOption('assets_url') . 'components/userlinkaccess/connector.php',
            'defaultLinkLifetime' => 3600, // 1 час по умолчанию
            'tempUserGroupPrefix' => 'Clients_', // Префикс для группы временных пользователей
            'contextAccess' => 'web', // Контекст для доступа
            'maxUsersPerResource' => 2, // Максимальное количество временных пользователей на ресурс
            'accessPolicy' => 1, // ID политики доступа Resource (просмотр)
            'createLinkHook' => $modx->getOption('userlinkaccess_create_link_hook', null, 'UserLinkAccessCreateHook'), // хук для создания ссылки
            'deleteLinkHook' => $modx->getOption('userlinkaccess_delete_link_hook', null, 'UserLinkAccessDeleteHook'), // хук для удаления ссылки
        ], $config);

        $this->modx->addPackage('userlinkaccess', $this->config['modelPath'], null, 'userlinkaccess\\');
    }

    /**
     * Генерирует временную ссылку для доступа к ресурсу
     *
     * @param int $resourceId ID ресурса
     * @param int $lifetime Время жизни ссылки в секундах
     * @param int $maxUsers Максимальное количество пользователей с доступом к ресурсу
     * @return string|bool Сгенерированная ссылка или false в случае ошибки
     */
    public function generateLink($resourceId, $lifetime = null, $maxUsers = null) {
        $currentUser = $this->modx->getUser();
        if (!$currentUser->isAuthenticated()) {
            $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Попытка создания ссылки неавторизованным пользователем');
            return false;
        }

        if ($lifetime === null) {
            $lifetime = $this->config['defaultLinkLifetime'];
        }

        if ($maxUsers === null) {
            $maxUsers = $this->config['maxUsersPerResource'];
        }

        // Проверяем, не превышен ли лимит пользователей для данного ресурса
        $activeLinks = $this->modx->getCount('userlinkaccess\\UserLinkAccessLink', [
            'resource_id' => $resourceId,
            'is_active' => 1,
            'expires_at:>' => time(),
        ]);
        if ($activeLinks >= $maxUsers) {
            $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Превышен лимит временных пользователей для ресурса #' . $resourceId);
            return false;
        }

        // Находим группы ресурсов, к которым принадлежит ресурс
        $resourceGroups = [];
        $q = $this->modx->newQuery('MODX\Revolution\modResourceGroupResource');
        $q->where(['document' => $resourceId]);
        $q->select(['document_group']);
        
        if ($q->prepare() && $q->stmt->execute()) {
            $resourceGroupIds = $q->stmt->fetchAll(\PDO::FETCH_COLUMN);
            if (empty($resourceGroupIds)) {
                $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Ресурс #' . $resourceId . ' не принадлежит ни к одной группе ресурсов');
                return false;
            }
            
            // Получаем информацию о группах ресурсов
            foreach ($resourceGroupIds as $rgId) {
                $resourceGroup = $this->modx->getObject('MODX\Revolution\modResourceGroup', $rgId);
                if ($resourceGroup) {
                    $resourceGroups[] = [
                        'id' => $rgId,
                        'name' => $resourceGroup->get('name')
                    ];
                }
            }
        }
        
        if (empty($resourceGroups)) {
            $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Не удалось получить информацию о группах ресурсов для ресурса #' . $resourceId);
            return false;
        }

        // Создаем временного пользователя
        $username = 'client_' . uniqid();
        $password = $this->generateRandomPassword();
        $email = $username . '@userlinkaccess.local';

        // Создаем нового пользователя
        $user = $this->modx->newObject('MODX\Revolution\modUser');
        $user->set('username', $username);
        $user->set('password', $password);
        $user->set('active', 1);
        
        $user->set('sudo', 0);
        $user->set('createdon', time());

        // Создаем профиль пользователя
        $profile = $this->modx->newObject('MODX\Revolution\modUserProfile');
        // $profile->set('internalKey', 0);
        $profile->set('email', $email);
        $profile->set('fullname', 'Временный доступ');
        
        $profile->set('blocked', 0);
        $profile->set('blockeduntil', 0);
        $profile->set('blockedafter', 0);
        
        $user->addOne($profile);

        if (!$user->save()) {
            $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Не удалось создать временного пользователя');
            return false;
        }

        // Для каждой группы ресурсов обеспечиваем доступ
        $tempUserGroups = [];
        foreach ($resourceGroups as $resourceGroup) {
            // Проверяем/создаем группу пользователей для временного доступа к этой группе ресурсов
            $tempUserGroupName = $this->config['tempUserGroupPrefix'] . $resourceGroup['name'];
            $tempUserGroup = $this->modx->getObject('MODX\Revolution\modUserGroup', ['name' => $tempUserGroupName]);
            
            if (!$tempUserGroup) {
                // Создаем новую группу пользователей для временного доступа
                $tempUserGroup = $this->modx->newObject('MODX\Revolution\modUserGroup');
                $tempUserGroup->set('name', $tempUserGroupName);
                $tempUserGroup->set('description', 'Группа для временного доступа к ресурсам группы ' . $resourceGroup['name']);
                
                if (!$tempUserGroup->save()) {
                    $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Не удалось создать группу пользователей ' . $tempUserGroupName);
                    $user->remove();
                    return false;
                }
                
                // ACL для контекста
                $accessContext = $this->modx->newObject('MODX\Revolution\modAccessContext');
                $accessContext->set('principal', $tempUserGroup->get('id'));
                $accessContext->set('principal_class', 'MODX\Revolution\modUserGroup');
                $accessContext->set('target', $this->config['contextAccess']);
                $accessContext->set('policy', $this->config['accessPolicy']);
                $accessContext->set('authority', 9999);
                $accessContext->save();
                
                // ACL для группы ресурсов
                $accessResourceGroup = $this->modx->newObject('MODX\Revolution\modAccessResourceGroup');
                $accessResourceGroup->set('principal', $tempUserGroup->get('id'));
                $accessResourceGroup->set('principal_class', 'MODX\Revolution\modUserGroup');
                $accessResourceGroup->set('target', $resourceGroup['id']);
                $accessResourceGroup->set('context_key', $this->config['contextAccess']);
                $accessResourceGroup->set('policy', $this->config['accessPolicy']);
                $accessResourceGroup->set('authority', 9999);
                $accessResourceGroup->save();
            }
            
            // Добавляем пользователя в группу временного доступа
            $userGroupMember = $this->modx->newObject('MODX\Revolution\modUserGroupMember');
            $userGroupMember->set('user_group', $tempUserGroup->get('id'));
            $userGroupMember->set('member', $user->get('id'));
            $userGroupMember->set('role', 1); // Member role
            $userGroupMember->save();
        }

        // Создаем запись о временной ссылке в таблице
        $tempLink = $this->modx->newObject('userlinkaccess\\UserLinkAccessLink');
        $hash = $this->generateUniqueHash();
        $expireTime = time() + $lifetime;
        $newUserId = $user->get('id');

        $tempLink->set('user_id', $newUserId);
        $tempLink->set('resource_id', $resourceId);
        $tempLink->set('hash', $hash);
        $tempLink->set('created_by', $currentUser->get('id'));
        $tempLink->set('created_at', time());
        $tempLink->set('expires_at', $expireTime);
        $tempLink->set('is_active', 1);
        $tempLink->set('used', '');
        // $tempLink->set('max_users', $maxUsers);

        if (!$tempLink->save()) {
          $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Не удалось сохранить временную ссылку');
          $user->remove();
          return false;
        }
        
        // Формируем ссылку
        $url = $this->modx->makeUrl($resourceId, '', [
            'userlinkaccess' => $hash
        ], 'full');

        // После создания пользователя и ссылки вызываем хук, если он указан в настройках и существует сниппет
        $createHook = $this->config['createLinkHook'];
        if ($this->modx->getObject('MODX\Revolution\modSnippet', ['name' => $createHook])) {
            $this->modx->runSnippet($createHook, [
                'resourceId' => $resourceId,
                'userId' => $newUserId,
                'url' => $url
            ]);
        }
        // else { // сниппет по умолчанию
        //     $this->modx->runSnippet('UserLinkAccessCreateHook', [
        //         'resourceId' => $resourceId,
        //         'userId' => $newUserId,
        //     ]);
        // }
        
        $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Ссылка: ' . $url);
        return $url;
    }

    /**
     * Проверяет и активирует временную ссылку
     *
     * @param string $hash Хэш ссылки
     * @return bool Результат активации
     */
    public function activateLink($hash, $requestInfo) {
        // Находим ссылку по хешу
        $tempLink = $this->modx->getObject('userlinkaccess\\UserLinkAccessLink', [
            'hash' => $hash,
            'is_active' => 1,
            'expires_at:>' => time(),
        ]);
        if (!$tempLink) {
            $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Временная ссылка не найдена или устарела - хеш: ' . $hash);
            return false;
        }

        // Проверяем IP-адрес
        $usedIp = $tempLink->get('used');
        if (!empty($usedIp)) {
            $currentIp = $requestInfo['IP'];
            
            // Если в сохраненном IP есть маска *.
            if (strpos($usedIp, '.*') !== false) {
                $usedIpPrefix = str_replace('.*', '', $usedIp);
                if (strpos($currentIp, $usedIpPrefix) !== 0) {
                    $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Попытка доступа из другой подсети: ' . $currentIp . ' (разрешено: ' . $usedIp . ')');
                    return false;
                }
            } 
            // Для публичных IP проверяем точное совпадение
            else if ($usedIp !== $currentIp) {
                $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Попытка доступа с другого IP-адреса: ' . $currentIp . ' (разрешено: ' . $usedIp . ')');
                return false;
            }
        }
        // Проверяем User-Agent на боты и не-браузеры
        $userAgent = $requestInfo['User-Agent'];
        if (preg_match('/(bot|crawler|spider|wget|curl|postman|python|java|php|ruby|unknown)/i', $userAgent)) {
            $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Попытка доступа по ссылке не из браузера: ' . $userAgent);
            return false;
        }
        // Если IP еще не сохранен, сохраняем его
        if (empty($usedIp)) {
            $ip = $requestInfo['IP'];
            
            // Определяем, является ли IP адрес частным
            $isPrivateNetwork = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
            
            if ($isPrivateNetwork) {
                // Для частных IP берем первые три октета
                $ipParts = explode('.', $ip);
                if (count($ipParts) === 4) {
                    $ip = implode('.', array_slice($ipParts, 0, 3)) . '.*';
                }
            }
            // Для публичных IP используем полный адрес
            
            $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '[UserLinkAccess] Доступ по ссылке для IP: ' . $ip . ' (original: ' . $requestInfo['IP'] . ')');
            $tempLink->set('used', $ip);
            $tempLink->save();
        }

				// Авторизуем пользователя
				$user = $this->modx->getObject('MODX\Revolution\modUser', $tempLink->get('user_id'));
				if (!$user) {
						$this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Пользователь не найден для ссылки: ' . $hash);
						return false;
				}
				// Получаем профиль пользователя
				$userProfile = $user->getOne('Profile');
				if (!$userProfile) {
						$this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Профиль пользователя не найден для ID: ' . $user->get('id'));
						return false;
				}

				// Устанавливаем время жизни сессии
				$lifetime = $tempLink->get('expires_at') - time();
				// Регенерируем ID сессии для безопасности
				if (session_id()) {
						session_regenerate_id(true);
				}
				// Получаем хеш-соль для паролей
				$hashRequest = $this->modx->runProcessor('security/user/get', ['id' => $user->get('id')]);
				$userObject = $hashRequest->getObject();
				
				// Создаем временный пароль для авторизации
				$tempPass = uniqid('p_');
				$originalHash = $user->get('password'); // текущий пароль
				
				// Устанавливаем временный пароль
				$user->set('password', $tempPass);
				$user->save();
					
				// Подготовка данных для процессора авторизации
				$loginData = [
						'username' => $user->get('username'),
						'password' => $tempPass,
						'login_context' => $this->config['contextAccess'],
						'add_contexts' => '',
						'rememberme' => true,
						'skipPasswordCheck' => true // Пропускаем проверку пароля
				];

				// Обновляем последнее время входа пользователя
				$userProfile->set('lastlogin', time());
				$userProfile->save();

				try {
					// Запускаем процессор авторизации
					$response = $this->modx->runProcessor('security/login', $loginData);
					
					// Проверяем результат
					if ($response->isError()) {
							$this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Ошибка авторизации через процессор: ' . $response->getMessage());
							// Дополнительное логирование для отладки
							$this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Данные авторизации: ' . print_r($loginData, true));
							return false;
					}
					
					// Если авторизация успешна - перенаправляем на нужный ресурс
					$resourceId = $tempLink->get('resource_id');
					$this->modx->sendRedirect($this->modx->makeUrl($resourceId, '', [], 'full'));
					return true;
					
				} catch (\Exception $e) {
					$this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[UserLinkAccess] Исключение при авторизации: ' . $e->getMessage());
					return false;
				}
    }

    /**
     * Освобождаем временную ссылку (при выходе пользователя)
		 * 
		 * @param $user modUser объект пользователя
		 * @return array{success: bool, message: string} Результат
     */
    public function releaseLink($user) {
        $currentUser = $user ?? $this->modx->getUser();

        if (!$currentUser) {
            return [
                'success' => false,
                'message' => 'Пользователь не определён!'
            ];
        }

        $userId = $currentUser->get('id');
        $username = $currentUser->get('username');

        // Является ли пользователь временным
        if (strpos($username, 'client_') !== 0) {
          return [
              'success' => false,
              'message' => 'Пользователь не относится к клиентам!'
          ];
        }

        // Находим ссылку
        $tempLink = $this->modx->getObject('userlinkaccess\\UserLinkAccessLink', [
            'user_id' => $userId,
            'is_active' => 1,
        ]);
        if (!$tempLink) {
          return [
            'success' => false,
            'message' => 'Ссылка не найдена!'
          ];
        }
        // $tempLink->set('used', '');
        // $tempLink->set('is_active', 0);
        // $tempLink->save();

        return [
          'success' => true,
          'message' => 'Успешно).'
        ];
    }

    /**
     * Очищаем просроченные ссылки и пользователей
     */
    public function cleanupExpiredLinks() {
        // Получаем все просроченные ссылки
        $expiredLinks = $this->modx->getCollection('userlinkaccess\\UserLinkAccessLink', [
            'expires_at:<' => time(),
            'is_active' => 1,
        ]);

        $cleanedCount = 0;
        foreach ($expiredLinks as $link) {
            $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '[UserLinkAccess cleanup] Найдена устаревшая активная ссылка: ' . $link->get('hash'));
            $userId = $link->get('user_id');
            
            // Удаляем пользователя
            $user = $this->modx->getObject('MODX\Revolution\modUser', $userId);
            
            if ($user) {
              // Вызываем хук, если он указан в настройках и существует сниппет
              $deleteHook = $this->config['deleteLinkHook'];

              $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '[UserLinkAccess cleanup] Запускается хук удаления: ' . $deleteHook . ' ...');
              
              if ($hook = $this->modx->getObject('MODX\Revolution\modSnippet', ['name' => $deleteHook])) {
                $this->modx->runSnippet($deleteHook, [
                  'resourceId' => $link->get('resource_id'),
                  'userId' => $userId,
                ]);
              }
              // else { // хук по умолчанию
              //   $this->modx->runSnippet('UserLinkAccessDeleteHook', [
              //     'resourceId' => $link->get('resource_id'),
              //     'userId' => $userId,
              //   ]);
              // }
              $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '[UserLinkAccess cleanup] ... успешно! Удаляется временный пользователь: ' . $userId);

              // Удаляем пользователя
              $user->remove();
              
              $cleanedCount++;
            }
            // Деактивируем ссылку
            $link->set('is_active', 0);
            $link->save();
            
        }
        
        $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '[UserLinkAccess cleanup] Очищено ' . $cleanedCount . ' устаревших ссылок и пользователей.');
    }

    /**
     * Генерирует случайный пароль
     *
     * @param int $length Длина пароля
     * @return string Сгенерированный пароль
     */
    private function generateRandomPassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }

    /**
     * Генерирует уникальный хеш для ссылки
     *
     * @return string Уникальный хеш
     */
    private function generateUniqueHash() {
        $hash = bin2hex(random_bytes(16));

        // Проверяем на уникальность
        $exists = $this->modx->getCount('userlinkaccess\\UserLinkAccessLink', ['hash' => $hash]);
        if ($exists > 0) {
            return $this->generateUniqueHash();
        }

        return $hash;
    }
}