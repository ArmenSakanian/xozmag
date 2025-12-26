// scripts/fa-migrate.mjs
import fs from "node:fs/promises";
import path from "node:path";

const SRC_DIR = path.resolve("src");

const prefixMap = {
  "fa-solid": "fas",
  "fa-regular": "far",
  "fa-brands": "fab",
};

// частые модификаторы FontAwesome (не иконки)
const ignoreFa = new Set([
  "fa-fw",
  "fa-spin",
  "fa-pulse",
  "fa-beat",
  "fa-bounce",
  "fa-fade",
  "fa-shake",
  "fa-flip",
  "fa-inverse",
  "fa-border",
  "fa-xs",
  "fa-sm",
  "fa-lg",
  "fa-xl",
  "fa-2xl",
  "fa-1x",
  "fa-2x",
  "fa-3x",
  "fa-4x",
  "fa-5x",
  "fa-6x",
  "fa-7x",
  "fa-8x",
  "fa-9x",
  "fa-10x",
]);

async function walk(dir, out = []) {
  const entries = await fs.readdir(dir, { withFileTypes: true });
  for (const e of entries) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) await walk(p, out);
    else if (e.isFile() && p.endsWith(".vue")) out.push(p);
  }
  return out;
}

function pickPrefix(classes) {
  for (const c of classes) if (prefixMap[c]) return prefixMap[c];
  return null;
}

function pickIconName(classes) {
  // ищем fa-xxxxx, которая не является префиксом и не является модификатором
  for (const c of classes) {
    if (!c.startsWith("fa-")) continue;
    if (prefixMap[c]) continue;
    if (ignoreFa.has(c)) continue;
    if (/^fa-\d+x$/.test(c)) continue; // типа fa-2x
    // похоже на имя иконки
    return c.slice(3);
  }
  return null;
}

function toFaExport(iconName) {
  // bars-staggered -> faBarsStaggered
  return (
    "fa" +
    iconName
      .split("-")
      .filter(Boolean)
      .map((w) => w[0].toUpperCase() + w.slice(1))
      .join("")
  );
}

const used = { fas: new Set(), far: new Set(), fab: new Set() };
let changedFiles = 0;
let changedTags = 0;

// матчим только <i ... class="...">...</i> (статический class)
const iTagRe = /<i\b([^>]*?)\sclass=(["'])(.*?)\2([^>]*)>\s*<\/i>/gms;

const files = await walk(SRC_DIR);

for (const file of files) {
  const src = await fs.readFile(file, "utf8");
  let replaced = false;

  const out = src.replace(iTagRe, (full, preAttrs, q, classStr, postAttrs) => {
    const classes = classStr.trim().split(/\s+/).filter(Boolean);

    const prefix = pickPrefix(classes);
    if (!prefix) return full;

    const iconName = pickIconName(classes);
    if (!iconName) return full;

    // сохраняем твои кастомные классы (search-icon и т.п.)
    const custom = classes.filter((c) => !c.startsWith("fa-") && c !== "fa");
    const classAttr = custom.length ? ` class="${custom.join(" ")}"` : "";

    used[prefix].add(iconName);
    replaced = true;
    changedTags++;

    // переносим остальные атрибуты i (title/aria/и т.п.)
    return `<Fa${preAttrs}${postAttrs}${classAttr} :icon="['${prefix}','${iconName}']" />`;
  });

  if (replaced && out !== src) {
    await fs.writeFile(file, out, "utf8");
    changedFiles++;
  }
}

console.log(`✅ Replaced tags: ${changedTags}`);
console.log(`✅ Changed files: ${changedFiles}`);

for (const p of ["fas", "far", "fab"]) {
  const arr = [...used[p]].sort();
  if (!arr.length) continue;
  console.log(`\n${p} icons (${arr.length}):`);
  console.log(arr.join(", "));
  console.log(`\nSuggested imports for ${p}:`);
  console.log(arr.map(toFaExport).join(", "));
}
