{{-- New styles for the dynamic screens. Include once, right after your existing </style>. --}}
<style>
.pd-today{display:grid;gap:16px}
.pd-hello{padding:20px 22px;border:1px solid var(--pd-line);border-radius:16px;background:linear-gradient(120deg,#1c2a4d,#172033 65%,#172b31)}
.pd-hello-top{display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;align-items:flex-start}
.pd-hello-title{font-size:26px;font-weight:800;letter-spacing:-.02em;margin:4px 0 2px;color:var(--pd-ink)}
.pd-hello-actions{display:flex;align-items:center;gap:10px}
.pd-brief{margin:14px 0 0;padding:0;list-style:none;counter-reset:b;display:grid;gap:8px}
.pd-brief li{counter-increment:b;display:flex;gap:10px;align-items:flex-start;color:var(--pd-ink);font-size:14px}
.pd-brief li::before{content:counter(b);flex:0 0 22px;height:22px;border-radius:50%;display:grid;place-items:center;font-size:11px;font-weight:800;background:var(--pd-pri-t);color:var(--pd-pri)}

.pd-kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px}
.pd-kpi{position:relative;display:grid;gap:4px;text-align:left;padding:14px;border:1px solid var(--pd-line);border-radius:14px;background:var(--pd-card);color:var(--pd-ink);cursor:pointer;overflow:hidden;transition:border-color .15s,transform .15s}
.pd-kpi:hover{border-color:var(--pd-pri);transform:translateY(-1px)}
.pd-kpi-value{font-size:22px;font-weight:800;letter-spacing:-.02em}
.pd-kpi-trend{font-size:11px;font-weight:700;margin-left:6px;color:var(--pd-mut)}
.pd-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:6px;background:var(--pd-pri)}
.pd-kpi-ok .pd-dot{background:var(--pd-ok)}.pd-kpi-warn .pd-dot{background:var(--pd-warn)}.pd-kpi-risk .pd-dot{background:var(--pd-bad)}
.pd-spark{width:100%;height:26px;margin-top:4px}.pd-spark polyline{fill:none;stroke:var(--pd-pri);stroke-width:2;vector-effect:non-scaling-stroke}

.pd-hero{padding:18px 20px;border:1px solid var(--pd-line);border-left:5px solid var(--pd-pri);border-radius:16px;background:var(--pd-card)}
.pd-tone-risk{border-left-color:var(--pd-bad)}.pd-tone-warn{border-left-color:var(--pd-warn)}.pd-tone-ok{border-left-color:var(--pd-ok)}.pd-tone-info{border-left-color:var(--pd-pri)}
.pd-hero.pd-tone-risk{background:linear-gradient(110deg,#3a1a1855,var(--pd-card) 55%)}
.pd-hero-head{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center}
.pd-hero-body{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-top:6px}
.pd-hero-title{font-size:20px;font-weight:800;margin:0;color:var(--pd-ink)}
.pd-hero-summary{color:var(--pd-mut);margin:6px 0 0;max-width:760px}
.pd-hero-metric{font-size:34px;font-weight:850;letter-spacing:-.03em;white-space:nowrap;color:var(--pd-ink)}

.pd-items{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:8px;margin-top:14px}
.pd-items-compact{grid-template-columns:1fr;margin-top:10px;gap:6px}
.pd-item{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--pd-line);border-radius:10px;background:#111a2a;font-size:13px}
.pd-items-compact .pd-item{padding:7px 10px}
.pd-card-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:14px}
.pd-btn-ghost{background:transparent;border-color:transparent;color:var(--pd-mut)}
.pd-link{background:none;border:0;padding:0;color:var(--pd-pri);font-weight:700;cursor:pointer;text-align:left}
.pd-why{margin-left:auto;font-size:12px;color:var(--pd-mut)}.pd-why summary{cursor:pointer}.pd-why ul{margin:6px 0 0;padding-left:18px}

.pd-feed-head{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:baseline}
.pd-feed{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:12px}
.pd-insight{display:flex;flex-direction:column;padding:16px;border:1px solid var(--pd-line);border-top:3px solid var(--pd-pri);border-radius:14px;background:var(--pd-card)}
.pd-insight.pd-tone-risk{border-top-color:var(--pd-bad)}.pd-insight.pd-tone-warn{border-top-color:var(--pd-warn)}.pd-insight.pd-tone-info{border-top-color:var(--pd-pri)}
.pd-insight-top{display:flex;gap:8px;align-items:center}
.pd-insight-cat{font-size:11px;text-transform:uppercase;letter-spacing:.1em;font-weight:800;color:var(--pd-mut)}
.pd-insight-metric{margin-left:auto;font-size:20px;font-weight:800;color:var(--pd-ink)}
.pd-insight-title{font-size:15px;font-weight:800;margin:8px 0 4px;color:var(--pd-ink)}
.pd-insight-change{font-size:12px;font-weight:700;color:var(--pd-warn);margin:6px 0 0}
.pd-insight .pd-card-actions{margin-top:auto;padding-top:12px}
.pd-change-new{background:var(--pd-pri-t);color:var(--pd-pri)}.pd-change-worse{background:var(--pd-bad-t);color:var(--pd-bad)}.pd-change-better{background:var(--pd-ok-t);color:var(--pd-ok)}

.pd-good{display:flex;flex-wrap:wrap;gap:8px;align-items:center;padding:12px 14px;border:1px solid #1f4a35;border-radius:14px;background:#12301f66}

.pd-chips{display:flex;gap:6px;flex-wrap:wrap}
.pd-chip{border:1px solid var(--pd-line);background:transparent;color:var(--pd-mut);border-radius:999px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer}
.pd-chip.active{background:var(--pd-pri-t);color:var(--pd-pri);border-color:#354b7a}

.pd-columns{display:flex;align-items:flex-end;gap:6px;height:120px}
.pd-column{flex:1;height:100%;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;gap:4px}
.pd-column span{width:100%;border-radius:6px 6px 2px 2px;background:var(--pd-pri)}.pd-column span.low{background:var(--pd-warn)}
.pd-column small{font-size:10px;color:var(--pd-mut)}

/* Grouped sidebar */
.pd-side-group{padding:12px 10px 4px;color:var(--pd-mut);font-weight:800;font-size:10.5px;letter-spacing:.12em;text-transform:uppercase}
.pd-side-icon svg{display:block;margin:auto}
.pd-side-badge{margin-left:auto;font-size:10.5px;background:var(--pd-bad-t);color:var(--pd-bad);border-radius:999px;padding:1px 7px;font-weight:800}
.pd-side-badge.info{background:var(--pd-pri-t);color:var(--pd-pri)}
/* Approvals */
.pd-appr{display:grid;grid-template-columns:150px minmax(0,1fr) 270px auto;gap:14px;align-items:center;padding:14px;border:1px solid var(--pd-line);border-radius:12px;background:#111a2a}
.pd-appr-actions{display:flex;gap:6px}
.pd-ai{display:flex;gap:6px;align-items:flex-start;font-size:12.5px;padding:8px 10px;border-radius:9px;background:#1c2a4d66;color:#cdd9f5}
.pd-ai-warn{background:#35280f88;color:#f6cf8e}.pd-ai-ok{background:#12301f88;color:#9fe3c0}
/* Circulars */
.pd-draft{border:1px solid var(--pd-line);border-radius:12px;background:#111a2a;padding:14px;display:grid;gap:8px}
.pd-msg{background:#0e1420;border:1px dashed #33415e;border-radius:9px;padding:10px 12px;font-size:13px;color:#cdd9f5;line-height:1.55}
.pd-chan{font-size:11px;font-weight:800;padding:3px 9px;border-radius:999px;border:1px solid var(--pd-line);color:var(--pd-mut)}
.pd-chan.on{background:var(--pd-pri-t);color:var(--pd-pri);border-color:#354b7a}
@media(max-width:1100px){.pd-appr{grid-template-columns:1fr 1fr}.pd-appr-actions{grid-column:1/-1}}
@media(max-width:600px){.pd-appr{grid-template-columns:1fr}}
/* Timetable */
.pd-tt-toolbar{display:flex;justify-content:space-between;align-items:flex-end;gap:14px;flex-wrap:wrap;margin-bottom:14px}
.pd-tt-controls{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
.pd-tt-date{display:flex;gap:6px;align-items:center}
.pd-tt-date .pd-input{width:auto;min-width:160px;color-scheme:dark}
.pd-tt-field{display:grid;gap:5px;min-width:120px}
.pd-week{min-width:900px}.pd-week td,.pd-week th{text-align:center}.pd-week td:first-child,.pd-week th:first-child{text-align:left}
.pd-week .pd-week-on{background:#1c2a4d55}
/* Layout helpers used in the page (not always present in Filament's compiled CSS) */
.pd-principal-page .flex{display:flex}.pd-principal-page .flex-wrap{flex-wrap:wrap}.pd-principal-page .items-end{align-items:flex-end}.pd-principal-page .items-start{align-items:flex-start}.pd-principal-page .items-center{align-items:center}.pd-principal-page .justify-between{justify-content:space-between}
.pd-principal-page .gap-2{gap:.5rem}.pd-principal-page .gap-3{gap:.75rem}.pd-principal-page .grid{display:grid}.pd-principal-page .grid-cols-1{grid-template-columns:1fr}
.pd-principal-page .space-y-3>*+*{margin-top:.75rem}
@media(min-width:640px){.pd-principal-page .sm\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:1200px){.pd-kpis{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:700px){.pd-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.pd-hero-body{flex-direction:column}.pd-hero-metric{font-size:28px}.pd-feed{grid-template-columns:1fr}.pd-why{margin-left:0}}
</style>
