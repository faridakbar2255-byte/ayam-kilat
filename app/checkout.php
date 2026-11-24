<?php
header('Content-Type: application/json; charset=utf-8');

function response($success, $message, $extra = []) {
    echo json_encode(
        array_merge(['success' => $success, 'message' => $message], $extra),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(false, 'Metode permintaan tidak diizinkan.');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = null;

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        throw new Exception('Data pesanan tidak valid.');
    }

    $name = trim($input['name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $address = trim($input['address'] ?? '');
    $items = $input['items'] ?? [];

    if ($name === '' || $phone === '' || $address === '') {
        throw new Exception('Nama, nomor HP, dan alamat wajib diisi.');
    }

    if (mb_strlen($name) > 100 || mb_strlen($phone) > 20) {
        throw new Exception('Nama atau nomor HP terlalu panjang.');
    }

    if (!is_array($items) || count($items) < 1 || count($items) > 50) {
        throw new Exception('Daftar produk tidak valid.');
    }

    // Gabungkan produk yang memiliki ID sama.
    $quantities = [];

    foreach ($items as $item) {
        $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
        $qty = filter_var($item['qty'] ?? null, FILTER_VALIDATE_FLOAT);

        if (!$id || $id < 1 || $qty === false || $qty <= 0 || $qty > 10000) {
            throw new Exception('ID produk atau jumlah pesanan tidak valid.');
        }

        $quantities[$id] = ($quantities[$id] ?? 0) + $qty;
    }

    ksort($quantities);

    // Koneksi database melalui ProxySQL.
    $dbHost = getenv('DB_HOST') ?: 'proxysql';
    $dbPort = (int)(getenv('DB_PORT') ?: 6033);
    $dbName = getenv('DB_NAME') ?: 'db_ayam_kilat';
    $dbUser = getenv('DB_USER') ?: 'ayam_user';
    $dbPass = getenv('DB_PASS') ?: '';

    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    $conn->set_charset('utf8mb4');

    $conn->begin_transaction();

    $orderItems = [];
    $total = 0;

    // Kunci stok produk selama transaksi berlangsung.
    $select = $conn->prepare(
        'SELECT nama_produk, harga, stok, satuan
         FROM produk WHERE id = ? FOR UPDATE'
    );

    foreach ($quantities as $id => $qty) {
        $select->bind_param('i', $id);
        $select->execute();

        $product = $select->get_result()->fetch_assoc();

        if (!$product) {
            throw new Exception('Salah satu produk tidak ditemukan.');
        }

        if ($qty > (float)$product['stok']) {
            throw new Exception(
                'Stok ' . $product['nama_produk'] . ' tidak mencukupi.'
            );
        }

        $price = (float)$product['harga'];
        $subtotal = $price * $qty;
        $total += $subtotal;

        $orderItems[] = [
            'id' => (int)$id,
            'name' => $product['nama_produk'],
            'qty' => $qty,
            'price' => $price,
            'unit' => $product['satuan'] ?: 'kg',
            'subtotal' => $subtotal
        ];
    }

    $select->close();

    // Simpan pelanggan.
    $customer = $conn->prepare(
        'INSERT INTO pelanggan (nama, telepon, alamat)
         VALUES (?, ?, ?)'
    );
    $customer->bind_param('sss', $name, $phone, $address);
    $customer->execute();

    $customerId = $conn->insert_id;
    $customer->close();

    // Buat kode transaksi unik.
    $code = 'AK-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $status = 'Menunggu Konfirmasi';

    // Simpan transaksi utama.
    $transaction = $conn->prepare(
        'INSERT INTO transaksi
         (kode_transaksi, pelanggan_id, total, status)
         VALUES (?, ?, ?, ?)'
    );
    $transaction->bind_param('sids', $code, $customerId, $total, $status);
    $transaction->execute();

    $transactionId = $conn->insert_id;
    $transaction->close();

    // Simpan rincian produk dan kurangi stok.
    $detail = $conn->prepare(
        'INSERT INTO detail_transaksi
         (transaksi_id, produk_id, jumlah, harga, subtotal)
         VALUES (?, ?, ?, ?, ?)'
    );

    $updateStock = $conn->prepare(
        'UPDATE produk SET stok = stok - ?
         WHERE id = ? AND stok >= ?'
    );

    foreach ($orderItems as $item) {
        $productId = $item['id'];
        $qty = $item['qty'];
        $price = $item['price'];
        $subtotal = $item['subtotal'];

        $detail->bind_param(
            'iiddd',
            $transactionId,
            $productId,
            $qty,
            $price,
            $subtotal
        );
        $detail->execute();

        $updateStock->bind_param('did', $qty, $productId, $qty);
        $updateStock->execute();

        if ($updateStock->affected_rows !== 1) {
            throw new Exception('Stok berubah. Silakan ulangi pesanan.');
        }
    }

    $detail->close();
    $updateStock->close();

    // Semua penyimpanan berhasil.
    $conn->commit();

    // Susun pesan WhatsApp berdasarkan data database.
    $message = "Halo AYAM KILAT, saya ingin mengonfirmasi pesanan.\n\n";
    $message .= "Kode Transaksi: " . $code . "\n";
    $message .= "Nama: " . $name . "\n";
    $message .= "Nomor HP: " . $phone . "\n";
    $message .= "Alamat: " . $address . "\n\n";
    $message .= "Daftar Pesanan:\n";

    foreach ($orderItems as $item) {
        $message .= '- ' . $item['name'] . ': '
            . $item['qty'] . ' ' . $item['unit']
            . ' x Rp' . number_format($item['price'], 0, ',', '.')
            . ' = Rp' . number_format($item['subtotal'], 0, ',', '.')
            . "\n";
    }

    $message .= "\nTotal: Rp" . number_format($total, 0, ',', '.');
    $message .= "\nStatus: " . $status;
    $message .= "\n\nTerima kasih.";

    $whatsappUrl = 'https://wa.me/6285606075703?text='
        . rawurlencode($message);

    response(true, 'Pesanan berhasil disimpan.', [
        'kode_transaksi' => $code,
        'total' => $total,
        'whatsapp_url' => $whatsappUrl
    ]);

} catch (Throwable $e) {
    if ($conn instanceof mysqli) {
        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }
    }

    error_log('AYAM KILAT checkout: ' . $e->getMessage());

    http_response_code(400);
    response(false, 'Pesanan gagal diproses: ' . $e->getMessage());

} finally {
    if ($conn instanceof mysqli) {
        $conn->close();
    }
}
