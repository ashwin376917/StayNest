<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - Guest</title>
  <link rel="stylesheet" href="../../CSS/Guest/GuestDashboard.css" />
  <link rel="stylesheet" href="../../CSS/Guest/GuestHeader.css?v=4">
</head>
<body>

  <!-- HEADER -->
  <?php include('../../HTML/Guest/GuestHeader.php'); ?>

  <!-- DASHBOARD CONTENT -->
  <div class="main-container">

    <!-- LAYER 1: Summary Boxes -->
    <div class="summary-row">
      <div class="summary-box">
        <div class="summary-number">--</div>
        <div class="summary-content">
          <img src="../../assets/Guest/order_icon.png" alt="Orders" />
          <span>Total Orders</span>
        </div>
      </div>
      <div class="summary-box">
        <div class="summary-number">--</div>
        <div class="summary-content">
          <img src="../../assets/Guest/review_icon.png" alt="Reviews" />
          <span>Total Reviews</span>
        </div>
      </div>
      <div class="summary-box">
        <div class="summary-number">--</div>
        <div class="summary-content">
          <img src="../../assets/Guest/report_icon.png" alt="Reports" />
          <span>Total Reports</span>
        </div>
      </div>
    </div>

    <!-- LAYER 2: Orders & Reviews -->
    <div class="layer-two">
      <!-- Recent Orders -->
      <div class="recent-orders">
        <h2>Recent Orders</h2>
        <table>
          <thead>
            <tr>
              <th>No</th>
              <th>Property Name</th>
              <th>Date Booked</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <!-- Placeholder rows -->
            <tr>
              <td>1</td>
              <td>Rumah Hijau</td>
              <td>2025-06-01</td>
              <td>Confirmed</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Recent Reviews -->
      <div class="recent-reviews">
        <h2>Recent Reviews</h2>
        <div class="review-box">
          <!-- Placeholder review -->
          <p>User123: Great place, would book again!</p>
        </div>
      </div>
    </div>

    <!-- LAYER 3: Reports -->
    <div class="reports-section">
      <h2>User Reports</h2>
      <div class="report-box">
        <!-- Placeholder report -->
        <p>Report #1: Property not as described.</p>
      </div>
    </div>

  </div>

  <footer>
    <!-- Footer container -->
  </footer>
  <script src="../../JS/Guest/SearchHandler.js?v=1"></script>

</body>
</html>
