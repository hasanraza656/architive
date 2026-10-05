/**
 * Builds the optimized portfolio imagery from the client's source files.
 *
 *   node --experimental-wasm-eh tools/build-work-assets.mjs
 *
 * Needs (install outside the project, e.g. in a scratch folder): `mupdf` and `sharp` (0.32.x on Node 16).
 * Source files live in the client folder (not in this repo); output goes to public/assets/img/work/
 * and the size manifest to resources/data/work-manifest.json (read by app/Support/Portfolio.php).
 *
 * PRIVACY: permit/BIM/case-study sheets contain real addresses, owner names, contractor contact details and a
 * client logo in their title blocks. Every sheet is therefore (1) cropped/whited-out over the title block and
 * (2) scanned line-by-line for specific names/addresses which are whited-out too. Cover sheets and site plans
 * are never published. If you add new sheets, re-check the output visually before committing.
 */
import fs from "fs";
import path from "path";
import { createRequire } from "module";
import { pathToFileURL } from "url";

const DEPS = process.env.ASSET_DEPS ? process.env.ASSET_DEPS.split("\\").join("/").replace(/\/+$/, "") : null;   // folder containing node_modules/{mupdf,sharp}
const require = createRequire(DEPS ? DEPS + "/" : import.meta.url);
const sharp = require("sharp");
const mupdf = DEPS ? await import(pathToFileURL(DEPS + "/node_modules/mupdf/dist/mupdf.js").href) : await import("mupdf");

const CLIENT = process.env.CLIENT_DIR || "F:/bhai log/Architive designs/client data/";
const VIS = CLIENT + "architive website update visuals/architive website update visuals/";
const PERMIT = CLIENT + "Architive_CAD_and_Permit_Samples/CAD_and_Permit_Samples/";
const BIM = VIS + "Revit and Bim/Architive_Revit_and_BIM_Samples/Revit_and_BIM_Samples/";
const CASES = VIS + "Case studies/";
const SERVICE1 = VIS + "Service  1 Architectural visualisation/";
const OUT = path.resolve("public/assets/img/work") + "/";
const manifest = {};

// Specific names / addresses that must never appear in published sheets.
const PRIVATE = /THOUSAND OAKS|TOAKS|BUILDINGHANDOUTS|B-2d|ERICB|SAMTHE|JFC|BETSY|HERNDON|FERNANDES|MANASSAS|\(703\)|DAIGLE|WATER STREET|MIRAMICHI|ALEX MANUEL|STRONGS|RUTLAND|MAVIS|GLENGARRY|PHILADELPHIA|ERNST|ERNS-|CAMELBACK|PHOENIX|HIGHVIEW|REDLANDS|SAPRA|GOSHEN|ROLLING RIDGE|GOSHEN|\b\d{5}(-\d{4})?\b/i;

const sets = [
  { id: "ny", file: PERMIT + "01_Residential_Addition_Permit_Set_New_York.pdf", whiten: [[0.81, 0.76, 1, 1]], width: 2200, sheets: [
    [2, "main-level-plan", "A1.1"], [3, "roof-plan", "A1.3"], [4, "elevations-front-back", "A2.1"], [5, "elevations-right-left", "A2.2"],
    [6, "building-section", "A2.3"], [7, "architectural-details", "A5.1"], [8, "door-window-details", "A6.2"]] },
  { id: "caa", file: PERMIT + "02_Residential_Addition_Permit_Set_California.pdf", whiten: [[0.81, 0.76, 1, 1]], width: 2200, sheets: [
    [2, "main-floor-plan", "A1.1"], [3, "roof-plan", "A1.2"], [4, "elevations-left-right", "A2.1"], [5, "elevations-front-back", "A2.2"], [6, "electrical-plan", "E1.2"]] },
  { id: "car", file: PERMIT + "03_Residential_Remodel_Permit_Set_California.pdf", whiten: [[0.81, 0.76, 1, 1]], width: 2200, sheets: [
    [3, "proposed-main-level-plan", "A2.2"], [2, "roof-plan", "A1.3"], [4, "elevations-left-front", "A2.3"], [5, "elevations-right-back", "A2.4"], [6, "kitchen-plan-elevation", "A2.5"]] },
  { id: "va", file: PERMIT + "04_Sunroom_Addition_Permit_Drawings_Virginia.pdf", crop: [0, 0, 0.80, 1], width: 2000, sheets: [
    [0, "main-level-plan", "A1"], [1, "roof-plan", "A2"], [2, "exterior-elevations", "A3"]] },
  { id: "vt", file: BIM + "01_Commercial_Scan_to_BIM_Existing_Conditions_Vermont.pdf", crop: [0.04, 0.04, 0.876, 0.96], width: 2200, sheets: [
    [3, "level-1-floor-plan", "A103"], [4, "level-2-floor-plan", "A104"], [15, "exterior-elevations", "A115"], [17, "building-sections", "A117"]] },
  { id: "pa", file: BIM + "02_Residential_Scan_to_BIM_Existing_Conditions_Pennsylvania.pdf", crop: [0.04, 0.04, 0.876, 0.96], width: 2200, sheets: [
    [3, "level-1-floor-plan", "A103"], [2, "basement-floor-plan", "A102"], [4, "level-2-floor-plan", "A104"], [6, "exterior-elevations", "A106", { crop: [0.02, 0, 0.876, 1] }]] },
  { id: "az", file: BIM + "03_Commercial_Revit_Existing_Conditions_Arizona.pdf", crop: [0.04, 0.04, 0.876, 0.96], width: 2200, sheets: [
    [1, "level-1-floor-plan", "A102"], [2, "level-2-floor-plan", "A103"], [0, "basement-floor-plan", "A101"], [6, "building-sections", "A107"]] },
  { id: "manuel", file: CASES + "case study 3 Alex Revit model canada/Double Storey House PDF.pdf", crop: [0.04, 0.08, 0.86, 0.92], width: 1900, sheets: [
    [1, "front-elevation", "A101"], [5, "basement-floor-plan", "A105"], [6, "main-floor-plan", "A106"], [7, "upper-floor-plan", "A107"],
    [9, "building-section-a-a", "A108"], [8, "building-section-b-b", "A109"]] },
];
// Case-study title sheet: only the 3D house is published (not the name/address/logo text).
const manuelHero = { file: sets[7].file, page: 0, crop: [0.40, 0.24, 0.93, 0.80], width: 1500, name: "cases/manuel-house-3d" };

async function webp(buf, base, widths) {
  fs.mkdirSync(path.dirname(OUT + base), { recursive: true });
  const meta = {};
  for (const [suffix, w, q] of widths) {
    const img = sharp(buf, { limitInputPixels: false }).resize({ width: w, withoutEnlargement: true });
    const info = await img.webp({ quality: q, effort: 5 }).toFile(OUT + base + suffix + ".webp");
    meta[suffix || "full"] = { w: info.width, h: info.height, kb: Math.round(info.size / 1024) };
  }
  manifest[base] = { w: meta.full.w, h: meta.full.h, tw: meta["-800"].w, th: meta["-800"].h };
  return meta;
}

async function renderPage(doc, pageIdx, { crop, whiten = [], width }) {
  const page = doc.loadPage(pageIdx); const b = page.getBounds(); const PW = b[2] - b[0], PH = b[3] - b[1];
  const c = crop || [0, 0, 1, 1];
  const scale = width / (PW * (c[2] - c[0]));
  const pix = page.toPixmap(mupdf.Matrix.scale(scale, scale), mupdf.ColorSpace.DeviceRGB, false, true);
  const W = pix.getWidth(), H = pix.getHeight();
  const rects = whiten.map(([x0, y0, x1, y1]) => ({ left: Math.round(x0 * W), top: Math.round(y0 * H), width: Math.round((x1 - x0) * W), height: Math.round((y1 - y0) * H) }));
  // regex line redaction (names / addresses)
  const j = JSON.parse(page.toStructuredText("preserve-whitespace").asJSON());
  const redacted = [];
  for (const bl of j.blocks || []) for (const ln of bl.lines || []) {
    const t = (ln.text || "").trim();
    if (t && PRIVATE.test(t)) {
      const x = ln.bbox.x / PW, y = ln.bbox.y / PH, w = ln.bbox.w / PW, h = ln.bbox.h / PH;
      redacted.push(t.slice(0, 40));
      rects.push({ left: Math.max(0, Math.round((x - 0.002) * W)), top: Math.max(0, Math.round((y - 0.0015) * H)), width: Math.round((w + 0.004) * W), height: Math.round((h + 0.003) * H) });
    }
  }
  let img = sharp(pix.asPNG());
  const overlays = rects.filter(r => r.width > 0 && r.height > 0 && r.left < W && r.top < H).map(r => ({
    input: { create: { width: Math.min(r.width, W - r.left), height: Math.min(r.height, H - r.top), channels: 3, background: "#ffffff" } }, left: r.left, top: r.top }));
  if (overlays.length) img = sharp(await img.composite(overlays).png().toBuffer());
  if (crop) {
    const l = Math.round(c[0] * W), t = Math.round(c[1] * H);
    img = img.extract({ left: l, top: t, width: Math.min(W - l, Math.round((c[2] - c[0]) * W)), height: Math.min(H - t, Math.round((c[3] - c[1]) * H)) });
  }
  return { buf: await img.png().toBuffer(), redacted };
}

async function main() {
  fs.rmSync(OUT, { recursive: true, force: true });
  // 1) PDF sheets
  for (const s of sets) {
    const doc = mupdf.Document.openDocument(fs.readFileSync(s.file), "application/pdf");
    let n = 0;
    for (const [pg, slug, , extra] of s.sheets) {
      n++;
      const { buf, redacted } = await renderPage(doc, pg, { ...s, ...(extra || {}) });
      const base = `sets/${s.id}/${String(n).padStart(2, "0")}-${slug}`;
      const m = await webp(buf, base, [["", s.width > 2000 ? 2000 : s.width, 80], ["-800", 800, 78]]);
      console.log(base, m.full.w + "x" + m.full.h, m.full.kb + "KB", "thumb", m["-800"].kb + "KB", redacted.length ? "redacted: " + redacted.join(" | ") : "");
    }
  }
  {
    const doc = mupdf.Document.openDocument(fs.readFileSync(manuelHero.file), "application/pdf");
    const { buf } = await renderPage(doc, manuelHero.page, manuelHero);
    const m = await webp(buf, manuelHero.name, [["", 1500, 82], ["-800", 800, 80]]);
    console.log(manuelHero.name, m.full.w + "x" + m.full.h, m.full.kb + "KB");
  }
  // 2) Visualization renders (client's own work)
  const viz = [
    ["viz/bandon-dusk-lawn", "bandon exterior v2 .png"], ["viz/bandon-dusk-wrap", "bandon v1 side .png"], ["viz/bandon-night-black", "bandon v2 black .png"],
    ["viz/brick-mixed-use", "exterior .jpg"], ["viz/commercial-options", "exterior commercial.png"], ["viz/bungalow-front", "front exterior .png"], ["viz/house-portrait", "Exterior 1.jpg"],
    ["viz/bedroom", "Bed 03.tif"], ["viz/dining-room", "Dining Room.jpg"], ["viz/kitchen-dining", "Kitchen + Dinning.jpg"], ["viz/lounge", "interior.jpg"], ["viz/kitchen-plan", "kitchen linkedin.jpg"],
    ["viz/floorplan-3d-retail", "3D floor plan v2 .png"], ["viz/floorplan-3d-top", "3D floor paln V3.png"], ["viz/floorplan-iso", "iso view 02 (12).png"],
    ["viz/site-aerial", "Top Correction.jpg"], ["viz/booth-concept", "booth sheen without brand .png"],
  ];
  for (const [base, file] of viz) {
    const m = await webp(fs.readFileSync(SERVICE1 + file), base, [["", 1600, 76], ["-800", 800, 74]]);
    console.log(base, m.full.w + "x" + m.full.h, m.full.kb + "KB", "thumb", m["-800"].kb + "KB");
  }
  // 3) Case-study boards
  const boards = [["cases/bonderud-kitchen-plan-to-render", CASES + "case study 1 san francisco/Kitchen visualization from plan to render.png"],
    ["cases/fifa-three-level-circulation", CASES + "case study 2 Cad Vesti event fifa 2026/Three-level circulation plan study.png"]];
  for (const [base, file] of boards) {
    const m = await webp(fs.readFileSync(file), base, [["", 1672, 80], ["-800", 800, 78]]);
    console.log(base, m.full.w + "x" + m.full.h, m.full.kb + "KB");
  }
  // 4) Scan-to-BIM before/after (cropped from the client's two JPG boards)
  const s1 = fs.readFileSync(VIS + "Revit and Bim/scan to bim 1.jpg");
  const crops = [
    ["scan/house-1-cloud", s1, { left: 119, top: 31, width: 730, height: 414 }], ["scan/house-1-model", s1, { left: 920, top: 31, width: 730, height: 414 }],
    ["scan/house-2-cloud", s1, { left: 119, top: 495, width: 730, height: 330 }], ["scan/house-2-model", s1, { left: 920, top: 495, width: 726, height: 330 }],
  ];
  const s2 = fs.readFileSync(VIS + "Revit and Bim/SCAN TO BIM 2 .jpg");
  crops.push(["scan/facade-cloud", s2, { left: 70, top: 185, width: 622, height: 353 }], ["scan/facade-model", s2, { left: 1058, top: 165, width: 676, height: 387 }]);
  for (const [base, src, rect] of crops) {
    const buf = await sharp(src).extract(rect).png().toBuffer();
    const m = await webp(buf, base, [["", 1600, 84], ["-800", 800, 80]]);
    console.log(base, m.full.w + "x" + m.full.h, m.full.kb + "KB");
  }
  fs.mkdirSync("resources/data", { recursive: true });
  fs.writeFileSync("resources/data/work-manifest.json", JSON.stringify(manifest, null, 1));
  console.log("manifest entries:", Object.keys(manifest).length);
}
main();
