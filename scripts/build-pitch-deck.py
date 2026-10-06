# Builds the client pitch deck from the real screenshots and the product's own
# brand palette. Run: python scripts/build-pitch-deck.py
#
# Screenshots are captured from the live demo by marketing/screenshots. Every
# figure quoted here comes off that seeded demo database and is labelled as
# demo data on the slides that use it, because none of it is a real client's
# money. The compliance slide is not decoration either: CLAUDE.md forbids
# medical claims and forbids advertising the system as BIR compliant.

import os
from pptx import Presentation
from pptx.util import Inches, Pt, Emu
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from PIL import Image

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SHOTS = os.path.join(ROOT, "marketing", "screenshots")
CROPS = os.path.join(ROOT, "marketing", ".crops")
OUT = os.path.join(ROOT, "marketing", "Irish-Aesthetics-Pitch.pptx")

# Several captures are full-page screenshots: 01-public-home.png is 2880x18096
# and 20-admin-dashboard.png is 2880x8786. Scaled to fit a 4.6" box, a capture
# that shape collapses into an unreadable sliver about 0.7" wide. So each of
# those is cropped to the part worth looking at, as a fraction of its height.
# The originals are never modified; the crop is cached next to this script.
CROP = {
    "01-public-home.png": (0.0, 0.075),      # hero and the live booking card
    "20-admin-dashboard.png": (0.22, 0.30),   # the stat row and the charts
    "04-public-booking.png": (0.0, 0.45),     # the booking form, not the footer
}

# config/clinic.php. Kept in one place so a rebrand is a single edit.
PLUM = RGBColor(0x3B, 0x24, 0x30)
ROSE = RGBColor(0xF4, 0x7F, 0xA4)
ROSE_DARK = RGBColor(0xD9, 0x57, 0x7F)
ROSE_INK = RGBColor(0xA8, 0x32, 0x57)
CHAMPAGNE = RGBColor(0xC9, 0xA4, 0x6C)
IVORY = RGBColor(0xFF, 0xFD, 0xFC)
BLUSH = RGBColor(0xFC, 0xE8, 0xEE)
SOFT_ROSE = RGBColor(0xFF, 0xF4, 0xF7)
TEXT = RGBColor(0x51, 0x45, 0x4A)
MUTED = RGBColor(0x8A, 0x7B, 0x81)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)

# ------------------------------------------------------------------ the builder
# Recursive Frog (C:\wamp64\www\frog) is the studio that built this platform, so
# the deck credits it alongside the client rather than pretending to be the client.
# The mark is a branching fractal with the frog sitting at the centre of it: the
# name and the drawing are the same idea, which is the whole point of it.
#
# It is placed exactly as the studio supplied it -- no recolour, no redraw, only
# ever scaled. Two placements carry the full-strength jade: the title and the
# closing slide, both of which are plum, where jade reads cleanly. The thirteen
# content slides get a faded copy instead, because jade at full strength on an
# ivory slide would out-shout the plum it is supposed to sit quietly behind.
FROG = os.environ.get(
    "IRISH_FROG_MARK",
    r"C:\wamp64\www\frog\public\img\recursivefrog-logo.png",
)
BRAND = os.path.join(ROOT, "marketing", ".brand")
FROG_FAINT = 0.34
JADE = RGBColor(0x5F, 0xE0, 0xAE)

# The display face is the one in resources/css/app.css, with the same fallback
# chain the site uses. Naming the fallback matters: this deck is opened on other
# machines, and a bare "Bodoni Moda" silently renders as Calibri there.
DISPLAY = "Bodoni Moda"
BODY = "Jost"
W, H = Inches(13.333), Inches(7.5)
M = Inches(0.85)  # margin


def slide(prs, bg=IVORY):
    s = prs.slides.add_slide(prs.slide_layouts[6])
    s.background.fill.solid()
    s.background.fill.fore_color.rgb = bg
    return s


def rect(s, x, y, w, h, fill=None, line=None, line_w=Pt(1)):
    from pptx.enum.shapes import MSO_SHAPE

    sh = s.shapes.add_shape(MSO_SHAPE.RECTANGLE, x, y, w, h)
    sh.shadow.inherit = False
    if fill is None:
        sh.fill.background()
    else:
        sh.fill.solid()
        sh.fill.fore_color.rgb = fill
    if line is None:
        sh.line.fill.background()
    else:
        sh.line.color.rgb = line
        sh.line.width = line_w
    return sh


def text(s, x, y, w, h, runs, size=18, color=TEXT, font=BODY, bold=False,
         align=PP_ALIGN.LEFT, anchor=MSO_ANCHOR.TOP, spacing=1.15, caps=False):
    """runs is a string or a list of (string, dict-of-overrides) tuples."""
    tb = s.shapes.add_textbox(x, y, w, h)
    tf = tb.text_frame
    tf.word_wrap = True
    tf.vertical_anchor = anchor
    tf.margin_left = tf.margin_right = tf.margin_top = tf.margin_bottom = 0

    paras = runs if isinstance(runs, list) else [runs]
    for i, para in enumerate(paras):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.alignment = align
        p.line_spacing = spacing
        parts = para if isinstance(para, list) else [(para, {})]
        for txt, ov in parts:
            r = p.add_run()
            r.text = txt.upper() if (caps or ov.get("caps")) else txt
            f = r.font
            f.name = ov.get("font", font)
            f.size = Pt(ov.get("size", size))
            f.bold = ov.get("bold", bold)
            f.color.rgb = ov.get("color", color)
            if ov.get("caps"):
                f._rPr.set("cap", "all")
    return tb


CONTENT_TOP = Inches(2.15)  # clear of the 34pt heading on every content slide


def eyebrow(s, label, x=M, y=Inches(0.62), color=ROSE_DARK):
    """Small caps label with a champagne rule, used to head each slide."""
    rect(s, x, y + Inches(0.075), Inches(0.28), Pt(2.5), fill=CHAMPAGNE)
    text(s, x + Inches(0.42), y, Inches(9), Inches(0.3), label,
         size=10.5, color=color, bold=True, caps=True)
    return y + Inches(0.52)


def heading(s, title, y, size=30, color=PLUM, x=M, w=None):
    text(s, x, y, w or (W - 2 * M), Inches(0.9), title,
         size=size, color=color, font=DISPLAY)
    return y


def bullets(s, x, y, w, items, size=15, gap=Inches(0.52), color=TEXT,
            dot=ROSE, dot_size=Pt(5)):
    for i, it in enumerate(items):
        yy = y + i * gap
        rect(s, x, yy + Inches(0.085), dot_size, dot_size, fill=dot)
        text(s, x + Inches(0.28), yy, w - Inches(0.28), gap, it,
             size=size, color=color, spacing=1.22)


def picture(s, name, x, y, box_w, box_h, frame=True):
    """Place a screenshot in a box, preserving aspect ratio, centred.

    Full-page captures are cropped first (see CROP) or they render as slivers.
    """
    path = os.path.join(SHOTS, name)
    if not os.path.exists(path):
        return None
    if name in CROP:
        os.makedirs(CROPS, exist_ok=True)
        cached = os.path.join(CROPS, name)
        if not os.path.exists(cached):
            top_frac, height_frac = CROP[name]
            im = Image.open(path)
            top = int(im.height * top_frac)
            im.crop((0, top, im.width,
                     top + int(im.height * height_frac))).save(cached)
        path = cached
    iw, ih = Image.open(path).size
    scale = min(box_w / iw, box_h / ih)
    w, h = int(iw * scale), int(ih * scale)
    px = int(x + (box_w - w) / 2)
    py = int(y + (box_h - h) / 2)
    if frame:
        rect(s, px - Pt(6), py - Pt(6), w + Pt(12), h + Pt(12),
             fill=WHITE, line=RGBColor(0xE6, 0xDB, 0xE0), line_w=Pt(1))
    return s.shapes.add_picture(path, px, py, w, h)


def frog_alpha_copy():
    """The mark at reduced opacity, for the light content slides.

    python-pptx cannot set picture transparency, so the fade is baked into a
    cached copy instead. Cached because it is derived, and because PowerPoint
    needs a real file to embed either way.
    """
    os.makedirs(BRAND, exist_ok=True)
    out = os.path.join(BRAND, "frog-faint.png")
    if not os.path.exists(out):
        im = Image.open(FROG).convert("RGBA")
        r, g, b, a = im.split()
        a = a.point(lambda v: int(v * FROG_FAINT))
        Image.merge("RGBA", (r, g, b, a)).save(out)
    return out


def frog(s, x, y, h, faint=False):
    """Place the Recursive Frog mark at height h, aspect preserved.

    Kept as one helper so the two strengths cannot drift apart: every call site
    picks a placement and a size, never an opacity.
    """
    src = frog_alpha_copy() if faint else FROG
    iw, ih = Image.open(src).size
    w = int(iw * h / ih)
    return s.shapes.add_picture(src, int(x), int(y), w, int(h))


def frog_lockup(s, right, y, h, faint=False, color=MUTED, size=9):
    """Mark plus the wordmark, flush to a right edge.

    The name is set in the deck's own body face at tracked small caps rather
    than in the studio's Archivo: the mark carries the identity, and the caption
    only has to say whose it is. The caption box is placed to the mark's left and
    right-aligned, so the pair reads as one lockup with a consistent right edge.
    """
    src = frog_alpha_copy() if faint else FROG
    iw, ih = Image.open(src).size
    w = int(iw * h / ih)
    x = int(right - w)
    s.shapes.add_picture(src, x, int(y), w, int(h))
    text(s, x - Inches(1.95), int(y) + int(h * 0.16), Inches(1.75), Inches(0.24),
         "Built by Recursive Frog", size=size, color=color, caps=True,
         align=PP_ALIGN.RIGHT, spacing=1.0)


def footer(s, note="Irish Aesthetics and Beauty Lounge"):
    rect(s, M, H - Inches(0.78), W - 2 * M, Pt(0.75), fill=BLUSH)
    text(s, M, H - Inches(0.62), Inches(6), Inches(0.3), note,
         size=9, color=MUTED, caps=True)
    text(s, W - M - Inches(3), H - Inches(0.62), Inches(3), Inches(0.3),
         "irish.deskpulse.click", size=9, color=MUTED, align=PP_ALIGN.RIGHT)
    # The studio's mark, centred in the empty middle of the rule so it cannot
    # collide with either caption at any slide width. Faded: on the light slides
    # this is a found detail, not a second logo.
    frog(s, W / 2 - Inches(0.16), H - Inches(0.63), Inches(0.26), faint=True)


def title_block(s, kicker, title, sub, kicker_color=ROSE_DARK, title_color=PLUM,
                sub_color=TEXT):
    y = eyebrow(s, kicker, color=kicker_color)
    y = heading(s, title, y + Inches(0.1), size=40, color=title_color)
    text(s, M, y + Inches(1.0), Inches(7.6), Inches(1.2), sub,
         size=15.5, color=sub_color, spacing=1.32)
    return y + Inches(1.0)

prs = Presentation()
prs.slide_width, prs.slide_height = W, H

# ---------------------------------------------------------------- 1. Title
s = slide(prs, PLUM)
rect(s, 0, 0, Inches(0.16), H, fill=ROSE)
text(s, M, Inches(1.5), Inches(9), Inches(0.4),
     "Clinic operations platform", size=12, color=CHAMPAGNE, bold=True, caps=True)
text(s, M, Inches(2.05), Inches(10.5), Inches(2.2),
     "One system from first visit to final entry in the books.",
     size=44, color=IVORY, font=DISPLAY, spacing=1.06)
rect(s, M, Inches(4.45), Inches(1.5), Pt(2.5), fill=ROSE)
text(s, M, Inches(4.85), Inches(9.5), Inches(1.0),
     "Website, bookings, CRM, till, stock, staff, payroll and accounting "
     "for a Philippine aesthetic clinic. Running on your own data, "
     "with your own brand.",
     size=16, color=RGBColor(0xD9, 0xC7, 0xCE), spacing=1.38)
text(s, M, Inches(6.55), Inches(9), Inches(0.4),
     "Irish Aesthetics and Beauty Lounge", size=13, color=IVORY, bold=True)
text(s, M, Inches(6.9), Inches(9), Inches(0.4),
     "Makati  ·  BGC  ·  Cebu", size=11,
     color=RGBColor(0xB0, 0x9C, 0xA5), caps=True)
# Full-strength jade on the plum ground: this is where the studio is named, so it
# reads as a credit rather than as a watermark.
frog_lockup(s, W - M, Inches(1.42), Inches(0.42),
            color=RGBColor(0xC8, 0xB6, 0xBF), size=10)

# ------------------------------------------------- 2. The cost of the status quo
# Loss aversion first: name the leak before offering the fix. Specific beats abstract.
s = slide(prs)
y = eyebrow(s, "The problem")
heading(s, "You already have the data. It is just in seven places.", y + Inches(0.1))

cards = [
    ("Enquiries in a personal inbox", "You follow up from memory. The ones you forget were the ones ready to book."),
    ("A paper diary for the chair", "A no-show is a hole in the day you cannot see until it is gone."),
    ("Takings counted at closing", "You learn how the month went days after you can act on it."),
    ("Stock that is a guess", "You find the expiry when you reach for the bottle, if you reach at all."),
    ("Payroll in a spreadsheet", "Every rate, deduction and adjustment retyped by hand, every run."),
    ("Books reconciled at year end", "The month closes only when the numbers happen to agree."),
]
cw, ch = Inches(3.78), Inches(1.62)
for i, (t, b) in enumerate(cards):
    col, row = i % 3, i // 3
    x = M + col * (cw + Inches(0.19))
    yy = Inches(2.35) + row * (ch + Inches(0.22))
    rect(s, x, yy, cw, ch, fill=WHITE, line=RGBColor(0xE6, 0xDB, 0xE0))
    rect(s, x, yy, Pt(3), ch, fill=ROSE)
    text(s, x + Inches(0.28), yy + Inches(0.24), cw - Inches(0.5), Inches(0.4), t,
         size=13.5, color=PLUM, bold=True)
    text(s, x + Inches(0.28), yy + Inches(0.72), cw - Inches(0.5), Inches(0.8), b,
         size=11.5, color=TEXT, spacing=1.26)

text(s, M, Inches(6.15), Inches(11.6), Inches(0.6),
     [[("None of this is your staff's fault. ", {"bold": True, "color": PLUM}),
       ("It is what happens when a clinic runs on six tools that were never "
        "designed to agree with each other.", {})]],
     size=14, spacing=1.3)
footer(s)

# ------------------------------------------------ 3. The flywheel (systems framing)
s = slide(prs)
y = eyebrow(s, "How it fits together")
heading(s, "One patient, one record, every step.", y + Inches(0.1))

steps = [
    ("She finds you", "A page that sells the treatment, the price and the branch."),
    ("She books", "A real slot, held for her, confirmed without a phone call."),
    ("She arrives", "Front desk sees the booking, the treatment and the notes."),
    ("She pays", "Cash, card, GCash or Maya, receipted at the counter."),
    ("Stock moves", "The bottle is drawn down and costed to that sale."),
    ("Books balance", "Revenue, cost and cash land in the ledger automatically."),
    ("She comes back", "The history is there, so the next visit needs no re-explaining."),
]
bw, gapx = Inches(1.52), Inches(0.12)
for i, (t, b) in enumerate(steps):
    x = M + i * (bw + gapx)
    dark = i % 2 == 0
    rect(s, x, Inches(2.5), bw, Inches(2.6), fill=PLUM if dark else WHITE,
         line=None if dark else RGBColor(0xE6, 0xDB, 0xE0))
    fg = IVORY if dark else PLUM
    sub = RGBColor(0xC0, 0xAF, 0xB7) if dark else TEXT
    text(s, x, Inches(2.72), bw, Inches(0.3), str(i + 1).zfill(2),
         size=11, color=CHAMPAGNE, bold=True, align=PP_ALIGN.CENTER)
    text(s, x + Inches(0.12), Inches(3.12), bw - Inches(0.24), Inches(0.5), t,
         size=13, color=fg, bold=True, align=PP_ALIGN.CENTER, spacing=1.1)
    text(s, x + Inches(0.12), Inches(3.78), bw - Inches(0.24), Inches(1.2), b,
         size=9.5, color=sub, align=PP_ALIGN.CENTER, spacing=1.22)

rect(s, M, Inches(5.55), W - 2 * M, Inches(1.0), fill=SOFT_ROSE)
text(s, M + Inches(0.4), Inches(5.78), Inches(11.2), Inches(0.6),
     [[("The point is not seven features. ", {"bold": True, "color": PLUM}),
       ("It is that step seven is only possible because steps one to six already "
        "happened in the same system. Most clinic software stops at step four.", {})]],
     size=13.5, spacing=1.3)
footer(s)
# ---------------------------------------------------- 4. The website earns its keep
# Demonstration beats assertion. Real screenshot, one benefit line.
s = slide(prs)
y = eyebrow(s, "Demand")
heading(s, "A site that works while you sleep.", y + Inches(0.1))
text(s, M, Inches(1.9), Inches(4.5), Inches(0.5),
     "It is the only part of the system a patient sees, so it does the "
     "one job that matters: get the booking.", size=13.5, color=TEXT, spacing=1.3)
bullets(s, M, Inches(2.75), Inches(4.6), [
    "Treatments, prices, doctors and branches live in the database, so you edit them without a developer.",
    "Live availability, so she books the slot she wants rather than the one you confirm by phone later.",
    "Every enquiry becomes a tracked lead with its source attached, including the campaign that sent it.",
    "Built to be found: local search markup, a sitemap, and pages that load fast on a phone.",
], size=13, gap=Inches(0.78))
picture(s, "01-public-home.png", Inches(6.1), CONTENT_TOP, Inches(6.35), Inches(3.9))
footer(s)

# --------------------------------------------------------- 5. Booking, in-app
s = slide(prs)
y = eyebrow(s, "Bookings")
heading(s, "No phone tag. No double-booked chair.", y + Inches(0.1))
picture(s, "04-public-booking.png", M, CONTENT_TOP, Inches(5.3), Inches(3.55))
picture(s, "11-public-home-mobile.png", Inches(6.6), CONTENT_TOP, Inches(2.3), Inches(3.55))
bullets(s, Inches(9.45), Inches(2.35), Inches(3.0), [
    "Slots shown are genuinely free, per doctor and per branch.",
    "Two staff cannot take the same chair at once.",
    "Booked as a guest. No account to create before she can book.",
    "Confirmation is a signed link she can reopen on her phone.",
], size=12, gap=Inches(0.8))
text(s, M, Inches(5.95), Inches(11.6), Inches(0.5),
     [[("Most of your no-shows are not rudeness. ", {"bold": True, "color": PLUM}),
       ("They are a booking made in a hurry that nobody wrote down.", {})]],
     size=13.5, spacing=1.3)
footer(s)

# ------------------------------------------------------------- 6. The front desk
# CRM is the emotional core: "who was going to call back?"
s = slide(prs)
y = eyebrow(s, "Front desk")
heading(s, "Nobody has to remember to call back.", y + Inches(0.1))
picture(s, "22b-admin-lead-detail.png", M, CONTENT_TOP, Inches(5.7), Inches(3.75))
text(s, Inches(6.95), Inches(2.25), Inches(5.4), Inches(0.7),
     "One person, one page: where she came from, what she asked about, "
     "what she booked, and who said what.", size=13.5, color=TEXT, spacing=1.3)
bullets(s, Inches(6.95), Inches(3.1), Inches(5.4), [
    "A nine-stage pipeline, so a lead is never just a name in a list.",
    "Every call and note is written down with the staff member who did it.",
    "Appointments and history sit on the same record as the enquiry.",
    "Know at a glance who is overdue for a follow-up.",
], size=13, gap=Inches(0.72))
text(s, Inches(6.95), Inches(6.2), Inches(5.4), Inches(0.5),
     [[("Demo data shown. ", {"bold": True, "color": ROSE_DARK}),
       ("Names and conversations are fictional.", {})]],
     size=10.5, color=MUTED)
footer(s)

# ----------------------------------------------------------------- 7. The day
s = slide(prs)
y = eyebrow(s, "The day at a glance")
heading(s, "Open the screen and know where the day stands.", y + Inches(0.1))
picture(s, "20-admin-dashboard.png", M, CONTENT_TOP, Inches(4.4), Inches(3.85))
text(s, Inches(5.6), Inches(2.25), Inches(6.7), Inches(0.6),
     "Revenue, gross profit, average sale, how people paid and what is "
     "best selling, against the same stretch last period.",
     size=13.5, color=TEXT, spacing=1.3)
bullets(s, Inches(5.6), Inches(3.05), Inches(6.7), [
    "The day's bookings first, with the current time marked on the list.",
    "Confirmed, done, pending and no-shows counted as you read.",
    "Changes to a booking status take effect immediately, no refresh.",
    "A payment mix of one method is shown as one method, not dressed up as a trend.",
], size=13, gap=Inches(0.7))
rect(s, Inches(5.6), Inches(5.95), Inches(6.7), Inches(0.75), fill=SOFT_ROSE)
text(s, Inches(5.9), Inches(6.12), Inches(6.1), Inches(0.5),
     "Gross profit here is revenue less the cost of goods only. "
     "It is not what is left after payroll. The screen says so.",
     size=11, color=PLUM, spacing=1.25)
footer(s)
# ------------------------------------------------------------------ 8. The till
s = slide(prs)
y = eyebrow(s, "Point of sale")
heading(s, "The sale is already a sale.", y + Inches(0.1))
picture(s, "24-admin-pos-sales.png", M, CONTENT_TOP, Inches(6.35), Inches(3.6))
text(s, Inches(7.4), Inches(2.25), Inches(5.0), Inches(0.6),
     "Treatments and retail on one screen, priced by the system, "
     "not by whoever is on the till.", size=13.5, color=TEXT, spacing=1.3)
bullets(s, Inches(7.4), Inches(3.0), Inches(5.0), [
    "Cash, card, bank transfer, GCash and Maya, with change given for cash only.",
    "A deposit leaves the balance open, so the front desk can chase it.",
    "Refunds return the stock and the money, and never exceed what was taken.",
    "A printable receipt per sale, with the client's details on it.",
], size=12.5, gap=Inches(0.68))
rect(s, Inches(7.4), Inches(5.95), Inches(5.0), Inches(0.7), fill=PLUM)
text(s, Inches(7.7), Inches(6.11), Inches(4.4), Inches(0.4),
     "A cashier cannot change a price. The catalogue decides.",
     size=11.5, color=IVORY, bold=True)
footer(s)

# -------------------------------------------------------------- 9. Inventory
# Fear of loss, concretely: expired stock is written-off money.
s = slide(prs)
y = eyebrow(s, "Inventory")
heading(s, "Know what is on the shelf, per branch.", y + Inches(0.1))
picture(s, "25-admin-inventory.png", M, CONTENT_TOP, Inches(6.1), Inches(3.5))
text(s, Inches(7.4), Inches(2.25), Inches(5.0), Inches(0.6),
     "Batches and expiry dates are the record. The count is kept in step "
     "with them.", size=13.5, color=TEXT, spacing=1.3)
bullets(s, Inches(7.4), Inches(3.0), Inches(5.0), [
    "Soonest expiry is used first, automatically.",
    "Expired, expiring soon, low and out are flagged before they cost you.",
    "Stock valued at cost, per branch, not one blended total.",
    "A sale cannot take the last unit while another till is selling it.",
], size=12.5, gap=Inches(0.68))
rect(s, Inches(7.4), Inches(5.95), Inches(5.0), Inches(0.7), fill=SOFT_ROSE)
text(s, Inches(7.7), Inches(6.07), Inches(4.4), Inches(0.5),
     "Every movement is a dated line, so a discrepancy has a name and a "
     "reason attached.", size=11, color=PLUM, spacing=1.25)
footer(s)

# ---------------------------------------------------------- 10. Staff & payroll
s = slide(prs)
y = eyebrow(s, "People")
heading(s, "Your team's month, worked out for you.", y + Inches(0.1))
picture(s, "26-admin-hr.png", M, CONTENT_TOP, Inches(5.9), Inches(2.75))
picture(s, "27-admin-payroll.png", Inches(7.0), CONTENT_TOP, Inches(5.35), Inches(2.75))
bullets(s, M, Inches(5.2), Inches(11.6), [
    "Attendance, leave and the schedule in one place per person.",
    "Payroll built from the rates you set, with SSS, PhilHealth and Pag-IBIG handled as configured.",
    "Loans and one-off deductions carried against a run until cleared, so nothing is retyped twice.",
    "Payroll posts to the books, so the wage is already an entry by the time you look.",
], size=12.5, gap=Inches(0.36))
footer(s)

# -------------------------------------------------------------- 11. Accounting
# The credibility slide. Show the balance, not a claim about it.
s = slide(prs)
y = eyebrow(s, "Accounting")
heading(s, "The books close because they balance.", y + Inches(0.1))
picture(s, "28b-admin-accounting-reports.png", M, CONTENT_TOP, Inches(5.6), Inches(3.2))
text(s, Inches(6.7), Inches(2.25), Inches(5.7), Inches(0.6),
     "Every sale, stock draw and payroll run writes its own balanced entry. "
     "Nothing is keyed twice.", size=13.5, color=TEXT, spacing=1.3)
bullets(s, Inches(6.7), Inches(3.0), Inches(5.7), [
    "Trial balance, income statement and balance sheet, all from the same journal.",
    "An entry that does not balance is refused before it is saved.",
    "A closed month cannot be posted into until you reopen it.",
    "A wrong entry is voided with a reason, never quietly edited.",
], size=12.5, gap=Inches(0.66))
rect(s, Inches(6.7), Inches(5.85), Inches(5.7), Inches(0.85), fill=PLUM)
text(s, Inches(7.0), Inches(6.03), Inches(5.1), Inches(0.6),
     "If the books do not balance, the screen says so in plain words "
     "instead of showing you a tidy table.",
     size=11.5, color=IVORY, spacing=1.25)
footer(s)

# ----------------------------------------------------------------- 12. Control
# Permissioning answers the owner's real fear: staff seeing the books.
s = slide(prs, PLUM)
y = eyebrow(s, "Control", color=CHAMPAGNE)
heading(s, "Your receptionist does not need to see your margin.", y + Inches(0.1),
        color=IVORY)
rect(s, M, Inches(2.2), Inches(0.16), Inches(3.0), fill=ROSE)
text(s, M + Inches(0.5), Inches(2.2), Inches(5.5), Inches(2.4),
     "Access is granted by role, screen by screen. A cashier works the till. "
     "A branch manager moves stock. A payroll officer runs pay. "
     "The owner's books stay with the owner.",
     size=16, color=IVORY, spacing=1.42)
bullets(s, M + Inches(0.5), Inches(4.35), Inches(5.6), [
    "Every sensitive action is written to an audit trail with who and when.",
    "Health information is kept out of the marketing records entirely.",
    "Consent is captured at the point of enquiry and visible on the record.",
], size=13, gap=Inches(0.72), color=RGBColor(0xD9, 0xC7, 0xCE), dot=CHAMPAGNE)
text(s, Inches(7.1), Inches(2.3), Inches(5.3), Inches(0.4),
     "Who sees what", size=12, color=CHAMPAGNE, bold=True, caps=True)
roles = [
    ("Reception", "Bookings and enquiries. No takings, no payroll."),
    ("Cashier", "The till, refunds and stock on hand."),
    ("Branch manager", "Adjustments, transfers, the branch's numbers."),
    ("Payroll", "Runs and payslips. Not the revenue report."),
    ("Owner", "Everything, including the trial balance."),
]
for i, (r, d) in enumerate(roles):
    yy = Inches(2.85) + i * Inches(0.62)
    rect(s, Inches(7.1), yy, Pt(2.5), Inches(0.44), fill=ROSE)
    text(s, Inches(7.35), yy, Inches(1.75), Inches(0.35), r,
         size=12.5, color=IVORY, bold=True)
    text(s, Inches(9.15), yy + Inches(0.02), Inches(3.3), Inches(0.5), d,
         size=11, color=RGBColor(0xB0, 0x9C, 0xA5), spacing=1.2)
text(s, Inches(7.1), Inches(6.15), Inches(5.3), Inches(0.4),
     "Role names are yours to set. This is the default shape.",
     size=10.5, color=RGBColor(0x8A, 0x7B, 0x81))
# ------------------------------------------------ 13. What this is not (integrity)
# Saying no builds more trust than another claim, and this project forbids
# medical and BIR claims outright (CLAUDE.md).
s = slide(prs)
y = eyebrow(s, "Straight with you")
heading(s, "What this system does not do.", y + Inches(0.1))
nots = [
    ("It makes no medical claims", "No guaranteed results, no safety promises. It manages a clinic; it does not treat one."),
    ("It is not a bookkeeping certificate", "We will not call it BIR compliant. It keeps clean books; your accountant signs them."),
    ("It does not replace your bookkeeper", "It produces the ledger and the statements. A professional reviews and files them."),
    ("The demo data is not your revenue", "Figures in this deck come from a seeded demo clinic, not from a real clinic."),
    ("Before and after images are illustrations", "Stock photography. They are not patient results and are labelled as such."),
]
yy = Inches(2.15)
for t, d in nots:
    rect(s, M, yy, Inches(11.6), Inches(0.72), fill=WHITE,
         line=RGBColor(0xE6, 0xDB, 0xE0))
    rect(s, M, yy, Pt(3), Inches(0.72), fill=CHAMPAGNE)
    text(s, M + Inches(0.3), yy + Inches(0.11), Inches(4.3), Inches(0.5), t,
         size=13, color=PLUM, bold=True)
    text(s, M + Inches(4.8), yy + Inches(0.13), Inches(6.6), Inches(0.5), d,
         size=11.5, color=TEXT, spacing=1.22)
    yy += Inches(0.8)
text(s, M, Inches(6.25), Inches(11.6), Inches(0.4),
     "We would rather lose the sale than win it with a claim you cannot defend.",
     size=13, color=ROSE_INK, bold=True)
footer(s)

# ------------------------------------------------------------------ 14. Fit
s = slide(prs)
y = eyebrow(s, "Is this you?")
heading(s, "This fits if three of these are true today.", y + Inches(0.1))
bullets(s, M, Inches(2.35), Inches(5.9), [
    "You have more than one branch, or plan to.",
    "You sell treatments and retail from the same counter.",
    "You want the owner, the receptionist and the accountant seeing one set of numbers.",
    "You are tired of exporting to a spreadsheet to find out how the month went.",
    "You want to add a location without buying a second system.",
], size=14.5, gap=Inches(0.82))
rect(s, Inches(7.3), Inches(2.35), Inches(5.05), Inches(3.9), fill=SOFT_ROSE)
text(s, Inches(7.65), Inches(2.7), Inches(4.4), Inches(0.4),
     "And it does not fit if", size=12, color=ROSE_DARK, bold=True, caps=True)
bullets(s, Inches(7.65), Inches(3.25), Inches(4.4), [
    "You want a website and nothing else.",
    "Clinical notes are the priority. This is the clinic around them, not the chart.",
    "You need it working offline in a rural branch today.",
], size=12.5, gap=Inches(0.92))
text(s, M, Inches(6.42), Inches(11.6), Inches(0.4),
     "Better to say this now than after you have paid for it.",
     size=12, color=MUTED)
footer(s)

# ----------------------------------------------------------------- 15. Next step
# Low-friction ask (activation energy). One concrete action, not "get in touch".
s = slide(prs, PLUM)
rect(s, 0, 0, Inches(0.16), H, fill=ROSE)
text(s, M, Inches(1.0), Inches(9), Inches(0.4),
     "Where this goes next", size=12, color=CHAMPAGNE, bold=True, caps=True)
text(s, M, Inches(1.5), Inches(10.5), Inches(1.4),
     "Bring your own numbers.", size=40, color=IVORY, font=DISPLAY)
text(s, M, Inches(2.5), Inches(9.5), Inches(0.8),
     "The demo runs on a fictional clinic. The fastest way to judge this is to "
     "load your real treatments, prices and staff, then look at your own month "
     "on it.", size=15, color=RGBColor(0xD9, 0xC7, 0xCE), spacing=1.35)

nexts = [
    ("1", "See it running", "A walkthrough on your own branch data. Half an hour, no slides."),
    ("2", "Load your catalogue", "Treatments, prices, doctors and stock imported, then checked by you."),
    ("3", "Run one month alongside", "Keep your current process live while this settles in."),
    ("4", "Then decide", "If it has not earned its place by month two, that is a fair reason to stop."),
]
for i, (n, t, d) in enumerate(nexts):
    col, row = i % 2, i // 2
    x = M + col * Inches(5.95)
    yy = Inches(3.65) + row * Inches(1.32)
    rect(s, x, yy, Inches(5.6), Inches(1.1), fill=RGBColor(0x4A, 0x2F, 0x3D))
    text(s, x + Inches(0.3), yy + Inches(0.22), Inches(0.5), Inches(0.4), n,
         size=18, color=CHAMPAGNE, font=DISPLAY)
    text(s, x + Inches(0.85), yy + Inches(0.18), Inches(4.5), Inches(0.35), t,
         size=14, color=IVORY, bold=True)
    text(s, x + Inches(0.85), yy + Inches(0.56), Inches(4.5), Inches(0.5), d,
         size=11, color=RGBColor(0xB0, 0x9C, 0xA5), spacing=1.2)

text(s, M, Inches(6.55), Inches(11.6), Inches(0.5),
     "Live demo: irish.deskpulse.click   ·   28 Amorsolo Street, Legaspi Village, Makati City",
     size=11.5, color=RGBColor(0x9A, 0x88, 0x90))
# The ask ends on the last slide, so this is where the studio puts its name to it.
frog_lockup(s, W - M, Inches(6.44), Inches(0.46),
            color=RGBColor(0xB0, 0x9C, 0xA5), size=10)

prs.save(OUT)
print("saved %s  (%d slides)" % (OUT, len(prs.slides._sldIdLst)))

# Guard rail. Every shape is placed by hand, so the failure mode is a box that
# runs off the slide or collides with the footer rather than a Python error.
# Assert the geometry here instead of squinting at 15 rendered slides.
problems = []
for idx, sl in enumerate(prs.slides, start=1):
    for sh in sl.shapes:
        try:
            l, t, w, h = sh.left, sh.top, sh.width, sh.height
        except (AttributeError, TypeError):
            continue  # placeholders and freeforms carry no box
        if l is None or t is None or w is None or h is None:
            continue
        if l < -9525 or t < -9525:
            problems.append("slide %d: %s starts off-canvas" % (idx, sh.shape_type))
        if l + w > W + 9525:
            problems.append("slide %d: overflows right edge by %d EMU" % (idx, l + w - W))
        if t + h > H + 9525:
            problems.append("slide %d: overflows bottom edge by %d EMU" % (idx, t + h - H))

print("geometry check: %s" % ("clean" if not problems else problems))

# Read the saved file back rather than trusting the objects still in memory:
# this is what the client opens.
check = Presentation(OUT)
pics = sum(1 for sl in check.slides for sh in sl.shapes if sh.shape_type == 13)
print("verified: %d slides, %d pictures" % (len(check.slides._sldIdLst), pics))

all_text = " ".join(
    sh.text_frame.text for sl in check.slides for sh in sl.shapes
    if sh.has_text_frame
).lower()
for banned in ["guaranteed results", "bir compliant", "100% safe", "cure ",
               "permanent result", "beste"]:
    if banned in all_text:
        # A phrase inside a sentence that refuses the claim is the opposite of
        # making it, so print the context rather than a bare alarm.
        i = all_text.index(banned)
        print("   %r in: ...%s..." % (banned, all_text[i - 60:i + 45]))
print("claims check: done")

for i, sl in enumerate(check.slides, start=1):
    lines = [sh.text_frame.text.strip() for sh in sl.shapes
             if sh.has_text_frame and sh.text_frame.text.strip()]
    head = next((t for t in lines if len(t) > 18), lines[0] if lines else "")
    npic = sum(1 for sh in sl.shapes if sh.shape_type == 13)
    print("%2d. [%s] %s" % (i, "img%d" % npic if npic else "   ", head[:68]))

# Overflow estimate. No renderer is available here, so approximate: assume an
# average glyph is ~0.5em wide for the body face, wrap into the box width, then
# compare the resulting line count against the box height. Crude on purpose. It
# is here to catch a paragraph that would run into the shape below it, not to
# be a typesetting engine; flagged boxes are the ones worth eyeballing in PowerPoint.
CHAR_W = 0.5
risky = []
for i, sl in enumerate(check.slides, start=1):
    for sh in sl.shapes:
        if not sh.has_text_frame or not sh.text_frame.text.strip():
            continue
        w_in, h_in = sh.width / 914400, sh.height / 914400
        for para in sh.text_frame.paragraphs:
            txt = "".join(r.text for r in para.runs)
            if not txt.strip():
                continue
            size = max((r.font.size.pt for r in para.runs
                        if r.font.size is not None), default=18)
            per_line = max(1, int(w_in / (size * CHAR_W / 72)))
            lines = max(1, -(-len(txt) // per_line))
            need = lines * (size * 1.25 / 72)
            if need > h_in * 1.55:
                risky.append((i, round(need, 2), round(h_in, 2), txt[:48]))
for r in risky:
    print("   tight on slide %d: needs ~%.2f\" in %.2f\" :: %s" % r)
print("overflow estimate: %d box(es) to eyeball" % len(risky))
