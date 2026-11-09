/**
 * SUPERSEDED for the drawing sets (client feedback: cover thumbnails only, see tools/build-covers-nov9.mjs; running the sets part again would re-create full sheets that must NOT be published).
 * Still used for the AutoCAD before/after pairs (cad/*).
 * ADDITIVE asset build for the client's 9 Nov 2026 update (permit sets, shop drawings, AutoCAD before/after).
 * Unlike build-work-assets.mjs it never deletes anything: it only writes the new folders and merges the new entries into
 * resources/data/work-manifest.json.
 *
 *   ASSET_DEPS=<folder with node_modules/{mupdf,sharp}> node tools/build-nov9-assets.mjs
 *
 * Source (outside the repo): client data/changes 9 nov 2026/v2/{Bim website update,Cad website update}/*.pdf
 * PRIVACY: the client supplied these PDFs with title blocks already blurred. We additionally crop the title-block strip
 * on those sets and skip cover sheets / site plans / photos. Check every new sheet visually before committing.
 */
import fs from "fs";
import path from "path";
import { createRequire } from "module";
import { pathToFileURL } from "url";

const DEPS = process.env.ASSET_DEPS ? process.env.ASSET_DEPS.split("\\").join("/").replace(/\/+$/, "") : null;
const require = createRequire(DEPS ? DEPS + "/" : import.meta.url);
const sharp = require("sharp");
const mupdf = DEPS ? await import(pathToFileURL(DEPS + "/node_modules/mupdf/dist/mupdf.js").href) : await import("mupdf");

const SRC = process.env.CLIENT_DIR || "F:/BHAI LOG/More Projects/architive/client data/changes 9 nov 2026/v2/";
const BIM = SRC + "Bim website update/";
const CAD = SRC + "Cad website update/";
const OUT = path.resolve("public/assets/img/work") + "/";
const MANIFEST = path.resolve("resources/data/work-manifest.json");
const manifest = JSON.parse(fs.readFileSync(MANIFEST, "utf8"));

// pages are 0-based PDF indexes; page 0 of every file is the client's own cover slide (not published as a sheet)
const sets = [
  // page, slug, optional { crop, width, whiten } ; whiten = rectangles [x0,y0,x1,y1] as fractions of the FULL page
  { id: "adr", file: BIM + "Adaptive_Reuse_Permit_Set_Blurred.pdf", crop: [0.003, 0, 0.866, 1], width: 2000, sheets: [
    [16, "existing-floor-plans"],
    [18, "proposed-floor-plans", { whiten: [[0.7085, 0.3672, 0.7470, 0.3742], [0.7100, 0.4735, 0.7475, 0.4800], [0.6280, 0.7620, 0.6625, 0.7675], [0.7080, 0.7638, 0.7455, 0.7698], [0.7545, 0.8420, 0.7880, 0.8520]] }],
    [24, "exterior-elevations"], [46, "building-section"], [8, "window-types-schedule"], [6, "wall-types"], [35, "interior-elevations"], [47, "wall-section-details"], [13, "stair-railing-details"]] },
  { id: "food", file: BIM + "Food_Service_Permit_Set_Blurred.pdf", crop: [0.003, 0, 0.925, 1], width: 2000, sheets: [
    [6, "equipment-plan"], [8, "plumbing-plan"], [7, "electrical-plan"], [9, "slab-penetration-plan"], [13, "equipment-elevations"], [14, "exhaust-hood-details"]] },
  { id: "drv", file: BIM + "Driver_Room_Shop_Drawings_Blurred.pdf", crop: [0.003, 0, 0.945, 1], width: 2000, sheets: [
    [1, "3d-model", { crop: [0.12, 0.34, 0.88, 0.97], width: 1500 }], [3, "floor-plan-schedules"], [9, "exterior-elevations"], [12, "sections"],
    [16, "mep-plan-schematic"], [19, "mep-3d-details"], [24, "roof-structural-plan"], [26, "structural-3d-details"]] },
  { id: "res", file: BIM + "Roberts_Complete_Portfolio_PDF.pdf", crop: [0, 0, 1, 1], width: 2000, sheets: [
    [2, "front-elevation"], [3, "rear-elevation"], [7, "foundation-plan"], [9, "main-level-plan-doors-windows"], [10, "main-level-plan-interior-walls"], [11, "building-section"], [13, "exterior-doors-schedule"], [15, "roof-plan"]] },
  { id: "adu", file: CAD + "ADU_Complete_Portfolio_PDF.pdf", crop: [0.062, 0.115, 0.912, 0.865], width: 2000, sheets: [
    [3, "floor-plans"], [4, "floor-plans-furnished"], [5, "roof-layout-and-section"], [6, "elevations-front-left"], [7, "elevations-right-rear"], [8, "electrical-hvac-plans"], [9, "plumbing-plans"], [11, "foundation-plan"]] },
  { id: "com", file: BIM + "Commercial_Complete_Portfolio_PDF.pdf", crop: [0, 0, 1, 1], width: 2000, sheets: [
    [2, "basement-floor-plans"], [3, "level-1-floor-plan"], [4, "level-2-floor-plan"], [6, "level-1-reflected-ceiling-plan"], [15, "exterior-elevations"], [17, "building-sections"]] },
];

// AutoCAD before/after pairs, cut from the client's slides (panel positions measured on a 1400 px wide render)
const PAIRS = [
  { id: "adu-upper", file: CAD + "ADU_Complete_Portfolio_PDF.pdf", page: 0 },
  { id: "adu-both", file: CAD + "ADU_Complete_Portfolio_PDF.pdf", page: 1 },
  { id: "germany", file: CAD + "German_Complete_Portfolio_PDF.pdf", page: 0 },
  { id: "uk-survey", file: CAD + "Survey_Complete_Portfolio_PDF.pdf", page: 0 },
  { id: "uk-marked", file: CAD + "Survey_Complete_Portfolio_PDF.pdf", page: 1 },
];
const PANEL = { before: [84, 207, 636, 761], after: [764, 207, 1316, 761], ref: 1400 };

async function webp(buf, base, widths) {
  fs.mkdirSync(path.dirname(OUT + base), { recursive: true });
  const meta = {};
  for (const [suffix, w, q] of widths) {
    const info = await sharp(buf, { limitInputPixels: false }).resize({ width: w, withoutEnlargement: true }).webp({ quality: q, effort: 5 }).toFile(OUT + base + suffix + ".webp");
    meta[suffix || "full"] = { w: info.width, h: info.height, kb: Math.round(info.size / 1024) };
  }
  manifest[base] = { w: meta.full.w, h: meta.full.h, tw: meta["-800"].w, th: meta["-800"].h };
  return meta;
}

async function render(doc, pageIdx, crop, width, whiten = []) {
  const page = doc.loadPage(pageIdx); const b = page.getBounds(); const PW = b[2] - b[0];
  const c = crop || [0, 0, 1, 1];
  const scale = width / (PW * (c[2] - c[0]));
  const pix = page.toPixmap(mupdf.Matrix.scale(scale, scale), mupdf.ColorSpace.DeviceRGB, false, true);
  const W = pix.getWidth(), H = pix.getHeight();
  const l = Math.round(c[0] * W), t = Math.round(c[1] * H);
  let img = sharp(pix.asPNG());
  const rects = whiten.map(([x0, y0, x1, y1]) => ({ left: Math.round(x0 * W), top: Math.round(y0 * H), width: Math.round((x1 - x0) * W), height: Math.round((y1 - y0) * H) }));
  if (rects.length) img = sharp(await img.composite(rects.map(r => ({ input: { create: { width: r.width, height: r.height, channels: 3, background: "#ffffff" } }, left: r.left, top: r.top }))).png().toBuffer());
  return img.extract({ left: l, top: t, width: Math.min(W - l, Math.round((c[2] - c[0]) * W)), height: Math.min(H - t, Math.round((c[3] - c[1]) * H)) }).png().toBuffer();
}

for (const s of sets) {
  fs.rmSync(OUT + "sets/" + s.id, { recursive: true, force: true });       // only this new folder
  for (const k of Object.keys(manifest)) if (k.startsWith(`sets/${s.id}/`)) delete manifest[k];
  const doc = mupdf.Document.openDocument(fs.readFileSync(s.file), "application/pdf");
  let n = 0;
  for (const [pg, slug, extra] of s.sheets) {
    n++;
    const buf = await render(doc, pg, (extra && extra.crop) || s.crop, (extra && extra.width) || s.width, (extra && extra.whiten) || []);
    const base = `sets/${s.id}/${String(n).padStart(2, "0")}-${slug}`;
    const m = await webp(buf, base, [["", 2000, 80], ["-800", 800, 78]]);
    console.log(base, `p${pg}`, m.full.w + "x" + m.full.h, m.full.kb + "KB");
  }
}

for (const p of PAIRS) {
  const doc = mupdf.Document.openDocument(fs.readFileSync(p.file), "application/pdf");
  const page = doc.loadPage(p.page); const b = page.getBounds(); const PW = b[2] - b[0];
  const scale = 2800 / PW, k = 2800 / PANEL.ref;
  const pix = page.toPixmap(mupdf.Matrix.scale(scale, scale), mupdf.ColorSpace.DeviceRGB, false, true);
  const png = pix.asPNG();
  for (const side of ["before", "after"]) {
    const [x0, y0, x1, y1] = PANEL[side].map(v => Math.round(v * k));
    const buf = await sharp(png).extract({ left: x0, top: y0, width: x1 - x0, height: y1 - y0 }).png().toBuffer();
    const base = `cad/${p.id}-${side}`;
    const m = await webp(buf, base, [["", 1100, 82], ["-800", 800, 78]]);
    console.log(base, m.full.w + "x" + m.full.h, m.full.kb + "KB");
  }
}

fs.writeFileSync(MANIFEST, JSON.stringify(manifest, null, 1) + "\n");
console.log("manifest updated:", Object.keys(manifest).length, "entries");
