<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Cache-Control: no-store");

// Database Connection Settings
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "smart_queue_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database Connection Failed"]));
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. ESP32 Triggers New Ticket (Push Button Pressed)
if ($action === 'new_ticket') {
    // Serialize ticket creation with pause/resume using the existing counter row.
    $conn->begin_transaction();
    $counter = $conn->query("SELECT is_active FROM counter_status WHERE counter_id = 1 FOR UPDATE")->fetch_assoc();
    if (!$counter || !(bool) $counter['is_active']) {
        $conn->rollback();
        http_response_code(409);
        echo json_encode(["status" => "paused", "message" => "Queue paused. Please wait until service resumes."]);
        exit;
    }
    // Get last queue number
    $result = $conn->query("SELECT MAX(queue_number) AS last_num FROM queues");
    $row = $result->fetch_assoc();
    $next_num = ($row['last_num'] ?? 0) + 1;

    $stmt = $conn->prepare("INSERT INTO queues (queue_number, status) VALUES (?, 'Waiting')");
    $stmt->bind_param("i", $next_num);
    
    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode(["status" => "success", "queue_number" => $next_num]);
    } else {
        $conn->rollback();
        echo json_encode(["status" => "error"]);
    }
} 

// Set an explicit state so retrying a request does not toggle it twice.
else if ($action === 'set_queue_status') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        echo json_encode(["status" => "error", "message" => "Use POST to change queue status."]);
        exit;
    }
    $active = $_POST['is_active'] ?? '';
    if (!in_array($active, ['0', '1'], true)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "is_active must be 0 or 1."]);
        exit;
    }
    $active = (int) $active;
    $stmt = $conn->prepare("UPDATE counter_status SET is_active = ? WHERE counter_id = 1");
    $stmt->bind_param('i', $active);
    $stmt->execute();
    $counter = $conn->query("SELECT is_active FROM counter_status WHERE counter_id = 1")->fetch_assoc();
    if (!$counter) {
        http_response_code(503);
        echo json_encode(["status" => "error", "message" => "Counter 1 is missing. Import database/schema.sql."]);
    } else {
        echo json_encode(["status" => "success", "is_active" => (bool) $counter['is_active']]);
    }
}

// 2. ESP32 Sends IR Sensor Status
else if ($action === 'ir_sensor') {
    $value = $_POST['value'] ?? 'Clear';
    $stmt = $conn->prepare("INSERT INTO sensor_logs (sensor_type, status_value) VALUES ('IR_Sensor', ?)");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    echo json_encode(["status" => "success"]);
}

// 3. Admin Clicks "Call Next Customer"
else if ($action === 'call_next') {
    // Get next waiting number
    $result = $conn->query("SELECT id, queue_number FROM queues WHERE status = 'Waiting' ORDER BY id ASC LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $num = $row['queue_number'];

        // Update status to 'Serving'
        $conn->query("UPDATE queues SET status = 'Serving', served_at = NOW() WHERE id = $id");
        $conn->query("UPDATE counter_status SET current_queue_number = $num WHERE counter_id = 1");

        echo json_encode(["status" => "success", "serving_number" => $num]);
    } else {
        echo json_encode(["status" => "empty", "message" => "No waiting customers"]);
    }
}

// 4. Get Current Status Data (Used by Dashboard Frontends)
else if ($action === 'get_dashboard_data') {
    $counter = $conn->query("SELECT is_active FROM counter_status WHERE counter_id = 1")->fetch_assoc();
    $serving = $conn->query("SELECT queue_number FROM queues WHERE status = 'Serving' ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $waiting = $conn->query("SELECT queue_number FROM queues WHERE status = 'Waiting' ORDER BY id ASC");
    
    $waiting_list = [];
    while($r = $waiting->fetch_assoc()) {
        $waiting_list[] = $r['queue_number'];
    }

    $latest_ir = $conn->query("SELECT status_value FROM sensor_logs WHERE sensor_type = 'IR_Sensor' ORDER BY id DESC LIMIT 1")->fetch_assoc();

    echo json_encode([
        "is_active" => $counter ? (bool) $counter['is_active'] : false,
        "now_serving" => $serving['queue_number'] ?? "None",
        "next_in_line" => $waiting_list,
        "ir_status" => $latest_ir['status_value'] ?? "Clear"
    ]);
}

$conn->close();
?>
