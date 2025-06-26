<?php
include '../../connect.php';
include '../../HTML/Admin/HeaderAdmin.php';

// Count total guests
$totalGuests = $conn->query("SELECT COUNT(*) AS count FROM guest")->fetch_assoc()['count'];

// Count total hosts
$totalHosts = $conn->query("SELECT COUNT(*) AS count FROM host")->fetch_assoc()['count'];

// Count banned users (guest + host)
$bannedGuests = $conn->query("SELECT COUNT(*) AS count FROM guest WHERE banned = 1")->fetch_assoc()['count'];
$bannedHosts = $conn->query("SELECT COUNT(*) AS count FROM host WHERE banned = 1")->fetch_assoc()['count'];
$totalBanned = $bannedGuests + $bannedHosts;

// Fetch activities
$bookings = $conn->query("SELECT g.guest_email AS email, 'Guest' AS role, 'Booked Homestay' AS action, b.booking_date AS date FROM booking b JOIN guest g ON b.guest_id = g.guest_id ORDER BY b.booking_date DESC LIMIT 3");
$hosts = $conn->query("SELECT h.host_email AS email, 'Host' AS role, 'Added Property' AS action, hs.date_created AS date FROM homestay hs JOIN host h ON hs.host_id = h.host_id ORDER BY hs.date_created DESC LIMIT 3");
$reviews = $conn->query("SELECT g.guest_email AS email, 'Guest' AS role, 'Submitted Review' AS action, r.review_date AS date FROM review r JOIN guest g ON r.guest_id = g.guest_id ORDER BY r.review_date DESC LIMIT 3");

// Merge & sort activities
$activities = [];
while ($row = $bookings->fetch_assoc()) $activities[] = $row;
while ($row = $hosts->fetch_assoc()) $activities[] = $row;
while ($row = $reviews->fetch_assoc()) $activities[] = $row;

usort($activities, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../../CSS/Admin/HeaderAdmin.css" />
</head>
<body class="bg-gray-50 font-sans">

  <!-- Header and Tabs are included via HeaderAdmin.php -->

  <main class="p-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
      <div class="w-9 h-9 bg-black text-white flex items-center justify-center rounded-full font-semibold">A</div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-10">
      <div class="bg-white shadow-sm rounded-2xl p-6 border border-gray-200 hover:shadow-lg transition">
        <h2 class="text-gray-600 font-semibold text-lg mb-1">Total Guests</h2>
        <div class="text-4xl font-bold text-blue-600"><?= $totalGuests ?></div>
        <p class="text-sm text-gray-500 mt-2">Registered guest accounts</p>
      </div>
      <div class="bg-white shadow-sm rounded-2xl p-6 border border-gray-200 hover:shadow-lg transition">
        <h2 class="text-gray-600 font-semibold text-lg mb-1">Total Hosts</h2>
        <div class="text-4xl font-bold text-green-600"><?= $totalHosts ?></div>
        <p class="text-sm text-gray-500 mt-2">Registered host accounts</p>
      </div>
      <div class="bg-white shadow-sm rounded-2xl p-6 border border-gray-200 hover:shadow-lg transition">
        <h2 class="text-gray-600 font-semibold text-lg mb-1">Banned Users</h2>
        <div class="text-4xl font-bold text-red-600"><?= $totalBanned ?></div>
        <p class="text-sm text-gray-500 mt-2">Guests or hosts currently banned</p>
      </div>
    </div>

    <section class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
      <h2 class="text-2xl font-semibold text-gray-800 mb-4">Recent User Activities</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-left">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-4 py-2 font-medium text-gray-600">User</th>
              <th class="px-4 py-2 font-medium text-gray-600">Type</th>
              <th class="px-4 py-2 font-medium text-gray-600">Action</th>
              <th class="px-4 py-2 font-medium text-gray-600">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php foreach (array_slice($activities, 0, 6) as $activity): ?>
              <tr>
                <td class="px-4 py-2"><?= htmlspecialchars($activity['email']) ?></td>
                <td class="px-4 py-2"><?= $activity['role'] ?></td>
                <td class="px-4 py-2"><?= $activity['action'] ?></td>
                <td class="px-4 py-2"><?= $activity['date'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
