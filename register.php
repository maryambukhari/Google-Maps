<?php
// register.php
// Updated: Instant redirect on success to prevent user interaction issues.

include 'db.php';

$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Check if username or email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $hashed_password, $email])) {
                // Instant redirect to login on success
                echo "<script>window.location.href = 'login.php?registered=true';</script>";
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MapClone - Register</title>
    <style>
        /* Internal CSS: Similar to login, modern and clean. */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Roboto', sans-serif; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); height: 100vh; display: flex; align-items: center; justify-content: center; color: #333; }
        .register-container { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); padding: 2rem; border-radius: 12px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; text-align: center; }
        h1 { color: #1a73e8; margin-bottom: 1.5rem; font-size: 1.8rem; font-weight: 400; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 0.75rem; margin: 0.5rem 0; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.3s ease; }
        input:focus { outline: none; border-color: #1a73e8; box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2); }
        button { width: 100%; padding: 0.75rem; background: #1a73e8; color: white; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; transition: background 0.3s ease; margin-top: 1rem; }
        button:hover { background: #1557b0; }
        .error { color: #d93025; margin-top: 1rem; font-size: 0.9rem; }
        .success { color: #137333; margin-top: 1rem; font-size: 0.9rem; }
        .link { margin-top: 1rem; }
        .link a { color: #1a73e8; text-decoration: none; }
        .link a:hover { text-decoration: underline; }
        @media (max-width: 480px) { .register-container { margin: 1rem; padding: 1.5rem; } }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>MapClone - Register</h1>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            <input type="password" name="password" placeholder="Password (min 6 chars)" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit">Register</button>
        </form>
        <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <div class="link"><a href="login.php">Already have an account? Login</a></div>
    </div>
</body>
</html>
