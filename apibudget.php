<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {
    case 'GET':
        // Ambil data anggaran berdasarkan tahun
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
        
        $stmt = $db->prepare("SELECT * FROM anggaran WHERE tahun = ?");
        $stmt->bind_param("i", $year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $data['pendapatan'] = json_decode($data['pendapatan'], true);
            $data['belanja'] = json_decode($data['belanja'], true);
            echo json_encode($data);
        } else {
            echo json_encode(['error' => 'Data tidak ditemukan']);
        }
        break;
        
    case 'POST':
        // Simpan data anggaran
        $input = json_decode(file_get_contents('php://input'), true);
        
        $tahun = $input['tahun'];
        $totalPendapatan = $input['totalPendapatan'];
        $totalBelanja = $input['totalBelanja'];
        $saldoAkhir = $input['saldoAkhir'];
        $deskripsi = $input['deskripsi'];
        $pendapatan = json_encode($input['pendapatan']);
        $belanja = json_encode($input['belanja']);
        
        // Cek apakah data tahun sudah ada
        $check = $db->prepare("SELECT id FROM anggaran WHERE tahun = ?");
        $check->bind_param("i", $tahun);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            // Update data yang ada
            $stmt = $db->prepare("UPDATE anggaran SET 
                total_pendapatan = ?,
                total_belanja = ?,
                saldo_akhir = ?,
                deskripsi = ?,
                pendapatan = ?,
                belanja = ?,
                updated_at = NOW()
                WHERE tahun = ?");
        } else {
            // Buat data baru
            $stmt = $db->prepare("INSERT INTO anggaran (
                tahun,
                total_pendapatan,
                total_belanja,
                saldo_akhir,
                deskripsi,
                pendapatan,
                belanja,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        }
        
        $stmt->bind_param("isssssi", 
            $tahun,
            $totalPendapatan,
            $totalBelanja,
            $saldoAkhir,
            $deskripsi,
            $pendapatan,
            $belanja
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Gagal menyimpan data']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method tidak diizinkan']);
}
?>