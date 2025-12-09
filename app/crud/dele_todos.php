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
$item_id = $data['item_id'] ?? null;
$user_id = $data['user_id'] ?? null; // 也傳入 user_id 以確保只能刪除自己的資料

if ($item_id === null || $user_id === null) {
    sendError('缺少必要的欄位 (item_id 或 user_id)。');
}

// 轉換 ID 為整數，以防萬一
$item_id = (int)$item_id;
$user_id = (int)$user_id;

if ($item_id <= 0 || $user_id <= 0) {
    sendError('提供的 ID 無效。');
}


// ==========================================================
// 4. 執行 SQL 刪除操作 (使用 $pdo)
// ==========================================================
global $pdo;

try {
    // 啟用事務 (Transaction) 以確保刪除 todos 和 sub_todos 是原子性操作
    // 要麼都成功，要麼都失敗，防止資料殘留。
    $pdo->beginTransaction();

    // --- 刪除相關聯的 sub_todos ---
    $sql_sub = "DELETE FROM sub_todos WHERE todos_id = :item_id";
    $stmt_sub = $pdo->prepare($sql_sub);
    $stmt_sub->bindParam(':item_id', $item_id, PDO::PARAM_INT);
    $stmt_sub->execute();

    // --- 刪除主 todos ---
    $sql_main = "DELETE FROM todos WHERE item_id = :item_id AND user_id = :user_id";
    $stmt_main = $pdo->prepare($sql_main);
    $stmt_main->bindParam(':item_id', $item_id, PDO::PARAM_INT);
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
        'deleted_id' => $item_id
    ]);

} catch (PDOException $e) {
    // 捕捉資料庫錯誤
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // 發生錯誤時回滾事務
    }
    error_log("Todo Delete Error: " . $e->getMessage());
    sendError("資料庫刪除失敗: " . $e->getMessage(), 500);
}