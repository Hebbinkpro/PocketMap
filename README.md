# PocketMap

A dynamic web-based world map for PocketMine-MP servers.

## How to install
1. Download the newest version of the plugin from [Poggit CI](https://poggit.pmmp.io/ci/Hebbinkpro/PocketMap)
2. Place the plugin in the plugins folder of your PocketMine-MP server
3. Restart your server
4. Go to `http://<server_ip>:3000` to see the map and load chunks to render new parts of the map.

## How does it work
- When the plugin loads for the first time, each world will get a complete render (this takes a while and can cause an unplayable server for a while!!!)
- A render reads all the highest blocks inside a chunk and retrieves its texture from the resource pack. 
- The blocks will be merged together in a file that contains one or more chunks. 
- For a better performance of the front-end, there are different zoom-levels, -4 until 4.
  - 4 represents 1 chunk per region and each block has a resolution of 16x16 pixels.
  - -4 represents 256x256 chunks with each chunk is 1 pixel.
- If there are more chunks inside a region, each block will take up less pixels so that each render has a size of 256x256 pixels.
- Leaflet.js uses the different zoom-levels so that you can zoom in and out on the map without any issue.

## Issues
If you encounter issues with the web server, please report them [here](https://github.com/Hebbinkpro/pmmp-webserver/issues, for any other issues with this plugin please report them [here](https://github.com/Hebbinkpro/pocketmap/issues)!

## TO DO
This is a list of all things that have to be added
### Configuration
- Add settings for rendering.
- Add settings for resource packs.

### WebPage
- Display online users (head and name)
- Multiple worlds support

### Renderer
- Make full world renders more efficient
- Render not full blocks (torches, fences, etc)
- Render opaque block under transparent blocks (leaves, glass, etc)
- Display blocks under the water surface (Block has the water texture overtop)
- Water diffusion (Visibility of blocks underwater depends on their depth)

### Resource packs
- Functionality to add and use custom resource packs for the renders.

## Credits
- The web server is created using my [WebServer](https://github.com/Hebbinkpro/pmmp-webserver) virion .
- The rendered textures are from the official [bedrock-samples](https://github.com/Mojang/bedrock-samples) resource pack.
- The dynamic map is created using [leaflet.js](https://leafletjs.com/)