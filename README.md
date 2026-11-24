# AYAM KILAT

Website penjualan ayam segar dan berkualitas.

## Deskripsi
AYAM KILAT merupakan aplikasi web penjualan ayam yang dibuat sebagai proyek mata kuliah Cloud Computing.

## Teknologi
- Ubuntu Server pada VMware
- Docker dan Docker Compose
- PHP 8.2
- MySQL 8.0
- ProxySQL 2.7.3
- Prometheus
- Node Exporter
- Grafana

## Fitur
- Menampilkan produk ayam
- Pemesanan melalui website
- Checkout pelanggan
- Penyimpanan transaksi ke database
- Pengelolaan stok produk
- Integrasi pemesanan WhatsApp

## Menjalankan Aplikasi
1. Salin `.env.example` menjadi `.env`.
2. Isi variabel lingkungan dengan password yang aman.
3. Siapkan database dan konfigurasi ProxySQL.
4. Jalankan Docker Compose menggunakan konfigurasi yang sudah disesuaikan.

## Keamanan
File database, backup, password asli, dan konfigurasi rahasia tidak disertakan dalam repository publik.
