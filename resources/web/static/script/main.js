/*
 *   _____           _        _   __  __
 *  |  __ \         | |      | | |  \/  |
 *  | |__) |__   ___| | _____| |_| \  / | __ _ _ __
 *  |  ___/ _ \ / __| |/ / _ \ __| |\/| |/ _` | '_ \
 *  | |  | (_) | (__|   <  __/ |_| |  | | (_| | |_) |
 *  |_|   \___/ \___|_|\_\___|\__|_|  |_|\__,_| .__/
 *                                            | |
 *                                            |_|
 *
 * Copyright (c) 2024 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

const API_URL = "/api/pocketmap/";
const ICON_CACHE = [];
const MARKER_CACHE = [];
const bounds = [[Number.MIN_SAFE_INTEGER, Number.MIN_SAFE_INTEGER], [Number.MAX_SAFE_INTEGER, Number.MAX_SAFE_INTEGER]];
let urlQuery, world, worldConfig, map;

window.addEventListener("load", async () => {
    let config = await getConfig();

    urlQuery = new URLSearchParams(window.location.search);
    let worlds = await getWorlds();


    world = urlQuery.get("world");
    if (world === null || !worlds[world]) {
        world = config["default-world"];
    }

    worldConfig = config["worlds"][world] ?? config["default-settings"];

    let mapPos = {
        x: worldConfig.view[0] / 16,
        z: -worldConfig.view[1] / 16,
        zoom: worldConfig.view[2],
    }

    if (urlQuery.get("x") !== null) mapPos.x = parseFloat(urlQuery.get("x")) / 16;
    if (urlQuery.get("z") !== null) mapPos.z = -parseFloat(urlQuery.get("z")) / 16;
    if (urlQuery.get("zoom") !== null) mapPos.zoom = parseInt(urlQuery.get("zoom"));

    let mapLayer = L.tileLayer(API_URL + `render/${world}/{z}/{x},{y}.png`, {
        minZoom: worldConfig.zoom[0],
        maxZoom: worldConfig.zoom[1],
        zoomReverse: true,
        attribution: "&copy; 2024 Hebbinkpro",
    });

    map = L.map("map", {
        crs: L.CRS.Simple,
        attributionControl: false
    });

    map.fitBounds(bounds);
    mapLayer.addTo(map);

    map.setView(L.latLng(mapPos.z, mapPos.x), mapPos.zoom);

    L.control.attribution({
        position: "bottomright",
        prefix: "PocketMap PMMP",
    }).addTo(map);

    createElements();
    loadMarkers().then(() => {
        console.log("The markers are loaded")
    });

    let mousePosElements = {
        x: document.getElementById("pocketmap-pos-x"),
        y: document.getElementById("pocketmap-pos-y"),
        z: document.getElementById("pocketmap-pos-z")
    }

    map.addEventListener("mousemove", (e) => {
        let x = Math.floor(e.latlng.lng * 16);
        let z = -Math.ceil(e.latlng.lat * 16);

        mousePosElements.x.innerText = `${x}`;
        mousePosElements.z.innerText = `${z}`;
    });

    map.addEventListener("click", (e) => {
        let x = Math.floor(e.latlng.lng * 16);
        let z = -Math.ceil(e.latlng.lat * 16);

        urlQuery.set("world", world);
        urlQuery.set("x", x.toString());
        urlQuery.set("z", z.toString());
        urlQuery.set("zoom", map.getZoom())
        window.history.pushState({path: "?" + urlQuery.toString()}, "", "?" + urlQuery.toString())
    })


    update(1000).then(() => {
    });
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
            worldEl.innerText = world["name"];
            worldEl.setAttribute("role", "button")

            worldEl.addEventListener("click", (event) => {
                window.location.assign("?world=" + world["name"]);
                event.preventDefault();
            });

            worldsDiv.appendChild(worldEl);
        }
    })


    let leafletTopLeft = document.getElementsByClassName("leaflet-top leaflet-left")[0];
    leafletTopLeft.appendChild(coordinates);

    let leafletTopRight = document.getElementsByClassName("leaflet-top leaflet-right")[0];
    leafletTopRight.appendChild(worldsDiv);
}


async function update(updateTime) {

    // get all online players
    let players = await getOnlinePlayers(world);

    // add markers for all online players
    for (let i in players) {
        let player = players[i]
        updatePlayerMarker(player, map);
    }

    for (let uuid in MARKER_CACHE) {
        // player with this uuid is online
        if (players.hasOwnProperty(uuid)) continue;

        // remove the marker from the map
        map.removeLayer(MARKER_CACHE[uuid]);
        delete MARKER_CACHE[uuid];
    }

    setTimeout(() => update(updateTime), updateTime)
}

function updatePlayerMarker(player) {
    let latLng = getLatLngPos(player["pos"]);

    if (MARKER_CACHE[player["uuid"]]) {
        MARKER_CACHE[player["uuid"]].setLatLng(latLng);
    } else {
        let head = getPlayerHead(player);
        let marker = L.marker(latLng, {icon: head});
        marker.bindTooltip(`${player["name"]}<br>${getCoordString(player)}`, {
            permanent: false,
            direction: "right"
        });

        MARKER_CACHE[player["uuid"]] = marker;
        map.addLayer(marker);
    }
}

async function getOnlinePlayers() {
    let req = await fetch(API_URL + "players");
    return (await req.json())[world] ?? [];
}

async function getWorlds() {
    let req = await fetch(API_URL + "worlds");
    return (await req.json());
}

async function getConfig() {
    let req = await fetch(API_URL + "config");
    return (await req.json());
}

function getPlayerHead(player) {
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

function getLatLngPos(pos, offsetX = 0, offsetZ = 0) {
    return L.latLng(-(pos.z + offsetZ) / 16, (pos.x + offsetX) / 16);
}


function getCoordString(player) {
    let pos = player["pos"];
    return `${pos["x"]}, ${pos["y"] ?? "64"}, ${pos["z"]}`;
}

async function loadMarkers() {
    let icons = await getMarkerIcons();
    let worldData = (await getWorlds())[world];

    let spawnMarker = worldConfig["spawnMarker"];
    if (spawnMarker !== "") {
        let worldSpawnMarker = L.marker(getLatLngPos(worldData["spawn"], 0.5, 0.5), {icon: icons[spawnMarker]})
        worldSpawnMarker.bindPopup("World Spawn")
        worldSpawnMarker.addTo(map)
    }

    let req = await fetch(API_URL + "markers");
    let markers = (await req.json())[world];
    if (!markers) return;


    // load the other markers
    for (let i in markers) {
        let marker = markers[i];

        let m = null;
        let positions = [];
        let data = marker["data"];
        switch (data["type"]) {
            case "icon":
                m = L.marker(getLatLngPos(data["pos"], 0.5, 0.5), {icon: icons[data["icon"]]})
                break;
            case "circle":
                // convert radius from blocks to lat lng
                data["options"]["radius"] /= 16;
                m = L.circle(getLatLngPos(data["pos"], 0.5, 0.5), data["options"]);
                break;
            case "polygon":
                positions = [];
                for (let i in data["positions"]) {
                    // dont apply offset to squares
                    positions.push(getLatLngPos(data["positions"][i]));
                }
                m = L.polygon(positions, data["options"])
                break;
            case "polyline":
                positions = [];
                for (let i in data["positions"]) {
                    positions.push(getLatLngPos(data["positions"][i], 0.5, 0.5));
                }
                m = L.polyline(positions, data["options"]);
                break;
            case "custom":
                let customType = data["custom_type"];
                // TODO implement custom marker types
                break;
        }

        if (m != null) {
            m.bindPopup(marker.name)
            m.addTo(map)
        }
    }

}

async function getIcons() {
    let req = await fetch(API_URL + "markers/icons");
    return (await req.json());
}

async function getMarkerIcons() {
    let icons = await getIcons() ?? []

    let iconSize = 32;
    let MarkerIcon = L.Icon.extend({
        options: {
            iconSize: [iconSize, iconSize],
            iconAnchor: [iconSize / 2, iconSize / 2],
            popupAnchor: [0, 0]
        }
    })

    let markerIcons = {}
    for (let i in icons) {
        let iconData = icons[i]
        if (!iconData.name) continue;

        let name = iconData.name;

        let iconUrl;
        if (iconData.path) {
            if (!iconData.path.includes(".")) iconData.path += ".png";
            iconUrl = API_URL + "markers/icons/" + iconData.path;
        } else if (iconData.url) iconUrl = iconData.url;
        else continue;

        markerIcons[name] = new MarkerIcon({iconUrl})
    }

    return markerIcons;
}