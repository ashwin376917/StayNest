<?php
session_start(); // Ensure this is at the very beginning of the file
include('../../connect.php'); // Ensure this path is correct for your setup

$keyword = '';
$stateFilter = '';
$districtFilter = '';
$categoryFilter = ''; // New variable for category filter
$sortBy = '';
$results = null; // Initialize as null to distinguish from empty array if query fails

// Redirect if guest is not logged in
if (!isset($_SESSION['guest_id'])) {
    header("Location: ../../HTML/LoginPage.php");
    exit;
}

// Get filter and sort parameters from GET request
if (isset($_GET['query'])) {
    $keyword = trim($_GET['query']);
}
if (isset($_GET['state']) && $_GET['state'] !== '') {
    $stateFilter = trim($_GET['state']);
}
if (isset($_GET['district']) && $_GET['district'] !== '') {
    $districtFilter = trim($_GET['district']);
}
if (isset($_GET['category']) && $_GET['category'] !== '') { // Get category from URL
    $categoryFilter = trim($_GET['category']);
}
if (isset($_GET['sort_by'])) {
    $sortBy = trim($_GET['sort_by']);
}

// --- IMPORTANT: Set $_SESSION['last_query'] based on the current search type ---
// This determines where the back button from ViewPropertyDetail.php will go
if (!empty($categoryFilter)) {
    // If a category filter is active, prioritize storing that.
    // The 'category=' prefix helps ViewPropertyDetail.php identify it as a category search.
    $_SESSION['last_query'] = 'category=' . urlencode($categoryFilter);
} elseif (!empty($keyword)) {
    // If no category, but a keyword is present, store the keyword.
    $_SESSION['last_query'] = $keyword;
} else {
    // If no keyword or category, it means we're likely viewing all listings or a direct visit.
    // Unset last_query or set to a default that indicates no specific search.
    unset($_SESSION['last_query']); // Clears previous search if no current one
}


// Start building the SQL query
// Always show active homestays (homestay_status = 1)
$sql = "SELECT * FROM homestay WHERE homestay_status = 1";

$conditions = [];
$paramTypes = "";
$bindValues = [];

// Add keyword search condition if a keyword is present
if ($keyword !== '') {
    // Escape special characters for LIKE, then wrap with wildcards
    // This prevents user input like '50%' from breaking the LIKE search pattern
    $escaped_keyword = '%' . str_replace(['%', '_'], ['\%', '\_'], $keyword) . '%';
    
    $conditions[] = "(title LIKE ? OR description LIKE ? OR state LIKE ? OR district LIKE ? OR amenities LIKE ?)";
    $paramTypes .= "sssss";
    $bindValues[] = $escaped_keyword;
    $bindValues[] = $escaped_keyword;
    $bindValues[] = $escaped_keyword;
    $bindValues[] = $escaped_keyword;
    $bindValues[] = $escaped_keyword;
}

// Add state filter
if ($stateFilter !== '') {
    $conditions[] = "state = ?";
    $paramTypes .= "s";
    $bindValues[] = $stateFilter;
}
// Add district filter
if ($districtFilter !== '') {
    $conditions[] = "district = ?";
    $paramTypes .= "s";
    $bindValues[] = $districtFilter;
}

// --- MODIFIED: Add category filter using FIND_IN_SET for robustness ---
if ($categoryFilter !== '') {
    // Use FIND_IN_SET if 'categories' column can store comma-separated values (e.g., "Family Friendly, Shared")
    // If 'categories' column *only* stores a single category string (e.g., "Family Friendly")
    // then "categories = ?" is sufficient. FIND_IN_SET is more flexible.
    $conditions[] = "FIND_IN_SET(?, categories)";
    $paramTypes .= "s";
    $bindValues[] = $categoryFilter;
}


// Append conditions to the SQL query
if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

// Add sorting
if ($sortBy === 'price_asc') {
    $sql .= " ORDER BY price_per_night ASC";
} elseif ($sortBy === 'price_desc') {
    $sql .= " ORDER BY price_per_night DESC";
} elseif ($sortBy === 'latest') {
    $sql .= " ORDER BY homestay_id DESC"; // Assuming homestay_id for latest (higher ID means later)
} else {
    $sql .= " ORDER BY homestay_id DESC"; // Default sort if none specified
}

// Prepare the statement
$stmt = $conn->prepare($sql);

if ($stmt) {
    // Dynamically bind parameters only if there are any
    if (!empty($paramTypes)) {
        // Create an array of references for bind_param
        $params = [$paramTypes]; // First element is the types string
        foreach ($bindValues as &$value) { // Use & to get reference
            $params[] = &$value;
        }
        // Call bind_param with the array of references
        call_user_func_array([$stmt, 'bind_param'], $params);
    }

    $stmt->execute();
    $results = $stmt->get_result(); // Get the mysqli_result object
    $stmt->close();
} else {
    // Handle SQL prepare error
    echo "<p style='padding: 20px; font-size: 18px; color: red;'>Error preparing search query: " . $conn->error . "</p>";
}

// Determine the display title for the results page
$display_title = "All Homestays";
if (!empty($keyword)) {
    $display_title = "Results for \"" . htmlspecialchars($keyword) . "\"";
}
if (!empty($categoryFilter)) {
    // If both keyword and category are present, you might combine them or prioritize one for the title
    if (!empty($keyword)) {
        $display_title = "Results for \"" . htmlspecialchars($keyword) . "\" in Category: \"" . htmlspecialchars($categoryFilter) . "\"";
    } else {
        $display_title = "Properties in Category: \"" . htmlspecialchars($categoryFilter) . "\"";
    }
}
// If no filters, the default "All Homestays" is fine.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Search Results</title>
    <link rel="stylesheet" href="../../HTML/Guest/css/GuestDashboard.css" />
    <link rel="stylesheet" href="../../HTML/Guest/css/SearchResult.css?v=4"/>
    <link rel="stylesheet" href="css/GuestHeader.css">
    <link rel="stylesheet" href="../../include/css/footer.css">
    <style>
        /* CSS for filter and sort container - You can move this to SearchResult.css */
        .filter-sort-container {
            display: flex;
            gap: 15px; /* Space between filter groups */
            margin-bottom: 20px;
            padding: 0 20px; /* Adjust padding to match your layout */
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
            align-items: flex-end; /* Align items at the bottom */
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .filter-group select,
        .filter-sort-container button {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            background-color: #fff;
            cursor: pointer;
        }

        .filter-sort-container button {
            background-color: #007bff; /* Example button color */
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
            height: 38px; /* Match height of select for alignment */
            align-self: flex-end; /* Align with selects */
        }

        .filter-sort-container button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<?php include('GuestHeader.php'); ?>

<div class="main-container">
    <div class="search-result">
        <h2><?= htmlspecialchars($display_title) ?></h2>

        <div class="filter-sort-container">
            <div class="filter-group">
                <label for="state-filter">State:</label>
                <select id="state-filter" name="state">
                    <option value="">All States</option>
                    </select>
            </div>

            <div class="filter-group">
                <label for="district-filter">District:</label>
                <select id="district-filter" name="district" disabled>
                    <option value="">All Districts</option>
                    </select>
            </div>

            <div class="filter-group">
                <label for="category-filter">Category:</label>
                <select id="category-filter" name="category">
                    <option value="">All Categories</option>
                    <option value="Shared">Shared</option>
                    <option value="Luxury">Luxury</option>
                    <option value="Minimalist">Minimalist</option>
                    <option value="Pet Friendly">Pet Friendly</option>
                    <option value="Family Friendly">Family Friendly</option>
                    <option value="Antique">Antique</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="sort-by">Sort By:</label>
                <select id="sort-by" name="sort_by">
                    <option value="">Default (Latest)</option>
                    <option value="price_asc" <?php if ($sortBy === 'price_asc') echo 'selected'; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php if ($sortBy === 'price_desc') echo 'selected'; ?>>Price: High to Low</option>
                    <option value="latest" <?php if ($sortBy === 'latest') echo 'selected'; ?>>Latest</option>
                </select>
            </div>
            <button onclick="applyFiltersAndSort()">Apply Filters</button>
        </div>

        <div class="result-grid">

        <?php
        // Check if $results is a valid mysqli_result object and has rows
        if ($results instanceof mysqli_result && $results->num_rows > 0) {
            while ($row = $results->fetch_assoc()) {
                $homestayId = htmlspecialchars($row['homestay_id']);
                echo '<a href="ViewPropertyDetail.php?homestay_id=' . $homestayId . '" class="result-link">';
                echo '      <div class="result-card">';
                echo '          <div class="result-left">';
                echo '              <img src="/StayNest/HTML/Host/' . htmlspecialchars($row['picture1']) . '" alt="Main Image">';
                echo '          </div>';
                echo '          <div class="result-middle">';
                echo '              <div class="text-top">';
                echo '                  <h3>' . htmlspecialchars($row['title']) . '</h3>';
                echo '                  <p>' . htmlspecialchars($row['state'] . ', ' . $row['district']) . '</p>';
                echo '              </div>';
                echo '              <div class="text-bottom">';
                echo '                  <p class="price">RM ' . htmlspecialchars(number_format($row['price_per_night'], 2)) . '</p>';
                echo '              </div>';
                echo '          </div>';
                echo '          <div class="result-right">';
                echo '              <div class="preview-img"><img src="/StayNest/HTML/Host/' . htmlspecialchars($row['picture2']) . '" alt="Preview 1"></div>';
                echo '              <div class="preview-img"><img src="/StayNest/HTML/Host/' . htmlspecialchars($row['picture3']) . '" alt="Preview 2"></div>';
                echo '          </div>';
                echo '      </div>';
                echo '</a>';
            }
        } else {
            echo "<p style='padding: 20px; font-size: 18px;'>No matching homestays found.</p>";
        }
        ?>

        </div>
    </div>
</div>

<?php include "../../include/footer.html"; ?>
<script>
// Function to apply filters and sort from the current page's filter bar
function applyFiltersAndSort() {
    // This function will build the URL based on the current select box values
    const query = new URLSearchParams(window.location.search).get('query') || ''; // Preserve existing keyword if any
    const state = document.getElementById('state-filter').value;
    const district = document.getElementById('district-filter').value;
    const sortBy = document.getElementById('sort-by').value;
    const category = document.getElementById('category-filter').value;

    let queryString = '';
    if (query) {
        queryString += `query=${encodeURIComponent(query)}`;
    }
    if (state) {
        queryString += (queryString ? '&' : '') + `state=${encodeURIComponent(state)}`;
    }
    if (district) {
        queryString += (queryString ? '&' : '') + `district=${encodeURIComponent(district)}`;
    }
    if (sortBy) {
        queryString += (queryString ? '&' : '') + `sort_by=${encodeURIComponent(sortBy)}`;
    }
    if (category) {
        queryString += (queryString ? '&' : '') + `category=${encodeURIComponent(category)}`;
    }

    window.location.href = `../../HTML/Guest/SearchResult.php?${queryString}`;
}


// --- JavaScript for Dynamic Filters (States and Districts) ---
const malaysiaLocations = {
    "Johor": ["Johor Bahru", "Batu Pahat", "Kluang", "Kota Tinggi", "Kulai", "Mersing", "Muar", "Pontian", "Segamat", "Tangkak"],
    "Kedah": ["Alor Setar", "Baling", "Bandar Baharu", "Kubang Pasu", "Kulim", "Langkawi", "Padang Terap", "Pendang", "Sik", "Yan", "Pokok Sena"],
    "Kelantan": ["Kota Bharu", "Bachok", "Dabong", "Gua Musang", "Jeli", "Kuala Krai", "Machang", "Pasir Mas", "Pasir Puteh", "Tanah Merah", "Tumpat"],
    "Melaka": ["Alor Gajah", "Jasin", "Melaka Tengah"],
    "Negeri Sembilan": ["Seremban", "Jempol", "Jelebu", "Kuala Pilah", "Port Dickson", "Rembau", "Tampin"],
    "Pahang": ["Kuantan", "Bentong", "Bera", "Cameron Highlands", "Jerantut", "Kuala Lipis", "Maran", "Pekan", "Raub", "Rompin", "Temerloh"],
    "Penang": ["George Town", "Butterworth", "Bukit Mertajam", "Nibong Tebal", "Balik Pulau"],
    "Perak": ["Ipoh", "Batang Padang", "Hilir Perak", "Kuala Kangsar", "Larut, Matang dan Selama", "Manjung", "Muallim", "Kinta", "Kerian", "Perak Tengah", "Tapah", "Kampar"],
    "Perlis": ["Kangar"],
    "Sabah": ["Kota Kinabalu", "Beaufort", "Beluran", "Keningau", "Kudat", "Lahad Datu", "Papar", "Penampang", "Putatan", "Ranau", "Sandakan", "Semporna", "Tawau", "Tongod", "Kuala Penyu", "Nabawan", "Sipitang", "Telupid", "Tuaran", "Kota Belud", "Kunak"],
    "Sarawak": ["Kuching", "Bintulu", "Kapit", "Limbang", "Miri", "Mukah", "Samarahan", "Sibu", "Sri Aman", "Betong", "Sarikei", "Serian"],
    "Selangor": ["Petaling Jaya", "Klang", "Gombak", "Hulu Langat", "Hulu Selangor", "Kuala Langat", "Kuala Selangor", "Sabak Bernam", "Sepang"],
    "Terengganu": ["Kuala Terengganu", "Besut", "Dungun", "Kemaman", "Marang", "Setiu", "Hulu Terengganu"],
    "Kuala Lumpur": ["Kuala Lumpur"],
    "Labuan": ["Labuan"],
    "Putrajaya": ["Putrajaya"]
};

const stateFilterSelect = document.getElementById('state-filter');
const districtFilterSelect = document.getElementById('district-filter');
const sortBySelect = document.getElementById('sort-by');
const categoryFilterSelect = document.getElementById('category-filter'); // Get category select element

function populateFilterStates() {
    // Clear existing options first
    stateFilterSelect.innerHTML = '<option value="">All States</option>';
    for (const state in malaysiaLocations) {
        const option = document.createElement('option');
        option.value = state;
        option.textContent = state;
        stateFilterSelect.appendChild(option);
    }

    // Set selected state if already in URL (from PHP's $stateFilter)
    const urlParams = new URLSearchParams(window.location.search);
    const selectedStateFromUrl = urlParams.get('state');
    if (selectedStateFromUrl) {
        stateFilterSelect.value = selectedStateFromUrl;
        populateFilterDistricts(selectedStateFromUrl); // Also populate districts for the pre-selected state
    }
}

function populateFilterDistricts(selectedState) {
    districtFilterSelect.innerHTML = '<option value="">All Districts</option>';
    if (selectedState && malaysiaLocations[selectedState]) {
        const districts = malaysiaLocations[selectedState];
        districts.forEach(district => {
            const option = document.createElement('option');
            option.value = district;
            option.textContent = district;
            districtFilterSelect.appendChild(option);
        });
        districtFilterSelect.disabled = false;

        // Set selected district if already in URL (from PHP's $districtFilter)
        const urlParams = new URLSearchParams(window.location.search);
        const selectedDistrictFromUrl = urlParams.get('district');
        if (selectedDistrictFromUrl) {
            districtFilterSelect.value = selectedDistrictFromUrl;
        }
    } else {
        districtFilterSelect.disabled = true;
    }
}

// Function to set selected category from URL
function setSelectedCategory() {
    const urlParams = new URLSearchParams(window.location.search);
    const selectedCategoryFromUrl = urlParams.get('category');
    if (selectedCategoryFromUrl) {
        categoryFilterSelect.value = selectedCategoryFromUrl;
    }
}

// Event listener for state filter change
stateFilterSelect.addEventListener('change', () => {
    const selectedState = stateFilterSelect.value;
    populateFilterDistricts(selectedState);
});

// Initialize filter states, districts, and categories on page load
window.addEventListener('load', () => {
    populateFilterStates();
    setSelectedCategory(); // Call to set category from URL
    // Set sortBy from URL on load
    const urlParams = new URLSearchParams(window.location.search);
    const selectedSortByFromUrl = urlParams.get('sort_by');
    if (selectedSortByFromUrl) {
        sortBySelect.value = selectedSortByFromUrl;
    }
});

</script>
</body>
</html>