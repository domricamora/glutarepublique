/*
 * Builds the 1200 x 630 social share images (Open Graph) for the public pages,
 * every active treatment and every journal post, in the brand fonts and colours,
 * into public/media/og/<key>.jpg. SeoComposer points og:image at them.
 *
 * 1. php artisan tinker export of the card list to storage/app/og-pages.json
 *    (see SESSION_STATE.md, "OG images")
 * 2. PLAYWRIGHT_CORE=<path to playwright-core> CHROME=<path to chrome.exe> node scripts/og-images.cjs
 *
 * Rendered by a real browser so the web fonts (Noto Serif Display, Geist) are
 * the ones the site uses; no font files are copied or converted.
 */
const fs = require('fs');
const path = require('path');
const { pathToFileURL } = require('url');
const { chromium } = require(process.env.PLAYWRIGHT_CORE || 'playwright-core');

const root = path.resolve(__dirname, '..');
const cards = JSON.parse(fs.readFileSync(path.join(root, 'storage/app/og-pages.json'), 'utf8'));
const out = path.join(root, 'public/media/og');
const file = (p) => pathToFileURL(path.join(root, p)).href;
const serif = file('node_modules/@fontsource-variable/noto-serif-display/files/noto-serif-display-latin-wght-normal.woff2');
const sans = file('node_modules/@fontsource-variable/geist/files/geist-latin-wght-normal.woff2');
const logo = file('public/media/brand/logo-light.png');
const escape = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);

const html = (card) => `<!doctype html><html><head><style>
@font-face { font-family: 'GR Display'; src: url(${serif}) format('woff2'); font-weight: 100 900; }
@font-face { font-family: 'GR Body'; src: url(${sans}) format('woff2'); font-weight: 100 900; }
* { margin: 0; box-sizing: border-box; }
body { width: 1200px; height: 630px; overflow: hidden; background: #406480; }
.photo { position: absolute; inset: 0; background: url(${pathToFileURL(card.image).href}) center / cover; }
.shade { position: absolute; inset: 0; background: linear-gradient(100deg, rgba(47,75,97,0.96) 0%, rgba(47,75,97,0.86) 46%, rgba(47,75,97,0.15) 100%); }
.inner { position: absolute; inset: 0; padding: 64px 72px; display: flex; flex-direction: column; justify-content: space-between; color: #fff; }
.brand { display: flex; align-items: center; gap: 18px; font: 600 26px 'GR Body'; letter-spacing: 0.01em; }
.brand img { height: 64px; }
h1 { font: 300 ${card.title.length > 38 ? 60 : 76}px/1.04 'GR Display'; max-width: 760px; }
p { margin-top: 22px; font: 400 28px/1.35 'GR Body'; color: #cde7e5; max-width: 720px; }
.foot { display: flex; justify-content: space-between; align-items: center; font: 500 22px 'GR Body'; color: #e3edf4; }
.bar { width: 120px; height: 4px; background: #6ab5b1; }
</style></head><body>
<div class="photo"></div><div class="shade"></div>
<div class="inner">
  <div class="brand"><img src="${logo}" alt=""> Gluta RepubliQ Aesthetics</div>
  <div><h1>${escape(card.title)}</h1><p>${escape(card.subtitle)}</p></div>
  <div class="foot"><span class="bar"></span><span>Cebu · San Pedro · Bacoor</span></div>
</div></body></html>`;

(async () => {
    fs.mkdirSync(out, { recursive: true });
    const browser = await chromium.launch({ executablePath: process.env.CHROME });
    const page = await browser.newPage({ viewport: { width: 1200, height: 630 } });

    for (const card of cards) {
        const tmp = path.join(out, `.${card.key}.html`);
        fs.writeFileSync(tmp, html(card));
        await page.goto(pathToFileURL(tmp).href, { waitUntil: 'load' });
        await page.evaluate(() => document.fonts.ready);
        await page.screenshot({ path: path.join(out, `${card.key}.jpg`), type: 'jpeg', quality: 84 });
        fs.unlinkSync(tmp);
    }

    await browser.close();
    console.log(`${cards.length} images in public/media/og`);
})();
