<?php
// login.php
// Updated: Show success message if registered=true in URL.

include 'db.php';

$success_msg = '';
$error = '';
if (isset($_GET['registered']) && $_GET['registered'] === 'true') {
    $success_msg = 'Account created successfully! Please login below.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            echo "<script>window.location.href = 'index.php';</script>";
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MapClone - Login</title>
    <style>
        /* Internal CSS: Modern, clean, Google Maps-inspired design. Sans-serif fonts, blues, subtle shadows for realism. */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Roboto', sans-serif; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); height: 100vh; display: flex; align-items: center; justify-content: center; color: #333; }
        .login-container { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); padding: 2rem; border-radius: 12px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; text-align: center; }
        h1 { color: #1a73e8; margin-bottom: 1.5rem; font-size: 1.8rem; font-weight: 400; }
        input[type="text"], input[type="password"] { width: 100%; padding: 0.75rem; margin: 0.5rem 0; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.3s ease; }
        input[type="text"]:focus, input[type="password"]:focus { outline: none; border-color: #1a73e8; box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2); }
        button { width: 100%; padding: 0.75rem; background: #1a73e8; color: white; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; transition: background 0.3s ease; margin-top: 1rem; }
        button:hover { background: #1557b0; }
        .error { color: #d93025; margin-top: 1rem; font-size: 0.9rem; }
        .success { color: #137333; margin-top: 1rem; font-size: 0.9rem; }
        .links { margin-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem; }
        .links a { color: #1a73e8; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
        @media (max-width: 480px) { .login-container { margin: 1rem; padding: 1.5rem; } }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>MapClone</h1>
        <!-- Default login: username 'admin', password 'password' -->
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success_msg): ?><div class="success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
        <div class="links">
            <a href="register.php">Register New Account</a>
            <a href="index.php">Skip Login (Guest Mode)</a>
        </div>
    </div>
</body>
</html>
