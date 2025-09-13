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

    // Null‑safe class helpers
    function tgl(node, cls, on){ if(node && node.classList){ node.classList.toggle(cls, !!on); } }
    function addCls(node, ...cls){ if(node && node.classList){ cls.forEach(c=>node.classList.add(c)); } }
    function rmCls(node, ...cls){ if(node && node.classList){ cls.forEach(c=>node.classList.remove(c)); } }

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
  applyView();
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

    // ====== Accounts helpers ======
function listAccounts(){
  const m = loadMeta();
  const all = Array.isArray(m.networth) ? m.networth : [];
  return all.filter(x => x && x.type === 'rekening');
}
function accountsOptions(selectedId){
  const accs = listAccounts();
  if(!accs.length) return '<option value="">— Geen rekeningen —</option>';
  return '<option value="">— Kies rekening —</option>' + accs.map(a =>
    `<option value="${a.id}" ${a.id===selectedId?'selected':''}>${(a.label||'Rekening')} — ${fmt.format(num(a.amount||0))}</option>`
  ).join('');
}
function creditAccount(accountId, amount){
  const m = loadMeta();
  const acc = (Array.isArray(m.networth)?m.networth:[]).find(x=> x && x.type==='rekening' && x.id===accountId);
  if(!acc) return false;
  acc.amount = num(acc.amount) + num(amount);
  saveMeta(m);
  return true;
}
function debitAccount(accountId, amount){
  return creditAccount(accountId, -num(amount));
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
          tgl(b, 'blocked-next', !canAdvance);
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
    <div class="field" style="width:220px">
      <label>Rekening</label>
      <select data-id="${item.id}" data-field="income-account">${accountsOptions(item.accountId||'')}</select>
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
          <div class="field" style="width:220px">
            <label>Rekening</label>
            <select data-id="${item.id}" data-field="saving-account">${accountsOptions(item.accountId||'')}</select>
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
          <td>
            <div class="field">
              <label>Rekening</label>
              <select data-id="${exp.id}" data-field="exp-account">${accountsOptions(exp.accountId||'')}</select>
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
      const categoriesSum = savingsSparen + savingsBeleg; // totaal toegekend aan spaar + beleg categorieën

      // Totaal die op rekening komt = Inkomsten − Uitgaven − (alle beleggen)
      const savedToAccountsThisMonth = totals.net - savingsBeleg; // kan negatief zijn
      el('#totalSavedThisMonth').textContent = fmt.format(savedToAccountsThisMonth);

      el('#totalIncome').textContent = fmt.format(totals.totalIncome);
      el('#totalExpense').textContent = fmt.format(totals.totalExpenses);
      el('#netSavings').textContent = fmt.format(totals.net);

      // Spaarquote: aandeel van (sparen + beleggen) t.o.v. inkomen (zonder dubbel te tellen)
      const rate = totals.totalIncome > 0 
        ? Math.round((categoriesSum / totals.totalIncome) * 100) 
        : 0;
      el('#savingsRate').textContent = `Spaarquote: ${isFinite(rate) ? rate : 0}%`;

      const kpiSavings = el('#kpiSavings');
      tgl(kpiSavings, 'positive', totals.net >= 0);
      tgl(kpiSavings, 'negative', totals.net < 0);
      const kpiTotalGuard = el('#kpiTotalSaved');
      if(kpiTotalGuard){
        // no-op toggle to ensure presence; avoids null access elsewhere if styles expect classes
        kpiTotalGuard.classList.contains('kpi');
      }
      const warnEl = el('#needsWarn');
      if(warnEl){
        const oversave = categoriesSum > totals.net; // je alloceert meer dan je netto overhoudt
        warnEl.textContent = oversave ? 'Je spaart meer dan je deze maand overhoudt' : '';
      }

      const kpiTotal = el('#kpiTotalSaved');
      const netAfterAlloc = totals.net - categoriesSum; // Inkomsten − Uitgaven − (Sparen + Beleggen)
      if(kpiTotal){
        el('#totalSavedAll').textContent = fmt.format(netAfterAlloc);
      }
      tgl(kpiTotal, 'positive', netAfterAlloc > 0);
      tgl(kpiTotal, 'negative', netAfterAlloc < 0);
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
        // Preserve open/closed state of groups
        const prevOpen = {
          accounts: nwWrap.querySelector('details.nw-group.accounts')?.open || false,
          items: nwWrap.querySelector('details.nw-group.items')?.open || false,
          transfers: nwWrap.querySelector('details.nw-group.transfers')?.open || false,
        };
        nwWrap.innerHTML = '';

        const all = Array.isArray(meta.networth) ? meta.networth : [];
        const accounts = all.filter(x => x && typeof x === 'object' && x.type === 'rekening');
        // legacy items = have numeric amount and are not goals (no target) and not accounts
        const legacyOrItems = all.filter(i => i && typeof i === 'object' && 'amount' in i && !('target' in i) && i.type !== 'rekening');
        // Alle items, inclusief overdrachten
        const itemsAll = legacyOrItems; // alle items, inclusief overdrachten

        // --- Accounts group (Rekeningen) ---
        const accDet = document.createElement('details');
        accDet.className = 'nw-group accounts';
        accDet.innerHTML = `
          <summary class="row nw-summary" style="justify-content:space-between; align-items:center">
            <span class="arrow">▸</span>
            <div><strong>Rekeningen</strong> <span class="muted">(${accounts.length})</span></div>
          </summary>
          <div class="stack" style="margin-top:8px"></div>
        `;
        if(prevOpen.accounts) accDet.open = true;

        // === Accounts summary (ALWAYS visible above Networth totals) ===
        const accSummary = document.createElement('div');
        accSummary.className = 'stack';
        accSummary.style.margin = '0 0 8px 0';

        // helper can be used here as well (defined below)
        let sumCur = 0; let sumProj = 0;
        accounts.forEach(ac => {
          const cur = num(ac.amount||0);
          const proj = (typeof projectAccount === 'function') ? projectAccount(ac) : cur;
          sumCur += cur; sumProj += proj;
          const projClass = proj > cur ? 'state-good' : (proj < cur ? 'state-bad' : 'state-warn');
          const row = document.createElement('div');
          row.className = 'row';
          row.style.cssText = 'justify-content: space-between; align-items: flex-end;';
          row.innerHTML = `
            <div class="field" style="flex:1 1 200px">
              <label>${ac.label || 'Rekening'}</label>
            </div>
            <div class="field" style="width:200px">
              <label>Huidig (€)</label>
              <span class="pill big">${fmt.format(cur)}</span>
            </div>
            <div class="field" style="width:220px">
              <label>Projectie eind maand</label>
              <span class="pill big ${projClass}">${fmt.format(proj)}</span>
            </div>
          `;
          accSummary.appendChild(row);
        });
        // footer row: totals (now with top pills for current and projected values)
        const sumRow = document.createElement('div');
        sumRow.className = 'row';
        sumRow.style.cssText = 'justify-content: space-between; align-items: center; margin-top:6px; padding-top:8px; border-top:1px solid var(--border)';
        sumRow.innerHTML = `
          <div class="field" style="flex:1 1 200px">
            <strong>Totaal</strong>
          </div>
          <div class="field" style="width:200px">
            <label>Huidig (€)</label>
            <span class="pill big" id="accSumCurPill"></span>
          </div>
          <div class="field" style="width:220px">
            <label>Projectie eind maand</label>
            <span class="pill big" id="accSumProjPill"></span>
          </div>
        `;
        accSummary.appendChild(sumRow);
        // place summary block before the collapsible accounts details
        nwWrap.appendChild(accSummary);

        const accStack = accDet.querySelector('.stack');
        // Helper: projection per account = current + (inkomsten to this account) + (sparen to this account) - (monthly expenses from this account) + networth item adds
        function projectAccount(ac){
          const cur = num(ac.amount);
          const addSavings = state.savings
            .filter(s => (s.kind||'sparen') === 'sparen' && s.accountId === ac.id)
            .reduce((s, it) => s + num(it.amount), 0);
          const investDebits = state.savings
            .filter(s => s.kind === 'beleggen' && s.accountId === ac.id)
            .reduce((s, it) => s + num(it.amount), 0);
          const incCredits = state.income
            .filter(i => i.accountId === ac.id)
            .reduce((s,i) => s + num(i.amount), 0);
          const expDebits = state.expenses
            .filter(e => e.accountId === ac.id)
            .reduce((s, e) => s + monthlyAmount(e), 0);
          // Nieuw: Networth-items die naar deze rekening stromen (geen overdrachten)
          const itemAdds = (Array.isArray(legacyOrItems)?legacyOrItems:[])
            .filter(i => i && !/^Overdracht\s\d{4}-\d{2}$/.test(i.label||'') && i.accountId === ac.id)
            .reduce((s,i) => s + num(i.amount), 0);
          return cur + incCredits - investDebits - expDebits + itemAdds; //sparen niet bijgeteld
        }
        let projAccountsTotal = 0;
        accounts.forEach(ac => {
          const row = document.createElement('div');
          row.className = 'row';
          row.style.justifyContent = 'space-between';
          row.style.alignItems = 'flex-end';
          const projected = projectAccount(ac);
          projAccountsTotal += projected;
          const projClass = projected > num(ac.amount) ? 'state-good' : (projected < num(ac.amount) ? 'state-bad' : 'state-warn');
          row.innerHTML = `
            <div class="field" style="flex:1 1 200px">
              <label>Rekeningnaam</label>
              <input type="text" value="${ac.label||''}" data-id="${ac.id}" data-field="acct-label"/>
            </div>
            <div class="field" style="width:200px">
              <label>Huidig (€)</label>
              <div class="numwrap">
                <input type="number" min="0" step="50" inputmode="decimal" value="${Number(ac.amount||0)}" data-id="${ac.id}" data-field="acct-amount" data-step50="true"/>
                <div class="stepbox">
                  <button type="button" class="step minus" data-id="${ac.id}" data-field="acct-amount" data-dir="minus" title="-50">−</button>
                  <button type="button" class="step plus"  data-id="${ac.id}" data-field="acct-amount" data-dir="plus"  title="+50">+</button>
                </div>
              </div>
            </div>
            <div class="field" style="width:220px">
              <label>Projectie eind maand</label>
              <span class="pill big ${projClass}">${fmt.format(projected)}</span>
            </div>
            <div class="field" style="width:auto">
              <label>&nbsp;</label>
              <button class="secondary" data-id="${ac.id}" data-action="delete-acct">Verwijderen</button>
            </div>
          `;
          accStack.appendChild(row);
        });
        // (Footer removed, now handled by the always-visible summary above.)
        nwWrap.appendChild(accDet);

        // --- Other items (non-transfers) ---
        // (leave unchanged block for rendering, only calculation below changes)
        const transfers = legacyOrItems.filter(i=> /^Overdracht\s\d{4}-\d{2}$/.test(i.label||''));
        const others = legacyOrItems.filter(i=> !/^Overdracht\s\d{4}-\d{2}$/.test(i.label||''));

        // === Top banner: elementen zonder rekening (inkomsten, sparen, uitgaven, items) ===
// === Top banner: elementen zonder rekening (inkomsten, sparen, uitgaven) ===
(function(){
  const incomeMissing   = state.income.filter(i => num(i.amount) > 0 && !i.accountId).length;
  const savingsMissing  = state.savings.filter(s => num(s.amount) > 0 && !s.accountId).length; // zowel sparen als beleggen
  const expensesMissing = state.expenses.filter(e => num(monthlyAmount(e)) > 0 && !e.accountId).length;

  const totalMissing = incomeMissing + savingsMissing + expensesMissing;

  let host = document.getElementById('nextMonthNotice');
  let banner = document.getElementById('unassignedNotice');
  if(!banner){
    banner = document.createElement('div');
    banner.id = 'unassignedNotice';
    banner.className = 'notice hidden';
    banner.style.cursor = 'pointer';
    if(host && host.parentNode){
      host.parentNode.insertBefore(banner, host.nextSibling);
    } else {
      document.body.prepend(banner);
    }
    banner.addEventListener('click', ()=>{
      if(incomeMissing){ showSectionAndScroll('income'); return; }
      if(savingsMissing){ showSectionAndScroll('sparen'); return; }
      if(expensesMissing){ showSectionAndScroll('expenses'); return; }
    });
  }

  if(totalMissing > 0){
    const parts = [];
    if(incomeMissing)   parts.push(`Inkomsten: ${incomeMissing}`);
    if(savingsMissing)  parts.push(`Sparen: ${savingsMissing}`);
    if(expensesMissing) parts.push(`Uitgaven: ${expensesMissing}`);
    banner.innerHTML = `⚠️ Je hebt elementen zonder rekening, ga niet naar de volgende maand — ${parts.join(' · ')}.`;
    banner.classList.remove('hidden');
  } else {
    banner.classList.add('hidden');
  }
})();

        // --- Items group (niet-overdrachten) ---
        if(others.length){
          const det = document.createElement('details');
          det.className = 'nw-group items';
          det.innerHTML = `
            <summary class="row nw-summary" style="justify-content:space-between; align-items:center">
              <span class="arrow">▸</span>
              <div><strong>Items</strong> <span class="muted">(${others.length})</span></div>
            </summary>
            <div class="stack" style="margin-top:8px"></div>
          `;
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
                <input type="text" value="${item.label||''}" data-id="${item.id}" data-field="nw-label"/>
              </div>
              <div class="field" style="width:200px">
                <label>Bedrag (€)</label>
                <div class="numwrap">
                  <input type="number" min="0" step="50" inputmode="decimal" value="${Number(item.amount||0)}" data-id="${item.id}" data-field="nw-amount" data-step50="true"/>
                  <div class="stepbox">
                    <button type="button" class="step minus" data-id="${item.id}" data-field="nw-amount" data-dir="minus" title="-50">−</button>
                    <button type="button" class="step plus"  data-id="${item.id}" data-field="nw-amount" data-dir="plus"  title="+50">+</button>
                  </div>
                </div>
              </div>
              <div class="field" style="width:220px">
                <label>Rekening</label>
                <select data-id="${item.id}" data-field="nw-account">${accountsOptions(item.accountId||'')}</select>
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

        // --- Transfers group ---
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
                    <button type="button" class="step plus"  data-id="${item.id}" data-field="nw-amount" data-dir="plus"  title="+50">+</button>
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

        // Huidig totaal = som van alle rekeningen + alle items (incl. overdrachten)
        const accountsTotal = accounts.reduce((s,i)=> s + num(i.amount), 0);
        const itemsTotal = itemsAll.reduce((s,i)=> s + num(i.amount), 0);
        const nwTotal = accountsTotal + itemsTotal;

        // Extra info (optionele elementen kunnen ontbreken in de markup)
        const futureSparen = state.savings.filter(i=> (i.kind||'sparen')==='sparen').reduce((s,i)=> s + num(i.amount), 0);
        const futureInvest = state.savings.filter(i=> i.kind==='beleggen').reduce((s,i)=> s + num(i.amount), 0);
        const totalFutureInvest = getFutureInvestments();
        const nf = el('#nwFuture'); if(nf){ nf.textContent = `Toekomstige stortingen (Sparen) deze maand: ${fmt.format(futureSparen)}`; }
        const nfi = el('#nwFutureInvest'); if(nfi){ nfi.textContent = `Future inkomsten (Beleggen) deze maand: ${fmt.format(futureInvest)}`; }
        const nfit = el('#nwFutureInvestTotal'); if(nfit){ nfit.textContent = `Totaal belegd: ${fmt.format(totalFutureInvest)}`; }

        // Items zonder gekozen rekening blijven als losse items meetellen
        const unassignedItemsTotal = itemsAll
          .filter(i => !/^Overdracht\s\d{4}-\d{2}$/.test(i.label||'') && !i.accountId)
          .reduce((s,i)=> s + num(i.amount), 0)
          + itemsAll
          .filter(i => /^Overdracht\s\d{4}-\d{2}$/.test(i.label||''))
          .reduce((s,i)=> s + num(i.amount), 0);
        const projEom = (typeof projAccountsTotal === 'number' ? projAccountsTotal : accountsTotal) + unassignedItemsTotal + Number(savedToAccountsThisMonth || 0);
        // NOTE: intentionally removed update of #nwTotal and #nwProjection elements

        // Set the top pills to **accounts-only** totals (no items/unassigned flows)
        const accCurPill = document.getElementById('accSumCurPill');
        const accProjPill = document.getElementById('accSumProjPill');
        if(accCurPill){ accCurPill.textContent = fmt.format(accountsTotal); }
        if(accProjPill){
          accProjPill.textContent = fmt.format(projAccountsTotal);
          accProjPill.classList.remove('state-good','state-bad','state-warn');
          if(projAccountsTotal > accountsTotal) accProjPill.classList.add('state-good');
          else if(projAccountsTotal < accountsTotal) accProjPill.classList.add('state-bad');
          else accProjPill.classList.add('state-warn');
        }

        // Optionally hide the old bottom pills so the top ones replace them visually
        const bottomWrap = document.querySelector('#nwTotal')?.closest('.field');
        if(bottomWrap) bottomWrap.classList.add('hidden');
        const bottomProjWrap = document.querySelector('#nwProjection')?.closest('.field');
        if(bottomProjWrap) bottomProjWrap.classList.add('hidden');
      }


      // --- Investments Overview rendering ---
      const invTotal = getFutureInvestments();
      const invPill = el('#invTotalPill');
      if(invPill){ invPill.textContent = `Totaal belegd: ${fmt.format(invTotal)}`; }
      const selRealize = el('#realizeToAccount');
      if(selRealize){ selRealize.innerHTML = accountsOptions(selRealize.value||''); }

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
              <div class="field" style="width:220px">
                <label>Rekening</label>
                <select data-id="${g.id}" data-field="goal-account">${accountsOptions(g.accountId||'')}</select>
              </div>
              <div class="field" style="min-width:220px; flex:1 1 220px">
                <label>Voortgang (${prog}%)</label>
                <div class="progress"><span style="width:${prog}%;"></span></div>
              </div>
              <div class="goal-actions">
                <button class="danger ${!g.accountId ? 'soft-disabled' : ''}" data-id="${g.id}" data-action="goal-delete" title="${!g.accountId ? 'Selecteer eerst een rekening' : 'Verwijderen'}">Verwijderen</button>
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

async function applyAccountFlows(){
  // 1) Inkomsten: storten op gekozen rekening
  for(const inc of state.income){
    const amt = num(inc.amount);
    if(amt > 0 && inc.accountId){
      creditAccount(inc.accountId, amt);
    }
  }

  // 2) Spaar- & Belegregels
  for(const s of state.savings){
    const amt = num(s.amount);
    if(amt <= 0) continue;

    if((s.kind||'sparen') === 'sparen'){
      // Sparen verhoogt de geselecteerde rekening
      if(s.accountId){ creditAccount(s.accountId, amt); }
    } else {
      // Beleggen: geld vertrekt van de rekening én gaat naar investeringspot
      if(s.accountId){ debitAccount(s.accountId, amt); }
      const cur = getFutureInvestments();
      setFutureInvestments(cur + amt);
      pushInvestHistory({ type:'storting', amount: amt, note: s.label||'' });
    }
  }

  // 3) Uitgaven: maandbedrag aftrekken van gekozen rekening
  for(const e of state.expenses){
    if(!e.accountId) continue;
    const mAmt = monthlyAmount(e);
    if(mAmt > 0){ debitAccount(e.accountId, mAmt); }
  }

  // tussentijds renderen om KPIs bij te werken
  render();
}
    // ====== Events ======
    function validateAccountAssignments(){
  const missing = [];
  // incomes with amount > 0 need account
  state.income.forEach(i=>{ if(num(i.amount)>0 && !i.accountId){ missing.push(`Inkomst: ${i.label||'—'}`); } });
  // SPAREN én BELEGGEN met amount > 0 vereisen een rekening
  state.savings.forEach(s=>{
    if (num(s.amount)>0 && !s.accountId){
      missing.push(`${(s.kind||'sparen')==='beleggen' ? 'Beleggen' : 'Sparen'}: ${s.label||'—'}`);
    }
  });
  // expenses with monthlyAmount > 0 need account
  state.expenses.forEach(e=>{ if(num(monthlyAmount(e))>0 && !e.accountId){ missing.push(`Uitgave: ${e.label||e.category||'—'}`); } });
  // goals should have an account (bron/doelrekening bekend)
  const meta = loadMeta();
  (Array.isArray(meta.goals)?meta.goals:[]).forEach(g=>{ if(!g.accountId){ missing.push(`Doel: ${g.label||'—'}`); } });

  if(missing.length){
    const msg = `Kies eerst een rekening voor:\n• ` + missing.slice(0,6).join('\n• ') + (missing.length>6? `\n… (+${missing.length-6} meer)`: '');
    if(typeof toast === 'function') toast(msg); else alert(msg);
    return false;
  }
  return true;
}

// Intercept Next Month clicks (ook in mobiele menu)
document.addEventListener('DOMContentLoaded', () => {
  ['#nextMonth','#nextMonthMenu'].forEach(sel=>{
    const btn = document.querySelector(sel);
    if(!btn) return;
    btn.addEventListener('click', (e)=>{
      if(!validateAccountAssignments()){
        e.preventDefault();
        e.stopImmediatePropagation();
        // Removed illegal top-level return statement
      }
    }, true); // capture: blokkeer vroeg
  });
});

    function showSectionAndScroll(key){
      const small = window.matchMedia('(max-width: 900px)').matches;
      const allowedMobile = ['overview','networth','categoryDetails'];
      if(small && !allowedMobile.includes(key)){
        // Op mobiel beperken we tot de drie secties
        return;
      }
      if(!small){
        const v = loadView();
        if(!(key in v)) return;
        if(!v[key]){ v[key] = true; saveView(v); applyView(); }
      }
      const elSection = document.querySelector(`[data-section="${key}"]`);
      if(elSection){ elSection.scrollIntoView({ behavior:'smooth', block:'start' }); }
    }
    // === Hard guards: je kan niet meer uitgeven/alloceren dan je verdient ===
    function validateProposed(incomeArr, expensesArr, savingsArr){
      const totalIncome = incomeArr.reduce((s,i)=> s + num(i.amount), 0);
      const totalExpenses = expensesArr.reduce((s,e)=> s + monthlyAmount(e), 0);
      const net = totalIncome - totalExpenses;
      const catSparen = savingsArr.filter(s=> (s.kind||'sparen')==='sparen').reduce((s,i)=> s + num(i.amount), 0);
      const catBeleg  = savingsArr.filter(s=> s.kind==='beleggen').reduce((s,i)=> s + num(i.amount), 0);
      const categoriesSum = catSparen + catBeleg;
      if(totalExpenses > totalIncome){
        return { ok:false, code:'expenses', msg:`Je geeft meer uit dan je verdient ( ${fmt.format(totalExpenses)} > ${fmt.format(totalIncome)} ).` };
      }
      if(categoriesSum > net){
        return { ok:false, code:'alloc', msg:`Je alloceert (sparen+beleggen) meer dan je netto overhoudt ( ${fmt.format(categoriesSum)} > ${fmt.format(net)} ).` };
      }
      return { ok:true };
    }

    document.addEventListener('change', (ev)=>{
      const t = ev.target;
      // View toggles
      if(t.classList && t.classList.contains('view-toggle')){
        const k = t.getAttribute('data-section');
        const v = loadView();
        if(k){ v[k] = !!t.checked; saveView(v); applyView(); }
        return;
      }
            const idAttr = t.getAttribute('data-id');
      const field = t.getAttribute('data-field');
      if(!field) return;

      // Income updates (incl. account)
      if(field.startsWith('income-')){
        const it = state.income.find(x=> x.id === idAttr);
        if(it){
          const nextIncome = state.income.map(x=> ({...x}));
          const idx = nextIncome.findIndex(x=> x.id===idAttr);
          if(idx>-1 && field==='income-amount') nextIncome[idx].amount = Number(t.value||0);
          const verdict = validateProposed(nextIncome, state.expenses, state.savings);
          if(!verdict.ok){
            if(typeof toast==='function') toast(verdict.msg); else alert(verdict.msg);
            // revert UI
            t.value = String(it.amount||0);
            return; // blokkeer
          }
          if(field==='income-label') it.label = t.value;
          if(field==='income-amount') it.amount = Number(t.value||0);
          if(field==='income-recurring') it.recurring = (String(t.value) === 'true');
          if(field==='income-account') it.accountId = t.value || '';
          save();
          render();
        }
        return;
      }

      // Savings updates (Spaar & Beleg)
      if(field.startsWith('saving-')){
        const it = state.savings.find(x=> x.id === idAttr);
        if(it){
          const nextSavings = state.savings.map(x=> ({...x}));
          const idx = nextSavings.findIndex(x=> x.id===idAttr);
          if(idx>-1 && field==='saving-amount') nextSavings[idx].amount = Number(t.value||0);
          if(idx>-1 && field==='saving-kind') nextSavings[idx].kind = t.value;
          const verdict = validateProposed(state.income, state.expenses, nextSavings);
          if(!verdict.ok){
            if(typeof toast==='function') toast(verdict.msg); else alert(verdict.msg);
            // revert UI
            if(field==='saving-amount') t.value = String(it.amount||0);
            if(field==='saving-kind')  t.value = String(it.kind||'sparen');
            return; // blokkeer
          }
          if(field==='saving-label') it.label = t.value;
          if(field==='saving-amount') it.amount = Number(t.value||0);
          if(field==='saving-recurring') it.recurring = (String(t.value) === 'true');
          if(field==='saving-kind') it.kind = t.value; // 'sparen' | 'beleggen'
          if(field==='saving-account'){
            it.accountId = t.value || '';
            // Auto-map type obv rekeningnaam om verwarring te vermijden
            const acc = listAccounts().find(a => a.id === it.accountId);
            if(acc){
              const name = String(acc.label||'');
              if(/beleg/i.test(name)) it.kind = 'beleggen';
              else if(/spaar/i.test(name)) it.kind = 'sparen';
            }
          }
          save();
          render();
        }
        return;
      }

      // Expense updates
      if(field.startsWith('exp-')){
        const it = state.expenses.find(x=> x.id === idAttr);
        if(it){
          const nextExpenses = state.expenses.map(x=> ({...x}));
          const idx = nextExpenses.findIndex(x=> x.id===idAttr);
          if(idx>-1 && field==='exp-amount') nextExpenses[idx].amount = Number(t.value||0);
          if(idx>-1 && field==='exp-frequency') nextExpenses[idx].frequency = t.value;
          if(idx>-1 && field==='exp-dueDay') nextExpenses[idx].dueDay = Number(t.value||1);
          const verdict = validateProposed(state.income, nextExpenses, state.savings);
          if(!verdict.ok){
            if(typeof toast==='function') toast(verdict.msg); else alert(verdict.msg);
            // revert UI
            if(field==='exp-amount') t.value = String(it.amount||0);
            if(field==='exp-frequency') t.value = String(it.frequency||'monthly');
            if(field==='exp-dueDay') t.value = String(it.dueDay||1);
            return; // blokkeer
          }
          if(field==='exp-category') it.category = t.value;
          if(field==='exp-label') it.label = t.value;
          if(field==='exp-amount') it.amount = Number(t.value||0);
          if(field==='exp-frequency') it.frequency = t.value;
          if(field==='exp-dueDay') it.dueDay = Number(t.value||1);
          if(field==='exp-carry') it.carry = (String(t.value) === 'true');
          if(field==='exp-account') it.accountId = t.value || '';
          save();
          render();
        }
        return;
      }

      // Accounts (Networth) updates
      if(field.startsWith('acct-')){
        const m = loadMeta();
        const list = Array.isArray(m.networth)? m.networth : [];
        const ac = list.find(x=> x && x.type==='rekening' && x.id===idAttr);
        if(ac){
          if(field==='acct-label') ac.label = t.value;
          if(field==='acct-amount') ac.amount = Number(t.value||0);
          saveMeta(m);
          render();
        }
        return;
      }

      // Legacy Networth item updates (non-account items)
      if(field.startsWith('nw-')){
        const m = loadMeta();
        const list = Array.isArray(m.networth)? m.networth : [];
        const it = list.find(x=> x && x.id===idAttr);
        if(it){
          if(field==='nw-label') it.label = t.value;
          if(field==='nw-amount') it.amount = Number(t.value||0);
          if(field==='nw-account') it.accountId = t.value || '';
          saveMeta(m);
          render();
        }
        return;
      }

      // Goals updates
      if(field.startsWith('goal-')){
        const m = loadMeta();
        const goals = Array.isArray(m.goals)? m.goals : [];
        const g = goals.find(x=> x && x.id===idAttr);
        if(g){
          if(field==='goal-label') g.label = t.value;
          if(field==='goal-target') g.target = Number(t.value||0);
          if(field==='goal-saved') g.saved = Number(t.value||0);
          if(field==='goal-prio') g.prio = t.value;
          if(field==='goal-account') g.accountId = t.value || '';
          saveMeta(m);
          render();
        }
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
      if(field==='exp-account') exp.accountId = t.value || '';
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
  const t = ev.target;
  // Declareer btn ÉÉN keer en meteen helemaal bovenaan
  const btn = t.closest ? t.closest('button') : null;
  const idAttr = 
    (t.getAttribute && t.getAttribute('data-id')) ||
    (btn && btn.getAttribute && btn.getAttribute('data-id')) || null;
  const action =
    (t.getAttribute && t.getAttribute('data-action')) ||
    (btn && btn.getAttribute && btn.getAttribute('data-action')) || null;


  // ... laat hier je overige if(btn.id===...) en if(action===...) handlers volgen ...
      if(btn.classList.contains('menu-close')){
        const d = btn.closest('details.menu');
        if(d){ d.removeAttribute('open'); }
        return;
      }
      // Mobile menu proxies
      if(btn.id === 'nextMonthMenu'){ const real = document.getElementById('nextMonth'); if(real){ real.click(); } return; }
      if(btn.id === 'logoutMenu'){ const form = document.querySelector('form[action="logout.php"]'); if(form){ form.submit(); } return; }

      // Goals UI buttons
      if(btn.id === 'addGoal'){
        const meta = loadMeta();
        meta.goals.push({ id: goalId(), label:'Nieuw doel', target: 0, saved: 0, prio:'hoog', accountId:'' });        saveMeta(meta);
        render();
        return;
      }
      if(action === 'goal-delete'){
        const meta = loadMeta();
        meta.goals = Array.isArray(meta.goals) ? meta.goals : [];
        const idx = meta.goals.findIndex(x => x && x.id === idAttr);
        if(idx === -1){ toast && toast('Doel niet gevonden.'); return; }
        const g = meta.goals[idx];
        if(!g.accountId){
          toast && toast('Selecteer eerst een rekening voor dit doel voor je het kan verwijderen.');
          return;
        }
        const label = g.label || 'Doel';
        if(!confirm(`Doel "${label}" verwijderen? Het gespaarde bedrag wordt afgetrokken van de gekozen rekening.`)) return;

        // Alleen bij verwijderen rekening aanpassen
        const amt = num(g.saved||0);
        if(amt > 0 && g.accountId){
          debitAccount(g.accountId, amt);
        }

        meta.goals.splice(idx,1);
        saveMeta(meta);
        render();
        toast && toast(`Doel "${label}" verwijderd${amt>0 && g.accountId ? ' en bedrag afgeboekt' : ''}.`);
        return;
      }
       if(action==='delete-acct'){
          const meta = loadMeta();
          meta.networth = Array.isArray(meta.networth) ? meta.networth : [];
          const idx = meta.networth.findIndex(x => x && x.id === idAttr && x.type === 'rekening');
          if(idx === -1){ toast && toast('Rekening niet gevonden.'); return; }
          const label = meta.networth[idx].label || 'Rekening';
          if(!confirm(`Rekening "${label}" verwijderen? Het saldo telt niet meer mee in Huidig totaal.`)) return;
          meta.networth.splice(idx,1);
          saveMeta(meta);
          render();
          toast && toast(`Rekening "${label}" verwijderd.`);
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
      
      if(btn.id==='addAccount'){
        const meta = loadMeta();
        meta.networth = Array.isArray(meta.networth) ? meta.networth : [];
        meta.networth.push({ id:id(), type:'rekening', label:'Nieuwe rekening', amount:0 });
        saveMeta(meta);
        render();
        // Open the accounts group to show the new entry
        requestAnimationFrame(()=>{
          const det = document.querySelector('#networthList details.nw-group.accounts');
          if(det) det.open = true;
        });
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

          // ===== Net worth overdracht (zet projectie van huidige maand als startbedrag voor volgende maand) =====
          {
            const meta = metaOnce; // gebruik hetzelfde meta object
            const allNow = Array.isArray(meta.networth) ? meta.networth : [];
            const accountsNow = allNow.filter(x => x && x.type === 'rekening');
            const nwItemsNow = allNow.filter(i => i && typeof i === 'object' && 'amount' in i && !('target' in i) && i.type !== 'rekening');
            const transfersNow = nwItemsNow.filter(i=> /^Overdracht\s\d{4}-\d{2}$/.test(i.label||''));
            const othersNow = nwItemsNow.filter(i=> !/^Overdracht\s\d{4}-\d{2}$/.test(i.label||''));

            // Voor elke rekening: projectie berekenen en als nieuw saldo zetten
            accountsNow.forEach(ac => {
              const cur = num(ac.amount);
              const incCredits = state.income
                .filter(i => i.accountId === ac.id)
                .reduce((s,i) => s + num(i.amount), 0);
              const investDebits = state.savings
                .filter(s => s.kind === 'beleggen' && s.accountId === ac.id)
                .reduce((s, it) => s + num(it.amount), 0);
              const expDebits = state.expenses
                .filter(e => e.accountId === ac.id)
                .reduce((s, e) => s + monthlyAmount(e), 0);
              const itemAdds = othersNow
                .filter(i => i && i.accountId === ac.id)
                .reduce((s,i) => s + num(i.amount), 0);

              // Sparen NIET optellen (is allocatie van inkomen, geen extra geld)
              const projected = cur + incCredits - investDebits - expDebits + itemAdds;
              ac.amount = Math.round(projected * 100) / 100; // negatieve bedragen toegestaan
            });

            // Opruimen: verwijder Overdracht <YYYY-MM> items, we zetten per-rekening direct de nieuwe saldi
            const transferLabel = `Overdracht ${state.month}`;
            meta.networth = allNow.filter(i => !(i && i.label === transferLabel));

            saveMeta(meta);
          }

          if (!alreadyTransferred) {
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

      if(t.id === 'realizeInvest'){
        const toSel = document.getElementById('realizeToAccount');
        const toId = toSel ? toSel.value : '';
        const amt = getFutureInvestments();
        if(amt <= 0){ toast && toast('Niets om uit te keren.'); return; }
        if(!toId){ toast && toast('Kies een rekening om naar uit te keren.'); return; }
        creditAccount(toId, amt);
        pushInvestHistory({ type:'realisatie', amount: -amt, note: 'Uitgekeerd naar rekening' });
        setFutureInvestments(0);
        render();
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