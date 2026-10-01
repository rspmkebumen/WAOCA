<?php
include 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fungsi Tambahan: Konversi Tanggal ke Nama Hari Indonesia
function getHariIndonesia($tanggal) {
    $ubah = date('D', strtotime($tanggal));
    $hari_list = [
        'Sun' => 'Minggu',
        'Mon' => 'Senin',
        'Tue' => 'Selasa',
        'Wed' => 'Rabu',
        'Thu' => 'Kamis',
        'Fri' => 'Jumat',
        'Sat' => 'Sabtu'
    ];
    return $hari_list[$ubah] ?? '';
}

function getTanggalIndonesia($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $ts = strtotime($tanggal);
    if (!$ts) {
        return $tanggal;
    }
    return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

function getNamaPoli($conn, $nm_dokter) {
    // Gunakan query biasa agar halaman ini tidak menambah prepared statement
    // ke server MySQL/MariaDB. Ini penting karena server sebelumnya sudah
    // mencapai max_prepared_stmt_count.
    $dokter = $conn->real_escape_string($nm_dokter);
    $sql = "SELECT p.nm_poli
            FROM jadwal j
            INNER JOIN dokter d ON j.kd_dokter = d.kd_dokter
            INNER JOIN poliklinik p ON j.kd_poli = p.kd_poli
            WHERE d.nm_dokter = '$dokter'
            LIMIT 1";
    $result = $conn->query($sql);
    if (!$result) {
        return '';
    }
    $row = $result->fetch_assoc();
    $result->free();
    return $row['nm_poli'] ?? '';
}

function Request($data)
{
    $url = "https://wa01.ocatelkom.co.id/api/v2/push/message"; 
    $token = ""; 

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);

    $result = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    return $err ? ["metadata" => ["message" => "Error: $err"]] : json_decode($result, true);
}

$daftar_pasien = [];
$mode_preview = false;
$preview_error = '';
$tgl_hari_ini = date('Y-m-d');

// 1. PROSES KIRIM REAL
if (isset($_POST['kirim_manual'])) {
   $template_id = $_POST['template_id'];

   $template_id = (int)$template_id;
   $qtemp = $connSIMIFM->query("SELECT id, nama_template, template_code FROM wa_template WHERE id=$template_id AND aktif='Y' LIMIT 1");

   if (!$qtemp) {
       die('Gagal membaca template WhatsApp: ' . htmlspecialchars($connSIMIFM->error));
   }

   $template = $qtemp->fetch_assoc();
   if (!$template) {
       echo "<script>alert('Template WhatsApp tidak ditemukan atau tidak aktif.'); history.back();</script>";
       exit();
   }

   $template_code = trim($template['template_code']);
    
    $kd_poli = $_POST['kd_poli'];
    $nm_dokter = $_POST['nm_dokter'];
    $tgl_praktek = $_POST['hari_praktek'];
    $jam_praktek = $_POST['jam_praktek'];
    
    $hari_indonesia = getHariIndonesia($tgl_praktek); 

    // Query biasa (bukan prepared statement) untuk menghindari error
    // max_prepared_stmt_count pada server SIMRS.
    $tgl_sql = $connSIMRS->real_escape_string($tgl_praktek);
    $poli_sql = $connSIMRS->real_escape_string($kd_poli);
    $dokter_sql = $connSIMRS->real_escape_string($nm_dokter);

    $sql = "SELECT b.no_tlp, b.nm_pasien
            FROM reg_periksa AS a
            JOIN pasien AS b ON a.no_rkm_medis = b.no_rkm_medis
            JOIN dokter AS c ON a.kd_dokter = c.kd_dokter
            JOIN poliklinik AS d ON a.kd_poli = d.kd_poli
            WHERE a.tgl_registrasi = '$tgl_sql'
              AND a.kd_poli = '$poli_sql'
              AND c.nm_dokter = '$dokter_sql'
              AND a.stts != 'Batal'";

    $result_reg = $connSIMRS->query($sql);
    if (!$result_reg) {
        die('Gagal mengambil daftar pasien: ' . htmlspecialchars($connSIMRS->error));
    }

    $jml_terkirim = 0;
    while ($row = $result_reg->fetch_assoc()) {
        $no_tlp = $row['no_tlp'];
        
        // Normalisasi nomor WA
        if (substr($no_tlp, 0, 1) == '0') {
            $no_tlp = '62' . substr($no_tlp, 1);
        } elseif (substr($no_tlp, 0, 2) != '62') {
            $no_tlp = '62' . $no_tlp;
        }
$parameter_template = [];

/* =========================================================
   KONFIGURASI 6 TEMPLATE YANG ADA DI wa_template
   ========================================================= */

if ($template_code == 'marketing:informasi_dokter_cuti') {

    // [1] Hari, [2] Tanggal, [3] Poli, [4] Dokter
    $nama_poli = getNamaPoli($connSIMRS, $nm_dokter);

    $parameter_template = [
        ["type" => "text", "text" => $hari_indonesia],
        ["type" => "text", "text" => getTanggalIndonesia($tgl_praktek)],
        ["type" => "text", "text" => $nama_poli],
        ["type" => "text", "text" => $nm_dokter]
    ];

} elseif ($template_code == 'marketing:pengingat_jadwal_dr_imbar_sudarsono_sppd') {

    // [1] Hari, [2] Tanggal, [3] Dokter
    $parameter_template = [
        ["type" => "text", "text" => $hari_indonesia],
        ["type" => "text", "text" => getTanggalIndonesia($tgl_praktek)],
        ["type" => "text", "text" => $nm_dokter]
    ];

} elseif ($template_code == 'marketing:pemberitahuan_dr_heti_cuti_ganti_dr_jaka_15_30_17_00_new') {

    // [1] Hari, [2] Tanggal, [3] Poli, [4] Dokter
    $nama_poli = getNamaPoli($connSIMRS, $nm_dokter);

    $parameter_template = [
        ["type" => "text", "text" => $hari_indonesia],
        ["type" => "text", "text" => getTanggalIndonesia($tgl_praktek)],
        ["type" => "text", "text" => $nama_poli],
        ["type" => "text", "text" => $nm_dokter]
    ];

} elseif ($template_code == 'marketing:jadwal_mundur_dr_heti_jam_12_00') {

    // [1] Hari, [2] Tanggal, [3] Poli, [4] Dokter
    $nama_poli = getNamaPoli($connSIMRS, $nm_dokter);

    $parameter_template = [
        ["type" => "text", "text" => $hari_indonesia],
        ["type" => "text", "text" => getTanggalIndonesia($tgl_praktek)],
        ["type" => "text", "text" => $nama_poli],
        ["type" => "text", "text" => $nm_dokter]
    ];

} elseif ($template_code == 'Marketing:jadwal_dokter_irbab_maju_12_00') {

    // [1] Hari, [2] Tanggal, [3] Poli, [4] Dokter
    $nama_poli = getNamaPoli($connSIMRS, $nm_dokter);

    $parameter_template = [
        ["type" => "text", "text" => $hari_indonesia],
        ["type" => "text", "text" => getTanggalIndonesia($tgl_praktek)],
        ["type" => "text", "text" => $nama_poli],
        ["type" => "text", "text" => $nm_dokter]
    ];

} elseif ($template_code == 'Marketing:pengingat_jadwal_dokter_irbab_jam_13_00_14_30') {

    // [1] Hari, [2] Tanggal, [3] Poli, [4] Dokter
    $nama_poli = getNamaPoli($connSIMRS, $nm_dokter);

    $parameter_template = [
        ["type" => "text", "text" => $hari_indonesia],
        ["type" => "text", "text" => getTanggalIndonesia($tgl_praktek)],
        ["type" => "text", "text" => $nama_poli],
        ["type" => "text", "text" => $nm_dokter]
    ];

} else {
    echo "<script>alert('Template belum memiliki konfigurasi parameter di tes.php: " . htmlspecialchars($template_code, ENT_QUOTES) . "'); history.back();</script>";
    exit();
}

   $requestData = json_encode([
    "message" => [
        "type" => "template",
        "template" => [
            "template_code_id" => $template_code,
            "payload" => [
                [
                    "position" => "body",
                    "parameters" => $parameter_template
                ]
            ]
        ]
    ],
    "phone_number" => $no_tlp
]);

        $res = Request($requestData);
        $status_msg = json_encode($res, JSON_UNESCAPED_UNICODE);

        // Simpan response OCA ke log.
        $stmt_log = $connSIMIFM->prepare("INSERT INTO wa_outbox (NOWA, PESAN, TANGGAL_JAM, RESPONSE) VALUES (?, ?, NOW(), ?)");
        if ($stmt_log) {
            $pesan_log = "Jadwal $nm_dokter kepada " . $row['nm_pasien'] . " pada pukul " . $jam_praktek;
            $stmt_log->bind_param("sss", $no_tlp, $pesan_log, $status_msg);
            $stmt_log->execute();
            $stmt_log->close();
        }

        // Tetap menghitung terkirim berdasarkan response OCA yang berhasil.
        if (isset($res['metadata']['message']) && stripos($res['metadata']['message'], 'success') !== false) {
            $jml_terkirim++;
        } elseif (isset($res['status']) && in_array(strtolower((string)$res['status']), ['success', '200', 'true'], true)) {
            $jml_terkirim++;
        } elseif (!isset($res['metadata']['message']) && !isset($res['error'])) {
            $jml_terkirim++;
        }
    }
    $result_reg->free();
    echo "<script>
    alert('Berhasil mengirim ke $jml_terkirim pasien.');
    window.location='tes.php';
    </script>";
    exit();
}

if (isset($_POST['cek_pasien'])) {
    $kd_poli = $_POST['kd_poli'];
    $nm_dokter = $_POST['nm_dokter'];
    $tgl_pencarian = $_POST['hari_praktek'] ?? date('Y-m-d');
    $mode_preview = true;
    $daftar_pasien = [];

    // Jangan gunakan prepare() di sini. Server SIMRS sedang mencapai
    // max_prepared_stmt_count, sehingga prepare() gagal sebelum bind_param().
    $tgl_sql = $connSIMRS->real_escape_string($tgl_pencarian);
    $poli_sql = $connSIMRS->real_escape_string($kd_poli);
    $dokter_sql = $connSIMRS->real_escape_string($nm_dokter);

    $sql = "SELECT b.no_tlp, b.nm_pasien
            FROM reg_periksa AS a
            JOIN pasien AS b ON a.no_rkm_medis = b.no_rkm_medis
            JOIN dokter AS c ON a.kd_dokter = c.kd_dokter
            JOIN poliklinik AS d ON a.kd_poli = d.kd_poli
            WHERE a.tgl_registrasi = '$tgl_sql'
              AND a.kd_poli = '$poli_sql'
              AND c.nm_dokter = '$dokter_sql'
              AND a.stts != 'Batal'";

    $res_preview = $connSIMRS->query($sql);
    if (!$res_preview) {
        $preview_error = 'Query daftar pasien gagal dijalankan: ' . $connSIMRS->error;
    } else {
        while ($row = $res_preview->fetch_assoc()) {
            $daftar_pasien[] = $row;
        }
        $res_preview->free();
    }
}

$result_log = $connSIMIFM->query("SELECT * FROM wa_outbox WHERE TANGGAL_JAM >= '$tgl_hari_ini 00:00:00' AND TANGGAL_JAM <= '$tgl_hari_ini 23:59:59' ORDER BY TANGGAL_JAM DESC");
if (!$result_log) {
    $result_log = false;
}
?> 

<?php include 'template/header.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Layanan Informasi Pasien</h3>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-calendar-alt me-2"></i>Kirim Jadwal Praktek Dokter</div>
                    </div>

                    <form action="" method="post">
                        <div class="card-body">
                           <div class="row mb-3">

    <!-- TEMPLATE WA -->
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label fw-bold">Template WhatsApp</label>

            <select class="form-select" name="template_id" required>
                <option value="">-- Pilih Template --</option>

                <?php
                $q_template = mysqli_query(
                     $connSIMIFM,
                    "SELECT * FROM wa_template
                    WHERE aktif='Y'
                    ORDER BY nama_template"
                );

                while($tp = mysqli_fetch_assoc($q_template)){
                ?>
                    <option value="<?= $tp['id']; ?>">
                        <?= $tp['nama_template']; ?>
                    </option>
                <?php } ?>
            </select>
        </div>
    </div>

    <!-- POLIKLINIK -->
    <div class="col-md-4"><div class="form-group"><label class="form-label fw-bold">Poliklinik</label><select class="form-select select2" name="kd_poli" required><option value="">-- Pilih Poliklinik --</option><?php $q_poli=$connSIMRS->query("SELECT kd_poli,nm_poli FROM poliklinik WHERE kd_poli!='UGD' ORDER BY nm_poli"); while($pl=$q_poli->fetch_assoc()){ $sel=(isset($_POST['kd_poli'])&&$_POST['kd_poli']==$pl['kd_poli'])?'selected':''; echo "<option value='{$pl['kd_poli']}' $sel>{$pl['nm_poli']}</option>"; } ?></select></div></div>

    <!-- NAMA DOKTER -->
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label fw-bold">Nama Dokter</label>
            <select class="form-select select2" name="nm_dokter" required>
                                        
                                            <option value="">-- Pilih Dokter --</option>
                                            <?php
                                            $sql_dr = "SELECT DISTINCT c.nm_dokter FROM reg_periksa a 
                                   JOIN dokter c ON a.kd_dokter=c.kd_dokter 
                                   JOIN poliklinik d ON a.kd_poli=d.kd_poli 
                                   WHERE d.kd_poli!='Umum' AND c.status='1'";
                                            $res_dr = $connSIMRS->query($sql_dr);
                                            while ($dr = $res_dr->fetch_assoc()) {
                                                $selected = (isset($_POST['nm_dokter']) && $_POST['nm_dokter'] == $dr['nm_dokter']) ? 'selected' : '';
                                                echo "<option value='" . $dr['nm_dokter'] . "' $selected>" . $dr['nm_dokter'] . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">Tanggal Praktek</label>
                                        <input type="date" class="form-control" name="hari_praktek"
                                            value="<?= $_POST['hari_praktek'] ?? date('Y-m-d') ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">Kedatangan Dokter</label>
                                        <input type="text" class="form-control" name="jam_praktek"
                                            value="<?= $_POST['jam_praktek'] ?? '' ?>" placeholder="Contoh: 08:00 - 12:00" required>
                                    </div>
                                </div>
                            </div>


                            <div class="col-md-4 d-flex align-items-end">
                                <div class="w-100 text-muted small pb-2">
                                    <i class="fas fa-info-circle me-1"></i> Pastikan semua filter di atas dipilih dengan benar.
                                </div>
                            </div>
                        </div>
                </div>

                <div class="card-footer text-end">
                    <button type="submit" name="cek_pasien" class="btn btn-info text-white">
                        <i class="fas fa-search me-2"></i> Cek Daftar Pasien
                    </button>
                </div>
                </form>

                <!-- Tampilkan Preview Jika Tombol Cek Ditekan -->
                <?php if ($mode_preview): ?>
                    <?php if (!empty($preview_error)): ?>
                        <div class="alert alert-danger">
                            <strong>Gagal mengambil daftar pasien.</strong><br>
                            <?= htmlspecialchars($preview_error) ?>
                        </div>
                    <?php endif; ?>
                    <div class="card border-primary shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h4 class="card-title">Daftar Penerima (Dokter: <?= htmlspecialchars($nm_dokter) ?>)</h4>
                        </div>
                        <form action="" method="post">
                            <!-- Data Hidden agar tidak perlu input ulang -->
                            <input type="hidden"
                                   name="template_id"
                                   value="<?= htmlspecialchars($_POST['template_id']) ?>">

                           <input type="hidden" name="kd_poli" value="<?= htmlspecialchars($_POST['kd_poli']) ?>">
                           <input type="hidden"
                                  name="nm_dokter"
                                  value="<?= htmlspecialchars($nm_dokter) ?>">

                           <input type="hidden"
                                  name="hari_praktek"
                                  value="<?= htmlspecialchars($_POST['hari_praktek']) ?>">

                           <input type="hidden"
                                  name="jam_praktek"
                                  value="<?= htmlspecialchars($_POST['jam_praktek']) ?>">
                            <div class="card-body">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nama Pasien</th>
                                            <th>Nomor Telepon</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($daftar_pasien) > 0): ?>
                                            <?php foreach ($daftar_pasien as $p): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($p['nm_pasien']) ?></td>
                                                    <td><span class="badge badge-success"><?= htmlspecialchars($p['no_tlp']) ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="2" class="text-center">Data tidak ditemukan.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($daftar_pasien) > 0): ?>
                                <div class="card-footer">
                                    <div class="alert alert-warning text-dark">
                                        <i class="fas fa-exclamation-triangle"></i> Klik tombol di bawah untuk mengirim pesan template WhatsApp ke semua pasien di atas.
                                    </div>
                                    <button type="submit" name="kirim_manual" class="btn btn-primary w-100 btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i> YA, KIRIM SEKARANG
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabel Monitoring Log -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Log Pesan Terkirim</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="basic-datatables" class="table table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nomor WA</th>
                                        <th>Informasi Pesan</th>
                                        <th>Waktu</th>
                                        <th>Status Response</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_log): ?>
                                    <?php while ($row = $result_log->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $row['NOWA'] ?></td>
                                            <td><?= $row['PESAN'] ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($row['TANGGAL_JAM'])) ?></td>
                                            <td><code><?= htmlspecialchars(substr($row['RESPONSE'], 0, 30)) ?>...</code></td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'template/footer.php'; ?>
<script>
$(document).ready(function(){

    $('.select2').select2({
        placeholder:'Ketik nama poliklinik...',
        width:'100%'
    });

});
</script>

