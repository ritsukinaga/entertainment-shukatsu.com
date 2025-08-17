<?php
// 文字エンコーディング設定
mb_internal_encoding('UTF-8');
mb_language('ja');

// 設定ファイルの読み込み
$config = include 'config.php';

// POSTデータの取得とサニタイズ
$name = isset($_POST['name']) ? htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8') : '';
$email = isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '';
$message = isset($_POST['message']) ? htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8') : '';

// 必須項目のチェック
if (empty($name) || empty($email) || empty($message)) {
    $response = array('success' => false, 'message' => '必須項目を入力してください。');
    echo json_encode($response);
    exit;
}

// メールアドレスの形式チェック
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response = array('success' => false, 'message' => '正しいメールアドレスを入力してください。');
    echo json_encode($response);
    exit;
}

// 送信先メールアドレス（設定ファイルから取得）
$to = $config['to_email'];

// 件名
$subject = $config['subject_prefix'] . 'お問い合わせフォームより';

// メール本文
$body = "以下の内容でお問い合わせがありました。\n\n";
$body .= "お名前: " . $name . "\n";
$body .= "メールアドレス: " . $email . "\n";
$body .= "お問い合わせ内容:\n" . $message . "\n\n";
$body .= "---\n";
$body .= $config['from_name'] . "\n";
$body .= "送信日時: " . date('Y年m月d日 H:i:s') . "\n";

// ヘッダー設定
$headers = "From: " . $config['from_name'] . " <" . $config['from_email'] . ">\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// メール送信
$mail_sent = mb_send_mail($to, $subject, $body, $headers);

// 自動返信の送信
$auto_reply_sent = false;
if ($config['auto_reply'] && $mail_sent) {
    $auto_reply_subject = $config['auto_reply_subject'];
    $auto_reply_body = str_replace(
        array('{name}', '{email}', '{message}'),
        array($name, $email, $message),
        $config['auto_reply_message']
    );
    
    $auto_reply_headers = "From: " . $config['from_name'] . " <" . $config['from_email'] . ">\r\n";
    $auto_reply_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $auto_reply_headers .= "X-Mailer: PHP/" . phpversion();
    
    $auto_reply_sent = mb_send_mail($email, $auto_reply_subject, $auto_reply_body, $auto_reply_headers);
}

if ($mail_sent) {
    // 送信成功
    $response = array(
        'success' => true, 
        'message' => 'お問い合わせを送信しました。ありがとうございます。' . 
                    ($auto_reply_sent ? '確認メールをお送りしました。' : '')
    );
} else {
    // 送信失敗
    $response = array('success' => false, 'message' => 'メールの送信に失敗しました。しばらく時間をおいて再度お試しください。');
}

// JSONレスポンスを返す
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
?> 