// $(document).ready(function () {
//     const bookedDates = JSON.parse($("#bookedDatesJson").text() || "[]");
//     const pricePerNight = parseFloat($("#propertyPrice").data("price"));

//     function disableBooked(date) {
//         const formatted = $.datepicker.formatDate('yy-mm-dd', date);
//         if (bookedDates.includes(formatted)) {
//             return [false, "booked-date", "Booked"];
//         }
//         return [true, ""];
//     }

//     $("#checkIn").datepicker({
//         minDate: +2,
//         dateFormat: "yy-mm-dd",
//         beforeShowDay: disableBooked,
//         showOn: "both",
//         buttonImage: "../../assets/calendar.png", // Replace with your icon path
//         buttonImageOnly: true,
//         buttonText: "Select date",
//         onSelect: function (selectedDate) {
//             const checkInDate = new Date(selectedDate);
//             const minCheckOut = new Date(checkInDate);
//             minCheckOut.setDate(checkInDate.getDate() + 1);
//             const maxCheckOut = new Date(checkInDate);
//             maxCheckOut.setDate(checkInDate.getDate() + 15);
    
//             $("#checkOut").datepicker("option", "minDate", minCheckOut);
//             $("#checkOut").datepicker("option", "maxDate", maxCheckOut);
    
//             updatePrice();
//         }
//     });
    
//     $("#checkOut").datepicker({
//         dateFormat: "yy-mm-dd",
//         beforeShowDay: disableBooked,
//         showOn: "both",
//         buttonImage: "../../assets/calendar.png", // Replace with your icon path
//         buttonImageOnly: true,
//         buttonText: "Select date",
//         onSelect: updatePrice
//     });
    

//     function updatePrice() {
//         const checkInVal = $("#checkIn").val();
//         const checkOutVal = $("#checkOut").val();

//         if (checkInVal && checkOutVal) {
//             const inDate = new Date(checkInVal);
//             const outDate = new Date(checkOutVal);
//             if (outDate > inDate) {
//                 const diffTime = outDate - inDate;
//                 const diffDays = diffTime / (1000 * 60 * 60 * 24);
//                 const total = diffDays * pricePerNight;
//                 $("#propertyPrice").text(`RM ${total.toFixed(2)} total (${diffDays} night${diffDays > 1 ? 's' : ''})`);
//                 return;
//             }
//         }
//         $("#propertyPrice").text(`RM ${pricePerNight.toFixed(2)} / night`);
//     }

//     $("#bookingForm").on("submit", function (e) {
//         const checkIn = $("#checkIn").val();
//         const checkOut = $("#checkOut").val();
//         const guests = $("#guests").val();

//         if (!checkIn || !checkOut || new Date(checkOut) <= new Date(checkIn)) {
//             e.preventDefault();
//             alert("Please select valid check-in and check-out dates.");
//             return;
//         }

//         if (bookedDates.includes(checkIn) || bookedDates.includes(checkOut)) {
//             e.preventDefault();
//             alert("You cannot select dates that are already booked. Please choose different dates.");
//             return;
//         }

//         if (!guests) {
//             e.preventDefault();
//             alert("Please select the number of guests.");
//             return;
//         }

//         $("#formCheckIn").val(checkIn);
//         $("#formCheckOut").val(checkOut);
//         $("#formGuests").val(guests);
//     });
// });
