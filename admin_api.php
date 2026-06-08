<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// ডাটাবেস কনফিগারেশন
$DB_HOST = "localhost";
$DB_USER = "YOUR_DATABASE_USERNAME"; 
$DB_PASS = "YOUR_DATABASE_PASSWORD"; 
$DB_NAME = "YOUR_DATABASE_NAME"; 

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) { exit(json_encode(["status" => "FAILED"])); }
$conn->set_charset("utf8mb4");

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// ১. অ্যাডমিন লগইন ভেরিফিকেশন
if ($action === 'admin_login') {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // 👑 এখানে আপনার অ্যাডমিন প্যানেলের ইউজারনেম ও পাসওয়ার্ড সেট করা আছে
    if ($username === 'admin' && $password === 'owner2026') {
        echo json_encode(["status" => "SUCCESS"]);
    } else {
        echo json_encode(["status" => "FAILED"]);
    }
    exit();
}

// ২. পেন্ডিং ডাটা তালিকা দেখা
if ($action === 'get_pending') {
    $result = $conn->query("SELECT id, username, work_date, details, amount, hajira_count, image_path FROM mistri_ledger WHERE status = 'Pending' ORDER BY id ASC");
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit();
}

// ৩. ডাটা Approve বা Reject করা
if ($action === 'update_status') {
    $id = intval($_POST['id']);
    $status = $_POST['status']; // Approved অথবা Rejected

    $stmt = $conn->prepare("UPDATE mistri_ledger SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    
    if($stmt->execute()) {
        echo json_encode(["status" => "SUCCESS"]);
    } else {
        echo json_encode(["status" => "FAILED"]);
    }
    exit();
}

$conn->close();
?>
