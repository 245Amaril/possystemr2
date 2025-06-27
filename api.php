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

        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];
            // $fileSize = $_FILES['image']['size']; // Anda bisa menambahkan validasi ukuran file
            // $fileType = $_FILES['image']['type']; // Anda bisa menambahkan validasi tipe MIME

            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $newFileName = uniqid() . '_' . md5(time() . $fileName) . '.' . $fileExtension; // Generate nama unik yang lebih kuat
            $destPath = $uploadDir . $newFileName;

            // Allowed file extensions
            $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
            if (in_array($fileExtension, $allowedfileExtensions)) {
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $image_url = $newFileName; // Simpan hanya nama filenya
                } else {
                    echo json_encode(['success' => false, 'error' => 'Gagal mengunggah gambar. Pastikan direktori "uploads" ada dan dapat ditulis.']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Ekstensi file tidak diizinkan. Hanya JPG, JPEG, PNG, GIF yang diizinkan.']);
                exit;
            }
        }

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

        $image_url_to_save = $current_image_url; // Default: pertahankan gambar yang ada

        // 2. Handle file upload (jika ada file baru diunggah)
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $newFileName = uniqid() . '_' . md5(time() . $fileName) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
            if (in_array($fileExtension, $allowedfileExtensions)) {
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    // Jika upload berhasil, hapus gambar lama (jika ada)
                    if ($current_image_url && file_exists($uploadDir . $current_image_url)) {
                        unlink($uploadDir . $current_image_url); // Hapus file gambar lama
                    }
                    $image_url_to_save = $newFileName; // Update ke nama file baru
                } else {
                    echo json_encode(['success' => false, 'error' => 'Gagal mengunggah gambar baru.']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Ekstensi file gambar tidak diizinkan. Hanya JPG, JPEG, PNG, GIF yang diizinkan.']);
                exit;
            }
        }

        // Gunakan prepared statement untuk UPDATE
        // Tambahkan image_url ke query UPDATE
        $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ?, stock = ?, image_url = ? WHERE id = ?");
        // "ssdssi" -> string, string, double, integer, string, integer (for image_url and id)
        $stmt->bind_param("ssdssi", $name, $category, $price, $stock, $image_url_to_save, $id);

        if ($stmt->execute()) {
            // Check if any rows were affected (product found and updated)
            if ($stmt->affected_rows > 0) {
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

        // 1. Ambil image_url dari produk yang akan dihapus
        $stmt_get_image = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
        $stmt_get_image->bind_param("i", $id);
        $stmt_get_image->execute();
        $result_image = $stmt_get_image->get_result();
        $product_to_delete = $result_image->fetch_assoc();
        $stmt_get_image->close();

        // 2. Hapus file gambar dari server jika ada
        if ($product_to_delete && !empty($product_to_delete['image_url'])) {
            $imagePath = $uploadDir . $product_to_delete['image_url'];
            if (file_exists($imagePath)) {
                unlink($imagePath); // Hapus file gambar
            }
        }

        // 3. Hapus entri produk dari database
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Produk berhasil dihapus.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Produk tidak ditemukan.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
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

    // Aksi default jika tidak ada yang cocok
    default:
        echo json_encode(['success' => false, 'error' => 'Aksi tidak valid']);
        break;
}

// Menutup koneksi database
$conn->close();
?>