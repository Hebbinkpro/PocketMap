# PocketMap Block Entity Fix - Resource Pack

## About Block Entity Fix

This resource pack is created to fix the mappings of block entities, such as signs and beds, which previously mapped to
incorrect block textures.

This resource pack is created for internal usage only. However, if you have created your own block entities, you can
register them here so that PocketMap can render them correctly.

This resource pack does NOT contain textures, only mappings from the blocks to their entity textures.

## Contents

- `blocks.json` - File containing the name of all blocks and their terrain texture name.
- `textures/terrain_texture.json` - File containing the mapping of the terrain texture name to the texture paths within
  a resource pack.

## How does it work?

When PocketMap is loading a resource pack, the `blocks` and `terrain_texture` files of that resource pack will be loaded
first.
These files contain the textures of the blocks that PocketMap should use. However, sometimes these files contain
mappings to textures which do not correspond to the textures that Minecraft uses, since the block is actually an Entity.
The correct textures of these blocks are placed in the `textures/entity/` folder instead of `textures/blocks/`, and this
resource pack overwrites these `textures/blocks/` paths with the correct `textures/entity/` paths.