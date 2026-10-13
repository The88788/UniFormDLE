<div class="uf-wrapper">
	<div class="uf-header">
		Оставить заявку на услугу
	</div>
	[debug]<div class="uf-content">{debug}</div>[/debug]
	[error]
		<div class="uf-alert uf-alert-error">
			<b>Ошибка</b>
			<ul>
				[attachments_error]
					<li>Не удалось прикрепить файлы: {not_attached_files}. Проверьте формат и размер (до 5 МБ на файл, до 10 файлов и 20 МБ всего).</li>
				[/attachments_error]
				[uf_token_error]
					<li>Ошибка сессии, попробуйте ещё раз.</li>
				[/uf_token_error]
				[uf_error_textarea]
					<li>Напишите сообщение</li>
				[/uf_error_textarea]
				[uf_error_email]
					<li>Укажите email</li>
				[/uf_error_email]
				[uf_email_error]
					<li>Проверьте правильность email</li>
				[/uf_email_error]
			</ul>
		</div>
	[/error]
	[success]
		<div class="uf-content"><b>Заявка отправлена!</b> <br> Отвечу вам в ближайшее время.</div>
	[/success]
	[form]
		<div class="uf-content">
			<div class="uf-field">
				<label class="uf-label" for="service-name">
					Ваше имя
				</label>
				<div class="uf-field-input">
					<input class="uf-input" type="text" id="service-name" name="name" autocomplete="name" value="{uf_field_name}" placeholder="Как к вам обращаться">
				</div>
			</div>
			<div class="uf-field">
				<label class="uf-label" for="service-email">
					Email <b>*</b>
				</label>
				<div class="uf-field-input">
					<input class="uf-input [uf_error_email]uf-input-error[/uf_error_email] [uf_email_error]uf-input-error[/uf_email_error]" type="email" id="service-email" name="email" autocomplete="email" aria-required="true" value="{uf_field_email}" placeholder="Для ответа на заявку">
				</div>
			</div>
			<div class="uf-field uf-field-wide">
				<label class="uf-label" for="service-site">
					Адрес сайта
				</label>
				<div class="uf-field-input">
					<input class="uf-input" type="text" id="service-site" name="site" inputmode="url" value="{uf_field_site}" placeholder="Например: https://site.ru">
				</div>
			</div>
			<div class="uf-field uf-field-wide">
				<label class="uf-label" for="service-message">
					Сообщение <b>*</b>
				</label>
				<div class="uf-field-input">
					<textarea class="uf-input [uf_error_textarea]uf-input-error[/uf_error_textarea]" id="service-message" name="textarea" aria-required="true" cols="30" rows="6" placeholder="Опишите задачу: что случилось, что нужно сделать">{uf_field_textarea}</textarea>
				</div>
			</div>
			<div class="uf-field uf-field-wide">
				<label class="uf-label" for="service-files">
					Файлы (скриншоты, pdf, doc) - необязательно
				</label>
				<div class="uf-field-input">
					<input class="uf-input" type="file" name="attachments[]" id="service-files" multiple>
				</div>
			</div>
			<div class="uf-field uf-field-wide">
				<div class="uf-label">
					&nbsp;
				</div>
				<div class="uf-field-input">
					<button class="uf-btn ladda-button" type="submit" data-style="zoom-in" data-ym-goal="service_form_submit"><span class="ladda-label">Отправить заявку</span></button>
				</div>
			</div>
		</div>
	[/form]
</div>
