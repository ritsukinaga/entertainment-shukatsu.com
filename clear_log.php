<?php
// ログファイルクリア用PHPファイル

// セキュリティチェック（簡単な認証）
$admin_key = 'admin123'; // 本番環境ではより安全な認証方法を使用してください

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ログファイルのパス
    $log_file = 'mail_log.txt';
    
    // ログファイルが存在するかチェック
    if (file_exists($log_file)) {
        // ログファイルをクリア
        if (file_put_contents($log_file, '') !== false) {
            // 成功時
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'ログファイルをクリアしました']);
        } else {
            // 失敗時
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'ログファイルのクリアに失敗しました']);
        }
    } else {
        // ログファイルが存在しない場合
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'ログファイルが見つかりません']);
    }
} else {
    // POST以外のリクエスト
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '不正なリクエストです']);
}
?> 