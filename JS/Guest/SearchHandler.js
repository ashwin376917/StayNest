function handleKeyPress(event) {
   if (event.key === "Enter") {
     const queryInput = document.getElementById("searchInput");
     if (!queryInput) return;
 
     const query = queryInput.value.trim();
     if (query) {
       window.location.href = `../../HTML/Guest/SearchResult.php?query=${encodeURIComponent(query)}`;
     }
   }
 }
 