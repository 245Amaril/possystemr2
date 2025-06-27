<?php
// File: db_connect.php
// Konfigurasi koneksi database
$servername = "localhost"; // Nama host database (biasanya 'localhost')
$username = "root";        // Username database
$password = "";            // Password database
$dbname = "kasir";         // Nama database

// Membuat koneksi ke database menggunakan MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi dan tampilkan pesan error yang mudah dipahami di konsol browser
if ($conn->connect_error) {
    // Kirim error ke konsol browser menggunakan JavaScript
    echo "<script>console.error('Koneksi ke database gagal: ".addslashes($conn->connect_error)."');</script>";
    // Hentikan eksekusi PHP dan tampilkan pesan error di halaman (hanya untuk development)
    die("Koneksi ke database gagal. Silakan cek konfigurasi koneksi pada file db_connect.php");
}

// Koneksi berhasil, variabel $conn siap digunakan untuk query
?>