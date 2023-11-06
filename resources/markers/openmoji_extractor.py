from json import JSONDecoder
from shutil import copy2

sourceFolder = "C:\\Users\\jorim\\Downloads\\openmoji-72x72-color\\"
destFolder = "icons\\openmoji"

iconsFile = open("icons.json")
icons = JSONDecoder().decode(iconsFile.read())
iconsFile.close()

iconsMap: dict[str, str] = dict()

for icon in icons:
    name: str = icon["name"]
    path: str = icon["path"]
    if not path.startswith("openmoji"):
        continue

    id = path.replace("openmoji/", "")

    print("Extracting: " + id + ": " + name)
    copy2(sourceFolder + id + ".png", destFolder)

    iconsMap[name] = id

columns = 10

markdownTable = open("icons_table.md", "w+")
markdownTable.write(("| " * columns) + "|\n")
markdownTable.write(("| ---  " * columns) + "|\n")

place = 0
line = ""
for name in iconsMap:
    id = iconsMap[name]
    
    line += "|" + name
    line += "<br>[<img alt='" + name + "' src='icons/openmoji/" + id + ".png'>](https://openmoji.org/library/emoji-" + id + "/)"
    
    place += 1
    if place >= columns:
        line += "|\n"
        markdownTable.write(line)
        place = 0
        line = ""

if place > 0:
    line += "| " * (columns - place) + "|\n"
    markdownTable.write(line)


markdownTable.close()