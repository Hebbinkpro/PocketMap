# PocketMap

A dynamic web-based world map for PocketMine-MP servers.<br>
For a full overview of all functions of the plugin, go to the [documentation](#documentation)

## What is new in v0.5

- The `/pocketmap` (or `/pmap`) command is now available, the previous commands (`/render` and `/reload`) are now
  sub-commands!
    - For a list of all available commands, use: `/pmap help`
- Markers - Now you can mark spots on your map with icons using commands (`/pmap marker`).

Go to the [changelogs](https://github.com/Hebbinkpro/PocketMap/blob/main/changelogs/v0.5.md)

## How to install

1. Download the newest version of the plugin from [Poggit CI](https://poggit.pmmp.io/ci/Hebbinkpro/PocketMap)
2. Place the plugin in the plugins folder of your PocketMine-MP server
3. Restart your server, this will load the plugin data.
    - If you are not hosting your server locally, you have to allocate a new port to your server.

    1. Allocate a new port for your server. If you don't know how to do this, contact your hosting provider.
    2. Go to the `config.yml` and change `web-server.port` to your new allocated port (replace `3000`).
    3. Restart your server again.
4. Go to `http://<server_ip>:<web-server.port>` to see the map.

- The default webserver port is `3000`, so if you haven't changed it, use this port.

## How to use

### Render a full world

If you have already created a world, and it is not yet (completely) visible on PocketMap, do the following:

- Execute `/pmap render full <world>`
  This will start a full world render for the given world. It can take a while before the complete map is loaded, and if
  your server stops/restart during the full render you have to start the full render again.

### Render new chunks

When you load new chunks in your world which have not been added to the map, the chunks will automatically be
rendered.<br>
If there are still chunks missing, make a full render of the world by executing `/pmap render full <world>`

### Render updated chunks

When a player breaks or places blocks, the map will automatically be updated after some time.

### Change rendering speed

If the rendering speed is too slow for you, or your server can't handle it anymore, you can change some values in
the `config.yml` to resolve these issues.

- Go to the `config.yml`
- Look in the settings under `renderer`
- If you want to alter the rendering speed, change some values in `scheduler`
- If you want to alter the rendering speed of updated chunks, change values in `chunk-scheduler`

## Issues

Please report all issues you encounter [here](https://github.com/Hebbinkpro/PocketMap/issues).

## TO DO

This is a list of all things that have to be added before the release of v1.0.0

### WebPage

- List of all online players
- Option to hide map icons
    - markers, players, etc
- Cleaner UI
- Show time in the world

### Renderer

- Better visualization of height difference
- Block Lighting
- Test/add support for other dimensions

### API

- Support for plugins to add their own API's to PocketMap
    - Let a plugin register files to `/api/<plugin_name>`
- Support for JS modules to extend the functionality of the web page.
    - These modules can be used to access a plugin's API endpoint on the web server.
- Add better documentation for the `MarkerManager` class such that plugins are able to make use of the build in marker
  system.

## Credits

- The web server is created using my [WebServer](https://github.com/Hebbinkpro/pmmp-webserver) virion .
- The rendered textures are from the official [bedrock-samples](https://github.com/Mojang/bedrock-samples) resource
  pack.
- The dynamic map is created using [leaflet.js](https://leafletjs.com/)
- The player heads shown on the map are created using the [LibSkin](https://github.com/HimbeersaftLP/LibSkin) virion by
  HimbeersaftLP.

# Documentation

This is the documentation of a lot of the PocketMap features.

- [Commands](#commands)
- [Web Map](#web-map)
- [Textures](#textures)
- [Markers](#markers)

## Commands

### Command Arguments

Some commands also need arguments, the following argument types are provided in this readme:

- `< ... >`: required
- `{ ... }`: optional for players but required for console, current location of the player is used when not provided
- `[ ... ]`: optional, default values will be used

### Help Command

Get a list of all command.
Usage: `/pmap help`

### Marker Command

Usage: `/pmap marker`

#### Common command arguments

- `name`: Name of the marker, required.
- `world`: Name of the world the marker is in
- `id`: Custom identifier for a marker
- `position`: Position (`x y z`)
- `icon`: Name of the icon defined in `markers/icons.json`
    - PocketMap provides a large range of default icons which are designed by [OpenMoji](https://openmoji.org/) – the
      open-source emoji and icon project.
    - Look [here](resources/markers/README.md) for more info about adding your own markers.
- `pos1`: First position (`x y z`)
- `pos2`: Second position (`x y z`)

#### Add Markers Command

Add markers to you map

- Icon markers: `/pmap marker add icon <name> <icon> {position} {world} [id]`
    - Create an icon marker on the given position
- Circle markers: `/pmap marker add circle <name> <radius> {position} {world} [id]`
    - `radius`: The radius (in blocks) of the circle, required
    - Create a circle with the given radius at the given position
- Area Markers: `/pmap marker add area <name> <pos1> {pos2} {world} [id]`
    - Create a rectangular area with `pos1` and `pos2` as corners
- Line Markers: `/pmap marker add line <name> <pos1> {pos2} {world} [id]`
    - Create a line between `pos1` and `pos2`

#### Remove Markers Command

You can remove any marker with the marker remove command.
Usage: `/pmap marker remove <id> {world}`

- You can only remove markers which have there `id` defined in `markers/markers.json`.

### Reload Command

Reload some parts of the plugin data, this will apply most changes in the running plugin.

- Not all changes will be applied with this command, to make sure all changes are applied, a restart is recommanded.

Available parts:

- `config`: Reload the plugin config
- `data`: Reload the folder structure
- `web`: Reload the web config

Usage: `/pmap reload <part>`

### Render Command

You can render a specific area of the map by using the render command.<br>
Usage: `/pmap render {x} {z} {world} {zoom}`

- `x`: The x coordinate of the region
- `z`: The z coordinate of the region
- `world`: THe world name of the region
- `zoom`: The zoom level of the region in the interval [0,8], where `zoom` is an area of <code>2<sup>zoom</sup> * 2<sup>
  zoom</sup></code> chunks (0=1 chunk, 8=256*256 chunks)

#### Full Render Command

Start a full world render for the given world.<br>
Usage: `/pmap render full {world}`

#### Render Lookup Command

Lookup the chunk and region coordinates of a given world position.<br>
Usage: `/pmap render lookup {x y z} [zoom]`

- `x y z` is the position in the world
- `zoom` is the amount of zoom regions you want the region coordinates from.
    - Default: `0`
      Output:
    - Coords (x,z): the given x and z coordinates
    - Chunk (x,z): The chunk x,z coordinates where the given position is in.
    - Regions (zoom/x,z): A list of all zoom regions in the range [0,`zoom`]

## Web Map

On the webpage of PocketMap (`http://<server_ip>:<pocketmap_port>/`) you can view the real map. On the map you can do
the following:

- View the location of all online players
- View all world maps (Currently only the overworld is tested)
    - You can select the world you want to view using buttons on the page
- View all created markers at each world
- View coordinates you are currently hovering on
    - When you click on the map, the coordinate will be added in the URL which can be used to share the location with
      others.
- Zoom in and out of the map.
    - This is managed by different zoom levels for the world, the zoom levels are in the interval [0,8], default zoom
      level is 4.
      You can change some of these items in the `config.yml` or `web/config.json`

## Textures

PocketMap makes use of the vanilla resource pack provided
by [bedrock-samples](https://github.com/Mojang/bedrock-samples).
To make PocketMap more consistent with your server, all resource packs you use in your server are also used in
PocketMap.

- Encrypted packs are not yet implemented

## Rendering

To show the map on the webpage, the minecraft world has to be rendered as images first. This is a complex process, and
I'm constantly improving the rendering algorithm to make it faster and more efficient.<br>

### From chunk to image

When a chunk is being rendered, we loop through all x,z positions in the chunk and get the highest block at each of the
positions.<br>
For each block we get the block type name and the block state. With these values, the correct texture is inside
the `resource_packs/terrain_textures.json` is found and used as the texture in the map.<br>
Not all blocks are the same, and PocketMap tries to make all blocks appear like in the minecraft world, such as

- Apply the right color from the color map for grass and foliage (leaves, etc)
- Apply biome colors for water
- Apply models for blocks like fences, buttons, etc. such that they have the right position and size on the map.
- Apply height difference for blocks
    - For now, blocks with `y%2 = 1`, will appear a bit darker than blocks with `y%2 = 0`
- Apply water transparency for blocks that are underwater using the correct values per biome
- Opaque blocks are visible through transparent blocks

## Markers

There are different types of markers you can use in PocketMap. You can add three different types of markers using
commands, but it is also possible to add markers by hand.
The markers that are visible on the web map are all defined in the file `markers/markers.json`. It is also possible to
add markers by hand.

### Marker types

- `icon`: An icon displayed at a given position
- `circle`: A circle with its center at the given position and a given radius in blocks
- `polygon`: An area with the given corners, at least 3 corners need to be provided.
- `polyline`: A line with multiple points, lines will be drawn between one point and the next.

### Command Markers

You can add some markers using commands. Please go to the [marker command documentation](#marker-command)  for more
details.

### Plugin Markers

It is possible for plugins to make use of PocketMaps built-in marker system.

1. Add PocketMap to the `softdepend` list in your `plugin.yml`. (Don't use the `depend` list, as this requires all users
   to use PocketMap otherwise they can't use your plugin).<br>

```yml
softdepend: [
  ..., # Other plugins
  PocketMap
]
```

2. Include `use Hebbinkpro\PocketMap\PocketMap;` where you want to use the add markers.
3. Add your markers

```php
if (class_exists(PocketMap::class)) {
    $markers = PocketMap::getMarkers(); 
}
```

- If you don't include PocketMap in the (soft)depend list, you cannot make use of the `MarkerManager` in the
  plugin's `onEnable` function.
- You cannot use the `MarkerManager` in the plugin's `onLoad` function.

#### Available functions

```php
$markers->getIcons(): array
$markers->getMarker(string $id, World $world): ?array
$markers->removeMarker(string $id, World $world): bool

$markers->addIconMarker(string $name, Position $pos, string $icon, ?string $id = null): bool
$markers->addCircleMarker(string $name, Position $pos, int $radius, ?string $id = null, string $color = "red", bool $fill = false, ?string $fillColor = null): bool
$markers->addPolygonMarker(string $name, array $positions, World $world, ?string $id = null, string $color = "red", bool $fill = false, ?string $fillColor = null): bool
$markers->addPolylineMarker(string $name, array $positions, World $world, ?string $id = null, string $color = "red", bool $fill = false, ?string $fillColor = null): bool
```

### Stored Markers

1. Open the file `markers/markers.json`
2. Go to the world your marker has to be displayed in
3. Add the following to the world markers:

```json5
"<id>": {// replace <id> with the custom identifier for the marker
"name": string, // the name of the marker
"data": {...}             // the data field of the marker
}
```

### Marker Type Data

These are all marker data types that are implemented for the front-end using Leaflet's marker system.

#### Common data fields

These are commonly used fields for the data types given below

##### Position

```json5
{
  "x": int
  |
  float,
  // the x coordinate in the world
  "z": int
  |
  float
  // the z coordinate in the world
}
```

##### Options

```json5
{
  "color": "red",
  // the border color
  "fill": false,
  // if the marker is filled
  "fillColor": "red"
  // the color of the filled area
}
```

#### Icon

```json5
{
  "type": "icon",
  // the data type
  "icon": string,
  // the name of the icon
  "pos": position
  // position of the marker
}
```

#### Circle

```json5
{
  "type": "circle",
  // the data type
  "options": {
    ...,
    // default options
    "radius": int
    |
    float
    // the radius (in blocks) of the circle
  },
  "pos": position
  // postion of the marker
}
```

#### Polygon

```json5
{
  "type": "polygon",
  // the data type
  "options": options,
  // options
  "positions": position[]     // list of all corner positions
}
```

##### Polyline

```json5
{
  "type": "polyline",
  // the data type
  "options": options,
  // options
  "positions": position[]     // list of all positions of the line
}
```





