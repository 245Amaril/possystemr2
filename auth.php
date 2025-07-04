<?php
// File: auth.php
// Set header agar response berupa JSON
header('Content-Type: application/json');

// Mulai session PHP
session_start();

// Koneksi ke database dengan OOP
require 'db_connect.php';
$db = new Database();
$conn = $db->getConnection();

// Ambil aksi dari parameter GET
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        // Ambil data dari POST
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        // Default role adalah 'kasir'
        $role = $_POST['role'] ?? 'kasir';

        // Validasi input
        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Username dan password wajib diisi.']);
            exit;
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Cek apakah username sudah ada
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Username sudah terdaftar.']);
            $stmt->close();
            exit;
        }
        $stmt->close();

        // Simpan user baru
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $role);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Registrasi berhasil.']);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        break;

    case 'login':
        // Ambil data dari POST
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // Validasi input
        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Username dan password wajib diisi.']);
            exit;
        }

        // Ambil data user dari database
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Verifikasi password
        if ($user && password_verify($password, $user['password'])) {
            // Set session user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            echo json_encode([
                'success' => true,
                'message' => 'Login berhasil.',
                'username' => $user['username'],
                'role' => $user['role']
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Username atau password salah.']);
        }
        break;

    case 'logout':
        // Hapus semua session
        $_SESSION = array();
        // Hapus cookie session jika ada
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        // Hancurkan session
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logout berhasil.']);
        break;

    case 'get_user_info':
        // Cek apakah user sudah login
        if (isset($_SESSION['username'])) {
            echo json_encode([
                'success' => true,
                'username' => $_SESSION['username'],
                'role' => $_SESSION['user_role']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'username' => null,
                'role' => null
            ]);
        }
        break;

    // Fungsionalitas Reset Password yang Baru
    case 'reset_password':
        // Ambil data dari POST
        $username = $_POST['username'] ?? '';
        $new_password = $_POST['new_password'] ?? '';

        // Validasi input
        if (empty($username) || empty($new_password)) {
            echo json_encode(['success' => false, 'error' => 'Username dan password baru wajib diisi.']);
            exit;
        }
        
        // Cek apakah username ada
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if ($stmt_check->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Username tidak ditemukan.']);
            $stmt_check->close();
            exit;
        }
        $stmt_check->close();
        
        // Hash password baru
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password di database
        $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt_update->bind_param("ss", $hashed_password, $username);

        if ($stmt_update->execute()) {
            echo json_encode(['success' => true, 'message' => 'Password berhasil direset.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Gagal mereset password: ' . $stmt_update->error]);
        }
        $stmt_update->close();
        break;


    default:
        // Aksi tidak valid
        echo json_encode(['success' => false, 'error' => 'Aksi tidak valid']);
        break;
}

// Tutup koneksi database
$conn->close();
?>