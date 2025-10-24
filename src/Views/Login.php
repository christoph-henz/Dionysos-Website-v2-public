<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Dionysos Restaurant</title>
    <link rel="stylesheet" href="public/assets/css/page-components.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Arial', sans-serif;
            margin: 0;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-header {
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 300;
        }

        .login-header p {
            color: #666;
            margin: 0;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .login-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .login-button:hover {
            transform: translateY(-2px);
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
            font-size: 14px;
        }

        .default-credentials {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #17a2b8;
            font-size: 13px;
            text-align: left;
        }

        .default-credentials strong {
            color: #17a2b8;
        }

        .back-to-site {
            margin-top: 20px;
        }

        .back-to-site a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .back-to-site a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🍷 Admin Login</h1>
            <p>Dionysos Restaurant</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/login">
            <div class="form-group">
                <label for="username">Benutzername:</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Passwort:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="login-button">
                Anmelden
            </button>
        </form>

        <div class="back-to-site">
            <a href="/">← Zurück zur Website</a>
        </div>
    </div>
</body>
</html>
