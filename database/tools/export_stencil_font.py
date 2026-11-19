"""Export the stencil font's outlines for USSCOS's custom stencil builder.

    python3 database/tools/export_stencil_font.py <shop clone>/fonts/DINSchablonierschrift.ttf out.json

The font's license does not allow it to be embedded in web pages, and this repository is
public. So neither the font nor this output is ever committed: the JSON goes straight to
/var/www/usscos/storage/stencil-data/stencil_font.json on the server, where
App\\Services\\Stencils\\TextDesigner reads it and turns a customer's text into outlines,
exactly as the stencil shop's fontsvg.py did with the TTF.

Reads the TTF with the shop's own reader (fontsvg.py), so the outlines are the ones the
shop drew. Run it from the shop clone's directory, or put that directory on PYTHONPATH.
"""
import json
import sys

import fontsvg

font = fontsvg.Font(sys.argv[1])
chars = {}

for code, gid in sorted(font.cmap.items()):
    if code < 32 or code > 0x2FF:
        continue
    chars[chr(code)] = {"adv": font.advances[gid], "bbox": list(font._bbox(gid)),
                        "c": [[[round(x, 3), round(y, 3), 1 if on else 0] for x, y, on in contour]
                              for contour in font.contours(gid)]}

out = {"unitsPerEm": font.units_per_em, "capHeight": font.cap_height,
       "spaceAdvance": font.advances[font.glyph_id(" ")], "missingAdvance": font.advances[0], "chars": chars}

with open(sys.argv[2], "w") as f:
    json.dump(out, f, separators=(",", ":"))

print(len(chars), "characters")
