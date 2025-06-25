<?php
// File: db_connect.php
$servername = "localhost"; // Ganti jika perlu (misal: nama host dari provider hosting)
$username = "root";        // Ganti dengan username database Anda
$password = "";            // Ganti dengan password database Anda
$dbname = "kasir";         // Ganti dengan nama database Anda

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    // In development, you can use die() for immediate feedback.
    // In production, you'd log this error and show a generic message.
    die("Koneksi gagal: " . $conn->connect_error);
}

// **HAPUS BARIS INI DARI SINI**
// header('Content-Type: application/json');
?>