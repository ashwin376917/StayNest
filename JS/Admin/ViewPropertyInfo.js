function filterProperties(status) {
  const cards = document.querySelectorAll('.card');
  const buttons = document.querySelectorAll('.filter-bar button');

  buttons.forEach(btn => btn.classList.remove('active'));
  const clickedBtn = Array.from(buttons).find(btn => btn.textContent.toLowerCase() === status);
  if (clickedBtn) clickedBtn.classList.add('active');

  cards.forEach(card => {
    const cardStatus = card.getAttribute('data-status');
    if (status === 'all' || cardStatus === status) {
      card.classList.remove('hidden');
    } else {
      card.classList.add('hidden');
    }
  });
}

function setStatus(button, newStatusText, color) {
  const card = button.closest('.card');
  const homestayId = card.getAttribute('data-id');

  const statusMap = {
    'Approved': 1,
    'Banned': 2
  };
  const statusCode = statusMap[newStatusText];

  fetch('../../HTML/Admin/update_status.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${encodeURIComponent(homestayId)}&status=${statusCode}`
  })
  .then(response => response.text())
  .then(data => {
    if (data === "Success") {
      card.setAttribute('data-status', newStatusText.toLowerCase());
      location.reload(); // Reload to update UI
    } else {
      alert("Failed to update status.");
    }
  })
  .catch(err => {
    console.error(err);
    alert("Error occurred.");
  });
}
