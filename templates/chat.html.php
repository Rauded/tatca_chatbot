<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>City Chatbot</title>
    <link rel="stylesheet" href="/styles.css" />
</head>
<body>
    <h2>Scraped Website</h2>
    <p><a href="https://www.tatce.cz/prakticke-info/aktuality/" target="_blank">https://www.tatce.cz/prakticke-info/aktuality/</a></p>

    <h3>Scraped Pages</h3>
    <ul>
        <?php for ($i = 1; $i <= 10; $i++): ?>
            <li><a href="https://www.tatce.cz/prakticke-info/aktuality/<?php echo $i > 1 ? '?page=' . $i : ''; ?>" target="_blank">
                Page <?php echo $i; ?>
            </a></li>
        <?php endfor; ?>
    </ul>

    <h3>Scraped Articles</h3>
    <ul>
        <?php
        $dir = __DIR__ . '/../data/processed/';
        if (is_dir($dir)) {
            $files = glob($dir . '*.txt');
            foreach ($files as $file) {
                $name = basename($file);
                echo "<li>$name</li>";
            }
        } else {
            echo "<li>No articles found.</li>";
        }
        ?>
    </ul>

    <h2>City Chatbot</h2>
    <div id="chat-box" style="height:400px; overflow-y:auto; border:1px solid #ccc; padding:10px; margin-bottom:10px;">
        <!-- Chat messages will appear here -->
    </div>
    <form id="chat-form">
        <input type="text" id="user-input" placeholder="Type your message..." style="width:80%;" required />
        <button type="submit">Send</button>
    </form>
    <script src="/script.js"></script>
</body>
</html>