<?php
// app/crud/update_todos.php
// 這個app用於接收待辦事項的 ID (item_id)、新標題 (title)、新截止日期 (deadline) 和新完成狀態 (completed)，
// 並更新 todos 資料表中的對應記錄。

// ==========================================================
// 1. 引入資料庫連線配置
// ==========================================================
require_once __DIR__ . '/../connect/database.php'; 

// ==========================================================
// 2. 設定標頭與錯誤處理
// ==========================================================
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, OPTIONS"); // 實際操作通常用 PUT/PATCH, 但為兼容性保留 POST
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

// 提取並驗證必要的欄位 (至少需要 item_id)
$todos_id = $data['todos_id'] ?? null; // 用於驗證權限
$item_id = $data['item_id'] ?? null;

// 可選的更新欄位
$title = $data['title'] ?? null;
$deadline = $data['deadline'] ?? null;
// completed 可能是布林值，這裡檢查是否存在
$completed_exists = isset($data['completed']);
$completed = $completed_exists ? (bool)$data['completed'] : null;

// 基本資料驗證
if ($todos_id === null || $item_id === null) {
    sendError('缺少必要的欄位 (todos_id 或 item_id)。');
}

if ($title === null && $deadline === null && !$completed_exists) {
    sendError('至少需要提供 title, deadline 或 completed 中的一個來進行更新。');
}

// 轉換 ID 為整數
$todos_id = (int)$todos_id;
$item_id = (int)$item_id;


// ==========================================================
// 4. 動態構建 SQL 更新語句 (使用 $pdo)
// ==========================================================
global $pdo;

$set_clauses = [];
$bind_params = [];
$bind_params[':todos_id'] = $todos_id;
$bind_params[':item_id'] = $item_id;

if ($title !== null) {
    $set_clauses[] = "title = :title";
    $bind_params[':title'] = $title;
}
if ($deadline !== null) {
    $set_clauses[] = "deadline = :deadline";
    $bind_params[':deadline'] = $deadline;
}
if ($completed_exists) {
    $set_clauses[] = "completed = :completed";
    // PDO::PARAM_INT 將布林值轉換為 0 或 1
    $bind_params[':completed'] = (int)$completed; 
}

if (empty($set_clauses)) {
     sendError('沒有可更新的欄位。'); // 再次檢查，避免空更新
}

$sql = "UPDATE sub_todos SET " . implode(', ', $set_clauses) . " WHERE todos_id = :todos_id AND item_id = :item_id";

try {
    $stmt = $pdo->prepare($sql);
    
    // 綁定參數
    foreach ($bind_params as $param => $value) {
        // 特殊處理 completed 參數的類型
        if ($param === ':completed') {
            $stmt->bindValue($param, $value, PDO::PARAM_INT);
        } elseif ($param === ':todos_id' || $param === ':item_id') {
            $stmt->bindValue($param, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($param, $value);
        }
    }

    // 執行語句
    $stmt->execute();
    
    // 檢查是否有記錄被影響 (更新)
    if ($stmt->rowCount() === 0) {
        // 如果沒有記錄被更新，可能 ID 不存在，或者資料沒有任何改變
        // 為了不讓前端誤以為成功，這裡返回一個提示
        $msg = "更新成功 (但沒有任何欄位被變更)。";
        // 也可以選擇返回 404 錯誤，這取決於業務邏輯
    } else {
        $msg = '待辦事項更新成功。';
    }


    // ==========================================================
    // 5. 回傳成功響應給前端
    // ==========================================================
    echo json_encode([
        'status' => 'success',
        'message' => $msg,
        'updated_id' => $item_id
    ]);

} catch (PDOException $e) {
    // 捕捉資料庫錯誤
    error_log("Todo Update Error: " . $e->getMessage());
    sendError("資料庫更新失敗: " . $e->getMessage(), 500);
}