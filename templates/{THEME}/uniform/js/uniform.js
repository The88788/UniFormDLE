/*!
 * DLE UniForm - унверсальные формы для DLE
 *
 * @author     pafnuty <pafnuty10@gmail.com>
 * @link       http://pafnuty.name/
 * @link       https://twitter.com/pafnuty_name
 * @version     2.1.0
 * @adapted    Adapted for DLE 21 / PHP 8.x by The88788.com (2026)
 */

var doc = $(document);

// Server HTML remains necessary for forms; accept only the expected form response.
function ufParseForm(html, expectedConfig) {
  if (typeof html !== 'string' || !html.trim()) throw new Error('Empty form response');
  var parsed = new DOMParser().parseFromString(html, 'text/html');
  if (parsed.body.children.length !== 1 || parsed.body.firstElementChild.tagName !== 'FORM'
      || Array.from(parsed.body.childNodes).some(function (node) { return node.nodeType === 3 && node.textContent.trim(); })
      || parsed.querySelector('script, iframe, object, embed, base, meta, link, style')) throw new Error('Invalid form response');
  var form = parsed.body.firstElementChild;
  var action = new URL(form.getAttribute('action') || '', location.href);
  var config = form.querySelector('input[name="formConfig"]');
  var token = form.querySelector('input[name="csrfToken"]');
  if (!form.hasAttribute('data-uf-form') || form.getAttribute('method')?.toLowerCase() !== 'post'
      || action.origin !== location.origin || action.pathname !== '/index.php'
      || action.searchParams.get('controller') !== 'ajax' || action.searchParams.get('mod') !== 'uniform'
      || !config || !token || !token.value || (expectedConfig && config.value !== expectedConfig)) throw new Error('Unexpected form');
  Array.from(form.querySelectorAll('*')).concat([form]).forEach(function (element) {
    Array.from(element.attributes).forEach(function (attribute) {
      if (/^on/i.test(attribute.name) || attribute.name === 'srcdoc'
          || (/^(href|src|action|formaction|xlink:href)$/i.test(attribute.name)
              && /^(javascript|vbscript|data):/i.test(attribute.value.replace(/[\x00-\x20]/g, '')))) throw new Error('Active content in form');
    });
  });
  return document.importNode(form, true);
}

function ufShowError(container, message) {
  var existing = container.querySelector('[data-uf-network-error]');
  if (existing) existing.remove();
  var alert = document.createElement('div');
  alert.className = 'uf-alert uf-alert-error';
  alert.dataset.ufNetworkError = '1';
  alert.setAttribute('role', 'alert');
  alert.textContent = message;
  container.prepend(alert);
}

async function ufLoadForm(url, config) {
  var controller = new AbortController();
  var timer = setTimeout(function () { controller.abort(); }, 30000);
  try {
    var response = await fetch(url, { signal: controller.signal, credentials: 'same-origin' });
    if (!response.ok || !/text\/html/i.test(response.headers.get('content-type') || '')) throw new Error('HTTP form error');
    return ufParseForm(await response.text(), config);
  } finally { clearTimeout(timer); }
}
window.UniForm = { parseForm: ufParseForm, showError: ufShowError, loadForm: ufLoadForm };

doc.on('submit', '[data-uf-form]', function () {
  var $form = $(this);
  if ($form.data('ufSubmitting')) return false;
  var expectedConfig = $form.find('input[name="formConfig"]').val();
  var controls;
  var loader;
  function finish() {
    if (loader) loader.ladda('stop');
    (controls || []).forEach(function (item) { item.element.disabled = item.disabled; });
    $form.removeData('ufSubmitting');
    $form.removeAttr('aria-busy');
  }
  try {
    $form.ajaxSubmit({
      dataType: 'text', timeout: 30000,
      beforeSubmit: function () {
        $form.data('ufSubmitting', true).attr('aria-busy', 'true');
        $form.find('[data-uf-network-error]').remove();
        controls = $form.find('button[type="submit"], input[type="submit"]').toArray().map(function (element) {
          return { element: element, disabled: element.disabled };
        });
        controls.forEach(function (item) { item.element.disabled = true; });
        if ($.fn.ladda) { loader = $form.find('.ladda-button').ladda(); loader.ladda('start'); }
        return true;
      },
      success: function (html) {
        try {
          var responseForm = ufParseForm(html, expectedConfig);
          $form[0].replaceChildren.apply($form[0], Array.from(responseForm.childNodes));
          $form[0].dispatchEvent(new CustomEvent('uniform:updated', { bubbles: true }));
        } catch (error) {
          ufShowError($form[0], 'Не удалось получить подтверждение отправки. Данные сохранены; проверьте соединение перед повторной попыткой.');
        }
      },
      error: function () {
        ufShowError($form[0], 'Не удалось получить подтверждение отправки. Данные сохранены; проверьте соединение перед повторной попыткой.');
      },
      complete: finish
    });
  } catch (error) {
    finish();
    ufShowError($form[0], 'Не удалось начать отправку. Попробуйте ещё раз.');
  }
  return false;
});

doc.on('click', '[data-uniform]', function () {
  var data = Object.assign({}, $(this).data('uniform'), { mod: 'uniform' });
  $.magnificPopup.open({
    items: { src: dle_root + 'index.php?controller=ajax' }, focus: '.uf-input-first', type: 'ajax',
    ajax: { settings: { data: data, timeout: 30000 } },
    callbacks: { parseAjax: function (response) {
      try { response.data = ufParseForm(response.data, data.formConfig).outerHTML; }
      catch (error) { response.data = '<div class="uf-alert uf-alert-error" role="alert">Не удалось загрузить форму. Закройте окно и попробуйте ещё раз.</div>'; }
    } }
  });
  return false;
});

doc.on('keyup input', '.uf-input-error', function () {
  if ($(this).val().length) $(this).removeClass('uf-input-error');
});

jQuery(function ($) {
  $('[data-uniform-inline]').each(function () {
    var holder = this;
    var data = Object.assign({}, $(holder).data('uniformInline'), { mod: 'uniform' });
    function load() {
      if (holder.getAttribute('aria-busy') === 'true') return;
      holder.setAttribute('aria-busy', 'true');
      $.ajax({ url: dle_root + 'index.php?controller=ajax', data: data, dataType: 'text', timeout: 30000 })
        .done(function (html) {
          try { holder.replaceChildren(ufParseForm(html, data.formConfig)); }
          catch (error) { failed(); }
        }).fail(failed).always(function () { holder.removeAttribute('aria-busy'); });
    }
    function failed() {
      holder.replaceChildren();
      ufShowError(holder, 'Не удалось загрузить форму.');
      var retry = document.createElement('button');
      retry.type = 'button'; retry.className = 'uf-btn'; retry.textContent = 'Попробовать ещё раз';
      retry.addEventListener('click', load); holder.appendChild(retry);
    }
    load();
  });
});
