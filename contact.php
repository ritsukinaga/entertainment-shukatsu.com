<?php
// エラーハンドリングの設定
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 文字エンコーディング設定
mb_internal_encoding('UTF-8');
mb_language('ja');

// mb_send_mail_encoding関数が存在するかチェック
if (function_exists('mb_send_mail_encoding')) {
    mb_send_mail_encoding('UTF-8');
}

// 設定ファイルの読み込み
try {
    $config = include 'config.php';
    if (!is_array($config)) {
        throw new Exception('設定ファイルの読み込みに失敗しました');
    }
} catch (Exception $e) {
    error_log("設定ファイル読み込みエラー: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '設定エラーが発生しました']);
    exit;
}

// ログファイルの設定
$log_file = 'mail_log.txt';

// ログ記録関数
function writeLog($message) {
    global $log_file;
    try {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] {$message}\n";
        
        if (file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX) === false) {
            error_log("ログファイル書き込み失敗: {$log_file}");
        }
    } catch (Exception $e) {
        error_log("ログ記録エラー: " . $e->getMessage());
    }
}

// POSTデータの取得とサニタイズ
try {
    $name = isset($_POST['name']) ? htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8') : '';
    $email = isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '';
    $message = isset($_POST['message']) ? htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8') : '';
    
    // ログ開始
    writeLog("お問い合わせフォーム送信開始 - 名前: {$name}, メール: {$email}");
} catch (Exception $e) {
    error_log("POSTデータ処理エラー: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'データ処理エラーが発生しました']);
    exit;
}

// 必須項目のチェック
if (empty($name) || empty($email) || empty($message)) {
    $error_msg = '必須項目を入力してください。';
    writeLog("バリデーションエラー: {$error_msg}");
    $response = array('success' => false, 'message' => $error_msg);
    echo json_encode($response);
    exit;
}

// メールアドレスの形式チェック
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_msg = '正しいメールアドレスを入力してください。';
    writeLog("バリデーションエラー: {$error_msg} - メールアドレス: {$email}");
    $response = array('success' => false, 'message' => $error_msg);
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

// 文字エンコーディングの確認（UTF-8として扱う）
$body = mb_convert_encoding($body, 'UTF-8', 'UTF-8');

// ヘッダー設定
$from_name_encoded = mb_encode_mimeheader($config['from_name'], 'UTF-8', 'B');
if ($from_name_encoded === false) {
    $from_name_encoded = $config['from_name'];
}

$headers = "From: " . $from_name_encoded . " <" . $config['from_email'] . ">\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// メール送信前のログ
writeLog("メール送信開始 - 宛先: {$to}, 件名: {$subject}");

// 件名（MIMEエンコーディングなしで直接使用）
$encoded_subject = $subject;

// メール送信
try {
    $mail_sent = mb_send_mail($to, $encoded_subject, $body, $headers);
    
    // メール送信結果のログ
    if ($mail_sent) {
        writeLog("メール送信成功 - 宛先: {$to}");
    } else {
        $last_error = error_get_last();
        $error_message = $last_error ? $last_error['message'] : '不明なエラー';
        writeLog("メール送信失敗 - 宛先: {$to}, エラー: {$error_message}");
    }
} catch (Exception $e) {
    error_log("メール送信例外: " . $e->getMessage());
    writeLog("メール送信例外 - 宛先: {$to}, エラー: " . $e->getMessage());
    $mail_sent = false;
}

// 自動返信の送信
$auto_reply_sent = false;
if ($config['auto_reply'] && $mail_sent) {
    writeLog("自動返信送信開始 - 宛先: {$email}");
    
    $auto_reply_subject = $config['auto_reply_subject'];
    $auto_reply_body = str_replace(
        array('{name}', '{email}', '{message}'),
        array($name, $email, $message),
        $config['auto_reply_message']
    );
    
    // 自動返信本文の文字エンコーディング確認（UTF-8として扱う）
    $auto_reply_body = mb_convert_encoding($auto_reply_body, 'UTF-8', 'UTF-8');
    
    // 自動返信の件名（MIMEエンコーディングなしで直接使用）
    $encoded_auto_reply_subject = $auto_reply_subject;
    
    $auto_reply_headers = "From: " . $from_name_encoded . " <" . $config['from_email'] . ">\r\n";
    $auto_reply_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $auto_reply_headers .= "Content-Transfer-Encoding: 8bit\r\n";
    $auto_reply_headers .= "X-Mailer: PHP/" . phpversion();
    
    $auto_reply_sent = mb_send_mail($email, $encoded_auto_reply_subject, $auto_reply_body, $auto_reply_headers);
    
    if ($auto_reply_sent) {
        writeLog("自動返信送信成功 - 宛先: {$email}");
    } else {
        writeLog("自動返信送信失敗 - 宛先: {$email}, エラー: " . error_get_last()['message']);
    }
}

// 最終結果のログ
if ($mail_sent) {
    $success_msg = 'お問い合わせを送信しました。ありがとうございます。' . 
                    ($auto_reply_sent ? '確認メールをお送りしました。' : '');
    writeLog("処理完了 - 成功: {$success_msg}");
    
    $response = array(
        'success' => true, 
        'message' => $success_msg
    );
} else {
    $error_msg = 'メールの送信に失敗しました。しばらく時間をおいて再度お試しください。';
    writeLog("処理完了 - 失敗: {$error_msg}");
    
    $response = array('success' => false, 'message' => $error_msg);
}

// JSONレスポンスを返す
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
?> 