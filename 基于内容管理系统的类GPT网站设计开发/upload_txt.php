<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $language = $_POST['language'];
    $txtFile = $_FILES['txt_file']['tmp_name'];

    // 检查语言是否合法
    if ($language !== 'zh-CN' && $language !== 'en-US') {
        die("Invalid language");
    }

    // 检查文件类型是否为 TXT
    $fileType = pathinfo($_FILES['txt_file']['name'], PATHINFO_EXTENSION);
    if (strtolower($fileType) !== 'txt') {
        die("Invalid file type. Only TXT files are allowed.");
    }

    // 读取文件内容
    if (!$fileContents = file_get_contents($txtFile)) {
        die("Failed to read the file.");
    }
    $lines = explode("\n", $fileContents);

    // 初始化结果数组
    $embeddings = array();

    // 连接到 MySQL 数据库以检查重复内容
    $servername = "localhost";
    $username = "root";
    $password = "root";
    $dbname = "transapi";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // 逐行检查文本内容并发送到 FastAPI
    $url = 'http://127.0.0.1:8001/process-text/';
    $totalLines = count($lines);
    $processedLines = 0;

    foreach ($lines as $index => $text) {
        if (!empty($text)) {
            // 检查数据库中是否存在相同内容
            $textEscaped = $conn->real_escape_string($text);
            $checkSql = "SELECT * FROM txt_data WHERE text = '$textEscaped' AND language = '$language' AND user_id = $userId";
            $result = $conn->query($checkSql);

            if ($result->num_rows > 0) {
                echo "Skipped line " . ($index + 1) . " due to duplicate content.\n";
                $embeddings[] = null;
            } else {
                // 处理未重复的内容
                $data = json_encode([
                    'language' => $language,
                    'text' => $text
                ], JSON_UNESCAPED_UNICODE); // 保持数据中非ASCII字符

                $options = [
                    'http' => [
                        'header' => "Content-Type: application/json\r\n",
                        'method' => 'POST',
                        'content' => $data,
                    ],
                ];

                $context = stream_context_create($options);
                $result = @file_get_contents($url, false, $context);

                if ($result === FALSE) {
                    // 获取错误信息
                    $error = error_get_last();
                    echo "Error occurred while processing line " . ($index + 1) . ": " . $error['message'] . "\n";
                } else {
                    // 解析 FastAPI 返回的结果
                    $response = json_decode($result, true);

                    if (!isset($response['embedding'])) {
                        echo "Invalid response from FastAPI for line " . ($index + 1) . ": " . json_encode($response) . "\n";
                    } else {
                        $embeddings[] = $response['embedding'];
                        echo "Processed line " . ($index + 1) . " successfully.\n";
                    }
                }
            }
        } else {
            // 如果文本为空，则向量存储为 null
            $embeddings[] = null;
            echo "Skipped line " . ($index + 1) . " due to empty text.\n";
        }

        $processedLines++;
        $progress = ($processedLines / $totalLines) * 100;
        echo "Progress: " . round($progress, 2) . "%\n";

        // 每处理一行等待1秒
        sleep(1);
    }

    // 准备插入新数据
    $stmt = $conn->prepare("INSERT INTO txt_data (text, vector, language, user_id) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepared statement failed: " . $conn->error);
    }

    foreach ($lines as $index => $line) {
        if ($embeddings[$index] !== null) {
            $textEscaped = $conn->real_escape_string($line);
            $embedding = json_encode($embeddings[$index]);

            $stmt->bind_param('sssi', $textEscaped, $embedding, $language, $userId);
            if (!$stmt->execute()) {
                echo "Error inserting data into database for line " . ($index + 1) . ": " . $stmt->error . "\n";
            }
        }
    }

    $stmt->close();
    $conn->close();

    echo "TXT file processed and stored successfully.";
}
?>
