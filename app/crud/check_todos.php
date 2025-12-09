<?php
// app/crud/check_todos.php

// ==========================================================
// 1. 引入資料庫連線配置
// ==========================================================
require_once __DIR__ . '/../connect/database.php'; 

// ==========================================================
// 2. 設定標頭與錯誤處理
// ==========================================================
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 錯誤回傳函式
function sendError($message, $http_code = 400) {
    http_response_code($http_code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit();
}

// 檢查請求方法是否為 POST
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
$user_id = $data['user_id'] ?? null;
$completed_date = $data['completed_date'] ?? null; // 這是前端傳來的日期或 '9999-12-31'

if ($item_id === null || $user_id === null) {
    sendError('缺少必要的欄位 (item_id, user_id )。');
}

// 轉換 ID 為整數
$item_id = (int)$item_id;
$user_id = (int)$user_id;
$completed_status = ($completed_date !== '' && $completed_date !== '9999-12-31') ? 1 : 0;

// ==========================================================
// 5. 執行 SQL 更新操作 (使用 $pdo)
// ==========================================================
global $pdo;

// 這裡只更新 completed 欄位。
$sql = "UPDATE todos SET completed_date = :completed_date
        WHERE item_id = :item_id AND user_id = :user_id";

try {
    $stmt = $pdo->prepare($sql);
    
    // 綁定參數
    // 將 completed_status 綁定為整數 (0 或 1)
    $stmt->bindParam(':completed_date', $completed_date, PDO::PARAM_STR);
    $stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        sendError('更新失敗，找不到該待辦事項或您無權操作。', 404);
    }

    // ==========================================================
    // 6. 回傳成功響應給前端
    // ==========================================================
    echo json_encode([
        'status' => 'success',
        'message' => '待辦事項完成狀態已更新。',
        'updated_id' => $item_id,
        'new_status' => $completed_status // 回傳新狀態供前端參考
    ]);

} catch (PDOException $e) {
    // 捕捉資料庫錯誤
    error_log("Todo Check Error: " . $e->getMessage());
    sendError("資料庫更新失敗: " . $e->getMessage(), 500);
}