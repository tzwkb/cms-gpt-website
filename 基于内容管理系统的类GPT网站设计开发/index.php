<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>你好</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #messages {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        .message {
            padding: 10px;
            margin-bottom: 10px;
        }
        .user-message {
            text-align: right;
            background-color: #d1ecf1;
        }
        .api-message {
            text-align: left;
            background-color: #f8d7da;
        }
        .top-right-buttons {
            position: absolute;
            right: 10px;
            top: 10px;
        }
        .dropdown-menu a {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php
    session_start();
    $user_id = $_SESSION['user_id'];
    ?>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">Login</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="login.php" method="post">
                        <div class="form-group">
                            <label for="username">Username：</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password：</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary">登录</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registerModalLabel">Register</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="register.php" method="post">
                        <div class="form-group">
                            <label for="username">Username：</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="email">E-mail：</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password：</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <button type="submit" name="register" class="btn btn-primary">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
        </div>
        <h1 class="mt-5">Get Answer Service</h1>
        <div id="messages" class="border rounded p-3"></div>
        <form id="translation-form">
            <div class="form-group">
                <label for="text">Your Question:</label>
                <input type="text" id="text" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Get Answer</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
    $(document).ready(function(){
        $('#translation-form').submit(function(event){
            event.preventDefault();
            var text = $('#text').val();
            // Append user message
            $('#messages').append('<div class="message user-message">' + text + '</div>');
            $.ajax({
                url: 'find_similar_sentences.php',
                type: 'POST',
                data: { text: text },
                success: function(response) {
                    try {
                        //console.log(response)
                        var data = JSON.parse(response);

                       console.log(data.similar_sentences)
                        if (data.assistant_message) {
                            $('#messages').append('<div class="message api-message">' + data.assistant_message + '</div>');
                            if (data.similar_sentences[0]) {
                                $('#messages').append('<div class="message api-message">参考来源：' + data.similar_sentences[0].source_text + '</div>');
                            }
                        } else {
                            $('#messages').append('<div class="message api-message">No result text found.</div>');
                        }
                    } catch (e) {
                        $('#messages').append('<div class="message api-message">Error parsing server response.</div>');
                    }
                },
                error: function() {
                    $('#messages').append('<div class="message api-message">Error occurred while translating.</div>');
                }
            });
            // Clear the input fields
            $('#text').val('');
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