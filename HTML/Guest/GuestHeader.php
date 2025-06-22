<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../../HTML/Home/index.php");
    exit();
}
?>

<!-- HEADER -->
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
    <img src="../../assets/Guest/notification.png" alt="Notification" class="icon" onclick="toggleSidebar('notificationSidebar')" />
    <img src="../../assets/Guest/message.png" alt="Messages" class="icon" onclick="toggleSidebar('messagesSidebar')" />
    <a href="../../HTML/Host/HostDashboard.html" class="be-a-host">+ Be a Host</a>
    <a href="../../HTML/Home/Profile.html" class="profile-wrapper">
      <img src="path/to/profile-image.jpg" alt="Profile" class="profile-icon" />
    </a>
    <a href="?logout=true" class="logout-btn">Logout</a>
  </div>
</header>
