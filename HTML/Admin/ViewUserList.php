<?php
// Ensure session is started for any potential admin authentication logic
session_start();


include '../../connect.php'; // Your database connection file (adjust path if necessary)

// Handle ban/unban form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ban_email'], $_POST['action'])) {
    $email = $_POST['ban_email'];
    $action = $_POST['action'];

    // Always target the 'guest' table
    $table = 'guest';
    $emailCol = 'guest_email';
    $statusCol = 'isBan'; 

    $status = ($action === 'ban') ? 1 : 0; 

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("UPDATE $table SET $statusCol = ? WHERE $emailCol = ?");
    $stmt->bind_param("is", $status, $email); // 'i' for integer (isBan), 's' for string (email)

    if ($stmt->execute()) {
        // Successfully updated.
        // Optional: add a success message or log this action.
    } else {
        // Error handling for database update
        error_log("Error updating ban status for $email: " . $stmt->error);
    }
    $stmt->close();

    // Redirect to prevent form resubmission on refresh, preserving current filter/search
    $redirect_url = 'ViewUserList.php?type=' . urlencode($_GET['type'] ?? 'active'); // Default to 'active'
    if (isset($_GET['search_id']) && !empty($_GET['search_id'])) {
        $redirect_url .= '&search_id=' . urlencode($_GET['search_id']);
    }
    header("Location: " . $redirect_url);
    exit();
}

// Determine current filter type (active, banned)
$type = $_GET['type'] ?? 'active'; // Default to 'active' guests

// Handle search by ID
$search_id = $_GET['search_id'] ?? '';
$search_condition = '';
$search_param_type = '';
$search_param_value = null;

if (!empty($search_id)) {
    // Search condition for guest_id. Use LIKE for partial matching if desired, or '=' for exact.
    // Using '=' for exact match as per "search user by id".
    $search_condition = " AND guest_id = ?";
    // Since guest_id is VARCHAR, bind as string 's'
    $search_param_type = 's';
    $search_param_value = $search_id;
}

// Base query construction for guests only, using correct column names from DB screenshot
$query = "SELECT
            guest_id,
            guest_name,
            guest_email,
            guest_phone_number,
            guest_profile_picture,
            isBan
          FROM guest";

// Add WHERE clause based on filter type
if ($type === 'active') {
    $query .= " WHERE isBan = 0";
} elseif ($type === 'banned') {
    $query .= " WHERE isBan = 1";
}
// If $type is anything else, it will fetch all, but our buttons only send 'active' or 'banned'.

// Add search condition if an ID is provided
$query .= $search_condition;

// Prepare and execute the final query
$stmt = $conn->prepare($query);

// Bind parameter if searching
if (!empty($search_id)) {
    $stmt->bind_param($search_param_type, $search_param_value);
}

$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Guest User List - Admin Panel</title>
  <link rel="stylesheet" href="css/ViewUserList.css" />
  <link rel="stylesheet" href="css/adminheadersheet.css" />
</head>
<body>

  <?php include 'adminheader.html'; ?> <main class="content">
    <h1>Guest User List</h1>

    <div class="action-bar">
        <div class="filter-bar">
            <button onclick="filterUsers('active')" class="<?= $type === 'active' ? 'active' : '' ?>">Active Guests</button>
            <button onclick="filterUsers('banned')" class="<?= $type === 'banned' ? 'active' : '' ?>">Banned Guests</button>
        </div>
        <div class="search-bar">
            <input
                type="text" id="searchIdInput"
                placeholder="Search by ID..."
                value="<?= htmlspecialchars($search_id) ?>"
                onkeypress="handleSearchKeyPress(event)"
            />
            <button onclick="searchUsers()">Search</button>
        </div>
    </div>


    <div class="user-container">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="user-card">
            <div class="user-avatar-wrapper">
                <?php
                // guest_profile_picture column contains the path from your site root, e.g., "uploads/profile_pictures/filename.png"
                $profilePicFromDB = htmlspecialchars($row['guest_profile_picture']);

            
                $fullRelativePath = '../../' . $profilePicFromDB;

                // Check if the path is not empty and the file actually exists
                if (!empty($profilePicFromDB) && file_exists($fullRelativePath)) {
                    echo '<img src="' . $fullRelativePath . '" alt="' . htmlspecialchars($row['guest_name']) . '" class="user-profile-pic">';
                } else {
                    echo '<div class="user-avatar-placeholder">' . strtoupper(substr($row['guest_name'], 0, 1)) . '</div>';
                }
                ?>
            </div>
            <div class="user-details">
              <span class="user-name"><?= htmlspecialchars($row['guest_name']) ?> (ID: <?= htmlspecialchars($row['guest_id']) ?>)</span>
              <span class="user-email"><?= htmlspecialchars($row['guest_email']) ?></span>
              <span class="user-phone">Phone: <?= htmlspecialchars($row['guest_phone_number'] ?? 'N/A') ?></span>
            </div>
            <div class="user-actions">
              <form method="POST" onsubmit="return confirmBan(this);">
                <input type="hidden" name="ban_email" value="<?= htmlspecialchars($row['guest_email']) ?>">
                <?php if ($row['isBan'] == 1): // Use isBan as per database column ?>
                  <input type="hidden" name="action" value="unban">
                  <button type="submit" class="ban-btn unban-btn">✅ Unban</button>
                <?php else: ?>
                  <input type="hidden" name="action" value="ban">
                  <button type="submit" class="ban-btn ban-user-btn">🚫 Ban</button>
                <?php endif; ?>
              </form>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="no-users-message">No guest users found for the current selection or search criteria.</p>
      <?php endif; ?>
    </div>
  </main>

  <script>
    // JavaScript function to handle filter button clicks
    function filterUsers(type) {
      const searchId = document.getElementById('searchIdInput').value;
      let url = `ViewUserList.php?type=${type}`;
      if (searchId) { // Preserve search ID if it exists
        url += `&search_id=${searchId}`;
      }
      window.location.href = url;
    }

    // JavaScript function to handle search button click
    function searchUsers() {
      const type = '<?= htmlspecialchars($type) ?>'; // Get current filter type from PHP
      const searchId = document.getElementById('searchIdInput').value;
      window.location.href = `ViewUserList.php?type=${type}&search_id=${searchId}`;
    }

    // JavaScript function to handle Enter key press in search input
    function handleSearchKeyPress(event) {
        if (event.key === 'Enter') {
            searchUsers();
        }
    }

    // JavaScript function for ban/unban confirmation
    function confirmBan(form) {
      const email = form.querySelector('input[name="ban_email"]').value;
      const action = form.querySelector('input[name="action"]').value;
      return confirm(`Are you sure you want to ${action} ${email}?`);
    }
  </script>
</body>
</html>