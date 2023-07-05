const map = L.map("map", {
    crs: L.CRS.Simple,
    attributionControl: false,
});

// set the map bounds to the lowest and highest possible values
const bounds = [[Number.MIN_SAFE_INTEGER,Number.MIN_SAFE_INTEGER], [Number.MAX_SAFE_INTEGER,Number.MAX_SAFE_INTEGER]];
map.fitBounds(bounds);

const mapLayer = L.tileLayer("/api/pocketmap/render/world/{z}/{x},{y}.png", {
    minZoom: -4,
    maxZoom: 4,
    attribution: "&copy; 2023 Hebbinkpro",
});

mapLayer.addTo(map);

L.control.attribution({
    position: "bottomright",
    prefix: "PocketMap PMMP",
}).addTo(map);

const leafletTopLeft = document.getElementsByClassName("leaflet-top leaflet-left")[0];

const coordinates = document.createElement("div");
coordinates.classList.add("pocketmap-coords", "leaflet-control", "leaflet-bar")
coordinates.innerHTML = `<span>
        x: <span id="pocketmap-pos-x">0</span>
        y: <span id="pocketmap-pos-y">64</span>
        z: <span id="pocketmap-pos-z">0</span>
    </span>`;

leafletTopLeft.appendChild(coordinates);

const mousePosElements = {
    x: document.getElementById("pocketmap-pos-x"),
    y: document.getElementById("pocketmap-pos-y"),
    z: document.getElementById("pocketmap-pos-z")
}


map.addEventListener("mousemove", (e) => {
    let x = Math.ceil(e.latlng.lng);
    let z = Math.ceil(e.latlng.lat);

    mousePosElements.x.innerText = `${x}`;
    mousePosElements.z.innerText = `${-z}`;
});
