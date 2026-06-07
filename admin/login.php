<?php
/**
 * Rai by Rai - Admin Login
 */

session_start();
require_once '../config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error_msg = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error_msg = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error_msg = 'Database error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rai by Rai - Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0f0f0d;
            --cream: #f5f2ed;
            --warm-white: #faf9f6;
            --gold: #b89a5e;
            --border: #e2ddd6;
            --muted: #8a8478;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, var(--warm-white) 0%, var(--cream) 100%);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            width: 100%;
            max-width: 420px;
            padding: 3.5rem 2.5rem;
            box-shadow: 0 20px 50px rgba(15, 15, 13, 0.06);
            text-align: center;
        }

        .brand {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.2rem;
            font-weight: 400;
            margin-bottom: 0.5rem;
            color: var(--ink);
        }

        .brand span {
            color: var(--gold);
        }

        .subtitle {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--muted);
            margin-bottom: 2.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid var(--border);
            background: var(--warm-white);
            color: var(--ink);
            font-family: inherit;
            font-size: 0.9rem;
            border-radius: 6px;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--gold);
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: var(--ink);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 0.75rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
            transition: opacity 0.2s;
            font-weight: 500;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            opacity: 0.9;
        }

        .error-box {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
            padding: 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">Rai <span>by</span> Rai</div>
        <div class="subtitle">Advisory Dashboard</div>
        
        <?php if (!empty($error_msg)): ?>
            <div class="error-box"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            
            <button type="submit" class="btn-submit">Log In</button>
        </form>
    </div>
</body>
</html>
