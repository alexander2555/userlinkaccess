<div class="userlinkaccess-container container">
    <h4>Создать ссылку</h4>

    <div class="userlinkaccess-form">

        <input type="hidden" name="csrf_token" value="[[!csrfhelper? &key=`user` &singleUse=`1`]]">

        <div class="userlinkaccess-field">
            <label for="userlinkaccess-lifetime">Время действия ссылки:</label>
            <select id="userlinkaccess-lifetime" class="userlinkaccess-lifetime-select">
                <option value="3600">1 час</option>
                <option value="7200">2 часа</option>
                <option value="14400">4 часа</option>
                <option value="28800">8 часов</option>
                <option value="43200">12 часов</option>
                <option value="86400">24 часа</option>
            </select>
        </div>

        <div class="userlinkaccess-actions">
            <button class="userlinkaccess-generate-btn" data-resource="[[*id]]">Сгенерировать ссылку</button>
        </div>
    </div>

    <div class="userlinkaccess-result">
        <!-- Здесь будет отображаться результат генерации ссылки -->
    </div>
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
