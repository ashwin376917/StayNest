<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../connect.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../../HTML/Home/login.php");
    exit();
}

// Default profile picture
$profileImage = '../../assets/default_profile.png';

if (isset($_SESSION['guest_id'])) {
    $guestId = $_SESSION['guest_id'];

    $stmt = $conn->prepare("SELECT guest_profile_picture FROM guest WHERE guest_id = ?");
    $stmt->bind_param("s", $guestId);
    $stmt->execute();
    $stmt->bind_result($dbPicture);
    $stmt->fetch();
    $stmt->close();

    if ($dbPicture && file_exists('../../' . $dbPicture)) {
        $profileImage = '../../' . $dbPicture;
    }
}
?>

<header class="main-header">
    <div class="left">
        <a href="../../HTML/Guest/AfterLoginHomepage.php" class="brand-link">
            <img src="../../assets/staynest_logo.png" alt="StayNest Logo" class="logo" />
            <span class="WebName">StayNest</span>
        </a>
        <a href="../../HTML/Guest/GuestDashboard.php" class="nav-link">Dashboard</a>
        <a href="../../HTML/Guest/BookingManagement.php" class="nav-link">My Booking</a>
    </div>

    <div class="search-container">
        <input
            type="text"
            id="searchInput"
            placeholder="Find your stay..."
            class="search-bar"
            onkeypress="handleKeyPress(event)"
        />
        <button class="search-btn" onclick="triggerSearch()">
            <img src="../../assets/search.png" alt="Search" />
        </button>
    </div>

    <div class="right">
        <a href="../../HTML/Host/ViewNest.php" class="be-a-host">+ Be a Host</a>
        <a href="../../HTML/Home/Profile.php" class="profile-wrapper">
            <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile" class="profile-icon" />
        </a>
    </div>
</header>

<script>
// These functions are now in GuestHeader.php to be available across pages
function handleKeyPress(event) {
    if (event.key === 'Enter') {
        triggerSearch();
    }
}

function triggerSearch() {
    const input = document.getElementById('searchInput');
    const query = input ? input.value.trim() : '';

    let targetUrl = '../../HTML/Guest/SearchResult.php';
    let params = new URLSearchParams();

    // Only add 'query' parameter if the input is not empty
    if (query) {
        params.append('query', query);
    }

    // Append parameters if any exist
    if (params.toString()) {
        targetUrl += '?' + params.toString();
    }

    window.location.href = targetUrl;
}

// Attach click listener for the search button if the script loads after the DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const searchButton = document.querySelector('.search-btn'); // Assuming this is your search button
    if (searchButton) {
        searchButton.addEventListener('click', triggerSearch);
    }
});
</script>