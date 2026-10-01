<?php session_start();

if(!isset($_SESSION['login'])){
    header("Location:login.php");
    exit;
}

include 'template/header.php'; ?>

<div class="main-panel">
    <div class="main-header">
        <!-- Navbar Header -->
        <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
            <div class="container-fluid">
                <nav class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
                </nav>
                <li class="nav-item topbar-user dropdown hidden-caret">
                    <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                        <div class="avatar-sm">
                            <img src="./assets/img/profile.jpg" alt="..." class="avatar-img rounded-circle" />
                        </div>
                        <span class="profile-username">
                           <span class="fw-bold">
                         <?= $_SESSION['nama']; ?>
                           </span>
                        </span>
                    </a>
                </li>
            </div>
        </nav>
        <!-- End Navbar -->
    </div>

    <div class="container">
        <div class="page-inner">
            <div
                class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                <div>
                    <h3 class="fw-bold mb-3">Dashboard</h3>
                </div>
            </div>
            <div class="row">
                <?php
                // Mengatur timezone ke Indonesia
                date_default_timezone_set('Asia/Jakarta');
                // Mendapatkan tanggal hari ini dalam format 'Y-m-d'
                $date = date('Y-m-d');
                // Mendapatkan nama hari dalam bahasa Indonesia
                $hari_ini = date('l'); // Mengambil nama hari dalam bahasa Inggris (contoh: Monday, Tuesday, dll.)
                // Mengonversi nama hari dalam bahasa Inggris ke bahasa Indonesia
                $hari_indonesia = [
                    'Monday'    => 'senin',
                    'Tuesday'   => 'selasa',
                    'Wednesday' => 'rabu',
                    'Thursday'  => 'kamis',
                    'Friday'    => 'jumat',
                    'Saturday'  => 'sabtu',
                    'Sunday'    => 'minggu'
                ];

                // Mengambil nama hari dalam bahasa Indonesia
                $hari = $hari_indonesia[$hari_ini];

                // Query untuk menghitung jumlah pasien per poli dan dokter
                $query1 = mysqli_query($conn, "SELECT b.nm_poli, d.nm_dokter, COUNT(a.no_rawat) AS jumlah_pasien FROM reg_periksa AS a 
                JOIN poliklinik AS b ON a.kd_poli = b.kd_poli 
                JOIN dokter AS d ON a.kd_dokter = d.kd_dokter 
                JOIN jadwal AS c ON a.kd_dokter = c.kd_dokter 
                WHERE a.tgl_registrasi = '$date' AND c.hari_kerja = '$hari' GROUP BY b.nm_poli, d.nm_dokter");

                // Inisialisasi array untuk menyimpan jumlah pasien per poli per dokter
                $jumlah_pasien_per_poli_dokter = [];

                // Menyimpan hasil query ke dalam array asosiatif
                while ($data1 = mysqli_fetch_assoc($query1)) {
                    $nm_poli = $data1['nm_poli'];
                    $nm_dokter = $data1['nm_dokter'];
                    $jumlah_pasien = $data1['jumlah_pasien'];

                    // Menyimpan jumlah pasien berdasarkan poli dan dokter
                    $jumlah_pasien_per_poli_dokter[$nm_poli][$nm_dokter] = $jumlah_pasien;
                }

                // Query untuk mendapatkan daftar poli dan dokter sesuai jadwal hari ini
                $query2 = mysqli_query($conn, "SELECT a.nm_poli, c.nm_dokter, b.jam_mulai, b.jam_selesai FROM poliklinik AS a 
                JOIN jadwal AS b ON a.kd_poli = b.kd_poli 
                JOIN dokter AS c ON b.kd_dokter = c.kd_dokter 
                WHERE b.hari_kerja = '$hari'");

                // Menampilkan hasil
                while ($data2 = mysqli_fetch_assoc($query2)) {
                    $nm_poli = $data2['nm_poli'];
                    $nm_dokter = $data2['nm_dokter'];

                    // Mengambil jumlah pasien berdasarkan poli dan dokter
                    $jumlah_pasien = isset($jumlah_pasien_per_poli_dokter[$nm_poli][$nm_dokter]) ? $jumlah_pasien_per_poli_dokter[$nm_poli][$nm_dokter] : 0;
                ?>


                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-primary bubble-shadow-small">
                                            <i class="fas fa-users"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category"><?= $nm_poli; ?> : <b><?= $data2['jam_mulai']; ?></b> - <?= $data2['jam_selesai']; ?>
                                                <br> <b><?= $data2['nm_dokter']; ?></b>
                                            </p>
                                            <br>
                                            <h4 class="card-title"><?= $jumlah_pasien; ?> Pasien</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <!-- History pesan -->
            <div class="row">
                <div class="card card-round">
                    <div class="card-header">
                        <div class="card-head-row card-tools-still-right">
                            <div class="card-title">Messages History</div>
                        </div>
                    </div>
                    <?php
                    $lihat = "SELECT * FROM wa_outbox";  // Ganti dengan nama tabel Anda
                    $result = $conn->query($lihat);
                    ?>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <!-- Projects table -->
                            <table class="table align-items-center mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th scope="col">Whatsapps Number</th>
                                        <th scope="col" class="text-end">Date & Time</th>
                                        <th scope="col" class="text-end">Amount</th>
                                        <th scope="col" class="text-end">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                            <th scope='row'>
                                            <button class='btn btn-icon btn-round btn-success btn-sm me-2'><i class='fa fa-check'></i></button>" . substr($row['NOWA'], 0, 13) . "
                                                        <td class='text-end'>" . $row['TANGGAL_JAM'] . "</td>
                                                        <td class='text-end'>" . substr($row['RESPONSE'], 0, 33) . '}' . "</td>
                                                        <td class='text-end'><span class='badge badge-success'>Completed</span></td>
                                                    </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4'>Belum ada data</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'template/header.php'; ?>
