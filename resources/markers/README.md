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
2. Open the `markers/icons.json` file. This file contains a list with all the available icons.

```json
[
  {
    "name": "performing_arts",
    "path": "openmoji/1F3AD"
  },
  {
    "name": "framed_picture",
    "path": "openmoji/1F5BC"
  },
  ...
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

```json
{
  "name": "<icon_name>",
  // the name of the icon by which it is identified
  "url": "<url>"
  // url of the image
}
```

## Default icons

### OpenMoji Icons

> All icons are designed by [OpenMoji](https://openmoji.org/) – the open-source emoji and icon project.
> License: [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0/#)

|                                                                                                                            |                                                                                                                          |                                                                                                                      |                                                                                                                          |                                                                                                                              |                                                                                                                        |                                                                                                                          |                                                                                                                      |                                                                                                                          |                                                                                                                              |
|----------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------|
| performing_arts<br>[<img alt='performing_arts' src='icons/openmoji/1F3AD.png'>](https://openmoji.org/library/emoji-1F3AD/) | artist_palette<br>[<img alt='artist_palette' src='icons/openmoji/1F3A8.png'>](https://openmoji.org/library/emoji-1F3A8/) | trophy<br>[<img alt='trophy' src='icons/openmoji/1F3C6.png'>](https://openmoji.org/library/emoji-1F3C6/)             | jack-o-lantern<br>[<img alt='jack-o-lantern' src='icons/openmoji/1F383.png'>](https://openmoji.org/library/emoji-1F383/) | christmas_tree<br>[<img alt='christmas_tree' src='icons/openmoji/1F384.png'>](https://openmoji.org/library/emoji-1F384/)     | fireworks<br>[<img alt='fireworks' src='icons/openmoji/1F386.png'>](https://openmoji.org/library/emoji-1F386/)         | firecracker<br>[<img alt='firecracker' src='icons/openmoji/1F9E8.png'>](https://openmoji.org/library/emoji-1F9E8/)       | sparkles<br>[<img alt='sparkles' src='icons/openmoji/2728.png'>](https://openmoji.org/library/emoji-2728/)           | balloon<br>[<img alt='balloon' src='icons/openmoji/1F388.png'>](https://openmoji.org/library/emoji-1F388/)               | party_popper<br>[<img alt='party_popper' src='icons/openmoji/1F389.png'>](https://openmoji.org/library/emoji-1F389/)         |
| wrapped_gift<br>[<img alt='wrapped_gift' src='icons/openmoji/1F381.png'>](https://openmoji.org/library/emoji-1F381/)       | ticket<br>[<img alt='ticket' src='icons/openmoji/1F380.png'>](https://openmoji.org/library/emoji-1F380/)                 | bullseye<br>[<img alt='bullseye' src='icons/openmoji/1F3AF.png'>](https://openmoji.org/library/emoji-1F3AF/)         | kite<br>[<img alt='kite' src='icons/openmoji/1FA81.png'>](https://openmoji.org/library/emoji-1FA81/)                     | crystal_ball<br>[<img alt='crystal_ball' src='icons/openmoji/1F52E.png'>](https://openmoji.org/library/emoji-1F52E/)         | magic_wand<br>[<img alt='magic_wand' src='icons/openmoji/1FA84.png'>](https://openmoji.org/library/emoji-1FA84/)       | game_die<br>[<img alt='game_die' src='icons/openmoji/1F3B2.png'>](https://openmoji.org/library/emoji-1F3B2/)             | puzzle_piece<br>[<img alt='puzzle_piece' src='icons/openmoji/1F9E9.png'>](https://openmoji.org/library/emoji-1F9E9/) | teddy_bear<br>[<img alt='teddy_bear' src='icons/openmoji/1F9F8.png'>](https://openmoji.org/library/emoji-1F9F8/)         | pinata<br>[<img alt='pinata' src='icons/openmoji/1FA85.png'>](https://openmoji.org/library/emoji-1FA85/)                     |
| spade_suit<br>[<img alt='spade_suit' src='icons/openmoji/2660.png'>](https://openmoji.org/library/emoji-2660/)             | heart_suit<br>[<img alt='heart_suit' src='icons/openmoji/2665.png'>](https://openmoji.org/library/emoji-2665/)           | diamond_suit<br>[<img alt='diamond_suit' src='icons/openmoji/2666.png'>](https://openmoji.org/library/emoji-2666/)   | club_suit<br>[<img alt='club_suit' src='icons/openmoji/2663.png'>](https://openmoji.org/library/emoji-2663/)             | chess_pawn<br>[<img alt='chess_pawn' src='icons/openmoji/265F.png'>](https://openmoji.org/library/emoji-265F/)               | fishing_pole<br>[<img alt='fishing_pole' src='icons/openmoji/1F3A3.png'>](https://openmoji.org/library/emoji-1F3A3/)   | tree<br>[<img alt='tree' src='icons/openmoji/1F333.png'>](https://openmoji.org/library/emoji-1F333/)                     | cactus<br>[<img alt='cactus' src='icons/openmoji/1F335.png'>](https://openmoji.org/library/emoji-1F335/)             | chequered_flag<br>[<img alt='chequered_flag' src='icons/openmoji/1F3C1.png'>](https://openmoji.org/library/emoji-1F3C1/) | triangular_flag<br>[<img alt='triangular_flag' src='icons/openmoji/1F6A9.png'>](https://openmoji.org/library/emoji-1F6A9/)   |
| black_flag<br>[<img alt='black_flag' src='icons/openmoji/1F3F4.png'>](https://openmoji.org/library/emoji-1F3F4/)           | white_flag<br>[<img alt='white_flag' src='icons/openmoji/1F3F3.png'>](https://openmoji.org/library/emoji-1F3F3/)         | books<br>[<img alt='books' src='icons/openmoji/1F4DA.png'>](https://openmoji.org/library/emoji-1F4DA/)               | scroll<br>[<img alt='scroll' src='icons/openmoji/1F4DC.png'>](https://openmoji.org/library/emoji-1F4DC/)                 | bookmark<br>[<img alt='bookmark' src='icons/openmoji/1F516.png'>](https://openmoji.org/library/emoji-1F516/)                 | door<br>[<img alt='door' src='icons/openmoji/1F6AA.png'>](https://openmoji.org/library/emoji-1F6AA/)                   | window<br>[<img alt='window' src='icons/openmoji/1FA9F.png'>](https://openmoji.org/library/emoji-1FA9F/)                 | bed<br>[<img alt='bed' src='icons/openmoji/1F6CF.png'>](https://openmoji.org/library/emoji-1F6CF/)                   | camera<br>[<img alt='camera' src='icons/openmoji/1F4F7.png'>](https://openmoji.org/library/emoji-1F4F7/)                 | magnifying_glass<br>[<img alt='magnifying_glass' src='icons/openmoji/1F50D.png'>](https://openmoji.org/library/emoji-1F50D/) |
| candle<br>[<img alt='candle' src='icons/openmoji/1F56F.png'>](https://openmoji.org/library/emoji-1F56F/)                   | light_bulb<br>[<img alt='light_bulb' src='icons/openmoji/1F4A1.png'>](https://openmoji.org/library/emoji-1F4A1/)         | locked<br>[<img alt='locked' src='icons/openmoji/1F512.png'>](https://openmoji.org/library/emoji-1F512/)             | unlocked<br>[<img alt='unlocked' src='icons/openmoji/1F513.png'>](https://openmoji.org/library/emoji-1F513/)             | key<br>[<img alt='key' src='icons/openmoji/1F511.png'>](https://openmoji.org/library/emoji-1F511/)                           | old_key<br>[<img alt='old_key' src='icons/openmoji/1F5DD.png'>](https://openmoji.org/library/emoji-1F5DD/)             | envelope<br>[<img alt='envelope' src='icons/openmoji/2709.png'>](https://openmoji.org/library/emoji-2709/)               | package<br>[<img alt='package' src='icons/openmoji/1F4E6.png'>](https://openmoji.org/library/emoji-1F4E6/)           | money_bag<br>[<img alt='money_bag' src='icons/openmoji/1F4B0.png'>](https://openmoji.org/library/emoji-1F4B0/)           | coin<br>[<img alt='coin' src='icons/openmoji/1FA99.png'>](https://openmoji.org/library/emoji-1FA99/)                         |
| note<br>[<img alt='note' src='icons/openmoji/1F3B5.png'>](https://openmoji.org/library/emoji-1F3B5/)                       | calendar<br>[<img alt='calendar' src='icons/openmoji/1F4C5.png'>](https://openmoji.org/library/emoji-1F4C5/)             | clipboard<br>[<img alt='clipboard' src='icons/openmoji/1F4CB.png'>](https://openmoji.org/library/emoji-1F4CB/)       | pin<br>[<img alt='pin' src='icons/openmoji/1F4CC.png'>](https://openmoji.org/library/emoji-1F4CC/)                       | round_pin<br>[<img alt='round_pin' src='icons/openmoji/1F4CD.png'>](https://openmoji.org/library/emoji-1F4CD/)               | paperclip<br>[<img alt='paperclip' src='icons/openmoji/1F4CE.png'>](https://openmoji.org/library/emoji-1F4CE/)         | coffin<br>[<img alt='coffin' src='icons/openmoji/26B0.png'>](https://openmoji.org/library/emoji-26B0/)                   | headstone<br>[<img alt='headstone' src='icons/openmoji/1FAA6.png'>](https://openmoji.org/library/emoji-1FAA6/)       | placard<br>[<img alt='placard' src='icons/openmoji/1FAA7.png'>](https://openmoji.org/library/emoji-1FAA7/)               | moai<br>[<img alt='moai' src='icons/openmoji/1F5FF.png'>](https://openmoji.org/library/emoji-1F5FF/)                         |
| telescope<br>[<img alt='telescope' src='icons/openmoji/1F52D.png'>](https://openmoji.org/library/emoji-1F52D/)             | test_tube<br>[<img alt='test_tube' src='icons/openmoji/1F9EA.png'>](https://openmoji.org/library/emoji-1F9EA/)           | bell<br>[<img alt='bell' src='icons/openmoji/1F514.png'>](https://openmoji.org/library/emoji-1F514/)                 | hammer<br>[<img alt='hammer' src='icons/openmoji/1F528.png'>](https://openmoji.org/library/emoji-1F528/)                 | axe<br>[<img alt='axe' src='icons/openmoji/1FA93.png'>](https://openmoji.org/library/emoji-1FA93/)                           | pickaxe<br>[<img alt='pickaxe' src='icons/openmoji/26CF.png'>](https://openmoji.org/library/emoji-26CF/)               | hammer_and_pick<br>[<img alt='hammer_and_pick' src='icons/openmoji/2692.png'>](https://openmoji.org/library/emoji-2692/) | sword<br>[<img alt='sword' src='icons/openmoji/1F5E1.png'>](https://openmoji.org/library/emoji-1F5E1/)               | crossed_swords<br>[<img alt='crossed_swords' src='icons/openmoji/2694.png'>](https://openmoji.org/library/emoji-2694/)   | bow_and_arrow<br>[<img alt='bow_and_arrow' src='icons/openmoji/1F3F9.png'>](https://openmoji.org/library/emoji-1F3F9/)       |
| shield<br>[<img alt='shield' src='icons/openmoji/1F6E1.png'>](https://openmoji.org/library/emoji-1F6E1/)                   | gear<br>[<img alt='gear' src='icons/openmoji/2699.png'>](https://openmoji.org/library/emoji-2699/)                       | balance_scale<br>[<img alt='balance_scale' src='icons/openmoji/2696.png'>](https://openmoji.org/library/emoji-2696/) | chains<br>[<img alt='chains' src='icons/openmoji/26D3.png'>](https://openmoji.org/library/emoji-26D3/)                   | ladder<br>[<img alt='ladder' src='icons/openmoji/1FA9C.png'>](https://openmoji.org/library/emoji-1FA9C/)                     | pencil<br>[<img alt='pencil' src='icons/openmoji/270F.png'>](https://openmoji.org/library/emoji-270F/)                 | up<br>[<img alt='up' src='icons/openmoji/2B06.png'>](https://openmoji.org/library/emoji-2B06/)                           | up-right<br>[<img alt='up-right' src='icons/openmoji/2197.png'>](https://openmoji.org/library/emoji-2197/)           | right<br>[<img alt='right' src='icons/openmoji/27A1.png'>](https://openmoji.org/library/emoji-27A1/)                     | down-right<br>[<img alt='down-right' src='icons/openmoji/2198.png'>](https://openmoji.org/library/emoji-2198/)               |
| down<br>[<img alt='down' src='icons/openmoji/2B07.png'>](https://openmoji.org/library/emoji-2B07/)                         | down-left<br>[<img alt='down-left' src='icons/openmoji/2199.png'>](https://openmoji.org/library/emoji-2199/)             | left<br>[<img alt='left' src='icons/openmoji/2B05.png'>](https://openmoji.org/library/emoji-2B05/)                   | up-left<br>[<img alt='up-left' src='icons/openmoji/2196.png'>](https://openmoji.org/library/emoji-2196/)                 | shuffle<br>[<img alt='shuffle' src='icons/openmoji/1F500.png'>](https://openmoji.org/library/emoji-1F500/)                   | dollar<br>[<img alt='dollar' src='icons/openmoji/1F4B2.png'>](https://openmoji.org/library/emoji-1F4B2/)               | orange_circle<br>[<img alt='orange_circle' src='icons/openmoji/1F7E0.png'>](https://openmoji.org/library/emoji-1F7E0/)   | blue_circle<br>[<img alt='blue_circle' src='icons/openmoji/1F535.png'>](https://openmoji.org/library/emoji-1F535/)   | orange_square<br>[<img alt='orange_square' src='icons/openmoji/1F7E7.png'>](https://openmoji.org/library/emoji-1F7E7/)   | blue_square<br>[<img alt='blue_square' src='icons/openmoji/1F7E6.png'>](https://openmoji.org/library/emoji-1F7E6/)           |
| orange_diamond<br>[<img alt='orange_diamond' src='icons/openmoji/1F536.png'>](https://openmoji.org/library/emoji-1F536/)   | blue_diamond<br>[<img alt='blue_diamond' src='icons/openmoji/1F537.png'>](https://openmoji.org/library/emoji-1F537/)     | triangle_up<br>[<img alt='triangle_up' src='icons/openmoji/1F53A.png'>](https://openmoji.org/library/emoji-1F53A/)   | triangle_down<br>[<img alt='triangle_down' src='icons/openmoji/1F53B.png'>](https://openmoji.org/library/emoji-1F53B/)   | diamond_with_dot<br>[<img alt='diamond_with_dot' src='icons/openmoji/1F4A0.png'>](https://openmoji.org/library/emoji-1F4A0/) | hollow_circle<br>[<img alt='hollow_circle' src='icons/openmoji/2B55.png'>](https://openmoji.org/library/emoji-2B55/)   | check<br>[<img alt='check' src='icons/openmoji/2714.png'>](https://openmoji.org/library/emoji-2714/)                     | cross<br>[<img alt='cross' src='icons/openmoji/274C.png'>](https://openmoji.org/library/emoji-274C/)                 | worship<br>[<img alt='worship' src='icons/openmoji/1F9D0.png'>](https://openmoji.org/library/emoji-1F9D0/)               | atom<br>[<img alt='atom' src='icons/openmoji/269B.png'>](https://openmoji.org/library/emoji-269B/)                           |
| yin_yang<br>[<img alt='yin_yang' src='icons/openmoji/262F.png'>](https://openmoji.org/library/emoji-262F/)                 | peace<br>[<img alt='peace' src='icons/openmoji/262E.png'>](https://openmoji.org/library/emoji-262E/)                     | warning<br>[<img alt='warning' src='icons/openmoji/26A0.png'>](https://openmoji.org/library/emoji-26A0/)             | no_entry<br>[<img alt='no_entry' src='icons/openmoji/26D4.png'>](https://openmoji.org/library/emoji-26D4/)               | prohibited<br>[<img alt='prohibited' src='icons/openmoji/1F6AB.png'>](https://openmoji.org/library/emoji-1F6AB/)             | radioactive<br>[<img alt='radioactive' src='icons/openmoji/2622.png'>](https://openmoji.org/library/emoji-2622/)       | biohazard<br>[<img alt='biohazard' src='icons/openmoji/2623.png'>](https://openmoji.org/library/emoji-2623/)             | world<br>[<img alt='world' src='icons/openmoji/1F30D.png'>](https://openmoji.org/library/emoji-1F30D/)               | meridians<br>[<img alt='meridians' src='icons/openmoji/1F310.png'>](https://openmoji.org/library/emoji-1F310/)           | compass<br>[<img alt='compass' src='icons/openmoji/1F9ED.png'>](https://openmoji.org/library/emoji-1F9ED/)                   |
| mountain<br>[<img alt='mountain' src='icons/openmoji/26F0.png'>](https://openmoji.org/library/emoji-26F0/)                 | camping<br>[<img alt='camping' src='icons/openmoji/1F3D5.png'>](https://openmoji.org/library/emoji-1F3D5/)               | beach<br>[<img alt='beach' src='icons/openmoji/1F3D6.png'>](https://openmoji.org/library/emoji-1F3D6/)               | desert<br>[<img alt='desert' src='icons/openmoji/1F3DC.png'>](https://openmoji.org/library/emoji-1F3DC/)                 | island<br>[<img alt='island' src='icons/openmoji/1F3DD.png'>](https://openmoji.org/library/emoji-1F3DD/)                     | national_park<br>[<img alt='national_park' src='icons/openmoji/1F3DE.png'>](https://openmoji.org/library/emoji-1F3DE/) | stadium<br>[<img alt='stadium' src='icons/openmoji/1F3DF.png'>](https://openmoji.org/library/emoji-1F3DF/)               | monument<br>[<img alt='monument' src='icons/openmoji/1F3DB.png'>](https://openmoji.org/library/emoji-1F3DB/)         | construction<br>[<img alt='construction' src='icons/openmoji/1F3D7.png'>](https://openmoji.org/library/emoji-1F3D7/)     | brick<br>[<img alt='brick' src='icons/openmoji/1F9F1.png'>](https://openmoji.org/library/emoji-1F9F1/)                       |
| rock<br>[<img alt='rock' src='icons/openmoji/1FAA8.png'>](https://openmoji.org/library/emoji-1FAA8/)                       | wood<br>[<img alt='wood' src='icons/openmoji/1FAB5.png'>](https://openmoji.org/library/emoji-1FAB5/)                     | hut<br>[<img alt='hut' src='icons/openmoji/1F6D6.png'>](https://openmoji.org/library/emoji-1F6D6/)                   | houses<br>[<img alt='houses' src='icons/openmoji/1F3D8.png'>](https://openmoji.org/library/emoji-1F3D8/)                 | derelict_house<br>[<img alt='derelict_house' src='icons/openmoji/1F3DA.png'>](https://openmoji.org/library/emoji-1F3DA/)     | house<br>[<img alt='house' src='icons/openmoji/1F3E0.png'>](https://openmoji.org/library/emoji-1F3E0/)                 | office<br>[<img alt='office' src='icons/openmoji/1F3E2.png'>](https://openmoji.org/library/emoji-1F3E2/)                 | hospital<br>[<img alt='hospital' src='icons/openmoji/1F3EF.png'>](https://openmoji.org/library/emoji-1F3EF/)         | bank<br>[<img alt='bank' src='icons/openmoji/1F3E6.png'>](https://openmoji.org/library/emoji-1F3E6/)                     | school<br>[<img alt='school' src='icons/openmoji/1F3EB.png'>](https://openmoji.org/library/emoji-1F3EB/)                     |
| store<br>[<img alt='store' src='icons/openmoji/1F3EC.png'>](https://openmoji.org/library/emoji-1F3EC/)                     | factory<br>[<img alt='factory' src='icons/openmoji/1F3ED.png'>](https://openmoji.org/library/emoji-1F3ED/)               | castle<br>[<img alt='castle' src='icons/openmoji/1F3F0.png'>](https://openmoji.org/library/emoji-1F3F0/)             | church<br>[<img alt='church' src='icons/openmoji/26EA.png'>](https://openmoji.org/library/emoji-26EA/)                   | fountain<br>[<img alt='fountain' src='icons/openmoji/26F2.png'>](https://openmoji.org/library/emoji-26F2/)                   | tent<br>[<img alt='tent' src='icons/openmoji/26FA.png'>](https://openmoji.org/library/emoji-26FA/)                     | city<br>[<img alt='city' src='icons/openmoji/1F3D9.png'>](https://openmoji.org/library/emoji-1F3D9/)                     | playground<br>[<img alt='playground' src='icons/openmoji/1F6DD.png'>](https://openmoji.org/library/emoji-1F6DD/)     | circus<br>[<img alt='circus' src='icons/openmoji/1F3AA.png'>](https://openmoji.org/library/emoji-1F3AA/)                 | train<br>[<img alt='train' src='icons/openmoji/1F686.png'>](https://openmoji.org/library/emoji-1F686/)                       |
| car<br>[<img alt='car' src='icons/openmoji/1F697.png'>](https://openmoji.org/library/emoji-1F697/)                         | traffic_light<br>[<img alt='traffic_light' src='icons/openmoji/1F6A6.png'>](https://openmoji.org/library/emoji-1F6A6/)   | anchor<br>[<img alt='anchor' src='icons/openmoji/2693.png'>](https://openmoji.org/library/emoji-2693/)               | canoe<br>[<img alt='canoe' src='icons/openmoji/1F6F6.png'>](https://openmoji.org/library/emoji-1F6F6/)                   | airplane<br>[<img alt='airplane' src='icons/openmoji/2708.png'>](https://openmoji.org/library/emoji-2708/)                   | satellite<br>[<img alt='satellite' src='icons/openmoji/1F6F0.png'>](https://openmoji.org/library/emoji-1F6F0/)         | rocket<br>[<img alt='rocket' src='icons/openmoji/1F680.png'>](https://openmoji.org/library/emoji-1F680/)                 | moon<br>[<img alt='moon' src='icons/openmoji/1F319.png'>](https://openmoji.org/library/emoji-1F319/)                 | sun<br>[<img alt='sun' src='icons/openmoji/2600.png'>](https://openmoji.org/library/emoji-2600/)                         | star<br>[<img alt='star' src='icons/openmoji/2B50.png'>](https://openmoji.org/library/emoji-2B50/)                           |
| cloud<br>[<img alt='cloud' src='icons/openmoji/2601.png'>](https://openmoji.org/library/emoji-2601/)                       | rainbow<br>[<img alt='rainbow' src='icons/openmoji/1F308.png'>](https://openmoji.org/library/emoji-1F308/)               | high_voltage<br>[<img alt='high_voltage' src='icons/openmoji/26A1.png'>](https://openmoji.org/library/emoji-26A1/)   | snowflake<br>[<img alt='snowflake' src='icons/openmoji/2744.png'>](https://openmoji.org/library/emoji-2744/)             | fire<br>[<img alt='fire' src='icons/openmoji/1F525.png'>](https://openmoji.org/library/emoji-1F525/)                         | signpost<br>[<img alt='signpost' src='icons/openmoji/E094.png'>](https://openmoji.org/library/emoji-E094/)             | transmission<br>[<img alt='transmission' src='icons/openmoji/E0A1.png'>](https://openmoji.org/library/emoji-E0A1/)       | location<br>[<img alt='location' src='icons/openmoji/E0A9.png'>](https://openmoji.org/library/emoji-E0A9/)           | bread<br>[<img alt='bread' src='icons/openmoji/E0CA.png'>](https://openmoji.org/library/emoji-E0CA/)                     | town<br>[<img alt='town' src='icons/openmoji/E203.png'>](https://openmoji.org/library/emoji-E203/)                           |


### Custom Icons

There are no custom icons, but maybe in the future.