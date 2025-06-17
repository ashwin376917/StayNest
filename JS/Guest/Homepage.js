document.addEventListener('DOMContentLoaded', () => {
  // Handle image clicks
  const clickableImages = document.querySelectorAll('.clickable-image');
  clickableImages.forEach(image => {
    image.addEventListener('click', () => {
      const confirmResult = confirm("Please sign in or sign up to view this property.\nClick OK to proceed to Sign Up.");
      if (confirmResult) {
        window.location.href = "../../HTML/Home/SignUp.html";
      }
    });
  });

  // Handle search bar clicks
  const searchBar = document.querySelector('.search-bar');
  if (searchBar) {
    searchBar.addEventListener('click', (e) => {
      e.preventDefault();
      const confirmResult = confirm("Please sign in or sign up to use the search feature.\nClick OK to proceed to Sign Up.");
      if (confirmResult) {
        window.location.href = "../../HTML/Home/SignUp.html";
      }
    });
  }
});
