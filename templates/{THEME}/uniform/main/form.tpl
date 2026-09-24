{* 
	Для вывода формы используем код:
	<div data-uniform-inline='{"formConfig": "main"}'>
		<div class="uf-inline-loading"></div>
	</div>
*}
<div class="uf-wrapper-inline">
	[debug]<div class="uf-content">{debug}</div>[/debug]
	[error]
		<div class="uf-alert uf-alert-error">
			<b>Ошибка</b>
			<ul>
				[uf_token_error]
					<li>Ошибка сессии, попробуйте ещё раз.</li>
				[/uf_token_error]
				[uf_email_error]
					<li>Проверьте правильность ввода email</li>
				[/uf_email_error]
			</ul>
		</div>
	[/error]
	[success]
		<div class="uf-alert uf-alert-success"><b>Спасибо за обращение!</b> <br> Если оно потребует ответа - мы свяжемся с вами.</div>
		<div class="contact-form">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="input-field">
							<input class="uf-input [uf_error_name]uf-input-error[/uf_error_name]" type="text" name="name" value="{uf_field_name}" placeholder="Ваше имя" required>
						</div>    
					</div>
				</div>
				<div class="col-md-12">
					<div class="row">
						<div class="col-md-6">
							<div class="input-field">
								<input class="uf-input uf-input-first [uf_error_phone]uf-input-error[/uf_error_phone]" type="text" name="phone" value="{uf_field_phone}" placeholder="Ваш телефон" required>
							</div>      
						</div>
						<div class="col-md-6">
							<div class="input-field">
								<input class="uf-input uf-input-first [uf_error_email]uf-input-error[/uf_error_email] [uf_email_error]uf-input-error[/uf_email_error]" type="text" name="email" value="" placeholder="Ваше email" required>
							</div>    
						</div>
					</div>
				</div>
				<div class="col-md-12">
					<textarea class="uf-input [uf_error_textarea]uf-input-error[/uf_error_textarea]" name="textarea" cols="10" rows="4" placeholder="Сообщение" required>{uf_field_textarea}</textarea>
				</div>
				<div class="col-md-12">
					<button class="thm-btn yellow-bg" type="submit" data-style="zoom-in">Отправить еще</button>
				</div>
			</div> 
		</div> 
	[/success]
	[form]
		<div class="contact-form">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="input-field">
							<input class="uf-input [uf_error_name]uf-input-error[/uf_error_name]" type="text" name="name" value="{uf_field_name}" placeholder="Ваше имя" required>
						</div>    
					</div>
				</div>
				<div class="col-md-12">
					<div class="row">
						<div class="col-md-6">
							<div class="input-field">
								<input class="uf-input uf-input-first [uf_error_phone]uf-input-error[/uf_error_phone]" type="text" name="phone" value="{uf_field_phone}" placeholder="Ваш телефон" required>
							</div>      
						</div>
						<div class="col-md-6">
							<div class="input-field">
								<input class="uf-input uf-input-first [uf_error_email]uf-input-error[/uf_error_email] [uf_email_error]uf-input-error[/uf_email_error]" type="text" name="email" value="" placeholder="Ваше email" required>
							</div>    
						</div>
					</div>
				</div>
				<div class="col-md-12">
					<textarea class="uf-input [uf_error_textarea]uf-input-error[/uf_error_textarea]" name="textarea" cols="10" rows="4" placeholder="Сообщение" required>{uf_field_textarea}</textarea>
				</div>
				<div class="col-md-12">
					<button class="thm-btn yellow-bg" type="submit" data-style="zoom-in">Отправить</button>
				</div>
			</div> 
		</div> 
	[/form]
</div>
