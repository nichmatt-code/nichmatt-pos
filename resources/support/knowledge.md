# Panduan NichmattPOS (basis pengetahuan asisten bantuan)

NichmattPOS adalah aplikasi kasir (POS) berbasis web untuk toko/kafe/restoran, dengan aplikasi kasir mobile pendamping. Setiap toko (cabang) punya data sendiri; data toko lain tidak pernah terlihat.

## Peran & hak akses
- Pemilik (owner) bisa mengakses semua menu.
- Karyawan (kasir) hanya bisa membuka menu yang diizinkan pemilik lewat menu Karyawan. Izin yang ada: Dashboard, Produk, Kategori, Inventory/Gudang, Stock Opname, Laporan Penjualan, Persiapan Pesanan, Kelola Karyawan, Pengaturan Cabang, Data Pelanggan.
- Kalau sebuah menu tidak muncul atau muncul "403 Forbidden", artinya akun itu belum diberi izin; minta pemilik toko menambahkannya di menu Administrasi > Karyawan.

## Menu utama (navigasi atas; di HP klik ikon garis tiga)
- Dashboard: ringkasan penjualan.
- Kasir: layar transaksi utama.
- Persiapan: daftar pesanan yang perlu disiapkan dapur/bar.
- Stock Opname: hitung stok fisik dan sesuaikan dengan stok sistem.
- Product: Produk, Kategori, Inventory (bahan baku/gudang), Satuan, Tags, Paket, Kupon.
- Administrasi: Pelanggan, Karyawan, Cabang.
- Laporan: laporan penjualan dan laporan stok/kerugian.
- Ikon bulan/matahari di kanan atas mengganti tema gelap/terang. Ikon layar penuh membuat tampilan fullscreen.

## Kasir (transaksi)
1. Buka menu Kasir.
2. Cari produk lewat kolom "Cari produk...", atau scan barcode di kolom "Scan barcode produk...". Filter cepat memakai tombol kategori dan tags. Produk dikelompokkan per kategori.
3. Klik produk untuk menambah ke keranjang di sisi kanan; atur jumlah dengan tombol + / -. Harga dan catatan per item bisa diubah kalau pemilik mengaktifkan pengaturan edit harga di menu Cabang.
4. Isi nama pelanggan/member (opsional; ketik untuk mencari pelanggan terdaftar) dan catatan pesanan (opsional).
5. Punya kupon? Masukkan kode kupon lalu terapkan; diskon dihitung otomatis.
6. "Cetak Bill" menampilkan bill sementara (belum dibayar) untuk ditunjukkan ke pelanggan.
7. Klik Bayar, pilih metode: Tunai, QRIS, atau Kartu. Untuk tunai isi uang diterima, kembalian dihitung otomatis. Setelah berhasil muncul struk yang bisa dicetak.
- Ikon kotak QR di sebelah kolom barcode membuka QR Self Order: pelanggan scan QR itu untuk memesan sendiri dari HP mereka; kasir memasukkan kode pesanan (6 karakter) untuk mengambil pesanan tersebut ke keranjang.
- Ikon tanda seru di sebelah kolom barcode = Catat Kerugian: mencatat barang rusak/hilang/kadaluarsa. Stok berkurang tanpa dihitung sebagai penjualan. Isi produk, jumlah, dan alasan.

## Produk, Kategori, Tags, Paket, Kupon
- Produk: tambah/ubah produk (nama, harga jual, harga pokok, stok, satuan, kategori, foto, barcode). Produk bisa diberi stok tak terbatas. Ada cetak label produk dengan barcode.
- Kategori: pengelompokan produk di layar kasir.
- Tags: label tambahan untuk filter cepat di kasir.
- Paket: gabungan beberapa produk yang dijual sebagai satu paket.
- Kupon: kode diskon yang dimasukkan kasir saat transaksi.

## Inventory & Satuan
- Inventory: bahan baku/gudang dengan stok dan batas minimum stok. Pemakaian bahan baku bisa dikaitkan ke produk sehingga stok bahan berkurang otomatis saat produk terjual.
- Satuan: daftar satuan (kg, pcs, liter, dll) yang dipakai produk dan inventory.

## Stock Opname
1. Menu Stock Opname > buat sesi baru, pilih jenis: Produk atau Bahan Baku.
2. Isi jumlah fisik hasil hitung untuk tiap item (boleh disimpan bertahap).
3. Klik Selesaikan: stok sistem otomatis disesuaikan dengan hasil hitung dan tercatat sebagai penyesuaian. Setelah selesai sesi tidak bisa diubah. Sesi draft masih bisa dihapus.

## Pelanggan
Menu Administrasi > Pelanggan: daftar pelanggan/member (nama, nomor telepon, alamat, tanggal lahir, catatan). Nomor telepon harus unik di satu toko. Jumlah transaksi per pelanggan ditampilkan. Pelanggan bisa dipilih di keranjang kasir agar transaksi tercatat atas namanya.

## Karyawan & Cabang
- Karyawan: tambah akun karyawan dan atur izin akses menunya.
- Cabang: pengaturan toko (nama, pajak, service charge, tampilan struk, pengaturan edit harga, QRIS, dll).

## Laporan
Laporan penjualan dan laporan stok (stock opname, monitor inventory, kerugian inventory, riwayat kerugian). Butuh izin Laporan.

## Langganan
Toko mendapat masa trial; setelah itu perlu berlangganan lewat tombol Subscription/Berlangganan. Jika trial habis, menu akan mengarahkan ke halaman berlangganan.

## Aplikasi kasir mobile
Ada aplikasi kasir mobile (Android/iOS lewat Expo Go) dengan login email/password atau Google. Fitur: kasir (produk per kategori, keranjang, pelanggan, catatan, kupon, self order, cetak bill/struk), riwayat transaksi, catat kerugian, stock opname, dan kelola pelanggan (sesuai izin akun), plus mode gelap.

## Hal yang perlu dialihkan ke tim developer
Bug/error, masalah pembayaran atau tagihan langganan, permintaan mengubah/menghapus data secara massal, fitur yang belum ada, dan hal lain yang tidak ada di panduan ini.
