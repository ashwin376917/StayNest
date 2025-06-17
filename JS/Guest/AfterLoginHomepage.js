// Toggle Sidebar
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
 
 // Simulate DB Fetch (replace with AJAX)
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
 
 // Auto-load
 window.onload = () => {
   loadNotifications();
   loadMessages();
 };
 function closeSidebar(id) {
   document.getElementById(id).classList.add('hidden');
 }
  