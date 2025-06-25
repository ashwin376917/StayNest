// === SIDEBAR HANDLING ===
document.querySelectorAll('.icon').forEach((icon, index) => {
  icon.addEventListener('click', () => {
    if (index === 0) toggleSidebar('notificationSidebar');
    if (index === 1) toggleSidebar('messagesSidebar');
  });
});

function toggleSidebar(id) {
  const sidebars = ['notificationSidebar', 'messagesSidebar'];
  sidebars.forEach(sid => {
    if (sid !== id) document.getElementById(sid).classList.add('hidden');
  });
  document.getElementById(id).classList.toggle('hidden');
}

function closeSidebar(id) {
  document.getElementById(id).classList.add('hidden');
}

// === CHAT FUNCTIONALITY ===
function loadNotifications() {
  const list = document.getElementById('notificationList');
  list.innerHTML = `
    <li>Booking confirmed for Ayer Keroh</li>
    <li>New message from Host (The Rumah)</li>
  `;
}

function loadMessages() {
  const chatList = document.getElementById('chatList');
  chatList.innerHTML = `
    <div onclick="openChat('The Rumah')">The Rumah (Host)</div>
    <div onclick="openChat('Luxury Stay')">Luxury Stay (Host)</div>
  `;
}

function openChat(user) {
  document.getElementById('chatUser').textContent = `Chat with ${user}`;
  document.getElementById('chatWindow').classList.remove('hidden');
  document.getElementById('chatMessages').innerHTML = `
    <div class="message received">Hello! Your booking is confirmed.</div>
    <div class="message sent">Thank you!</div>
  `;
}

function closeChat() {
  document.getElementById('chatWindow').classList.add('hidden');
}

function sendMessage() {
  const input = document.getElementById('chatInputBox');
  const msg = input.value.trim();
  if (!msg) return;
  const container = document.getElementById('chatMessages');
  container.innerHTML += `<div class="message sent">${msg}</div>`;
  input.value = '';
}

// === DOM READY ===
document.addEventListener("DOMContentLoaded", () => {
  console.log("Page loaded. Running JS...");

  // === RANDOM BANNER IMAGE ===
  const banner = document.getElementById('randomBanner');
  if (banner) {
    const bannerImages = [
      '../../assets/Guest/mainbanner1.jpg',
      '../../assets/Guest/mainbanner2.jpg',
      '../../assets/Guest/mainbanner3.jpg',
      '../../assets/Guest/mainbanner4.jpg',
      '../../assets/Guest/mainbanner5.jpg'
    ];
    const randomImage = bannerImages[Math.floor(Math.random() * bannerImages.length)];
    banner.src = `${randomImage}?t=${new Date().getTime()}`;
  } else {
    console.warn("Banner image element with id 'randomBanner' not found.");
  }

  // === LOAD MOST CLICKED PROPERTIES (Replaces previous 'Most Searched') ===
  fetch('../../HTML/Guest/getMostSearched.php')
    .then(response => response.json())
    .then(data => {
      const section = document.querySelector('.most-searched h2');
      const container = document.querySelector('.photo-searched');

      if (!data || data.length === 0 || !container || !section) {
        section.textContent = 'Most searched';
        return;
      }

      section.textContent = 'Most clicked properties';

      container.innerHTML = '';

      data.forEach(item => {
        const { homestay_id, title, picture1 } = item;

        const box = document.createElement('div');
        box.className = 'photo-box image-wrapper';

        const overlay = document.createElement('div');
        overlay.className = 'overlay';

        const label = document.createElement('div');
        label.textContent = title;
        label.style.position = 'absolute';
        label.style.bottom = '10px';
        label.style.left = '10px';
        label.style.color = 'white';
        label.style.fontWeight = 'bold';
        label.style.fontSize = '18px';
        label.style.zIndex = '2';

        if (picture1) {
          box.style.backgroundImage = `url('/StayNest/upload/${picture1}')`;
          box.style.backgroundSize = 'cover';
          box.style.backgroundPosition = 'center';
        }

        box.appendChild(overlay);
        box.appendChild(label);

        box.addEventListener('click', () => {
          window.location.href = `../../HTML/Guest/ViewPropertyDetail.php?homestay_id=${encodeURIComponent(homestay_id)}`;
        });

        container.appendChild(box);
      });
    })
    .catch(error => {
      console.error('Error loading most clicked properties:', error);
    });
});
