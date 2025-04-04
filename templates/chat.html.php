<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>City Chatbot</title>
    <link rel="stylesheet" href="/styles.css" />
</head>
<body>
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