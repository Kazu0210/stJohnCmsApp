<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is authenticated
 * @return bool
 */
function isAuthenticated() {
    return isset($_SESSION['is_authenticated']) && 
           $_SESSION['is_authenticated'] === true && 
           isset($_SESSION['user_id']) && 
           !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return isAuthenticated() ? $_SESSION['user_id'] : null;
}

/**
 * Get current user role
 * @return string|null
 */
function getCurrentUserRole() {
    return isAuthenticated() ? $_SESSION['role'] : null;
}

/**
 * Get current user full name
 * @return string|null
 */
function getCurrentUserName() {
    return isAuthenticated() ? $_SESSION['name'] : null;
}

/**
 * Check if user has specific role
 * @param string $role
 * @return bool
 */
function hasRole($role) {
    return isAuthenticated() && $_SESSION['role'] === $role;
}

/**
 * Check if user is admin or secretary
 * @return bool
 */
function isAdminOrSecretary() {
    return hasRole('Admin') || hasRole('Secretary');
}

/**
 * Check if user is client
 * @return bool
 */
function isClient() {
    return hasRole('Client');
}

/**
 * Require authentication - redirect to login if not authenticated
 * @param string $loginUrl
 */
function requireAuth($loginUrl = '../auth/login/login.php') {
    if (!isAuthenticated()) {
        header("Location: $loginUrl");
        exit;
    }
}

/**
 * Require specific role - redirect if user doesn't have required role
 * @param string $requiredRole
 * @param string $accessDeniedUrl
 */
function requireRole($requiredRole, $accessDeniedUrl = '../auth/login/login.php') {
    if (!hasRole($requiredRole)) {
        header("Location: $accessDeniedUrl");
        exit;
    }
}

/**
 * Require admin or secretary role
 * @param string $accessDeniedUrl
 */
function requireAdminOrSecretary($accessDeniedUrl = '../auth/login/login.php') {
    if (!isAdminOrSecretary()) {
        header("Location: $accessDeniedUrl");
        exit;
    }
}

/**
 * Logout user and destroy session
 * @param string $redirectUrl
 */
function logout($redirectUrl = '../auth/login/login.php') {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login
    header("Location: $redirectUrl");
    exit;
}

/**
 * Check session timeout (optional - set timeout in seconds)
 * @param int $timeout Default 24 hours
 * @return bool
 */
function checkSessionTimeout($timeout = 86400) {
    if (isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > $timeout) {
            logout();
            return false;
        }
    }
    return true;
}

function log_audit($user_id, $action, $details = null) {
    require_once 'db_connect.php'; // adjust path as needed
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}
?>