[header]
	[not_to_sender]
		Заявка на услугу: {service}
	[/not_to_sender]
	[to_sender]
		Копия вашей заявки: {service}
	[/to_sender]
[/header]
<h2 style="margin: 0; padding: 20px; color: #10120f; background: #c9ff3f;">
[not_to_sender]
	Новая заявка на услугу
[/not_to_sender]
[to_sender]
	Копия вашей заявки
[/to_sender]
</h2><div style="background: #fafafa; padding: 20px; color: #333333;">
	[not_to_sender]
		<b>Услуга: </b> {service} <br>
		<b>Имя: </b> {name} <br>
		<b>Email: </b> {email} <br>
		<b>Сайт: </b> {site} <br>
		<b>Сообщение: </b> <br>
	[/not_to_sender]
	[to_sender]
		<b>Услуга: </b> {service} <br>
		<b>Ваше сообщение: </b> <br>
	[/to_sender]

	{textarea}
</div>

<div style="margin-top: 16px; padding: 16px 20px; color: #555555; background: #f2f2f2; font-size: 13px; line-height: 1.5;">
<div><b>Дата отправки:</b> {send_date}</div>
[attached_files]<div><b>Вложения:</b><br>{attached_files}</div>[/attached_files]
</div>
