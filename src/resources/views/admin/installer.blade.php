<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Yap Installer</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
        }
        h1 { color: #333; margin-bottom: 1rem; }
        p { color: #666; line-height: 1.6; }
        .settings-list {
            background: #f9f9f9;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        .settings-list li {
            font-family: monospace;
            margin: 0.5rem 0;
        }
        a { color: #007bff; }
        code { background: #e9ecef; padding: 0.2rem 0.4rem; border-radius: 3px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Yap Installer</h1>
    <p>Welcome to Yap. To complete the installation, you need to create a <code>config.php</code> file in the root of your yap folder.</p>
    <p>The following settings are required:</p>
    <ul class="settings-list">
        <?php foreach ($minimalRequiredSettings as $setting) : ?>
            <li><?php echo htmlspecialchars($setting); ?></li>
        <?php endforeach; ?>
    </ul>
    <p>Please refer to the <a href="https://bmlt.app/yap" target="_blank">documentation</a> for detailed configuration instructions.</p>
</div>
</body>
</html>
