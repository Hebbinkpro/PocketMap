# Icons

Icons are essential for marking spots on your map, and because of this it is important that you can mark the location
with the icons you think fits best.
In PocketMap, there are 150+ default icons. These icons are free to use and are coming
from [OpenMoji.org](http://openmoji.org/), an organisation that provides free to use open source emoji's.

## Adding custom icons

If you cannot find the right icon in the default icons, you can also add your own. You can add emoji's by adding png
files to the `makers/icons` folder or by providing an url.

### Adding icons using pngs

1. Add your png icon to the `markers/icons` folder
2. Open the `markers/icons/icons.json` file. This file contains a list with all the available icons.

```json5
[
  {
    "name": "performing_arts",
    "path": "openmoji/1F3AD"
  },
  {
    "name": "framed_picture",
    "path": "openmoji/1F5BC"
  },
  // ...
]
```

3. Add your icon to the list by adding an object with the name, this is the UNIQUE identifier of the icon, and the path,
   the location INSIDE the `markers/icons` folder.
    - your file is located at: `markers/icons/myicon.png`, the path is `myicon`.
    - Your file is located at: `markers/icons/myicons/myicon.png`, the path is `myicons/myicon`

```json5
{
  "name": "<icon_name>",
  // the name of the icon by which it is identified
  "path": "<icon_path>"
  // the path INSIDE the icons folder
}
```

### Adding icons using urls

1. Open the `markers/icons.json` file. This file contains a list with all the available icons.
2. Add your icon to the list by adding an object with the name, this is the UNIQUE identifier of the icon, and the url
   of the icon.

```json5
{
  "name": "<icon_name>", // the name of the icon by which it is identified
  "url": "<url>" // url of the image
}
```

## Default icons

You can find all the default icons [here](icons_table.md)

### Custom Icons

There are no custom icons, but maybe in the future.