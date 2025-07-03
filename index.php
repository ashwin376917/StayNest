<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StayNest - Homepage</title>
    <link rel="stylesheet" href="HTML/Guest/css/GuestHeader.css">
    <link rel="stylesheet" href="HTML/Guest/css/AfterLoginHomepage.css">
    <link rel="stylesheet" href="include/css/footer.css">
    <style>
      .SignIn {
    text-decoration: none;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: bold;
    color: white;
    background-color: rgb(0, 0, 0);
    border: none;
    border-radius: 25px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    font-family: 'Archivo', sans-serif;
    display: inline-block;
}

.SignIn:hover {
    background-color: rgb(104, 104, 104);
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
}
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 300px;
            text-align: center;
            position: relative;
        }
        .close-modal {
            position: absolute;
            top: 10px;
            right: 15px;
            cursor: pointer;
            font-weight: bold;
        }
        .modal button {
            margin: 10px;
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            background-color: black;
            color: white;
            font-weight: bold;
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
            <img id="randomBanner" alt="StayNest Welcome" class="main-banner" src="assets/FrontPage/mainbanner.jpg">
            <div class="overlay"></div>
            <div class="banner-label">
                <p class="small-title">BEST EXPERIENCE</p>
                <h2 class="main-title">STAYNEST</h2>
                <p class="subtitle">Modern and affordable accommodation in Melaka</p>
            </div>
        </div>
    </section>

    <section class="most-searched">
        <h2>Most Popular</h2>
        <div class="photo-searched">
            <?php
            require_once 'connect.php';
            $sql = "SELECT homestay_id, title, picture1, total_click FROM homestay WHERE homestay_status = 1 ORDER BY total_click DESC LIMIT 10";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $imageUrl = 'HTML/Host/' . htmlspecialchars($row['picture1']);
                    echo '<div class="photo-box image-wrapper clickable-image" style="background-image: url(\'' . $imageUrl . '\');">';
                    echo '<div class="overlay"></div>';
                    echo '<div class="bottom-label">' . htmlspecialchars($row['title']) . '</div>';
                    echo '</div>';
                }
            } else {
                echo "<p>No popular properties found.</p>";
            }
            ?>
        </div>
    </section>

    <section class="certified-nest" id="certified-nest">
        <h2>Explore Certified Nest Categories</h2>
        <div class="certified-grid">
            <div class="fixed-photo image-wrapper clickable-image">
                <img src="assets/FrontPage/family.jpg" alt="Family Friendly">
                <div class="top-label"><img src="assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST</div>
                <div class="bottom-label">Family Friendly</div>
            </div>
            <div class="fixed-photo image-wrapper clickable-image">
                <img src="assets/FrontPage/pet.jpg" alt="Pet Friendly">
                <div class="top-label"><img src="assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST</div>
                <div class="bottom-label">Pet Friendly</div>
            </div>
            <div class="fixed-photo image-wrapper clickable-image">
                <img src="assets/FrontPage/antique.jpg" alt="Antique">
                <div class="top-label"><img src="assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST</div>
                <div class="bottom-label">Antique</div>
            </div>
            <div class="fixed-photo image-wrapper clickable-image">
                <img src="assets/FrontPage/shared.jpg" alt="Shared">
                <div class="top-label"><img src="assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST</div>
                <div class="bottom-label">Shared</div>
            </div>
            <div class="fixed-photo image-wrapper clickable-image">
                <img src="assets/FrontPage/luxury.jpg" alt="Luxury">
                <div class="top-label"><img src="assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST</div>
                <div class="bottom-label">Luxury</div>
            </div>
            <div class="fixed-photo image-wrapper clickable-image">
                <img src="assets/FrontPage/minimal.jpg" alt="Minimalist">
                <div class="top-label"><img src="assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST</div>
                <div class="bottom-label">Minimalist</div>
            </div>
        </div>
    </section>
</div>

<?php include "include/Footer.html"; ?>

<!-- Modal HTML -->
<div class="modal" id="authModal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Sign In or Sign Up</h3>
        <p>Please log in or create an account to view this property.</p>
        <button id="loginBtn">Log In</button>
        <button id="signupBtn">Sign Up</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const clickableElements = document.querySelectorAll('.clickable-image');
    const modal = document.getElementById('authModal');
    const closeModal = document.querySelector('.close-modal');
    const loginBtn = document.getElementById('loginBtn');
    const signupBtn = document.getElementById('signupBtn');

    clickableElements.forEach(element => {
        element.addEventListener('click', () => {
            modal.style.display = 'flex';
        });
    });

    closeModal.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    loginBtn.addEventListener('click', () => {
        window.location.href = "HTML/Home/login.php";
    });

    signupBtn.addEventListener('click', () => {
        window.location.href = "HTML/Home/signup.php";
    });

    const banner = document.getElementById('randomBanner');
    if (banner) {
        const bannerImages = [
            'assets/FrontPage/mainbanner.jpg',
            'assets/FrontPage/mainbanner1.png',
            'assets/FrontPage/mainbanner2.jpg',
            'assets/FrontPage/mainbanner3.jpg',
            'assets/FrontPage/mainbanner4.jpg',
            'assets/FrontPage/mainbanner5.jpg'
        ];
        const randomImage = bannerImages[Math.floor(Math.random() * bannerImages.length)];
        banner.src = `${randomImage}?t=${new Date().getTime()}`;
    }
});
</script>

</body>
</html>
