<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// ডাটাবেস কনফিগারেশন (আপনার তথ্য দিন)
$DB_HOST = "localhost";
$DB_USER = "YOUR_DATABASE_USERNAME"; 
$DB_PASS = "YOUR_DATABASE_PASSWORD"; 
$DB_NAME = "YOUR_DATABASE_NAME"; 

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    echo json_encode(["status" => "FAILED", "message" => "Database Connection Failed"]);
    exit();
}
$conn->set_charset("utf8mb4");

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// ১. মিস্ত্রি লগইন ও লাইভ হিসাব দেখা
if ($action === 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($username) || empty($password)) {
        echo json_encode(["status" => "FAILED", "message" => "Username/Password Required"]);
        exit();
    }

    $stmt = $conn->prepare("SELECT username, password, role_type, hajira_rate FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user_res = $stmt->get_result();

    if ($user_res->num_rows === 1) {
        $user = $user_res->fetch_assoc();
        
        if ($password === $user['password'] || password_verify($password, $user['password'])) {
            $history = [];
            $totalEarned = 0; $totalPaid = 0; $totalHajira = 0;

            // শুধুমাত্র Approved হওয়া ডাটার হিসাব মিস্ত্রি দেখতে পারবে
            $ledger_stmt = $conn->prepare("SELECT work_date, report_type, details, amount, paid_amount, hajira_count FROM mistri_ledger WHERE username = ? AND status = 'Approved' ORDER BY id DESC");
            $ledger_stmt->bind_param("s", $username);
            $ledger_stmt->execute();
            $ledger_res = $ledger_stmt->get_result();

            while ($row = $ledger_res->fetch_assoc()) {
                $totalEarned += floatval($row['amount']);
                $totalPaid += floatval($row['paid_amount']);
                $totalHajira += floatval($row['hajira_count']);
                $history[] = [
                    "date" => $row['work_date'],
                    "type" => $row['report_type'],
                    "details" => $row['details'],
                    "earned" => $row['amount'],
                    "paid" => $row['paid_amount'],
                    "hajira" => $row['hajira_count']
                ];
            }

            echo json_encode([
                "status" => "SUCCESS",
                "name" => $user['username'],
                "type" => $user['role_type'],
                "rate" => $user['hajira_rate'],
                "totalEarned" => $totalEarned,
                "totalPaid" => $totalPaid,
                "totalHajira" => $totalHajira,
                "history" => $history
            ]);
        } else {
            echo json_encode(["status" => "FAILED", "message" => "Wrong Password"]);
        }
    } else {
        echo json_encode(["status" => "FAILED", "message" => "User Not Found"]);
    }
    exit();
}

// ২. নতুন কাজের রিপোর্ট পেন্ডিং অবস্থায় জমা নেওয়া
if ($action === 'submit_report') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $report_type = isset($_POST['report_type']) ? trim($_POST['report_type']) : '';
    $details = isset($_POST['details']) ? trim($_POST['details']) : '';
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $hajira_count = isset($_POST['hajira_count']) ? floatval($_POST['hajira_count']) : 0;
    
    $image_path = "No Image";

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true); // অটো ফোল্ডার তৈরি হবে
        }

        $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png", "gif", "webp"];

        if (in_array($file_ext, $allowed)) {
            $new_file_name = "IMG_" . time() . "_" . rand(1000, 9999) . "." . $file_ext;
            $target = $upload_dir . $new_file_name;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                $image_path = $target;
            }
        }
    }

    $today = date("Y-m-d");
    $insert = $conn->prepare("INSERT INTO mistri_ledger (username, work_date, report_type, details, amount, paid_amount, hajira_count, image_path, status) VALUES (?, ?, ?, ?, ?, 0, ?, ?, 'Pending')");
    $insert->bind_param("ssssdds", $username, $today, $report_type, $details, $amount, $hajira_count, $image_path);

    if ($insert->execute()) {
        echo json_encode(["status" => "SUCCESS", "message" => "Report saved as pending"]);
    } else {
        echo json_encode(["status" => "FAILED", "message" => "Failed to save record"]);
    }
    exit();
}

echo json_encode(["status" => "INVALID_ACTION"]);
$conn->close();
?>
