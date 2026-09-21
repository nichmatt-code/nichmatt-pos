# Panduan NichmattPOS (basis pengetahuan asisten bantuan)

NichmattPOS adalah aplikasi kasir (POS) berbasis web untuk toko/kafe/resto, dengan aplikasi kasir mobile pendamping. Fitur utama: kasir cepat, self-order pelanggan lewat QR, inventory dengan resep otomatis, stock opname, kupon, data pelanggan, dashboard dan laporan, manajemen karyawan. Data tiap toko terpisah; data toko lain tidak pernah terlihat.

## Peran, izin akses, dan menu
- Ada dua peran: Owner (akses penuh ke semua menu) dan Karyawan (hanya menu yang dicentang owner).
- Menu atas: Dashboard; Kasir (Kasir, Persiapan, Stock Opname); Product (Produk, Kategori, Inventory, Satuan, Tags, Paket, Kupon); Administrasi (Pelanggan, Karyawan, Cabang); Laporan; tombol Subscription (hanya owner). Menu akun di kanan atas: Profil, Ganti Akun, + Tambah Akun Lain, Keluar. Di HP semua menu ada di ikon garis tiga. Ikon bulan/matahari mengganti tema gelap/terang.
- Izin karyawan: Dashboard, Produk, Kategori, Inventory / Gudang, Stock Opname, Laporan Penjualan, Persiapan Pesanan, Kelola Karyawan, Pengaturan Cabang, Data Pelanggan. Izin "Produk" juga membuka Tags, Paket, dan Kupon. Izin "Inventory" juga membuka Satuan. Izin "Laporan Penjualan" membuka semua tab Laporan.
- Halaman Kasir selalu bisa dibuka semua akun yang login. Setelah login, semua orang diarahkan ke halaman Kasir.
- Kalau membuka menu tanpa izin, muncul "403 Forbidden". Solusi: minta owner mencentang izinnya di Administrasi > Karyawan > Detail > Izin Akses Tambahan. Karyawan tanpa izin Dashboard yang membuka Dashboard dialihkan ke Kasir.

## Dashboard
Menu Dashboard (butuh izin Dashboard). Isinya: tabel Ringkasan (Omzet, Jumlah Transaksi, Kerugian Tercatat, Inventory Hilang untuk Hari Ini / Minggu Ini / Bulan Ini), Akses Cepat (Buka Kasir, Kelola Produk, Laporan), Produk Terlaris Bulan Ini (5 teratas), Tren Penjualan 14 Hari Terakhir, Pemakaian Inventory Hari Ini, Inventory Menipis (stok kurang dari atau sama dengan stok minimum), dan Stok Produk Menipis (stok 5 atau kurang, angka ini tetap). Hanya transaksi yang sudah selesai dihitung. Tidak ada filter tanggal dan tidak ada ekspor di Dashboard.

## Kasir: mencari dan menambah produk
1. Buka Kasir > Kasir.
2. Cari produk lewat kolom "Cari produk..." (berdasarkan nama), atau scan barcode di kolom "Scan barcode produk..." (alat scan mengetik kode lalu Enter; cocok dengan barcode atau SKU produk aktif, langsung masuk keranjang qty 1). Kalau tidak ketemu muncul pesan kode tidak ditemukan.
3. Filter dengan tombol "Semua" dan kategori, serta tombol tag (boleh lebih dari satu). Produk dikelompokkan per kategori; yang tanpa kategori masuk "Tanpa Kategori". Paket tampil di bagian "Paket".
4. Maksimal 40 produk tampil sekaligus; pakai pencarian atau filter untuk produk lainnya. Hanya produk aktif yang tampil.
5. Klik produk untuk membuka detail: atur jumlah dan isi catatan item (mis. tanpa gula), lalu "Tambahkan". Jumlah dibatasi stok (produk stok tak terbatas tidak dibatasi). Produk berstatus "Habis" atau stok 0 tidak bisa ditambah ("Stok ... habis.").
6. Klik paket untuk menambah 1 paket langsung. Kalau salah satu isi paket habis muncul "Stok untuk Paket ... tidak cukup."
7. Di keranjang, tombol - dan + mengubah jumlah; menekan - saat jumlah 1 menghapus baris. Tiap baris punya kolom catatan.
8. Harga per item hanya bisa diubah kalau owner mengaktifkan "Kasir bisa mengubah harga saat transaksi" di Cabang. Harga item paket dan hadiah kupon tidak bisa diubah.

## Kasir: pelanggan, catatan, diskon, kupon, dan total
- Kolom Customer (opsional): ketik nama atau nomor HP untuk memilih pelanggan terdaftar dari saran (maks 5), atau ketik nama bebas. Nama bebas hanya tersimpan di transaksi dan TIDAK menjadi data Pelanggan baru. Tombol "Ganti" melepas pilihan.
- Catatan Order (opsional, maks 255 karakter) tampil di halaman Persiapan.
- Diskon (Rp): diskon manual, tidak boleh melebihi subtotal.
- Kupon: isi kode (otomatis huruf besar) lalu "Terapkan". Hanya satu kupon per transaksi; "Hapus" untuk melepas. Pesan galat: "Kupon tidak ditemukan.", "Kupon sudah tidak berlaku.", dan untuk kupon umur "Pilih customer dengan tanggal lahir tercatat untuk pakai kupon ini." Kupon hadiah menambah baris produk "(Hadiah Kupon)" berharga 0 kalau stoknya ada. Kupon hanya bisa dipakai kasir, tidak bisa dipakai di Self Order.
- Urutan total: subtotal, dikurangi diskon manual dan diskon kupon, ditambah Service Charge (persen dari hasil itu), lalu ditambah Pajak (persen dari hasil sebelumnya). Service Charge dan Pajak hanya tampil kalau persentasenya lebih dari 0 (diatur di Cabang).

## Kasir: pembayaran, bill, dan struk
- Cetak Bill: membuka tab "BILL SEMENTARA - BELUM DIBAYAR" untuk ditunjukkan ke pelanggan. Tidak menyimpan transaksi dan tidak mengurangi stok.
- Metode pembayaran: Tunai, QRIS, Kartu. Tunai: isi "Uang Diterima", kembalian dihitung otomatis; kalau kurang muncul "Jumlah bayar kurang dari total." QRIS dan Kartu di dropdown hanya pencatatan manual (dianggap dibayar pas; kasir memastikan uang sudah masuk sendiri, mis. lewat QRIS statis atau mesin EDC).
- Klik "Konfirmasi Pembayaran", lalu konfirmasi dialog. Transaksi langsung tersimpan dan muncul "Transaksi Berhasil" dengan nomor TRX-..., total, dan kembalian (tunai), plus tombol "Cetak Struk" dan "Transaksi Baru".
- Efek saat transaksi tersimpan: stok produk berkurang (kecuali stok tak terbatas), stok bahan Inventory berkurang sesuai resep produk, stok isi paket berkurang, dan kalau dari Self Order statusnya menjadi selesai.
- Keranjang kosong menampilkan "Keranjang masih kosong."

## Kasir: QRIS online (Midtrans)
Tombol "Bayar QRIS Online" hanya muncul kalau owner sudah mengaktifkan "Aktifkan pembayaran QRIS online di Kasir" dan menyimpan Server Key Midtrans di Administrasi > Cabang > Pembayaran Online (QRIS). Uang masuk langsung ke akun Midtrans toko. Alur: klik tombol, muncul QR "Scan untuk Bayar", pelanggan scan pakai e-wallet/m-banking, status "Menunggu pembayaran..." dicek otomatis tiap 3 detik, dan begitu lunas transaksi otomatis tersimpan. QR berlaku 15 menit; setelah itu muncul "QR sudah kedaluwarsa." Tombol "Batalkan" membatalkan QR. Transaksi baru dibuat setelah pembayaran lunas.

## Cetak struk, thermal, dan Bluetooth
Format struk diatur di Cabang > Format Struk: "Printer Thermal" (lebar 58mm atau 80mm; tombol Cetak dan "Cetak via Bluetooth") atau "Invoice / PDF" (untuk dicetak atau disimpan PDF, tanpa tombol Bluetooth). Cetak via Bluetooth memakai Web Bluetooth di browser dan hanya muncul di browser yang mendukung (Chrome/Edge di Android atau komputer); Safari/iOS tidak mendukung. Footer struk default "Terima kasih telah berbelanja" dan bisa diubah di Cabang. Struk juga bisa dicetak ulang dari Laporan > Penjualan > Riwayat Transaksi.

## Self Order (pesan sendiri lewat QR)
- Owner membagikan link/QR dari Administrasi > Cabang > Link Self Order (Salin Link, Lihat QR) atau ikon "QR Self Order" di Kasir. Halaman QR punya "Cetak / Simpan PDF" dan "Download Gambar (PNG)".
- Pelanggan (tanpa login) scan QR, cari/pilih menu, isi jumlah dan catatan, buka "Pesanan Anda", isi nama dan catatan (opsional), klik "Konfirmasi Pesanan". Mereka mendapat "Kode Pesanan" 6 karakter (contoh A3F9K2) berlaku 60 menit, lalu membayar ke kasir. Self Order tidak menerima pembayaran online. Total di layar pelanggan belum termasuk pajak dan service charge.
- Kasir: di Kasir isi kolom "Input Kode Self Order (Untuk Pembayaran)" (atau scan barcode kode), klik "Ambil". Isi pesanan masuk keranjang; nama dan catatan ikut terisi. Galat: "Kode tidak ditemukan.", "Kode sudah kedaluwarsa.", "Kode sudah pernah dipakai." Menu yang sudah tidak tersedia dilewati.
- Kode hanya sekali pakai: begitu "Ambil", kode tidak bisa dipakai lagi walaupun pembayaran dibatalkan. Pesanan Self Order baru muncul di Persiapan setelah dibayar di Kasir.

## Catat Kerugian
Untuk barang rusak, jatuh, atau kadaluarsa: stok berkurang tanpa dianggap penjualan. Langkah: klik ikon Catat Kerugian di Kasir, ketik nama produk lalu pilih dari hasil, atur jumlah, isi alasan (wajib, maks 255 karakter, mis. Gelas pecah), lalu klik "Catat & Cetak". Nilai kerugian dihitung dari harga modal dikali jumlah. Stok produk dan bahan resep berkurang, dan nota LOSS-... terbuka di tab baru. Tidak masuk laporan penjualan; lihat di Laporan > Riwayat Kerugian dan Dashboard.

## Persiapan
Menu Kasir > Persiapan (izin Persiapan Pesanan), diperbarui otomatis tiap 5 detik. "Perlu Diproses" berisi semua transaksi yang sudah dibayar dan belum ditandai siap (dari kasir maupun Self Order), lengkap dengan nama customer, item beserta catatan, dan catatan order. Klik kartu lalu "Tandai Selesai" untuk memindahkannya ke "Sudah Siap" (20 terakhir hari ini); "Batalkan Selesai" mengembalikannya. Tidak ada notifikasi ke pelanggan.

## Stock Opname
Menu Kasir > Stock Opname (izin Stock Opname; ada juga di aplikasi mobile).
1. Klik "Mulai Stock Opname", pilih "Hitung Stok Untuk": Produk atau Inventory, isi catatan (opsional), klik "Mulai". Sistem mencatat stok sistem saat itu (produk stok tak terbatas dilewati).
2. Isi hasil hitung fisik tiap item; tersimpan otomatis. Item yang belum diisi berlabel "Belum dihitung".
3. Klik "Selesaikan Stock Opname" dan konfirmasi. Stok tiap item yang sudah dihitung diganti sesuai hitungan fisik dan tercatat sebagai penyesuaian. Item yang dikosongkan tidak berubah. Setelah selesai sesi tidak bisa diubah atau dihapus; sesi yang masih "Berjalan" bisa dihapus.
Kekurangan stok Inventory masuk ke Dashboard (Inventory Hilang) dan Laporan.

## Produk
Menu Product > Produk (izin Produk). Daftar bisa dicari, difilter kategori/status (Stok Menipis, Stok Habis), dengan ikon: Cetak Label, Tandai Habis / Tersedia Lagi, Atur Stok, Edit, Hapus.
Form Tambah Produk: Foto Produk (maks 2 MB), Nama Produk (wajib), Deskripsi, Kategori, Satuan (wajib, teks bebas mis. pcs, kg), Tag (centang atau tambah baru), Bahan / Inventory yang Terpakai (resep), SKU dan Barcode (opsional, unik per toko, ada tombol "Generate"), Harga Modal (wajib), Harga Jual (wajib), Stok tak terbatas, Tandai habis sekarang, Stok Awal. Semua harga bilangan bulat rupiah.
- Resep: centang bahan Inventory yang dipakai dan isi jumlah per porsi; setiap produk terjual, stok bahan berkurang otomatis. Bahan baru bisa dibuat langsung dari form ini.
- Atur Stok: pilih "Stok Masuk", "Stok Keluar", atau "Set Stok ke Jumlah Ini", isi jumlah dan catatan; dicatat sebagai pergerakan stok.
- Cetak Label membuka label barcode; hanya bisa kalau produk punya Barcode (buat dengan tombol Generate).
- "Habis" adalah tanda manual yang menghalangi penjualan walau stok ada.

## Kategori, Tags, Paket
- Kategori (Product > Kategori, izin Kategori): tambah/edit nama kategori; tabel menampilkan jumlah produk. Dipakai untuk mengelompokkan produk di Kasir dan Self Order.
- Tags (Product > Tags): label tambahan untuk memfilter produk di Kasir dan Self Order; bisa juga dibuat langsung dari form Produk.
- Paket (Product > Paket): gabungan beberapa produk dengan satu harga. Isi: Foto Paket (opsional), Nama Paket, Deskripsi, Produk dalam Paket (minimal 1, dengan jumlah per paket), Harga Jual Paket, dan centang "Aktifkan paket ini di Kasir & Self Order". Paket tidak punya stok sendiri: ketersediaan mengikuti isinya, dan stok tiap produk isi berkurang saat paket terjual.

## Kupon
Menu Product > Kupon (izin Produk). Klik "Tambah Kupon". Isian: Nama Kupon, Kode Kupon (unik per toko, tombol Generate, disimpan huruf besar), diskon harga (Persen % atau Nominal Rp dengan Nilai Dasar), opsi nilai dinamis berdasar umur pelanggan (Pengali per Tahun Umur; nilai akhir = Nilai Dasar + umur x pengali; butuh pelanggan terdaftar bertanggal lahir), hadiah produk gratis (Produk Hadiah dan Jumlah Hadiah), periode Mulai/Berakhir (opsional; hari terakhir masih berlaku), dan "Aktifkan kupon ini". Kupon harus punya diskon dan/atau hadiah. Diskon tidak melebihi subtotal. TIDAK ada fitur minimum belanja, batas jumlah pemakaian, atau batas satu per pelanggan.

## Inventory dan Satuan
- Inventory (Product > Inventory, izin Inventory / Gudang) adalah bahan baku/gudang. Klik "Tambah Barang": Nama Barang, Kode (opsional, unik, tombol Generate), Satuan (pilih dari daftar atau tambah baru), Stok Saat Ini, Stok Minimum, Harga Modal per satuan, Catatan. Setelah dibuat, stok hanya diubah lewat tombol "Atur Stok" (Stok Masuk / Stok Keluar / Set Stok) atau Stock Opname. Stok menipis = stok kurang dari atau sama dengan Stok Minimum (filter "Stok menipis saja"). Harga Modal dipakai menghitung nilai kerugian di laporan.
- Penjualan dan Catat Kerugian mengurangi bahan sesuai resep produk. Stok bahan bisa menjadi negatif tanpa peringatan.
- Satuan (Product > Satuan): daftar satuan (kg, gram, liter, pcs...) unik per toko, dipakai sebagai pilihan di form Inventory. Field Satuan di form Produk adalah teks bebas.

## Pelanggan
Menu Administrasi > Pelanggan (izin Data Pelanggan; ada juga di aplikasi mobile). Cari lewat nama atau nomor HP. Klik "Tambah Pelanggan": Nama (wajib), Nomor HP (wajib, unik per toko), Alamat, Tanggal Lahir (opsional, harus sebelum hari ini, dipakai kupon umur), Catatan. Tabel menampilkan Total Transaksi tiap pelanggan. "Edit" dan "Hapus" tersedia; hapus tidak menghapus transaksi lamanya. Di Kasir pelanggan terdaftar dipilih lewat kolom Customer agar transaksi tercatat atas namanya. Kalau nomor HP sudah dipakai pelanggan lain di toko yang sama, penyimpanan ditolak.

## Karyawan
Menu Administrasi > Karyawan (izin Kelola Karyawan; mengundang dan mengubah hanya untuk owner). Karyawan ditambah lewat undangan email: klik "Undang Karyawan", isi Email, pilih Role (Karyawan atau Owner), centang Izin Akses Tambahan untuk Karyawan, klik "Kirim Undangan". Link undangan berlaku 7 hari dan bisa disalin dengan tombol "Salin" kalau email belum masuk. Karyawan membuka link, mengisi Nama Lengkap dan Password, lalu langsung masuk. Undangan menunggu bisa "Kirim Ulang" atau "Batalkan". "Detail" membuka pengaturan role, izin, dan "Nonaktifkan / Aktifkan Akun".
Aturan: email yang sudah terdaftar sebagai pengguna di toko mana pun tidak bisa diundang ("Email ini sudah terdaftar sebagai pengguna."); tidak bisa mengubah role/izin akun sendiri; toko harus punya minimal satu owner aktif; akun nonaktif tidak bisa login.

## Cabang (pengaturan toko)
Menu Administrasi > Cabang (izin Pengaturan Cabang). Setiap kartu punya tombol simpan sendiri.
- Status Langganan: menampilkan masa trial/aktif dan tombol "Perpanjang Langganan".
- Informasi Cabang: nama toko, alamat, telepon (tampil di struk).
- Logo Toko: unggah logo (maks 2 MB) untuk bill/struk/invoice, dan pilih "Logo yang Ditampilkan di Aplikasi" (Logo NichmattPOS atau Logo Toko Saya).
- Format Struk: Printer Thermal (58mm/80mm) atau Invoice / PDF, dan pesan footer struk.
- Pengaturan Kasir: Tampilkan gambar produk; Kasir bisa mengubah harga saat transaksi; Pajak (%) dan Service Charge (%) berupa bilangan bulat 0 sampai 100 (tidak bisa desimal).
- Pembayaran Online (QRIS): aktifkan QRIS online, isi Server Key (disimpan terenkripsi, kosongkan untuk tetap memakai yang lama), Client Key (opsional), dan "Mode Production" (matikan untuk uji coba Sandbox). Kunci diambil dari akun Midtrans (Settings > Access Keys).
- Link Self Order: Salin Link dan Lihat QR.

## Laporan
Menu Laporan (izin Laporan Penjualan) dengan lima tab dan filter "Dari Tanggal" / "Sampai Tanggal" (default awal bulan sampai hari ini). Tidak ada fitur ekspor CSV/Excel/PDF.
- Penjualan: Total Omzet, Jumlah Transaksi, Estimasi Laba (harga jual dikurangi harga modal dikali jumlah, belum mengurangi diskon/pajak), Produk Terlaris (10 teratas), Riwayat Transaksi (cari nomor/customer, tombol Cetak Struk), Penggunaan Inventory.
- Stock Opname: riwayat sesi selesai beserta selisih dan tautan Lihat Detail.
- Monitor Inventory: nilai kerugian, bahan terpakai, dan rincian per bahan.
- Inventory Hilang: kejadian bahan kurang saat opname beserta nilainya.
- Riwayat Kerugian: daftar Catat Kerugian dengan tautan Lihat Nota.

## Langganan (Subscription)
- Toko baru mendapat trial gratis 30 hari tanpa kartu kredit. Setelah trial habis dan tidak ada langganan aktif, semua halaman (termasuk Kasir dan aplikasi mobile) diarahkan ke halaman berlangganan; data tidak dihapus.
- Tombol Subscription (hanya owner) membuka halaman "Berlangganan NichmattPOS". Pilih paket, isi Kode Promo (opsional) lalu "Terapkan", lihat Total Bayar, klik "Bayar dengan Midtrans", dan selesaikan pembayaran di popup Midtrans. Kalau langganan masih aktif, periode baru menyambung dari tanggal berakhir. Harga paket terkini ada di halaman itu.
- Karyawan tidak melihat tombol Subscription; minta owner yang memperpanjang.

## Akun, login, dan Ganti Akun
- Daftar di halaman Register: Nama Toko, Nama Anda, Email, Password (minimal 8 karakter), lalu "Daftar" (atau "Daftar dengan Google"). Ini membuat toko baru dan akun Owner dengan trial 30 hari.
- Login dengan email dan password atau "Masuk dengan Google". Lupa password: "Lupa password?" lalu "Kirim Link Reset Password". Lebih dari 5 kali salah login mengunci sementara.
- Akun Google tidak punya password sampai dibuat di Profil (bagian "Buat Password").
- Profil (menu akun): ubah nama/email, ubah password, hapus akun.
- Ganti Akun: menu akun > "Ganti Akun" berisi akun lain yang pernah login di browser yang sama (maks 5), pindah tanpa mengetik password. "+ Tambah Akun Lain" untuk menambah akun ke daftar.

## Aplikasi kasir mobile
- "NichmattPOS Kasir", terhubung ke server yang sama dengan web (transaksi sungguhan). Saat ini dijalankan lewat Expo Go.
- Login dengan email dan password atau "Masuk dengan Google". Toko yang trialnya habis tidak bisa masuk.
- Fitur: Kasir (cari produk, filter kategori, produk dikelompokkan per kategori, keranjang dengan pelanggan, catatan pesanan, catatan item, kupon, kode self order, cetak bill), pembayaran Tunai/QRIS/Kartu (QRIS di mobile hanya pencatatan manual, tanpa QRIS online), cetak struk, Catat Kerugian (ikon tanda seru), Riwayat Transaksi, Stock Opname (produk dan bahan baku, sesuai izin), Pelanggan (sesuai izin), QR Self Order, tema gelap/terang, dan Keluar lewat menu.
- Belum ada di mobile: Dashboard, Laporan, kelola Produk/Kategori/Inventory/Kupon/Paket/Karyawan/Cabang, Persiapan, Profil, Ganti Akun, dan Subscription. Untuk itu gunakan web.
- Cetak di mobile memakai dialog print bawaan HP (bukan langsung ke printer Bluetooth thermal); printer harus sudah terpasang di sistem HP. Tidak ada scan barcode dengan kamera; barcode hanya bisa diketik.

## Kendala yang sering terjadi
- 403 Forbidden: akun belum diberi izin; minta owner mengatur di Karyawan.
- Semua halaman mengarah ke halaman berlangganan: trial atau langganan habis; owner perlu berlangganan.
- "Stok ... habis": produk berstatus Habis atau stok 0. Produk stok tak terbatas selalu tersedia; paket tidak tersedia kalau ada isinya yang habis.
- Nomor HP pelanggan, kode kupon, SKU, barcode, kode Inventory, dan nama Satuan harus unik per toko.
- Kupon umur butuh pelanggan terdaftar yang punya tanggal lahir dan dipilih dari saran di kolom Customer.
- Harga tidak bisa diubah di Kasir kecuali pengaturan Cabang mengizinkan.
- Tombol "Bayar QRIS Online" tidak muncul kalau QRIS online belum diaktifkan dan Server Key belum disimpan di Cabang.
- Cetak Label 404: produk belum punya barcode.
- Tombol Cetak via Bluetooth tidak muncul di Safari/iOS atau kalau format struk Invoice/PDF.
- Produk di Kasir hanya 40 sekaligus; gunakan pencarian atau filter.

## Hal yang perlu dialihkan ke tim developer
Bug/error, masalah pembayaran atau tagihan langganan, permintaan mengubah/menghapus data secara massal, fitur yang belum ada, dan hal lain yang tidak ada di panduan ini.
