const API_URL = "/api/pocketmap/";
const ICON_CACHE = [];
const MARKER_CACHE = [];

const map = L.map("map", {
    crs: L.CRS.Simple,
    attributionControl: false,
});

// set the map bounds to the lowest and highest possible values
const bounds = [[Number.MIN_SAFE_INTEGER, Number.MIN_SAFE_INTEGER], [Number.MAX_SAFE_INTEGER, Number.MAX_SAFE_INTEGER]];
map.fitBounds(bounds);

const mapLayer = L.tileLayer(API_URL+"render/world/{z}/{x},{y}.png", {
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

// time in ms when the map should be updated
let updateTime = 1000;
update(updateTime)
async function update(updateTime) {

    // get all online players
    let players = await getOnlinePlayers();

    // add markers for all online players
    for(let i in players) {
        let player = players[i];
        updateMarker(player);
    }

    setTimeout(() => update(updateTime), updateTime)
}

function updateMarker(player) {
    let pos = player["pos"];
    console.log(pos)
    let latLng = L.latLng(-pos.z, pos.x);

    if (MARKER_CACHE[player["uuid"]]) {
        MARKER_CACHE[player["uuid"]].setLatLng(latLng);
    } else {
        let icon = getIcon(player);
        console.log(pos)
        let marker = L.marker(latLng, {icon})

        MARKER_CACHE[player["uuid"]] = marker;
        map.addLayer(marker);
    }
}

async function getOnlinePlayers() {
    let req = await fetch(API_URL+"players");
    return (await req.json())["world"] ?? [];
}

function getIcon(player) {
    let skinId = player["skin"];
    let icon = ICON_CACHE[skinId] ?? null;


    if (icon !== null) return icon;

    let headUrl = API_URL+`players/skin/${player["skin"]}.png`;
    let skinSize = player["skinSize"];

    icon = L.icon({
        iconUrl: headUrl,
        iconSize: [skinSize, skinSize]
    })

    ICON_CACHE[skinId] = icon;

    return icon;
}