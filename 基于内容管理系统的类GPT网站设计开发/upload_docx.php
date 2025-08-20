<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $language = $_POST['language'] ?? 'en-US'; // 默认为英语
    $docFile = $_FILES['docx_file']['tmp_name'];

    // 获取文件信息
    $fileType = pathinfo($_FILES['docx_file']['name'], PATHINFO_EXTENSION);
    $fileMimeType = $_FILES['docx_file']['type'];

    // 输出文件和上传的详细信息（调试用）
    #echo "File Name: " . $_FILES['docx_file']['name'] . "<br>";
    #echo "File Type: " . $fileType . "<br>";
    #echo "MIME Type: " . $fileMimeType . "<br>";
    #echo "File Size: " . ($_FILES['docx_file']['size'] / 1024) . " KB<br>";
    #echo "Temporary file: " . $_FILES['docx_file']['tmp_name'] . "<br>";

    // 检查语言是否合法
    if ($language !== 'zh-CN' && $language !== 'en-US') {
        die("Invalid language selection.");
    }

    // 检查文件类型和 MIME 类型
    $allowedMimeTypes = [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip', // DOCX 文件可能被视为 ZIP
        'application/octet-stream', // 通用的二进制格式
        'application/x-zip-compressed' // 另一种 ZIP 类型
    ];
    if (strtolower($fileType) !== 'docx' || !in_array($fileMimeType, $allowedMimeTypes)) {
        die("Invalid file type or MIME type. Only DOCX files are allowed.");
    }

    // 读取文件内容
    $textContents = readDocxContents($docFile);
    if ($textContents === false) {
        die("Failed to read DOCX file contents.");
    }

    $lines = explode("\n", $textContents);

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

    $conn->set_charset("utf8mb4");

    // 准备 SQL 语句
    $stmt = $conn->prepare("INSERT INTO txt_data (text, vector, language, user_id) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepared statement failed: " . $conn->error);
    }

    foreach ($lines as $index => $text) {
        if (!empty($text)) {
            $textEscaped = $conn->real_escape_string($text);
            $checkSql = "SELECT * FROM txt_data WHERE text = '$textEscaped' AND language = '$language' AND user_id = $userId";
            $result = $conn->query($checkSql);

            if ($result->num_rows > 0) {
                echo "Skipped line " . ($index + 1) . " due to duplicate content.\n";
                $embeddings[] = null;
            } else {
                $data = json_encode(['language' => $language, 'text' => $text], JSON_UNESCAPED_UNICODE);
                $url = 'http://127.0.0.1:8001/process-text/';
                $options = [
                    'http' => [
                        'header' => "Content-Type: application/json\r\n",
                        'method' => 'POST',
                        'content' => $data,
                    ]
                ];
                $context = stream_context_create($options);
                $result = @file_get_contents($url, false, $context);

                if ($result === FALSE) {
                    $error = error_get_last();
                    echo "Error occurred while processing line " . ($index + 1) . ": " . $error['message'] . "\n";
                } else {
                    $response = json_decode($result, true);
                    if (!isset($response['embedding'])) {
                        echo "Invalid response from FastAPI for line " . ($index + 1) . ": " . json_encode($response) . "\n";
                    } else {
                        $embeddings[] = $response['embedding'];
                    }
                }
            }
        } else {
            $embeddings[] = null;
            echo "Skipped line " . ($index + 1) . " due to empty text.\n";
        }
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

    echo "DOCX file processed and stored successfully.";
}

function readDocxContents($docxFile) {
    $zip = new ZipArchive;
    $documentText = '';

    if ($zip->open($docxFile) === true) {
        if (($xmlIndex = $zip->locateName('word/document.xml')) !== false) {
            $xmlContents = $zip->getFromIndex($xmlIndex);
            $zip->close();

            $doc = new DOMDocument();
            @$doc->loadXML($xmlContents);
            $xpath = new DOMXPath($doc);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $textNodes = $xpath->query('//w:t');

            foreach ($textNodes as $textNode) {
                $documentText .= $textNode->textContent . ' ';
            }

            return trim($documentText);
        } else {
            $zip->close();
            return false;
        }
    } else {
        return false;
    }
}
?>
