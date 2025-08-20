<?php
session_start();

// 启用错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json'); // 确保返回 JSON 格式

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => '用户未登录']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $languagePair = $_POST['language_pair'];
    $tmxFile = $_FILES['tmx_file']['tmp_name'];

    // 检查语言对是否合法
    if ($languagePair !== '1' && $languagePair !== '2') {
        echo json_encode(['status' => 'error', 'message' => '无效的语言对']);
        exit();
    }

    // 检查文件类型是否为 TMX
    $fileType = pathinfo($_FILES['tmx_file']['name'], PATHINFO_EXTENSION);
    if ($fileType !== 'tmx') {
        echo json_encode(['status' => 'error', 'message' => '无效的文件类型。只允许 TMX 文件。']);
        exit();
    }

    // 根据语言对设置源语言和目标语言
    if ($languagePair === '1') {
        $source = "zh-CN";
        $target = "en-US";
    } else {
        $source = "en-US";
        $target = "zh-CN";
    }

    // 加载 TMX 文件并提取源语言和目标语言的文本内容
    try {
        $xml = simplexml_load_file($tmxFile);
        if ($xml === false) {
            throw new Exception("加载 TMX 文件失败");
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }

    $json = json_encode($xml);
    $jsonData = json_decode($json, true);

    $sourceTexts = array();
    $targetTexts = array();

    foreach ($jsonData["body"]["tu"] as $tu) {
        $sourceTexts[] = $tu["tuv"][0]["seg"];
        $targetTexts[] = isset($tu["tuv"][1]["seg"]) ? $tu["tuv"][1]["seg"] : ""; // 检查 target_text 是否存在
    }

    // 初始化结果数组
    $sourceEmbeddings = array();
    $targetEmbeddings = array();
    $errors = array();

    // 逐行发送文本内容到 FastAPI
    $url = 'http://127.0.0.1:8001/process-tmx-line/';
    $totalLines = count($sourceTexts);
    $processedLines = 0;

    foreach ($sourceTexts as $index => $sourceText) {
        $targetText = $targetTexts[$index];

        // 如果 target_text 不为空,则发送请求获取向量
        if (!empty($targetText)) {
            $data = json_encode([
                'source_lang' => $source,
                'target_lang' => $target,
                'source_text' => $sourceText,
                'target_text' => $targetText
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
                $errors[] = "处理第 " . ($index + 1) . " 行时发生错误: " . $error['message'];
            } else {
                // 解析 FastAPI 返回的结果
                $response = json_decode($result, true);

                if (!isset($response['source_embedding']) || !isset($response['target_embedding'])) {
                    $errors[] = "FastAPI 返回无效响应，第 " . ($index + 1) . " 行: " . json_encode($response);
                } else {
                    $sourceEmbeddings[] = $response['source_embedding'];
                    $targetEmbeddings[] = $response['target_embedding'];
                }
            }
        } else {
            // 如果 target_text 为空,则向量存储为 null
            $sourceEmbeddings[] = null;
            $targetEmbeddings[] = null;
            $errors[] = "跳过第 " . ($index + 1) . " 行，因为目标文本为空。";
        }

        $processedLines++;
        $progress = ($processedLines / $totalLines) * 100;
        echo json_encode(['status' => 'progress', 'progress' => round($progress, 2)]);
        flush();
    }

    // 连接到 MySQL 数据库并将结果存储到数据库中
    $servername = "localhost";
    $username = "root";
    $password = "root";
    $dbname = "transapi";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        echo json_encode(['status' => 'error', 'message' => '连接数据库失败: ' . $conn->connect_error]);
        exit();
    }

    foreach ($sourceTexts as $i => $sourceText) {
        $targetText = $targetTexts[$i];

        // 查询是否已存在相同的记录
        $sql_check = "SELECT COUNT(*) as count FROM tmx_data WHERE source_text = '$sourceText' AND target_text = '$targetText' AND user_id = '$userId'";
        $result_check = $conn->query($sql_check);

        if ($result_check === FALSE) {
            $errors[] = "查询重复记录时发生错误: " . $conn->error;
            continue;
        }

        $row = $result_check->fetch_assoc();
        $count = $row['count'];

        if ($count > 0) {
            $errors[] = "跳过第 " . ($i + 1) . " 行，因为相同的记录已存在。";
            continue;
        }

        // 如果向量为 null,则存储为 NULL
        $sourceText = $conn->real_escape_string($sourceText);
        $targetText = $conn->real_escape_string($targetText);
        $sourceEmbedding = is_null($sourceEmbeddings[$i]) ? 'NULL' : "'" . json_encode($sourceEmbeddings[$i]) . "'";
        $targetEmbedding = is_null($targetEmbeddings[$i]) ? 'NULL' : "'" . json_encode($targetEmbeddings[$i]) . "'";

        $sql_insert = "INSERT INTO tmx_data (source_text, target_text, source_vector, target_vector, source_lang, target_lang, user_id)
                VALUES ('$sourceText', '$targetText', $sourceEmbedding, $targetEmbedding, '$source', '$target', '$userId')";

        if ($conn->query($sql_insert) === FALSE) {
            $errors[] = "插入第 " . ($i + 1) . " 行数据到数据库时发生错误: " . $conn->error;
        }
    }

    $conn->close();

    echo json_encode(['status' => 'success', 'message' => 'TMX file processed and stored successfully.', 'errors' => $errors]);
    exit();
}
?>
