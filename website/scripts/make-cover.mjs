// Generator for the social/OG + README cover images.
// Produces light & dark variants in ../art/ and the OG image in ./public/cover.png.
// Rendered at 2x for crispness. Run: node scripts/make-cover.mjs
import sharp from 'sharp';
import { mkdirSync } from 'node:fs';

const W = 1200, H = 630;
const SANS = `font-family="system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif"`;
const MONO = `font-family="ui-monospace, 'SF Mono', Menlo, Consolas, monospace"`;
const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

const LIGHT = {
  bgFrom: '#ffffff', bgTo: '#e8ecff', grid: '#c7d2fe', gridOpacity: 0.35,
  eyebrow: '#4f46e5', gradFrom: '#6366f1', gradTo: '#7c3aed', solidTitle: '#1e1b4b',
  tagline: '#334155', pillBg: '#ffffff', pillStroke: '#c7d2fe', pillText: '#4f46e5',
  termBg: '#0f172a', termBar: '#1e293b', termStroke: 'none', termTitle: '#94a3b8',
  prompt: '#22c55e', cmd: '#e2e8f0', flag: '#a5b4fc', folder: '#c7d2fe',
  branch: '#475569', subfolder: '#93c5fd', muted: '#64748b', success: '#4ade80',
  shadow: '#312e81', shadowOpacity: 0.28,
};

const DARK = {
  bgFrom: '#0b1020', bgTo: '#181635', grid: '#4338ca', gridOpacity: 0.20,
  eyebrow: '#a5b4fc', gradFrom: '#818cf8', gradTo: '#c084fc', solidTitle: '#f1f5f9',
  tagline: '#cbd5e1', pillBg: '#1e1b4b', pillStroke: '#4338ca', pillText: '#c7d2fe',
  termBg: '#111827', termBar: '#1f2937', termStroke: '#334155', termTitle: '#94a3b8',
  prompt: '#4ade80', cmd: '#e5e7eb', flag: '#a5b4fc', folder: '#c7d2fe',
  branch: '#64748b', subfolder: '#93c5fd', muted: '#94a3b8', success: '#4ade80',
  shadow: '#000000', shadowOpacity: 0.5,
};

function tspanLine(x, y, parts) {
  const spans = parts.map((p) => `<tspan fill="${p.fill}">${esc(p.t)}</tspan>`).join('');
  return `<text x="${x}" y="${y}" ${MONO} font-size="15" xml:space="preserve">${spans}</text>`;
}

function buildSvg(c) {
  // faint background grid
  let grid = '';
  for (let x = 0; x <= W; x += 40) grid += `<line x1="${x}" y1="0" x2="${x}" y2="${H}"/>`;
  for (let y = 0; y <= H; y += 40) grid += `<line x1="0" y1="${y}" x2="${W}" y2="${y}"/>`;

  // terminal geometry
  const tx = 632, ty = 120, tw = 500, th = 392, bar = 40, r = 14;

  const tree = [
    ['Actions/', 'CreateAnimal …'],
    ['Aggregates/', 'AnimalAggregate'],
    ['Events/', 'AnimalCreated …'],
    ['Projections/', 'Animal'],
    ['Projectors/', 'AnimalProjector'],
    ['Reactors/', 'AnimalReactor'],
  ];
  const bodyLines = [
    [{ t: '$ ', fill: c.prompt }, { t: 'php artisan make:event-sourcing-domain', fill: c.cmd }],
    [{ t: '      Animal ', fill: c.cmd }, { t: '--aggregate=1 --unit-test', fill: c.flag }],
    [],
    [{ t: 'app/Domain/Animal/', fill: c.folder }],
    ...tree.map(([folder, cls], i) => [
      { t: (i === tree.length - 1 ? '└─ ' : '├─ '), fill: c.branch },
      { t: folder.padEnd(14), fill: c.subfolder },
      { t: cls, fill: c.muted },
    ]),
    [],
    [{ t: '✓ Domain [Animal] created successfully.', fill: c.success }],
  ];

  let body = '';
  let by = ty + bar + 32;
  for (const parts of bodyLines) {
    if (parts.length) body += tspanLine(tx + 22, by, parts);
    by += 26;
  }

  // pills (kept short so the last one clears the terminal window on the right)
  const pills = ['Migrations', 'Aggregates', 'Unit tests'];
  let px = 80, pillsSvg = '';
  for (const label of pills) {
    const pw = 46 + label.length * 10.4;
    pillsSvg += `
      <rect x="${px}" y="500" width="${pw}" height="46" rx="23" fill="${c.pillBg}" stroke="${c.pillStroke}" stroke-width="1.5"/>
      <text x="${px + 24}" y="529" ${SANS} font-size="17" font-weight="600" letter-spacing="0.2" fill="${c.pillText}">${esc(label)}</text>`;
    px += pw + 18;
  }

  const titleAttrs = `${SANS} font-size="80" font-weight="800" letter-spacing="3"`;

  return `<svg xmlns="http://www.w3.org/2000/svg" width="${W * 2}" height="${H * 2}" viewBox="0 0 ${W} ${H}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="${c.bgFrom}"/>
      <stop offset="1" stop-color="${c.bgTo}"/>
    </linearGradient>
    <linearGradient id="grad" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="${c.gradFrom}"/>
      <stop offset="1" stop-color="${c.gradTo}"/>
    </linearGradient>
    <filter id="shadow" x="-30%" y="-30%" width="160%" height="160%">
      <feDropShadow dx="0" dy="18" stdDeviation="24" flood-color="${c.shadow}" flood-opacity="${c.shadowOpacity}"/>
    </filter>
  </defs>

  <rect width="${W}" height="${H}" fill="url(#bg)"/>
  <g stroke="${c.grid}" stroke-width="1" opacity="${c.gridOpacity}">${grid}</g>

  <circle cx="86" cy="116" r="6" fill="${c.eyebrow}"/>
  <text x="104" y="123" ${SANS} font-size="20" font-weight="700" letter-spacing="3" fill="${c.eyebrow}">LARAVEL · ARTISAN COMMAND</text>

  <text x="78" y="212" ${titleAttrs} fill="url(#grad)">Event</text>
  <text x="78" y="290" ${titleAttrs} fill="url(#grad)">Sourcing</text>
  <text x="78" y="368" ${titleAttrs} fill="${c.solidTitle}">Generator</text>

  <text x="80" y="420" ${SANS} font-size="22" fill="${c.tagline}">Scaffold complete event-sourced domains - events,</text>
  <text x="80" y="452" ${SANS} font-size="22" fill="${c.tagline}">projectors, aggregates and tests - with one command.</text>

  ${pillsSvg}

  <g filter="url(#shadow)">
    <rect x="${tx}" y="${ty}" width="${tw}" height="${th}" rx="${r}" fill="${c.termBg}" stroke="${c.termStroke}" stroke-width="1"/>
    <path d="M${tx} ${ty + bar} v-${bar - r} a${r} ${r} 0 0 1 ${r} -${r} h${tw - 2 * r} a${r} ${r} 0 0 1 ${r} ${r} v${bar - r} z" fill="${c.termBar}"/>
    <circle cx="${tx + 22}" cy="${ty + 20}" r="6" fill="#ef4444"/>
    <circle cx="${tx + 44}" cy="${ty + 20}" r="6" fill="#f59e0b"/>
    <circle cx="${tx + 66}" cy="${ty + 20}" r="6" fill="#22c55e"/>
    <text x="${tx + tw / 2}" y="${ty + 25}" text-anchor="middle" ${MONO} font-size="14" fill="${c.termTitle}">make:event-sourcing-domain</text>
    ${body}
  </g>
</svg>`;
}

mkdirSync('../art', { recursive: true });
mkdirSync('public', { recursive: true });

for (const [name, palette] of [['light', LIGHT], ['dark', DARK]]) {
  await sharp(Buffer.from(buildSvg(palette))).png().toFile(`../art/cover-${name}.png`);
  console.log(`../art/cover-${name}.png written`);
}
// OG image uses the light variant
await sharp(Buffer.from(buildSvg(LIGHT))).png().toFile('public/cover.png');
console.log('public/cover.png written (light)');
