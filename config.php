<?php
// メール送信設定ファイル
// このファイルで送信先メールアドレスなどの設定を変更できます

// 送信先メールアドレス（ここを変更してください）
$config = array(
    'to_email' => 'bump7107@gmail.com', // ← ここに実際のメールアドレスを入力
    'from_name' => 'エンタメ就活.com',
    'from_email' => 'info@entertainment-shukatsu.com',
    'subject_prefix' => '【エンタメ就活.com】',
    'auto_reply' => true, // 自動返信を有効にするかどうか
    'auto_reply_subject' => 'お問い合わせありがとうございます',
    'auto_reply_message' => "お問い合わせありがとうございます。\n\n以下の内容でお問い合わせを受け付けました。\n内容を確認の上、担当者よりご連絡いたします。\n\nお名前: {name}\nメールアドレス: {email}\nお問い合わせ内容:\n{message}\n\n---\nエンタメ就活.com\n"
);

// 設定を返す
return $config;
?> 