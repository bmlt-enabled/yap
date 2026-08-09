<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Yap Database Upgrade Required</title>
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
            max-width: 700px;
            width: 100%;
        }
        h1 { color: #c0392b; margin-bottom: 1rem; }
        p { color: #666; line-height: 1.6; }
        .migration-list {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        .migration-list li {
            font-family: monospace;
            margin: 0.5rem 0;
        }
        code { background: #e9ecef; padding: 0.2rem 0.4rem; border-radius: 3px; }
        a { color: #1565c0; }
    </style>
</head>
<body>
<div class="container">
    <h1>Database Upgrade Required</h1>
    <p>
        Yap has detected pending database migrations that alter primary keys or
        otherwise require operator attention. These migrations <strong>cannot</strong>
        be applied automatically from a web request.
    </p>
    <p><strong>Before proceeding, back up your database.</strong></p>
    <p>Pending destructive migrations:</p>
    <ul class="migration-list">
        <?php foreach ($migrations as $migration) : ?>
            <li><?php echo htmlspecialchars($migration); ?></li>
        <?php endforeach; ?>
    </ul>
    <p>
        <strong>What to do next:</strong> Contact your server administrator or hosting
        provider and ask them to apply the pending database migration during a
        maintenance window. Most Yap operators on shared hosting do not have shell
        access — your host's support team or whoever manages the server can run the
        migration for you.
    </p>
    <p>
        After the migration completes successfully, reload this page. If the migration
        fails, consult the <code>users_pre_uuid_backup</code> table for recovery data
        and restore from your own backup if needed.
    </p>
    <p>
        For other configuration checks (PHP extensions, Twilio, database shape), open
        <a href="/api/v1/upgrade">/api/v1/upgrade</a> once the site is reachable, or
        see the
        <a href="https://yap.bmlt.app/miscellaneous/upgrading-from-yap-4x-to-yap-5x">4.x → 5.x upgrade guide</a>.
    </p>
</div>
</body>
</html>
