<?php
// read_todos.php

// -----------------------------------------------------------------
// 1. 載入資料庫連線實例 (使用您的 database.php 檔案)
// -----------------------------------------------------------------
require_once __DIR__ . '/../connect/database.php';

// -----------------------------------------------------------------
// 2. 設置 HTTP 標頭與獲取 user_id
// -----------------------------------------------------------------
header('Content-Type: application/json; charset=utf-8');

// 假設 user_id 是透過 GET 請求傳入，例如: fetch_todos.php?user_id=123
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => '請提供有效的 user_id']);
    exit();
}

// -----------------------------------------------------------------
// 3. 執行資料庫查詢
// -----------------------------------------------------------------
try {
    // SQL 查詢語句：使用 LEFT JOIN 一次性抓取 todos 及其所有的 sub_todos
    $sql = "
        SELECT
            t.item_id AS todo_id,
            t.title AS todo_title,
            t.deadline AS todo_deadline,
            t.completed AS todo_completed,
            s.item_id AS sub_todo_id,
            s.title AS sub_todo_title,
            s.deadline AS sub_todo_deadline,
            s.completed AS sub_todo_completed
        FROM
            todos t
        LEFT JOIN
            sub_todos s ON t.item_id = s.todos_id
        WHERE
            t.user_id = :user_id
        ORDER BY
            t.item_id, s.item_id
    ";

    // $pdo 變數來自 require_once './config/database.php';
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION 會捕捉到錯誤
    http_response_code(500); // Internal Server Error
    error_log("Todo Fetch Error: " . $e->getMessage()); // 記錄錯誤到伺服器日誌
    echo json_encode(['error' => '資料庫查詢錯誤，請稍後再試。']);
    exit();
}


// -----------------------------------------------------------------
// 4. 處理與格式化資料為巢狀結構
// -----------------------------------------------------------------
$formatted_todos = [];

foreach ($results as $row) {
    $todo_id = $row['todo_id'];

    // 格式化主待辦事項 (todos)
    if (!isset($formatted_todos[$todo_id])) {
        // 將布林值字串 ('0', '1') 轉換為實際的布林值
        $is_completed = (bool)$row['todo_completed'];
        
        $formatted_todos[$todo_id] = [
            'item_id' => $todo_id,
            'title' => $row['todo_title'],
            'deadline' => $row['todo_deadline'],
            'completed' => $is_completed,
            'sub_todos' => []
        ];
    }

    // 格式化子待辦事項 (sub_todos)
    // 檢查 sub_todo_id 是否為 NULL (代表該 todo 沒有 sub_todo)
    if ($row['sub_todo_id'] !== null) {
        // 將布林值字串 ('0', '1') 轉換為實際的布林值
        $is_sub_completed = (bool)$row['sub_todo_completed'];
        
        $formatted_todos[$todo_id]['sub_todos'][] = [
            'item_id' => $row['sub_todo_id'],
            'title' => $row['sub_todo_title'],
            'deadline' => $row['sub_todo_deadline'],
            'completed' => $is_sub_completed
        ];
    }
}

// -----------------------------------------------------------------
// 5. 返回 JSON 結果
// -----------------------------------------------------------------
// 將關聯陣列的值取出 (去除數字鍵值) 並輸出 JSON
echo json_encode(
    array_values($formatted_todos), 
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT // JSON_UNESCAPED_UNICODE 確保中文不會被編碼成 \uXXXX
);