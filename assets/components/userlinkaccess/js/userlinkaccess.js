/**
 * UserLinkAccess Frontend JS
 *
 */
(() => {
  const UserLinkAccess = {
    init() {
      this.setupListeners()
    },

    setupListeners() {
      // Находим все кнопки генерации ссылок
      const generateButtons = document.querySelectorAll('[data-action="userlinkaccess-generate"]');

      generateButtons.forEach(button => {
        button.addEventListener('click', e => {
          e.preventDefault();

          const resourceId = button.getAttribute('data-resource');
          const container = document.getElementById('userlinkaccess-container');
          const formContainer = document.getElementById('userlinkaccess-form');
          const lifetimeSelect = document.getElementById('userlinkaccess-lifetime');
          const resultContainer = document.getElementById('userlinkaccess-result');

          // Если нет контейнера для результата или не указан ресурс, выходим
          if (!resourceId || !resultContainer) return

          // Получаем выбранное время жизни ссылки
          const lifetime = lifetimeSelect ? parseInt(lifetimeSelect.value) || 3600 : 3600; // 1 час по умолчанию

          // Индикатор загрузки
          resultContainer.innerHTML = `
            <div class="d-flex justify-content-center">
              <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
          `

          // Генерация ссылки и создание пользователя через AJAX-коннектор
          this.generateLink(resourceId, lifetime, response => {
            if (response.success) {
              formContainer.style.display = 'none'
              this.displayLink(resultContainer, response.object.link);
              
              document.dispatchEvent(
                new CustomEvent('newUserLinkAddEvent', {
                  detail: { link: response.object.link },
                  bubbles: true,
                  cancelable: true,
                })
              )
            } else {
              resultContainer.innerHTML = `
                <div class="card border-danger p-3">
                  <div class="text-danger fw-semibold small">${response.message || 'Ошибка генерации ссылки'}</div>
                </div>
              `
            }
          })
        })
      })

      // Обработка нажатия на кнопку копирования
      document.addEventListener('click', e => {
        const isTargetBtn = e.target.getAttribute('id') === 'userlinkaccess-copy-btn' || (e.target.closest('button') && e.target.closest('button').getAttribute('id') === 'userlinkaccess-copy-btn')
        if (isTargetBtn) {
          e.preventDefault()
          const btnCopy = document.getElementById('userlinkaccess-copy-btn')
          const inputLink = document.getElementById('userlinkaccess-link')
          const linkText = inputLink.value
          if (linkText)
            this.copyToClipboard(linkText, btnCopy)
              .then(() => console.log('The user\'s link has been copied to the clipboard.'))
        }
      })
    },

    /**
     * Генерация временной ссылки
     *
     * @param {number}    resourceId  ID ресурса
     * @param {number}    lifetime    Время жизни ссылки в секундах
     * @param {function}  callback    Обработка результата
     *
     * @param_global {Object} UserLinkAccessConfig Global config object with connector url
     */
    async generateLink(resourceId, lifetime, callback) {
      try {
        // Создаем параметры запроса
        const formData = new FormData();
        formData.append('action', 'userlinkaccess/generate');
        formData.append('resourceId', resourceId);
        formData.append('lifetime', lifetime);
        // formData.append('session_id', '<?php echo session_id(); ?>')

        // Добавляем CSRF токен, если он есть
        const csrfToken = this.getCSRFToken()
        if (csrfToken)
          formData.append('csrf', csrfToken)

        // Отправляем AJAX запрос
        const response = await fetch(UserLinkAccessConfig.connectorUrl, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
        })
        const data = await response.json();
        console.log('Ответ коннектора:', data)
        callback?.(data);
      } catch (error) {
        console.error('AJAX Error:', error);
        callback?.({
          success: false,
          message: 'Ошибка при обращении к серверу'
        })
      }
    },

    /**
     * Получение CSRF-токена
     *
     * @returns {string|null} CSRF-токен или null
     */
    getCSRFToken() {
      // Проверяем наличие скрытого поля с токеном
      const tokenField = document.querySelector('input[name="csrf_token"]')
      if (tokenField)
        return tokenField.value

      // Также проверяем мета-тег
      const metaToken = document.querySelector('meta[name="csrf_token"]')
      if (metaToken)
        return metaToken.getAttribute('content')

      // Проверяем наличие токена в данных страницы (если CSRFHelper используется с Javascript)
      if (window.CSRFHelper && window.CSRFHelper.token)
        return window.CSRFHelper.token

      console.warn('CSRF-токен не найден. Проверьте настройки.')
      return null
    },

    /**
     * Отображение сгенерированной ссылки
     *
     * @param {HTMLElement} container Контейнер для отображения
     * @param {string} link Сгенерированная ссылка
     */
    displayLink(container, link) {
      // Создаем контейнер для ссылки
      const html = `
        <div class="card border-success p-3">
          <div class="fw-bold mb-2">Ссылка для временного доступа:</div>
          <div class="input-group mb-2">
            <input id="userlinkaccess-link" type="text" class="form-control" value="${link}" readonly>
            <button id="userlinkaccess-copy-btn" class="btn btn-primary" type="button">
              <i class="bi bi-clipboard"></i>
            </button>
          </div>
          <div class="text-muted small">Ссылка будет действительна в течение выбранного времени</div>
        </div>
      `
      container.innerHTML = html
    },

    /**
     * Копирование текста в буфер обмена
     *
     * @param {string} text Текст для копирования
     * @param {HTMLElement} button Кнопка, которая была нажата
     */
    async copyToClipboard(text, button) {
      const copyBtnHtml = '<i class="bi bi-check"></i>'
      const prevBtnHtml = button.innerHTML
      console.log(prevBtnHtml)
      const btnCopyState = (readyHtml = copyBtnHtml) => {// деактивируем кнопку и меняем иконку на галочку
        button.disabled = true
        button.innerHTML = readyHtml
        button.classList.remove('btn-primary')
        button.classList.add('btn-success')
      }
      const btnPrevState = () => {// возвращаем исходное состояние
          button.disabled = false
          button.innerHTML = prevBtnHtml
          button.classList.remove('btn-success')
          button.classList.add('btn-primary')
      }

      try {
        // Clipboard API
        await navigator.clipboard.writeText(text)
        // Сообщение об успешном копировании
        const toast = new bootstrap.Toast(document.getElementById('userlinkaccess-copy'));
        toast.show()
        
        btnCopyState()
        setTimeout(() => {
          btnPrevState()
        }, 2000)
        
      } catch (err) {
        console.error('Не удалось скопировать текст через Clipboard API: ', err);

        // Запасной метод без поддержки Clipboard API
        try {
          // Создаем временный элемент ввода
          const input = document.createElement('input');
          input.value = text;

          // Настраиваем стили, чтобы скрыть элемент, но сделать его доступным
          input.style.position = 'fixed';
          input.style.opacity = '0';
          input.style.pointerEvents = 'none';

          // Добавляем в DOM
          document.body.appendChild(input);

          // Для iOS нужно разрешить редактирование перед выделением
          input.contentEditable = true;
          input.readOnly = false;

          // Для iOS Safari требуется диапазон выделения
          const range = document.createRange();
          range.selectNodeContents(input);

          // Выделяем текст
          const selection = window.getSelection();
          selection.removeAllRanges();
          selection.addRange(range);
          input.setSelectionRange(0, text.length);

          // Фокусируемся на элементе
          input.focus();

          // Показываем пользователю подсказку для ручного копирования
          button.textContent = 'Нажмите Ctrl+C/⌘+C для копирования';

          // Слушатель для отслеживания копирования пользователем
          const handleCopy = () => {
            btnCopyState()

            document.removeEventListener('copy', handleCopy)

            setTimeout(() => {
              btnPrevState()
            }, 2000)
          }

          document.addEventListener('copy', handleCopy, { once: true })

          // Удаляем элемент через определенное время
          setTimeout(() => {
            document.body.removeChild(input);
            selection.removeAllRanges();

            // Если копирование не произошло, вернем исходный текст кнопки
            if (button.innerHtml !== copyBtnHtml)
              btnPrevState()
          }, 5000)
          
        } catch (fallbackErr) {
          console.error('Все методы копирования не сработали: ', fallbackErr)
          alert('Не удалось скопировать ссылку. Пожалуйста, выделите и скопируйте её вручную.')
          btnPrevState()
        }
      }
    }
  }

  // Инициализация после загрузки DOM
  document.addEventListener('DOMContentLoaded', () => {
    UserLinkAccess.init();
  })

  // Экспортируем объект UserLinkAccess в глобальную область видимости
  window.UserLinkAccess = UserLinkAccess;
})()