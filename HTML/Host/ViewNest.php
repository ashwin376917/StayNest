<?php
session_start();

include("../../connect.php"); // Ensure this path is correct and it connects properly

// Redirect if not logged in as a guest (who might become a host)
if (!isset($_SESSION['guest_id'])) {
    header("Location: ../Guest/login.php");
    exit();
}

$loggedInGuestId = $_SESSION['guest_id']; // Get the logged-in guest ID

$hostApprovalStatus = 0; // Default to 0 (not a host or deactivated)
$hostId = null; // Initialize hostId

$stmt = $conn->prepare("SELECT host_id, isApprove FROM host WHERE guest_id = ?");
if ($stmt === false) {
    error_log("Prepare statement failed (host approval check): " . $conn->error);
    echo "<script>alert('Database error during host status check.'); window.location.href = '../../HTML/Guest/AfterLoginHomepage.php';</script>";
    exit();
}
$stmt->bind_param("s", $loggedInGuestId);
$stmt->execute();
$stmt->bind_result($hostId, $hostApprovalStatus);
$stmt->fetch();
$stmt->close();

if (is_null($hostId)) {
    // Guest is logged in, but not yet a host.
    // They will be prompted to become a host via the "Request to be a Host" button.
    error_log("No host record found for guest_id: " . $loggedInGuestId . ". User is a guest.");
    // Insert a new host record with isApprove = 0 if it doesn't exist
    $insertStmt = $conn->prepare("INSERT INTO host (guest_id, isApprove) VALUES (?, 0)");
    if ($insertStmt === false) {
        error_log("Prepare statement failed (insert new host): " . $conn->error);
    } else {
        $insertStmt->bind_param("s", $loggedInGuestId);
        $insertStmt->execute();
        $insertStmt->close();
        // After insertion, hostId is still null here, but next page load will pick it up
    }
} else {
    // Host ID found, set it in session for consistency in property creation/management
    $_SESSION['host_id'] = $hostId;
    $_SESSION['user_role'] = 'host'; // Update role if it was 'guest'
    error_log("Host ID found: " . $hostId . ", Approval Status: " . $hostApprovalStatus);
}

// Handle actions (Request Host, Cancel Request, Delete Homestay)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'request_host') {
            // Update isApprove to 1 (pending) when requesting to be a host
            $stmt = $conn->prepare("UPDATE host SET isApprove = 1 WHERE guest_id = ?");
            if ($stmt === false) {
                error_log("Prepare statement failed (request host): " . $conn->error);
                echo "<script>alert('Database error during host request.');</script>";
            } else {
                $stmt->bind_param("s", $loggedInGuestId);
                $stmt->execute();
                $stmt->close();
                // Re-fetch status to update display immediately
                $hostApprovalStatus = 1; // Set to pending
            }
        } elseif ($_POST['action'] === 'cancel_request') {
            // Update isApprove to 0 (deactivated) when canceling request
            $stmt = $conn->prepare("UPDATE host SET isApprove = 0 WHERE guest_id = ?");
            if ($stmt === false) {
                error_log("Prepare statement failed (cancel request): " . $conn->error);
                echo "<script>alert('Database error during cancel request.');</script>";
            } else {
                $stmt->bind_param("s", $loggedInGuestId);
                $stmt->execute();
                $stmt->close();
                // Re-fetch status to update display immediately
                $hostApprovalStatus = 0; // Set to deactivated
            }
        }
        elseif ($_POST['action'] === 'delete' && isset($_POST['delete_ids'])) {
            // Ensure host is approved (status 2) before allowing delete
            if ($hostId && $hostApprovalStatus == 2) {
              
                $deleteIds = $_POST['delete_ids'];
                
                $quotedIds = array_map(function($id) use ($conn) {
                    return "'" . $conn->real_escape_string($id) . "'";
                }, $deleteIds);
                $idList = implode(",", $quotedIds);

                $sql = "DELETE FROM homestay WHERE homestay_id IN ($idList) 
                                AND host_id = ? 
                                AND homestay_status = 0";
                // Removed `AND isBan = 0`
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    error_log("Prepare statement failed (delete homestay): " . $conn->error);
                    echo "<script>alert('Error preparing delete statement.');</script>";
                } else {
                    $stmt->bind_param("s", $hostId); // 's' for string host_id
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            echo "<script>alert('Selected homestays deleted successfully!');</script>";
                        } else {
                            echo "<script>alert('No eligible homestays were deleted (might be occupied, or you don\\'t own them).');</script>";
                        }
                    } else {
                        echo "<script>alert('Error deleting homestays: " . $stmt->error . "');</script>";
                    }
                    $stmt->close();
                }
            } else {
                echo "<script>alert('You are not authorized to delete properties or your account is not approved.');</script>";
            }
        }
    }

    // Always redirect after POST to prevent re-submission and clear form data
    header("Location: ViewNest.php");
    exit();
}

// --- Fetch Homestays with Search and Filter ---
$homestays = []; // Initialize an empty array for results

if ($hostId && $hostApprovalStatus == 2) {
    $sql = "SELECT homestay_id, title, description, district, state, price_per_night, 
                    amenities, categories, homestay_status, picture1, total_click, max_guests 
            FROM homestay 
            WHERE host_id = ?";
    $params = [$hostId];
    $types = "s"; // For host_id

    // Search by name
    $search_name = $_GET['search_name'] ?? '';
    if (!empty($search_name)) {
        $sql .= " AND title LIKE ?";
        $params[] = '%' . $search_name . '%';
        $types .= "s";
    }

    // Filter by homestay_status
    $filter_status = $_GET['filter_status'] ?? '';
    // Changed comparison to integer values (0 and 1)
    if ($filter_status !== '' && ($filter_status == '0' || $filter_status == '1')) { 
        $sql .= " AND homestay_status = ?";
        $params[] = (int)$filter_status; // Cast to int for binding
        $types .= "i"; // 'i' for integer binding
    }
    

    $sql .= " ORDER BY homestay_id DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare statement failed (fetch homestays): " . $conn->error);
        echo "<script>alert('Database error fetching homestays.');</script>";
    } else {
        // Dynamically bind parameters
        $bind_names = [$types];
        for ($i = 0; $i < count($params); $i++) {
            $bind_names[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);

        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $homestays[] = $row;
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Nest - StayNest Host</title>
    <link rel="stylesheet" href="../../include/css/footer.css">
    <link rel="stylesheet" href="css/hostheadersheet.css"/> 
    
    <style>
        body {
            font-family: 'NType', sans-serif;
            margin: 0;
            background-color: #f8f8f8; /* Light background */
        }

        h2 {
            font-weight: normal;
            font-size: large;
        }

        /* Rounded checkbox */
        input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            border-radius: 25%;
            border: 1px solid #ccc;
            background-color: #fff;
            cursor: pointer;
            position: relative;
            transition: border-color 0.2s ease, background-color 0.2s ease;
        }

        input[type="checkbox"]::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 8px;
            height: 8px;
            background-color: black;
            border-radius: 25%;
            transform: translate(-50%, -50%) scale(0);
            transition: transform 0.2s ease;
        }

        input[type="checkbox"]:checked::before {
            transform: translate(-50%, -50%) scale(1);
        }

        input[type="checkbox"]:disabled {
            cursor: not-allowed;
            background-color: #e0e0e0;
            border-color: #bbb;
        }
        input[type="checkbox"]:disabled::before {
            background-color: #a0a0a0;
        }


        /* Tabs */
        .host-nav-wrapper {
            width: 100%;
            margin-top: 30px;
            margin-bottom: 65px;
        }

        .host-nav {
            display: flex;
            gap: 25px;
            width: 80%;
            max-width: 1200px; /* Added max-width */
            margin: 0 auto;
        }

        .host-nav-link {
            font-family: 'NType', sans-serif;
            font-size: 15px;
            font-weight: 400;
            text-decoration: none;
            color: black;
            position: relative;
            padding-bottom: 5px;
        }

        .host-nav-link.active {
            font-weight: 600;
        }

        .host-nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            height: 2px;
            width: 100%;
            background-color: black;
        }

        /* Wrapper */
        .container {
            display: flex;
            justify-content: center; /* Center the content */
            width: 100%;
        }

        .left {
            flex-grow: 1;
            max-width: 1200px; /* Max width for content */
        }

        .host-content {
            width: 90%;
            /* Adjusted width to provide some padding */
            max-width: 900px;
            /* A reasonable max-width for the content itself */
            margin: 0 auto;
            /* Center the content */
            padding: 20px 0;
            /* Add some vertical padding */
        }

        .dashboard-title {
            margin: 20px 0;
            font-size: 35px;
        }

        /* Add Property / Action Buttons */
        .select-add {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 40px;
            margin-bottom: 25px;
        }

        .select-all-label {
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .add-property-btn {
            display: block;
            background-color: #000;
            color: white;
            padding: 15px 0;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-family: 'NType', sans-serif;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .add-property-btn:hover {
            background-color: #222;
        }

        .full-width {
            width: 100%;
            text-align: center;
        }

        /* Property Cards */
        .property-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .property-card {
            background-color: #fff;
            /* White background for cards */
            display: flex;
            flex-direction: column;
            padding: 15px;
            border-radius: 10px;
            gap: 10px;
            position: relative;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            /* More prominent shadow */
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .property-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.12);
        }

        /* Make card not clickable if disabled */
        .property-card.disabled-card {
            cursor: not-allowed;
            opacity: 0.7;
        }
        .property-card.disabled-card:hover {
            transform: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }


        .clickable-card {
            /* No default cursor here, handled by JS based on context */
            user-select: none;
        }

        .property-thumb {
            width: 100%;
            height: 180px;
            border-radius: 8px;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .property-card .main-info {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            width: 100%;
        }
        .property-card .property-info {
            flex-grow: 1;
            text-align: left;
        }
        .property-info h3 {
            font-size: 18px;
            margin: 0 0 5px 0;
            color: #333;
        }
        .property-info p {
            font-size: 14px;
            color: #666;
            margin: 0 0 4px 0;
        }
        .property-info .details-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
            font-size: 13px;
            color: #444;
        }
        .property-info .details-row span {
            font-weight: bold;
        }
        .property-info .details-row span.max-guests {
            font-size: 14px;
            color: #777;
            font-weight: normal;
        }

        /* Status Chips */
        .status {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
            z-index: 5;
            /* Ensure it's above other elements if positioned over image */
        }
        .status.available { background-color: #28a745;
        } /* Green for available */
        .status.booked { background-color: #dc3545;
        } /* Red for booked/occupied */
        .status.pending { background-color: #ffc107; color: #343a40;
        } /* Yellow for pending */
        /* Removed .status.banned as isBan is no longer used */


        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .delete-selected-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 20px; /* Smaller padding */
            font-size: 14px;
            font-family: 'NType', sans-serif;
            cursor: pointer;
            display: none; /* Controlled by JS initially */
            align-self: flex-end;
            transition: background-color 0.3s ease;
        }
        .delete-selected-btn:hover {
            background-color: #c0392b;
        }


        /* Host Action Buttons (Request/Add Property) */
        .host-action-btn {
            background-color: #007bff;
            /* Primary blue for Add Property / Request */
            color: white;
            border: none;
            border-radius: 6px;
            padding: 15px 25px;
            font-size: 16px; /* Larger font size */
            font-family: 'NType', sans-serif;
            cursor: pointer;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: background-color 0.3s ease;
            margin-top: 20px;
            /* Space above */
        }
        /* New style for "Cancel Request" button */
        .host-action-btn.cancel-request {
            background-color: #ffc107;
            /* Yellow */
            color: #343a40;
            /* Dark text for contrast */
        }
        .host-action-btn.cancel-request:hover {
            background-color: #e0a800;
            /* Darker yellow on hover */
        }
        .host-action-btn:hover {
            opacity: 0.9;
            background-color: #0056b3; /* Darker blue on hover */
        }
        .full-width {
            width: 100%;
            box-sizing: border-box;
        }

        /* New: Search and Filter Section */
        .filter-section {
            display: flex;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
            gap: 10px;
            /* Smaller gap */
            margin-bottom: 25px;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .filter-section input[type="text"],
        .filter-section select {
            padding: 8px;
            /* Slightly smaller padding */
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'NType', sans-serif;
            font-size: 13px; /* Slightly smaller font */
            flex: 1;
            /* Allow inputs to grow */
            min-width: 150px;
            /* Minimum width for inputs */
        }
        .filter-section button {
            padding: 8px 15px;
            /* Smaller padding */
            background-color: #000;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'NType', sans-serif;
            font-size: 13px;
            /* Smaller font */
            transition: background-color 0.3s ease;
        }
        .filter-section button:hover {
            background-color: #333;
        }
        .filter-section .reset-button {
            background-color: #95a5a6;
            /* Grey for reset */
        }
        .filter-section .reset-button:hover {
            background-color: #7f8c8d;
        }

        /* Amenities icons */
        .amenities-display {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            justify-content: center;
        }
        .amenities-display .amenity-icon {
            width: 24px;
            height: 24px;
            object-fit: contain;
            filter: grayscale(0%);
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }
        .amenities-display .amenity-icon:hover {
            opacity: 1;
        }

        /* Edit/Delete Buttons for each card */
        .edit-delete-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            justify-content: flex-end; /* Align buttons to the right */
            width: 100%;
            border-top: 1px solid #eee; /* Separator line */
            padding-top: 10px;
        }
        .edit-delete-buttons a,
        .edit-delete-buttons button {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            border: none;
            transition: background-color 0.2s ease;
        }
        .edit-delete-buttons .edit-btn {
            background-color: #007bff;
        }
        .edit-delete-buttons .edit-btn:hover {
            background-color: #0056b3;
        }
        .edit-delete-buttons .delete-btn {
            background-color: #dc3545;
        }
        .edit-delete-buttons .delete-btn:hover {
            background-color: #c82333;
        }
        .edit-delete-buttons .disabled-btn {
            background-color: #b0b0b0;
            /* Lighter grey for disabled buttons */
            cursor: not-allowed;
            opacity: 0.6;
        }
        .edit-delete-buttons .disabled-btn:hover {
            background-color: #b0b0b0;
            /* No hover effect for disabled */
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .host-nav, .host-content {
                width: 95%;
                /* More width on smaller screens */
            }
            .filter-section {
                flex-direction: column;
                /* Stack filters vertically */
                align-items: stretch;
                /* Stretch to fill width */
            }
            .filter-section input[type="text"],
            .filter-section select,
            .filter-section button {
                width: 100%;
                min-width: unset;
            }
            .property-card {
                flex-direction: column;
                /* Stack details vertically */
                align-items: flex-start;
                gap: 15px;
            }
            .property-card .main-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .property-info .details-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            .property-info .details-row span {
                width: 100%;
                text-align: left;
            }
            .edit-delete-buttons {
                flex-direction: column;
                align-items: stretch;
            }
            .edit-delete-buttons a,
            .edit-delete-buttons button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'hostheader.html';
?>

<div class="container">
    <div class="left">
        <div class="host-content">

            <form method="GET" class="filter-section">
                <input type="text" name="search_name" placeholder="Search by name" value="<?= htmlspecialchars($_GET['search_name'] ?? '') ?>" />
                <select name="filter_status">
                    <option value="">All Status</option>
     
                    <option value="0" <?= (isset($_GET['filter_status']) && $_GET['filter_status'] === '0') ?
'selected' : '' ?>>Not Occupied</option>
                    <option value="1" <?= (isset($_GET['filter_status']) && $_GET['filter_status'] === '1') ?
'selected' : '' ?>>Occupied</option>
                </select>
                <button type="submit">Apply Filters</button>
                <a href="ViewNest.php" style="text-decoration: none;"><button type="button" class="reset-button">Reset</button></a>
            </form>

            <form method="POST">
                <div 
class="select-add">
                    <div class="select-bar">
                        <label class="select-all-label">
                            <div class="checkbox-container">
                         
                                <input type="checkbox" id="select-all" />
                                <span>Select All</span>
                            </div>
                        </label>
     
                        <button type="submit" id="deleteBtn" name="action" value="delete" class="delete-selected-btn">Delete Selected</button>
                    </div>

                    <?php if ($hostApprovalStatus == 0): // Guest not a host or deactivated ?>
                       
                        <button type="submit" name="action" value="request_host" class="host-action-btn full-width">Become a Host Now!</button>
                    <?php elseif ($hostApprovalStatus == 1): // Pending approval ?>
                        <button type="submit" name="action" value="cancel_request" class="host-action-btn full-width cancel-request">Cancel Request</button>
                    <?php elseif ($hostApprovalStatus == 2): // Approved host ?>
    
                        <a href="AddProperty.php" class="host-action-btn full-width" style="text-decoration: none;">+ Add Property</a>
                    <?php endif;
?>
                </div>

                <div class="property-list">
                    <?php
                    // Only display property list if host is approved (status == 2)
                
                    if ($hostId && $hostApprovalStatus == 2) {
                        if (!empty($homestays)) {
                            foreach ($homestays as $row) {
                                
                                $homestay_id = htmlspecialchars($row['homestay_id']);
                                $title = htmlspecialchars($row['title']);
                                $district = htmlspecialchars($row['district']);
                                $state = htmlspecialchars($row['state']);
                                $categories = htmlspecialchars($row['categories']);
                                $max_guests = htmlspecialchars($row['max_guests']);
                                $price_per_night = htmlspecialchars(number_format($row['price_per_night'], 2));
                                $amenities_str = htmlspecialchars($row['amenities']);
                                $homestay_status = (int)$row['homestay_status']; // Cast to int

                            
                                $statusLabel = 'Unknown';
                                $statusClass = 'status';
                                $canEditDelete = false; 

                            
                                if ($homestay_status === 0) { // Check for int 0
        
                                    $statusLabel = 'Not Occupied';
                                    $statusClass .= ' available';
                                    $canEditDelete = true; // Can edit/delete if not occupied
                                } elseif ($homestay_status === 1) { // Check for int 1
                                    $statusClass .= ' booked';
                                    
                                    $local_conn = mysqli_connect("localhost", "root", "", "staynest"); // Re-open connection or use the existing $conn
                                    if (!$local_conn) {
                                        error_log("Failed to connect to MySQL: " . mysqli_connect_error());
                                        $statusLabel = 'Occupied (DB Error)';
                                    } else {
                                        $booking_query = $local_conn->prepare(
                                            "SELECT check_in_date, check_out_date FROM booking
                                             WHERE homestay_id = ? AND check_out_date >= CURDATE()
                                             ORDER BY check_in_date ASC LIMIT 1"
                                        );
                                        if ($booking_query) {
                                            $booking_query->bind_param("s", $row['homestay_id']);
                                            $booking_query->execute();
                                            $booking_result = $booking_query->get_result();
                                            if ($booking_row = $booking_result->fetch_assoc()) {
                                                $check_in = htmlspecialchars($booking_row['check_in_date']);
                                                $check_out = htmlspecialchars($booking_row['check_out_date']);
                                                $statusLabel = "Occupied: {$check_in} to {$check_out}";
                                            } else {
                                                $statusLabel = 'Occupied (No Dates Found)'; // Fallback if no active booking found despite status 1
                                            }
                                            $booking_query->close();
                                        } else {
                                            error_log("Failed to prepare booking query: " . $local_conn->error);
                                            $statusLabel = 'Occupied (Query Error)';
                                        }
                                        
                                    }
                                }
                               

                                $thumb = !empty($row['picture1']) ?
$row['picture1'] : "../../assets/placeholder.png"; // Use a placeholder if no image
                    ?>
                        <div class="property-card"> <input type="checkbox" name="delete_ids[]" value="<?= $homestay_id ?>" class="card-checkbox" <?= $canEditDelete ?
'' : 'disabled' ?> />
                            <span class="status <?= $statusClass ?>"><?= $statusLabel ?></span>
                            
                            <img src="<?= $thumb ?>" alt="property" class="property-thumb" 
/>
                            <div class="main-info">
                                <div class="property-info">
                                    <h3><?= $title 
?></h3>
                                    <p><?= $district ?>, <?= $state ?></p>
                                    <p>Category: <?= $categories ?></p>
                    
                                    <div class="details-row">
                                        <span class="max-guests">Max Guests: <?= $max_guests ?></span>
                                      
                                        <span>RM <?= $price_per_night ?> / night</span>
                                    </div>
                                </div>
                         
                            </div>
                            <div class="amenities-display">
                                <?php
                                $amenity_icons_map = [
  
                                    "Wifi" => "Wifi.png",
                                    "Parking" => "Parking.png",
                          
                                    "Kitchen" => "Kitchen.png",
                                    "Pool" => "Pool.png",
                                    "Smart TV" => "SmartTV.png",
           
                                    "Personal Workspace" => "PersonalWorkspace.png",
                                    "Washer" => "Washer.png",
                                  
                                    "Hair Dryer" => "HairDryer.png",
                                    "Dryer" => "Dryer.png",
                                    "Aircond" => "Aircond.png"
                   
                                ];
                                $amenities_array = array_map('trim', explode(',', $amenities_str));
                                foreach ($amenities_array as $amenity) {
                                    $amenity_key = str_replace(' ', '', $amenity);
                                    // Ensure the key exists in the map
                                    if (array_key_exists($amenity_key, $amenity_icons_map)) {
                                        $icon_file = $amenity_icons_map[$amenity_key];
                                        echo '<img src="../../assets/Property/' . htmlspecialchars(strtolower($icon_file)) . '" alt="' . htmlspecialchars($amenity) . '" title="' . htmlspecialchars($amenity) . '" class="amenity-icon" />';
                                    }
                                }
                                ?>
                            </div>
        
                            <div class="edit-delete-buttons">
                                <button type="submit" name="action" value="delete_single" data-homestay-id="<?= $homestay_id ?>" class="delete-btn single-delete-btn <?= $canEditDelete ? '' : 'disabled-btn' ?>" <?= $canEditDelete ?
'' : 'disabled' ?>>Delete</button>
                            </div>
                        </div>
                    <?php
                         
                            }
                        } else {
                            echo '<p style="text-align: center; margin-top: 20px;">No properties found for this host matching your criteria.
Click "+ Add Property" to get started!</p>';
                        }
                    } else {
                        // Display message based on approval status
                        $message = "";
                        if ($hostApprovalStatus == 0) {
                            $message = "You are not yet a host. Please use the 'Become a Host Now!'
button to send your request.";
                        } elseif ($hostApprovalStatus == 1) {
                            $message = "Your host request is pending approval. You will be able to add properties once approved.";
                        }
                        echo '<p style="text-align: center; margin-top: 50px; color: #555;">' .
$message . '</p>';
                    }
                    ?>
                </div>
            </form>

        </div>
    </div>
    <div class="right"></div>
</div>

<script>
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.card-checkbox');
    const deleteBtn = document.getElementById('deleteBtn');
    const singleDeleteButtons = document.querySelectorAll('.single-delete-btn');
    const hostApprovalStatus = <?php echo json_encode($hostApprovalStatus); ?>;
    // Pass PHP variable to JS

    // Initial state check for controls based on hostApprovalStatus
    if (hostApprovalStatus !== 2) { // If not approved, disable/hide controls
        if (selectAll) selectAll.disabled = true;
        if (deleteBtn) deleteBtn.style.display = 'none'; // Hide mass delete button
        checkboxes.forEach(cb => cb.disabled = true);
        // Disable all checkboxes
    }

    // Event listener for "Select All" checkbox
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => {
                // Only toggle checkboxes that are not disabled (i.e., deletable)
                if (!cb.disabled) {
         
                    cb.checked = this.checked;
                }
            });
            toggleDeleteButton();
        });
    }
    
    // Event listener for individual checkboxes
    checkboxes.forEach(cb => {
        cb.addEventListener('change', toggleDeleteButton);
    });
    // Function to show/hide the mass delete button
    function toggleDeleteButton() {
        if (hostApprovalStatus !== 2) {
            if (deleteBtn) deleteBtn.style.display = 'none';
            return;
        }
        const anyChecked = Array.from(checkboxes).some(cb => cb.checked && !cb.disabled);
        if (deleteBtn) deleteBtn.style.display = anyChecked ? 'inline-block' : 'none';
    }

    // Handle single delete button clicks
    singleDeleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default form submission or link action
            // The logic for disabling delete button is now only tied to homestay_status, not isBan
            if (this.disabled) {
      
                showCustomAlert('This property cannot be deleted at this time (it might be occupied).'); // Updated message
                return;
            }

            // Using custom modal instead of confirm()
            showConfirmModal('Are you sure you want to delete this property? This action cannot be undone.', () => {
  
                const homestayIdToDelete = this.dataset.homestayId;
                const form = this.closest('form'); // Get the parent form

                // Create a hidden input for the specific ID to be deleted
                const hiddenInput = document.createElement('input');
             
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'delete_ids[]'; // Use the same name as mass delete
                hiddenInput.value = homestayIdToDelete;
                form.appendChild(hiddenInput);
                // Set the action for single delete
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete'; // Use the same action as mass delete
                form.appendChild(actionInput);
                form.submit(); // Submit the form
            });
        });
    });
    // Call toggleDeleteButton on page load to set initial state
    toggleDeleteButton();
    // Custom Modal for Alerts and Confirms (replaces alert() and confirm())
    function showCustomAlert(message) {
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5); display: flex; justify-content: center;
            align-items: center; z-index: 1000;
        `;
        modal.innerHTML = `
            <div style="background-color: white; padding: 20px; border-radius: 8px;
                        max-width: 400px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                <p>${message}</p>
                <button id="custom-alert-ok" style="background-color: #007bff; color: white;
           
                                        padding: 10px 20px; border: none; border-radius: 5px;
                                                 cursor: pointer; margin-top: 15px;">OK</button>
    
            </div>
        `;
        document.body.appendChild(modal);
        document.getElementById('custom-alert-ok').onclick = () => modal.remove();
    }

    function showConfirmModal(message, onConfirm) {
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5); display: flex; justify-content: center;
            align-items: center; z-index: 1000;
        `;
        modal.innerHTML = `
            <div style="background-color: white; padding: 20px; border-radius: 8px;
                        max-width: 400px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                <p>${message}</p>
                <button id="custom-confirm-yes" style="background-color: #28a745; color: white;
           
                                        padding: 10px 20px; border: none; border-radius: 5px;
                                                  cursor: pointer; margin-top: 15px; margin-right: 
10px;">Yes</button>
                <button id="custom-confirm-no" style="background-color: #dc3545; color: white;
                                                 padding: 10px 20px; border: none; border-radius: 5px;
                        
                                                 cursor: pointer; margin-top: 15px;">No</button>
            </div>
        `;
        document.body.appendChild(modal);

        document.getElementById('custom-confirm-yes').onclick = () => {
            modal.remove();
            onConfirm();
        };
        document.getElementById('custom-confirm-no').onclick = () => modal.remove();
    }

    // Override default alert and confirm for this page
    window.alert = showCustomAlert;
    window.confirm = showCustomModal; // This won't directly replace `confirm` but the usage in the code is adjusted
</script>

<?php include "../../include/Footer.html"; ?>

</body>
</html>