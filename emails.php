<?php
$conn = new mysqli("localhost", "root", "", "email_id");

// Pagination parameters
$limit = 10; // Number of records per page (adjust as needed)
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch records with pagination
$result = $conn->query("SELECT * FROM email LIMIT $limit OFFSET $offset");
$emails = [];
while ($row = $result->fetch_assoc()) {
    $emails[] = $row;
}

// Count total records
$totalResult = $conn->query("SELECT COUNT(*) as total FROM email");
$totalRecords = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Handle CSV Upload in Batches
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csv_file"])) {
    $file = $_FILES["csv_file"]["tmp_name"];
    $handle = fopen($file, "r");

    $batchSize = 500; // Insert records in batches
    $batchData = [];

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $email = $data[0];
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            list($username, $domain) = explode("@", $email);
            $batchData[] = "('$email', '$username', '$domain')";

            if (count($batchData) >= $batchSize) {
                $conn->query("INSERT INTO email (raw_emailid, sp_account, sp_domain) VALUES " . implode(",", $batchData));
                $batchData = [];
            }
        }
    }

    if (!empty($batchData)) {
        $conn->query("INSERT INTO email (raw_emailid, sp_account, sp_domain) VALUES " . implode(",", $batchData));
    }

    fclose($handle);
}

// Handle delete request
if (isset($_GET["delete"])) {
    $id = $_GET["delete"];
    $stmt = $conn->prepare("DELETE FROM email WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    exit; // Avoid loading the whole page again
}
?>