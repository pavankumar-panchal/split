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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Split</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
    <script src="https://unpkg.com/gridjs/dist/gridjs.umd.js"></script>
    <link href="https://unpkg.com/gridjs/dist/theme/mermaid.min.css" rel="stylesheet" />
</head>
<body class="h-screen flex items-center justify-center bg-gradient-to-r from-blue-400 to-purple-500">
    <div class="flex flex-col items-center space-y-6">
        <div class="w-full p-6 bg-white bg-opacity-20 backdrop-blur-md rounded-lg shadow-lg border border-white/30 w-full max-w-lg">
            <h2 class="text-2xl font-bold text-white text-center mb-4">Email Split</h2>
            <form id="upload-form" method="post" enctype="multipart/form-data" class="flex flex-col items-center space-y-3">
                <input type="file" name="csv_file" required class="w-full p-2 rounded border bg-white bg-opacity-50 text-gray-800">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow-md hover:bg-blue-700 transition">Upload</button>
            </form>
        </div>

        <div id="email-table" class="w-5/6"></div>

        <!-- Pagination Controls -->
        <div id="pagination" class="flex space-x-2"></div>
    </div>

    <script>
        let currentPage = 1;

        function fetchEmails(page = 1) {
            fetch(`emails.php?page=${page}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById("email-table").innerHTML = "";
                    new gridjs.Grid({
                        columns: ["ID", "Email", "sp_account", "sp_domain", {
                            name: "Actions",
                            formatter: (cell, row) => gridjs.html(`<a href="#" onclick="deleteEmail(${row.cells[0].data})" class="delete-btn" style="color: white; background-color: red; padding: 5px 10px; border-radius: 5px; text-decoration: none;">Delete</a> `)
                        }],
                        data: data.records,
                        theme: 'mermaid'
                    }).render(document.getElementById("email-table"));

                    // Update pagination
                    updatePagination(data.totalPages, page);
                });
        }

        function updatePagination(totalPages, page) {
            let pagination = document.getElementById("pagination");
            pagination.innerHTML = "";
            for (let i = 1; i <= totalPages; i++) {
                let btn = document.createElement("button");
                btn.innerText = i;
                btn.classList.add("px-3", "py-1", "rounded-lg", i === page ? "bg-blue-600 text-white" : "bg-gray-300");
                btn.onclick = () => fetchEmails(i);
                pagination.appendChild(btn);
            }
        }

        function deleteEmail(id) {
            fetch(`emails.php?delete=${id}`)
                .then(() => fetchEmails(currentPage));
        }

        document.getElementById("upload-form").addEventListener("submit", function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            fetch("emails.php", { method: "POST", body: formData })
                .then(() => fetchEmails());
        });

        fetchEmails();
    </script>
</body>
</html>
