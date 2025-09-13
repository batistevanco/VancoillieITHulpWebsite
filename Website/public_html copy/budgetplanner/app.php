<?php
// app.php
session_start();

// Als niet ingelogd → terug naar login
if (!isset($_SESSION['user_id'])) {
  header('Location: index.html');
  exit;
}
?>


<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Budgetplanner BETA 1</title>
  <meta name="description" content="Moderne persoonlijke budgetplanner met inkomsten, uitgaven per categorie, en maandelijkse overzichten. Opslaan per maand in LocalStorage, export/import en CSV." />
  <style>
    /* Goals & simulations */
    .goal-row{display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap}
    .progress{height:10px; background:var(--border); border-radius:999px; overflow:hidden}
    .progress > span{display:block; height:100%; background:var(--accent-2)}
    .goal-actions{display:flex; gap:6px; align-items:center}
    .sim-grid{display:grid; grid-template-columns: repeat(12,1fr); gap:12px}
    .sim-grid > .field{grid-column: span 6}
    @media (min-width: 900px){ .sim-grid > .field{grid-column: span 3} }
    :root{
      --bg: #0b0d10;
      --panel:#12161b;
      --muted:#778397;
      --text:#e7edf6;
      --accent:#4f8cff;
      --accent-2:#15c39a;
      --danger:#ff5c5c;
      --warn:#ffb020;
      --ring: rgba(79,140,255,.5);
      --border:#1e2630;
    }
    :root.light{
      --bg:#f7f9fc; --panel:#ffffff; --muted:#5c6775; --text:#101418; --accent:#2b6cff; --accent-2:#0aa57d; --danger:#e04444; --warn:#cc8a00; --ring: rgba(43,108,255,.25); --border:#e6eaf1;
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{margin:0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Inter, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji","Segoe UI Emoji"; background:var(--bg); color:var(--text);}
    body{overflow-x:hidden}
    .container{max-width:98%; margin:0 auto; padding:12px;}
header{
  display:flex; gap:16px; align-items:center; justify-content:space-between;
  margin-bottom:18px;
  position: sticky; top: 0; z-index: 20;
  background: color-mix(in oklab, var(--bg), transparent 12%);
  backdrop-filter: blur(6px);
  border-bottom: 1px solid var(--border);
  padding-block: 10px;
}    .brand{display:flex; gap:12px; align-items:center}
    .logo{
      width:34px;
      height:34px;
      border-radius:6px;
      object-fit:contain;
    }
    h1{font-size:20px; margin:0}

    

    .controls{display:flex; gap:10px; align-items:center; flex-wrap:wrap}
    .control{display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--border); background:var(--panel); border-radius:12px}
    select,input[type="month"]{background:transparent; color:var(--text); border:none; outline:none}
    button{border:none; border-radius:10px; padding:10px 12px; background:var(--accent); color:white; cursor:pointer}
    button.secondary{background:transparent; color:var(--text); border:1px solid var(--border)}
    button.ghost{background:transparent; color:var(--text); padding:8px 10px}
    button.danger{background:var(--danger)}
    button:focus-visible{outline:2px solid var(--ring); outline-offset:2px}


    /* Zorg voor gelijke hoogte van alle knoppen, inclusief .btn-donate */
    button,
    .btn-donate {
      height: 40px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    /* Unified button sizing for consistent look */
    :root{ --btn-font-size:14px; --btn-pad-y:10px; --btn-pad-x:14px; --btn-radius:12px; }
    button, .btn-donate{
      font-size: var(--btn-font-size);
      padding: var(--btn-pad-y) var(--btn-pad-x);
      border-radius: var(--btn-radius);
      line-height: 1; /* voorkomt verticale drift */
    }

    /* View settings helper */
.hidden{ display:none !important; }
.menu-panel .group{ width:100%; display:flex; flex-wrap:wrap; gap:8px; align-items:center }
.menu-panel .group label{ display:inline-flex; gap:6px; align-items:center; padding:6px 8px; border:1px solid var(--border); border-radius:999px }
.menu-panel .title{ font-weight:600; width:100%; margin-top:4px; margin-bottom:2px }

    .grid{display:grid; grid-template-columns: repeat(12, 1fr); gap:16px}
.card{
  grid-column: span 12;
  background:var(--panel);
  border:1px solid var(--border);
  border-radius:12px;
  padding:16px;
  box-shadow: 0 8px 24px rgba(0,0,0,.18);
}    @media (min-width: 900px){
      .card.span-4{grid-column: span 4}
      .card.span-8{grid-column: span 8}
      .card.span-6{grid-column: span 6}
    }

    .card h2{font-size:16px; margin:0 0 12px 0}
    .muted{color:var(--muted); font-size:12px}
    .row{display:flex; gap:10px; align-items:center; flex-wrap:wrap}
    .field{
      display: flex;
      flex-direction: column;
      gap: 4px;
      min-width: 140px
    }
    .field label{
      font-size: 0.85rem;
      color: var(--muted);
    }
    @media (min-width: 768px) {
      table thead {
        display: none;
      }
    }
    .field input, .field select{padding:10px 12px; border-radius:10px; border:1px solid var(--border); background:transparent; color:var(--text)}

    table{width:100%; border-collapse:collapse}
    th,td{border-bottom:1px solid var(--border); padding:10px; text-align:left}
    th{font-size:12px; color:var(--muted); font-weight:600}
    td .tiny{font-size:11px; color:var(--muted)}
    td.actions{width:1%; white-space:nowrap}

    .kpi{display:flex; gap:12px; align-items:center}
    .kpi .num{font-size:22px; font-weight:700}
    .kpi .tag{font-size:11px; padding:4px 8px; border-radius:999px; background:rgba(79,140,255,.12); color:var(--accent)}
    .kpi.positive .tag{background:rgba(21,195,154,.12); color:var(--accent-2)}
    .kpi.negative .tag{background:rgba(255,92,92,.12); color:var(--danger)}

    .stack{display:flex; flex-direction:column; gap:12px}
    .footer-actions{display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap}

    .pill{padding:6px 8px; border-radius:999px; border:1px solid var(--border); font-size:12px}
    .pill.big{
      font-size: 18px;
      padding: 10px 14px;
      font-weight: 700;
    }
    .pill.state-good{ background: rgba(21,195,154,.14); border-color: rgba(21,195,154,.35); color: var(--accent-2); }
    .pill.state-bad{ background: rgba(255,92,92,.14); border-color: rgba(255,92,92,.35); color: var(--danger); }
    .pill.state-warn{ background: rgba(255,176,32,.14); border-color: rgba(255,176,32,.35); color: var(--warn); }
    .category-dot{display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:6px; vertical-align:middle}

    canvas{width:100%; height:auto; min-height:240px}

    /* Action button colors */
    button[data-action="delete-exp"],
    button[data-action="delete-income"],
    #resetMonth,
    #clearAllData{
      background: var(--danger);
      color: #fff;
      border: none;
    }
    button[data-action="delete-exp"]:hover,
    button[data-action="delete-income"]:hover,
    #resetMonth:hover,
    #clearAllData:hover{filter:brightness(1.05)}

    button[data-action="duplicate-exp"]{
      background: var(--accent);
      color: #fff;
      border: none;
    }
    button[data-action="duplicate-exp"]:hover{filter:brightness(1.05)}

    /* Nicer selects */
    select{
      -webkit-appearance:none; appearance:none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23778397' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat:no-repeat; background-position: right 10px center; background-size: 18px 18px;
      padding-right: 36px; border-radius: 999px;
    }
    select:focus{outline:2px solid var(--ring); outline-offset:2px}

    /* Custom numeric steppers */
    .numwrap{position:relative}
    .numwrap input{padding-right:30px}
    .numwrap .stepbox{position:absolute; right:8px; top:50%; transform:translateY(-50%); display:flex; flex-direction:row; align-items:center; gap:4px}
    .numwrap .step {
      width: 28px;
      height: 28px;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 1px solid var(--border);
      background: var(--panel);
      color: var(--text);
      border-radius: 10px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      user-select: none;
      box-sizing: border-box;
    }
    .numwrap .step:focus-visible{outline:2px solid var(--ring); outline-offset:2px}
    .numwrap .step.plus{background:rgba(79,140,255,.14); border-color:rgba(79,140,255,.35)}
    .numwrap .step.minus{background:rgba(255,92,92,.14); border-color:rgba(255,92,92,.35)}
    .numwrap .step:hover{filter:brightness(1.05)}

    /* Verberg native number spinners (we gebruiken eigen steppers) */
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }
    input[type="number"] {
      -webkit-appearance: none; /* Safari/Chromium */
      -moz-appearance: textfield; /* Firefox */
      appearance: textfield;
    }

    /* Compactere custom steppers voor mobiele schermen */
    @media (max-width: 600px) {
      .numwrap .stepbox {
        flex-direction: row;
        right: 4px;
        top: 50%;
        transform: translateY(-50%);
        gap: 2px;
      }
      .numwrap .step {
        width: 28px;
        height: 28px;
        font-size: 14px;
        line-height: 26px;
        padding: 0;
        box-sizing: border-box;
      }
    }

    /* ===== Responsive tweaks ===== */
    @media (max-width: 1024px){
      .container{padding:10px}
      .grid{gap:12px}
      .card{padding:14px}
    }

    @media (max-width: 900px){
      /* Make all cards full width */
      .card{grid-column: span 12 !important}
      /* Controls stack nicely */
      .controls{gap:8px}
      .controls > *{flex:1 1 auto}
      .controls button{flex:0 0 auto}
      /* Fields stop enforcing fixed widths from inline styles */
      .row .field{width:100% !important; flex:1 1 100% !important}
      .field input, .field select{width:100%}

      /* Compact header for small screens: ONLY Month + one menu (Meer) */
      #viewMenu{ display:none !important; }

      /* Hide everything by default... */
      .controls > *{ display:none !important; }
      /* ...except the month control */
      .controls > .control{ display:flex !important; }
      /* ...and the first menu (Meer) */
      .controls > details.menu:first-of-type{ display:inline-block !important; }

      /* Strongly hide elements that had later display rules */
      .controls .btn-donate{ display:none !important; }
      .controls form{ display:none !important; }
      .controls #nextMonth{ display:none !important; }

      /* Force show Snelkoppelingen inside menu-panel on small screens */
      .menu-panel .title,
      .menu-panel .group{ display:block !important; width:100%; }

      /* Ensure donate button inside mobile menu remains visible */
      .mobile-only-shortcuts .btn-donate{ display:inline-flex !important; }
    }
    /* Hide Snelkoppelingen shortcuts on large screens, show only on small screens */
    .mobile-only-shortcuts{ display:none; }
    @media (max-width: 900px){ .mobile-only-shortcuts{ display:block !important; } }

    @media (max-width: 720px){
      h1{font-size:18px}
      .muted{font-size:11px}
      .controls{flex-direction:row; flex-wrap:wrap}
      .control{width:100%}
      .controls button{padding:9px 10px}
    }

    /* Responsive table: collapse into cards on small screens */
    @media (max-width: 900px){
      #expenseTable, #expenseTable thead, #expenseTable tbody, #expenseTable th, #expenseTable td, #expenseTable tr{display:block; width:100%}
      #expenseTable thead{display:none}
      #expenseTable tr{border:1px solid var(--border); border-radius:12px; padding:8px; margin-bottom:10px; background:var(--panel)}
      #expenseTable td{border:none; padding:8px 6px; display:flex; align-items:center; justify-content:space-between; gap:12px}
      #expenseTable td::before{content: attr(data-label); color: var(--muted); font-size:12px; margin-right:8px; flex:0 0 130px}
      /* Label content via nth-child for existing markup */
      #expenseTable td:nth-child(1){--label:'Categorie'}
      #expenseTable td:nth-child(2){--label:'Omschrijving'}
      #expenseTable td:nth-child(3){--label:'Bedrag (€)'}
      #expenseTable td:nth-child(4){--label:'Frequentie'}
      #expenseTable td:nth-child(5){--label:'Vervaldag'}
      #expenseTable td:nth-child(6){--label:'Maandbedrag'}
      #expenseTable td:nth-child(7){--label:'Terugkeerbaar'}
      #expenseTable td:nth-child(8){--label:'Acties'}
      #expenseTable td::before{content: var(--label)}
      #expenseTable td.actions{justify-content:flex-end}
      #expenseTable td.actions button{margin-left:6px}
    }

    /* Readability for table rows */
#expenseTable tbody tr{ min-height:44px }
#expenseTable tbody tr:nth-child(odd){ background: color-mix(in oklab, var(--panel), white 4%); }

/* Toast notifications */
#toast{
  position:fixed;
  top:16px;
  right:16px;
  max-width:320px;
  background: var(--accent);
  color:#fff;
  font-weight:600;
  border:none;
  padding:14px 16px;
  border-radius:10px;
  box-shadow:0 8px 24px rgba(0,0,0,.35);
  display:none;
  z-index:1000;
}
#toast.show{ display:block; animation: toast-fade 250ms ease }
@keyframes toast-fade{ from{opacity:0; transform:translateY(6px)} to{opacity:1; transform:none} }

    @media (max-width: 480px){
      .container{padding:8px}
      .logo{width:28px; height:28px}
      .card{padding:12px}
      /* Keep chart readable on narrow screens */
      canvas#breakdown{min-height:280px}
    }

    /* Compacte actieknoppen zodat de tabel niet breder wordt dan de container */
    td.actions {
    white-space: nowrap; /* knoppen blijven naast elkaar */
    }
    td.actions button {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px; /* compact, maar nog klikvriendelijk */
    margin-left: 2px;
    }

    /* iets kleinere variant op small/average desktops als het nog krap is */
    @media (max-width: 1366px){
    td.actions button {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        margin-left: 1px;
    }
    }

    /* Stapel dupliceren/verwijderen onder elkaar zodat er geen horizontale scroll meer is */
    td.actions{
    display:flex;
    flex-direction:column;
    align-items:flex-end;
    gap:8px;
    white-space:normal;           /* overschrijft de oude nowrap */
    width:auto;                   /* niet forceren naar 1% */
    }

    td.actions button{
    width: 36px;
    height: 36px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    margin: 0;                    /* geen extra marge die kan uitrekken */
    }

    /* Op smallere schermen mogen ze nog compacter */
    @media (max-width: 1366px){
    td.actions button{
        width: 32px;
        height: 32px;
        border-radius: 8px;
    }
    }
    /* Drag & drop styling */
    #expenseTable tr.dragging { opacity: .6; }
    #expenseTable tr.drag-over { outline: 2px dashed var(--accent); outline-offset: -2px; }

    /* --- Net worth overdrachten disclosure arrow --- */
    /* --- Header options dropdown --- */
    details.menu{position:relative}
    details.menu > summary{
      list-style:none;
      display:inline-flex; align-items:center; gap:8px;
      padding:10px 12px; border:1px solid var(--border); background:var(--panel);
      border-radius:12px; cursor:pointer; user-select:none;
    }
    details.menu > summary::-webkit-details-marker{display:none}
    .menu-panel{
      position:absolute; right:0; top:calc(100% + 8px);
      background:var(--panel); border:1px solid var(--border); border-radius:12px;
      padding:12px; box-shadow:0 10px 30px rgba(0,0,0,.25);
      display:flex; flex-wrap:wrap; gap:8px; min-width: 320px; z-index:10;
    }
    @media (max-width: 600px){ .menu-panel{ right:auto; left:0; min-width: 240px; } }
    details.nw-group summary .arrow {
      display:inline-block;
      transition: transform 0.2s;
      margin-right:6px;
    }
    details.nw-group[open] summary .arrow {
      transform: rotate(90deg);
    }

    /* Modern PayPal donate button */
    .btn-donate{
      display:inline-flex; align-items:center; gap:10px;
      padding:10px 14px; border-radius:999px;
      background:linear-gradient(180deg, color-mix(in oklab, var(--accent), white 14%), var(--accent));
      color:#fff; font-weight:700; text-decoration:none;
      border:1px solid color-mix(in oklab, var(--accent), black 25%);
      box-shadow: 0 6px 18px rgba(0,0,0,.18);
      transition: transform .1s ease, filter .15s ease;
    }
    .btn-donate:hover{ filter:brightness(1.03); }
    .btn-donate:active{ transform: translateY(1px); }
    .btn-donate:focus-visible{ outline:2px solid var(--ring); outline-offset:3px; }

    /* klein PayPal-logo vooraan */
    .btn-donate .pp-logo{
      width:20px; height:20px; display:inline-block;
      background-repeat:no-repeat; background-size:contain;
      /* witte PayPal P inlined als SVG */
      background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'%3E%3Cpath fill='%23fff' d='M17.55 3.01c-1.04-.69-2.78-1.01-5.08-.94H7.09a.9.9 0 0 0-.89.77L4.01 18.6a.6.6 0 0 0 .59.68h3.33l.24-1.5l.46-2.85l.06-.38a.9.9 0 0 1 .89-.77h1.85c3.68 0 6.56-1.49 7.39-5.8c.02-.1.03-.19.05-.28c.24-1.34-.06-2.26-.81-2.69z'/%3E%3C/svg%3E");
    }

    .month-label{ font-weight:600; padding:6px 0; }
    .notice{ background: var(--warn); color:#fff; font-weight:600; padding:10px 14px; border-radius:8px; margin: 0 0 12px 0; }
  </style>

     <link rel="icon" sizes="32x32" href="favicon-32x32.png">
     <link rel="icon" sizes="48x48" href="favicon-48x48.png">
     <link rel="icon" sizes="96x96" href="favicon-96x96.png">
     <link rel="icon" sizes="192x192" href="Favicon-192x192.png">
     <link rel="apple-touch-icon" sizes="180x180" href="favicon-180x180.png">
     <link rel="shortcut icon" type="image/x-icon" href="favicon-48x48.ico">
</head>
<body>
  <div class="container">
    <header>
      <div class="brand">
        <img src="Budgetplanner.png" alt="Budgetplanner logo" class="logo" />
        <div>
          <h1>Budgetplanner BETA 1</h1>
          <div class="muted">Sla per maand op • NL-BE • €</div>
        </div>
      </div>
      <div class="controls">
        <div class="control">
          <label class="muted">Maand</label>
          <span id="month" class="month-label"></span>
        </div>
        <form action="logout.php" method="post" style="margin-left:10px">
            <button type="submit" class="danger">⎋ Uitloggen</button>
        </form>
        <a href="https://www.paypal.com/donate/?hosted_button_id=J474XD68GVAGG"
          target="_blank" rel="noopener"
          class="btn-donate" style="margin-left:10px"
          aria-label="Doneer via PayPal">
          <span class="pp-logo" aria-hidden="true"></span>
          <span>Steun dit project</span>
        </a>
        <button id="nextMonth" class="secondary" title="Ga naar volgende maand en draag over">➡️ Volgende maand</button>
        <details class="menu">
          <summary aria-label="Meer opties">⋯ Meer</summary>
          <div class="menu-panel">
            <button id="toggleTheme" class="secondary" aria-pressed="false" title="Schakel licht/donker">🌗 Thema</button>
            <button id="duplicateFromPrev" class="secondary" title="Kopieer vorige maand">⬇️ Kopieer vorige</button>
            <button id="exportJson" class="secondary">⬇️ Export JSON</button>
            <button id="importJson" class="secondary">⬆️ Importeren</button>
            <button id="exportCsv" class="secondary">🗂️ CSV</button>
            <button id="resetMonth" class="danger">Reset maand</button>
            <button id="clearAllData" class="danger">Verwijder alle data</button>
            <div class="mobile-only-shortcuts">
              <div class="title" style="margin-top:6px">Snelkoppelingen</div>
              <div class="group">
                <button id="nextMonthMenu" class="secondary">➡️ Volgende maand</button>
                <button id="logoutMenu" class="danger">⎋ Uitloggen</button>
                <a href="https://www.paypal.com/donate/?hosted_button_id=J474XD68GVAGG" target="_blank" rel="noopener" class="btn-donate">Steun dit project</a>
              </div>
            </div>
          </div>
        </details>
        <details class="menu" id="viewMenu">
        <summary aria-label="Weergave instellingen">👁️ Weergave</summary>
        <div class="menu-panel">
          <div class="title">Toon secties</div>
          <div class="group">
            <label><input type="checkbox" class="view-toggle" data-section="income" checked> Inkomsten</label>
            <label><input type="checkbox" class="view-toggle" data-section="sparen" checked> Spaar & Beleg</label>
            <label><input type="checkbox" class="view-toggle" data-section="overview" checked> Overzicht</label>
            <label><input type="checkbox" class="view-toggle" data-section="networth" checked> Net worth</label>
            <label><input type="checkbox" class="view-toggle" data-section="investments" checked> Beleggingsoverzicht</label>
            <label><input type="checkbox" class="view-toggle" data-section="goals" checked> Doelen</label>
            <label><input type="checkbox" class="view-toggle" data-section="expenses" checked> Uitgaven per categorie</label>
            <label><input type="checkbox" class="view-toggle" data-section="breakdown" checked> Categorieverdeling</label>
            <label><input type="checkbox" class="view-toggle" data-section="categoryDetails" checked> Details per categorie</label>
          </div>

          <div class="title">Presets</div>
          <div class="group">
            <button class="secondary" data-preset="all">Alles</button>
            <button class="secondary" data-preset="minimal">Minimalistisch</button>
            <button class="secondary" data-preset="invoer">Invoer</button>
            <button class="secondary" data-preset="analyse">Analyse</button>
          </div>
        </div>
        </details>
      </div>
      
    </header>

    <div id="nextMonthNotice" class="notice hidden"></div>

    <section class="grid">
      <div class="card span-6" data-section="income">
        <h2>Inkomsten</h2>
        <div class="stack" id="incomeList"></div>
        <div class="row">
          <button id="addIncome" class="ghost">+ Inkomst toevoegen</button>
        </div>
        <div class="muted" style="margin-top:8px">Tip: voeg vaste premies of kinderbijslag toe als terugkerend.</div>

        <br>
          <div class="card span-6" data-section="sparen">
        <h2>Spaar & Beleg categorieën</h2>
        <div class="stack" id="spaar&belegCategorie"></div>
        <div class="row">
          <button id="addSpaar" class="ghost">+ besparing toevoegen</button>
        </div>
        </div>
      </div>

      <div class="card span-6" data-section="overview">
        <h2>Overzicht</h2>
        <div class="stack">
          <div class="kpi" id="kpiIncome"><span class="num" id="totalIncome">€ 0</span><span class="tag">Inkomsten</span></div>
          <div class="kpi" id="kpiExpense"><span class="num" id="totalExpense">€ 0</span><span class="tag">Uitgaven</span></div>
          <div class="kpi" id="kpiSavings"><span class="num" id="netSavings">€ 0</span><span class="tag">Gespaard (Inkomsten - Uitgaven)</span></div>
          <div class="kpi" id="kpiTotalSaved"><span class="num" id="totalSavedAll">€ 0</span><span class="tag">Gespaard - spaar & beleg categorieën</span></div>
          <div class="kpi" id="kpiTotalSavedThisMonth"><span class="num" id="totalSavedThisMonth">€ 0</span><span class="tag">Totaal die op rekening komt deze maand</span></div>
          <div class="row">
            <span class="pill" id="savingsRate">Spaarquote: 0%</span>
            <span class="pill" id="savingsCategory">Sparen-categorie: € 0</span>
            <span class="pill" id="investCategory">Beleggen-categorie: € 0</span>
            <span class="pill" id="needsWarn"></span>
          </div>
        </div>
        <br>
         <div class="card span-6" data-section="networth">
        <h2>Networth</h2>
        <div class="stack" id="networthList"></div>
        <div class="row" style="margin-top:8px">
          <button id="addNetItem" class="ghost">+ Item toevoegen</button>
        </div>
        <div class="row" style="margin-top:12px; gap:16px">
          <span class="pill big" id="nwTotal">Huidig totaal: € 0</span>
          <span class="pill" id="nwFuture">Toekomstige stortingen (Sparen) deze maand: € 0</span>
          <span class="pill" id="nwFutureInvest">Future inkomsten (Beleggen) totaal: € 0</span>
          <div id="nwFutureInvestTotal" class="pill pill-outline"></div>
          <span class="pill big" id="nwProjection">Projectie eind maand: € 0</span>
        </div>
      </div>
      </div>


      <div class="card span-12" data-section="investments">
        <h2>Beleggingen — Totaal overzicht</h2>
        <div class="stack">
          <div class="row" style="gap:16px; align-items:flex-end; flex-wrap:wrap">
            <span class="pill big" id="invTotalPill">Totaal belegd: € 0</span>

            <div class="field" style="width:200px">
              <label>Voorbije belegging (€)</label>
              <div class="numwrap">
                <input id="pastInvestAmount" type="number" min="0" step="50" inputmode="decimal" value="0"
                       data-id="__inv" data-field="past-invest" data-step50="true" />
                <div class="stepbox">
                  <button type="button" class="step minus" data-id="__inv" data-field="past-invest" data-dir="minus" title="-50">−</button>
                  <button type="button" class="step plus" data-id="__inv" data-field="past-invest" data-dir="plus" title="+50">+</button>
                </div>
              </div>
            </div>
            <button id="addPastInvest" class="ghost">+ Voorbije belegging toevoegen</button>

            <div class="field" style="width:180px">
              <label>Rente toevoegen (%)</label>
              <input id="interestPct" type="number" min="0" step="0.1" inputmode="decimal" value="0" />
            </div>
            <button id="addInterest" class="secondary">+ Interest</button>

            <button id="realizeInvest" class="danger" title="Zet totaal belegd over naar huidig totaal en reset">Uitkeren naar Huidig totaal</button>
          </div>

          <details class="nw-group" id="invHistoryPanel">
            <summary class="row nw-summary" style="justify-content:space-between; align-items:center">
              <span class="arrow">▸</span>
              <div><strong>Geschiedenis beleggingen</strong> <span class="muted" id="invHistMeta"></span></div>
            </summary>
            <div class="stack" id="invHistory" style="margin-top:8px"></div>
          </details>
        </div>
      </div>

      <div class="card span-12" data-section="goals">
        <h2>Doelen</h2>
        <div id="goalsList" class="stack"></div>
        <div class="row" style="margin-top:8px; align-items:center">
          <button id="addGoal" class="ghost">+ Doel toevoegen</button>
          <label class="muted" style="display:flex; align-items:center; gap:8px">
            <input type="checkbox" id="toggleAutoDistribute" />
            Automatisch sparen verdelen bij ‘Volgende maand’
          </label>
        </div>
        <div class="muted" style="margin-top:6px">Tip: stel doelen in (bv. auto €5000). Je kan de spaarbedragen van deze maand proportioneel verdelen of niet door het vinkje aan te zetten.</div>
      </div>

      
     
  

      
      


      <div class="card span-12" data-section="expenses">
        <h2>Uitgaven per categorie</h2>
        <div class="row" style="margin-bottom:10px">
          <button id="addExpense" class="ghost">+ Uitgave</button>
          <button id="addPresets" class="ghost">+ Vooraf ingestelde categorieën</button>
          <select id="categoryFilter" title="Filter op categorie" style="min-width:220px">
            <option value="">Alle categorieën</option>
          </select>
          <span class="pill">Prorata per maand voor kwartaal/jaar</span>
        </div>
        <div style="overflow:auto">
        <table id="expenseTable" aria-describedby="expenseHelp">
          <thead>
            <tr>
              <th>Categorie</th>
              <th>Omschrijving</th>
              <th>Bedrag (€)</th>
              <th>Frequentie</th>
              <th>Vervaldag</th>
              <th>Maandbedrag</th>
              <th>Terugkeerbaar</th>
              <th></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
        </div>
        <div id="expenseHelp" class="muted" style="margin-top:8px">Frequenties: maandelijks, per kwartaal (÷3), jaarlijks (÷12), eenmalig (telt enkel in gekozen maand).</div>
      </div>

      

      <div class="card span-12" data-section="breakdown">
        <h2>Categorieverdeling</h2>
        <canvas id="breakdown"></canvas>
      </div>

      <div class="card span-12" data-section="categoryDetails">
        <h2>Details per categorie</h2>
        <div id="categorySummary" class="stack"></div>
      </div>
    </section>

    <input id="fileInput" type="file" accept="application/json" style="display:none" />
  </div>

  <script>
    // ====== Hulpfuncties ======
    const fmt = new Intl.NumberFormat('nl-BE', { style: 'currency', currency: 'EUR', maximumFractionDigits: 2 });
    const num = n => isFinite(n) ? Number(n) : 0;
    const id = () => Math.random().toString(36).slice(2,9);

    function getMockToday(){
      try{ const iso = localStorage.getItem('mockTodayISO'); return iso ? new Date(iso) : null; }catch(e){ return null; }
    }
    function getToday(){
      const m = getMockToday();
      return (m instanceof Date && !isNaN(m)) ? new Date(m) : new Date();
    }
    function todayYm(){
      const d = getToday();
      return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
    }

    // Format YYYY-MM to Dutch month label (e.g. "september 2025")
    function formatMonthLabel(ym){
      try{
        const [y,m] = String(ym).split('-').map(Number);
        const d = new Date(y, (m||1)-1, 1);
        return d.toLocaleDateString('nl-BE', { month:'long', year:'numeric' });
      }catch(e){
        return ym;
      }
    }

    const DEFAULT_INCOME = [
      { id:id(), label:'Netto maandloon', amount:0, recurring:true },
      { id:id(), label:'Bijberoep', amount:0, recurring:true },
      { id:id(), label:'Andere inkomsten', amount:0, recurring:true },
    ];
    const PRESET_EXPENSES = [
      ['Woning • Hypotheek/Huur','Hypotheek', 0, 'monthly', 1],
      ['Woning • Nutsvoorzieningen','Elektriciteit/Gas', 0, 'monthly', 10],
      ['Woning • Nutsvoorzieningen','Water', 0, 'quarterly', 15],
      ['Internet/Telefonie','Internet + GSM', 0, 'monthly', 25],
      ['Boodschappen','Supermarkt', 0, 'monthly', 28],
      ['Vervoer','Brandstof/OV', 0, 'monthly', 20],
      ['Verzekeringen','Auto/Woning/BA', 0, 'yearly', 30],
      ['Abonnementen','Streaming/Cloud', 0, 'monthly', 12],
      ['Gezondheid','Ziektekosten/Apotheek', 0, 'monthly', 18],
      ['Onderhoud & Herstellingen','Huis/Toestellen', 0, 'monthly', 22],
      ['Vrije tijd','Uit eten/Sport', 0, 'monthly', 27],
      ['Kleding','Kledij/Schoenen', 0, 'monthly', 5],
      ['Onvoorzien','Buffer', 0, 'monthly', 1],
      ['Belastingen & Heffingen','Onroerende voorheffing', 0, 'yearly', 6]
    ];

    const state = {
      month: todayYm(),
      income: structuredClone(DEFAULT_INCOME),
      expenses: [],
      savings: []   // nieuw
    };

    const el = sel => document.querySelector(sel);
    const els = sel => Array.from(document.querySelectorAll(sel));

    // ====== Opslag ======
    // ====== Opslag (server) ======
    async function apiLoad(ym){
      const res = await fetch('load_budget.php?ym=' + encodeURIComponent(ym), { headers: { 'Accept': 'application/json' } });
      if(!res.ok) throw new Error('Load HTTP '+res.status);
      const json = await res.json();
      if(!json.ok) throw new Error(json.message || 'Load error');
      return json.data; // null of object {income, expenses, savings}
    }

    async function apiSave(ym, data){
      const res = await fetch('save_budget.php', {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'Accept':'application/json' },
        body: JSON.stringify({ ym, data })
      });
      if(!res.ok) throw new Error('Save HTTP '+res.status);
      const json = await res.json();
      if(!json.ok) throw new Error(json.message || 'Save error');
      return true;
    }

    // Vervanging voor load/save — GEEN LocalStorage meer voor maanddata
    async function load(m){
      try{
        const data = await apiLoad(m);
        if(data){
          const { income = DEFAULT_INCOME, expenses = [], savings = [] } = data;
          state.income = income; state.expenses = expenses; state.savings = savings;
        } else {
          state.income = structuredClone(DEFAULT_INCOME);
          state.expenses = [];
          state.savings = [];
          // creëer startrecord in DB
          await apiSave(m, { income: state.income, expenses: state.expenses, savings: state.savings });
        }
      }catch(e){
        toast && toast('Kon niet laden van de server.');
        state.income = structuredClone(DEFAULT_INCOME);
        state.expenses = [];
        state.savings = [];
      }
      render();
    }

let saveTimer = null;
let isAdvancingMonth = false;
    async function save(){
      const payload = { income: state.income, expenses: state.expenses, savings: state.savings };
      if(saveTimer) clearTimeout(saveTimer);
      saveTimer = setTimeout(async ()=>{
        try{ await apiSave(state.month, payload); }
        catch(e){ toast && toast('Opslaan mislukt (server).'); }
      }, 300);
    }
    // ====== Meta opslag (server) ======
    let metaState = { networth: [], goals: [], goalsAutoDistribute: false, projectionAdjust: 0, investmentsHistory: [], futureInvestments: 0, view: null };
    let metaSaveTimer = null;

    async function apiLoadMeta(){
      const res = await fetch('load_meta.php', { headers: { 'Accept':'application/json' } });
      if(!res.ok) throw new Error('Load meta HTTP '+res.status);
      const json = await res.json();
      if(!json.ok) throw new Error(json.message||'Load meta error');
      return json.data || {};
    }
    async function apiSaveMeta(data){
      const res = await fetch('save_meta.php', {
        method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'},
        body: JSON.stringify({ data })
      });
      if(!res.ok) throw new Error('Save meta HTTP '+res.status);
      const json = await res.json();
      if(!json.ok) throw new Error(json.message||'Save meta error');
      return true;
    }
function defaultView(){
  // Responsive default: on small screens (<= 900px), only show the sections from the screenshot
  //  - Overzicht (overview)
  //  - Net worth (networth)
  //  - Details per categorie (categoryDetails)
  // On wider screens, keep the original default (everything visible).
  const small = window.matchMedia('(max-width: 900px)').matches;
  if (small) {
    return {
      income:false,
      sparen:false,
      overview:true,
      networth:true,
      investments:false,
      goals:false,
      expenses:false,
      breakdown:false,
      categoryDetails:true
    };
  }
  return { income:true, sparen:true, overview:true, networth:true, investments:true, goals:true, expenses:true, breakdown:true, categoryDetails:true };
}
    function loadMeta(){ return metaState; }
    function saveMeta(next){
      metaState = next;
      if(metaSaveTimer) clearTimeout(metaSaveTimer);
      metaSaveTimer = setTimeout(async()=>{ try{ await apiSaveMeta(metaState); }catch(e){ toast && toast('Opslaan meta mislukt (server).'); } }, 300);
    }
    function loadView(){
      const small = window.matchMedia('(max-width: 900px)').matches;
      // On small screens, enforce the responsive default and ignore saved custom view
      if(small) return defaultView();
      // On larger screens, merge saved preferences with the desktop default
      return metaState.view ? { ...defaultView(), ...metaState.view } : defaultView();
    }
    function saveView(v){ metaState.view = v; saveMeta(metaState); }
    // Future investments helpers (server-backed via meta)
    function getFutureInvestments(){ return Number(metaState.futureInvestments||0); }
    function setFutureInvestments(v){ metaState.futureInvestments = Math.max(0, Number(v)||0); saveMeta(metaState); }
    function pushInvestHistory(entry){
      if(!Array.isArray(metaState.investmentsHistory)) metaState.investmentsHistory = [];
      metaState.investmentsHistory.unshift({ id:id(), date:new Date().toISOString(), ...entry });
      saveMeta(metaState);
    }
    // ====== View settings (persisted) ======
function applyView(){
  const v = loadView();
  Object.keys(defaultView()).forEach(key=>{
    document.querySelectorAll(`[data-section="${key}"]`).forEach(node=>{
      if(v[key]) node.classList.remove('hidden'); else node.classList.add('hidden');
    });
  });
  // Sync checkboxes wanneer menu open is
  document.querySelectorAll('#viewMenu input.view-toggle').forEach(cb=>{
    const k = cb.getAttribute('data-section');
    if(k in v) cb.checked = !!v[k];
  });
}
// If no custom view is saved yet, adapt to screen size changes responsively
window.addEventListener('resize', ()=>{
  if (!metaState.view) { applyView(); }
});
    // ====== Goals helpers ======
    function goalId(){ return 'g_' + Math.random().toString(36).slice(2,9) }
    function saveGoals(goals){
      const meta = loadMeta();
      meta.goals = goals;
      saveMeta(meta);
    }

    function calcProgress(saved, target){
      const t = Math.max(0, Number(target||0));
      const s = Math.max(0, Number(saved||0));
      return t>0 ? Math.min(100, Math.round((s/t)*100)) : 0;
    }

    // ====== Simulation helpers ======
    function runSimulationFV(start, monthly, annualRatePct, years){
      const P = Math.max(0, Number(start||0));
      const PMT = Math.max(0, Number(monthly||0));
      const r = Math.max(0, Number(annualRatePct||0))/100/12; // monthly rate
      const n = Math.max(0, Math.round(Number(years||0)*12));
      if(n===0) return P;
      if(r===0) return P + PMT*n;
      const growth = Math.pow(1+r, n);
      const fvLump = P * growth;
      const fvStream = PMT * ((growth - 1)/r);
      return fvLump + fvStream;
    }

    // ====== Rendering ======
    function render(){
      // Month text value (bv. "september 2025")
      el('#month').textContent = formatMonthLabel(state.month);
      // Disable/enable Volgende maand knop op basis van (mock) vandaag
      (function(){
        const [cy, cm] = state.month.split('-').map(Number);
        const nextMonthStart = new Date(cy, cm, 1);
        const today = getToday(); today.setHours(0,0,0,0);
        const canAdvance = today >= nextMonthStart;
        ['#nextMonth','#nextMonthMenu'].forEach(sel=>{
          const b = document.querySelector(sel);
          if(!b) return;
          // Belangrijk: NIET meer disablen, zodat klik de popup/ toast triggert
          b.disabled = false;
          // geen aria-disabled zetten, knop blijft echt klikbaar
          const nextLabel = nextMonthStart.toLocaleDateString('nl-BE', { month:'long', year:'numeric' });
          b.title = canAdvance ? 'Ga naar volgende maand' : `Je kan pas naar de volgende maand gaan op of na 1 ${nextLabel}`;
          b.classList.toggle('blocked-next', !canAdvance);
        });
      })();

      // Toon hint bovenaan als de volgende maand nu beschikbaar is
      (function(){
        const [cy, cm] = state.month.split('-').map(Number);
        const nextMonthStart = new Date(cy, cm, 1);
        const today = getToday(); today.setHours(0,0,0,0);
        const canAdvance = today >= nextMonthStart;
        const notice = document.getElementById('nextMonthNotice');
        if(notice){
          if(canAdvance){
            notice.textContent = '💡 Vergeet niet naar de volgende maand te gaan!';
            notice.classList.remove('hidden');
          } else {
            notice.classList.add('hidden');
          }
        }
      })();

      // Income list
      const incomeWrap = el('#incomeList');
      incomeWrap.innerHTML = '';
      state.income.forEach(item => {
        const row = document.createElement('div');
        row.className = 'row';
        row.style.justifyContent = 'space-between';
        row.style.alignItems = 'flex-end';
        row.innerHTML = `
          <div class="field" style="flex:1 1 180px">
            <label>Omschrijving</label>
            <input type="text" value="${item.label}" data-id="${item.id}" data-field="income-label"/>
          </div>
          <div class="field" style="width:180px">
            <label>Bedrag (€)</label>
            <div class="numwrap">
              <input type="number" min="0" step="50" inputmode="decimal" value="${item.amount}" data-id="${item.id}" data-field="income-amount" data-step50="true"/>
              <div class="stepbox">
                <button type="button" class="step minus" data-id="${item.id}" data-field="income-amount" data-dir="minus" title="-50">−</button>
                <button type="button" class="step plus" data-id="${item.id}" data-field="income-amount" data-dir="plus" title="+50">+</button>
              </div>
            </div>
          </div>
          <div class="field" style="width:130px">
            <label>Terugkerend</label>
            <select data-id="${item.id}" data-field="income-recurring">
              <option value="true" ${item.recurring? 'selected':''}>Ja</option>
              <option value="false" ${!item.recurring? 'selected':''}>Nee</option>
            </select>
          </div>
          <div class="field" style="width:auto">
            <label>&nbsp;</label>
            <button class="secondary" data-id="${item.id}" data-action="delete-income">Verwijderen</button>
          </div>
        `;
        incomeWrap.appendChild(row);
      });

      // Savings list
      const savingsWrap = el('#spaar\\&belegCategorie'); // let op: & moet ge-escaped worden
      savingsWrap.innerHTML = '';
      state.savings.forEach(item => {
        const row = document.createElement('div');
        row.className = 'row';
        row.style.justifyContent = 'space-between';
        row.style.alignItems = 'flex-end';
        row.innerHTML = `
          <div class="field" style="flex:1 1 180px">
            <label>Omschrijving</label>
            <input type="text" value="${item.label}" data-id="${item.id}" data-field="saving-label"/>
          </div>
          <div class="field" style="width:180px">
            <label>Bedrag (€)</label>
            <div class="numwrap">
              <input type="number" min="0" step="50" inputmode="decimal" value="${item.amount}" data-id="${item.id}" data-field="saving-amount" data-step50="true"/>
              <div class="stepbox">
                <button type="button" class="step minus" data-id="${item.id}" data-field="saving-amount" data-dir="minus">−</button>
                <button type="button" class="step plus" data-id="${item.id}" data-field="saving-amount" data-dir="plus">+</button>
              </div>
            </div>
          </div>
          <div class="field" style="width:130px">
            <label>Terugkerend</label>
            <select data-id="${item.id}" data-field="saving-recurring">
              <option value="true" ${item.recurring? 'selected':''}>Ja</option>
              <option value="false" ${!item.recurring? 'selected':''}>Nee</option>
            </select>
          </div>
          <div class="field" style="width:160px">
            <label>Type</label>
            <select data-id="${item.id}" data-field="saving-kind">
              <option value="sparen" ${(!item.kind || item.kind==='sparen')? 'selected':''}>Sparen</option>
              <option value="beleggen" ${(item.kind==='beleggen')? 'selected':''}>Beleggen</option>
            </select>
          </div>
          <div class="field" style="width:auto">
            <label>&nbsp;</label>
            <button class="danger" data-id="${item.id}" data-action="delete-saving">Verwijderen</button>
          </div>
        `;
        savingsWrap.appendChild(row);
      });

      // Expense table
      const tbody = el('#expenseTable tbody');
      tbody.innerHTML = '';
      const activeFilter = el('#categoryFilter') ? el('#categoryFilter').value : '';
      const visibleExpenses = activeFilter ? state.expenses.filter(e => (e.category || '').trim() === activeFilter) : state.expenses.slice();

      visibleExpenses.forEach(exp => {
        const monthly = monthlyAmount(exp);
        const tr = document.createElement('tr');
        tr.setAttribute('draggable','true');
        tr.dataset.id = exp.id;
        const idCat = `exp-cat-${exp.id}`;
        const idLabel = `exp-label-${exp.id}`;
        const idAmount = `exp-amount-${exp.id}`;
        const idFreq = `exp-freq-${exp.id}`;
        const idDue = `exp-due-${exp.id}`;
        tr.innerHTML = `
          <td>
            <div class="field">
              <label for="${idCat}">Categorie</label>
              <input id="${idCat}" type="text" value="${exp.category}" data-id="${exp.id}" data-field="exp-category"/>
            </div>
          </td>
          <td>
            <div class="field">
              <label for="${idLabel}">Omschrijving</label>
              <input id="${idLabel}" type="text" value="${exp.label}" data-id="${exp.id}" data-field="exp-label"/>
            </div>
          </td>
          <td>
            <div class="field">
              <label for="${idAmount}">Bedrag (€)</label>
              <div class="numwrap">
                <input id="${idAmount}" type="number" min="0" step="50" inputmode="decimal" value="${exp.amount}" data-id="${exp.id}" data-field="exp-amount" data-step50="true"/>
                <div class="stepbox">
                  <button type="button" class="step minus" data-id="${exp.id}" data-field="exp-amount" data-dir="minus" title="-50">−</button>
                  <button type="button" class="step plus" data-id="${exp.id}" data-field="exp-amount" data-dir="plus" title="+50">+</button>
                </div>
              </div>
            </div>
          </td>
          <td>
            <div class="field">
              <label for="${idFreq}">Frequentie</label>
              <select id="${idFreq}" data-id="${exp.id}" data-field="exp-frequency">
                <option value="monthly" ${exp.frequency==='monthly'?'selected':''}>Maandelijks</option>
                <option value="quarterly" ${exp.frequency==='quarterly'?'selected':''}>Kwartaal</option>
                <option value="yearly" ${exp.frequency==='yearly'?'selected':''}>Jaarlijks</option>
                <option value="once" ${exp.frequency==='once'?'selected':''}>Eenmalig</option>
              </select>
            </div>
          </td>
          <td>
            <div class="field">
              <label for="${idDue}">Vervaldag</label>
              <input id="${idDue}" type="number" min="1" max="31" value="${exp.dueDay||1}" data-id="${exp.id}" data-field="exp-dueDay"/>
              <div class="tiny">Dag v/d maand</div>
            </div>
          </td>
          <td>
            <div class="field">
              <label>Maandbedrag</label>
              <div>${fmt.format(monthly)}</div>
            </div>
          </td>
          <td>
            <div class="field">
              <label>Terugkeerbaar</label>
              <select data-id="${exp.id}" data-field="exp-carry">
                <option value="true" ${exp.carry?'selected':''}>Ja</option>
                <option value="false" ${!exp.carry?'selected':''}>Nee</option>
              </select>
            </div>
          </td>
          <td class="actions">
            <button class="secondary" data-id="${exp.id}" data-action="move-up" title="Omhoog">▲</button>
            <button class="secondary" data-id="${exp.id}" data-action="move-down" title="Omlaag">▼</button>
            <button class="secondary" data-id="${exp.id}" data-action="duplicate-exp" title="Dupliceren">⎘</button>
            <button class="secondary" data-id="${exp.id}" data-action="delete-exp" title="Verwijderen">✕</button>
          </td>
        `;
        tbody.appendChild(tr);
      });

      setupDragAndDrop();
      updateCategoryFilter();
// Totals
      const totals = calculateTotals();
      const savingsSparen = state.savings.filter(i=> (i.kind||'sparen')==='sparen').reduce((s,i)=> s + num(i.amount), 0);
      const savingsBeleg = state.savings.filter(i=> i.kind==='beleggen').reduce((s,i)=> s + num(i.amount), 0);
      const totalSavedAll = totals.net - (savingsSparen + savingsBeleg);
      el('#totalSavedThisMonth').textContent = fmt.format(totals.net - savingsBeleg);
      el('#totalIncome').textContent = fmt.format(totals.totalIncome);
      el('#totalExpense').textContent = fmt.format(totals.totalExpenses);
      el('#netSavings').textContent = fmt.format(totals.net);
const rate = totals.totalIncome > 0 
  ? Math.round(((totals.net + savingsSparen + savingsBeleg) / totals.totalIncome) * 100) 
  : 0;      el('#savingsRate').textContent = `Spaarquote: ${isFinite(rate) ? rate : 0}%`;
      const kpiSavings = el('#kpiSavings');
      kpiSavings.classList.toggle('positive', totals.net >= 0);
      kpiSavings.classList.toggle('negative', totals.net < 0);
      const warnEl = el('#needsWarn');
      if(warnEl){
        const oversave = (totals.net - savingsSparen) < 0;
        warnEl.textContent = (totalSavedAll < 0)
          ? 'Let op: je geeft meer uit dan je verdient'
          : (oversave ? 'Je spaart meer dan je deze maand overhoudt' : '');
      }

      const kpiTotal = el('#kpiTotalSaved');
      if(kpiTotal){
        el('#totalSavedAll').textContent = fmt.format(totalSavedAll);
        kpiTotal.classList.toggle('positive', totalSavedAll >= 0);
        kpiTotal.classList.toggle('negative', totalSavedAll < 0);
      }
      const sc = el('#savingsCategory');
if(sc){ sc.textContent = `Sparen-categorie: ${fmt.format(savingsSparen)}`; }

const inv = el('#investCategory');
if(inv){ inv.textContent = `Beleggen-categorie: ${fmt.format(savingsBeleg)}`; }

      // Category summary blocks
      const catMap = groupByCategory();
      const cs = el('#categorySummary');
      cs.innerHTML = '';
      Object.entries(catMap).sort((a,b)=>b[1]-a[1]).forEach(([cat, val], idx) => {
        const div = document.createElement('div');
        div.className = 'row';
        div.innerHTML = `<span class="category-dot" style="background:${colorForIndex(idx)}"></span> <strong style="min-width:220px">${cat}</strong> <span class="muted">${fmt.format(val)} per maand</span>`;
        cs.appendChild(div);
      });

      
      // --- Net worth rendering ---
      const meta = loadMeta();
      const nwWrap = el('#networthList');
      if(nwWrap){
        // --- preserve open/closed state for networth groups ---
        const prevOpen = {
          items: nwWrap.querySelector('details.nw-group.items')?.open || false,
          transfers: nwWrap.querySelector('details.nw-group.transfers')?.open || false,
        };
        nwWrap.innerHTML = '';
        // Tel ALLEEN echte net worth items mee (hebben een numeriek 'amount' en GEEN 'target' attribuut)
        const nwItems = (Array.isArray(meta.networth) ? meta.networth : []).filter(i => i && typeof i === 'object' && 'amount' in i && !('target' in i));
        const transfers = nwItems.filter(i=> /^Overdracht\s\d{4}-\d{2}$/.test(i.label||''));
        const others = nwItems.filter(i=> !/^Overdracht\s\d{4}-\d{2}$/.test(i.label||''));

        // Show non-transfer items, in a collapsible group
        if (others.length > 0) {
          const det = document.createElement('details');
          det.className = 'nw-group items';
          // Add summary for items group
          det.innerHTML = `
            <summary class="row nw-summary" style="justify-content:space-between; align-items:center">
              <span class="arrow">▸</span>
              <div><strong>Items</strong> <span class="muted">(${others.length})</span></div>
            </summary>
            <div class="stack" style="margin-top:8px"></div>
          `;
          // Restore open state if previously open
          if (prevOpen.items) det.open = true;
          const stack = det.querySelector('.stack');
          others.forEach(item => {
            const row = document.createElement('div');
            row.className = 'row';
            row.style.justifyContent = 'space-between';
            row.style.alignItems = 'flex-end';
            row.innerHTML = `
              <div class="field" style="flex:1 1 200px">
                <label>Omschrijving</label>
                <input type="text" value="${item.label}" data-id="${item.id}" data-field="nw-label"/>
              </div>
              <div class="field" style="width:200px">
                <label>Bedrag (€)</label>
                <div class="numwrap">
                  <input type="number" min="0" step="50" inputmode="decimal" value="${item.amount}" data-id="${item.id}" data-field="nw-amount" data-step50="true"/>
                  <div class="stepbox">
                    <button type="button" class="step minus" data-id="${item.id}" data-field="nw-amount" data-dir="minus" title="-50">−</button>
                    <button type="button" class="step plus" data-id="${item.id}" data-field="nw-amount" data-dir="plus" title="+50">+</button>
                  </div>
                </div>
              </div>
              <div class="field" style="width:auto">
                <label>&nbsp;</label>
                <button class="secondary" data-id="${item.id}" data-action="delete-nw">Verwijderen</button>
              </div>
            `;
            stack.appendChild(row);
          });
          nwWrap.appendChild(det);
        }

        // Collapsible group for transfers (overdrachten)
        if(transfers.length){
          const det = document.createElement('details');
          det.className = 'nw-group transfers';
          det.innerHTML = `
            <summary class="row nw-summary" style="justify-content:space-between; align-items:center">
              <span class="arrow">▸</span>
              <div><strong>Overdrachten</strong> <span class="muted">(${transfers.length})</span></div>
            </summary>
            <div class="stack" style="margin-top:8px"></div>
          `;
          // Restore open state if previously open
          if (prevOpen.transfers) det.open = true;
          const stack = det.querySelector('.stack');
          transfers.forEach(item => {
            const row = document.createElement('div');
            row.className = 'row';
            row.style.justifyContent = 'space-between';
            row.style.alignItems = 'flex-end';
            row.innerHTML = `
              <div class="field" style="flex:1 1 200px">
                <label>Omschrijving</label>
                <input type="text" value="${item.label}" data-id="${item.id}" data-field="nw-label"/>
              </div>
              <div class="field" style="width:200px">
                <label>Bedrag (€)</label>
                <div class="numwrap">
                  <input type="number" min="0" step="50" inputmode="decimal" value="${item.amount}" data-id="${item.id}" data-field="nw-amount" data-step50="true"/>
                  <div class="stepbox">
                    <button type="button" class="step minus" data-id="${item.id}" data-field="nw-amount" data-dir="minus" title="-50">−</button>
                    <button type="button" class="step plus" data-id="${item.id}" data-field="nw-amount" data-dir="plus" title="+50">+</button>
                  </div>
                </div>
              </div>
              <div class="field" style="width:auto">
                <label>&nbsp;</label>
                <button class="secondary" data-id="${item.id}" data-action="delete-nw">Verwijderen</button>
              </div>
            `;
            stack.appendChild(row);
          });
          nwWrap.appendChild(det);
        }

        // Neem voor Huidig totaal de overdracht van **vorige maand** mee i.p.v. "laatste lexicografisch".
        // Dit voorkomt dat een per ongeluk latere overdracht (bv. Overdracht 2025-12) het huidige totaal maskeert.
        let latestTransferAmt = 0;
        if (transfers.length) {
          const [cy, cm] = state.month.split('-').map(Number);
          const prevDate = new Date(cy, (cm-1)-1, 1); // vorige maand
          const prevYm = `${prevDate.getFullYear()}-${String(prevDate.getMonth()+1).padStart(2,'0')}`;
          const matching = transfers.find(t => String(t.label||'') === `Overdracht ${prevYm}`);
          if (matching) {
            latestTransferAmt = num(matching.amount);
          } else {
            // Fallback: kies de grootste overdracht <= vorige maand, anders 0
            const prevOrEarlier = transfers
              .map(t => ({ ym: String((t.label||'').split(' ')[1]||''), amount: num(t.amount) }))
              .filter(x => x.ym && x.ym <= prevYm)
              .sort((a,b) => a.ym.localeCompare(b.ym));
            if (prevOrEarlier.length) latestTransferAmt = prevOrEarlier[prevOrEarlier.length-1].amount;
          }
        }
        const othersTotal = others
          .filter(i => i.label !== 'Future inkomsten (Beleggen)')
          .reduce((s,i)=> s + num(i.amount), 0);
        const nwTotal = othersTotal + latestTransferAmt;
        const futureSparen = state.savings.filter(i=> (i.kind||'sparen')==='sparen').reduce((s,i)=> s + num(i.amount), 0);
        const futureInvest = state.savings.filter(i=> i.kind==='beleggen').reduce((s,i)=> s + num(i.amount), 0);
        el('#nwTotal').textContent = `Huidig totaal: ${fmt.format(nwTotal)}`;
        const totalFutureInvest = getFutureInvestments();
        el('#nwFuture').textContent = `Toekomstige stortingen (Sparen) deze maand: ${fmt.format(futureSparen)}`;
        el('#nwFutureInvest').textContent = `Future inkomsten (Beleggen) deze maand: ${fmt.format(futureInvest)}`;
        el('#nwFutureInvestTotal').textContent = `Totaal belegd: ${fmt.format(totalFutureInvest)}`;
        // Projectie = Huidig totaal + (Gespaard - (Sparen + Beleggen)) + Sparen
        // => Huidig totaal + Gespaard - Beleggen - eventuele correctie
        const projDelta = nwTotal + totals.net - futureInvest;
        el('#nwProjection').textContent = `Projectie eind maand: ${fmt.format(projDelta)}`;
        const projPill = el('#nwProjection');
        if(projPill){
          projPill.classList.remove('state-good','state-bad','state-warn');
          if(projDelta > nwTotal) projPill.classList.add('state-good');
          else if(projDelta < nwTotal) projPill.classList.add('state-bad');
          else projPill.classList.add('state-warn');
        }
      }


      // --- Investments Overview rendering ---
      const invTotal = getFutureInvestments();
      const invPill = el('#invTotalPill');
      if(invPill){ invPill.textContent = `Totaal belegd: ${fmt.format(invTotal)}`; }

      const invHistMeta = el('#invHistMeta');
      const invHistWrap = el('#invHistory');
      if(invHistWrap){
        const m3 = loadMeta();
        const hist = Array.isArray(m3.investmentsHistory) ? m3.investmentsHistory : [];
        if(invHistMeta){ invHistMeta.textContent = `(${hist.length} items)`; }
        invHistWrap.innerHTML = '';
        if(hist.length===0){
          const d = document.createElement('div'); d.className='muted'; d.textContent='Nog geen gebeurtenissen.'; invHistWrap.appendChild(d);
        } else {
          hist.forEach(h=>{
            const row = document.createElement('div');
            row.className = 'row';
            const dt = new Date(h.date);
            const label = h.type==='storting' ? 'Storting' : (h.type==='interest' ? 'Interest' : 'Realisatie');
            const note = h.note ? ` — ${h.note}` : '';
            row.innerHTML = `<strong style="min-width:180px">${label}</strong><span class="muted">${dt.toLocaleString('nl-BE')}</span><span style="margin-left:auto">${fmt.format(num(h.amount))}${note}</span>`;
            invHistWrap.appendChild(row);
          });
        }
      }

      // --- Goals rendering ---
      const meta2 = loadMeta();
      const goals = Array.isArray(meta2.goals) ? meta2.goals : [];
      const goalsWrap = el('#goalsList');
      if(goalsWrap){
        goalsWrap.innerHTML = '';
        if(goals.length === 0){
          const empty = document.createElement('div');
          empty.className = 'muted';
          empty.textContent = 'Nog geen doelen. Klik op "+ Doel toevoegen".';
          goalsWrap.appendChild(empty);
        } else {
          goals.forEach(g => {
            const row = document.createElement('div');
            row.className = 'goal-row';
            const prog = calcProgress(g.saved, g.target);
            row.innerHTML = `
              <div class="field" style="flex:1 1 220px">
                <label>Doel</label>
                <input type="text" value="${g.label||''}" data-id="${g.id}" data-field="goal-label"/>
              </div>
              <div class="field" style="width:160px">
                <label>Doelbedrag (€)</label>
                <input type="number" min="0" step="50" value="${Number(g.target||0)}" data-id="${g.id}" data-field="goal-target"/>
              </div>
              <div class="field" style="width:160px">
                <label>Gespaard tot nu (€)</label>
                <input type="number" min="0" step="50" value="${Number(g.saved||0)}" data-id="${g.id}" data-field="goal-saved"/>
              </div>
              <div class="field" style="width:140px">
                <label>Prioriteit</label>
                <select data-id="${g.id}" data-field="goal-prio">
                  <option value="hoog" ${(g.prio||'hoog')==='hoog'?'selected':''}>Hoog</option>
                  <option value="middel" ${(g.prio||'')==='middel'?'selected':''}>Middel</option>
                  <option value="laag" ${(g.prio||'')==='laag'?'selected':''}>Laag</option>
                </select>
              </div>
              <div class="field" style="min-width:220px; flex:1 1 220px">
                <label>Voortgang (${prog}%)</label>
                <div class="progress"><span style="width:${prog}%;"></span></div>
              </div>
              <div class="goal-actions">
                <button class="danger" data-id="${g.id}" data-action="goal-delete">Verwijderen</button>
              </div>
            `;
            goalsWrap.appendChild(row);
          });
        }
      }
      // Toggle state for auto-distribute
      const autoChk = el('#toggleAutoDistribute');
      if(autoChk){
        const m = loadMeta();
        autoChk.checked = !!m.goalsAutoDistribute;
      }

      // --- Simulation defaults ---
      const simStart = el('#simStart');
      const simMonthly = el('#simMonthly');
      if(simStart) simStart.value = (nwWrap ? Number((el('#nwTotal')?.textContent||'').replace(/[^0-9,-]/g,'').replace(',', '.')) : 0) || 0;
      if(simMonthly){
        const mSparen = state.savings.filter(i=> (i.kind||'sparen')==='sparen').reduce((s,i)=> s + num(i.amount), 0);
        simMonthly.value = mSparen || 0;
      }

      drawChart(catMap);
      applyView();
      save();
    }

    function monthlyAmount(exp){
      const amt = num(exp.amount);
      switch(exp.frequency){
        case 'monthly': return amt;
        case 'quarterly': return amt/3;
        case 'yearly': return amt/12;
        case 'once':
          // telt enkel mee als de gekozen maand overeenkomt met dueDay maand? we nemen: alleen wanneer de dueDay in huidige maand zit (altijd waar), maar om eenvoud: wanneer once, telt in de geselecteerde maand.
          return amt;
        default: return amt;
      }
    }

    function calculateTotals(){
      const totalIncome = state.income.reduce((s,i)=> s + num(i.amount), 0);
      const totalExpenses = state.expenses.reduce((s,e)=> s + monthlyAmount(e), 0);
      return { totalIncome, totalExpenses, net: totalIncome - totalExpenses };
    }

    function groupByCategory(){
      const map = {};
      state.expenses.forEach(e=>{
        const k = e.category?.trim() || 'Overig';
        map[k] = (map[k]||0) + monthlyAmount(e);
      });
      return map;
    }
    // ====== Filter en Drag & Drop helpers ======
    function updateCategoryFilter(){
      const sel = el('#categoryFilter');
      if(!sel) return;
      const current = sel.value;
      const cats = [...new Set(state.expenses.map(e => (e.category || '').trim()).filter(Boolean))].sort((a,b)=>a.localeCompare(b));
      sel.innerHTML = '<option value="">Alle categorieën</option>' + cats.map(c => `<option value="${c}">${c}</option>`).join('');
      if(current && cats.includes(current)) sel.value = current;
    }

    function setupDragAndDrop(){
      const tbody = el('#expenseTable tbody');
      if(!tbody) return;

      tbody.onDragStartHandler && tbody.removeEventListener('dragstart', tbody.onDragStartHandler);
      tbody.onDragEndHandler && tbody.removeEventListener('dragend', tbody.onDragEndHandler);
      tbody.onDragOverHandler && tbody.removeEventListener('dragover', tbody.onDragOverHandler);
      tbody.onDropHandler && tbody.removeEventListener('drop', tbody.onDropHandler);

      let draggingId = null;

      tbody.onDragStartHandler = function(e){
        const tr = e.target.closest('tr');
        if(!tr) return;
        draggingId = tr.dataset.id;
        tr.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
      };
      tbody.onDragEndHandler = function(e){
        const tr = e.target.closest('tr');
        if(tr) tr.classList.remove('dragging');
        draggingId = null;
        Array.from(tbody.children).forEach(r => r.classList.remove('drag-over'));
      };
      tbody.onDragOverHandler = function(e){
        e.preventDefault();
        const tr = e.target.closest('tr');
        if(!tr) return;
        Array.from(tbody.children).forEach(r => r.classList.remove('drag-over'));
        tr.classList.add('drag-over');
        e.dataTransfer.dropEffect = 'move';
      };
      tbody.onDropHandler = function(e){
        e.preventDefault();
        const targetTr = e.target.closest('tr');
        if(!targetTr || !draggingId) return;
        const from = state.expenses.findIndex(x => x.id === draggingId);
        const to = state.expenses.findIndex(x => x.id === targetTr.dataset.id);
        if(from > -1 && to > -1 && from !== to){
          const [row] = state.expenses.splice(from,1);
          state.expenses.splice(to,0,row);
          render();
        }
      };

      tbody.addEventListener('dragstart', tbody.onDragStartHandler);
      tbody.addEventListener('dragend', tbody.onDragEndHandler);
      tbody.addEventListener('dragover', tbody.onDragOverHandler);
      tbody.addEventListener('drop', tbody.onDropHandler);
    }


    // ====== Events ======
document.addEventListener('change', (ev)=>{

      const t = ev.target;
      // View toggles
if(t.classList && t.classList.contains('view-toggle')){
  const k = t.getAttribute('data-section');
  const v = loadView();
  if(k){ v[k] = !!t.checked; saveView(v); applyView(); }
  return; // niet verder verwerken
}

      const idAttr = t.getAttribute('data-id');
      const field = t.getAttribute('data-field');
      // Toggle auto-distribute goals when moving to next month
      if(t.id === 'toggleAutoDistribute'){
        const meta = loadMeta();
        meta.goalsAutoDistribute = !!t.checked;
        saveMeta(meta);
        return;
      }
      if(!idAttr || !field) return;

      // Income updates
      
      if(field.startsWith('nw-')){
        const meta = loadMeta();
        const item = meta.networth.find(x=>x.id===idAttr);
        if(item){
          if(field==='nw-label') item.label = t.value;
          if(field==='nw-amount') item.amount = Number(t.value || 0);
          saveMeta(meta);
          render();
        }
        return;
      }

      // Goals updates
      if(field && field.startsWith('goal-')){
        const meta = loadMeta();
        const g = (meta.goals||[]).find(x=>x.id===idAttr);
        if(g){
          if(field==='goal-label') g.label = t.value;
          if(field==='goal-target') g.target = Number(t.value||0);
          if(field==='goal-saved') g.saved = Math.max(0, Number(t.value||0));
          if(field==='goal-prio') g.prio = t.value;
          saveMeta(meta);
          render();
        }
        return;
      }

      if (field.startsWith('saving-')) {
        const item = state.savings.find(x => x.id===idAttr);
        if(!item) return;

        if(field==='saving-label') item.label = t.value;

        if(field==='saving-amount'){
          const proposed = Number(t.value || 0);

          // Nieuwe regel: sparen/beleggen mag SAMEN nooit groter zijn dan wat er netto overblijft
          // Bepaal wat er al gealloceerd is aan sparen+beleggen (exclusief dit item)
          const totalsNow = calculateTotals();
          const totalAllocatedOther = state.savings
            .filter(s => s.id !== item.id)
            .reduce((s, it) => s + num(it.amount), 0);

          // Maximaal beschikbaar voor dit item = netto budget minus reeds gealloceerd (geen negatieven)
          const maxForThis = Math.max(0, totalsNow.net - totalAllocatedOther);

          if(proposed > maxForThis){
            item.amount = maxForThis;
            t.value = String(maxForThis);
            toast(`Je kan maximaal ${fmt.format(maxForThis)} ${item.kind === 'beleggen' ? 'beleggen' : 'sparen'} deze maand.`);
            render();
            return;
          }
          item.amount = proposed;
        }

        if(field==='saving-recurring') item.recurring = t.value==='true';

        if(field==='saving-kind'){
          item.kind = t.value;

          // Controleer onmiddellijk of huidig bedrag nog binnen het maximum past
          const totalsNow = calculateTotals();
          const totalAllocatedOther = state.savings
            .filter(s => s.id !== item.id)
            .reduce((s, it) => s + num(it.amount), 0);
          const maxForThis = Math.max(0, totalsNow.net - totalAllocatedOther);

          if(num(item.amount) > maxForThis){
            item.amount = maxForThis;
            toast(`Je kan maximaal ${fmt.format(maxForThis)} ${item.kind === 'beleggen' ? 'beleggen' : 'sparen'} deze maand. Het bedrag is aangepast.`);
          }
        }
        render();
        return;
      }

      if(field.startsWith('income-')){
        const item = state.income.find(x=>x.id===idAttr);
        if(!item) return;
        if(field==='income-label') item.label = t.value;
        if(field==='income-amount') item.amount = Number(t.value || 0);
        if(field==='income-recurring') item.recurring = t.value==='true';
        render();
        return;
      }

      // Expense updates
      const exp = state.expenses.find(x=>x.id===idAttr);
      if(!exp) return;
      if(field==='exp-category') exp.category = t.value;
      if(field==='exp-label') exp.label = t.value;
      if(field==='exp-amount') exp.amount = Number(t.value || 0);
      if(field==='exp-frequency') exp.frequency = t.value;
      if(field==='exp-dueDay') exp.dueDay = Number(t.value || 1);
      if(field==='exp-carry') exp.carry = (t.value==='true');
      render();
    });

    // Keyboard up/down arrow step for 50 increments on amount fields
    document.addEventListener('keydown', (ev)=>{
      const t = ev.target;
      if(!(t instanceof HTMLInputElement)) return;
      if(t.type !== 'number' || !t.hasAttribute('data-step50')) return;
      if(ev.key === 'ArrowUp' || ev.key === 'ArrowDown'){
        ev.preventDefault();
        const cur = Number(t.value || 0);
        const delta = 50 * (ev.key === 'ArrowUp' ? 1 : -1);
        const next = cur + delta;
        t.value = String(Math.max(Number(t.min||0), next));
        // Trigger input to update state
        const e = new Event('input', {bubbles:true});
        t.dispatchEvent(e);
      }
    });

    // Hold-to-repeat for + / - steppers
    let stepHoldTimer = null;
    function bumpInputBy(button){
      const idAttr = button.getAttribute('data-id');
      const field = button.getAttribute('data-field');
      const dir = button.getAttribute('data-dir');
      const input = document.querySelector(`input[data-id="${idAttr}"][data-field="${field}"]`);
      if(!input) return;
      const cur = Number(input.value || 0);
      const delta = dir === 'plus' ? 50 : -50;
      const next = Math.max(Number(input.min||0), cur + delta);
      input.value = String(next);
      input.dispatchEvent(new Event('change', {bubbles:true}));
    }

    document.addEventListener('pointerdown', (ev)=>{
      const step = ev.target.closest('.step');
      if(!step) return;
      ev.preventDefault();
      bumpInputBy(step);
      stepHoldTimer = setInterval(()=> bumpInputBy(step), 120);
    });
    ['pointerup','pointercancel','pointerleave'].forEach(evt=>{
      document.addEventListener(evt, ()=>{
        if(stepHoldTimer){ clearInterval(stepHoldTimer); stepHoldTimer=null; }
      });
    });

document.addEventListener('click', async (ev)=>{
      const btn = ev.target.closest('button');
      if(!btn) return;
      // Mobile menu proxies
      if(btn.id === 'nextMonthMenu'){ const real = document.getElementById('nextMonth'); if(real){ real.click(); } return; }
      if(btn.id === 'logoutMenu'){ const form = document.querySelector('form[action="logout.php"]'); if(form){ form.submit(); } return; }
      const idAttr = btn.getAttribute('data-id');
      const action = btn.getAttribute('data-action');

      // Goals UI buttons
      if(btn.id === 'addGoal'){
        const meta = loadMeta();
        meta.goals.push({ id: goalId(), label:'Nieuw doel', target: 0, saved: 0, prio:'hoog' });
        saveMeta(meta);
        render();
        return;
      }
      if(action === 'goal-delete'){
        const meta = loadMeta();
        const g = (meta.goals||[]).find(x=>x.id===idAttr);
        if(!g) { render(); return; }

        const savedAmt = Math.max(0, num(g.saved||0));
        const labelTxt = g.label || '';
        if(!confirm(`Weet je zeker dat je het doel "${labelTxt}" wil verwijderen?`)) return;

        // 1) Doel verwijderen
        meta.goals = (meta.goals||[]).filter(x=>x.id!==idAttr);

        // 2) Gespaarde bedrag van Huidig totaal aftrekken via negatieve correctie
        if(savedAmt > 0){
          const corrLabel = `Correctie doel verwijderd: ${labelTxt}`;
          meta.networth = Array.isArray(meta.networth) ? meta.networth : [];
          meta.networth.push({ id:id(), label: corrLabel, amount: -savedAmt });
        }

        saveMeta(meta);
        render();
        toast(savedAmt > 0 ? `Doel verwijderd. ${fmt.format(savedAmt)} afgetrokken van Huidig totaal.` : 'Doel verwijderd.');
        return;
      }

      if(btn && btn.id === 'runSim'){
        const start = Number(el('#simStart')?.value||0);
        const monthly = Number(el('#simMonthly')?.value||0);
        const rate = Number(el('#simRate')?.value||0);
        const years = Number(el('#simYears')?.value||0);
        const extra = Number(el('#simExtra')?.value||0);
        const fv = runSimulationFV(start, monthly, rate, years);
        const fvExtra = runSimulationFV(start, monthly + extra, rate, years);
        el('#simResult').textContent = `Resultaat: ${fmt.format(fv)}`;
        el('#simDelta').textContent = `+ Extra wat-als: ${fmt.format(fvExtra - fv)}`;
        return;
      }

      if(btn.id==='addIncome'){
        state.income.push({ id:id(), label:'Nieuwe inkomst', amount:0, recurring:true });
        render();
        return;
      }
      if (btn.id === 'addSpaar') {
        state.savings.push({ id:id(), label:'Nieuwe besparing', amount:0, recurring:true, kind:'sparen' });
        render();
        return;
      }
      if(action==='delete-saving'){
        const idx = state.savings.findIndex(x=>x.id===idAttr);
        if(idx>-1) state.savings.splice(idx,1);
        render();
        return;
      }
      if(btn.id==='addExpense'){
        state.expenses.push({ id:id(), category:'Overig', label:'Nieuwe uitgave', amount:0, frequency:'monthly', dueDay:1, carry:false });
        render();
        return;
      }
      
      if(btn.id==='addNetItem'){
        const meta = loadMeta();
        meta.networth.push({ id:id(), label:'Nieuw item', amount:0 });
        saveMeta(meta);
        render();
        // Zorg dat de nieuwe groep zichtbaar is (openklappen bij eerste item)
        requestAnimationFrame(()=>{
          const det = document.querySelector('#networthList details.nw-group.items');
          if(det) det.open = true;
        });
        return;
      }

      if(btn.id==='addPresets'){
        PRESET_EXPENSES.forEach(([cat,label,amount,frequency,dueDay])=>{
          state.expenses.push({ id:id(), category:cat, label, amount, frequency, dueDay, carry:false });
        });
        render();
        return;
      }
      if(btn.id==='resetMonth'){
        if(confirm('Weet je zeker dat je deze maand volledig wil resetten?')){
          state.income = structuredClone(DEFAULT_INCOME);
          state.expenses = [];
          state.savings = [];
          await apiSave(state.month, { income: state.income, expenses: state.expenses, savings: state.savings });
          render();
          toast('Maand gereset (server-opslag).');
        }
        return;
      }
      if(btn.id==='clearAllData'){
        if(!confirm('Weet je zeker dat je ALLE data (alle maanden + meta) wil verwijderen? Dit kan niet ongedaan gemaakt worden.')) return;
        try{
          const res = await fetch('clear_all.php', { method:'POST', headers:{ 'Accept':'application/json' } });
          const json = await res.json();
          if(!res.ok || !json.ok){ throw new Error(json.message||('HTTP '+res.status)); }

          // Reset lokale state
          state.income = structuredClone(DEFAULT_INCOME);
          state.expenses = [];
          state.savings = [];

          // Reset meta-state
          metaState = { networth: [], goals: [], goalsAutoDistribute: false, projectionAdjust: 0, investmentsHistory: [], futureInvestments: 0, view: null };

          // Zet maand terug naar actuele maand
          state.month = todayYm();
          const monthInput = document.getElementById('month');
          if(monthInput) monthInput.textContent = formatMonthLabel(state.month);

          // Sla lege huidige maand & meta meteen op zodat DB een startrecord heeft
          await apiSave(state.month, { income: state.income, expenses: state.expenses, savings: state.savings });
          await apiSaveMeta(metaState);

          // Laad en render
          await load(state.month);
          toast('Alle data verwijderd en maand teruggezet naar huidige maand.');
        } catch(e){
          toast('Verwijderen mislukt op de server. Probeer opnieuw.');
        }
        return;
      }
      if(btn.id==='duplicateFromPrev'){
        const [y,m] = state.month.split('-').map(Number);
        const prev = new Date(y, m-2, 1); // maand -1
        const prevYm = `${prev.getFullYear()}-${String(prev.getMonth()+1).padStart(2,'0')}`;
        try{
          const prevData = await apiLoad(prevYm);
          if(!prevData){ toast('Geen gegevens van vorige maand gevonden op de server.'); return; }
          const { income = DEFAULT_INCOME, expenses = [], savings = [] } = prevData;
          state.income = income; state.expenses = expenses; state.savings = savings;
          render();
          await apiSave(state.month, { income: state.income, expenses: state.expenses, savings: state.savings });
          toast('Vorige maand gekopieerd (server).');
        }catch(e){ toast('Kon vorige maand niet laden.'); }
        return;
      }
      if(btn.id==='exportJson'){
        const data = JSON.stringify({ month: state.month, income: state.income, expenses: state.expenses, savings: state.savings }, null, 2);
        download(`budget-${state.month}.json`, data, 'application/json');
        return;
      }
      if(btn.id==='importJson'){
        el('#fileInput').click();
        return;
      }
      if(btn.id==='exportCsv'){
        const rows = [
          ['Type','Categorie','Omschrijving','Bedrag','Frequentie','Vervaldag','Maand','Maandbedrag'],
        ];
        state.income.forEach(i=>{
          rows.push(['Inkomst','', i.label, i.amount, i.recurring?'recurring':'one-off','', state.month, i.amount]);
        });
        state.expenses.forEach(e=>{
          rows.push(['Uitgave', e.category, e.label, e.amount, e.frequency, e.dueDay||'', state.month, monthlyAmount(e)]);
        });
        state.savings.forEach(s=>{
          rows.push(['Besparing', s.kind||'sparen', s.label, s.amount, s.recurring?'recurring':'one-off','', state.month, s.amount]);
        });
        const csv = rows.map(r=> r.map(v => typeof v==='string' && v.includes(',') ? '"'+String(v).replaceAll('"','""')+'"' : v).join(',')).join('\n');
        download(`budget-${state.month}.csv`, csv, 'text/csv');
        return;
      }
      
if(btn.id==='nextMonth'){
        // Guard: prevent re-entry
        if (isAdvancingMonth) { toast('Bezig met maand wisselen…'); return; }
        isAdvancingMonth = true;
        // === Gate: blokkeren tot het effectief de 1ste is van de volgende maand ===
          {
            const [cy, cm] = state.month.split('-').map(Number); // cm = 1..12
            const nextMonthStart = new Date(cy, cm, 1); // 1e dag van de volgende maand
            const today = getToday();
            today.setHours(0,0,0,0);
            if (today < nextMonthStart) {
              const msg = 'Je kan pas naar de volgende maand gaan op of na de 1e van de volgende maand.';
              if (typeof toast === 'function') toast(msg); else alert(msg);
              isAdvancingMonth = false; // guard resetten
              return; // *** STOP: actie NIET uitvoeren ***
            }
          }
        try {
          const tmpTotals = calculateTotals();
          const tmpFutureSparen = state.savings.filter(i => (i.kind || 'sparen') === 'sparen').reduce((s, i) => s + num(i.amount), 0);
          const tmpFutureBeleg = state.savings.filter(i => i.kind === 'beleggen').reduce((s, i) => s + num(i.amount), 0);
          const availableAfterSparen = tmpTotals.net - tmpFutureSparen;
          if (availableAfterSparen < 0) {
            toast('Je spaart meer dan je deze maand overhoudt. Je kan nog steeds doorgaan naar de volgende maand.');
          }
          if (availableAfterSparen <= 0 && tmpFutureBeleg > 0){
            toast('Je probeert te beleggen terwijl er geen geld meer over is deze maand. Zet je belegging op 0 of pas je budget aan.');
            isAdvancingMonth = false;
            return;
          }

          // ===== Maand +1 berekenen =====
          const [y,m] = state.month.split('-').map(Number);
          const nextDate = new Date(y, (m-1)+1, 1);
          const nextYm = `${nextDate.getFullYear()}-${String(nextDate.getMonth()+1).padStart(2,'0')}`;

          // === Idempotente bescherming: voer overdracht/invest/goal-distributie max. 1x per BRONMAAND uit ===
          const srcMonth = state.month; // maand die we verlaten
          const metaOnce = loadMeta();
          if(!Array.isArray(metaOnce.transferredMonths)) metaOnce.transferredMonths = [];
          const alreadyTransferred = metaOnce.transferredMonths.includes(srcMonth);

          if (!alreadyTransferred) {
            // ===== Net worth overdracht (zet projectie van huidige maand als startbedrag voor volgende maand) =====
            {
              const meta = metaOnce; // gebruik hetzelfde meta object
              const nwItemsNow = (Array.isArray(meta.networth) ? meta.networth : []).filter(i => i && typeof i === 'object' && 'amount' in i && !('target' in i));
              const transfersNow = nwItemsNow.filter(i=> /^Overdracht\s\d{4}-\d{2}$/.test(i.label||''));
              const othersNow = nwItemsNow.filter(i=> !/^Overdracht\s\d{4}-\d{2}$/.test(i.label||''));
              let latestTransferAmtNow = 0;
              if (transfersNow.length) {
                const latestT = transfersNow.reduce((acc, cur)=>{
                  const lm = String((acc.label||'').split(' ')[1]||'');
                  const cm = String((cur.label||'').split(' ')[1]||'');
                  return (cm > lm) ? cur : acc;
                });
                latestTransferAmtNow = num(latestT.amount);
              }
              const othersTotalNow = othersNow
                .filter(i => i.label !== 'Future inkomsten (Beleggen)')
                .reduce((s,i)=> s + num(i.amount), 0);
              const nwTotalNow = othersTotalNow + latestTransferAmtNow;

              const projEnd = nwTotalNow + tmpTotals.net - tmpFutureBeleg;
              const carry = Math.round(projEnd * 100) / 100; // negatieve waarden toegestaan

              const transferLabel = `Overdracht ${state.month}`;
              meta.networth = (Array.isArray(meta.networth) ? meta.networth : []).filter(i => (i.label||'') !== transferLabel);
              meta.networth.push({ id:id(), label: transferLabel, amount: carry });
              saveMeta(meta);
            }

            // ===== Beleggingen van deze maand boeken naar 'totaal belegd' =====
            {
              const investSum = tmpFutureBeleg; // alles wat als 'beleggen' in sparen & beleg staat
              if(investSum > 0){
                const meta = metaOnce; // hetzelfde meta object blijven gebruiken
                setFutureInvestments(getFutureInvestments() + investSum);
                let investItem = meta.networth.find(i => i.label === 'Future inkomsten (Beleggen)');
                if (investItem) investItem.amount += investSum; 
                else meta.networth.push({ id:id(), label:'Future inkomsten (Beleggen)', amount: investSum });
                saveMeta(meta);
                pushInvestHistory({ type:'storting', amount: investSum, note:'automatisch bij maandwissel' });
              }
            }

            // ===== Doelen: automatisch sparen verdelen (indien geactiveerd) =====
            try{
              const meta = metaOnce; // zelfde meta object
              if (meta && meta.goalsAutoDistribute) {
                const totalToDistribute = tmpFutureSparen; // enkel sparen (geen beleggen)
                if (totalToDistribute > 0 && Array.isArray(meta.goals) && meta.goals.length) {
                  const openGoals = meta.goals
                    .map(g => ({...g, target: num(g.target), saved: num(g.saved)}))
                    .filter(g => g.target > g.saved);
                  if (openGoals.length) {
                    const prioWeight = (p)=> ({ 'hoog':3, 'middel':2, 'laag':1 })[String(p||'hoog')] || 1;
                    const totalWeight = openGoals.reduce((s,g)=> s + prioWeight(g.prio), 0) || 1;

                    let remaining = totalToDistribute;
                    const adds = new Map();
                    openGoals.forEach(g => {
                      const need = Math.max(0, g.target - g.saved);
                      const share = (totalToDistribute * prioWeight(g.prio)) / totalWeight;
                      const add = Math.min(need, Math.max(0, Math.round(share * 100) / 100));
                      adds.set(g.id, add);
                    });
                    let used = 0; for(const v of adds.values()) used += v;
                    remaining = Math.max(0, Math.round((totalToDistribute - used) * 100) / 100);

                    if (remaining > 0) {
                      const sorted = openGoals.slice().sort((a,b)=> (prioWeight(b.prio) - prioWeight(a.prio)));
                      for (const g of sorted) {
                        if (remaining <= 0) break;
                        const needLeft = Math.max(0, g.target - (g.saved + (adds.get(g.id)||0)));
                        if (needLeft <= 0) continue;
                        const extra = Math.min(needLeft, remaining);
                        adds.set(g.id, (adds.get(g.id)||0) + extra);
                        remaining = Math.max(0, Math.round((remaining - extra) * 100) / 100);
                      }
                    }

                    meta.goals = meta.goals.map(g => {
                      const inc = adds.get(g.id)||0; if(inc>0){ g.saved = num(g.saved) + inc; }
                      return g;
                    });
                    saveMeta(meta);
                    toast(`Sparen verdeeld over doelen: ${fmt.format(totalToDistribute)}`);
                  }
                }
              }
            } catch(e){ /* negeer verdelingsfouten zodat maandwissel doorgaat */ }

            // Markeer deze bronmaand als afgehandeld zodat we niet dubbel tellen
            if(!metaOnce.transferredMonths.includes(srcMonth)){
              metaOnce.transferredMonths.push(srcMonth);
              saveMeta(metaOnce);
            }
          }

          // ===== Nieuwe maand-record opbouwen =====
          const newIncome = state.income.map(i => ({...i, amount: i.recurring ? i.amount : 0}));
          const newExpenses = state.expenses.map(e => ({...e, amount: e.carry ? e.amount : 0}));
          const newSavings  = state.savings.map(s => ({...s, amount: s.recurring ? s.amount : 0}));

          // Sla eerst de nieuwe maand op
          await apiSave(nextYm, { income:newIncome, expenses:newExpenses, savings:newSavings });

          // Zet UI-state en input meteen naar volgende maand (direct zichtbaar)
          state.month = nextYm;
          const monthInput = document.getElementById('month');
          if(monthInput) monthInput.textContent = formatMonthLabel(state.month);
          // Laad daarna de maand (server) en render
          await load(state.month);
          toast('Volgende maand aangemaakt, beleggingen overgeboekt en naar server opgeslagen.');
        } finally {
          isAdvancingMonth = false;
        }
        return;
      }

      if(btn.id==='addPastInvest'){
        const amt = Math.max(0, Number(el('#pastInvestAmount')?.value||0));
        if(!isFinite(amt) || amt<=0){ toast('Voer een geldig bedrag in.'); return; }
        const meta = loadMeta();
        const prev = getFutureInvestments();
        setFutureInvestments(prev + amt);
        let investItem = meta.networth.find(i => i.label === 'Future inkomsten (Beleggen)');
        if (investItem) investItem.amount += amt; else meta.networth.push({ id:id(), label:'Future inkomsten (Beleggen)', amount: amt });
        saveMeta(meta);
        pushInvestHistory({ type:'storting', amount: amt });
        const input = el('#pastInvestAmount'); if(input) input.value = '0';
        render();
        toast('Voorbije belegging toegevoegd.');
        return;
      }

      if(btn.id==='addInterest'){
        const pct = Math.max(0, Number(el('#interestPct')?.value||0));
        if(!isFinite(pct) || pct<=0){ toast('Voer een geldige rente (%).'); return; }
        const total = getFutureInvestments();
        if(total<=0){ toast('Er is nog geen totaal belegd bedrag.'); return; }
        const inc = total * (pct/100);
        setFutureInvestments(total + inc);
        const meta = loadMeta();
        let investItem = meta.networth.find(i => i.label === 'Future inkomsten (Beleggen)');
        if (investItem) investItem.amount += inc; else meta.networth.push({ id:id(), label:'Future inkomsten (Beleggen)', amount: inc });
        saveMeta(meta);
        pushInvestHistory({ type:'interest', amount: inc, note: `( +${pct}% )` });
        const ip = el('#interestPct'); if(ip) ip.value = '0';
        render();
        toast('Interest toegevoegd aan totaal belegd.');
        return;
      }

      if(btn.id==='realizeInvest'){
        const total = getFutureInvestments();
        if(total<=0){ toast('Geen belegd bedrag om uit te keren.'); return; }
        const meta = loadMeta();
        meta.networth.push({ id:id(), label:`Belegging gerealiseerd ${state.month}`, amount: total });
        let investItem = meta.networth.find(i => i.label === 'Future inkomsten (Beleggen)');
        if(investItem) investItem.amount = 0;
        setFutureInvestments(0);
        saveMeta(meta);
        pushInvestHistory({ type:'realisatie', amount: total });
        render();
        toast('Totaal belegd uitgekeerd naar Huidig totaal en teruggezet naar 0.');
        return;
      }

      if(btn.id==='toggleTheme'){
        const light = document.documentElement.classList.toggle('light');
        btn.setAttribute('aria-pressed', light ? 'true':'false');
        return;
      }

      // Net worth row actions
      if(action==='delete-nw'){
        const meta = loadMeta();
        const idx = meta.networth.findIndex(x=>x.id===idAttr);
        if(idx>-1){
          const wasInvestAggregate = (meta.networth[idx].label === 'Future inkomsten (Beleggen)');
          meta.networth.splice(idx,1);
          saveMeta(meta);
          if(wasInvestAggregate){
            // Reset totaal belegd als het aggregaat-item wordt verwijderd
            setFutureInvestments(0);
          }
        }
        render();
        return;
      }

      // Row actions
      if(action==='delete-income'){
        const idx = state.income.findIndex(x=>x.id===idAttr);
        if(idx>-1) state.income.splice(idx,1);
        render();
        return;
      }
      if(action==='delete-exp'){
        const idx = state.expenses.findIndex(x=>x.id===idAttr);
        if(idx>-1) state.expenses.splice(idx,1);
        render();
        return;
      }
      if(action==='duplicate-exp'){
        const e = state.expenses.find(x=>x.id===idAttr);
        if(e){
          const copy = {...e, id:id(), label:e.label+ ' (kopie)'};
          state.expenses.push(copy);
          render();
        }
        return;
      }
      if(action==='move-up' || action==='move-down'){
        const idx = state.expenses.findIndex(x=>x.id===idAttr);
        if(idx>-1){
          const to = action==='move-up' ? idx-1 : idx+1;
          if(to>=0 && to<state.expenses.length){
            const [row] = state.expenses.splice(idx,1);
            state.expenses.splice(to,0,row);
            render();
          }
        }
        return;
      }
      // View presets
if(btn && btn.hasAttribute('data-preset')){
  const p = btn.getAttribute('data-preset');
  const base = defaultView();
  let next = { ...base };
  if(p==='all'){
    next = { ...base };
  } else if(p==='minimal'){
    next = { income:false, sparen:true, overview:true, networth:true, goals:false, expenses:true, breakdown:false, categoryDetails:false };
  } else if(p==='invoer'){
    next = { income:true, sparen:true, overview:false, networth:false, goals:false, expenses:true, breakdown:false, categoryDetails:false };
  } else if(p==='analyse'){
    next = { income:false, sparen:false, overview:true, networth:true, goals:true, expenses:true, breakdown:true, categoryDetails:true };
  }
  saveView(next);
  applyView();
  return;
}
    });

    // Filter per categorie
    document.addEventListener('change', (e)=>{
      if(e.target && e.target.id === 'categoryFilter'){
        render();
      }
    });

    el('#fileInput').addEventListener('change', (ev)=>{
      const file = ev.target.files?.[0];
      if(!file) return;
      const reader = new FileReader();
      reader.onload = () => {
        try{
          const data = JSON.parse(reader.result);
          if(data.month) state.month = data.month;
          if(Array.isArray(data.income)) state.income = data.income;
          if(Array.isArray(data.expenses)) state.expenses = data.expenses;
          if(Array.isArray(data.savings)) state.savings = data.savings;
          render();
        }catch(err){ toast('Kon JSON niet lezen.'); }
      };
      reader.readAsText(file);
      ev.target.value = '';
    });

    el('#month').addEventListener('change', (ev)=>{
      state.month = ev.target.value || todayYm();
      load(state.month);
    });

    function download(filename, content, mime){
      const blob = new Blob([content], {type:mime});
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(a.href);
    }

    // Sluit het opties-menu bij klik buiten
    document.addEventListener('click', (e)=>{
      const dd = document.querySelector('details.menu');
      if(!dd) return;
      if(!dd.hasAttribute('open')) return;
      if(!dd.contains(e.target)) dd.removeAttribute('open');
    });

    // Toast helper (non-blocking notifications)
function toast(msg, undoCb){
  let t = document.getElementById('toast');
  if(!t){
    t = document.createElement('div');
    t.id = 'toast';
    document.body.appendChild(t);
  }
  // inhoud opbouwen
  t.innerHTML = '';
  const span = document.createElement('span');
  span.textContent = msg;
  t.appendChild(span);

  // optionele Undo-knop
  if(typeof undoCb === 'function'){
    const btn = document.createElement('button');
    btn.textContent = 'Ongedaan maken';
    btn.className = 'secondary';
    btn.style.marginLeft = '10px';
    btn.addEventListener('click', ()=>{
      try { undoCb(); }
      finally {
        t.className = '';
        clearTimeout(t._timer);
      }
    });
    t.appendChild(btn);
  }

  t.className = 'show';
  clearTimeout(t._timer);
  t._timer = setTimeout(()=>{ t.className=''; }, 4000);
}

    // ====== Mini chart (zonder externe libs) ======
    function colorForIndex(i){
      const colors = [
        '#4f8cff','#15c39a','#ff8a4a','#b968ff','#36c2ff','#ffd166','#ef476f','#06d6a0','#118ab2','#8338ec','#ff006e'
      ];
      return colors[i % colors.length];
    }

    function drawChart(catMap){
      const canvas = el('#breakdown');
      const ctx = canvas.getContext('2d');

      // Sort categories by value (desc) and aggregate tail into "Overig" if many
      const sorted = Object.entries(catMap).sort((a,b)=>b[1]-a[1]);
      let items = sorted;
      if(sorted.length > 10){
        const head = sorted.slice(0,10);
        const tail = sorted.slice(10);
        const rest = tail.reduce((s,[,v])=>s+v,0);
        items = head.concat([[`Overig (${tail.length})`, rest]]);
      }

      const labels = items.map(([k])=>k);
      const values = items.map(([,v])=>v);
      const bars = values.length || 1;

      // Dynamic sizing
      const pad = 28;          // top/bottom padding
      const gap = 12;          // gap between bars
      const barH = 26;         // bar height
      const cssHeight = Math.max(240, pad*2 + bars*(barH+gap));
      canvas.style.height = cssHeight + 'px';

      const dpr = window.devicePixelRatio || 1;
      const w = canvas.clientWidth;
      const h = cssHeight;
      canvas.width  = w * dpr;
      canvas.height = h * dpr;
      ctx.setTransform(dpr,0,0,dpr,0,0);
      ctx.clearRect(0,0,w,h);

      // Chart area (reserve space on left for labels)
      const chartLeft = 180;
      const chartRightPad = 150;
      const chartTop = pad;
      const chartBottom = h - pad;
      const chartWidth = Math.max(10, w - chartLeft - chartRightPad);

      const maxV = Math.max(...values, 1);
      // Use explicit high-contrast colors per theme
      const isLight = document.documentElement.classList.contains('light');
      const borderCol = isLight ? '#e6eaf1' : '#1e2630';
      const textCol   = isLight ? '#101418' : '#e7edf6';
      const mutedCol  = isLight ? '#475569' : '#b7c2d3';

      // Vertical gridlines and tick labels (0%,25%,50%,75%,100%)
      ctx.strokeStyle = borderCol;
      ctx.fillStyle = mutedCol;
      ctx.font = '13px system-ui';
      for(let i=0;i<=4;i++){
        const x = chartLeft + (i/4)*chartWidth;
        ctx.beginPath(); ctx.moveTo(x, chartTop); ctx.lineTo(x, chartBottom); ctx.stroke();
        const v = maxV * (i/4);
        const label = fmt.format(v);
        ctx.fillText(label, x - ctx.measureText(label).width/2, chartTop - 6);
      }

      // Bars
      labels.forEach((lab, i)=>{
        const y = chartTop + i*(barH+gap);
        const len = (values[i] / maxV) * chartWidth;

        // Bar
        ctx.fillStyle = colorForIndex(i);
        ctx.fillRect(chartLeft, y, len, barH);

        // Left label
        ctx.fillStyle = textCol;
        ctx.font = '500 12px system-ui';
        const clippedLab = lab.length>30 ? lab.slice(0,28) + '…' : lab;
        ctx.fillText(clippedLab, 12, y + barH - 8);

        // Value at bar end
        ctx.font = '13px system-ui';
        ctx.fillText(fmt.format(values[i]), chartLeft + len + 6, y + barH - 8);
      });
    }

    // Init
    (function init(){
      // Thema voorkeur opslaan
      const storedTheme = localStorage.getItem('budget:theme');
      if(storedTheme==='light') document.documentElement.classList.add('light');
      el('#toggleTheme').addEventListener('click', ()=>{
        const light = document.documentElement.classList.contains('light');
        localStorage.setItem('budget:theme', light? 'light':'dark');
      });

      state.month = todayYm();
      load(state.month);
      updateCategoryFilter();
      // Prefill simulation on first render
      setTimeout(()=>{ render(); }, 0);
      // Prepare toast container
if(!document.getElementById('toast')){
  const t = document.createElement('div');
  t.id = 'toast';
  document.body.appendChild(t);
}
      // Apply stored view en sync bij openen

applyView();
const viewMenu = document.querySelector('#viewMenu');
if(viewMenu){
  viewMenu.addEventListener('toggle', ()=>{ if(viewMenu.open) applyView(); });
}
    })();



    // Bootstrapping: load meta from server before first render
(async function boot(){
  try{ const data = await apiLoadMeta(); metaState = { ...metaState, ...data }; }
  catch(e){ /* keep defaults */ }
  applyView();
  // init month input and load current month from server
  const mm = el('#month'); if(mm && !mm.value) mm.value = state.month;
  await load(state.month);
})();
(function initMonthInput(){
  const monthInputEl = document.getElementById('month');
  if(!monthInputEl) return;
  if(!monthInputEl.value) monthInputEl.value = state.month;
  if(monthInputEl._wired) return;
  monthInputEl.addEventListener('change', async (e)=>{
    state.month = e.target.value || todayYm();
    await load(state.month);
  });
  monthInputEl._wired = true;
})();
  // ===== Boot: meta laden, maand laden, month-input koppelen =====
(async function boot(){
  try { const data = await apiLoadMeta(); metaState = { ...metaState, ...data }; } catch(e) {}
  applyView();
  const monthInputEl = document.getElementById('month');
  if(monthInputEl){
    if(!monthInputEl.value) monthInputEl.value = state.month;
    if(!monthInputEl._wired){
      monthInputEl.addEventListener('change', async (e)=>{
        state.month = e.target.value || todayYm();
        await load(state.month);
      });
      monthInputEl._wired = true;
    }
  }
  await load(state.month);
})();




</script>

  
</body>
</html>
