<?php
session_start();

// Check if the guest is logged in
if (!isset($_SESSION['guest_id'])) {
    header("Location: ../../HTML/Home/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Homepage-Guest</title>
   <link rel="stylesheet" href="..//Home/css/homeheadersheet.css">
   <link rel="stylesheet" href="../../include/css/footer.css">
   <link rel="stylesheet" href="css/AfterLoginHomepage.css">
   
   
</head>
<body>
    <header>
   <!-- HEADER -->
   <?php include('GuestHeader.php'); ?>
   </header>

   <!-- NAVIGATION -->
   <nav class="sub-nav">
      <a href="#" class="tab-link active">For You</a>
      <a href="#certified-nest" class="tab-link">Category</a>
   </nav>

   <!-- MAIN CONTENT -->
   <div class="main-container">

     <!-- RECOMMENDED SECTION -->
    <section class="recommended">
      <div class="banner-container image-wrapper">
        <img id="randomBanner" alt="StayNest Welcome" class="main-banner" src="../../assets/FrontPage/mainbanner.jpg">

        <div class="overlay"></div>
        <div class="banner-label">
            <p class="small-title">BEST EXPERIENCE</p>
            <h2 class="main-title"> STAYNEST</h2>
            <p class="subtitle">Modern and affordable accommodation in Melaka</p>
        </div>
      </div>
    </section>

      <!-- MOST SEARCHED -->
      <section class="most-searched">
        <h2>Most Popular</h2>
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
            <h2>Explore Certified Nest in Ayer Keroh</h2>
            <div class="certified-grid">
            
              <div class="fixed-photo image-wrapper clickable-image">
                <img src="../../assets/FrontPage/family.jpg" alt="Certified Nest">
                <div class="top-label">
                   <img src="../../assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Family Friendly</div>
              </div>
            
              <div class="fixed-photo image-wrapper clickable-image">
                <img src="../../assets/FrontPage/pet.jpg" alt="Certified Nest">
                <div class="top-label">
                   <img src="../../assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Pet Friendly</div>
              </div>
            
              <div class="fixed-photo image-wrapper clickable-image">
                <img src="../../assets/FrontPage/antique.jpg" alt="Certified Nest">
                <div class="top-label">
                   <img src="../../assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Antique</div>
              </div>
            
              <div class="fixed-photo image-wrapper clickable-image">
                <img src="../../assets/FrontPage/shared.jpg" alt="Certified Nest">
                <div class="top-label">
                   <img src="../../assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Shared</div>
              </div>
            
              <div class="fixed-photo image-wrapper clickable-image">
                <img src="../../assets/FrontPage/luxury.jpg" alt="Certified Nest">
                <div class="top-label">
                   <img src="../../assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Luxury</div>
              </div>
            
              <div class="fixed-photo image-wrapper clickable-image">
                <img src="../../assets/FrontPage/minimal.jpg" alt="Certified Nest">
                <div class="top-label">
                   <img src="../../assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Minimalist</div>
              </div> 
            </div>
        </section>
      

   </div> 
 
    <?php
      include "../../include/footer.html";
    ?>

   <footer>
     </footer>
   <script src="../../JS/Guest/AfterLoginHomepage.js?t=<?= time() ?>"></script>
   <script src="../../JS/Guest/SearchHandler.js?v=1"></script>
  
</body>
</html>
