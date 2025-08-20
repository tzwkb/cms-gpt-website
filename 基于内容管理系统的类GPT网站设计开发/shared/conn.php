<?php
$dbhost = 'localhost'; // 数据库所在主机地址
$dbuser = 'root';
$dbpass = 'root';
$dbname = 'transapi';  // 指定数据库名称

// 创建与数据库的连接
$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

// 检查连接是否成功
if (!$conn) {
    die('服务器连接失败：' . mysqli_connect_error());
}

// 设置数据库字符集为 UTF-8，确保中文数据的正确显示
if (!mysqli_set_charset($conn, "utf8")) {
    echo '错误：无法设置字符集 UTF-8 ' . mysqli_error($conn);
}
?>
