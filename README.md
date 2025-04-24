# UserLinkAccess

**UserLinkAccess** — компонент для **MODX 3**, который позволяет предоставлять временный доступ по ссылке к ресурсам

## 📦 Установка

**Желалельно** установить пакет [CSRFHelper](https://docs.modmore.com/en/Open_Source/CSRFHelper/index.html)

**Установить** **_UserLinkAccess_** в Менеджере пакетов

**Настроить** параметры в пространстве имен _userlinkaccess_ при необходимости

**Необходим** [Bootstrap](https://docs.modmore.com/en/Open_Source/CSRFHelper/index.html) для корректного вывода фронтенда

## 🚀 Использование

Для генерации ссылки непосредственно можно использовать **сниппет**:

``[[UserLinkAccessGenerate? &resourceId=`10` &lifetime=`3600`]]``

**сниппет** ``[[!UserLinkAccessGetActiveLinks]]`` - для вывода актуальных активных ссылок.

Форма создания ссылки - это **чанк** `[[$UserLinkAccessFormTpl]]`

Также при создании и удалении ссылки выполняются соответствующие **хуки** (по умолчанию: _UserLinkAccessCreateHook_ и _UserLinkAccessDeleteHook_.).
Эти сниппеты можно переопределить в соответствующих настройках (_userlinkaccess.userlinkaccess_create_link_hook_, _userlinkaccess.userlinkaccess_delete_link_hook_).
