<?php
// ==========================================
// KONEKSI DATABASE AYAM KILAT MELALUI PROXYSQL
// ==========================================

$dbHost = getenv('DB_HOST') ?: 'proxysql';
$dbPort = (int)(getenv('DB_PORT') ?: 6033);
$dbName = getenv('DB_NAME') ?: 'db_ayam_kilat';
$dbUser = getenv('DB_USER') ?: 'ayam_user';
$dbPass = getenv('DB_PASS') ?: '';

$produk = [];
$error = '';

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli(
        $dbHost,
        $dbUser,
        $dbPass,
        $dbName,
        $dbPort
    );

    $conn->set_charset('utf8mb4');

    $result = $conn->query(
        "SELECT id, nama_produk, harga, stok, satuan, deskripsi
         FROM produk
         ORDER BY id ASC"
    );

    while ($row = $result->fetch_assoc()) {
        $produk[] = $row;
    }

    $conn->close();

} catch (Throwable $e) {
    error_log('AYAM KILAT database: ' . $e->getMessage());
    $error = 'Koneksi database belum berhasil. Silakan coba beberapa saat lagi.';
}

// ==========================================
// FOTO PRODUK
// ==========================================

$fotoProduk = [
    'Ayam Potong' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=900&q=85',
    'Ayam Kampung' => 'https://images.unsplash.com/photo-1587593810167-a84920ea0781?auto=format&fit=crop&w=900&q=85',
    'Ayam Fillet' => 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?auto=format&fit=crop&w=900&q=85'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>AYAM KILAT | Toko Ayam Segar</title>

<style>
* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #fffaf4;
    color: #29251f;
}

header {
    background: #173f2b;
    color: white;
    padding: 17px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 3px 15px #0002;
}

.logo {
    font-size: 25px;
    font-weight: 900;
    letter-spacing: 1px;
}

.logo span {
    color: #ffc34d;
}

nav a {
    color: white;
    text-decoration: none;
    margin-left: 22px;
    font-weight: bold;
}

.hero {
    padding: 75px 8%;
    background: linear-gradient(
        110deg,
        #173f2b 0%,
        #24633e 65%,
        #397b4e 100%
    );
    color: white;
    display: grid;
    grid-template-columns: 1.1fr .9fr;
    align-items: center;
    gap: 35px;
}

.hero h1 {
    font-size: 48px;
    line-height: 1.12;
    margin: 0 0 18px;
}

.hero h1 span {
    color: #ffc34d;
}

.hero p {
    font-size: 18px;
    line-height: 1.7;
    color: #e8f3e9;
}

.hero-img {
    width: 100%;
    height: 310px;
    object-fit: cover;
    border-radius: 25px;
    box-shadow: 0 15px 35px #0005;
}

.btn {
    display: inline-block;
    background: #ffc34d;
    color: #26351f;
    padding: 14px 24px;
    border: 0;
    border-radius: 9px;
    font-weight: bold;
    text-decoration: none;
    cursor: pointer;
}

.section {
    padding: 55px 8%;
}

.section-title {
    text-align: center;
    margin-bottom: 32px;
}

.section-title h2 {
    font-size: 32px;
    color: #173f2b;
    margin-bottom: 8px;
}

.section-title p {
    color: #777;
}

.products {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 25px;
}

.card {
    background: white;
    border-radius: 17px;
    overflow: hidden;
    box-shadow: 0 5px 22px #00000012;
    transition: .2s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 28px #0002;
}

.card img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    background: #eee;
}

.card-content {
    padding: 20px;
}

.card h3 {
    color: #173f2b;
    margin: 0 0 9px;
    font-size: 21px;
}

.card p {
    color: #777;
    line-height: 1.5;
    min-height: 42px;
}

.price {
    font-size: 23px;
    font-weight: 900;
    color: #d66b16;
    margin: 14px 0;
}

.stock {
    font-size: 13px;
    color: #64816b;
}

.add-btn {
    width: 100%;
    background: #1d7043;
    color: white;
    border: 0;
    border-radius: 8px;
    padding: 13px;
    font-weight: bold;
    cursor: pointer;
}

.add-btn:hover {
    background: #145333;
}

.cart-section {
    background: #f0f5ed;
}

.cart-box {
    max-width: 850px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 20px #00000010;
}

.cart-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 13px 0;
    border-bottom: 1px solid #eee;
}

.cart-row button {
    border: 0;
    background: #ffe4df;
    color: #a52b20;
    padding: 7px 10px;
    border-radius: 6px;
    cursor: pointer;
}

.total {
    font-size: 23px;
    font-weight: bold;
    text-align: right;
    color: #173f2b;
    margin: 20px 0;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 13px;
}

input,
textarea {
    width: 100%;
    padding: 13px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font: inherit;
}

textarea {
    grid-column: 1/-1;
    min-height: 85px;
    resize: vertical;
}

.checkout {
    width: 100%;
    padding: 15px;
    background: #20a65a;
    color: white;
    border: 0;
    border-radius: 9px;
    font-weight: bold;
    font-size: 16px;
    cursor: pointer;
    margin-top: 15px;
}

.checkout:hover {
    background: #168447;
}

.checkout:disabled {
    opacity: .65;
    cursor: not-allowed;
}

.info {
    padding: 25px 8%;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    text-align: center;
}

.info div {
    background: white;
    padding: 20px;
    border-radius: 12px;
}

.info strong {
    display: block;
    color: #173f2b;
    margin-bottom: 8px;
}

footer {
    background: #173f2b;
    color: white;
    text-align: center;
    padding: 30px 15px;
    line-height: 1.8;
}

.error {
    background: #ffe4df;
    color: #9c241b;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.empty {
    text-align: center;
    color: #888;
    padding: 20px;
}

@media(max-width:800px) {
    .hero {
        grid-template-columns: 1fr;
        padding: 48px 6%;
    }

    .hero h1 {
        font-size: 37px;
    }

    .hero-img {
        height: 230px;
    }

    .products {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .section {
        padding: 42px 6%;
    }
}

@media(max-width:560px) {
    header {
        padding: 15px 5%;
        flex-wrap: wrap;
        gap: 12px;
    }

    nav a {
        margin: 0 12px 0 0;
        font-size: 13px;
    }

    .products {
        grid-template-columns: 1fr;
    }

    .card img {
        height: 230px;
    }

    .info {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    textarea {
        grid-column: auto;
    }
}
</style>
</head>

<body>

<!-- ======================================
     HEADER
====================================== -->

<header>
    <div class="logo">
        🐔 AYAM <span>KILAT</span>
    </div>

    <nav>
        <a href="#beranda">Beranda</a>
        <a href="#produk">Produk</a>
        <a href="#keranjang">
            Keranjang (<span id="cart-count">0</span>)
        </a>
    </nav>
</header>

<!-- ======================================
     HERO
====================================== -->

<section class="hero" id="beranda">

    <div>
        <h1>
            Ayam Segar,
            <span>Belanja Jadi Kilat!</span>
        </h1>

        <p>
            Penjualan ayam segar, berkualitas, dan harga terjangkau.
            Pilih produk favoritmu dan pesan dengan mudah melalui WhatsApp.
        </p>

        <a class="btn" href="#produk">
            Belanja Sekarang
        </a>
    </div>

    <img
        class="hero-img"
        src="https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=1000&q=85"
        alt="Produk ayam segar"
        onerror="this.style.display='none'"
    >

</section>

<!-- ======================================
     DAFTAR PRODUK
====================================== -->

<section class="section" id="produk">

    <div class="section-title">
        <h2>Produk Unggulan Kami</h2>
        <p>
            Pilihan ayam segar untuk kebutuhan keluarga Anda
        </p>
    </div>

    <?php if ($error): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php elseif (empty($produk)): ?>

        <div class="empty">
            Belum ada produk yang tersedia.
        </div>

    <?php else: ?>

        <div class="products">

        <?php foreach ($produk as $item):

            $nama = $item['nama_produk'];

            $foto = $fotoProduk[$nama]
                ?? $fotoProduk['Ayam Potong'];

        ?>

            <article class="card">

                <img
                    src="<?= htmlspecialchars($foto) ?>"
                    alt="<?= htmlspecialchars($nama) ?>"
                    loading="lazy"
                    onerror="this.src='https://placehold.co/800x500?text=AYAM+KILAT'"
                >

                <div class="card-content">

                    <h3>
                        <?= htmlspecialchars($nama) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars($item['deskripsi'] ?? '') ?>
                    </p>

                    <div class="price">
                        Rp<?= number_format(
                            (float)$item['harga'],
                            0,
                            ',',
                            '.'
                        ) ?>/<?= htmlspecialchars($item['satuan'] ?: 'kg') ?>
                    </div>

                    <div class="stock">
                        Stok tersedia:
                        <?= number_format(
                            (float)$item['stok'],
                            0,
                            ',',
                            '.'
                        ) ?>
                        <?= htmlspecialchars($item['satuan'] ?: 'kg') ?>
                    </div>

                    <br>

                    <button
                        class="add-btn"
                        onclick='addToCart(
                            <?= (int)$item["id"] ?>,
                            <?= htmlspecialchars(
                                json_encode(
                                    $nama,
                                    JSON_HEX_APOS |
                                    JSON_HEX_QUOT |
                                    JSON_HEX_TAG |
                                    JSON_HEX_AMP
                                ),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>,
                            <?= (float)$item["harga"] ?>,
                            <?= (float)$item["stok"] ?>,
                            <?= htmlspecialchars(
                                json_encode(
                                    $item["satuan"] ?: "kg",
                                    JSON_HEX_APOS |
                                    JSON_HEX_QUOT
                                ),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        )'
                    >
                        + Tambah ke Keranjang
                    </button>

                </div>

            </article>

        <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<!-- ======================================
     KERANJANG BELANJA
====================================== -->

<section class="section cart-section" id="keranjang">

    <div class="section-title">

        <h2>🛒 Keranjang Belanja</h2>

        <p>
            Periksa pesanan sebelum dikirim ke WhatsApp
        </p>

    </div>

    <div class="cart-box">

        <div id="cart-items">
            <div class="empty">
                Keranjang masih kosong. Silakan pilih produk.
            </div>
        </div>

        <div class="total">
            Total:
            <span id="cart-total">Rp0</span>
        </div>

        <div class="form-grid">

            <input
                type="text"
                id="customer-name"
                placeholder="Nama lengkap"
                maxlength="100"
                required
            >

            <input
                type="tel"
                id="customer-phone"
                placeholder="Nomor HP pembeli"
                maxlength="20"
                required
            >

            <textarea
                id="customer-address"
                placeholder="Alamat lengkap pengiriman"
                required
            ></textarea>

        </div>

        <button
            class="checkout"
            id="checkout-button"
            onclick="checkout()"
        >
            Pesan Sekarang melalui WhatsApp
        </button>

    </div>

</section>

<!-- ======================================
     INFORMASI TOKO
====================================== -->

<section class="info">

    <div>
        <strong>🐔 Ayam Segar</strong>
        Produk pilihan berkualitas
    </div>

    <div>
        <strong>💰 Harga Terjangkau</strong>
        Harga jelas dan bersahabat
    </div>

    <div>
        <strong>📱 Pesan Mudah</strong>
        Pemesanan langsung melalui WhatsApp
    </div>

</section>

<!-- ======================================
     FOOTER
====================================== -->

<footer>

    <h2>🐔 AYAM KILAT</h2>

    <p>
        Penjualan Ayam Segar dan Berkualitas
    </p>

    <p>
        WhatsApp: 085606075703
    </p>

    <p>
        © 2026 AYAM KILAT. Semua Hak Dilindungi.
    </p>

</footer>

<!-- ======================================
     JAVASCRIPT KERANJANG
====================================== -->

<script>

const cart = {};

const whatsappNumber = '6285606075703';

// Format mata uang rupiah.
function rupiah(value) {
    return 'Rp' + Number(value).toLocaleString('id-ID');
}

// Tambahkan produk ke keranjang.
function addToCart(id, name, price, stock, unit) {

    if (stock <= 0) {
        alert('Maaf, stok produk ini sedang kosong.');
        return;
    }

    if (cart[id]) {

        if (cart[id].qty + 1 > stock) {
            alert('Jumlah pesanan melebihi stok yang tersedia.');
            return;
        }

        cart[id].qty++;

    } else {

        cart[id] = {
            id,
            name,
            price,
            stock,
            unit,
            qty: 1
        };

    }

    renderCart();

    document.getElementById('keranjang')
        .scrollIntoView({ behavior: 'smooth' });
}

// Ubah jumlah produk.
function changeQty(id, amount) {

    if (!cart[id]) return;

    const next = cart[id].qty + amount;

    if (next <= 0) {

        delete cart[id];

    } else if (next <= cart[id].stock) {

        cart[id].qty = next;

    } else {

        alert('Jumlah melebihi stok tersedia.');

    }

    renderCart();
}

// Tampilkan isi keranjang.
function renderCart() {

    const items = Object.values(cart);

    const container = document.getElementById('cart-items');

    let total = 0;
    let count = 0;

    if (items.length === 0) {

        container.innerHTML =
            '<div class="empty">Keranjang masih kosong. Silakan pilih produk.</div>';

    } else {

        container.innerHTML = items.map(item => {

            const subtotal = item.price * item.qty;

            total += subtotal;
            count += item.qty;

            return `
                <div class="cart-row">

                    <div>
                        <strong>${escapeHtml(item.name)}</strong>
                        <br>

                        ${item.qty} ${escapeHtml(item.unit)}
                        × ${rupiah(item.price)}

                        <br>

                        <strong>${rupiah(subtotal)}</strong>
                    </div>

                    <div>
                        <button onclick="changeQty(${item.id},-1)">
                            −
                        </button>

                        <strong>${item.qty}</strong>

                        <button onclick="changeQty(${item.id},1)">
                            +
                        </button>
                    </div>

                </div>
            `;

        }).join('');

    }

    document.getElementById('cart-total').textContent =
        rupiah(total);

    document.getElementById('cart-count').textContent =
        count;

}

// Mencegah HTML dimasukkan sebagai teks produk.
function escapeHtml(text) {

    return String(text).replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[char]);

}

// ==========================================
// CHECKOUT KE DATABASE DAN WHATSAPP
// ==========================================

async function checkout() {

    const items = Object.values(cart);

    const name = document
        .getElementById('customer-name')
        .value.trim();

    const phone = document
        .getElementById('customer-phone')
        .value.trim();

    const address = document
        .getElementById('customer-address')
        .value.trim();

    // Periksa keranjang.
    if (items.length === 0) {

        alert('Keranjang belanja masih kosong.');

        return;
    }

    // Periksa data pelanggan.
    if (!name || !phone || !address) {

        alert('Mohon isi nama, nomor HP, dan alamat pengiriman.');

        return;
    }

    // Cegah tombol diklik berulang kali.
    const button = document.getElementById('checkout-button');

    button.disabled = true;

    button.textContent = 'Memproses pesanan...';

    try {

        // Kirim pesanan ke PHP.
        const response = await fetch('checkout.php', {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json'
            },

            body: JSON.stringify({

                name: name,

                phone: phone,

                address: address,

                items: items.map(item => ({

                    id: item.id,

                    qty: item.qty

                }))

            })

        });

        // Baca jawaban dari server.
        const result = await response.json();

        // Periksa keberhasilan transaksi.
        if (!response.ok || !result.success) {

            throw new Error(
                result.message || 'Pesanan gagal disimpan.'
            );

        }

        // Tampilkan kode transaksi.
        alert(
            'PESANAN BERHASIL DISIMPAN!\n\n' +
            'Kode transaksi: ' + result.kode_transaksi +
            '\nTotal: ' + rupiah(result.total) +
            '\n\nAnda akan diarahkan ke WhatsApp.'
        );

        // Kosongkan keranjang setelah transaksi tersimpan.
        for (const id in cart) {
            delete cart[id];
        }

        renderCart();

        // Buka WhatsApp.
        window.location.href = result.whatsapp_url;

    } catch (error) {

        alert(error.message);

        button.disabled = false;

        button.textContent =
            'Pesan Sekarang melalui WhatsApp';

    }

}

</script>

</body>
</html>
