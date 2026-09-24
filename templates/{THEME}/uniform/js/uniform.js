/*!
 * DLE UniForm - унверсальные формы для DLE
 *
 * @author     pafnuty <pafnuty10@gmail.com>
 * @link       http://pafnuty.name/
 * @link       https://twitter.com/pafnuty_name
 * @adapted    Adapted for DLE 21 / PHP 8.x by The88788.com (2026)
 */

var doc = $(document);

var laddaLoad;

doc
	// ajax-отправка формы + эффекты
	.on('submit', '[data-uf-form]', function () {
		var $this   = $(this);
		var options = {
			beforeSubmit: ufStart,
			success: ufDone,
		};

		$this.ajaxSubmit(options);

		return false;
	})
	// Открытие ajax-окна с формой
	// DLE 21: точка входа ajax - index.php?controller=ajax
		.on('click', '[data-uniform]', function () {
		var $this = $(this);
		var data  = $this.data('uniform');
		data.mod = 'uniform';

		$.magnificPopup.open({
			items: {
				src: dle_root + 'index.php?controller=ajax',
			},
			focus: '.uf-input-first',
			type: 'ajax',
			ajax: {
				settings: {
					data: data
				}
			}
		});
		return false;
	})
	// Убираем класс с из инпута с ошибочным заполнением
	.on('keyup input', '.uf-input-error', function (e) {
		var $this = $(this);
		if (e.type === 'input') {
			doc.off('keyup', '.uf-input-error');
		}
		if ($this.val().length) {
			$this.removeClass('uf-input-error');
		}
	});

// Функция, выполняемая перед отправкой формы
function ufStart(formData, jqForm) {
	laddaLoad = jqForm.find('.ladda-button').ladda();
	laddaLoad.ladda('start');

	return true;
}

// Функция, выполняемая после удачной отправки формы
function ufDone(responseText, statusText, xhr, $form) {

	var $responseText  = $(responseText);
	var responseResult = ($responseText.is('form')) ? $responseText.html() : responseText;

	if (statusText === 'success') {
		laddaLoad.ladda('stop');
		$form.html(responseResult);
	}
}

jQuery(document).ready(function ($) {
	var $inlineUniform = $('[data-uniform-inline]');

	if ($inlineUniform.length) {
		$.each($inlineUniform, function () {
			var $this = $(this);
			var data  = $this.data('uniformInline');
			data.mod = 'uniform';

			$.ajax({
				url: dle_root + 'index.php?controller=ajax',
				data: data,
			})
				.done(function (data) {
					$this.html(data);
				});

		});
	}
});
