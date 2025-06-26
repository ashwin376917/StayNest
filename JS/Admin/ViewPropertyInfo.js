document.addEventListener("DOMContentLoaded", () => {
  // Handle approve/ban buttons
  document.querySelectorAll(".approve-btn, .ban-btn").forEach(button => {
    button.addEventListener("click", function () {
      const card = this.closest(".card");
      const propertyId = card.getAttribute("data-id");
      const action = this.classList.contains("approve-btn") ? "approve" : "ban";
      const actionLabel = action === "approve" ? "Approve" : "Ban";

      const confirmed = confirm(`Are you sure you want to ${actionLabel} this property?`);
      if (!confirmed) return;

      // Optional: AJAX request can go here to update DB
      // For now, just update the UI
      if (action === "approve") {
        card.setAttribute("data-status", "approved");
      } else {
        card.setAttribute("data-status", "banned");
      }

      // Remove the card from view for simplicity
      card.remove();
      alert(`Property has been ${actionLabel}d.`);
    });
  });

  // Filtering cards by status
  window.filterProperties = function (status) {
    const cards = document.querySelectorAll(".card");
    document.querySelectorAll(".filter-bar button").forEach(btn => {
      btn.classList.remove("active");
      if (btn.textContent.toLowerCase() === status) {
        btn.classList.add("active");
      }
    });

    cards.forEach(card => {
      const cardStatus = card.getAttribute("data-status");
      if (status === "all" || cardStatus === status) {
        card.style.display = "";
      } else {
        card.style.display = "none";
      }
    });
  };
});
