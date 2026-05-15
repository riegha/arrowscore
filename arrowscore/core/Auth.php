<?php
class Auth {
    public static function isLoggedIn() {
        return isset($_SESSION[SESSION_NAME]) && $_SESSION[SESSION_NAME] === true;
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/admin');
            exit();
        }
    }

    public static function login($email, $password) {
        // Rate limiting: maks 5 percobaan dalam 15 menit
        $ip = $_SERVER['REMOTE_ADDR'];
        $attemptKey = 'login_attempts_' . $ip;
        if (!isset($_SESSION[$attemptKey])) {
            $_SESSION[$attemptKey] = ['count' => 0, 'first_attempt' => time()];
        }
        $attempt = &$_SESSION[$attemptKey];
        if (time() - $attempt['first_attempt'] > 900) { // reset setiap 15 menit
            $attempt = ['count' => 0, 'first_attempt' => time()];
        }
        if ($attempt['count'] >= 5) {
            die("Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.");
        }

        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($password, $user['password_hash'])) {
                // Reset percobaan jika berhasil
                unset($_SESSION[$attemptKey]);
                $_SESSION[SESSION_NAME] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                return true;
            }
        }
        // Tambah percobaan gagal
        $attempt['count']++;
        return false;
    }

    public static function logout() {
        session_destroy();
    }

    public static function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}