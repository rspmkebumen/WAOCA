<?php

use LDAP\Result;

include 'config.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION["username"])) {
    echo ("<script type='text/javascript'>
        alert('SILAHKAN LOGIN TERLEBIH DAHULU!');
        window.location='index.html';
    </script>");
    exit;
}

$host = "10.4.1.225:3377";
$username = "client";
$password = "PkuKuto#12";
$dbname = "sik";  // Your database name

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tanggal = date('Y-m-d'); // Current date

// Correct query with date comparison
$sql = "SELECT a.`no_rawat`, a.`no_reg`, b.`nm_pasien`, b.`no_tlp`, c.`nm_poli`, d.`nm_dokter`
        FROM reg_periksa AS a
        JOIN pasien AS b ON a.`no_rkm_medis` = b.`no_rkm_medis`
        JOIN poliklinik AS c ON a.`kd_poli` = c.`kd_poli`
        JOIN dokter AS d ON a.`kd_dokter` = d.`kd_dokter`
        WHERE a.`tgl_registrasi` = '$tanggal'";  // Wrap $tanggal in single quotes

$result = $conn->query($sql);

// Function to format phone numbers (remove leading 0 and add 62)
function formatPhoneNumber($no_tlp)
{
    // Remove leading zero if exists
    if (substr($no_tlp, 0, 1) == '0') {
        $no_tlp = '62' . substr($no_tlp, 1); // Replace 0 with 62
    } else {
        $no_tlp = '62' . $no_tlp; // Just add 62 if no leading 0
    }
    return $no_tlp;
}

// If data is found, export as CSV
if ($result->num_rows > 0) {
    // Set headers to initiate the CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="whatsapp_numbers.csv"');

    // Open the output stream to write the CSV file
    $output = fopen('php://output', 'w');

    // Output column headers for CSV
    fputcsv($output, ['NOWA', 'Nama Pasien', 'Nama Dokter', 'PESAN']);

    // Loop through the results and write each row to the CSV
    while ($row = $result->fetch_assoc()) {
        // Format phone number
        $formattedPhoneNumber = formatPhoneNumber($row['no_tlp']);

        fputcsv($output, [
            $formattedPhoneNumber . '@c.us', // Format as WhatsApp number
            $row['nm_pasien'],
            $row['nm_dokter']
        ]);
    }

    // Close the output stream
    fclose($output);
} else {
    // If no records were found
    echo "No data found!";
}

// Close the database connection
$conn->close();
