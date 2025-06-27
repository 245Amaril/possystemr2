<?php
// File: api.php
header('Content-Type: application/json'); // Pastikan ini ada di paling atas untuk output JSON

// Memasukkan file koneksi database
require 'db_connect.php'; // Pastikan file ini ada dan berisi koneksi $conn

// Direktori untuk menyimpan gambar produk
// PASTIKAN DIREKTORI INI ADA DAN MEMILIKI IZIN TULIS (e.g., chmod 775 atau 777 untuk testing)
$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true); // Buat direktori jika belum ada
}

// Fungsi untuk handle upload gambar, return nama file jika sukses, null jika tidak ada upload, false jika gagal
function handle_image_upload($uploadDir, $current_image_url = null) {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileNameCmps = explode('.', $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
        if (!in_array($fileExtension, $allowedfileExtensions)) {
            echo json_encode(['success' => false, 'error' => 'Ekstensi file tidak diizinkan. Hanya JPG, JPEG, PNG, GIF yang diizinkan.']);
            exit;
        }
        $newFileName = uniqid() . '_' . md5(time() . $fileName) . '.' . $fileExtension;
        $destPath = $uploadDir . $newFileName;
        if (!file_exists($fileTmpPath)) {
            echo json_encode(['success' => false, 'error' => 'File upload tidak ditemukan di server (tmp).']);
            exit;
        }
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            // Hapus gambar lama jika ada (khusus update)
            if ($current_image_url && file_exists($uploadDir . $current_image_url)) {
                unlink($uploadDir . $current_image_url);
            }
            return $newFileName;
        } else {
            echo json_encode(['success' => false, 'error' => 'Gagal mengunggah gambar. Pastikan folder uploads dapat ditulis.']);
            exit;
        }
    }
    return null; // Tidak ada upload
}

// Mendapatkan aksi yang diminta dari frontend
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Menggunakan switch untuk menangani berbagai aksi
switch ($action) {
    // Mengambil semua produk dari database
    case 'get_products':
        // Pastikan Anda memilih kolom image_url dari database
        $sql = "SELECT id, name, category, price, stock, image_url FROM products ORDER BY name ASC";
        $result = $conn->query($sql);
        $products = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        echo json_encode($products);
        break;

    // Menambahkan produk baru
    case 'add_product':
        $name = $_POST['name'] ?? null;
        $category = $_POST['category'] ?? null;
        $price = $_POST['price'] ?? null;
        $stock = $_POST['stock'] ?? null;
        $image_url = null; // Default null, akan diisi jika ada file diupload

        // Validasi dasar input teks
        if (empty($name) || empty($category) || !isset($price) || !isset($stock)) {
            echo json_encode(['success' => false, 'error' => 'Nama, kategori, harga, dan stok wajib diisi.']);
            exit;
        }

        // Handle file upload dengan fungsi baru
        $image_url = handle_image_upload($uploadDir);

        // Menggunakan prepared statement untuk keamanan (mencegah SQL Injection)
        // Tambahkan image_url ke query INSERT
        $stmt = $conn->prepare("INSERT INTO products (name, category, price, stock, image_url) VALUES (?, ?, ?, ?, ?)");
        // "ssdis" -> string, string, double, integer, string (for image_url)
        $stmt->bind_param("ssdis", $name, $category, $price, $stock, $image_url);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id, 'image_url' => $image_url]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        break;

    // Memperbarui produk yang sudah ada
    case 'update_product':
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? null;
        $category = $_POST['category'] ?? null;
        $price = $_POST['price'] ?? null;
        $stock = $_POST['stock'] ?? null;
        $image_url_to_save = null; // Ini akan menjadi image_url yang akhirnya disimpan di DB

        // Validasi input
        if (empty($id) || empty($name) || empty($category) || !isset($price) || !isset($stock)) {
            echo json_encode(['success' => false, 'error' => 'ID, nama, kategori, harga, dan stok wajib diisi.']);
            exit;
        }

        // Konversi tipe data
        $id = (int)$id;
        $price = (float)$price;
        $stock = (int)$stock;

        // 1. Ambil image_url yang ada saat ini dari database
        $current_image_url = null;
        $stmt_get_current_image = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
        $stmt_get_current_image->bind_param("i", $id);
        $stmt_get_current_image->execute();
        $result_get_current_image = $stmt_get_current_image->get_result();
        if ($row_current_image = $result_get_current_image->fetch_assoc()) {
            $current_image_url = $row_current_image['image_url'];
        }
        $stmt_get_current_image->close();

        // 2. Handle file upload (jika ada file baru diunggah) dengan fungsi baru
        $uploaded_image = handle_image_upload($uploadDir, $current_image_url);
        if ($uploaded_image !== null) {
            $image_url_to_save = $uploaded_image;
        } else {
            $image_url_to_save = $current_image_url;
        }

        // Gunakan prepared statement untuk UPDATE
        // Tambahkan image_url ke query UPDATE
        $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ?, stock = ?, image_url = ? WHERE id = ?");
        // "ssdssi" -> string, string, double, integer, string, integer (for image_url and id)
        $stmt->bind_param("ssdssi", $name, $category, $price, $stock, $image_url_to_save, $id);

        if ($stmt->execute()) {
            // Jika tidak ada baris yang terpengaruh, cek apakah gambar berubah
            if ($stmt->affected_rows > 0 || ($image_url_to_save !== $current_image_url)) {
                echo json_encode(['success' => true, 'message' => 'Produk berhasil diperbarui.', 'image_url' => $image_url_to_save]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Tidak ada perubahan yang dibuat atau produk tidak ditemukan.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        break;

    // Menghapus produk
    case 'delete_product':
        $id = $_POST['id'] ?? null;

        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'ID Produk wajib diisi.']);
            exit;
        }

        // 1. Ambil seluruh data produk yang akan dihapus
        $stmt_get_product = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt_get_product->bind_param("i", $id);
        $stmt_get_product->execute();
        $result_product = $stmt_get_product->get_result();
        $product_data = $result_product->fetch_assoc();
        $stmt_get_product->close();

        if (!$product_data) {
            echo json_encode(['success' => false, 'error' => 'Produk tidak ditemukan.']);
            exit;
        }

        // Gunakan transaksi agar insert dan delete berjalan atomik
        $conn->begin_transaction();
        try {
            // 2. Simpan data produk ke tabel deleted_products
            $stmt_insert_deleted = $conn->prepare("INSERT INTO deleted_products (original_product_id, name, category, price, stock, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert_deleted->bind_param(
                "issdis",
                $product_data['id'],
                $product_data['name'],
                $product_data['category'],
                $product_data['price'],
                $product_data['stock'],
                $product_data['image_url']
            );
            if (!$stmt_insert_deleted->execute()) {
                throw new Exception('Gagal memindahkan ke tempat sampah: ' . $stmt_insert_deleted->error);
            }
            $stmt_insert_deleted->close();

            // 3. Hapus semua transaction_details terkait produk ini
            $stmt_del_details = $conn->prepare("DELETE FROM transaction_details WHERE product_id = ?");
            $stmt_del_details->bind_param("i", $id);
            if (!$stmt_del_details->execute()) {
                throw new Exception('Gagal menghapus detail transaksi terkait produk: ' . $stmt_del_details->error);
            }
            $stmt_del_details->close();

            // 4. Hapus entri produk dari database
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute() || $stmt->affected_rows < 1) {
                throw new Exception('Gagal menghapus produk dari daftar utama.');
            }
            $stmt->close();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Produk berhasil dipindahkan ke tempat sampah. Semua histori transaksi produk ini juga dihapus.']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // Memproses transaksi pembayaran
    case 'process_transaction':
        $cart = json_decode($_POST['cart'], true);
        $total = floatval($_POST['total']);

        // Validasi keranjang
        if (empty($cart) || !is_array($cart)) {
            echo json_encode(['success' => false, 'error' => 'Keranjang kosong atau tidak valid.']);
            exit;
        }

        // Memulai transaksi database untuk memastikan semua query berhasil atau tidak sama sekali
        $conn->begin_transaction();

        try {
            // 1. Simpan data utama transaksi ke tabel 'transactions'
            $stmt = $conn->prepare("INSERT INTO transactions (total_amount) VALUES (?)");
            $stmt->bind_param("d", $total);
            if (!$stmt->execute()) {
                throw new Exception("Gagal menyimpan transaksi utama: " . $stmt->error);
            }
            $transaction_id = $conn->insert_id; // Dapatkan ID dari transaksi yang baru saja dibuat
            $stmt->close();

            // 2. Siapkan statement untuk menyimpan detail item dan mengurangi stok
            $stmt_details = $conn->prepare("INSERT INTO transaction_details (transaction_id, product_id, quantity, price_per_item) VALUES (?, ?, ?, ?)");
            $stmt_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

            // Loop setiap item di keranjang
            foreach ($cart as $item) {
                // Validasi item keranjang
                if (!isset($item['id']) || !isset($item['quantity']) || !isset($item['price'])) {
                    throw new Exception("Data item keranjang tidak lengkap.");
                }

                // Ambil stok produk saat ini untuk mencegah penjualan negatif
                $stmt_check_stock = $conn->prepare("SELECT stock FROM products WHERE id = ?");
                $stmt_check_stock->bind_param("i", $item['id']);
                $stmt_check_stock->execute();
                $result_stock = $stmt_check_stock->get_result();
                $product_stock = $result_stock->fetch_assoc();
                $stmt_check_stock->close();

                if (!$product_stock || $product_stock['stock'] < $item['quantity']) {
                    throw new Exception("Stok untuk produk ID " . $item['id'] . " tidak cukup.");
                }

                // Simpan detail transaksi
                $stmt_details->bind_param("iiid", $transaction_id, $item['id'], $item['quantity'], $item['price']);
                if (!$stmt_details->execute()) {
                    throw new Exception("Gagal menyimpan detail transaksi: " . $stmt_details->error);
                }

                // Kurangi stok produk
                $stmt_stock->bind_param("ii", $item['quantity'], $item['id']);
                if (!$stmt_stock->execute()) {
                    throw new Exception("Gagal memperbarui stok produk: " . $stmt_stock->error);
                }
            }
            $stmt_details->close();
            $stmt_stock->close();

            // Jika semua query berhasil, commit (simpan permanen) transaksi
            $conn->commit();
            echo json_encode(['success' => true, 'transaction_id' => $transaction_id]);

        } catch (Exception $e) {
            // Jika terjadi kesalahan, batalkan semua perubahan (rollback)
            $conn->rollback();
            // Log the detailed error on the server side for debugging
            error_log("Transaction error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan saat memproses transaksi: ' . $e->getMessage()]);
        }
        break;

    // Mengambil riwayat transaksi
    case 'get_transactions':
        $sql = "SELECT id, transaction_date, total_amount FROM transactions ORDER BY transaction_date DESC";
        $result = $conn->query($sql);
        $transactions = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }
        }
        echo json_encode($transactions);
        break;
        
    // Mengambil detail transaksi spesifik
    case 'get_transaction_details':
        $id = $_GET['id'] ?? null;
        $response = [];
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'Transaction ID is required.']);
            exit;
        }

        // Ambil info transaksi utama
        $stmt = $conn->prepare("SELECT id, transaction_date, total_amount FROM transactions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $response['transaction'] = $result->fetch_assoc();
        $stmt->close();
        
        // Ambil item-item dalam transaksi
        $stmt = $conn->prepare("
            SELECT td.quantity, td.price_per_item, p.name, p.image_url
            FROM transaction_details td
            JOIN products p ON td.product_id = p.id
            WHERE td.transaction_id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $response['items'] = $items;
        $stmt->close();
        
        echo json_encode($response);
        break;

    // Mengambil data untuk laporan
    case 'get_reports':
        $reports = [];

        // Penjualan Hari Ini
        $result = $conn->query("SELECT SUM(total_amount) as total FROM transactions WHERE DATE(transaction_date) = CURDATE()");
        $reports['daily_sales'] = $result->fetch_assoc()['total'] ?? 0;

        // Transaksi Hari Ini
        $result = $conn->query("SELECT COUNT(id) as count FROM transactions WHERE DATE(transaction_date) = CURDATE()");
        $reports['daily_transactions'] = $result->fetch_assoc()['count'] ?? 0;

        // Total Produk
        $result = $conn->query("SELECT COUNT(id) as count FROM products");
        $reports['total_products'] = $result->fetch_assoc()['count'] ?? 0;
        
        // Total Stok Produk
        $result = $conn->query("SELECT SUM(stock) as total_stock FROM products");
        $reports['total_stock_products'] = $result->fetch_assoc()['total_stock'] ?? 0;

        echo json_encode($reports);
        break;

    // Mengambil produk yang ada di tempat sampah
    case 'get_deleted_products':
        $sql = "SELECT * FROM deleted_products ORDER BY deleted_at DESC";
        $result = $conn->query($sql);
        $deleted_products = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $deleted_products[] = $row;
            }
        }
        echo json_encode($deleted_products);
        break;

    // Memulihkan produk dari tempat sampah
    case 'restore_product':
        $id = $_POST['id'] ?? null; // id pada tabel deleted_products
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'ID produk di tempat sampah wajib diisi.']);
            exit;
        }
        // Ambil data dari deleted_products
        $stmt = $conn->prepare("SELECT * FROM deleted_products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $deleted_product = $result->fetch_assoc();
        $stmt->close();
        if (!$deleted_product) {
            echo json_encode(['success' => false, 'error' => 'Produk di tempat sampah tidak ditemukan.']);
            exit;
        }
        // Cek apakah original_product_id sudah ada di products (jika iya, tidak boleh restore)
        $stmt_check = $conn->prepare("SELECT id FROM products WHERE id = ?");
        $stmt_check->bind_param("i", $deleted_product['original_product_id']);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $stmt_check->close();
            echo json_encode(['success' => false, 'error' => 'Produk dengan ID asli sudah ada di daftar produk.']);
            exit;
        }
        $stmt_check->close();
        // Insert kembali ke products
        $stmt_restore = $conn->prepare("INSERT INTO products (id, name, category, price, stock, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_restore->bind_param(
            "issdis",
            $deleted_product['original_product_id'],
            $deleted_product['name'],
            $deleted_product['category'],
            $deleted_product['price'],
            $deleted_product['stock'],
            $deleted_product['image_url']
        );
        if ($stmt_restore->execute()) {
            // Hapus dari deleted_products
            $stmt_del = $conn->prepare("DELETE FROM deleted_products WHERE id = ?");
            $stmt_del->bind_param("i", $id);
            $stmt_del->execute();
            $stmt_del->close();
            echo json_encode(['success' => true, 'message' => 'Produk berhasil dipulihkan.']);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt_restore->error]);
        }
        $stmt_restore->close();
        break;

    // Hapus permanen produk dari tempat sampah beserta histori transaksinya
    case 'permanent_delete_product':
        $id = $_POST['id'] ?? null; // id pada tabel deleted_products
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'ID produk di tempat sampah wajib diisi.']);
            exit;
        }
        // Ambil data dari deleted_products
        $stmt = $conn->prepare("SELECT * FROM deleted_products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $deleted_product = $result->fetch_assoc();
        $stmt->close();
        if (!$deleted_product) {
            echo json_encode(['success' => false, 'error' => 'Produk di tempat sampah tidak ditemukan.']);
            exit;
        }
        $product_id = $deleted_product['original_product_id'];
        // Hapus histori transaksi (transaction_details) untuk produk ini
        $stmt_del_details = $conn->prepare("DELETE FROM transaction_details WHERE product_id = ?");
        $stmt_del_details->bind_param("i", $product_id);
        $stmt_del_details->execute();
        $stmt_del_details->close();
        // (Opsional) Hapus transaksi di tabel transactions yang tidak punya detail lagi
        $conn->query("DELETE t FROM transactions t LEFT JOIN transaction_details td ON t.id = td.transaction_id WHERE td.id IS NULL");
        // Hapus file gambar jika ada
        if (!empty($deleted_product['image_url'])) {
            $imagePath = $uploadDir . $deleted_product['image_url'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        // Hapus dari deleted_products
        $stmt_del = $conn->prepare("DELETE FROM deleted_products WHERE id = ?");
        $stmt_del->bind_param("i", $id);
        if ($stmt_del->execute()) {
            echo json_encode(['success' => true, 'message' => 'Produk dan seluruh histori transaksinya berhasil dihapus permanen.']);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt_del->error]);
        }
        $stmt_del->close();
        break;

    // Aksi default jika tidak ada yang cocok
    default:
        echo json_encode(['success' => false, 'error' => 'Aksi tidak valid']);
        break;
}

// Menutup koneksi database
$conn->close();
?>