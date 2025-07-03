// Wait for the page to load
document.addEventListener("DOMContentLoaded", () => {
  // Select all ban buttons
  const banButtons = document.querySelectorAll(".ban-btn");

  banButtons.forEach(button => {
    button.addEventListener("click", () => {
      // Find the closest user card container
      const userCard = button.closest(".card");

      // Get the user's name from the card
      const userName = userCard.querySelector("strong")?.textContent;

      // Confirm the ban action
      const confirmBan = confirm(`Are you sure you want to ban ${userName}?`);

      if (confirmBan) {
        // Remove the user card from the DOM
        userCard.remove();
        alert(`${userName} has been banned.`);
      }
    });
  });
});
// Function to filter users based on status