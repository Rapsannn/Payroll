<div>
    <div class="container mx-auto max-w-sm">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="grid grid-cols-1 gap-6 mb-6">
                <div>
                    <h2 class="text-2xl font-bold mb-2">Informasi Pegawai</h2>
                    <div class="bg-gray-100 p-4 rounded-lg">
                        <p><strong>Nama Pegawai: </strong> {{ $schedule->user->name }}</p>
                        <p><strong>Kantor: </strong> {{ $schedule->office->name }}</p>
                        <p><strong>Shift: </strong> {{ $schedule->shift->name }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                        <div class="bg-gray-200 p-4 rounded-lg">
                            <h4>Jam Masuk</h4>
                            <p>09:00</p>
                        </div>
                        <div class="bg-gray-200 p-4 rounded-lg">
                            <h4>Jam Keluar</h4>
                            <p>17:00</p>
                        </div>
                    </div>
                </div>
 
                <div>
                    <h2 class="text-2xl font-bold mb-2">Presensi</h2>
                    <div id="map" class="mb-4 border border-gray-300 rounded" wire:ignore></div>
                    <button type="button" onclick="tagLocation()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 hover:cursor-pointer">Tag Location</button>
                    @if ($insideRadius)    
                    <button type="button" onclick="tagLocation()" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-700 hover:cursor-pointer">Submit Presensi</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let marker;
    let map;
    let lat;
    let lng;
    let component;
    let office = [{{ $schedule->office->latitude }}, {{ $schedule->office->longitude }}];
    let radius = {{ $schedule->office->radius }};

    document.addEventListener('livewire:initialized', function() {
        component = @this;

        map = L.map('map').setView(office, 17);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
    
        var circle = L.circle(office, {
            color: 'red',
            fillColor: '#f03',
            fillOpacity: 0.5,
            radius: radius
        }).addTo(map);
    });


    function tagLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                lat = position.coords.latitude;
                lng = position.coords.longitude;

                if (marker) {
                    map.removeLayer(marker);
                }

                marker = L.marker([lat, lng]).addTo(map);
                map.setView([lat, lng], 18);

                if (isWithinRadius(lat, lng, office, radius)) {
                    component.set('insideRadius', true);
                } else {
                    alert('Presensi Gagal Anda Tidak Berada Di Dalam Radius Kantor!');
                }
            });
        } else {
            alert('Tidak Bisa Tag Location!');
        }
    }

    function isWithinRadius(lat, lng, center, radius) {
        let distance = map.distance([lat, lng], center);
        return distance <= radius;
    }
</script>