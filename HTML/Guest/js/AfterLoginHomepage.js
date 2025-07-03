// === DOM READY ===
document.addEventListener("DOMContentLoaded", () => {
  console.log("Page loaded. Running JS...");

  // === RANDOM BANNER IMAGE ===
  // This section dynamically sets a random banner image each time the page loads.
  const banner = document.getElementById('randomBanner');
  if (banner) {
      const bannerImages = [
          '../../assets/FrontPage/mainbanner.jpg',
          '../../assets/FrontPage/mainbanner1.png',
          '../../assets/FrontPage/mainbanner2.jpg',
          '../../assets/FrontPage/mainbanner3.jpg',
          '../../assets/FrontPage/mainbanner4.jpg',
          '../../assets/FrontPage/mainbanner5.jpg'
      ];
      // Selects a random image from the array
      const randomImage = bannerImages[Math.floor(Math.random() * bannerImages.length)];
      // Sets the banner source and appends a timestamp to prevent caching, ensuring a fresh image on each load.
      banner.src = `${randomImage}?t=${new Date().getTime()}`;
  } else {
      // Logs a warning if the banner element isn't found, useful for debugging.
      console.warn("Banner image element with id 'randomBanner' not found.");
  }

  // The logic for fetching "Most Clicked Properties" has been removed from here.
  // This data is now directly populated by the PHP script on the server-side
  // before the page is sent to the browser, which improves performance.
});