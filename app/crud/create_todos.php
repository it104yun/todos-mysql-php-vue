<?php
// app/crud/create_todos.php

// ==========================================================
// 1. 引入資料庫連線配置
// ==========================================================

// 使用 __DIR__ 取得當前目錄 (app/crud/)，然後向上兩層到 app/ 
// 然後再進入 connect/ 目錄找到 database.php
require_once __DIR__ . '/../connect/database.php'; 
// 引入後，可以直接使用 database.php 中已經建立好的 $pdo 物件。


// ==========================================================
// 2. 設定標頭與錯誤處理
// ==========================================================

// 設定響應標頭為 JSON 格式
header('Content-Type: application/json; charset=utf-8');
// 允許來自任何來源的跨域請求 (CORS)
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 錯誤回傳函式
function sendError($message) {
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

// 讀取前端發送的原始 JSON 資料
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true); // 將 JSON 解析成 PHP 關聯陣列

// 檢查資料是否解析成功
if ($data === null) {
    sendError('無效的 JSON 資料或未提供資料。');
}

// 提取並驗證必要的欄位
$title = $data['title'] ?? null;
$deadline = $data['deadline'] ?? "9999-12-31"; // 預設為遠未來日期
$user_id = $data['user_id'] ?? null;

// 基本資料驗證
if (empty($title) || $user_id === null) {
    sendError('缺少必要的欄位 (title 或 user_id)。');
}


// ==========================================================
// 4. 執行 SQL 插入操作 (使用 $pdo)
// ==========================================================

global $pdo; // 確保我們能夠存取 database.php 中建立的 $pdo 變數

$sql = "INSERT INTO todos (title, deadline, user_id) 
        VALUES (:title, :deadline, :user_id)";

try {
    $stmt = $pdo->prepare($sql);
    
    // 綁定參數
    $stmt->bindParam(':title', $title);
    // 將 completed 轉換為整數 (0 或 1)
    $stmt->bindParam(':deadline', $db_deadline, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    // 執行語句
    $stmt->execute();
    
    // 取得資料庫自動生成的新 ID
    $new_db_id = $pdo->lastInsertId();

    // ==========================================================
    // 5. 回傳成功響應給前端
    // ==========================================================
    echo json_encode([
        'status' => 'success',
        'message' => '待辦事項新增成功。',
        'new_db_id' => $new_db_id // 回傳新 ID 給前端
    ]);

} catch (PDOException $e) {
    // 插入資料庫失敗
    sendError("資料庫插入失敗: " . $e->getMessage());
}

?>