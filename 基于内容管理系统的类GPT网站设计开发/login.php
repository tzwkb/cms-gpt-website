<?php
session_start();

if (isset($_POST['login'])) {
    include 'shared/conn.php';
    mysqli_select_db($conn, 'transapi');
    mysqli_query($conn, "SET NAMES 'utf8'");

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // 输入验证
    if (empty($username) || empty($password)) {
        echo "<script>alert('用户名和密码不能为空')</script>";
    } else {
        // 使用准备好的语句以防SQL注入
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $_SESSION["user_id"] = $user['user_id']; // 从数据库获取 user_id
            $_SESSION["username"] = $username;
            header('Location: index.php');
            exit();
        } else {
            echo "<script>alert('用户名或密码无效')</script>";
        }

        $stmt->close();
    }

    $conn->close();
}
?>
