
<!--  footer -->
<footer>
   <div class="footer">
      <div class="container">
         <div class="row">
            <div class="col-md-8 offset-md-2">
               <ul class="location_icon">
                  <li><a href="index.php"><i class="fa fa-home" aria-hidden="true"></i></a><br> Ana Sayfa</li>
                  <li><a href="firmalar.php"><i class="fa fa-plus" aria-hidden="true"></i></a><br> Firmalar</li>
                  <li><a href="hareketgir.php"><i class="fa fa-calendar" aria-hidden="true"></i></a><br> Hareket Gir</li>
                  <li><a href="kartgir.php"><i class="fa fa-suitcase" aria-hidden="true"></i></a><br> Kart Gir</li>
               </ul>
            </div>
         </div>
      </div>
      <div class="copyright">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <p>© 2023 All Rights Reserved. Design by<a href="https://www.rmtroje.com/"> RMT Proje</a></p>
               </div>
            </div>
         </div>
      </div>
   </div>
</footer>
<!-- end footer -->
<!-- Javascript files-->
<script src="js/jquery.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script src="js/jquery-3.0.0.min.js"></script>
<!-- sidebar -->
<script src="js/jquery.mCustomScrollbar.concat.min.js"></script>
<script src="js/custom.js"></script>
<script>
         // This example adds a marker to indicate the position of Bondi Beach in Sydney,
         // Australia.
   function initMap() {
     var map = new google.maps.Map(document.getElementById('map'), {
       zoom: 11,
       center: {lat: 40.645037, lng: -73.880224},
    });

     var image = 'images/maps-and-flags.png';
     var beachMarker = new google.maps.Marker({
       position: {lat: 40.645037, lng: -73.880224},
       map: map,
       icon: image
    });
  }
</script>
<!-- google map js -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA8eaHt9Dh5H57Zh0xVTqxVdBFCvFMqFjQ&callback=initMap"></script>
<!-- end google map js 
<script>
        // Her 10 saniyede bir sayfayı yenile
  setInterval(function() {
   location.reload();
        }, 10000); // 10 saniye = 10,000 milisaniye
     </script>
  --> 

  <style>
   .form-row {
      display: flex;
      justify-content: space-between;
   }

   .form-column {
      flex: 1;
      padding: 0 10px;
   }

   .expanded-row {
      flex-basis: 100%;
   }
 </style>

</body>
</html>