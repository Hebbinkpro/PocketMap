const API_URL = "/api/pocketmap/";
const ICON_CACHE = [];
const MARKER_CACHE = [];
const bounds = [[Number.MIN_SAFE_INTEGER, Number.MIN_SAFE_INTEGER], [Number.MAX_SAFE_INTEGER, Number.MAX_SAFE_INTEGER]];

window.addEventListener("load", () => {

    let urlQuery = new URLSearchParams(window.location.search);
    let world = urlQuery.get("world") ?? "world";

    let mapLayer = L.tileLayer(API_URL + `render/${world}/{z}/{x},{y}.png`, {
        minZoom: -4,
        maxZoom: 4,
        attribution: "&copy; 2023 Hebbinkpro",
    });

    let map = L.map("map", {
        crs: L.CRS.Simple,
        attributionControl: false,
    });

    map.fitBounds(bounds);
    mapLayer.addTo(map);

    L.control.attribution({
        position: "bottomright",
        prefix: "PocketMap PMMP",
    }).addTo(map);

     createElements();

    let mousePosElements = {
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


    update(1000, world, map).then(r => {});
});

function createElements() {
    let coordinates = document.createElement("div");
    coordinates.classList.add("pocketmap-coords", "leaflet-control", "leaflet-bar")
    coordinates.innerHTML =
    `<span>
        x: <span id="pocketmap-pos-x">0</span>
        y: <span id="pocketmap-pos-y">64</span>
        z: <span id="pocketmap-pos-z">0</span>
    </span>`;

    let worldsDiv = document.createElement("div");
    worldsDiv.classList.add("pocketmap-worlds", "leaflet-control", "leaflet-bar");

    getWorlds().then(worlds => {
        for (let i in worlds) {
            let world = worlds[i];
            let worldEl = document.createElement("a");
            worldEl.href = `?world=${world["name"]}`;
            worldEl.innerText = world["name"];
            worldEl.setAttribute("role", "button")

            worldsDiv.appendChild(worldEl);
        }
    })


    let leafletTopLeft = document.getElementsByClassName("leaflet-top leaflet-left")[0];
    leafletTopLeft.appendChild(coordinates);

    let leafletTopRight = document.getElementsByClassName("leaflet-top leaflet-right")[0];
    leafletTopRight.appendChild(worldsDiv);
}


async function update(updateTime, world, map) {

    // get all online players
    let players = await getOnlinePlayers(world);

    // add markers for all online players
    for (let i in players) {
        let player = players[i]
        updateMarker(player);
    }

    for (let uuid in MARKER_CACHE) {
        // player with this uuid is online
        if (players.hasOwnProperty(uuid)) continue;

        // remove the marker from the map
        map.removeLayer(MARKER_CACHE[uuid]);
        delete MARKER_CACHE[uuid];
    }

    setTimeout(() => update(updateTime, world, map), updateTime)
}

function updateMarker(player) {
    let pos = player["pos"];
    let latLng = L.latLng(-pos.z, pos.x);

    if (MARKER_CACHE[player["uuid"]]) {
        MARKER_CACHE[player["uuid"]].setLatLng(latLng);
    } else {
        let icon = getIcon(player);
        let marker = L.marker(latLng, {icon});
        marker.bindTooltip(`${player["name"]}<br>${getCoordString(player)}`, {
            permanent: false,
            direction: "right"
        });

        MARKER_CACHE[player["uuid"]] = marker;
        map.addLayer(marker);
    }
}

async function getOnlinePlayers(world) {
    let req = await fetch(API_URL + "players");
    return (await req.json())[world] ?? [];
}

async function getWorlds() {
    let req = await fetch(API_URL + "worlds");
    return (await req.json());
}

function getIcon(player) {
    let skinId = player["skin"]["id"];
    let icon = ICON_CACHE[skinId] ?? null;

    if (icon !== null) return icon;

    let headUrl = API_URL + `players/skin/${skinId}.png`;
    let skinSize = player["skin"]["size"];

    icon = L.icon({
        iconUrl: headUrl,
        iconSize: [skinSize, skinSize]
    })

    ICON_CACHE[skinId] = icon;

    return icon;
}

function getCoordString(player) {
    let pos = player["pos"];
    return `${pos["x"]}, ${pos["y"] ?? "64"}, ${pos["z"]}`;
}