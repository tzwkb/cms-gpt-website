<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Word Document</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['username'])) {
        header('Location: index.php');
        exit();
    }
    ?>

    <div class="container">
        <h1 class="mt-5">Upload Word Document</h1>
        <form id="uploadForm" action="upload_doc.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="language">Language:</label>
                <select name="language" id="language" class="form-control" required>
                    <option value="">Select Language</option>
                    <option value="zh-CN">Chinese</option>
                    <option value="en-US">English</option>
                </select>
            </div>
            <div class="form-group">
                <label for="docx_file">Select Word File:</label>
                <input type="file" id="docx_file" name="docx_file" class="form-control-file" accept=".docx" required>
            </div>
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

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#uploadForm').submit(function(e) {
                e.preventDefault();
                var formData = new FormData(this);

                $('#progressContainer').show();  // 确保进度条容器可见

                $.ajax({
                    url: 'upload_docx.php',  // 确保与表单的 action URL 一致
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
                        $('#progressMessages').append('<p>' + response + '</p>');
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert('Error uploading file: ' + textStatus);
                    }
                });
            });
        });
    </script>
</body>
</html>


