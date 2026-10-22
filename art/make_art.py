"""Generate the Deleted Records art set (SVG) in the shared amjadiqbal template.

Usage: python3 -I make_art.py   (writes SVGs next to this file; render.sh makes the PNG/JPG)
No brown: warm colour (rose) appears only as solid strokes/text, never as a fill or translucent wash.
"""
import os

HERE = os.path.dirname(os.path.abspath(__file__))
FONT = "Helvetica, Arial, sans-serif"
PKG = "amjadiqbal/filament-deleted-records"
TITLE = "Deleted Records"
TAGLINE = "A recycle bin for your Filament panel"
CHIPS = ["preview · restore · purge", "filter by model, date, user", "Filament 4 · 5"]

THEMES = {
    "light": dict(bg=("#F8FAFC", "#ECFDF9"), title="#0F172A", accent="#0F766E", chip_fill="#FFFFFF",
                  chip_stroke="#E2E8F0", chip_text="#334155", card="#FFFFFF", card_stroke="#E2E8F0",
                  muted="#64748B", body="#0F172A", badge_fill="#F1F5F9", badge_text="#334155",
                  restore="#0D9488", purge="#E11D48", pkg="#94A3B8", row="#F8FAFC"),
    "dark": dict(bg=("#0B1220", "#0E2230"), title="#F8FAFC", accent="#5EEAD4", chip_fill="#111C33",
                 chip_stroke="#273449", chip_text="#CBD5E1", card="#111C33", card_stroke="#273449",
                 muted="#94A3B8", body="#F8FAFC", badge_fill="#1E293B", badge_text="#CBD5E1",
                 restore="#0D9488", purge="#FB7185", pkg="#64748B", row="#0F1A2E"),
}


def icon_body():
    """The 512x512 mark: a bin with a record lifting back out of it, and a restore arrow."""
    return """
    <rect width="512" height="512" rx="112" fill="url(#iconbg)"/>
    <!-- record card rising out of the bin -->
    <g transform="rotate(-8 256 190)">
      <rect x="168" y="88" width="176" height="150" rx="18" fill="#F8FAFC"/>
      <circle cx="204" cy="126" r="14" fill="#2DD4BF"/>
      <rect x="228" y="118" width="92" height="16" rx="8" fill="#94A3B8"/>
      <rect x="190" y="160" width="132" height="14" rx="7" fill="#CBD5E1"/>
      <rect x="190" y="190" width="100" height="14" rx="7" fill="#CBD5E1"/>
    </g>
    <!-- bin -->
    <rect x="112" y="226" width="288" height="30" rx="15" fill="#5EEAD4"/>
    <path d="M140 270 H372 L352 420 Q349 440 328 440 H184 Q163 440 160 420 Z" fill="#134E4A" stroke="#5EEAD4" stroke-width="10" stroke-linejoin="round"/>
    <line x1="210" y1="300" x2="218" y2="408" stroke="#2DD4BF" stroke-width="10" stroke-linecap="round"/>
    <line x1="256" y1="300" x2="256" y2="408" stroke="#2DD4BF" stroke-width="10" stroke-linecap="round"/>
    <line x1="302" y1="300" x2="294" y2="408" stroke="#2DD4BF" stroke-width="10" stroke-linecap="round"/>
    <!-- restore arrow (counter-clockwise) -->
    <circle cx="392" cy="392" r="74" fill="#0D9488" stroke="#0F172A" stroke-width="14"/>
    <g transform="translate(392 392) scale(4.2) translate(-12 -12)" fill="none" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
      <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>
    </g>
"""


def defs(t):
    return f"""  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{t['bg'][0]}"/>
      <stop offset="100%" stop-color="{t['bg'][1]}"/>
    </linearGradient>
    <linearGradient id="iconbg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0F172A"/>
      <stop offset="100%" stop-color="#0F3B3A"/>
    </linearGradient>
  </defs>"""


def chips(t, x, y, size=15, h=32, gap=10, anchor_center=False):
    widths = [round(len(c) * size * 0.56 + 34) for c in CHIPS]
    if anchor_center:
        x = x - (sum(widths) + gap * (len(widths) - 1)) / 2
    out = [f'  <g font-family="{FONT}" font-size="{size}" font-weight="600" fill="{t["chip_text"]}" text-anchor="middle">']
    for c, w in zip(CHIPS, widths):
        out.append(f'<g transform="translate({x:.0f},{y})"><rect width="{w}" height="{h}" rx="{h/2}" fill="{t["chip_fill"]}" stroke="{t["chip_stroke"]}"/>'
                   f'<text x="{w/2}" y="{h*0.66:.2f}">{c}</text></g>')
        x += w + gap
    out.append("  </g>")
    return "\n".join(out)


ROWS = [("Invoice", "#1042", "2 min ago", "Sara"), ("Customer", "#318", "1 hour ago", "Omar"), ("Product", "#77", "yesterday", "Sara")]


def bin_card(t, x, y, s=1.0, rows=ROWS):
    """A miniature recycle bin table: model badge, key, when, who, Restore."""
    h = 52 + 44 * len(rows)
    out = [f'  <g transform="translate({x},{y}) scale({s})" font-family="{FONT}">',
           f'    <rect width="300" height="{h}" rx="16" fill="{t["card"]}" stroke="{t["card_stroke"]}" stroke-width="1.5"/>',
           f'    <text x="18" y="30" font-size="12" font-weight="700" fill="{t["muted"]}" letter-spacing="0.6">DELETED RECORDS</text>']
    for i, (m, k, when, who) in enumerate(rows):
        ry = 46 + i * 44
        bw = len(m) * 7.4 + 16
        out.append(f'    <line x1="0" y1="{ry}" x2="300" y2="{ry}" stroke="{t["card_stroke"]}"/>')
        out.append(f'    <rect x="18" y="{ry+11}" width="{bw:.0f}" height="22" rx="11" fill="{t["badge_fill"]}"/>'
                   f'<text x="{18+bw/2:.0f}" y="{ry+26}" font-size="12" font-weight="700" fill="{t["badge_text"]}" text-anchor="middle">{m}</text>')
        out.append(f'    <text x="{28+bw:.0f}" y="{ry+27}" font-size="13" font-weight="700" fill="{t["body"]}">{k}</text>')
        out.append(f'    <text x="146" y="{ry+27}" font-size="12" fill="{t["muted"]}">{when} · {who}</text>')
        out.append(f'    <g transform="translate(258 {ry+12}) scale(0.85)" fill="none" stroke="{t["restore"]}" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">'
                   '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></g>')
    out.append("  </g>")
    return "\n".join(out)


def svg(w, h, body, t):
    return f'<svg width="{w}" height="{h}" viewBox="0 0 {w} {h}" xmlns="http://www.w3.org/2000/svg">\n{defs(t)}\n  <rect width="{w}" height="{h}" fill="url(#bg)"/>\n{body}\n</svg>\n'


def banner(name):
    t = THEMES[name]
    body = f"""  <g transform="translate(60,75) scale(0.29297)">{icon_body()}</g>
  <text x="235" y="148" font-family="{FONT}" font-size="64" font-weight="700" fill="{t['title']}">{TITLE}</text>
  <text x="238" y="190" font-family="{FONT}" font-size="23" font-weight="500" fill="{t['accent']}" letter-spacing="0.3">{TAGLINE}</text>
{chips(t, 238, 218)}
{bin_card(t, 905, 34, s=0.88)}
  <text x="1170" y="284" text-anchor="end" font-family="{FONT}" font-size="15" fill="{t['pkg']}">{PKG}</text>"""
    return svg(1200, 300, body, t)


def social():
    t = THEMES["dark"]
    body = f"""  <g transform="translate(490,50) scale(0.5859)">{icon_body()}</g>
  <text x="640" y="430" text-anchor="middle" font-family="{FONT}" font-size="76" font-weight="700" fill="{t['title']}">{TITLE}</text>
  <text x="640" y="482" text-anchor="middle" font-family="{FONT}" font-size="28" font-weight="500" fill="{t['accent']}">Browse, preview, restore or purge deleted models in Filament</text>
{chips(t, 640, 530, size=18, h=40, gap=14, anchor_center=True)}
  <text x="1240" y="610" text-anchor="end" font-family="{FONT}" font-size="18" fill="{t['pkg']}">{PKG}</text>"""
    return svg(1280, 640, body, t)


def directory():
    """16:9 LIGHT listing image for filamentphp.com, authored at 1280x720, rendered at 2560x1440.
    Zoomed into the feature: no panel sidebar or top bar."""
    t = THEMES["light"]
    body = f"""  <g transform="translate(90,80) scale(0.25)">{icon_body()}</g>
  <text x="90" y="290" font-family="{FONT}" font-size="64" font-weight="700" fill="{t['title']}">{TITLE}</text>
  <text x="92" y="338" font-family="{FONT}" font-size="26" font-weight="500" fill="{t['accent']}">{TAGLINE}</text>
  <g font-family="{FONT}" font-size="21" fill="{t['chip_text']}">
    <text x="92" y="410">• Every deleted model in one list</text>
    <text x="92" y="448">• Preview the stored attributes before restoring</text>
    <text x="92" y="486">• Restore or purge, one by one or in bulk</text>
    <text x="92" y="524">• Filter by model, date and who deleted it</text>
  </g>
{bin_card(t, 690, 150, s=1.8)}
  <text x="1190" y="690" text-anchor="end" font-family="{FONT}" font-size="18" fill="{t['pkg']}">{PKG}</text>"""
    return svg(1280, 720, body, t).replace('width="1280" height="720"', 'width="2560" height="1440"', 1)


def icon():
    return f'<svg width="512" height="512" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">\n{defs(THEMES["dark"])}\n<g>{icon_body()}</g>\n</svg>\n'


if __name__ == "__main__":
    files = {"icon.svg": icon(), "banner-light.svg": banner("light"), "banner-dark.svg": banner("dark"),
             "social-preview.svg": social(), "filament-directory.svg": directory()}
    for f, s in files.items():
        with open(os.path.join(HERE, f), "w") as fh:
            fh.write(s)
        print("wrote", f)
