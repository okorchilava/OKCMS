<?php
// 403.php — დამოუკიდებელი error template
if (!headers_sent()) {
    http_response_code(403);
}
?>
<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>წვდომა აკრძალულია (403)</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;700;900&display=swap');

        :root {
            --text-color: #2c3e50;
            --accent-color: #e74c3c;
            --background-color: #ecf0f1;
        }

        body {
            margin: 0;
            padding: 0 20px;
            font-family: 'Noto Sans Georgian', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            overflow: hidden;
        }

        .error-code {
            font-size: 15vw;
            font-weight: 900;
            margin: 0;
            color: rgba(0, 0, 0, 0.05);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
            pointer-events: none;
        }

        .content {
            position: relative;
            z-index: 2;
        }

        .title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 10px;
            animation: color-pulse 4s infinite alternate;
        }

        .message {
            font-size: 1.2rem;
            margin-bottom: 30px;
            max-width: 500px;
        }

        .home-link {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            border-bottom: 2px solid transparent;
            padding-bottom: 3px;
            transition: border-color 0.3s ease;
        }

        .home-link:hover {
            border-bottom-color: var(--accent-color);
        }

        @keyframes color-pulse {
            0% { color: var(--text-color); }
            100% { color: var(--accent-color); }
        }

        @media (max-width: 768px) {
            .title { font-size: 2rem; }
            .message { font-size: 1rem; }
        }
    </style>
</head>
<body>

    <div class="error-code">403</div>
    
    <div class="content">
        <h1 class="title"><?php echo htmlspecialchars($error_title ?? 'წვდომა შეზღუდულია'); ?></h1>
        <p class="message"><?php echo htmlspecialchars($error_message ?? 'ამ გვერდის სანახავად არ გაგაჩნიათ საჭირო უფლებები.'); ?></p>
        <a href="<?php echo htmlspecialchars($error_home ?? '/'); ?>" class="home-link">მთავარ გვერდზე გადასვლა</a>
    </div>

</body>
</html>
