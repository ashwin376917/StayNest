<?php
// Ensure session is started for any potential admin authentication logic
session_start();

include '../../connect.php'; // Your database connection file (adjust path if necessary)

// Initialize message variables
$success_message = '';
$error_message = '';

// Handle property deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['homestay_id'], $_POST['action'])) {
    $homestay_id = $_POST['homestay_id'];
    $action = $_POST['action']; // 'delete'

    if ($action === 'delete') {
        // --- STEP 1: Check if the homestay has active or future bookings ---
        $check_booking_stmt = $conn->prepare("SELECT COUNT(*) FROM booking WHERE homestay_id = ? AND check_out_date >= CURDATE()");
        $check_booking_stmt->bind_param("s", $homestay_id);
        $check_booking_stmt->execute();
        $check_booking_result = $check_booking_stmt->get_result();
        $booking_count = $check_booking_result->fetch_row()[0];
        $check_booking_stmt->close();

        if ($booking_count > 0) {
            // Homestay is occupied or has future bookings, prevent deletion
            $_SESSION['error_message'] = "Cannot delete homestay with ID: {$homestay_id}. It has active or future bookings.";
        } else {
            // No active or future bookings, proceed with deletion
            $stmt = $conn->prepare("DELETE FROM homestay WHERE homestay_id = ?");
            $stmt->bind_param("s", $homestay_id);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Homestay with ID: {$homestay_id} successfully deleted.";
            } else {
                $_SESSION['error_message'] = "Error deleting homestay with ID: {$homestay_id}. " . $stmt->error;
                error_log("Error deleting homestay with ID $homestay_id: " . $stmt->error);
            }
            $stmt->close();
        }
    }

    // Redirect to prevent form resubmission on refresh, preserving current search
    $redirect_url = 'ViewPropertyInfo.php';
    if (isset($_GET['search_id']) && !empty($_GET['search_id'])) {
        $redirect_url .= '?search_id=' . urlencode($_GET['search_id']);
    }
    header("Location: " . $redirect_url);
    exit();
}

// Handle search by homestay_id
$search_id = $_GET['search_id'] ?? '';
$search_condition = '';
$search_param_type = '';
$search_param_value = null;

// Base query construction for homestays
$query = "SELECT * FROM homestay"; // Select all columns for display

if (!empty($search_id)) {
    // Search condition for homestay_id. Using '=' for exact match.
    $search_condition = " WHERE homestay_id = ?";
    // Assuming homestay_id is a string/varchar, bind as 's'.
    $search_param_type = 's';
    $search_param_value = $search_id;
    $query .= $search_condition; // Apply the search condition
}

// Prepare and execute the final query
$stmt = $conn->prepare($query);

// Bind parameter if searching
if (!empty($search_id)) {
    $stmt->bind_param($search_param_type, $search_param_value);
}

$stmt->execute();
$result = $stmt->get_result();

// Function to map homestay_status to text and color
// This function will now also fetch booking dates if status is 'Occupied'
function mapStatus($conn, $homestay_id, $status) {
    if ($status == 0) return ["Not Occupied", "green"];
    if ($status == 1) {
        // If occupied, fetch current or next booking dates
        $booking_dates = ["", ""]; // Default empty
        $booking_query = $conn->prepare(
            "SELECT check_in_date, check_out_date FROM booking
             WHERE homestay_id = ? AND check_out_date >= CURDATE()
             ORDER BY check_in_date ASC LIMIT 1"
        );
        $booking_query->bind_param("s", $homestay_id);
        $booking_query->execute();
        $booking_result = $booking_query->get_result();
        if ($booking_row = $booking_result->fetch_assoc()) {
            $booking_dates[0] = htmlspecialchars($booking_row['check_in_date']);
            $booking_dates[1] = htmlspecialchars($booking_row['check_out_date']);
        }
        $booking_query->close();

        if (!empty($booking_dates[0]) && !empty($booking_dates[1])) {
            return ["Occupied: {$booking_dates[0]} to {$booking_dates[1]}", "red"];
        } else {
            // Fallback if status is 1 but no active booking dates found
            return ["Occupied (No Dates)", "red"];
        }
    }
    if ($status == 2) return ["Banned", "gray"];
    return ["Unknown", "gray"];
}

// Retrieve and clear session messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin View Properties</title>
    <link rel="stylesheet" href="css/adminheadersheet.css" />
    <style>


        /* Basic Body Styling */
        body {
            margin: 0;
            font-family: 'NType', sans-serif; /* Use your custom font */
            background-color: #f9f9f9;
            color: #333; /* Default text color */
            line-height: 1.6;
        }

        /* Main Content Area */
        .content {
            max-width: 960px; /* Wider content area for better layout */
            margin: 30px auto; /* Center the content */
            padding: 0 25px 140px; /* Padding, with extra space at bottom for sticky bar */
            box-sizing: border-box; /* Include padding in element's total width and height */
        }

        h1 {
            margin-bottom: 30px;
            font-size: 32px; /* Larger heading */
            color: #1a1a1a;
            text-align: center;
        }

        /* --- ACTION BAR (Now only contains Search) --- */
        .action-bar {
            position: sticky; /* Make it sticky */
            top: 0px; /* Position at the top, adjust if your header overlaps */
            left: 0;
            right: 0;
            z-index: 999; /* Ensure it stays on top of other content when scrolling */
            background: #f9f9f9; /* Match body background for seamless look */
            padding: 20px 0; /* Vertical padding */
            display: flex;
            flex-wrap: wrap; /* Allow items to wrap on smaller screens */
            justify-content: center; /* Center the search bar */
            align-items: center;
            border-bottom: 1px solid #eee; /* Subtle separator at the bottom */
            margin-bottom: 25px; /* Space between action bar and property list */
        }

        /* Filter bar is removed */
        .filter-bar {
            display: none; /* Hide filter bar */
        }

        /* Search Bar Styling */
        .search-bar {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-bar input[type="text"] {
            padding: 8px 15px;
            border: 1px solid #ccc;
            border-radius: 25px; /* Rounded input field */
            font-size: 15px;
            outline: none; /* Remove default outline on focus */
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            width: 250px; /* Increased width for search input */
        }

        .search-bar input[type="text"]:focus {
            border-color: #007bff; /* Blue border on focus */
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2); /* Soft blue glow on focus */
        }

        .search-bar button {
            padding: 8px 18px;
            background-color: #007bff; /* Blue search button */
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .search-bar button:hover {
            background-color: #0056b3; /* Darker blue on hover */
            transform: translateY(-1px); /* Slight lift effect */
        }

        .search-bar button:active {
            transform: translateY(0); /* Reset on click */
        }


        /* --- PROPERTY LIST CONTAINER (mimics user-container) --- */
        .property-list-container {
            display: flex;
            flex-direction: column; /* Stack property cards vertically */
            gap: 15px; /* Space between property cards */
        }

        /* --- INDIVIDUAL PROPERTY CARD (List View Style, mimics user-card) --- */
        .property-list-item {
            display: flex;
            align-items: center; /* Vertically align items in the card */
            background: white;
            border-radius: 12px; /* Rounded corners for the card */
            padding: 15px 25px; /* Internal padding */
            color: #333;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.07); /* Soft, subtle shadow */
            transition: transform 0.2s ease, box-shadow 0.2s ease; /* Smooth hover effects */
            min-height: 100px; /* Adjust height for property content */
        }

        .property-list-item:hover {
            transform: translateY(-2px); /* Slight lift on hover */
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1); /* Enhanced shadow on hover */
        }

        /* Property Image Wrapper (mimics user-avatar-wrapper) */
        .property-image-wrapper {
            width: 100px; /* Fixed width for image */
            height: 80px; /* Fixed height for image */
            border-radius: 8px; /* Slightly rounded corners for image */
            overflow: hidden; /* Clips content outside */
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e0e0e0; /* Placeholder background color */
            flex-shrink: 0; /* Prevent it from shrinking */
            margin-right: 20px; /* Space between image and property details */
            border: 1px solid #ddd; /* Subtle border around the image */
        }

        .property-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Ensures the image covers the entire area, cropping if necessary */
        }

        /* Property Details (Title, Host ID, Location, Price, Guests, Status) */
        .property-details {
            flex-grow: 1; /* Allows details to take up available space */
            display: flex;
            flex-direction: column; /* Stack details vertically */
            margin-right: 20px;
        }

        .property-details h3 {
            font-size: 18px; /* Larger font size for the title */
            font-weight: bold;
            color: #1a1a1a;
            margin: 0 0 5px 0; /* Space below the title */
        }

        .property-details p {
            font-size: 14px;
            color: #666;
            margin: 0 0 2px 0; /* Small space between paragraphs */
        }

        .property-details p strong {
            color: #444; /* Make labels slightly darker */
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px; /* More rounded badge */
            color: white;
            font-size: 0.85em;
            font-weight: bold;
            margin-top: 8px; /* Space above the badge */
            text-transform: capitalize; /* Ensure first letter is capitalized */
        }

        .status-badge.orange { background-color: #ff9800; } /* Slightly more vibrant orange */
        .status-badge.green { background-color: #4CAF50; } /* Standard green */
        .status-badge.red { background-color: #F44336; } /* Standard red */
        .status-badge.gray { background-color: #9E9E9E; } /* Standard gray */


        .property-actions {
            flex-shrink: 0; /* Prevent the actions button from shrinking */
        }

        /* Delete Button Styling (mimics ban-btn) */
        .delete-btn {
            background-color: #dc3545; /* Red for Delete button */
            color: white;
            border: none;
            border-radius: 25px; /* Rounded button */
            padding: 10px 20px; /* Padding for the button */
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease; /* Smooth transitions */
            white-space: nowrap; /* Prevent button text from wrapping */
        }

        .delete-btn:hover {
            opacity: 0.9; /* Slightly transparent on hover */
            transform: translateY(-1px); /* Slight lift */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Subtle shadow on hover */
        }

        .delete-btn:active {
            transform: translateY(0); /* Reset on click */
            box-shadow: none; /* Remove shadow on click */
        }

        /* Message when no properties are found (mimics no-users-message) */
        .no-properties-message {
            text-align: center;
            padding: 40px;
            font-size: 18px;
            color: #888;
            background-color: white;
            border-radius: 12px;
            margin-top: 30px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.07);
        }

        /* Message Box Styling */
        .message-box {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 16px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .message-box.success {
            background-color: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .message-box.error {
            background-color: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }

        /* --- RESPONSIVE ADJUSTMENTS (adapted for properties) --- */
        @media (max-width: 768px) {
            .content {
                padding: 0 15px 120px; /* Adjust padding for smaller screens */
            }

            .action-bar {
                flex-direction: column; /* Stack search bar vertically */
                gap: 15px; /* Space between stacked items */
                padding: 15px;
                position: static; /* Remove sticky on smaller screens if it causes layout issues */
                border-bottom: none; /* Remove border if no longer sticky */
                margin-bottom: 20px;
            }

            .search-bar {
                width: 100%; /* Full width */
                justify-content: center; /* Center search elements */
            }

            .search-bar input[type="text"] {
                flex-grow: 1; /* Allow input to take more space */
                max-width: 250px; /* Limit max width for input */
            }

            .property-list-item {
                flex-direction: column; /* Stack property card details vertically */
                align-items: flex-start; /* Align content to the start */
                padding: 20px;
                height: auto; /* Allow height to adjust based on content */
            }

            .property-image-wrapper {
                margin-bottom: 15px; /* Space below image when stacked */
                margin-right: 0;
            }

            .property-details {
                margin-bottom: 15px; /* Space below details when stacked */
                margin-right: 0;
            }

            .property-details h3,
            .property-details p {
                text-align: left; /* Ensure text aligns left */
            }

            .property-actions {
                width: 100%; /* Make button container full width */
                text-align: center; /* Center the button within its container */
            }
            .delete-btn {
                width: 80%; /* Button takes more width */
                max-width: 200px; /* Limit button max width */
            }
        }
    </style>
</head>
<body>

    <?php include 'adminheader.html'; ?>

    <main class="content">
        <h1>Property List</h1>

        <?php if ($success_message): ?>
            <div class="message-box success">
                <?= $success_message ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message-box error">
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <div class="action-bar">
            <div class="search-bar">
                <input
                    type="text" id="searchIdInput"
                    placeholder="Search by Homestay ID..."
                    value="<?= htmlspecialchars($search_id) ?>"
                    onkeypress="handleSearchKeyPress(event)"
                />
                <button onclick="searchProperties()">Search</button>
            </div>
        </div>

        <div class="property-list-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()):
                    // Pass $conn to mapStatus to allow it to query the database
                    [$statusText, $statusColor] = mapStatus($conn, $row['homestay_id'], $row['homestay_status']);
                    $picture1 = htmlspecialchars($row['picture1']);
                    // Adjust the image path to be relative to the CSS/PHP file for the browser
                    $imageUrl = !empty($picture1) ? "../Host/{$picture1}" : "https://via.placeholder.com/100x80?text=No+Image";

                    // Determine if delete button should be disabled
                    $is_occupied = ($row['homestay_status'] == 1); // Check if status is "Occupied"
                ?>
                    <div class="property-list-item">
                        <div class="property-image-wrapper">
                            <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($row['title']) ?>">
                        </div>
                        <div class="property-details">
                            <h3><?= htmlspecialchars($row['title']) ?> (ID: <?= htmlspecialchars($row['homestay_id']) ?>)</h3>
                            <p><strong>Host ID:</strong> <?= htmlspecialchars($row['host_id']) ?></p>
                            <p><strong>Location:</strong> <?= htmlspecialchars($row['district']) ?>, <?= htmlspecialchars($row['state']) ?></p>
                            <p><strong>Price/Night:</strong> RM<?= htmlspecialchars(number_format($row['price_per_night'], 2)) ?></p>
                            <p><strong>Max Guests:</strong> <?= htmlspecialchars($row['max_guests']) ?></p>
                            <span class="status-badge <?= $statusColor ?>"><?= $statusText ?></span>
                        </div>
                        <div class="property-actions">
                            <form method="POST" onsubmit="return confirmDelete(this, <?= json_encode($is_occupied) ?>);">
                                <input type="hidden" name="homestay_id" value="<?= htmlspecialchars($row['homestay_id']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="delete-btn" <?= $is_occupied ? 'disabled' : '' ?>>🗑️ Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-properties-message">No properties found for the current selection or search criteria.</p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // JavaScript function to handle search button click
        function searchProperties() {
            const searchId = document.getElementById('searchIdInput').value;
            window.location.href = `ViewPropertyInfo.php?search_id=${searchId}`;
        }

        // JavaScript function to handle Enter key press in search input
        function handleSearchKeyPress(event) {
            if (event.key === 'Enter') {
                searchProperties();
            }
        }

        // JavaScript function for delete confirmation
        function confirmDelete(form, isOccupied) {
            const homestayId = form.querySelector('input[name="homestay_id"]').value;
            if (isOccupied) {
                // Using a custom message box instead of alert/confirm for better UX
                showMessageBox(`Cannot delete homestay with ID: ${homestayId}. It has active or future bookings.`, 'error');
                return false; // Prevent form submission
            }
            return confirm(`Are you sure you want to delete homestay with ID: ${homestayId}? This action cannot be undone.`);
        }

        // Function to show a custom message box
        function showMessageBox(message, type) {
            let messageBox = document.createElement('div');
            messageBox.className = `message-box ${type}`;
            messageBox.textContent = message;

            let mainContent = document.querySelector('.content');
            mainContent.insertBefore(messageBox, mainContent.firstChild);

            // Automatically hide the message after a few seconds
            setTimeout(() => {
                messageBox.remove();
            }, 5000); // Message disappears after 5 seconds
        }
    </script>
</body>
</html>