// script.js

// 1) Supabase init
const SUPABASE_URL = 'https://zgqugmmztvsdguqhlykq.supabase.co';
const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InpncXVnbW16dHZzZGd1cWhseWtxIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDU2NjY1MTcsImV4cCI6MjA2MTI0MjUxN30.l5r-K3MLXBOHSfWrKx1iZcOECr5_WNCGs8_sHOwWCQc';
const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_KEY);

// 2) Chart.js datalabels plugin registeren
Chart.register(ChartDataLabels);

(function() {
  // DOM‐elementen
  const salaryEl    = document.getElementById('salary');
  const extraEl     = document.getElementById('extraIncome');
  const catCont     = document.getElementById('categories-container');
  const addCatBtn   = document.getElementById('add-category');
  const remainingEl = document.getElementById('remaining');
  const themeToggle = document.getElementById('theme-toggle');
  const exportCsv   = document.getElementById('export-csv');
  const exportPdf   = document.getElementById('export-pdf');
  let pieChart      = null;
  const monthEl     = document.getElementById('month');
  const resetBtn    = document.getElementById('reset-btn');
  const saveBtn     = document.getElementById('save-btn');
  const subsCont    = document.getElementById('subscriptions-container');
  const addSubBtn   = document.getElementById('add-subscription');
  const recCont     = document.getElementById('recurring-container');
  const addRecBtn   = document.getElementById('add-recurring');

  // Hulpfunctie voor melding
  function showMessage(text) {
    const msgEl = document.createElement('div');
    msgEl.textContent = text;
    msgEl.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow';
    document.body.appendChild(msgEl);
    setTimeout(() => msgEl.remove(), 3000);
  }

  // 3) Auth‐state listener: toon #auth of #app
  supabase.auth.onAuthStateChange((event, session) => {
    const authDiv = document.getElementById('auth');
    const appDiv  = document.getElementById('app');
    if (session && session.user) {
      authDiv.classList.add('hidden');
      appDiv.classList.remove('hidden');
      loadFromDB();   // laad zodra ingelogd
    } else {
      authDiv.classList.remove('hidden');
      appDiv.classList.add('hidden');
    }
  });

  // 4) Login formulier
  document.getElementById('login-form').addEventListener('submit', async e => {
    e.preventDefault();
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    const { error } = await supabase.auth.signInWithPassword({ email, password });
    document.getElementById('auth-message').textContent = error ? error.message : '';
  });

  // 5) Signup formulier
  document.getElementById('signup-form').addEventListener('submit', async e => {
    e.preventDefault();
    const email = document.getElementById('signup-email').value;
    const password = document.getElementById('signup-password').value;
    const { error } = await supabase.auth.signUp({ email, password });
    document.getElementById('auth-message').textContent = error
      ? error.message
      : 'Registratie gelukt! Check je e-mail om te bevestigen.';
  });

  // 6) Logout knop
  document.getElementById('btn-logout').addEventListener('click', async () => {
    const { error } = await supabase.auth.signOut();
    if (error) {
      console.error('Fout bij uitloggen:', error.message);
      return;
    }
    // Toon login en verberg app
    document.getElementById('auth').classList.remove('hidden');
    document.getElementById('app').classList.add('hidden');
  });

  // 7) Helper: nieuwe categorie‐rij maken
  function createCategoryRow(name = '', amount = 0) {
    const row = document.createElement('div');
    row.className = 'flex flex-col sm:flex-row items-center gap-4';

    const nameIn = document.createElement('input');
    nameIn.type = 'text'; nameIn.placeholder = 'Categorie'; nameIn.value = name;
    nameIn.className = 'w-full sm:flex-1 p-2 border rounded dark:bg-gray-600 dark:border-gray-500 category-name';

    const amtIn = document.createElement('input');
    amtIn.type = 'number'; amtIn.placeholder = 'Bedrag'; amtIn.value = amount;
    amtIn.className = 'w-full sm:flex-1 p-2 border rounded dark:bg-gray-600 dark:border-gray-500 category-amount';
    // Wrap input with euro prefix
    const amtWrapper = document.createElement('div');
    amtWrapper.className = 'flex items-center';
    const euroSpan = document.createElement('span');
    euroSpan.textContent = '€';
    euroSpan.className = 'mr-2';
    amtWrapper.append(euroSpan, amtIn);

    const rmBtn = document.createElement('button');
    rmBtn.textContent = '–';
    rmBtn.className = 'w-full sm:w-auto px-3 py-2 bg-red-500 text-white rounded';
    rmBtn.onclick = () => { row.remove(); updateAll(); };

    [nameIn, amtIn].forEach(el => el.addEventListener('input', updateAll));
    row.append(nameIn, amtWrapper, rmBtn);
    return row;
  }

  // Helper: nieuwe abonnement‐rij
  function createSubscriptionRow(name = '', amount = 0, expiry = '') {
    const row = document.createElement('div');
    row.className = 'flex flex-col sm:flex-row items-center gap-4';

    const nameIn = document.createElement('input');
    nameIn.type = 'text'; nameIn.placeholder = 'Abonnement (bijv. Netflix)';
    nameIn.value = name;
    nameIn.className = 'w-full sm:flex-1 p-2 border rounded dark:bg-gray-600 dark:border-gray-500 sub-name';

    const amtIn = document.createElement('input');
    amtIn.type = 'number'; amtIn.placeholder = 'Maandelijks';
    amtIn.value = amount;
    amtIn.className = 'w-full sm:flex-1 p-2 border rounded dark:bg-gray-600 dark:border-gray-500 sub-amount';
    const amtWrapper = document.createElement('div');
    amtWrapper.className = 'flex items-center';
    const euroSpan = document.createElement('span');
    euroSpan.textContent = '€';
    euroSpan.className = 'mr-2';
    amtWrapper.append(euroSpan, amtIn);

    const dateIn = document.createElement('input');
    dateIn.type = 'date'; dateIn.value = expiry;
    dateIn.className = 'w-full sm:w-40 p-2 border rounded dark:bg-gray-600 dark:border-gray-500 sub-expiry';

    const rmBtn = document.createElement('button');
    rmBtn.textContent = '–';
    rmBtn.className = 'w-full sm:w-auto px-3 py-2 bg-red-500 text-white rounded';
    rmBtn.onclick = () => { row.remove(); updateAll(); };

    [nameIn, amtIn, dateIn].forEach(el => el.addEventListener('input', updateAll));
    row.append(nameIn, amtWrapper, dateIn, rmBtn);
    return row;
  }

  // Helper: nieuwe terugkerende post
  function createRecurringRow(name = '', amount = 0, interval = 'monthly', start = '') {
    const row = document.createElement('div');
    row.className = 'flex flex-col sm:flex-row items-center gap-4';

    const nameIn = document.createElement('input');
    nameIn.type = 'text'; nameIn.placeholder = 'Omschrijving';
    nameIn.value = name;
    nameIn.className = 'w-full sm:flex-1 p-2 border rounded dark:bg-gray-600 dark:border-gray-500 rec-name';

    const amtIn = document.createElement('input');
    amtIn.type = 'number'; amtIn.placeholder = 'Bedrag';
    amtIn.value = amount;
    amtIn.className = 'w-full sm:flex-1 p-2 border rounded dark:bg-gray-600 dark:border-gray-500 rec-amount';
    const amtWrapper = document.createElement('div');
    amtWrapper.className = 'flex items-center';
    const euroSpan = document.createElement('span');
    euroSpan.textContent = '€';
    euroSpan.className = 'mr-2';
    amtWrapper.append(euroSpan, amtIn);

    const intervalIn = document.createElement('select');
    ['monthly','weekly','yearly'].forEach(val => {
      const opt = document.createElement('option');
      opt.value = val;
      opt.textContent =
        val === 'monthly' ? 'Maandelijks' :
        val === 'weekly'  ? 'Wekelijks'  :
                            'Jaarlijks';
      intervalIn.append(opt);
    });
    intervalIn.value = interval;
    intervalIn.className = 'w-full sm:w-32 p-2 border rounded dark:bg-gray-600 dark:border-gray-500 rec-interval';

    const startIn = document.createElement('input');
    startIn.type = 'date'; startIn.value = start;
    startIn.className = 'w-full sm:w-40 p-2 border rounded dark:bg-gray-600 dark:border-gray-500 rec-start';

    const rmBtn = document.createElement('button');
    rmBtn.textContent = '–';
    rmBtn.className = 'w-full sm:w-auto px-3 py-2 bg-red-500 text-white rounded';
    rmBtn.onclick = () => { row.remove(); updateAll(); };

    [nameIn, amtIn, intervalIn, startIn].forEach(el => el.addEventListener('input', updateAll));
    row.append(nameIn, amtWrapper, intervalIn, startIn, rmBtn);
    return row;
  }

  // 8) Dark/light mode toggle
  themeToggle.onclick = () => {
    document.documentElement.classList.toggle('dark');
    localStorage.setItem(
      'theme',
      document.documentElement.classList.contains('dark') ? 'dark' : 'light'
    );
  };
  if (localStorage.getItem('theme') === 'dark')
    document.documentElement.classList.add('dark');

  // 9) Voeg categorie toe knop
  addCatBtn.onclick = () => catCont.appendChild(createCategoryRow());
  // Abonnementen toevoegen
  addSubBtn.onclick = () => subsCont.appendChild(createSubscriptionRow());
  addRecBtn.onclick  = () => recCont.appendChild(createRecurringRow());
  subsCont.appendChild(createSubscriptionRow());
  recCont.appendChild(createRecurringRow());

  // 9.1) Reset knop
  function resetAll() {
    monthEl.value     = '';
    salaryEl.value    = '';
    extraEl.value     = '';
    catCont.innerHTML = '';
    catCont.appendChild(createCategoryRow());
    updateAll();
  }
  resetBtn.onclick = resetAll;

  // 9.2) Opslaan knop
  saveBtn.onclick = async () => {
    const salary    = parseFloat(salaryEl.value) || 0;
    const extra     = parseFloat(extraEl.value)  || 0;

    // Categories (expenses/income)
    const categories = Array.from(catCont.children).map(row => ({
      name:   row.querySelector('.category-name').value.trim(),
      amount: parseFloat(row.querySelector('.category-amount').value) || 0,
      type:   'expense'
    }));

    // Subscriptions
    const subs = Array.from(subsCont.children).map(row => ({
      name:   row.querySelector('.sub-name').value.trim(),
      amount: parseFloat(row.querySelector('.sub-amount').value) || 0,
      expiry: row.querySelector('.sub-expiry').value,
      type:   'subscription'
    })).filter(s => s.name && s.amount > 0);

    // Recurring posts
    const recs = Array.from(recCont.children).map(row => ({
      name:    row.querySelector('.rec-name').value.trim(),
      amount:  parseFloat(row.querySelector('.rec-amount').value) || 0,
      interval: row.querySelector('.rec-interval').value,
      start:   row.querySelector('.rec-start').value,
      type:    'recurring'
    })).filter(r => r.name && r.amount > 0);

    const allItems = [...categories, ...subs, ...recs];

    if (!validateInputs(salary, extra, allItems)) {
      showMessage('Controleer je invoer: lege naam of negatief bedrag.');
      return;
    }

    try {
      await saveToDB(allItems, salary, extra);
      showMessage('Opgeslagen!');
    } catch (err) {
      console.error(err);
      showMessage('Fout bij opslaan.');
    }
  };

  // 10) Valideren van invoer
  function validateInputs(salary, extra, cats) {
    if (salary < 0 || extra < 0) return false;
    return !cats.some(c => c.amount < 0 || c.name.trim() === '');
  }

  // 11) Inladen uit Supabase
  async function loadFromDB() {
    const { data: { user } } = await supabase.auth.getUser();

    const { data: settings } = await supabase
      .from('user_settings')
      .select('*')
      .eq('user_id', user.id)
      .single();
    salaryEl.value = settings?.salary || '';
    extraEl.value  = settings?.extra_income || '';
    monthEl.value  = settings?.month || '';

    const { data: items } = await supabase
      .from('budget_items')
      .select('*')
      .eq('user_id', user.id)
      .order('created_at', { ascending: true });

    const { data: subs } = await supabase
      .from('subscriptions')
      .select('*')
      .eq('user_id', user.id)
      .order('created_at', { ascending: true });

    const { data: recs } = await supabase
      .from('recurring_posts')
      .select('*')
      .eq('user_id', user.id)
      .order('created_at', { ascending: true });

    catCont.innerHTML  = '';
    subsCont.innerHTML = '';
    recCont.innerHTML  = '';

    if (items?.length) {
      items.forEach(i => {
        catCont.appendChild(createCategoryRow(i.category, i.amount));
      });
    } else {
      catCont.appendChild(createCategoryRow());
    }

    const today = new Date();

    if (subs?.length) {
      subs.filter(s => !s.expiry_date || new Date(s.expiry_date) >= today).forEach(s => {
        subsCont.appendChild(createSubscriptionRow(s.name, s.amount, s.expiry_date || ''));
      });
    } else {
      subsCont.appendChild(createSubscriptionRow());
    }

    if (recs?.length) {
      recs.forEach(r => {
        recCont.appendChild(createRecurringRow(r.description, r.amount, r.interval, r.start_date));
      });
    } else {
      recCont.appendChild(createRecurringRow());
    }

    updateAll();
  }

  // 12) Opslaan naar Supabase
  async function saveToDB(items, salary, extra) {
    const { data: { user } } = await supabase.auth.getUser();
    const monthValue = monthEl.value || null;

    await supabase.from('user_settings').upsert({
      user_id: user.id,
      salary: salary,
      extra_income: extra,
      month: monthValue
    });

    const categories = items.filter(item => item.type === 'expense' || item.type === 'income');
    const subscriptions = items.filter(item => item.type === 'subscription');
    const recurrings = items.filter(item => item.type === 'recurring');

    await supabase.from('budget_items')
      .delete()
      .eq('user_id', user.id);

    if (categories.length > 0) {
      await supabase.from('budget_items').insert(
        categories.map(item => ({
          user_id: user.id,
          type: item.type,
          category: item.name,
          amount: item.amount,
          budget_month: monthValue,
          note: '',
          tags: null
        }))
      );
    }

    await supabase.from('subscriptions')
      .delete()
      .eq('user_id', user.id);

    if (subscriptions.length > 0) {
      await supabase.from('subscriptions').insert(
        subscriptions.map(item => ({
          user_id: user.id,
          name: item.name,
          amount: item.amount,
          expiry_date: item.expiry || null
        }))
      );
    }

    await supabase.from('recurring_posts')
      .delete()
      .eq('user_id', user.id);

    if (recurrings.length > 0) {
      await supabase.from('recurring_posts').insert(
        recurrings.map(item => ({
          user_id: user.id,
          description: item.name,
          amount: item.amount * (item.interval === 'yearly' ? 12 : item.interval === 'weekly' ? 0.25 : 1),
          interval: item.interval || 'monthly',
          start_date: item.start || new Date().toISOString().split('T')[0]
        }))
      );
    }
  }

  // 13) Berekenen, grafiek updaten en saven
  function updateAll() {
    const salary = parseFloat(salaryEl.value) || 0;
    const extra  = parseFloat(extraEl.value)  || 0;
    const categories = Array.from(catCont.children).map(row => ({
      name: row.querySelector('.category-name').value.trim(),
      amount: parseFloat(row.querySelector('.category-amount').value) || 0
    }));

    // Abonnementen ophalen en filteren op vervaldatum
    const today = new Date();
    const subs = Array.from(subsCont.children).map(row => ({
      name:   row.querySelector('.sub-name').value.trim(),
      amount: parseFloat(row.querySelector('.sub-amount').value) || 0,
      expiry: row.querySelector('.sub-expiry').value
    })).filter(s => {
      if (!s.name || s.amount <= 0) return false;
      if (s.expiry) {
        const expDate = new Date(s.expiry);
        return expDate >= new Date(today.getFullYear(), today.getMonth(), 1);
      }
      return true;
    }).map(s => ({ name: s.name, amount: s.amount }));

    // Recurring posten ophalen, filteren en omrekenen naar maandbedrag
    const recs = Array.from(recCont.children)
        // 1) Lees de waarden uit de inputs
        .map(row => ({
          name:     row.querySelector('.rec-name').value.trim(),
          amount:   parseFloat(row.querySelector('.rec-amount').value) || 0,
          interval: row.querySelector('.rec-interval').value,
          start:    row.querySelector('.rec-start').value
        }))
        // 2) Filter lege of ongeldige entries (optioneel kun je hier ook op startdatum filteren)
        .filter(r => r.name && r.amount > 0)
        // 3) Reken om naar maandelijks equivalent
        .map(r => {
          let amt = r.amount;
          if (r.interval === 'yearly')   amt = amt / 12;
          else if (r.interval === 'weekly') amt = amt * 4;
          // 'monthly' blijft ongewijzigd
          return { name: r.name, amount: amt };
        });

    const allCosts = categories.concat(subs, recs);

    if (!validateInputs(salary, extra, allCosts)) {
      remainingEl.textContent = 'Ongeldige invoer';
      return;
    }

    const totalIncome = salary + extra;
    const sumCosts    = allCosts.reduce((sum, c) => sum + c.amount, 0);
    const remain      = totalIncome - sumCosts;
    remainingEl.textContent = `€${remain.toFixed(2)}`;

    // Chart.js update
    const ctx = document.getElementById('pieChart').getContext('2d');
    if (pieChart) pieChart.destroy();
    pieChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: allCosts.map(c => c.name),
        datasets: [{ data: allCosts.map(c => c.amount) }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' },
          datalabels: {
            formatter: (value, ctx) => {
              const data = ctx.chart.data.datasets[0].data;
              const sum  = data.reduce((a, b) => a + b, 0);
              return (value / sum * 100).toFixed(1) + '%';
            },
            color: '#fff',
            anchor: 'end',
            align: 'start'
          }
        }
      }
    });
  }

  // 14) CSV-export blijft ongewijzigd
  exportCsv.onclick = () => {
    const salary = parseFloat(salaryEl.value) || 0;
    const extra  = parseFloat(extraEl.value)  || 0;
    const categories = Array.from(catCont.children).map(row => ({
      name: row.querySelector('.category-name').value.trim(),
      amount: parseFloat(row.querySelector('.category-amount').value) || 0
    }));
    let csv = 'Categorie;Bedrag (€)\n';
    categories.forEach(c => csv += `${c.name};${c.amount}\n`);
    csv += `\nMaandloon;${salary}\nExtra inkomsten;${extra}\n`;
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'budget.csv';
    a.click();
  };

  // 15) PDF-export blijft ongewijzigd
  exportPdf.onclick = () => {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text('Budget Overzicht', 10, 10);
    const categories = Array.from(catCont.children).map(row => ({
      name: row.querySelector('.category-name').value.trim(),
      amount: parseFloat(row.querySelector('.category-amount').value) || 0
    }));
    let y = 20;
    categories.forEach(c => {
      doc.text(`- ${c.name}: €${c.amount}`, 10, y);
      y += 7;
    });
    doc.text(`\nResterend: €${remainingEl.textContent}`, 10, y + 5);
    doc.save('budget.pdf');
  };

  // 16) Initialisatie: voeg alvast één rij toe (wordt overschreven door loadFromDB)
  catCont.appendChild(createCategoryRow());
  updateAll();

  Sortable.create(catCont, {});
  Sortable.create(subsCont, {});
  Sortable.create(recCont, {});
})();