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
 * Copyright (C) 2023 Hebbinkpro
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
let urlQuery, world, map;

window.addEventListener("load", async () => {
    let config = await getConfig();

    urlQuery = new URLSearchParams(window.location.search);
    let worlds = await getWorlds();


    world = urlQuery.get("world");
    if (world === null || !worlds[world]) {
        world = config["default-world"];
    }

    let worldConfig = config["worlds"][world];

    let mapPos = {
        x: worldConfig.view.x / 16,
        z: -worldConfig.view.z / 16,
        zoom: worldConfig.view.zoom,
    }

    if (urlQuery.get("x") !== null) mapPos.x = parseFloat(urlQuery.get("x")) / 16;
    if (urlQuery.get("z") !== null) mapPos.z = -parseFloat(urlQuery.get("z")) / 16;
    if (urlQuery.get("zoom") !== null) mapPos.zoom = parseInt(urlQuery.get("zoom"));

    let mapLayer = L.tileLayer(API_URL + `render/${world}/{z}/{x},{y}.png`, {
        minZoom: worldConfig.zoom.min,
        maxZoom: worldConfig.zoom.max,
        zoomReverse: true,
        attribution: "&copy; 2023 Hebbinkpro",
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

    let mousePosElements = {
        x: document.getElementById("pocketmap-pos-x"),
        y: document.getElementById("pocketmap-pos-y"),
        z: document.getElementById("pocketmap-pos-z")
    }

    map.addEventListener("mousemove", (e) => {
        let x = Math.ceil(e.latlng.lng * 16);
        let z = -Math.ceil(e.latlng.lat * 16);

        mousePosElements.x.innerText = `${x}`;
        mousePosElements.z.innerText = `${z}`;
    });

    map.addEventListener("click", (e) => {
        let x = Math.ceil(e.latlng.lng * 16);
        let z = -Math.ceil(e.latlng.lat * 16);

        urlQuery.set("world", world);
        urlQuery.set("x", x.toString());
        urlQuery.set("z", z.toString());
        urlQuery.set("zoom", map.getZoom())
        window.history.pushState({path: "?" + urlQuery.toString()}, "", "?" + urlQuery.toString())
    })


    update(1000, world, map).then(() => {
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
        updateMarker(player, map);
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

function updateMarker(player) {
    let pos = player["pos"];
    let latLng = L.latLng(-pos.z / 16, pos.x / 16);

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