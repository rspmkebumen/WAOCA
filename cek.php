<?php
session_start();

include "config.php";

$username = $_POST["username"];
$p = ($_POST["passworde"]);

$qry = mysqli_query($conn, "SELECT * FROM pegawai WHERE nik='$username' AND passworde=AES_ENCRYPT('$p','habib')");

// proceed only if a query is executed
if (mysqli_num_rows($qry) > 0) {
    $data = mysqli_fetch_assoc($qry);
}
if ($conn->affected_rows > 0) {
    $_SESSION["username"]     = $username;
    $_SESSION["id"]         = $data["id"];
    $_SESSION["jbtn"]         = $data["jbtn"];
    $_SESSION["nama"]         = $data["nama"];
    $_SESSION["alamat"]     = $data["alamat"];
    $_SESSION["bidang"]     = $data["bidang"];
    $_SESSION["rekening"]         = $data["rekening"];
    header("Location:./index.php");
} else {
    echo "
    <script type='text/javascript'>
    alert('PASSWORD ATAU USERNAME TIDAK COCOK, PASTIKAN INGAT JIKA TIDAK AKAN DI BLOKIR OTOMATIS!!');
    history.back(self);
    </script>";
}
