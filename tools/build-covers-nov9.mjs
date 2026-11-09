/**
 * Cover images for the BIM samples (client feedback 9 Nov 2026: show ONLY the cover thumbnail of each PDF + a "View complete sheet" button,
 * never the sheets themselves). ADDITIVE: writes cover/* and merges into resources/data/work-manifest.json; also removes the
 * full-sheet folders (sets/adr, food, drv, res, adu, com) and their manifest entries that tools/build-nov9-assets.mjs created earlier.
 *
 *   ASSET_DEPS=<folder with node_modules/{mupdf,sharp}> node tools/build-covers-nov9.mjs
 *
 * Page 0 of each client PDF is her own cover slide (title, subtitle, two small thumbnails): it is published as-is.
 * cover/drv-3d is the Revit 3D view from page 1 of the Driver Room PDF (the same picture that is on her cover slide).
 */
import fs from "fs";
import path from "path";
import { createRequire } from "module";
import { pathToFileURL } from "url";

const DEPS = process.env.ASSET_DEPS ? process.env.ASSET_DEPS.split("\\").join("/").replace(/\/+$/, "") : null;
const require = createRequire(DEPS ? DEPS + "/" : import.meta.url);
const sharp = require("sharp");
const mupdf = DEPS ? await import(pathToFileURL(DEPS + "/node_modules/mupdf/dist/mupdf.js").href) : await import("mupdf");

const BIM = (process.env.CLIENT_DIR || "F:/BHAI LOG/More Projects/architive/client data/changes 9 nov 2026/v2/") + "Bim website update/";
const OUT = path.resolve("public/assets/img/work") + "/";
const MANIFEST = path.resolve("resources/data/work-manifest.json");
const manifest = JSON.parse(fs.readFileSync(MANIFEST, "utf8"));

const covers = [
  ["adr", "Adaptive_Reuse_Permit_Set_Blurred.pdf"], ["com", "Commercial_Complete_Portfolio_PDF.pdf"], ["drv", "Driver_Room_Shop_Drawings_Blurred.pdf"],
  ["food", "Food_Service_Permit_Set_Blurred.pdf"], ["res", "Roberts_Complete_Portfolio_PDF.pdf"],
];

function render(doc, pageIdx, width, crop) {
  const page = doc.loadPage(pageIdx); const b = page.getBounds(); const c = crop || [0, 0, 1, 1];
  const scale = width / ((b[2] - b[0]) * (c[2] - c[0]));
  const pix = page.toPixmap(mupdf.Matrix.scale(scale, scale), mupdf.ColorSpace.DeviceRGB, false, true);
  const W = pix.getWidth(), H = pix.getHeight(); const l = Math.round(c[0] * W), t = Math.round(c[1] * H);
  return sharp(pix.asPNG()).extract({ left: l, top: t, width: Math.min(W - l, Math.round((c[2] - c[0]) * W)), height: Math.min(H - t, Math.round((c[3] - c[1]) * H)) }).png().toBuffer();
}
async function save(buf, base, full = 1600) {
  fs.mkdirSync(path.dirname(OUT + base), { recursive: true });
  const m = {};
  for (const [suffix, w, q] of [["", full, 82], ["-800", 800, 80]]) m[suffix || "full"] = await sharp(buf).resize({ width: w, withoutEnlargement: true }).webp({ quality: q, effort: 5 }).toFile(OUT + base + suffix + ".webp");
  manifest[base] = { w: m.full.width, h: m.full.height, tw: m["-800"].width, th: m["-800"].height };
  console.log(base, m.full.width + "x" + m.full.height, Math.round(m.full.size / 1024) + "KB");
}

for (const [id, file] of covers) {
  const doc = mupdf.Document.openDocument(fs.readFileSync(BIM + file), "application/pdf");
  await save(await render(doc, 0, 1600), `cover/${id}`);
  if (id === "drv") await save(await render(doc, 1, 1500, [0.12, 0.34, 0.88, 0.97]), "cover/drv-3d", 1500);
}

// the full-sheet images are not published any more
for (const id of ["adr", "food", "drv", "res", "adu", "com"]) {
  fs.rmSync(OUT + "sets/" + id, { recursive: true, force: true });
  for (const k of Object.keys(manifest)) if (k.startsWith(`sets/${id}/`)) delete manifest[k];
}
fs.writeFileSync(MANIFEST, JSON.stringify(manifest, null, 1) + "\n");
console.log("manifest entries:", Object.keys(manifest).length);
