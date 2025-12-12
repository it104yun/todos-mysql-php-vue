<?php
// app/crud/dele_todos.php
// 這個app用於接收一個待辦事項的 ID (item_id)，並從 todos 資料表中將其刪除。
// 為了確保資料完整性，它應該同時刪除與該 item_id 相關聯的所有子待辦事項 (sub_todos)。


// ==========================================================
// 1. 引入資料庫連線配置
// ==========================================================
require_once __DIR__ . '/../connect/database.php'; 

// ==========================================================
// 2. 設定標頭與錯誤處理
// ==========================================================
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, OPTIONS"); // 實際操作通常用 DELETE, 但為兼容性保留 POST
header("Access-Control-Allow-Headers: Content-Type");

// 錯誤回傳函式
function sendError($message, $http_code = 400) {
    http_response_code($http_code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit();
}

// 檢查請求方法是否為 POST (或 DELETE，如果前端改為 DELETE)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('不支援的請求方法。');
}

// ==========================================================
// 3. 接收與解析前端資料
// ==========================================================
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true); 

if ($data === null) {
    sendError('無效的 JSON 資料或未提供資料。');
}

// 提取並驗證必要的欄位
$user_id = $data['user_id'] ?? null; // 也傳入 user_id 以確保只能刪除自己的資料
$delete_all = $data['delete_all'] ?? null;

if ($user_id === null || $delete_all === null) {
    sendError('缺少必要的欄位 (user_id 或 delete_all)。');
}

if ($delete_all=="YES_1jqa3z2wsx2897uiop=409+urq876"){
    // 轉換 ID 為整數，以防萬一
    $user_id = (int)$user_id;

    if ( $user_id <= 0 ) {
        sendError('提供的 ID 無效。');
    }


    // ==========================================================
    // 4. 執行 SQL 刪除操作 (使用 $pdo)
    // ==========================================================
    global $pdo;

    try {
        // 啟用事務 (Transaction) 以確保刪除 todos 
        // 要麼都成功，要麼都失敗，防止資料殘留。
        $pdo->beginTransaction();

        // --- 刪除主 todos 會自動刪除相關聯的 sub_todos -----
        $sql_main = "DELETE FROM todos WHERE user_id = :user_id";
        $stmt_main = $pdo->prepare($sql_main);
        $stmt_main->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_main->execute();
        
        // 檢查是否有記錄被刪除
        if ($stmt_main->rowCount() === 0) {
            $pdo->rollBack(); // 回滾事務
            // 如果沒有刪除，可能是 ID 不存在或 user_id 不匹配
            sendError('刪除失敗，找不到該待辦事項或您無權操作。', 404);
        }
        
        // 提交事務
        $pdo->commit();

        // ==========================================================
        // 5. 回傳成功響應給前端
        // ==========================================================
        echo json_encode([
            'status' => 'success',
            'message' => '待辦事項及其所有子項目已成功刪除。',
            'ueer_id' => $user_id
        ]);

    } catch (PDOException $e) {
        // 捕捉資料庫錯誤
        if ($pdo->inTransaction()) {
            $pdo->rollBack(); // 發生錯誤時回滾事務
        }
        error_log("Todo Delete Error: " . $e->getMessage());
        sendError("資料庫刪除失敗: " . $e->getMessage(), 500);
    }

} else {
        error_log("Todo Delete Error: " . $e->getMessage());
        sendError("There's some issues at instruction : " . $e->getMessage(), 500);
}

