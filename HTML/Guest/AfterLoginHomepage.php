<?php
session_start();
require_once '../../connect.php'; // Adjust if path is different

// Check if the guest is logged in
if (!isset($_SESSION['guest_id'])) {
    header("Location: ../../HTML/Home/login.php");
    exit();
}

// Fetch Most Clicked Properties from homestay table based on total_click
$sql = "
    SELECT homestay_id, title, picture1, total_click
    FROM homestay
    WHERE homestay_status = 1
    ORDER BY total_click DESC
    LIMIT 10
";

$result = $conn->query($sql);

$mostClicked = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $mostClicked[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage - Guest</title>
    <link rel="stylesheet" href="../Home/css/homeheadersheet.css">
    <link rel="stylesheet" href="../../include/css/footer.css">
    <link rel="stylesheet" href="css/AfterLoginHomepage.css">
</head>
<body>
<header>
    <?php include('GuestHeader.php'); ?>
</header>

<nav class="sub-nav">
    <a href="#" class="tab-link active">For You</a>
    <a href="#certified-nest" class="tab-link">Category</a>
</nav>

<div class="main-container">

    <section class="recommended">
        <div class="banner-container image-wrapper">
            <img id="randomBanner" alt="StayNest Welcome" class="main-banner" src="../../assets/FrontPage/mainbanner.jpg">
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
            if (!empty($mostClicked)) {
                foreach ($mostClicked as $property) {
                    $homestayId = htmlspecialchars($property['homestay_id']);
                    $title = htmlspecialchars($property['title']);
                    $image = htmlspecialchars($property['picture1']); // e.g., 'uploads/image.jpg'

                    // Corrected path for background-image
                    $imageUrl = '/StayNest/HTML/Host/' . $image; // Assumes /StayNest/uploads/ is the correct web path

                    echo '<a href="ViewPropertyDetail.php?homestay_id=' . $homestayId . '" ';
                    echo 'class="photo-box image-wrapper clickable-image" ';
                    echo 'style="background-image: url(\'' . $imageUrl . '\');">'; // Use single quotes for URL
                    echo '    <div class="overlay"></div>';
                    echo '    <div class="bottom-label">' . $title . '</div>';
                    echo '</a>';
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
                <img src="../../assets/FrontPage/family.jpg" alt="Family Friendly">
                <div class="top-label">
                    <img src="../../assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Family Friendly</div>
            </div>
            <div class="fixed-photo image-wrapper clickable-image">
                <img src="../../assets/FrontPage/pet.jpg" alt="Pet Friendly">
                <div class="top-label">
                    <img src="../../assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Pet Friendly</div>
            </div>
            <div class="fixed-photo image-wrapper clickable-image">
                <img src="../../assets/FrontPage/antique.jpg" alt="Antique">
                <div class="top-label">
                    <img src="../../assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Antique</div>
            </div>
            <div class="fixed-photo image-wrapper clickable-image">
                <img src="../../assets/FrontPage/shared.jpg" alt="Shared">
                <div class="top-label">
                    <img src="../../assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Shared</div>
            </div>
            <div class="fixed-photo image-wrapper clickable-image">
                <img src="../../assets/FrontPage/luxury.jpg" alt="Luxury">
                <div class="top-label">
                    <img src="../../assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Luxury</div>
            </div>
            <div class="fixed-photo image-wrapper clickable-image">
                <img src="../../assets/FrontPage/minimal.jpg" alt="Minimalist">
                <div class="top-label">
                    <img src="../../assets/FrontPage/certified.png" class="certified-icon" alt="Certified"> CERTIFIED HOST
                </div>
                <div class="bottom-label">Minimalist</div>
            </div>
        </div>
    </section>

</div>

<?php include "../../include/footer.html"; ?>

<script src="../../HTML/Guest/js/AfterLoginHomepage.js?v=<?php echo time(); ?>"></script>
<script src="js/SearchHandler.js"></script>

</body>
</html>