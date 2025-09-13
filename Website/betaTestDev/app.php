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
<html lang="nl" class="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Budgetplanner BETA 2</title>
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
      #expenseTable td:nth-child(8){--label:'Rekening'}
      #expenseTable td:nth-child(9){--label:'Acties'}
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
      position: relative;
    }
    details.menu > summary::-webkit-details-marker{display:none}
    .menu-panel .menu-close{
      position:absolute; top:8px; right:8px;
      border:1px solid var(--border);
      background:var(--panel);
      color:var(--muted);
      font-weight:700;
      border-radius:8px;
      width:28px; height:28px;
      display:inline-flex; align-items:center; justify-content:center;
      cursor:pointer;
    }
    .menu-panel .menu-close:focus-visible{ outline:2px solid var(--ring); outline-offset:2px; }
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

    /* Enforce mobile visibility: ONLY overview, networth, categoryDetails */
@media (max-width: 900px){
  [data-section]:not([data-section="overview"]):not([data-section="networth"]):not([data-section="categoryDetails"]) {
    display: none !important;
  }
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
   
    button.icon-btn {
  /* compact icon-only button with subtle circular background */
  width: 32px !important;
  height: 32px !important;
  padding: 0 !important;
  margin: 0 !important;
  border-radius: 999px !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  background: color-mix(in oklab, var(--panel), blue 6%) !important; /* zacht, werkt in licht & donker */
  border: 1px solid var(--border) !important;
  color: inherit !important;   /* geen rood */
  cursor: pointer;
  line-height: 2;
  position: relative;
}
button.icon-btn:hover {
  background: var(--danger) !important;
  color: #fff !important;
  border-color: color-mix(in oklab, var(--danger), black 20%) !important;
}
    /* Inline hover label next to icon buttons (uses the title attribute) */
    button.icon-btn::after{
      content: attr(title);
      position: absolute;
      left: calc(100% + 8px);
      top: 50%;
      transform: translateY(-50%) translateX(4px);
      background: var(--panel);
      color: var(--text);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 4px 6px;
      font-size: 12px;
      line-height: 1;
      white-space: nowrap;
      box-shadow: 0 4px 12px rgba(0,0,0,.18);
      opacity: 0;
      pointer-events: none;
      transition: opacity .12s ease, transform .12s ease;
    }
    button.icon-btn:hover::after{
      opacity: 1;
      transform: translateY(-50%) translateX(0);
    }
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
          <h1>Budgetplanner BETA 2</h1>
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
            <button type="button" class="menu-close" aria-label="Sluiten">✕</button>
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
          <button type="button" class="menu-close" aria-label="Sluiten">✕</button>
          <div class="title">Toon secties</div>
          <div class="group">
            <label><input type="checkbox" class="view-toggle" data-section="income" checked> Inkomsten</label>
            <label><input type="checkbox" class="view-toggle" data-section="sparen" checked> Beleg verdeling</label>
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

        <div id="incomeSplitHost" class="stack"></div>

        <br>
          <div class="card span-6" data-section="sparen">
        <h2>Beleg verdeling</h2>
        <div class="stack" id="spaar&belegCategorie"></div>
        <div class="row">
          <button id="addSpaar" class="ghost">+ belegging toevoegen</button>
        </div>
        </div>
      </div>

      <div class="card span-6" data-section="overview">
        <h2>Overzicht</h2>
        <div class="stack">
          <div class="kpi" id="kpiIncome"><span class="num" id="totalIncome">€ 0</span><span class="tag">Inkomsten</span></div>
          <div class="kpi" id="kpiExpense"><span class="num" id="totalExpense">€ 0</span><span class="tag">Uitgaven</span></div>
          <div class="kpi" id="kpiSavings"><span class="num" id="netSavings">€ 0</span><span class="tag">Gespaard (Inkomsten - Uitgaven)</span></div>
          <div class="kpi" id="kpiTotalSaved"><span class="num" id="totalSavedAll">€ 0</span><span class="tag">Gespaard - beleggen</span></div>
          <div class="kpi" id="kpiTotalSavedThisMonth"><span class="num" id="totalSavedThisMonth">€ 0</span><span class="tag">Totaal die op rekening komt deze maand</span></div>
          <div class="row">
            <span class="pill" id="savingsRate">Spaarquote: 0%</span>
            <span class="pill" id="investCategory">Beleggen-categorie: € 0</span>
            <span class="pill" id="needsWarn"></span>
          </div>
        </div>
        <br>
         <div class="card span-6" data-section="networth">
        <h2>Networth</h2>

        <div class="stack" id="networthList"></div>
        <div class="row" style="margin-top:8px; gap:10px">
          <button id="addAccount" class="ghost">+ Rekening toevoegen</button>
          <button id="addNetItem" class="ghost">+ Item toevoegen</button>
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

            <button id="realizeInvest" class="danger" title="Zet totaal belegd over naar huidig totaal en reset">Uitkeren naar geselecteerde rekening</button>
            <div class="field" style="width:240px">
              <label>Uitkeren naar rekening</label>
              <select id="realizeToAccount"></select>
            </div>
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
          
        </div>
        <div class="muted" style="margin-top:6px">Tip: stel doelen in (bv. auto €5000). Vul de bedragen handmatig in.
          </div>
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
              <th>Rekening</th>
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

  
  <script src="script.js?v=<?php echo time(); ?>" defer></script>
</body>
</html>

      