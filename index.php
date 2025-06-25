<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem POS - Point of Sale</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .product-card {
            cursor: pointer;
            transition: all 0.3s;
        }
        .product-card:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .total-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
        }
        /* Style untuk cetak */
        @media print {
            body * {
                visibility: hidden; /* Sembunyikan semua elemen di body secara default */
            }
            #printableArea, #printableArea * {
                visibility: visible; /* Tampilkan hanya printableArea dan semua anaknya */
                background-color: white; /* Pastikan latar belakang putih */
                color: black; /* Pastikan teks hitam */
                padding: 20px; /* Tambahkan padding agar tidak terlalu mepet */
                box-sizing: border-box; /* Sertakan padding dalam lebar */
            }
            #printableArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            /* Penyesuaian font dan margin untuk tampilan struk yang ringkas */
            #printableArea h5,
            #printableArea p,
            #printableArea small,
            #printableArea strong,
            #printableArea span,
            #printableArea div {
                font-size: 12px; /* Ukuran font lebih kecil untuk struk */
                margin: 0; /* Hapus margin default */
                padding: 0; /* Hapus padding default */
                line-height: 1.2; /* Kerapatan baris */
            }
            #printableArea h5 {
                font-size: 14px; /* Judul sedikit lebih besar */
                text-align: center;
                margin-bottom: 5px;
            }
            #printableArea hr {
                border: 0.5px dashed #ccc; /* Garis putus-putus untuk pemisah */
                margin: 10px 0; /* Margin atas dan bawah untuk HR */
            }
            #printableArea table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                margin-bottom: 10px;
            }
            #printableArea th,
            #printableArea td {
                font-size: 11px; /* Font lebih kecil di tabel */
                padding: 2px 0;
                border-bottom: none; /* Hilangkan garis bawah tabel bawaan Bootstrap */
                vertical-align: top;
            }
            #printableArea .text-center { text-align: center; }
            #printableArea .text-end { text-align: right; }
            #printableArea .d-flex { display: flex !important; } /* Pastikan flexbox tetap berfungsi */
            #printableArea .justify-content-between { justify-content: space-between !important; }
            #printableArea .fs-5 { font-size: 13px !important; } /* Khusus untuk total */
            #printableArea .fw-bold { font-weight: bold !important; }
        }
        /* Styles for authentication */
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .auth-card {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            background-color: #fff;
        }
        .main-content-wrapper {
            display: none; /* Sembunyikan secara default, akan ditampilkan setelah login */
        }
    </style>
</head>
<body>
    <!-- Authentication Container (Login/Register) -->
    <div id="authContainer" class="auth-container">
        <div class="auth-card text-center">
            <h2 class="mb-4" id="authTitle">Login</h2>
            <div id="authMessage" class="alert alert-danger d-none" role="alert"></div>

            <!-- Login Form -->
            <form id="loginForm" class="auth-form">
                <div class="mb-3 text-start">
                    <label for="loginUsername" class="form-label">Username</label>
                    <input type="text" class="form-control" id="loginUsername" required>
                </div>
                <div class="mb-3 text-start">
                    <label for="loginPassword" class="form-label">Password</label>
                    <input type="password" class="form-control" id="loginPassword" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                <p>Belum punya akun? <a href="#" id="showRegister">Daftar sekarang</a></p>
            </form>

            <!-- Register Form (hidden by default) -->
            <form id="registerForm" class="auth-form d-none">
                <div class="mb-3 text-start">
                    <label for="registerUsername" class="form-label">Username</label>
                    <input type="text" class="form-control" id="registerUsername" required>
                </div>
                <div class="mb-3 text-start">
                    <label for="registerPassword" class="form-label">Password</label>
                    <input type="password" class="form-control" id="registerPassword" required>
                </div>
                <!-- Pilihan role untuk pendaftaran. HATI-HATI: Dalam aplikasi nyata, pendaftaran umum biasanya hanya untuk 'cashier' -->
                <!-- Jika ini hanya untuk demo, Anda bisa sertakan. Untuk produksi, admin harus mendaftarkan user lain. -->
                <div class="mb-3 text-start">
                    <label for="registerRole" class="form-label">Daftar Sebagai</label>
                    <select class="form-select" id="registerRole">
                        <option value="cashier">Kasir</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-success w-100 mb-3">Daftar</button>
                <p>Sudah punya akun? <a href="#" id="showLogin">Login</a></p>
            </form>
        </div>
    </div>

    <!-- Main Content Wrapper (Hidden until login) -->
    <div id="mainContentWrapper" class="container-fluid main-content-wrapper">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-3">
                <div class="text-center mb-4">
                    <h4 class="text-white"><i class="fas fa-cash-register"></i> Kedai Kopi</h4>
                    <p class="text-white-50 small mt-2" id="loggedInUser">Belum Login</p>
                    <p class="text-white-50 small" id="loggedInRole"></p> <!-- Tempat role -->
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#" data-section-id="pos-section">
                        <i class="fas fa-shopping-cart me-2"></i> Kasir
                    </a>
                    <!-- Menu untuk Admin saja -->
                    <a class="nav-link admin-only-menu d-none" href="#" data-section-id="products-section">
                        <i class="fas fa-box me-2"></i> Produk
                    </a>
                    <a class="nav-link admin-only-menu d-none" href="#" data-section-id="transactions-section">
                        <i class="fas fa-receipt me-2"></i> Transaksi
                    </a>
                    <a class="nav-link admin-only-menu d-none" href="#" data-section-id="reports-section">
                        <i class="fas fa-chart-bar me-2"></i> Laporan
                    </a>
                    <hr class="text-white-50">
                    <a class="nav-link" href="#" id="logoutButton">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content Area -->
            <div class="col-md-10 p-4">
                <!-- POS Section -->
                <div id="pos-section" class="content-section">
                    <h2>Kasir</h2>
                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5><i class="fas fa-shopping-bag me-2"></i>Pilih Produk</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" id="searchProductInput" placeholder="Cari produk...">
                                        </div>
                                        <div class="col-md-6">
                                            <select class="form-select" id="filterProductCategory">
                                                <option value="">Semua Kategori</option>
                                                <option value="makanan">Makanan</option>
                                                <option value="minuman">Minuman</option>
                                                <option value="snack">Snack</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row" id="products-grid">
                                        <!-- Products will be loaded here by JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5><i class="fas fa-shopping-cart me-2"></i>Keranjang</h5>
                                </div>
                                <div class="card-body">
                                    <div id="cart-items">
                                        <p class="text-muted text-center">Keranjang kosong</p>
                                    </div>
                                </div>
                            </div>
                            <div class="total-section mt-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span id="subtotal">Rp 0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Pajak (10%):</span>
                                    <span id="tax">Rp 0</span>
                                </div>
                                <hr class="text-white">
                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Total:</strong>
                                    <strong id="total">Rp 0</strong>
                                </div>
                                <button class="btn btn-light w-100 fw-bold" id="processPaymentButton">
                                    <i class="fas fa-credit-card me-2"></i>Bayar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Section -->
                <div id="products-section" class="content-section" style="display: none;">
                    <h2>Manajemen Produk</h2>
                    <div class="card">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-box me-2"></i>Manajemen Produk</h5>
                            <button class="btn btn-light" id="showAddProductButton">
                                <i class="fas fa-plus me-2"></i>Tambah Produk
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Gambar & Nama</th> <th>Kategori</th>
                                            <th>Harga</th>
                                            <th>Stok</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="products-table">
                                        </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions Section -->
                <div id="transactions-section" class="content-section" style="display: none;">
                    <h2>Riwayat Transaksi</h2>
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5><i class="fas fa-receipt me-2"></i>Riwayat Transaksi</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID Transaksi</th>
                                            <th>Tanggal</th>
                                            <th>Total</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transactions-table">
                                        <!-- Transactions will be populated here by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reports Section -->
                <div id="reports-section" class="content-section" style="display: none;">
                    <h2>Laporan Penjualan</h2>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-money-bill-wave fa-2x text-success mb-2"></i>
                                    <h5>Penjualan Hari Ini</h5>
                                    <h3 class="text-success" id="daily-sales">Rp 0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-shopping-cart fa-2x text-primary mb-2"></i>
                                    <h5>Transaksi Hari Ini</h5>
                                    <h3 class="text-primary" id="daily-transactions">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-box fa-2x text-warning mb-2"></i>
                                    <h5>Total Produk</h5>
                                    <h3 class="text-warning" id="total-products">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                                    <h5>Rata-rata per Transaksi</h5>
                                    <h3 class="text-info" id="avg-transaction">Rp 0</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel">Tambah Produk Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="productForm">
                    <div class="modal-body">
                        <input type="hidden" id="productId">
                        <div class="mb-3">
                            <label for="productName" class="form-label">Nama Produk</label>
                            <input type="text" class="form-control" id="productName" required>
                        </div>
                        <div class="mb-3">
                            <label for="productCategory" class="form-label">Kategori</label>
                            <select class="form-select" id="productCategory" required>
                                <option value="">Pilih Kategori</option>
                                <option value="makanan">Makanan</option>
                                <option value="minuman">Minuman</option>
                                <option value="snack">Snack</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="productPrice" class="form-label">Harga (Rp)</label>
                            <input type="number" class="form-control" id="productPrice" step="1" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="productStock" class="form-label">Stok</label>
                            <input type="number" class="form-control" id="productStock" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="productImage" class="form-label">Gambar Produk</label>
                            <input type="file" class="form-control" id="productImage" name="image" accept="image/*">
                            <div class="mt-2 text-center" id="imagePreviewContainer">
                                <img id="currentProductImage" src="https://via.placeholder.com/150" alt="Pratinjau Gambar" class="img-thumbnail" style="max-width: 150px; max-height: 150px; object-fit: cover; display: none;">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Total Pembayaran</label>
                        <input type="text" class="form-control" id="paymentTotal" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah Bayar</label>
                        <input type="number" class="form-control" id="paymentAmount" placeholder="Masukkan jumlah bayar">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kembalian</label>
                        <input type="text" class="form-control" id="paymentChange" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="completePaymentButton">Selesai</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Transaction Detail/Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">Detail Transaksi (Struk)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="receiptModalBody">
                    <!-- Receipt content will be injected here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="printReceiptFromDetailButton"><i class="fas fa-print me-2"></i>Cetak</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Success Modal -->
    <div class="modal fade" id="paymentSuccessModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="paymentSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                    <h4 class="mb-3" id="paymentSuccessModalLabel">Pembayaran Berhasil!</h4>
                    <p>Transaksi telah selesai.</p>
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                        <button type="button" class="btn btn-outline-secondary btn-lg px-4" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // --- Konfigurasi Endpoint API ---
        const API_BASE_URL = 'api.php';
        const AUTH_BASE_URL = 'auth.php';

        // --- Variabel Global ---
        let cart = [];
        let productsCache = []; // Untuk menyimpan semua produk yang diambil
        let currentUserRole = ''; // Peran pengguna yang sedang login
        let currentTransactionId = null; // Menyimpan ID transaksi terakhir untuk cetak struk

        // --- Fungsi Pembantu (Helper Functions) ---

        // Fungsi untuk melakukan request ke API (selain autentikasi)
        async function apiFetch(action, method = 'GET', body = null) {
            const url = `${API_BASE_URL}?action=${action}`;
            const options = {
                method: method,
                headers: {}
            };

            if (body) {
                options.body = body; // FormData tidak memerlukan 'Content-Type' header
            }

            try {
                const response = await fetch(url, options);
                const data = await response.json(); // Selalu coba parse JSON

                if (!response.ok) {
                    // Cek jika unauthorized (misal session habis atau akses ditolak)
                    if (response.status === 401 || response.status === 403) {
                        Swal.fire({
                            title: 'Sesi Habis atau Akses Ditolak!',
                            text: 'Anda perlu login kembali atau tidak memiliki hak akses.',
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            resetAuthState(); // Arahkan ke tampilan login
                        });
                    }
                    throw new Error(data.error || 'Terjadi kesalahan pada server.');
                }
                return data;
            } catch (error) {
                console.error(`Error fetching ${action}:`, error);
                Swal.fire('Error!', error.message || 'Terjadi kesalahan saat berkomunikasi dengan server.', 'error');
                throw error; // Re-throw the error for further handling if needed
            }
        }

        // Fungsi untuk melakukan request ke API Autentikasi
        async function authFetch(action, method = 'GET', body = null) {
            const url = `${AUTH_BASE_URL}?action=${action}`;
            const options = {
                method: method,
                headers: {}
            };

            if (body) {
                options.body = body; // FormData tidak memerlukan 'Content-Type' header
            }

            try {
                const response = await fetch(url, options);
                const data = await response.json(); // Selalu coba parse JSON

                if (!response.ok) {
                    throw new Error(data.error || 'Terjadi kesalahan pada server autentikasi.');
                }
                return data;
            } catch (error) {
                console.error(`Error fetching auth ${action}:`, error);
                Swal.fire('Error!', error.message || 'Terjadi kesalahan saat autentikasi.', 'error');
                throw error;
            }
        }

        // Format angka ke Rupiah
        function formatRupiah(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(amount);
        }

        // --- DOMContentLoaded dan Event Listeners ---
        document.addEventListener('DOMContentLoaded', function() {
            // Periksa status login saat halaman dimuat
            checkLoginStatus();

            // Navigasi Sidebar menggunakan event delegation
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const sectionId = this.dataset.sectionId;
                    showSection(sectionId);
                });
            });

            // Tampilkan form register
            document.getElementById('showRegister').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('loginForm').classList.add('d-none');
                document.getElementById('registerForm').classList.remove('d-none');
                document.getElementById('authTitle').innerText = 'Daftar';
                document.getElementById('authMessage').classList.add('d-none');
            });

            // Tampilkan form login
            document.getElementById('showLogin').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('registerForm').classList.add('d-none');
                document.getElementById('loginForm').classList.remove('d-none');
                document.getElementById('authTitle').innerText = 'Login';
                document.getElementById('authMessage').classList.add('d-none');
            });

            // Submit Form Login
            document.getElementById('loginForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                const username = document.getElementById('loginUsername').value;
                const password = document.getElementById('loginPassword').value;

                const formData = new FormData();
                formData.append('username', username);
                formData.append('password', password);

                try {
                    const data = await authFetch('login', 'POST', formData);
                    const authMessage = document.getElementById('authMessage');
                    if (data.success) {
                        authMessage.classList.add('d-none');
                        Swal.fire('Berhasil!', data.message, 'success');
                        currentUserRole = data.role;
                        document.getElementById('authContainer').style.display = 'none';
                        document.getElementById('mainContentWrapper').style.display = 'block';
                        document.getElementById('loggedInUser').innerText = data.username;
                        document.getElementById('loggedInRole').innerText = `(${data.role.toUpperCase()})`;
                        updateSidebarMenu();
                        showSection('pos-section'); // Default ke section POS setelah login
                    } else {
                        authMessage.innerText = data.error;
                        authMessage.classList.remove('d-none');
                    }
                } catch (error) {
                    // Error sudah ditangani oleh authFetch
                }
            });

            // Submit Form Register
            document.getElementById('registerForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                const username = document.getElementById('registerUsername').value;
                const password = document.getElementById('registerPassword').value;
                const role = document.getElementById('registerRole').value;

                const formData = new FormData();
                formData.append('username', username);
                formData.append('password', password);
                formData.append('role', role);

                try {
                    const data = await authFetch('register', 'POST', formData);
                    const authMessage = document.getElementById('authMessage');
                    if (data.success) {
                        authMessage.classList.add('d-none');
                        Swal.fire('Berhasil!', data.message + ' Silakan login.', 'success');
                        document.getElementById('registerForm').classList.add('d-none');
                        document.getElementById('loginForm').classList.remove('d-none');
                        document.getElementById('authTitle').innerText = 'Login';
                        document.getElementById('loginUsername').value = username;
                        document.getElementById('loginPassword').value = '';
                        document.getElementById('registerForm').reset(); // Reset register form fields
                    } else {
                        authMessage.innerText = data.error;
                        authMessage.classList.remove('d-none');
                    }
                } catch (error) {
                    // Error sudah ditangani oleh authFetch
                }
            });

            // Tombol Logout
            document.getElementById('logoutButton').addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Anda yakin ingin logout?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Logout',
                    cancelButtonText: 'Batal'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const data = await authFetch('logout', 'POST');
                            if (data.success) {
                                Swal.fire('Berhasil!', data.message, 'success').then(() => {
                                    resetAuthState();
                                });
                            } else {
                                Swal.fire('Error!', data.error, 'error');
                            }
                        } catch (error) {
                            // Error sudah ditangani oleh authFetch
                        }
                    }
                });
            });

            // Search product functionality for POS section
            document.getElementById('searchProductInput').addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const categoryFilter = document.getElementById('filterProductCategory').value;
                displayFilteredProducts(productsCache, searchTerm, categoryFilter);
            });

            // Filter product by category for POS section
            document.getElementById('filterProductCategory').addEventListener('change', function() {
                const searchTerm = document.getElementById('searchProductInput').value.toLowerCase();
                const categoryFilter = this.value;
                displayFilteredProducts(productsCache, searchTerm, categoryFilter);
            });

            // Tombol "Bayar" di POS
            document.getElementById('processPaymentButton').addEventListener('click', processPayment);
            
            // Event listener untuk input jumlah bayar di modal pembayaran
            document.getElementById('paymentAmount').addEventListener('input', function() {
                const paymentAmount = parseFloat(this.value) || 0;
                const totalText = document.getElementById('paymentTotal').value;
                // Ekstrak angka dari string Rp. 1.234
                // Pastikan ini juga menangani kasus ketika format Rupiah menggunakan koma sebagai desimal
                const totalPayment = parseFloat(totalText.replace(/[^0-9,-]+/g,"").replace(",","."));

                const change = paymentAmount - totalPayment;
                
                // Perbaiki baris ini agar menggunakan formatRupiah untuk kedua kondisi
                document.getElementById('paymentChange').value =
                    change >= 0 ? formatRupiah(change) : 'Kurang bayar: ' + formatRupiah(Math.abs(change));
            });

            // Tombol "Selesai" di modal pembayaran
            document.getElementById('completePaymentButton').addEventListener('click', completePayment);

            // Tombol "Tambah Produk" di section manajemen produk
            document.getElementById('showAddProductButton').addEventListener('click', showAddProductModal);

            // Submit Form Tambah/Edit Produk (satu modal, satu form)
            document.getElementById('productForm').addEventListener('submit', async function(e) {
                e.preventDefault(); // Mencegah form submit default
                const productId = document.getElementById('productId').value;
                
                if (productId) { // Jika ada ID, berarti edit
                    await updateProduct();
                } else { // Jika tidak ada ID, berarti tambah
                    await addProduct();
                }
            });

            // Tombol Cetak Struk dari detail transaksi
            document.getElementById('printReceiptFromDetailButton').addEventListener('click', printReceipt);
        });

        // --- Fungsi Manajemen Autentikasi dan UI ---

        // Memeriksa status login pengguna saat ini
        async function checkLoginStatus() {
            // Sembunyikan kontainer autentikasi segera untuk mencegah flickering
            document.getElementById('authContainer').style.display = 'none';

            // Tampilkan SweetAlert2 loading spinner saat memulai pengecekan status login
            Swal.fire({
                title: 'Memuat...',
                text: 'Memeriksa sesi Anda.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const data = await authFetch('get_user_info');
                if (data.success && data.username) {
                    currentUserRole = data.role;
                    document.getElementById('mainContentWrapper').style.display = 'block';
                    document.getElementById('loggedInUser').innerText = data.username;
                    document.getElementById('loggedInRole').innerText = `(${data.role.toUpperCase()})`;
                    updateSidebarMenu();
                    showSection('pos-section'); // Default ke section POS setelah login
                    // Tutup loading setelah penundaan singkat untuk efek yang lebih halus
                    setTimeout(() => {
                        Swal.close();
                    }, 800); // Durasi loading 800ms
                } else {
                    // Jika tidak berhasil login, panggil resetAuthState
                    // resetAuthState akan menangani loading dan penutupan Swal.fire sendiri
                    resetAuthState();
                }
            } catch (error) {
                // Error sudah ditangani oleh authFetch.
                // Panggil resetAuthState untuk memastikan UI kembali ke login.
                resetAuthState();
                console.error('Error checking login status:', error);
            }
        }

        // Mereset tampilan ke mode belum login
        function resetAuthState() {
            // Tampilkan SweetAlert2 loading spinner
            Swal.fire({
                title: 'Mohon Tunggu...',
                text: 'Mengatur ulang sesi Anda.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Sembunyikan elemen utama dan tampilkan kontainer autentikasi
            document.getElementById('mainContentWrapper').style.display = 'none';
            document.getElementById('authContainer').style.display = 'flex';

            // Atur ulang formulir dan informasi pengguna
            document.getElementById('loginForm').classList.remove('d-none');
            document.getElementById('registerForm').classList.add('d-none');
            document.getElementById('authTitle').innerText = 'Login';
            document.getElementById('loginUsername').value = '';
            document.getElementById('loginPassword').value = '';
            document.getElementById('loggedInUser').innerText = 'Belum Login';
            document.getElementById('loggedInRole').innerText = '';
            currentUserRole = ''; // Hapus peran pengguna
            updateSidebarMenu(); // Reset visibilitas menu sidebar

            // Tutup SweetAlert2 setelah semua pengaturan UI selesai (atau setelah penundaan singkat)
            // Penundaan singkat bisa membantu memastikan semua perubahan DOM selesai di-render
            setTimeout(() => {
                Swal.close();
            }, 300); // Penundaan 300ms, bisa disesuaikan
        }

        // Memperbarui visibilitas menu sidebar berdasarkan peran pengguna
        function updateSidebarMenu() {
            document.querySelectorAll('.admin-only-menu').forEach(item => {
                if (currentUserRole === 'admin') {
                    item.classList.remove('d-none');
                } else {
                    item.classList.add('d-none');
                }
            });
        }

        // Mengatur tampilan section yang aktif
        function showSection(sectionId) {
            // Hapus kelas 'active' dari semua link sidebar
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.classList.remove('active');
            });

            // Tambahkan kelas 'active' pada link yang dipilih
            const targetLink = document.querySelector(`.sidebar .nav-link[data-section-id="${sectionId}"]`);
            if (targetLink) {
                targetLink.classList.add('active');
            }

            // Sembunyikan semua section
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
            });

            // Tampilkan section yang dipilih
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.style.display = 'block';
            }

            // Muat data spesifik untuk setiap section, dengan pengecekan peran
            switch (sectionId) {
                case 'pos-section':
                    loadProducts();
                    break;
                case 'products-section':
                    if (currentUserRole === 'admin') {
                        loadProducts(); // Memuat produk untuk tabel admin (productsCache akan diperbarui)
                        loadProductsTable(); // Memuat tabel manajemen produk
                    } else {
                        Swal.fire('Akses Ditolak!', 'Anda tidak memiliki hak akses untuk halaman ini.', 'error');
                        showSection('pos-section'); // Kembali ke POS jika tidak punya akses
                    }
                    break;
                case 'transactions-section':
                    loadTransactionsTable();
                    break;
                case 'reports-section':
                    if (currentUserRole === 'admin') {
                        updateReports();
                    } else {
                        Swal.fire('Akses Ditolak!', 'Anda tidak memiliki hak akses untuk halaman ini.', 'error');
                        showSection('pos-section'); // Kembali ke POS jika tidak punya akses
                    }
                    break;
            }
        }

        // --- Fungsi Manajemen Produk ---

        // Memuat semua produk dari API
        async function loadProducts() {
            try {
                const data = await apiFetch('get_products');
                productsCache = data; // Simpan semua produk ke cache
                // Saat loadProducts dipanggil, pastikan untuk menampilkan produk di grid POS
                const searchTerm = document.getElementById('searchProductInput').value;
                const categoryFilter = document.getElementById('filterProductCategory').value;
                displayFilteredProducts(productsCache, searchTerm, categoryFilter);
            } catch (error) {
                // Error sudah ditangani oleh apiFetch
            }
        }

        // Menampilkan produk di bagian POS (card view) berdasarkan filter
        function displayFilteredProducts(products, filter = '', category = '') {
            const grid = document.getElementById('products-grid');
            grid.innerHTML = '';

            let filteredProducts = products.filter(p => p.stock > 0); // Hanya tampilkan produk dengan stok > 0 di POS

            if (filter) {
                filteredProducts = filteredProducts.filter(p => p.name.toLowerCase().includes(filter.toLowerCase()));
            }
            if (category) {
                filteredProducts = filteredProducts.filter(p => p.category.toLowerCase() === category.toLowerCase());
            }

            if(filteredProducts.length === 0) {
                grid.innerHTML = '<p class="text-center text-muted col-12">Produk tidak ditemukan.</p>';
                return;
            }

            filteredProducts.forEach(product => {
                // Tentukan sumber gambar, jika ada, gunakan placeholder jika tidak
                // Pastikan path `uploads/` sesuai dengan $uploadDir di api.php
                const imageUrl = product.image_url ? `uploads/${product.image_url}` : `https://via.placeholder.com/100x80?text=No+Image`;

                const productCard = `
                    <div class="col-md-4 mb-3">
                        <div class="card product-card h-100" onclick="addToCart(${product.id})">
                            <div class="card-body text-center">
                                <img src="${imageUrl}" class="img-fluid rounded mb-2" alt="${product.name}" style="max-height: 80px; object-fit: cover;">
                                <h6 class="card-title">${product.name}</h6>
                                <p class="card-text">
                                    <small class="text-muted">${product.category}</small><br>
                                    <strong class="text-success">${formatRupiah(product.price)}</strong><br>
                                    <small>Stok: <span class="badge bg-secondary">${product.stock}</span></small>
                                </p>
                            </div>
                        </div>
                    </div>
                `;
                grid.innerHTML += productCard;
            });
        }
        
        // Memuat dan menampilkan produk di tabel manajemen produk (khusus admin)
        async function loadProductsTable() {
            // productsCache sudah diisi oleh loadProducts() yang dipanggil dari showSection('products-section')
            const tbody = document.getElementById('products-table');
            tbody.innerHTML = '';
            
            if (!productsCache || productsCache.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">Belum ada produk.</td></tr>';
                return;
            }
            
            productsCache.forEach(product => {
                const stockBadgeClass = product.stock > 10 ? 'bg-success' : product.stock > 0 ? 'bg-warning' : 'bg-danger';
                // Pastikan path `uploads/` sesuai dengan $uploadDir di api.php
                const imageUrl = product.image_url ? `uploads/${product.image_url}` : `https://via.placeholder.com/50x50?text=No+Image`;

                const row = `
                    <tr>
                        <td>${product.id}</td>
                        <td>
                            <img src="${imageUrl}" alt="${product.name}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                            <span class="ms-2">${product.name}</span>
                        </td>
                        <td><span class="badge bg-secondary">${product.category}</span></td>
                        <td>${formatRupiah(product.price)}</td>
                        <td><span class="badge ${stockBadgeClass}">${product.stock}</span></td>
                        <td>
                            <button class="btn btn-sm btn-primary me-1" onclick="showEditProductModal(${product.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        // Menampilkan modal tambah produk
        function showAddProductModal() {
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('productModalLabel').textContent = 'Tambah Produk Baru';
            // Reset pratinjau gambar saat menambah produk baru
            document.getElementById('currentProductImage').src = 'https://via.placeholder.com/150';
            document.getElementById('currentProductImage').style.display = 'none'; // Sembunyikan jika tidak ada gambar
            document.getElementById('productImage').value = ''; // Pastikan input file kosong

            const modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();
        }

        // Menambahkan produk baru
        async function addProduct() {
            const form = document.getElementById('productForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData();
            formData.append('name', document.getElementById('productName').value);
            formData.append('category', document.getElementById('productCategory').value);
            formData.append('price', document.getElementById('productPrice').value);
            formData.append('stock', document.getElementById('productStock').value);
            formData.append('image', document.getElementById('productImage').files[0]);
            
            try {
                const data = await apiFetch('add_product', 'POST', formData);
                if (data.success) {
                    Swal.fire('Berhasil!', 'Produk berhasil disimpan.', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
                    loadProducts(); // Muat ulang semua produk dan perbarui tampilan POS/tabel
                } else {
                    Swal.fire('Error!', 'Gagal menyimpan produk: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                // Error sudah ditangani oleh apiFetch
            }
        }
        
        // Membuka modal edit produk dengan data yang sudah terisi
        function showEditProductModal(productId) {
            // Menggunakan operator '==' untuk perbandingan longgar, karena ID dari database
            // bisa berupa string, dan productId dari parameter bisa berupa angka.
            const product = productsCache.find(p => p.id == productId); 
            if (!product) {
                // Menggunakan SweetAlert2 untuk notifikasi yang lebih baik (jika Anda menggunakannya)
                Swal.fire('Error!', 'Produk tidak ditemukan untuk diedit.', 'error'); 
                // Jika SweetAlert2 tidak digunakan, gunakan kembali: alert('Produk tidak ditemukan!');
                return;
            }

            // Mengisi nilai-nilai ke dalam form modal
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.name;
            document.getElementById('productCategory').value = product.category;
            document.getElementById('productPrice').value = product.price;
            document.getElementById('productStock').value = product.stock;
            
            // Memperbarui pratinjau gambar di modal
            const currentProductImage = document.getElementById('currentProductImage');
            if (product.image_url) {
                // Pastikan path `uploads/` sesuai dengan $uploadDir di api.php Anda
                currentProductImage.src = `uploads/${product.image_url}`;
                currentProductImage.style.display = 'block'; // Tampilkan gambar
            } else {
                // Jika tidak ada gambar, tampilkan placeholder
                currentProductImage.src = 'https://via.placeholder.com/150'; 
                currentProductImage.style.display = 'block'; // Tetap tampilkan placeholder
            }
            
            // Mengosongkan input file agar pengguna bisa memilih file gambar baru
            // Ini penting agar file yang sudah dipilih sebelumnya tidak dikirim ulang secara tidak sengaja
            document.getElementById('productImage').value = '';

            // Mengubah judul modal menjadi 'Edit Produk'
            document.getElementById('productModalLabel').textContent = 'Edit Produk';

            // Menampilkan modal Bootstrap
            const modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();
        }

        // Memperbarui produk yang sudah ada
        async function updateProduct() {
            const form = document.getElementById('productForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData();
            formData.append('id', document.getElementById('productId').value);
            formData.append('name', document.getElementById('productName').value);
            formData.append('category', document.getElementById('productCategory').value);
            formData.append('price', document.getElementById('productPrice').value);
            formData.append('stock', document.getElementById('productStock').value);
            formData.append('image', document.getElementById('productImage').value);
            
            try {
                const data = await apiFetch('update_product', 'POST', formData);
                if (data.success) {
                    Swal.fire('Berhasil!', 'Produk berhasil diperbarui!', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
                    loadProducts(); // Muat ulang semua produk dan perbarui tampilan POS/tabel
                } else {
                    Swal.fire('Error!', 'Gagal memperbarui produk: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                // Error sudah ditangani oleh apiFetch
            }
        }

        // Menghapus produk
        async function deleteProduct(productId) {
            Swal.fire({
                title: 'Anda yakin?',
                text: "Produk ini akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('id', productId);
                    
                    try {
                        // Memanggil fungsi apiFetch untuk mengirim permintaan DELETE
                        // apiFetch diharapkan mengembalikan respons JSON
                        const data = await apiFetch('delete_product', 'POST', formData);
                        
                        if (data.success) {
                            Swal.fire('Dihapus!', 'Produk berhasil dihapus.', 'success');
                            loadProducts(); // Muat ulang semua produk dan perbarui tampilan POS/tabel
                        } else {
                            // Tampilkan pesan error dari server jika ada
                            Swal.fire('Error!', 'Gagal menghapus produk: ' + (data.error || 'Tidak diketahui'), 'error');
                        }
                    } catch (error) {
                        // Tangani error yang terjadi selama fetch atau parsing JSON
                        // Error "SyntaxError: JSON.parse: unexpected character" akan tertangkap di sini
                        console.error("Error fetching delete_product:", error); // Tampilkan error di konsol untuk debugging
                        Swal.fire('Error Koneksi!', 'Terjadi masalah saat berkomunikasi dengan server. Mohon coba lagi. Detail: ' + error.message, 'error');
                    }
                }
            });
        }
        
        // --- Fungsi Manajemen Keranjang dan Pembayaran ---

        // Menambahkan produk ke keranjang
        function addToCart(productId) {
            const product = productsCache.find(p => p.id == productId);
            if (!product) {
                Swal.fire('Error!', 'Produk tidak ditemukan.', 'error');
                return;
            }
            if (product.stock <= 0) {
                Swal.fire('Stok Habis!', `${product.name} sedang tidak tersedia.`, 'warning');
                return;
            }

            const existingItem = cart.find(item => item.id == productId);
            if (existingItem) {
                if (existingItem.quantity + 1 > product.stock) {
                    Swal.fire('Stok Kurang!', `Stok ${product.name} hanya tersisa ${product.stock}.`, 'warning');
                    return;
                }
                existingItem.quantity++;
            } else {
                cart.push({ id: parseInt(product.id), name: product.name, price: parseFloat(product.price), quantity: 1, stock: product.stock });
            }
            updateCartDisplay();
        }

        // Menghapus item dari keranjang
        function removeFromCart(productId) {
            cart = cart.filter(item => item.id != productId);
            updateCartDisplay();
        }

        // Memperbarui kuantitas item di keranjang
        function updateQuantity(productId, change) {
            const item = cart.find(item => item.id == productId);
            const product = productsCache.find(p => p.id == productId); // Dapatkan info stok terbaru

            if (item && product) {
                const newQuantity = item.quantity + change;
                if (newQuantity <= 0) {
                    removeFromCart(productId);
                } else if (newQuantity > product.stock) {
                    Swal.fire('Stok Kurang!', `Stok ${item.name} hanya tersisa ${product.stock}.`, 'warning');
                    item.quantity = product.stock; // Set ke maksimum stok yang tersedia
                } else {
                    item.quantity = newQuantity;
                }
            }
            updateCartDisplay();
        }

        // Memperbarui tampilan keranjang belanja
        function updateCartDisplay() {
            const cartItemsContainer = document.getElementById('cart-items');
            cartItemsContainer.innerHTML = '';

            if (cart.length === 0) {
                cartItemsContainer.innerHTML = '<p class="text-muted text-center">Keranjang kosong</p>';
            } else {
                cart.forEach(item => {
                    const cartItemHTML = `
                        <div class="cart-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">${item.name}</h6>
                                    <small class="text-muted">${formatRupiah(item.price)}</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(${item.id}, -1)">-</button>
                                    <span class="mx-2">${item.quantity}</span>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(${item.id}, 1)">+</button>
                                    <button class="btn btn-sm btn-outline-danger ms-2" onclick="removeFromCart(${item.id})"> <i class="fas fa-trash"></i> </button>
                                </div>
                            </div>
                            <div class="text-end mt-1"> <strong>${formatRupiah(item.price * item.quantity)}</strong> </div>
                        </div>
                    `;
                    cartItemsContainer.innerHTML += cartItemHTML;
                });
            }
            calculateAndDisplayTotals();
        }

        // Menghitung dan menampilkan subtotal, pajak, dan total
        function calculateAndDisplayTotals() {
            const subtotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
            const tax = subtotal * 0.10; // Pajak 10%
            const total = subtotal + tax;

            document.getElementById('subtotal').textContent = formatRupiah(subtotal);
            document.getElementById('tax').textContent = formatRupiah(tax);
            document.getElementById('total').textContent = formatRupiah(total);
        }

        // Menampilkan modal pembayaran
        function processPayment() {
            if (cart.length === 0) {
                Swal.fire('Keranjang Kosong!', 'Tambahkan produk ke keranjang terlebih dahulu.', 'warning');
                return;
            }

            const totalAmount = parseFloat(document.getElementById('total').textContent.replace(/[^0-9,-]+/g,"").replace(",",".")); // Dapatkan total dari tampilan

            document.getElementById('paymentTotal').value = formatRupiah(totalAmount);
            document.getElementById('paymentAmount').value = '';
            document.getElementById('paymentChange').value = '';

            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            paymentModal.show();
        }

        // Menyelesaikan pembayaran
        async function completePayment() {
            const paymentAmount = parseFloat(document.getElementById('paymentAmount').value) || 0;
            const totalAmountDue = parseFloat(document.getElementById('total').textContent.replace(/[^0-9,-]+/g,"").replace(",","."));

            if (paymentAmount < totalAmountDue) {
                Swal.fire('Pembayaran Kurang!', 'Jumlah bayar kurang dari total tagihan.', 'warning');
                return;
            }
            
            const cartItemsForAPI = cart.map(item => ({
                id: item.id,
                quantity: item.quantity,
                price: item.price // harga per item
            }));

            const formData = new FormData();
            formData.append('cart', JSON.stringify(cartItemsForAPI));
            formData.append('total', totalAmountDue);

            try {
                const data = await apiFetch('process_transaction', 'POST', formData);
                if (data.success) {
                    currentTransactionId = data.transaction_id; // Simpan ID transaksi untuk struk
                    // Sembunyikan modal pembayaran
                    const paymentModalInstance = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
                    if (paymentModalInstance) {
                        paymentModalInstance.hide();
                    }

                    // Tampilkan modal sukses
                    const successModal = new bootstrap.Modal(document.getElementById('paymentSuccessModal'));
                    successModal.show();

                    // Setelah modal sukses ditutup, reset keranjang dan muat ulang data
                    document.getElementById('paymentSuccessModal').addEventListener('hidden.bs.modal', () => {
                        cart = []; // Kosongkan keranjang
                        loadProducts(); // Muat ulang produk untuk update stok
                        loadTransactionsTable(); // Perbarui riwayat transaksi
                        updateReports(); // Perbarui laporan
                        updateCartDisplay(); // Perbarui tampilan keranjang kosong
                    }, { once: true }); // Listener hanya berjalan sekali
                } else {
                    Swal.fire('Error!', 'Gagal memproses transaksi: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                // Error sudah ditangani oleh apiFetch
            }
        }

        // --- Fungsi Manajemen Transaksi dan Laporan ---

        // Memuat dan menampilkan riwayat transaksi di tabel
        async function loadTransactionsTable() {
            try {
                const transactions = await apiFetch('get_transactions');
                const tbody = document.getElementById('transactions-table');
                tbody.innerHTML = '';
                
                if (transactions.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">Belum ada transaksi.</td></tr>';
                    return;
                }
                
                transactions.forEach(transaction => {
                    const date = new Date(transaction.transaction_date);
                    const row = `
                        <tr>
                            <td>#${String(transaction.id).padStart(4, '0')}</td>
                            <td>${date.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })}</td>
                            <td>${formatRupiah(transaction.total_amount)}</td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewTransactionDetails(${transaction.id})">
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            } catch (error) {
                // Error sudah ditangani oleh apiFetch
            }
        }

        // Menampilkan detail transaksi di modal struk
        async function viewTransactionDetails(transactionId) {
            try {
                const data = await apiFetch(`get_transaction_details&id=${transactionId}`);

                if (data && data.transaction) {
                    const receiptBody = document.getElementById('receiptModalBody');
                    const tx = data.transaction;
                    const items = data.items;
                    const txDate = new Date(tx.transaction_date);

                    let subtotal = 0;
                    let itemsHtml = '';
                    items.forEach(item => {
                        const itemTotal = item.quantity * item.price_per_item;
                        subtotal += itemTotal;
                        itemsHtml += `
                            <tr>
                                <td>${item.name}</td>
                                <td class="text-center">${item.quantity}</td>
                                <td class="text-end">${formatRupiah(item.price_per_item)}</td>
                                <td class="text-end">${formatRupiah(itemTotal)}</td>
                            </tr>
                        `;
                    });

                    const tax = subtotal * 0.10;

                    receiptBody.innerHTML = `
                        <div id="printableArea">
                            <h5 class="text-center mb-0">KEDAI BIASANE</h5>
                            <p class="text-center small">Jl. Awan, RT.03/RW.21, No. 0000</p>
                            <hr class="my-2">
                            <p class="mb-0"><strong>ID Transaksi:</strong> #${String(tx.id).padStart(4, '0')}</p>
                            <p><strong>Tanggal:</strong> ${txDate.toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' })}</p>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-center">Jml</th>
                                        <th class="text-end">Harga</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>${itemsHtml}</tbody>
                            </table>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between"><span>Subtotal</span> <strong>${formatRupiah(subtotal)}</strong></div>
                            <div class="d-flex justify-content-between"><span>Pajak (10%)</span> <strong>${formatRupiah(tax)}</strong></div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fs-5 fw-bold"><span>TOTAL</span> <span>${formatRupiah(tx.total_amount)}</span></div>
                            <hr class="my-2">
                            <p class="text-center small mt-3">Terima kasih telah berbelanja!</p>
                        </div>
                    `;

                    currentTransactionId = transactionId; // Set ID transaksi untuk cetak dari detail
                    const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
                    receiptModal.show();
                } else {
                    Swal.fire('Error!', 'Detail transaksi tidak ditemukan.', 'error');
                }
            } catch (error) {
                // Error sudah ditangani oleh apiFetch
            }
        }
        
        // Fungsi untuk mencetak struk dari area #printableArea
        function printReceipt() {
            const printableContent = document.getElementById('printableArea').innerHTML;
            const printWindow = window.open('', '', 'height=800,width=500'); // Atur ukuran jendela

            printWindow.document.write('<html><head><title>Cetak Struk</title>');
            // Memuat Bootstrap CSS
            printWindow.document.write('<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">');

            // Memuat Font Awesome (jika ada ikon)
            printWindow.document.write('<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">');

            // Menambahkan CSS kustom Anda secara inline untuk print
            printWindow.document.write(`
            <style>
                /* Reset dasar untuk cetak */
                body { font-family: 'Courier New', Courier, monospace; margin: 0; padding: 0; }
                * { box-sizing: border-box; }

                /* Kontainer utama struk */
                #printableArea {
                    width: 300px; /* Lebar standar untuk struk kasir */
                    margin: 0 auto; /* Tengah di halaman cetak */
                    padding: 15px; /* Padding di sekitar konten struk */
                    font-size: 12px; /* Ukuran font default */
                    color: black;
                }

                /* Penyesuaian elemen di dalam struk */
                h5 {
                    font-size: 16px;
                    text-align: center;
                    margin-bottom: 5px;
                }
                p, small, strong, span, div {
                    font-size: 12px;
                    margin: 0;
                    padding: 0;
                    line-height: 1.3;
                }
                hr {
                    border: none;
                    border-top: 1px dashed #bbb;
                    margin: 10px 0;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 10px 0;
                }
                th, td {
                    font-size: 12px;
                    padding: 2px 0;
                    vertical-align: top;
                    border-bottom: none; /* Hilangkan garis bawah tabel Bootstrap */
                }
                .text-center { text-align: center; }
                .text-end { text-align: right; }
                .d-flex { display: flex; }
                .justify-content-between { justify-content: space-between; }
                .fs-5 { font-size: 14px !important; }
                .fw-bold { font-weight: bold !important; }
            </style>
            `);
            printWindow.document.write('</head><body>');
            printWindow.document.write(printableContent);
            printWindow.document.write('</body></html>');

            printWindow.document.close(); // Penting: tutup dokumen setelah semua konten ditulis
            printWindow.focus(); // Fokuskan jendela cetak

            // Beri sedikit waktu agar browser merender konten sebelum memanggil print
            setTimeout(() => {
                printWindow.print(); // Panggil dialog cetak browser
                printWindow.close(); // Tutup jendela setelah cetak
            }, 500); // Penundaan 500ms
        }

        // Memperbarui data laporan
        async function updateReports() {
            if (currentUserRole !== 'admin') {
                // Notifikasi sudah ditangani oleh showSection, tapi ini sebagai fallback
                return;
            }

            try {
                const reports = await apiFetch('get_reports');
                document.getElementById('daily-sales').textContent = formatRupiah(reports.daily_sales || 0);
                document.getElementById('daily-transactions').textContent = reports.daily_transactions || 0;
                document.getElementById('total-products').textContent = reports.total_products || 0;

                const avg = (reports.daily_transactions > 0) ? (reports.daily_sales / reports.daily_transactions) : 0;
                document.getElementById('avg-transaction').textContent = formatRupiah(avg);

            } catch (error) {
                // Error sudah ditangani oleh apiFetch
            }
        }
    </script>
</body>
</html>