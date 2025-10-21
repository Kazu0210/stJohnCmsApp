<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$sql = "SELECT status, COUNT(*) as count FROM lots WHERE status IN ('Available', 'Reserved', 'Occupied') GROUP BY status";
$result = $conn->query($sql);

$counts = [
	'Available' => 0,
	'Reserved' => 0,
	'Occupied' => 0
];

if ($result) {
	while ($row = $result->fetch_assoc()) {
		$status = $row['status'];
		$count = (int)$row['count'];
		if (isset($counts[$status])) {
			$counts[$status] = $count;
		}
	}
	echo json_encode([
		'status' => 'success',
		'message' => 'Lot classification counts fetched successfully',
		'data' => $counts
	]);
} else {
	echo json_encode([
		'status' => 'error',
		'message' => 'Failed to fetch lot classification counts',
		'data' => $counts
	]);
}
$conn->close();
