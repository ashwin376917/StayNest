window.addEventListener("DOMContentLoaded", () => {
   // Placeholder for future PHP dynamic data (via PHP echo or AJAX)
 
   const data = {
     name: "", // Will be populated from DB
     city: "",
     price: "",
     description: "",
     mainImage: "", // Path from DB
     amenities: [] // Array of amenity names from DB
   };
 
   // Example fallback (you may remove when connecting PHP)
   data.name = "Loading Property Name...";
   data.city = "Loading City...";
   data.price = "Loading Price...";
   data.description = "Loading description...";
   data.mainImage = "../../assets/sample.jpg";
   data.amenities = ["Wifi", "Free Parking", "Kitchen"];
 
   // Update DOM
   document.getElementById("propertyName").textContent = data.name;
   document.getElementById("propertyCity").textContent = data.city;
   document.getElementById("propertyPrice").textContent = data.price;
   document.getElementById("propertyDescription").textContent = data.description;
   document.getElementById("mainImage").style.backgroundImage = `url('${data.mainImage}')`;
 
   const amenitiesIcons = {
     "Wifi": "wifi_icon.png",
     "Free Parking": "parking.png",
     "Kitchen": "kitchen.png",
     "Pool": "pool.png",
     "Smart TV": "tv.png",
     "Personal Workspace": "workspace.png",
     "Washer": "Washer.png",
     "Hair Dryer": "wifi_icon.png",
     "Dryer": "wifi_icon.png",
     "Aircond": "AC.png"
   };
 
   const amenitiesList = document.getElementById("amenitiesList");
   data.amenities.forEach(item => {
     const div = document.createElement("div");
     div.className = "amenity-item";
     div.innerHTML = `
       <img src="../../assets/${amenitiesIcons[item] || "default.png"}" class="amenity-icon" />
       <span>${item}</span>
     `;
     amenitiesList.appendChild(div);
   });
 });
 