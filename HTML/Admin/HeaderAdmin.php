<!-- HeaderAdmin.php -->
<nav class="tabs">
  <a href="UserReports.php" class="tab <?= basename($_SERVER['PHP_SELF']) === 'UserReports.php' ? 'active' : '' ?>">User Reports</a>
  <a href="ViewUserList.php" class="tab <?= basename($_SERVER['PHP_SELF']) === 'ViewUserList.php' ? 'active' : '' ?>">User List</a>
  <a href="ViewPropertyInfo.php" class="tab <?= basename($_SERVER['PHP_SELF']) === 'ViewPropertyInfo.php' ? 'active' : '' ?>">Property List</a>
  <a href="AdminDashboard.php" class="tab <?= basename($_SERVER['PHP_SELF']) === 'AdminDashboard.php' ? 'active' : '' ?>">Admin Dashboard</a>
</nav>
