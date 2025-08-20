<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload TMX File</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .top-right-buttons {
            position: absolute;
            right: 10px;
            top: 10px;
        }
    </style>
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['username'])) {
        header('Location: index.php');
        exit();
    }
    $user_id = $_SESSION['user_id']; // 获取用户的 user_id
    ?>

    <div class="container">
        <div class="top-right-buttons">
            <?php if (isset($_SESSION['username'])): ?>
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="userDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        User: <span id="username"><?php echo $_SESSION['username']; ?></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="txt.php" id="uploadTxt">Upload TXT file</a>
                        <a class="dropdown-item" href="docx.php" id="uploadDocx">Upload DOCX file</a>
                        <a class="dropdown-item" href="tmx.php" id="uploadTmx">Upload TMX file</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="logout.php" id="logoutBtn">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#loginModal">Login</button>
                <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#registerModal">Register</button>
            <?php endif; ?>
            <a href="index.php" class="btn btn-info ml-2">Back to index</a>
        </div>

        <h1 class="mt-5">Upload TMX File</h1>
        <form id="uploadForm" action="upload_tmx.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="language_pair">Language Pair:</label>
                <select name="language_pair" id="language_pair" class="form-control" required>
                    <option value="">Select Language Pair</option>
                    <option value="1">Chinese to English</option>
                    <option value="2">English to Chinese</option>
                </select>
            </div>
            <div class="form-group">
                <label for="tmx_file">Select TMX File:</label>
                <input type="file" id="tmx_file" name="tmx_file" class="form-control-file" required>
            </div>
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>
        
        <div id="progressContainer" class="mt-4" style="display: none;">
            <h3>Processing Progress</h3>
            <div id="progressBar" class="progress">
                <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
            <div id="progressMessages" class="mt-3"></div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
    $('#uploadForm').submit(function(e) {
        e.preventDefault();
        var formData = new FormData(this);

        $.ajax({
            url: 'upload_tmx.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        $('#progressBar .progress-bar').css('width', percentComplete + '%').attr('aria-valuenow', percentComplete).text(percentComplete.toFixed(2) + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
    var data = JSON.parse(response);
    if (data.status === 'progress') {
        $('#progressMessages').append('<p>' + data.progress + '%</p>');
    } else if (data.status === 'success') {
        $('#progressMessages').append('<p>' + data.message + '</p>');
    }
}


        $('#progressContainer').show();
    });

    $('#logoutBtn').click(function(event) {
        event.preventDefault();
        $.ajax({
            url: 'logout.php',
            type: 'POST',
            success: function() {
                window.location.href = 'index.php';
            }
        });
    });
});

    </script>
</body>
</html>

