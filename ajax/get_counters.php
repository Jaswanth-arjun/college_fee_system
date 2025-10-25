<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_student_auth();
$db = new Database();
$conn = $db->getConnection();

try {
        $sql = "SELECT c.*, GROUP_CONCAT(ft.name) as fee_types, 
                   a.is_online as accountant_online,
                   CASE 
                       WHEN a.is_online = 1 THEN 'Active - Online' 
                       WHEN a.is_online = 0 THEN 'Inactive - Offline'
                       ELSE 'Inactive - No Accountant'
                   END as status_display
            FROM counters c 
            LEFT JOIN counter_fee_types cft ON c.id = cft.counter_id 
            LEFT JOIN fee_types ft ON cft.fee_type_id = ft.id 
            LEFT JOIN accountants a ON c.id = a.counter_id AND a.is_active = 1
            WHERE c.is_active = 1
            GROUP BY c.id 
            ORDER BY a.is_online DESC, c.name";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $counters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($counters);
} catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to load counters']);
}
?>