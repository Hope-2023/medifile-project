<?php
include 'connect.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit();
}

$sql = "SELECT patient_id, sa_id_number, first_name, last_name, date_of_birth, gender, phone_number, preferred_lang FROM patients";
$result = $conn->query($sql);

$patients = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
    echo json_encode([
        "status" => "success",
        "data" => $patients
    ]);
} else {
    echo json_encode([
        "status" => "success",
        "data" => [],
        "message" => "No patients found"
    ]);
}

$conn->close();
?>