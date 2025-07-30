function renderErrorPage($message) {
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Error</title>
        <style>
            body {
                font-family: "Arial", sans-serif;
                background-color: #f9f9f9;
                color: #c00;
                text-align: center;
                direction: ltr;
                padding: 100px 20px;
            }
            .error-box {
                display: inline-block;
                background: #ffeaea;
                border: 2px solid #f99;
                padding: 20px 40px;
                border-radius: 10px;
                font-size: 20px;
            }
            a {
                color: blue;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>⚠️ Data Error</h1>
            <p>' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>
            <br>
            <p><a href="javascript:history.back();">🔙 Go Back</a></p>
        </div>
    </body>
    </html>';
    exit();
}