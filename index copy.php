<?php
$conn = new mysqli("localhost", "root", "", "email_id");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csv_file"])) {
    $file = $_FILES["csv_file"]["tmp_name"];
    $handle = fopen($file, "r");
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $email = $data[0];
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            list($username, $domain) = explode("@", $email);
            $conn->query("INSERT INTO email (raw_emailid, sp_account, sp_domain) VALUES ('$email', '$username', '$domain')");
        }
    }
    fclose($handle);
}

if (isset($_GET["delete"])) {
    $id = $_GET["delete"];
    $conn->query("DELETE FROM email WHERE id='$id'");
}

$result = $conn->query("SELECT * FROM email");
$emails = [];
while ($row = $result->fetch_assoc()) {
    $emails[] = $row;
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

    <div class="flex flex-col items-center space-y-6 ">
        <!-- Glassmorphic Container for Form -->
        <div
            class="w-full p-6 bg-white bg-opacity-20 backdrop-blur-md rounded-lg shadow-lg border border-white/30 w-full max-w-lg">
            <h2 class="text-2xl font-bold text-white text-center mb-4">Email Split</h2>
            <form method="post" enctype="multipart/form-data" class="flex flex-col items-center space-y-3">
                <input type="file" name="csv_file" required
                    class="w-full p-2 rounded border bg-white bg-opacity-50 text-gray-800">
                <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow-md hover:bg-blue-700 transition">
                    Upload
                </button>
            </form>
        </div>

        <!-- Table Container -->
        <div id="email-table" class="w-5/6"></div>

    </div>

    <script>
        new gridjs.Grid({
            columns: ["ID", "Email", "sp_account", "sp_domain", {
                name: "Actions",
                // formatter: (cell, row) => gridjs.html(`<a href='?delete=${row.cells[0].data}' class='delete-btn'>Delete</a>`)
                formatter: (cell, row) => gridjs.html(`<a href='?delete=${row.cells[0].data}' class='delete-btn' style='color: white; background-color: red; padding: 5px 10px; border-radius: 5px; text-decoration: none;'>Delete</a> `)
            }],
            data: <?php echo json_encode(array_map(function ($row) {
                return [$row['id'], $row['raw_emailid'], $row['sp_account'], $row['sp_domain']];
            }, $emails)); ?>,
            pagination: true,
            search: true,
            sort: true,
            theme: 'mermaid'
        }).render(document.getElementById("email-table"));
    </script>
</body>

</html>