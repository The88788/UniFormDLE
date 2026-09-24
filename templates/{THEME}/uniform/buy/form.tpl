{* 
	Для вывода кнопки открытия формы используем код:
	<span data-uniform='{"formConfig": "buy"}' class="uf-btn">Купить</span> 
*}
<div class="uf-wrapper">
	<span class="mfp-close">&times;</span>
	<div class="uf-header">
		Покупка товара
	</div>
	[debug]<div class="uf-content">{debug}</div>[/debug]
	[error]
		<div class="uf-alert uf-alert-error">
			<b>Ошибка</b>
			<ul>
				[uf_token_error]
					<li>Ошибка сессии, попробуйте ещё раз.</li>
				[/uf_token_error]
				[uf_error_textarea]
					<li>Вы не написали сообщение</li>
				[/uf_error_textarea]
				[uf_error_email]
					<li>Вы не указали email</li>
				[/uf_error_email]
				[uf_email_error]
					<li>Проверьте правильность ввода email</li>
				[/uf_email_error]
				[uf_error_name]
					<li>Вы не указали Имя</li>
				[/uf_error_name]
			</ul>
		</div>
	[/error]
	[success]
		<div class="uf-content"><b>Спасибо за обращение!</b> <br> Если оно потребует ответа - мы свяжемся с вами</div>
	[/success]
	[form]
		<!-- [group=1]
			<div class="uf-alert uf-alert-success">
				Сообщение для админа
			</div>
		[/group]
		[not-group=1]
			<div class="uf-alert uf-alert-success">
				Сообщение для остальных пользователей
			</div>
		[/not-group] -->
		<div class="uf-content">	
			<div class="uf-field">
				<div class="uf-label">
					Ваше имя
				</div>
				<div class="uf-field-input">
					<input class="uf-input [uf_error_name]uf-input-error[/uf_error_name]" type="text" name="name" value="{uf_field_name}">
				</div>
			</div>		
			<div class="uf-field">
				<div class="uf-label">
					Ваш email
				</div>
				<div class="uf-field-input">
					<input class="uf-input uf-input-first [uf_error_email]uf-input-error[/uf_error_email] [uf_email_error]uf-input-error[/uf_email_error]" type="text" name="email" value="">
				</div>
			</div>
			<div class="uf-field">
				<div class="uf-label">
					Ваш телефон
				</div>
				<div class="uf-field-input">
					<input class="uf-input uf-input-first [uf_error_phone]uf-input-error[/uf_error_phone]" type="text" name="phone" value="{uf_field_phone}">
				</div>
			</div>
			<div class="uf-field">
				<div class="uf-label">
					Примечание
				</div>
				<div class="uf-field-input">
					<textarea class="uf-input [uf_error_textarea]uf-input-error[/uf_error_textarea]" name="textarea" cols="10" rows="4">{uf_field_textarea}</textarea>
				</div>
			</div>
			<div class="uf-field">
				<div class="uf-label">
					&nbsp;
				</div>
				<div class="uf-field-input">
					<button class="uf-btn ladda-button" type="submit" data-style="zoom-in"><span class="ladda-label">Отправить</span></button>
				</div>
			</div>
		</div>
	[/form]
</div>
