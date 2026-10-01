<?php

/* =====================================================
   KONEKSI DATABASE SIMRS (SERVER 1)
   Digunakan untuk membaca data pasien, dokter, jadwal, dll.
   ===================================================== */

$serverSIMRS   = "";
$userSIMRS     = "";
$passSIMRS     = "";
$databaseSIMRS = "";

$connSIMRS = mysqli_connect($serverSIMRS, $userSIMRS, $passSIMRS, $databaseSIMRS);

if (!$connSIMRS) {
    die("Koneksi ke Database SIMRS gagal : " . mysqli_connect_error());
}

mysqli_set_charset($connSIMRS, "utf8");


/* =====================================================
   KONEKSI DATABASE LOKAL SIMIFM (SERVER 2)
   Digunakan untuk wa_template, wa_outbox, simifm_user
   ===================================================== */

$serverSIMIFM   = "";
$userSIMIFM     = "";
$passSIMIFM     = "";   // Ganti jika password MySQL Server 2 berbeda
$databaseSIMIFM = "";

$connSIMIFM = mysqli_connect($serverSIMIFM, $userSIMIFM, $passSIMIFM, $databaseSIMIFM);

if (!$connSIMIFM) {
    die("Koneksi ke Database SIMIFM gagal : " . mysqli_connect_error());
}

mysqli_set_charset($connSIMIFM, "utf8");
$conn = $connSIMIFM;
