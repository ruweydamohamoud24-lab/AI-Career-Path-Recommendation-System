<?php
// auth.php - Login/Register processing (UPDATED)

require_once 'db_connect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$action = $_GET['action'] ?? '';

if ($action === 'register') {
    // Handle registration
    $input = json_decode(file_get_contents('php://input'), true);
    
    $fullname = trim($input['fullname'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($fullname) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Email already registered']);
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
    
    if ($stmt->execute([$fullname, $email, $hashed_password])) {
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Registration failed']);
    }
    
} elseif ($action === 'login') {
    // ============================================
    // LOGIN - ACCEPTS BOTH POST AND GET
    // ============================================
    
    // Try to get from JSON POST first
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input && isset($input['email'])) {
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
    } else {
        // Then try from GET parameters
        $email = trim($_GET['email'] ?? '');
        $password = $_GET['password'] ?? '';
    }
    
    // Debug log (optional)
    error_log("Login attempt - Email: " . $email);
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Email and password required']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, fullname, email, password, is_admin FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'fullname' => $user['fullname'],
                'email' => $user['email'],
                'is_admin' => (bool)$user['is_admin']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
    }
    
} elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    
} elseif ($action === 'check') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'fullname' => $_SESSION['fullname'],
                'email' => $_SESSION['email'],
                'is_admin' => $_SESSION['is_admin'] ?? false
            ]
        ]);
    } else {
        echo json_encode(['success' => true, 'logged_in' => false]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>