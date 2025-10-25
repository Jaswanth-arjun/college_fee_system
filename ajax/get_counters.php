<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_student_auth();
$db = new Database();
$conn = $db->getConnection();

$sql = "SELECT c.id, c.name, c.location, 
               GROUP_CONCAT(ft.name SEPARATOR ', ') as fee_types
        FROM counters c
        LEFT JOIN counter_fee_types cft ON c.id = cft.counter_id
        LEFT JOIN fee_types ft ON cft.fee_type_id = ft.id
        WHERE c.is_active = 1
        GROUP BY c.id";
$stmt = $conn->prepare($sql);
$stmt->execute();
$counters = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($counters);
?>