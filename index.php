<?php
// index.php
// Main page: Interactive map with search, directions, markers. Loads favorites if logged in.
// Uses Leaflet.js via CDN (essential for map; no local files). Inline JS/CSS.

include 'db.php';

$user_id = $_SESSION['user_id'] ?? null;
$favorites = [];
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $favorites = $stmt->fetchAll();
}

// Handle AJAX save favorite (via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_favorite' && $user_id) {
    $lat = (float)$_POST['lat'];
    $lng = (float)$_POST['lng'];
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($lat && $lng && $name) {
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, lat, lng, name, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $lat, $lng, $name, $address]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
    }
    exit;
}

// If not logged in and trying to access, redirect to login via JS
if (!$user_id) {
    // For guest, allow but no save
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MapClone - Interactive Map</title>
    <!-- Leaflet CSS CDN (essential, no local file) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        /* Internal CSS: Premium Google Maps clone style. Clean, intuitive UI with blues, shadows, rounded corners. Responsive, smooth animations. */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Roboto', sans-serif; background: #f8f9fa; height: 100vh; overflow: hidden; }
        #map { height: 100vh; width: 100%; position: relative; z-index: 1; }
        .header { position: absolute; top: 0; left: 0; right: 0; z-index: 1000; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); padding: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 1rem; }
        .logo { font-size: 1.5rem; font-weight: 400; color: #1a73e8; }
        .search-bar { flex: 1; position: relative; }
        .search-input { width: 100%; padding: 0.75rem 3rem 0.75rem 1rem; border: 1px solid #dadce0; border-radius: 24px; font-size: 1rem; background: white; transition: all 0.3s ease; }
        .search-input:focus { outline: none; border-color: #1a73e8; box-shadow: 0 0 0 2px rgba(26,115,232,0.2); }
        .search-btn { position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #5f6368; }
        .controls { display: flex; gap: 0.5rem; }
        .btn { padding: 0.5rem 1rem; background: #1a73e8; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.875rem; transition: background 0.3s ease; }
        .btn:hover { background: #1557b0; }
        .btn-secondary { background: #f1f3f4; color: #3c4043; }
        .btn-secondary:hover { background: #e8eaed; }
        .sidebar { position: absolute; right: 0; top: 0; bottom: 0; width: 300px; background: rgba(255,255,255,0.95); backdrop-filter: blur(20px); z-index: 999; transform: translateX(100%); transition: transform 0.3s ease; overflow-y: auto; }
        .sidebar.open { transform: translateX(0); }
        .sidebar-header { padding: 1rem; border-bottom: 1px solid #dadce0; display: flex; justify-content: space-between; align-items: center; }
        .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #5f6368; }
        .directions-form { padding: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.875rem; color: #3c4043; margin-bottom: 0.25rem; }
        .form-group input { width: 100%; padding: 0.5rem; border: 1px solid #dadce0; border-radius: 4px; }
        .favorites { padding: 1rem; border-top: 1px solid #dadce0; }
        .favorite-item { padding: 0.75rem; border-bottom: 1px solid #f1f3f4; cursor: pointer; transition: background 0.3s ease; }
        .favorite-item:hover { background: #f8f9fa; }
        .favorite-item:last-child { border-bottom: none; }
        .marker-popup { max-width: 200px; }
        .marker-popup button { margin-top: 0.5rem; width: 100%; }
        .logout { position: absolute; top: 1rem; right: 1rem; z-index: 1001; }
        .logout .btn { padding: 0.5rem; font-size: 0.875rem; }
        @media (max-width: 768px) { .header { flex-direction: column; gap: 0.5rem; padding: 0.5rem; } .search-input { padding-right: 2.5rem; } .sidebar { width: 100%; } }
    </style>
</head>
<body>
    <div id="map"></div>
    
    <div class="header">
        <div class="logo">🗺️ MapClone</div>
        <div class="search-bar">
            <input type="text" id="searchInput" class="search-input" placeholder="Search for places...">
            <button class="search-btn" onclick="performSearch()">🔍</button>
        </div>
        <div class="controls">
            <button class="btn btn-secondary" onclick="toggleSidebar()">Directions</button>
            <button class="btn" onclick="addMarker()">📍 Drop Pin</button>
        </div>
        <?php if ($user_id): ?>
        <div class="logout">
            <button class="btn btn-secondary" onclick="logout()">Logout</button>
        </div>
        <?php else: ?>
        <div class="controls">
            <button class="btn" onclick="window.location.href='login.php'">Login to Save</button>
        </div>
        <?php endif; ?>
    </div>
    
    <div id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <h3>Directions & Favorites</h3>
            <button class="close-btn" onclick="toggleSidebar()">&times;</button>
        </div>
        <div class="directions-form">
            <div class="form-group">
                <label>Start</label>
                <input type="text" id="startLoc" placeholder="Enter starting point">
            </div>
            <div class="form-group">
                <label>End</label>
                <input type="text" id="endLoc" placeholder="Enter destination">
            </div>
            <button class="btn" onclick="getDirections()">Get Directions</button>
        </div>
        <?php if ($user_id): ?>
        <div class="favorites">
            <h4>Saved Favorites</h4>
            <div id="favoritesList">
                <?php foreach ($favorites as $fav): ?>
                <div class="favorite-item" onclick="goToLocation(<?php echo $fav['lat']; ?>, <?php echo $fav['lng']; ?>)">
                    <strong><?php echo htmlspecialchars($fav['name']); ?></strong><br>
                    <?php echo htmlspecialchars($fav['address']); ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Leaflet JS CDN (essential) -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        // Inline JS: Handles map init, search (Nominatim), directions (OSRM), markers, AJAX save, navigation.
        let map, currentMarkers = [], routeLayer, userId = <?php echo $user_id ?: 'null'; ?>;
        
        // Initialize map with OpenStreetMap tiles
        function initMap() {
            map = L.map('map').setView([51.505, -0.09], 13);  // Default London view
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // Click to add marker
            map.on('click', function(e) {
                if (confirm('Drop a pin here?')) addMarker(e.latlng);
            });
            
            // Load favorites markers if logged in
            <?php if ($user_id): ?>
            <?php foreach ($favorites as $fav): ?>
            addMarker(L.latLng(<?php echo $fav['lat']; ?>, <?php echo $fav['lng']; ?>), '<?php echo addslashes($fav['name']); ?>', '<?php echo addslashes($fav['address']); ?>');
            <?php endforeach; ?>
            <?php endif; ?>
        }
        
        // Perform location search using Nominatim
        function performSearch() {
            const query = document.getElementById('searchInput').value;
            if (!query) return;
            
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
                .then(res => res.json())
                .then(data => {
                    if (data[0]) {
                        const lat = parseFloat(data[0].lat), lng = parseFloat(data[0].lon);
                        map.setView([lat, lng], 13);
                        addMarker([lat, lng], data[0].display_name);
                    } else {
                        alert('Location not found.');
                    }
                })
                .catch(err => alert('Search error: ' + err));
        }
        
        // Add marker with popup (save option if logged in)
        function addMarker(latlng, name = 'New Pin', address = '') {
            const marker = L.marker(latlng).addTo(map);
            currentMarkers.push(marker);
            const popupContent = `
                <div class="marker-popup">
                    <strong>${name || 'Unnamed Pin'}</strong><br>
                    ${address}<br>
                    <button class="btn btn-secondary" onclick="saveFavorite(${latlng.lat}, ${latlng.lng}, '${name.replace(/'/g, "\\'")}', '${address.replace(/'/g, "\\'")}')">Save Favorite</button>
                </div>
            `;
            marker.bindPopup(popupContent).openPopup();
        }
        
        // Save favorite via AJAX to PHP
        function saveFavorite(lat, lng, name, address) {
            if (!userId) { alert('Login to save!'); return; }
            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=save_favorite&lat=${lat}&lng=${lng}&name=${encodeURIComponent(name)}&address=${encodeURIComponent(address)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Saved! Refresh to see in list.');
                    location.reload();  // Simple reload to update list
                } else {
                    alert('Save error: ' + (data.error || 'Unknown'));
                }
            });
        }
        
        // Get directions using OSRM
        function getDirections() {
            const start = document.getElementById('startLoc').value;
            const end = document.getElementById('endLoc').value;
            if (!start || !end) { alert('Enter start and end locations.'); return; }
            
            // Geocode start and end
            Promise.all([
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(start)}&limit=1`).then(r => r.json()),
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(end)}&limit=1`).then(r => r.json())
            ]).then(([startData, endData]) => {
                if (!startData[0] || !endData[0]) { alert('One or both locations not found.'); return; }
                const startCoords = `${startData[0].lon},${startData[0].lat}`;
                const endCoords = `${endData[0].lon},${endData[0].lat}`;
                
                fetch(`https://router.project-osrm.org/route/v1/driving/${startCoords};${endCoords}?overview=full&alternatives=false&steps=true&geometries=geojson`)
                    .then(r => r.json())
                    .then(data => {
                        if (routeLayer) map.removeLayer(routeLayer);
                        routeLayer = L.geoJSON(data.routes[0]).addTo(map);
                        map.fitBounds(routeLayer.getBounds());
                        
                        // Add start/end markers
                        L.marker([startData[0].lat, startData[0].lon]).addTo(map).bindPopup('Start');
                        L.marker([endData[0].lat, endData[0].lon]).addTo(map).bindPopup('End');
                        
                        // Display steps in sidebar (simple)
                        const steps = data.routes[0].legs[0].steps.map(s => s.maneuver.instruction).join('<br>');
                        document.querySelector('.directions-form').innerHTML += `<div style="margin-top:1rem;padding:1rem;background:#f0f0f0;border-radius:4px;"><strong>Directions:</strong><br>${steps}</div>`;
                    })
                    .catch(err => alert('Directions error: ' + err));
            });
        }
        
        // Go to location from favorites
        function goToLocation(lat, lng) {
            map.setView([lat, lng], 13);
            toggleSidebar();
        }
        
        // Toggle sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
        
        // Logout
        function logout() {
            fetch('index.php', { method: 'POST', body: 'action=logout' });  // Optional PHP handler
            window.location.href = 'login.php';
        }
        
        // Enter to search
        document.getElementById('searchInput').addEventListener('keypress', e => { if (e.key === 'Enter') performSearch(); });
        
        // Init on load
        window.onload = initMap;
    </script>
</body>
</html>
