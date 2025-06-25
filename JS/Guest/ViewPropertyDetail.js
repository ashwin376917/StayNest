document.addEventListener("DOMContentLoaded", () => {
  const today = new Date();
  const maxDate = new Date();
  maxDate.setMonth(maxDate.getMonth() + 1);

  const checkIn = document.getElementById("checkIn");
  const checkOut = document.getElementById("checkOut");
  const guestsSelect = document.getElementById("guests");
  const priceBox = document.getElementById("propertyPrice");
  const pricePerNight = parseFloat(priceBox.dataset.price);
  const bookingForm = document.getElementById("bookingForm");
  const formCheckIn = document.getElementById("formCheckIn");
  const formCheckOut = document.getElementById("formCheckOut");
  const formGuests = document.getElementById("formGuests");

  // Format date to YYYY-MM-DD
  const formatDate = (date) => date.toISOString().split("T")[0];

  checkIn.min = formatDate(today);
  checkIn.max = formatDate(maxDate);
  checkOut.min = formatDate(today);
  checkOut.max = formatDate(maxDate);

  function updatePrice() {
    const inDate = new Date(checkIn.value);
    const outDate = new Date(checkOut.value);

    if (checkIn.value && checkOut.value && outDate > inDate) {
      const diffTime = outDate - inDate;
      const diffDays = diffTime / (1000 * 60 * 60 * 24);
      const total = diffDays * pricePerNight;
      priceBox.textContent = `RM ${total.toFixed(2)} total (${diffDays} night${diffDays > 1 ? 's' : ''})`;
    } else {
      priceBox.textContent = `RM ${pricePerNight.toFixed(2)} / night`;
    }
  }

  checkIn.addEventListener("change", () => {
    if (checkIn.value) {
      const selectedCheckIn = new Date(checkIn.value);
      selectedCheckIn.setDate(selectedCheckIn.getDate() + 1);
      checkOut.min = formatDate(selectedCheckIn);
    }
    updatePrice();
  });

  checkOut.addEventListener("change", updatePrice);

  bookingForm.addEventListener("submit", (e) => {
    if (!checkIn.value || !checkOut.value || new Date(checkOut.value) <= new Date(checkIn.value)) {
      e.preventDefault();
      alert("⚠️ Please select a valid check-in and check-out date.\nCheck-out must be after check-in.");
      return;
    }

    if (!guestsSelect.value || guestsSelect.value === "Select Guests") {
      e.preventDefault();
      alert("⚠️ Please select the number of guests.");
      return;
    }

    formCheckIn.value = checkIn.value;
    formCheckOut.value = checkOut.value;
    formGuests.value = guestsSelect.value;
  });
});
