<?php
// connect/dotenv_loader.php

/**
 * 載入並解析 .env 檔案
 * 將變數設定為 $_ENV 和 getenv() 內
 * @param string $path .env 檔案的路徑
 */
function loadDotEnv(string $path)
{
    // echo "<div>嘗試載入的 .env 檔案路徑是: <strong>" . $path . "</strong></div>";
    // die("請檢查路徑是否正確。");
    // 檢查 .env 檔案是否存在
    if (!file_exists($path)) {
        die("錯誤：找不到 .env 檔案於 $path，無法載入資料庫設定。");
    }

    // -----------------------------------------------------------------
    // 【修正後的讀取邏輯】: 使用 file_get_contents 並手動分隔行，避免使用舊版本不支援的常數
    // -----------------------------------------------------------------
    $content = file_get_contents($path);
    if ($content === false) {
        die("錯誤：無法讀取 .env 檔案內容。");
    }
    
    // 按行分隔
    $lines = explode("\n", $content); 
    foreach ($lines as $line) {
        // 清理行首尾空白
        $line = trim($line);
        
        // 忽略空行和註解行 (# 開頭)
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        // 確保行內包含 '='
        if (strpos($line, '=') === false) {
            continue;
        }
        
        // 解析 KEY=VALUE 格式
        list($key, $value) = explode('=', $line, 2);
        
        $key = trim($key);
        $value = trim($value);

        // 移除引號 (如果有的話)
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        }

        // 將變數設定到環境中
        if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
            putenv(sprintf('%s=%s', $key, $value));
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// 執行載入 .env 檔案
loadDotEnv(__DIR__ .'\.env');