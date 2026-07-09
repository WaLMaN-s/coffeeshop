-- =====================================================
-- LORONG KOPI - Sistem Pemesanan Coffee Shop
-- Database: MariaDB / MySQL
-- Import: mysql -u root < database.sql
-- =====================================================

CREATE DATABASE IF NOT EXISTS lorongkopi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lorongkopi_db;

-- ---------- ADMIN ----------
CREATE TABLE admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- KASIR (akun kasir, terpisah dari admin) ----------
CREATE TABLE kasir (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- KATEGORI ----------
CREATE TABLE kategori (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- MENU ----------
CREATE TABLE menu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kategori_id INT NOT NULL,
  nama VARCHAR(150) NOT NULL,
  harga DECIMAL(12,0) NOT NULL DEFAULT 0,
  deskripsi TEXT,
  tanpa_gula TINYINT(1) NOT NULL DEFAULT 0, -- 1 = tidak menampilkan opsi gula (mis. Espresso, Americano)
  foto VARCHAR(255) DEFAULT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_menu_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id)
) ENGINE=InnoDB;

-- ---------- PELANGGAN (akun lama, dipertahankan untuk data historis) ----------
CREATE TABLE pelanggan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE,
  no_hp VARCHAR(20),
  password VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- MEJA (QR dine-in) ----------
CREATE TABLE meja (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nomor_meja VARCHAR(20) NOT NULL,
  kode VARCHAR(40) NOT NULL UNIQUE,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- PESANAN ----------
CREATE TABLE pesanan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nomor_pesanan VARCHAR(30) NOT NULL UNIQUE,
  pelanggan_id INT NULL,
  meja_id INT NULL,
  nama_tamu VARCHAR(100) NULL,
  sesi_kode VARCHAR(40) NULL,
  total DECIMAL(12,0) NOT NULL DEFAULT 0,
  status ENUM('menunggu','diproses','siap','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu',
  catatan TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pesanan_pelanggan FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id),
  CONSTRAINT fk_pesanan_meja FOREIGN KEY (meja_id) REFERENCES meja(id)
) ENGINE=InnoDB;

CREATE INDEX idx_pesanan_tanggal ON pesanan (created_at);
CREATE INDEX idx_pesanan_status ON pesanan (status);

-- ---------- ITEM PESANAN ----------
CREATE TABLE pesanan_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pesanan_id INT NOT NULL,
  menu_id INT NOT NULL,
  opsi VARCHAR(255) DEFAULT NULL, -- contoh: "Large - Dingin - No Sugar"
  jumlah INT NOT NULL DEFAULT 1,
  harga DECIMAL(12,0) NOT NULL DEFAULT 0,
  CONSTRAINT fk_item_pesanan FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
  CONSTRAINT fk_item_menu FOREIGN KEY (menu_id) REFERENCES menu(id)
) ENGINE=InnoDB;

-- ---------- PEMBAYARAN ----------
CREATE TABLE pembayaran (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pesanan_id INT NOT NULL,
  metode ENUM('cash','qris') NOT NULL DEFAULT 'cash',
  jumlah DECIMAL(12,0) NOT NULL DEFAULT 0,
  status ENUM('belum_dibayar','sudah_dibayar','gagal') NOT NULL DEFAULT 'belum_dibayar',
  tanggal_bayar DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bayar_pesanan FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_bayar_status ON pembayaran (status);
CREATE INDEX idx_bayar_tanggal ON pembayaran (tanggal_bayar);

-- ---------- PENGATURAN TOKO ----------
CREATE TABLE pengaturan (
  id INT PRIMARY KEY,
  nama_toko VARCHAR(150) NOT NULL,
  logo VARCHAR(255) DEFAULT NULL,
  banner VARCHAR(255) DEFAULT NULL,
  alamat TEXT,
  whatsapp VARCHAR(25),
  jam_operasional VARCHAR(100),
  deskripsi TEXT,
  wifi_ssid VARCHAR(100) DEFAULT NULL,
  wifi_password VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB;

-- ---------- NOTIFIKASI ----------
CREATE TABLE notifikasi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipe ENUM('pesanan_baru','pembayaran','pesanan_batal') NOT NULL,
  pesan VARCHAR(255) NOT NULL,
  pesanan_id INT NULL,
  dibaca TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- SEED DATA
-- =====================================================

-- Login: admin / admin123
INSERT INTO admin (username, password, nama) VALUES
('admin', '$2y$10$W8xTDE80WMlL.JrAH8ybZufjP10djhImhj0gHHnH8C182XJpQ9a3G', 'Administrator');

-- Login kasir: kasir / kasir123
INSERT INTO kasir (username, password, nama) VALUES
('kasir', '$2y$12$R/Pm4ZQfeyEJblCgM/rynuuhpE1c/91d4mNKXlIzTbik5GWLexhge', 'Kasir');

INSERT INTO kategori (nama) VALUES
('Coffee'), ('Non Coffee'), ('Tea'), ('Snack');

INSERT INTO menu (kategori_id, nama, harga, deskripsi, tanpa_gula, status) VALUES
(1, 'Espresso', 15000, 'Single shot espresso dari biji arabika pilihan.', 1, 'aktif'),
(1, 'Americano', 18000, 'Espresso dengan air panas, rasa bold dan clean.', 1, 'aktif'),
(1, 'Cafe Latte', 25000, 'Espresso dengan steamed milk yang creamy.', 0, 'aktif'),
(1, 'Cappuccino', 25000, 'Espresso, steamed milk, dan foam tebal.', 0, 'aktif'),
(1, 'Kopi Susu Gula Aren', 22000, 'Es kopi susu dengan gula aren asli.', 0, 'aktif'),
(1, 'Caramel Macchiato', 28000, 'Espresso, susu, dan saus karamel manis.', 0, 'aktif'),
(1, 'V60 Manual Brew', 24000, 'Seduh manual V60, single origin nusantara.', 0, 'aktif'),
(1, 'Cold Brew', 23000, 'Kopi seduh dingin 12 jam, smooth dan segar.', 0, 'aktif'),
(2, 'Chocolate', 23000, 'Cokelat premium dengan susu segar.', 0, 'aktif'),
(2, 'Matcha Latte', 26000, 'Matcha grade premium dengan susu.', 0, 'aktif'),
(2, 'Red Velvet Latte', 24000, 'Red velvet creamy dengan susu segar.', 0, 'aktif'),
(3, 'Lemon Tea', 15000, 'Teh segar dengan perasan lemon asli.', 0, 'aktif'),
(3, 'Lychee Tea', 18000, 'Teh dengan buah leci segar.', 0, 'aktif'),
(3, 'Teh Tarik', 16000, 'Teh susu khas dengan tarikan creamy.', 0, 'aktif'),
(4, 'French Fries', 18000, 'Kentang goreng renyah dengan saus.', 0, 'aktif'),
(4, 'Pisang Goreng Keju', 17000, 'Pisang goreng dengan topping keju dan cokelat.', 0, 'aktif'),
(4, 'Croissant', 20000, 'Croissant butter renyah, hangat.', 0, 'aktif'),
(4, 'Roti Bakar Cokelat Keju', 19000, 'Roti bakar dengan cokelat dan keju melimpah.', 0, 'aktif'),
(4, 'Donat Gula', 12000, 'Donat empuk dengan taburan gula halus.', 0, 'aktif');

INSERT INTO pengaturan (id, nama_toko, alamat, whatsapp, jam_operasional, deskripsi, wifi_ssid, wifi_password) VALUES
(1, 'Lorong Kopi', 'Jl. Margasatwa No.9, Cilandak Timur, Pasar Minggu, Kota Jakarta Selatan, DKI Jakarta 12560', '6287878778748', '07.00 - 23.00 WIB', 'Kedai kopi dengan suasana hangat, kopi berkualitas, dan harga bersahabat.', 'LorongKopi-Guest', 'ngopidulu123');

-- 10 meja siap pakai — cetak QR-nya dari Admin > Meja
INSERT INTO meja (nomor_meja, kode, status) VALUES
('1',  '3d7ac1f082afe7a1', 'aktif'),
('2',  '206190c53d2bb852', 'aktif'),
('3',  '4e918646da1126ff', 'aktif'),
('4',  '342cbf53ef66d14a', 'aktif'),
('5',  '25bdf2e71c4bed2c', 'aktif'),
('6',  '82e8f9533e1c5a33', 'aktif'),
('7',  '1fbda39c57778b99', 'aktif'),
('8',  'a4883e26b29feb15', 'aktif'),
('9',  '8d17376e53ce6592', 'aktif'),
('10', 'aafb9043386c03b7', 'aktif');
