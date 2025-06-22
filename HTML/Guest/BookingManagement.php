<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Booking Management - Guest</title>
  <link rel="stylesheet" href="../../CSS/Guest/BookingManagement.css" />
  <link rel="stylesheet" href="../../CSS/Guest/GuestHeader.css?v=4">
</head>
<body>
  
  
   <!-- HEADER -->
   <?php include('../../HTML/Guest/GuestHeader.php'); ?>


  <!-- Booking Management Section -->
  <div class="main-container">
    <div class="booking-management">
      <h2>My Booking Management</h2>
      <table class="booking-table">
        <thead>
          <tr>
            <th>No</th>
            <th>Property Name</th>
            <th>Booking Date</th>
            <th>Payment Info</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <!-- Example row -->
          <tr>
            <td>1</td>
            <td>Cozy Nest Homestay</td>
            <td>2025-06-05</td>
            <td>Paid - RM250</td>
            <td>Confirmed</td>
            <td>
              <button class="action-btn btn-print">Print Receipt</button>
              <button class="action-btn btn-cancel">Cancel</button>
              <a href="viewpropertylisting.html">
                <button class="action-btn btn-view">View Info</button>
              </a>
            </td>
          </tr>

          <tr>
            <td>2</td>
            <td>Villa Melaka</td>
            <td>2025-06-12</td>
            <td>Pending - RM480</td>
            <td>Pending</td>
            <td>
              <button class="action-btn btn-print">Print Receipt</button>
              <button class="action-btn btn-cancel">Cancel</button>
              <a href="viewpropertylisting.html">
                <button class="action-btn btn-view">View Info</button>
              </a>
            </td>
          </tr>

        </tbody>
      </table>
    </div>
  </div>

  <footer>
    <!-- Footer content -->
  </footer>
  <script src="../../JS/Guest/SearchHandler.js?v=1"></script>

</body>
</html>
