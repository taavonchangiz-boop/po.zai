<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطا | <?php echo htmlspecialchars($code); ?></title>
    <style>
        /* فونت Vazirmatn از فایل محلی بارگذاری می‌شود — fallback: Tahoma */
        
        body {
            font-family: Vazirmatn, Tahoma, Arial, sans-serif;
            background-color: #f3f4f6;
            color: #374151;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .container {
            text-align: center;
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
            max-width: 480px;
            width: 90%;
            border-top: 5px solid #ef4444;
        }

        h1 {
            font-size: 5rem;
            margin: 0;
            color: #ef4444;
            line-height: 1;
        }

        h2 {
            font-size: 1.5rem;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }

        p {
            font-size: 0.95rem;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-block;
            background-color: #3b82f6;
            color: #ffffff;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: bold;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($code); ?></h1>
        <h2>اوه! خطایی رخ داده است</h2>
        <p><?php echo htmlspecialchars($message ?: 'متأسفانه صفحه‌ای که به دنبال آن بودید پیدا نشد یا خطایی در سرور رخ داده است.'); ?></p>
        <a href="/" class="btn">بازگشت به صفحه اصلی 🏠</a>
    </div>
</body>
</html>
