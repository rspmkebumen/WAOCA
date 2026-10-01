<?php
// Data yang akan ditulis ke dalam file CSV
$data = [
    ['Name', 'Age', 'Address', 'Phone Number', 'Email', 'Date of Birth'], // Kolom Header
    ['John Doe', '30', '123 Main St', '555-1234', 'john.doe@example.com', '1994-05-15'],
    ['Jane Smith', '28', '456 Oak St', '555-5678', 'jane.smith@example.com', '1996-07-22']
];

// Nama file CSV
$fileName = 'data.csv';

// Set header untuk mendownload file
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Membuka output sebagai stream untuk menulis CSV
$output = fopen('php://output', 'w');

// Menulis setiap baris data ke file CSV
foreach ($data as $row) {
    fputcsv($output, $row); // Fungsi untuk menulis array ke dalam file CSV
}

// Menutup stream setelah selesai menulis
fclose($output);
