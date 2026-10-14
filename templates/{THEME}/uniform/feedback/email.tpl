[header]
	[not_to_sender]
		Обращение из формы обратной связи
	[/not_to_sender]
	[to_sender]
		Ваше обращение из формы обратной связи
	[/to_sender]
[/header]
<h2 style="margin: 0; padding: 20px; color: #ffffff; background: #4b9fc5;">
[not_to_sender]
	Новое сообщение из формы обратной связи
[/not_to_sender]
[to_sender]
	Копия Вашего сообщения из формы обратной связи
[/to_sender]
</h2><div style="background: #fafafa; padding: 20px; color: #333333;">
	[not_to_sender]
		<b>email: </b> {email} <br>
		<b>Текст сообщения: </b> <br>
	[/not_to_sender]
	[to_sender]
		<b>Вам отправлена копия Вашего обращения: </b> <br>
	[/to_sender]
	
	{textarea} 
</div>
<div style="margin-top: 16px; padding: 16px 20px; color: #555555; background: #f2f2f2; font-size: 13px; line-height: 1.5;">
<div><b>Дата отправки:</b> {send_date}</div>
[attached_files]<div><b>Вложения:</b><br>{attached_files}</div>[/attached_files]
</div>
