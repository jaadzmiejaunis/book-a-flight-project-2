<?php
session_start();

include 'connection.php';

// Check connection
if (!$connection) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("An error occurred connecting to the database to fetch flights. Please try again later.");
}

$loggedIn = isset($_SESSION['book_id']); 
$username = $loggedIn && isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; 
$defaultProfilePicture = 'path/to/default-profile-picture.png'; 
$profilePictureUrl = $loggedIn && isset($_SESSION['profile_picture_url']) ? htmlspecialchars($_SESSION['profile_picture_url']) : $defaultProfilePicture;

$search_from = trim($_GET['from_location'] ?? ''); 
$search_to = trim($_GET['to_location'] ?? '');

mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Flight</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Leaflet used for rendering map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <style>
        body {
            background-color: #1e1e2d;
            color: #e0e0e0;
            font-family: sans-serif;
             margin: 0;
             padding: 0;
             display: flex;
             flex-direction: column;
             min-height: 100vh;
        }

        .top-gradient-bar {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
            padding: 10px 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .top-gradient-bar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
             max-width: 1140px;
             margin: 0 auto;
             flex-wrap: wrap;
        }

        .top-gradient-bar .site-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
            margin-right: auto;
            white-space: nowrap;
        }
         .top-gradient-bar .site-title:hover {
              text-decoration: underline;
         }

        .top-gradient-bar .user-info {
            display: flex;
            align-items: center;
            color: white;
             flex-shrink: 0;
             margin-left: auto;
             white-space: nowrap;
        }
         .top-gradient-bar .user-info a {
             color: white;
             text-decoration: none;
             display: flex;
             align-items: center;
         }
         .top-gradient-bar .user-info a:hover {
              text-decoration: underline;
         }

        .top-gradient-bar .profile-picture-nav,
        .top-gradient-bar .profile-icon-nav {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-left: 8px;
            vertical-align: middle;
            object-fit: cover;
             border: 1px solid white;
        }
         .top-gradient-bar .profile-icon-nav {
              border: none;
         }

         .top-gradient-bar .btn-danger {
             background-color: #dc3545;
             border-color: #dc3545;
             padding: .3rem .6rem;
             font-size: .95rem;
             line-height: 1.5;
             border-radius: .2rem;
             margin-left: 10px;
         }
         .top-gradient-bar .btn-danger:hover {
             background-color: #c82333;
             border-color: #bd2130;
         }

        .navbar {
            background-color: #212529;
            padding: 0 20px;
            margin-bottom: 0;
            background-image: none;
            box-shadow: none;
            min-height: auto;
        }

        .navbar > .container {
             display: flex;
             align-items: center;
             width: 100%;
             max-width: 1140px;
             margin: 0 auto;
             padding: 0;
        }

        .navbar-brand,
        .navbar-toggler {
            display: none;
        }
        @media (max-width: 991.98px) {
             .navbar-toggler {
                 display: block;
                 padding: .25rem .75rem;
                 font-size: 1.25rem;
                 line-height: 1;
                 background-color: transparent;
                 border: 1px solid rgba(255, 255, 255, .1);
                 border-radius: .25rem;
             }
              .navbar-collapse {
                  background-color: #212529;
                  padding: 10px;
              }
               .navbar > .container {
                   justify-content: space-between;
              }
               .navbar-collapse {
                    flex-grow: 1;
               }
        }

        .navbar-nav .nav-link {
             padding: 8px 15px;
             color: white !important;
             transition: background-color 0.3s ease, text-decoration 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            text-decoration: underline;
            color: white !important;
        }

        .navbar-nav .nav-link:active {
             background-color: rgba(255, 255, 255, 0.2);
        }

        .page-content {
             padding: 20px;
             flex-grow: 1;
        }

        .search-container {
            margin: 30px auto;
            max-width: 800px;
            background-color: #282b3c;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .search-container label {
             color: #e0e0e0;
        }

         .search-form .form-control {
             background-color: #3a3e52;
             border-color: #3a3e52;
             color: #e0e0e0;
         }
         .search-form .form-control::placeholder {
             color: #a0a0a0;
         }
          .search-form .form-control:focus {
              background-color: #3a3e52;
              border-color: #ffb03a;
              color: white;
              box-shadow: none;
          }

         .search-form .btn-primary {
             background-color: #ffb03a;
             border-color: #ffb03a;
             color: white;
             background-image: none;
             background-size: auto;
             transition: none;
             border-radius: .25rem;
             font-size: 1rem;
             padding: .375rem .75rem;
             box-shadow: none;
         }
          .search-form .btn-primary:hover {
              background-color: #dd5b12;
              border-color: #dd5b12;
              background-position: initial;
          }
           .search-form .btn-primary:focus {
                box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.5);
           }

        .flight-card {
            background-color: #2c2c54;
            color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            padding: 15px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: transform 0.2s ease-in-out;
             border: 1px solid #3a3e52;
        }

        .flight-card:hover {
            transform: translateY(-5px);
             border-color: #6a82fb;
        }

        .flight-card h5 {
            color: #6a82fb;
            margin-bottom: 5px;
        }

        .flight-card p {
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

         .flight-card .btn-primary {
             background-color: #007bff;
             border-color: #007bff;
             color: white;
             background-image: none;
             background-size: auto;
             transition: none;
             border-radius: .25rem;
             font-size: 0.9rem;
             padding: .25rem .5rem;
             box-shadow: none;
         }
          .flight-card .btn-primary:hover {
              background-color: #0056b3;
              border-color: #0056b3;
          }

        .flight-list-container {
            margin-top: 20px;
            padding: 0 20px;
        }

         .container > p {
             color: #e0e0e0;
         }

    </style>
</head>

<body>
    <!-- Most upper bar -->
    <div class="top-gradient-bar">
        <div class="container"> <a href="index.php" class="site-title">SierraFlight</a>
            <div class="user-info">
                <?php if ($loggedIn): ?>
                     <a href="profile_page.php">
                         Profile
                         <?php if ($profilePictureUrl === $defaultProfilePicture): ?>
                              <i class="fas fa-user-circle fa-lg profile-icon-nav"></i>
                         <?php else: ?>
                              <img src="<?php echo $profilePictureUrl; ?>" alt="Profile Picture" class="profile-picture-nav">
                         <?php endif; ?>
                     </a>
                     <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
                <?php else: ?>
                    <a href="login_page.php" class="nav-link">Login/Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Second upper bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container"> <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home <span class="sr-only">(current)</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="book_a_flight.php">Book a Flight</a>
                    </li>
                     <?php if ($loggedIn): ?>
                     <li class="nav-item">
                         <a class="nav-link" href="profile_page.php">Profile</a>
                     </li>
                      <li class="nav-item">
                         <a class="nav-link" href="booking_history.php">Check Book</a>
                     </li>
                     <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <h2 class="text-center mb-4" style="color: white;">Search Flights</h2>

        <div class="search-container">
            <form action="book_a_flight.php" method="get" class="search-form">
            <div class="form-row">
                <div class="form-group col-md-6">
                <label for="from_location">From:</label>
                <input type="text" class="form-control" id="from_location" name="from_location" 
                    placeholder="Origin" value="<?php echo htmlspecialchars($search_from); ?> ">
                <div id="mapFrom" style="height:250px; margin-top:10px; border-radius:8px;"></div>
                </div>

                <div class="form-group col-md-6">
                <label for="to_location">To:</label>
                <input type="text" class="form-control" id="to_location" name="to_location" 
                    placeholder="Destination" value="<?php echo htmlspecialchars($search_to); ?>">
                <div id="mapTo" style="height:250px; margin-top:10px; border-radius:8px;"></div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Search Flight</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // init maps
            var mapFrom = L.map('mapFrom').setView([3.1390, 101.6869], 5); 
            var mapTo   = L.map('mapTo').setView([3.1390, 101.6869], 5);

            // display/render the map
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(mapFrom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(mapTo);

            // place initial markers
            var markerFrom = L.marker([3.1390, 101.6869], {draggable:true}).addTo(mapFrom);
            var markerTo   = L.marker([3.1390, 101.6869], {draggable:true}).addTo(mapTo);

            // reverse geocoding (basically to get location name from the longitude/latitude)
            function reverseGeocode(lat, lng, inputId) {
                fetch(`reverse_proxy.php?lat=${lat}&lon=${lng}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.address) {
                            let addr = data.address;
                            let city = addr.city || addr.town || addr.village || addr.hamlet || "";
                            let state = addr.state || "";
                            let country = addr.country || "";

                            let formatted = [city, state, country].filter(Boolean).join(", ");
                            document.getElementById(inputId).value = formatted ||
                                (lat.toFixed(6) + ", " + lng.toFixed(6));
                        } else {
                            document.getElementById(inputId).value = lat.toFixed(6) + ", " + lng.toFixed(6);
                        }
                    })
                    .catch(err => {
                        console.error("Reverse geocoding failed:", err);
                        document.getElementById(inputId).value = lat.toFixed(6) + ", " + lng.toFixed(6);
                    });
            }
            
            // search via textboxes
            function searchLocation(query, map, marker, inputId) {
                fetch(`search_proxy.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            let place = data[0];
                            let lat = parseFloat(place.lat);
                            let lon = parseFloat(place.lon);

                            map.setView([lat, lon], 10);
                            marker.setLatLng([lat, lon]);

                            // update textbox with formatted name (optional)
                            document.getElementById(inputId).value = place.display_name;
                        } else {
                            alert("No results found for: " + query);
                        }
                    })
                    .catch(err => console.error("Search failed:", err));
            }

            document.getElementById("from_location").addEventListener("change", function() {
                searchLocation(this.value, mapFrom, markerFrom, "from_location");
            });

            document.getElementById("to_location").addEventListener("change", function() {
                searchLocation(this.value, mapTo, markerTo, "to_location");
            });


            // update input on marker dragged end
            function updateInput(marker, inputId) {
                var pos = marker.getLatLng();
                reverseGeocode(pos.lat, pos.lng, inputId);
            }

            // set marker dropped data to variables: from_location & to_location
            markerFrom.on('dragend', function() { updateInput(markerFrom, 'from_location'); });
            markerTo.on('dragend', function() { updateInput(markerTo, 'to_location'); });

            // if browser allows location permission, set starting position to user location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                mapFrom.setView([lat, lng], 10);
                markerFrom.setLatLng([lat, lng]);
                updateInput(markerFrom, 'from_location');
                });
            }

            updateInput(markerFrom, 'from_location');
            updateInput(markerTo, 'to_location');
        });     
    </script>


    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>