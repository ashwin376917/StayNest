<?php
// Ensure session is started and admin is logged in
session_start();

// !!! IMPORTANT !!!
// Implement robust admin authentication here. This is a placeholder.
// For example:
// if (!isset($_SESSION['loggedin']) || $_SESSION['user_role'] !== 'admin') {
//     header("Location: ../../HTML/Home/login.php"); // Redirect to login if not admin
//     exit();
// }

require_once '../../connect.php'; // Your database connection file (adjust path if necessary)

// Handle approve/set_pending form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['host_id'], $_POST['action'])) {
    $hostId = $_POST['host_id'];
    $action = $_POST['action'];

    $table = 'host';
    $idCol = 'host_id';
    $statusCol = 'isApprove'; // Column for approval status

    // Determine the status value based on the action
    $status = null;
    if ($action === 'approve') {
        $status = 2; // Approved
    } elseif ($action === 'set_pending') { // Only 'set_pending' remains for changing state to 1
        $status = 1; // Pending
    }

    if ($status !== null) {
        // Use prepared statements to prevent SQL injection
        $stmt = $conn->prepare("UPDATE $table SET $statusCol = ? WHERE $idCol = ?");
        $stmt->bind_param("is", $status, $hostId); // 'i' for integer (isApprove), 's' for string (host_id)

        if ($stmt->execute()) {
            // Successfully updated.
            // Optional: add a success message or log this action.
        } else {
            // Error handling for database update
            error_log("Error updating approval status for Host ID $hostId: " . $stmt->error);
        }
        $stmt->close();
    }

    // Redirect to prevent form resubmission on refresh, preserving current filter/search
    $redirect_url = 'ViewHostList.php?type=' . urlencode($_GET['type'] ?? 'pending'); // Default to 'pending'
    if (isset($_GET['search_id']) && !empty($_GET['search_id'])) {
        $redirect_url .= '&search_id=' . urlencode($_GET['search_id']);
    }
    header("Location: " . $redirect_url);
    exit();
}

// Determine current filter type (pending, approved, unapproved)
// Default to 'pending' as requested, to only fetch where isApprove = 1
$type = $_GET['type'] ?? 'pending';

// Handle search by Host ID
$search_id = $_GET['search_id'] ?? '';
$search_condition = '';
$search_param_type = '';
$search_param_value = null;

if (!empty($search_id)) {
    // Search for exact host_id. Since host_id is VARCHAR, bind as string 's'
    $search_condition = " AND h.host_id = ?";
    $search_param_type = 's';
    $search_param_value = $search_id;
}

// Base query construction for hosts
// JOIN with the guest table to get guest_name, guest_email, guest_phone_number
$query = "SELECT
            h.host_id,
            h.guest_id,
            g.guest_name,        -- Fetched from guest table
            g.guest_email,       -- Fetched from guest table
            g.guest_phone_number,-- Fetched from guest table
            h.isApprove          -- Fetched from host table
          FROM host h
          JOIN guest g ON h.guest_id = g.guest_id"; // Join condition

// Add WHERE clause based on filter type
if ($type === 'pending') {
    $query .= " WHERE h.isApprove = 1"; // Pending hosts
} elseif ($type === 'approved') {
    $query .= " WHERE h.isApprove = 2"; // Approved hosts
}
// Removed 'unapproved' filter option, as it's no longer managed through UI actions to that state.
// If no type is explicitly set (e.g., initial load without type in URL), it will default to 'pending' due to the line above.

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
  <title>Host Approval List - Admin Panel</title>
  <link rel="stylesheet" href="css/ViewUserList.css" />
  <link rel="stylesheet" href="css/adminheadersheet.css" />
  <style>
    /* Specific styles for host approval list */
    .user-card.pending-host {
        background-color: #fffacd; /* Light yellow for pending */
        border: 1px solid #ffd700; /* Gold border */
    }
    .user-card.approved-host {
        background-color: #e6ffe6; /* Light green for approved */
        border: 1px solid #4CAF50; /* Green border */
    }
    /* Removed .user-card.unapproved-host styles as the option to explicitly set to 0 is gone */

    /* Adjust button colors for host actions */
    .approve-btn {
        background-color: #28a745; /* Green for Approve */
    }
    .approve-btn:hover {
        background-color: #218838;
    }
    /* Removed .unapprove-btn styles */
    .set-pending-btn { /* New button for setting to pending */
        background-color: #ffc107; /* Orange for Set Pending */
        color: #333; /* Dark text for contrast */
    }
    .set-pending-btn:hover {
        background-color: #e0a800;
    }

    /* Optional: Style for the status indicator text */
    .user-details .status-indicator {
        font-size: 13px;
        font-weight: bold;
        margin-top: 5px;
        padding: 3px 8px;
        border-radius: 5px;
        display: inline-block; /* Allows padding and background to wrap content */
    }
    /* Specific colors for status indicator text */
    .pending-host .status-indicator {
        color: #DAA520; /* Darker yellow/gold */
        background-color: #FFFACD; /* Match card background */
    }
    .approved-host .status-indicator {
        color: #218838; /* Darker green */
        background-color: #e6ffe6; /* Match card background */
    }
    /* Removed .unapproved-host .status-indicator styles */

    /* Responsive adjustments from previous CSS are mostly generic and will apply */
  </style>
</head>
<body>

  <?php include 'adminheader.html'; ?>
  <main class="content">
    <h1>Host Approval List</h1>

    <div class="action-bar">
        <div class="filter-bar">
            <button onclick="filterHosts('pending')" class="<?= $type === 'pending' ? 'active' : '' ?>">Pending Hosts</button>
            <button onclick="filterHosts('approved')" class="<?= $type === 'approved' ? 'active' : '' ?>">Approved Hosts</button>
            </div>
        <div class="search-bar">
            <input
                type="text"
                id="searchIdInput"
                placeholder="Search by Host ID..."
                value="<?= htmlspecialchars($search_id) ?>"
                onkeypress="handleSearchKeyPress(event)"
            />
            <button onclick="searchHosts()">Search</button>
        </div>
    </div>


    <div class="user-container">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()):
            $cardClass = '';
            switch ($row['isApprove']) {
                case 1: $cardClass = 'pending-host'; break; // Pending
                case 2: $cardClass = 'approved-host'; break; // Approved
                case 0: $cardClass = 'unapproved-host'; break; // Unapproved. Still includes the class for display if a host is already 0
            }
        ?>
          <div class="user-card <?= $cardClass ?>">
            <div class="user-details">
              <span class="user-name">Host: <?= htmlspecialchars($row['guest_name']) ?> (ID: <?= htmlspecialchars($row['host_id']) ?>)</span>
              <span class="user-email">Guest ID: <?= htmlspecialchars($row['guest_id'] ?? 'N/A') ?></span>
              <span class="user-email">Email: <?= htmlspecialchars($row['guest_email']) ?></span>
              <span class="user-phone">Phone: <?= htmlspecialchars($row['guest_phone_number'] ?? 'N/A') ?></span>
              <span class="status-indicator">Status:
                <?php
                    if ($row['isApprove'] == 1) echo 'Pending'; //
                    else if ($row['isApprove'] == 2) echo 'Approved'; //
                    else echo 'Unapproved'; // Still displays if the status is 0, even if it cannot be set from UI
                ?>
              </span>
            </div>
            <div class="user-actions">
              <form method="POST" onsubmit="return confirmApprovalAction(this);">
                <input type="hidden" name="host_id" value="<?= htmlspecialchars($row['host_id']) ?>">
                <?php if ($row['isApprove'] == 1): // Currently Pending ?>
                  <input type="hidden" name="action" value="approve">
                  <button type="submit" class="ban-btn approve-btn">✅ Approve</button>
                  <?php elseif ($row['isApprove'] == 2): // Currently Approved ?>
                  <input type="hidden" name="action" value="set_pending">
                  <button type="submit" class="ban-btn set-pending-btn">🟡 Set Pending</button>
                <?php else: // Currently Unapproved (isApprove == 0) ?>
                  <input type="hidden" name="action" value="approve">
                  <button type="submit" class="ban-btn approve-btn">✅ Approve</button>
                  <input type="hidden" name="action" value="set_pending">
                  <button type="submit" class="ban-btn set-pending-btn">🟡 Set Pending</button>
                <?php endif; ?>
              </form>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="no-users-message">No hosts found for the current selection or search criteria.</p>
      <?php endif; ?>
    </div>
  </main>

  <script>
    // JavaScript function to handle filter button clicks
    function filterHosts(type) {
      const searchId = document.getElementById('searchIdInput').value;
      let url = `ViewHostList.php?type=${type}`;
      if (searchId) { // Preserve search ID if it exists
        url += `&search_id=${searchId}`;
      }
      window.location.href = url;
    }

    // JavaScript function to handle search button click
    function searchHosts() {
      const type = '<?= htmlspecialchars($type) ?>'; // Get current filter type from PHP
      const searchId = document.getElementById('searchIdInput').value;
      window.location.href = `ViewHostList.php?type=${type}&search_id=${searchId}`;
    }

    // JavaScript function to handle Enter key press in search input
    function handleSearchKeyPress(event) {
        if (event.key === 'Enter') {
            searchHosts();
        }
    }

    // JavaScript function for approval action confirmation
    function confirmApprovalAction(form) {
      const hostId = form.querySelector('input[name="host_id"]').value;
      const action = form.querySelector('input[name="action"]').value;
      let message = `Are you sure you want to proceed with this action for Host ID ${hostId}?`;

      if (action === 'approve') {
        message = `Are you sure you want to APPROVE Host ID ${hostId}?`;
      } else if (action === 'set_pending') {
        message = `Are you sure you want to set Host ID ${hostId} back to PENDING status?`;
      }
      // Removed confirmation message for 'unapprove' action
      return confirm(message);
    }
  </script>
</body>
</html>