<div id="userlinkaccess-container" class="mt-2">
    <div id="userlinkaccess-form" class="card">
        <div class="card-header fw-bold">
            <span>Создать клиентскую ссылку</span>
        </div>

        <div class="card-body">
            <input type="hidden" name="csrf_token" value="[[!csrfhelper? &key=`user` &singleUse=`1`]]"/>

            <div class="mb-2">
                <label for="userlinkaccess-lifetime" class="form-label">Время действия ссылки:</label>
                <select id="userlinkaccess-lifetime" class="form-select">
                    <option value="3600">1 час</option>
                    <option value="7200">2 часа</option>
                    <option value="14400">4 часа</option>
                    <option value="28800">8 часов</option>
                    <option value="43200">12 часов</option>
                    <option value="86400">24 часа</option>
                </select>
            </div>

            <div id="userlinkaccess-actions" class="mt-2">
                <button class="btn btn-sm btn-success" data-resource="[[*id]]" data-action="userlinkaccess-generate"><i class="bi bi-link-45deg me-1"></i>&nbsp;Создать</button>
            </div>
        </div>
    </div>
    
    <div id="userlinkaccess-result">
        <!-- результат генерации ссылки -->
    </div>

    <!-- Всплывающее уведомление -->
    <div class="py-2">
        <div id="userlinkaccess-copy" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle"></i>&nbsp;Ссылка скопирована в буфер обмена
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    [[!UserLinkAccessGetActiveLinks]]

    <link rel="stylesheet" href="[[++assets_url]]components/userlinkaccess/css/userlinkaccess.css">

    <script>
      const UserLinkAccessConfig = {
        connectorUrl: '[[++assets_url]]components/userlinkaccess/connector.php'
      };
    </script>
    <script src="[[++assets_url]]components/userlinkaccess/js/userlinkaccess.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        document.addEventListener('newUserLinkAddEvent', e => {
          console.log('New user add. Event:', e.detail)
        })
      })
    </script>
</div>