# Texture Packs
## Vanilla Resource Packs
PocketMap contains the resource pack of the latest (major) Minecraft Bedrock update, to make sure that all (new) textures can be used for generating the images. <br>
The vanilla resource packs used in PocketMap are retrieved from the [official bedrock-samples repository](https://github.com/Mojang/bedrock-samples).
### Latest Version
**v1.20.0.1**


## Custom Resource Packs - _Not Supported_
Custom resource packs are not yet supported, but this will be implemented in a future release of PocketMap. <br>

### But I want my custom resource pack now!
It is possible to use your custom resource pack, but it is **NOT** supported nor recommended, because this can break your map or (in the worst case) the plugin which causes your server to crash.<br>
If you really want to do it, you have to put your resource pack in the PocketMap `resource_packs` folder and rename your own resource pack to the latest vanilla pack version that is inside that folder.

### Help, I was stupid and used my own resource pack and now everything is broken!
Lucky for you, this is an easy fix!
1. Stop your server.
2. Remove **your own resource pack** that was named to the **latest vanilla resource pack version**.
3. Remove the `renders` folder inside the PocketMap plugin data
4. Start your server.
   - During the start of the server, all worlds will be rendered at this startup so the server will lag or be unplayable until everything is rendered.