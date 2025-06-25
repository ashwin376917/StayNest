<!-- 
require_once '../../connect.php'; // Adjust the path if needed

for ($i = 1; $i <= 10; $i++) {
    $adminId = 'A' . str_pad($i, 3, '0', STR_PAD_LEFT); // Example: A001, A002...
    $adminName = "Admin $i";
    $adminEmail = "admin$i@example.com";
    $adminPhone = "0123456" . str_pad($i, 3, '0', STR_PAD_LEFT);
    $plainPassword = "1234";
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO admin (admin_id, admin_name, admin_email, admin_password, admin_phone_number) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $adminId, $adminName, $adminEmail, $hashedPassword, $adminPhone);

    if ($stmt->execute()) {
        echo "Inserted: $adminName ($adminEmail)<br>";
    } else {
        echo "Failed to insert $adminName: " . $stmt->error . "<br>";
    }

    $stmt->close();
}

echo "<br>Dummy admin creation completed.";
 -->
