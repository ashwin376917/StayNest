<?php
session_start();

// Handle logout if logout button is clicked
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../../HTML/Home/index.php");
    exit();
}

// Check if the guest is logged in
if (!isset($_SESSION['guest_id'])) {
    header("Location: ../../HTML/Home/index.php");
    exit();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Homepage-Guest</title>
   <link rel="stylesheet" href="../../CSS/Guest/AfterLoginHomepage.css?v=4">
   <link rel="stylesheet" href="../../CSS/Guest/GuestHeader.css?v=4">
   
</head>
<body>
    <header>
   <!-- HEADER -->
   <?php include('../../HTML/Guest/GuestHeader.php'); ?>
   </header>

   <!-- NAVIGATION -->
   <nav class="sub-nav">
      <a href="#" class="tab-link active">Recommended For You</a>
      <a href="#certified-nest" class="tab-link">Best Near You</a>
   </nav>

   <!-- MAIN CONTENT -->
   <div class="main-container">

     <!-- RECOMMENDED SECTION -->
    <section class="recommended">
      <div class="banner-container image-wrapper">
        <img id="randomBanner" alt="StayNest Welcome" class="main-banner">

        <div class="overlay"></div>
        <div class="banner-label">
            <p class="small-title">BEST EXPERIENCE</p>
            <h2 class="main-title">StayNest</h2>
            <p class="subtitle">Modern and affordable nest in Melaka</p>
        </div>
      </div>
    </section>

      <!-- MOST SEARCHED -->
      <section class="most-searched">
        <h2>Most searched for ""</h2>
        <div class="photo-searched">
          <div class="photo-box image-wrapper"><div class="overlay"></div></div>
          <div class="photo-box image-wrapper"><div class="overlay"></div></div>
          <div class="photo-box image-wrapper"><div class="overlay"></div></div>
          <div class="photo-box image-wrapper"><div class="overlay"></div></div>
          <div class="photo-box image-wrapper"><div class="overlay"></div></div> <!-- NEW -->
          <div class="photo-box image-wrapper"><div class="overlay"></div></div> <!-- NEW -->
        </div>
      </section>

      <!-- CERTIFIED NEST -->
      <section class="certified-nest" id="certified-nest">
        <h2>Explore Certified Nest Categories</h2>
        <div class="certified-grid">
      
          <div class="fixed-photo image-wrapper">
            <img src="../../assets/Guest/familyfriendly.jpg" alt="Certified Nest">
            <div class="top-label">
               <img src="../../assets/Guest/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
            </div>
            <div class="bottom-label">Family-Friendly</div>
          </div>
      
          <div class="fixed-photo image-wrapper">
            <img src="../../assets/Guest/petfriendly.jpeg" alt="Certified Nest">
            <div class="top-label">
               <img src="../../assets/Guest/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
            </div>
            <div class="bottom-label">Pet-Friendly</div>
          </div>
      
          <div class="fixed-photo image-wrapper">
            <img src="../../assets/Guest/antique.jpg" alt="Certified Nest">
            <div class="top-label">
               <img src="../../assets/Guest/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
            </div>
            <div class="bottom-label">Antique</div>
          </div>
      
          <div class="fixed-photo image-wrapper">
            <img src="../../assets/Guest/shared.avif" alt="Certified Nest">
            <div class="top-label">
               <img src="../../assets/Guest/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
            </div>
            <div class="bottom-label">Shared</div>
          </div>
      
          <div class="fixed-photo image-wrapper">
            <img src="../../assets/Guest/luxury.jpg" alt="Certified Nest">
            <div class="top-label">
               <img src="../../assets/Guest/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
            </div>
            <div class="bottom-label">Luxury</div>
          </div>
      
          <div class="fixed-photo image-wrapper">
            <img src="../../assets/Guest/minimalist.avif" alt="Certified Nest">
            <div class="top-label">
               <img src="../../assets/Guest/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
            </div>
            <div class="bottom-label">Minimalist</div>
          </div>      
        </div>
      </section>
      

   </div> <!-- END main-container -->
 <!-- NOTIFICATION SIDEBAR -->
<div id="notificationSidebar" class="sidebar hidden">
   <div class="sidebar-header">
     <h3>Notifications</h3>
     <button onclick="closeSidebar('notificationSidebar')">✕</button>
   </div>
   <ul id="notificationList">
     <!-- Notifications go here -->
   </ul>
 </div>
 
 <!-- MESSAGES SIDEBAR -->
 <div id="messagesSidebar" class="sidebar hidden">
   <div class="sidebar-header">
     <h3>Messages</h3>
     <button onclick="closeSidebar('messagesSidebar')">✕</button>
   </div>
   <div id="chatList">
     <!-- Messages go here -->
   </div>
 </div>
 
 
 <!-- CHAT WINDOW -->
 <div id="chatWindow" class="chat-window hidden">
   <div class="chat-header">
     <span id="chatUser">Chat with Guest</span>
     <button onclick="closeChat()">X</button>
   </div>
   <div class="chat-body" id="chatMessages"></div>
   <div class="chat-input">
     <input type="text" id="chatInputBox" placeholder="Type a message...">
     <button onclick="sendMessage()">Send</button>
   </div>
 </div>
 
 
   <footer>
    <?php include '../../Header_Footer/Footer.html'; ?>
   </footer>
   <script src="../../JS/Guest/AfterLoginHomepage.js?t=<?= time() ?>"></script>
   <script src="../../JS/Guest/SearchHandler.js?v=1"></script>
  
</body>
</html>
