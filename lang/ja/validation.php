<?php

return [
    'required' => ':attributeは必須です。',
    'string' => ':attributeは文字列で入力してください。',
    'email' => ':attributeは有効なメールアドレス形式で入力してください。',
    'unique' => ':attributeはすでに使用されています。',
    'confirmed' => ':attributeと確認用入力が一致しません。',

    'min' => ['string' => ':attributeは:min文字以上で入力してください。'],

    'max' => ['string' => ':attributeは:max文字以下で入力してください。'],

    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード確認',
    ],
];
