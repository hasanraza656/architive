/**
 * Case study "Sketch to CAD Commercial Plan Set" (client PDF, 9 Nov 2026). ADDITIVE: only writes cases/cad-tenant-* and og/case-sketch-cad.jpg
 * and merges the new entries into resources/data/work-manifest.json.
 *
 *   ASSET_DEPS=<folder with node_modules/{mupdf,sharp}> node tools/build-case-sketch-cad.mjs
 *
 * Page 0 of the PDF is the client's cover slide (not used). Page 1 is the hand-marked sketch (scanned upside down: rotated 180 degrees here so the
 * handwriting reads normally). Pages 2-10 are the system plans, page 11 the bathroom layout. Each page is trimmed to its drawing.
 */
import fs from "fs";
import path from "path";
import { createRequire } from "module";
import { pathToFileURL } from "url";

const DEPS = process.env.ASSET_DEPS ? process.env.ASSET_DEPS.split("\\").join("/").replace(/\/+$/, "") : null;
const require = createRequire(DEPS ? DEPS + "/" : import.meta.url);
const sharp = require("sharp");
const mupdf = DEPS ? await import(pathToFileURL(DEPS + "/node_modules/mupdf/dist/mupdf.js").href) : await import("mupdf");

const FILE = (process.env.CLIENT_DIR || "F:/BHAI LOG/More Projects/architive/client data/changes 9 nov 2026/v2/") + "case study/Sketch_to_CAD_Case_Study_Complete_Portfolio_PDF.pdf";
const OUT = path.resolve("public/assets/img/work/cases") + "/";
const OG = path.resolve("public/assets/img/og/case-sketch-cad.jpg");
const MANIFEST = path.resolve("resources/data/work-manifest.json");
const manifest = JSON.parse(fs.readFileSync(MANIFEST, "utf8"));

const pages = [
  [1, "sketch", { rotate: 180 }], [2, "overall-plan"], [3, "door-plan"], [4, "glass-plan"], [5, "camera-plan"], [6, "light-plan"],
  [7, "hvac-plan"], [8, "smoke-plan"], [9, "egress-ingress-plan"], [10, "motion-plan"], [11, "bathroom-layout"],
];

const doc = mupdf.Document.openDocument(fs.readFileSync(FILE), "application/pdf");
async function render(pageIdx, rotate) {
  const page = doc.loadPage(pageIdx); const b = page.getBounds();
  const scale = 2200 / (b[2] - b[0]);
  const pix = page.toPixmap(mupdf.Matrix.scale(scale, scale), mupdf.ColorSpace.DeviceRGB, false, true);
  let img = sharp(pix.asPNG()).flatten({ background: "#ffffff" }).trim({ background: "#ffffff", threshold: 18 });
  if (rotate) img = sharp(await img.png().toBuffer()).rotate(rotate);
  const buf = await img.extend({ top: 40, bottom: 40, left: 40, right: 40, background: "#ffffff" }).png().toBuffer();
  return buf;
}

let overall = null;
for (const [idx, slug, opt] of pages) {
  const buf = await render(idx, opt && opt.rotate);
  if (slug === "overall-plan") overall = buf;
  const base = `cases/cad-tenant-${slug}`;
  const meta = {};
  for (const [suffix, w, q] of [["", 1900, 80], ["-800", 800, 78]]) {
    const info = await sharp(buf).resize({ width: w, withoutEnlargement: true }).webp({ quality: q, effort: 5 }).toFile(OUT + base.replace("cases/", "") + suffix + ".webp");
    meta[suffix || "full"] = info;
  }
  manifest[base] = { w: meta.full.width, h: meta.full.height, tw: meta["-800"].width, th: meta["-800"].height };
  console.log(base, meta.full.width + "x" + meta.full.height, Math.round(meta.full.size / 1024) + "KB");
}

// share image 1200x630: overall plan on white
const plan = await sharp(overall).resize({ width: 1100, height: 540, fit: "inside" }).png().toBuffer();
await sharp({ create: { width: 1200, height: 630, channels: 3, background: "#ffffff" } }).composite([{ input: plan, gravity: "centre" }]).jpeg({ quality: 86 }).toFile(OG);

fs.writeFileSync(MANIFEST, JSON.stringify(manifest, null, 1) + "\n");
console.log("manifest entries:", Object.keys(manifest).length);
