<?php
// Mobile version shell for Budgetplanner UI
// Only presentation differs from desktop. All server endpoints and logic remain identical.
// (Assumes the same session/auth includes as desktop app if needed.)
@session_start();
?>
<!DOCTYPE html>
<html lang="nl-BE">
<head>
  <meta name="robots" content="noindex">
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover" />
  <title>Budgetplanner – Mobiel</title>
  <link rel="icon" sizes="32x32" href="../favicon-32x32.png">
  <link rel="icon" sizes="48x48" href="../favicon-48x48.png">
  <link rel="apple-touch-icon" sizes="180x180" href="../favicon-180x180.png">
  <style>
    :root{
      --bg:#f7f7f8; --panel:#ffffff; --text:#111; --muted:#666; --border:#e6e6e6;
      --brand:#111; --pill:#f1f1f4;
      --safe:#e7f7ef; --warn:#fff8e1; --bad:#fdecec;
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{margin:0;font:16px system-ui, -apple-system, Segoe UI, Roboto, sans-serif;color:var(--text);background:var(--bg);}
    header{
      position:sticky;top:0;z-index:1000;background:var(--panel);
      padding:12px 16px;display:flex;align-items:center;justify-content:space-between;
      border-bottom:1px solid var(--border);
    }
    .title{display:flex;align-items:center;gap:10px;font-weight:800;letter-spacing:.2px}
    .title .app{font-size:18px}
    .title #month{font-weight:700;color:var(--muted);font-size:14px}
    main{padding:12px 12px 92px 12px;max-width:960px;margin:0 auto}
    .stack{display:flex;flex-direction:column;gap:10px}
    .row{display:flex;gap:10px;flex-wrap:wrap}
    .field>label{display:block;margin:0 0 6px;font-weight:600;}
    .field input[type=text],.field input[type=number],.field select{width:100%;height:40px;border:1px solid var(--border);border-radius:10px;background:#fff;padding:0 10px}
    table{width:100%;border-collapse:separate;border-spacing:0 8px}
    td{vertical-align:bottom}
    .numwrap{position:relative}
    .stepbox{position:absolute;right:6px;top:6px;display:flex;gap:6px}
    .step{width:36px;height:28px;border:1px solid var(--border);border-radius:8px;background:var(--pill)}
    .icon-btn,.secondary,button{cursor:pointer}
    .icon-btn{border:none;background:var(--pill);padding:8px 10px;border-radius:10px}
    .secondary{border:1px solid var(--border);background:#fff;padding:6px 10px;border-radius:10px}
    .pill{display:inline-block;background:var(--pill);border-radius:999px;padding:8px 10px}
    .pill.big{font-weight:800}
    .muted{color:var(--muted)}

    /* Top-right menu */
    details.menu{position:relative}
    details.menu>summary{list-style:none;cursor:pointer;border-radius:10px;padding:6px 10px}
    details.menu[open]>summary{background:var(--pill)}
    details.menu .sheet{position:absolute;right:0;top:48px;background:var(--panel);border:1px solid var(--border);border-radius:14px;min-width:240px;box-shadow:0 8px 24px rgba(0,0,0,.08);padding:8px}
    details.menu .sheet .group{padding:8px}
    details.menu .sheet .group+.
    group{border-top:1px solid var(--border)}
    .menu-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0}

    /* Bottom nav */
    nav.tabbar{
      position:fixed;bottom:0;left:0;right:0;z-index:999;background:rgba(255,255,255,0.9);
      backdrop-filter:saturate(180%) blur(10px);border-top:1px solid var(--border);
    }
    nav.tabbar .wrap{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;padding:10px 14px;max-width:960px;margin:0 auto}
    nav.tabbar button{border:none;background:transparent;padding:6px 6px 2px;border-radius:12px;display:flex;flex-direction:column;align-items:center;gap:4px}
    nav.tabbar button .label{font-size:12px;color:#000}

    /* Sections */
    section{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:12px}
    section+section{margin-top:12px}
    section.hidden{display:none}

    /* Simple helpers for state colors */
    .state-good{background:var(--safe)}
    .state-warn{background:var(--warn)}
    .state-bad{background:var(--bad)}

    /* Category summary dot */
    .category-dot{width:12px;height:12px;border-radius:50%;display:inline-block;margin-right:8px}
  </style>
</head>
<body>
  <header>
    <div class="title">
      <span class="app">Budgetplanner</span>
      <span id="month" class="muted">—</span>
    </div>
    <div class="actions">
      <button id="nextMonth" class="secondary" title="Volgende maand">Volgende maand ▶</button>
      <details class="menu">
        <summary aria-label="Menu">☰</summary>
        <div class="sheet">
          <div class="group">
            <div class="menu-row"><strong>Snelle acties</strong></div>
            <div class="menu-row"><button id="nextMonthMenu" class="secondary">Volgende maand</button></div>
            <div class="menu-row"><button id="duplicateFromPrev" class="secondary">Kopieer vorige maand</button></div>
          </div>
          <div class="group" id="viewMenu">
            <div class="menu-row"><strong>Weergave</strong></div>
            <label class="menu-row"><input class="view-toggle" type="checkbox" data-section="overview" checked> Overzicht</label>
            <label class="menu-row"><input class="view-toggle" type="checkbox" data-section="networth" checked> Net worth</label>
            <label class="menu-row"><input class="view-toggle" type="checkbox" data-section="categoryDetails" checked> Categorie details</label>
          </div>
          <div class="group">
            <form method="post" action="logout.php"><button type="submit" class="secondary" id="logoutMenu">Afmelden</button></form>
          </div>
        </div>
      </details>
    </div>
  </header>

  <main class="stack">
    <!-- OVERZICHT / KPI's are injected by JS; sticky row is created dynamically -->
    <section data-section="overview" class="stack">
      <h2 style="margin:0">Overzicht</h2>
      <div id="categorySummary" class="stack"></div>
      <div class="row" id="totalsRow">
        <div class="field"><label>Totaal inkomen</label><span id="totalIncome" class="pill big">—</span></div>
        <div class="field"><label>Totaal uitgaven</label><span id="totalExpense" class="pill big">—</span></div>
        <div class="field"><label>Netto</label><span id="netSavings" class="pill big">—</span></div>
        <div class="field"><label>Spaarquote</label><span id="savingsRate" class="pill">—</span></div>
        <div class="field"><label>Op rekening deze maand</label><span id="totalSavedThisMonth" class="pill big">—</span></div>
      </div>
    </section>

    <!-- INKOMEN -->
    <section data-section="income" class="stack hidden">
      <h2 style="margin:0">Inkomen</h2>
      <div id="incomeList" class="stack"></div>
      <div class="row">
        <button id="addIncome" class="secondary">+ Inkomst toevoegen</button>
      </div>
    </section>

    <!-- SPAREN / BELEGGEN (mobile variant gebruikt enkel beleggen als categorie) -->
    <section data-section="sparen" class="stack hidden">
      <h2 style="margin:0">Beleggen</h2>
      <div id="spaar&belegCategorie" class="stack"></div>
      <div class="row">
        <button id="addSpaar" class="secondary">+ Belegging toevoegen</button>
      </div>
    </section>

    <!-- UITGAVEN -->
    <section data-section="expenses" class="stack hidden">
      <h2 style="margin:0">Uitgaven</h2>
      <div class="row" style="align-items:flex-end">
        <div class="field" style="flex:1 1 200px">
          <label>Categorie filter</label>
          <select id="categoryFilter"><option value="">Alle categorieën</option></select>
        </div>
        <button id="addExpense" class="secondary">+ Uitgave</button>
        <button id="addPresets" class="secondary">+ Voorinstellingen</button>
      </div>
      <div class="stack">
        <table id="expenseTable">
          <tbody></tbody>
        </table>
      </div>
    </section>

    <!-- NET WORTH -->
    <section data-section="networth" class="stack">
      <h2 style="margin:0">Net worth</h2>
      <div class="row">
        <button id="addAccount" class="secondary">+ Rekening</button>
        <button id="addNetItem" class="secondary">+ Item</button>
      </div>
      <div id="networthList" class="stack"></div>
    </section>

    <!-- CATEGORIE DETAILS (grafiek + details door JS) -->
    <section data-section="categoryDetails" class="stack">
      <h2 style="margin:0">Details per categorie</h2>
      <div id="categoryDetailsHost" class="stack"></div>
    </section>

    <!-- Helpers / modals / hidden inputs used by JS -->
    <input type="file" id="fileInput" accept="application/json" style="display:none" />
    <div id="helpOverlay" style="display:none"></div>
    <div id="helpModal" style="display:none"></div>
  </main>

  <!-- Bottom Tab Bar -->
  <nav class="tabbar" aria-label="Primaire navigatie">
    <div class="wrap">
      <button onclick="showSectionAndScroll('overview')" aria-label="Overzicht"><span>🏠</span><span class="label">Overzicht</span></button>
      <button onclick="showSectionAndScroll('income')" aria-label="Inkomen"><span>🪙</span><span class="label">Inkomen</span></button>
      <button onclick="showSectionAndScroll('expenses')" aria-label="Uitgaven"><span>🎯</span><span class="label">Uitgaven</span></button>
      <button onclick="showSectionAndScroll('sparen')" aria-label="Beleggen"><span>💹</span><span class="label">Beleggen</span></button>
    </div>
  </nav>

  <script src="mobileScript.js"></script>
  <script>
    // Ensure we load initial data if the script copy doesn't already do it.
    if (typeof load === 'function' && typeof apiLoadMeta === 'function') {
      (async()=>{ try{ const m = await apiLoadMeta(); if(m) saveMeta(Object.assign(loadMeta(), m)); }catch(e){}; await load(state.month); })();
    }
  </script>
</body>
</html>
