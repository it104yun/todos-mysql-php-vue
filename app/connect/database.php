<?php
// connect/database.php

// ---------------------------------------------
// 1. 載入 .env 檔案中的機密資料
// ---------------------------------------------
require_once __DIR__ . '/dotenv_loader.php';

// ---------------------------------------------
// 2. 從環境變數中讀取設定
// ---------------------------------------------
// 使用 getenv() 來獲取環境變數，這是最安全和標準的方式
$db_host = getenv('DB_HOST');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_port = getenv('DB_PORT') ?: 3306; // 如果 .env 沒有設定 PORT，預設為 3306


// ---------------------------------------------
// 3. 建立資料庫連線
// ---------------------------------------------
try {
    $pdo = new PDO(
        "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // 處理連線失敗
    die("資料庫連線失敗，請檢查 .env 檔案中的 DB 設定和您的 MySQL 服務是否啟動: " . $e->getMessage());
}