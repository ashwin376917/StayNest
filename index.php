<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <link rel="stylesheet" href="HTML/Guest/css/AfterLoginHomepage.css">
    <link rel="stylesheet" href="include/css/footer.css">
    <style>
        /* INTERNAL CSS FOR HEADER ONLY */

        /* Font Imports - Important: These paths need to be correct relative to the CSS file itself, 
           or use absolute paths if the PHP file's location means relative paths change.
           Given Homepage.php is in HTML/Guest/, and assets are in assets/, 
           you might need to adjust these paths if they cause issues.
           e.g., if assets is at the root, use /assets/NType-Regular.ttf
           For this example, I'll assume assets is two levels up from 'Guest' folder. */
        @font-face {
            font-family: 'NType';
            src: url('../../assets/NType-Regular.ttf') format('opentype');
        }
        @font-face {
            font-family: 'Archivo';
            src: url('../../assets/archivo/Archivo-Medium.ttf') format('truetype');
        }

        /* Global reset for header elements if desired, or apply only to header */
        header.main-header * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'NType', sans-serif; /* Default font for the body */
            background-color: #f7f7f7; /* Light grey background for content areas */
            color: #111;
            line-height: 1.6;
        }
        
        header.main-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 40px; /* Increased padding for better spacing */
            background-color: white;
            font-size: 17px;
            border-bottom: 1px solid #e0e0e0; /* Lighter border */
            position: sticky; /* Make header sticky */
            top: 0;
            z-index: 1000; /* Ensure header stays on top */
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); /* Subtle shadow */
        }

        .left {
            display: flex;
            align-items: center;
            gap: 15px; /* Adjusted gap */
        }

        .left .logo {
            width: 45px; /* Slightly smaller logo */
            height: 45px;
        }

        .WebName {
            font-weight: bold;
            font-family: 'Archivo', sans-serif;
            font-size: 26px; 
            letter-spacing: 0.5px;
            transition: color 0.3s;
        }

        /* Ensure the brand link is clickable and affects text */
        .brand-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }

        .center {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            padding: 0 20px;
        }

        .search-bar {
            width: 100%;
            max-width: 380px; /* Adjusted max-width */
            padding: 10px 20px;
            border: 1px solid #ccc; /* Lighter border */
            border-radius: 30px; /* More rounded */
            outline: none;
            font-size: 16px;
            text-align: center; /* Center placeholder text */
            box-shadow: 0 1px 3px rgba(0,0,0,0.1); /* Subtle shadow for depth */
            transition: all 0.3s ease;
        }

        .search-bar:focus {
            border-color: #FF5A5F; /* Highlight on focus */
            box-shadow: 0 0 0 3px rgba(255, 90, 95, 0.2); /* Soft glow on focus */
        }

        .right {
            display: flex;
            align-items: center;
            gap: 25px; /* Increased gap */
        }

        .SignIn {
            text-decoration: none;
            padding: 8px 18px; /* Adjusted padding */
            border: 1px solid black; /* Border matches branding */
            border-radius: 30px;
            font-weight: 500;
            color: black;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        .SignIn:hover {
            background-color: blue;
            color: white;
            border-color: blue;
        }

        /* Basic responsiveness for header when using internal CSS */
        @media (max-width: 768px) {
            header.main-header {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
                padding: 10px 15px;
            }
            .left, .right {
                width: 100%;
                justify-content: center;
                margin-bottom: 10px;
            }
            .center {
                order: 3;
                width: 100%;
                padding: 0 10px;
            }
            .search-bar {
                max-width: none;
            }
            .SignIn {
                padding: 6px 15px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="left">
            <a href="#" class="brand-link"> 
               <img src="assets/staynest_logo.png" alt="StayNest Logo" class="logo">
               <span class="WebName">StayNest</span>
            </a>
        </div>
        
        <div class="right">
            <a href="HTML/Home/signup.php" class="SignIn">Sign in/Sign Up</a>
        </div>
    </header>

    <nav class="sub-nav">
      <a href="#" class="tab-link active">For You</a>
      <a href="#certified-nest" class="tab-link">Category</a>
    </nav>

    <div class="main-container">

        <section class="recommended">
            <div class="banner-container image-wrapper clickable-image">
                <img src="assets/FrontPage/mainbanner.jpg" alt="The Rumah" class="main-banner">
            
                <div class="overlay"></div>
                <div class="banner-label">
                   <p class="small-title">BEST EXPERIENCE</p>
                   <h2 class="main-title">STAYNEST</h2>
                   <p class="subtitle">Modern and affordable accommodation in Melaka</p>
                </div>
            </div>
        </section>

        <section class="most-searched">
            <h2>Most popular</h2>
            <div class="photo-searched">
               <div class="photo-box image-wrapper clickable-image" style="background-image: url('../../upload/h008_1.jpg');">
                 <div class="overlay"></div>
               </div>
               <div class="photo-box image-wrapper clickable-image" style="background-image: url('../../upload/h001_2.jpg');">
                 <div class="overlay"></div>
               </div>
               <div class="photo-box image-wrapper clickable-image" style="background-image: url('../../upload/h009_3.jpg');">
                 <div class="overlay"></div>
               </div>
               <div class="photo-box image-wrapper clickable-image" style="background-image: url('../../upload/h002_1.jpg');">
                 <div class="overlay"></div>
               </div>
               <div class="photo-box image-wrapper clickable-image" style="background-image: url('../../upload/h002_2.jpg');">
                 <div class="overlay"></div>
               </div>
               <div class="photo-box image-wrapper clickable-image" style="background-image: url('../../upload/h002_3.jpg');">
                 <div class="overlay"></div>
               </div>
            </div>
        </section>

        <section class="certified-nest" id="certified-nest">
            <h2>Explore Certified Nest in Ayer Keroh</h2>
            <div class="certified-grid">
            
              <div class="fixed-photo image-wrapper clickable-image">
                <img src="assets/FrontPage/family.jpg" alt="Certified Nest">
                <div class="top-label">
                   <img src="assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Family Friendly</div>
              </div>
            
              <div class="fixed-photo image-wrapper clickable-image">
                <img src="assets/FrontPage/pet.jpg" alt="Certified Nest">
                <div class="top-label">
                   <img src="assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Pet Friendly</div>
              </div>
            
              <div class="fixed-photo image-wrapper clickable-image">
                <img src="assets/FrontPage/antique.jpg" alt="Certified Nest">
                <div class="top-label">
                   <img src="assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Antique</div>
              </div>
            
              <div class="fixed-photo image-wrapper clickable-image">
                <img src="assets/FrontPage/shared.jpg" alt="Certified Nest">
                <div class="top-label">
                   <img src="assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Shared</div>
              </div>
            
              <div class="fixed-photo image-wrapper clickable-image">
                <img src="assets/FrontPage/luxury.jpg" alt="Certified Nest">
                <div class="top-label">
                   <img src="assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Luxury</div>
              </div>
            
              <div class="fixed-photo image-wrapper clickable-image">
                <img src="assets/FrontPage/minimal.jpg" alt="Certified Nest">
                <div class="top-label">
                   <img src="assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Minimalist</div>
              </div> 
            </div>
        </section>
        
    </div> <footer>
       </footer>
    <script>
document.addEventListener('DOMContentLoaded', () => {
  // Handle image clicks
  const clickableImages = document.querySelectorAll('.clickable-image');
  clickableImages.forEach(image => {
    image.addEventListener('click', () => {
      const confirmResult = confirm("Please sign in or sign up to view this property.\nClick OK to proceed to Sign Up.");
      if (confirmResult) {
        window.location.href = "HTML/Home/signup.php";
      }
    });
  });
});


    </script>

    <?php
      include "include/Footer.html";
    ?>
</body>
</html>