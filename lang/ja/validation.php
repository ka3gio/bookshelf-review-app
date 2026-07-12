<?php

return [
	'custom' => [
		'email' => [
			'required' => 'メールアドレスを入力してください',
			'email' => 'メールアドレスはメール形式で入力してください',
			'unique' => 'このメールアドレスは使えません',
		],
		'password' => [
			'required' => 'パスワードを入力してください',
		],
	],

	'attributes' => [
		'email' => 'メールアドレス',
		'password' => 'パスワード',
		'name' => 'お名前',
	]
];