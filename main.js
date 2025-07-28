window.onload = () => {
  // Load balance and UPI ID on page load
  fetch("../backend/get_balance.php", {
    headers: { 'X-CSRF-Token': getCsrfToken() }
  })
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! Status: ${res.status}`);
      return res.json();
    })
    .then(data => {
      if (data.error) throw new Error(data.error);
      if (!data.balance || !data.upi_id || isNaN(parseFloat(data.balance))) {
        throw new Error('Invalid balance or UPI ID data');
      }
      document.getElementById("balance").innerText = parseFloat(data.balance).toFixed(2);
      document.getElementById("upi_id").innerText = data.upi_id;
    })
    .catch(err => {
      console.error('Balance/UPI ID fetch error:', err);
      document.getElementById("balance").innerText = 'Error';
      document.getElementById("upi_id").innerText = 'Error';
    });

  // Load transactions
  fetch('../backend/transactions.php', {
    headers: { 'X-CSRF-Token': getCsrfToken() }
  })
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! Status: ${res.status}`);
      return res.json();
    })
    .then(data => {
      let html = "<table><tr><th>From</th><th>To</th><th>Amount</th><th>Method</th><th>Date</th></tr>";
      if (Array.isArray(data) && data.length > 0) {
        data.forEach(tx => {
          html += `<tr>
            <td>${tx.sender_upi || 'BANK'}</td>
            <td>${tx.receiver_upi || 'BANK'}</td>
            <<td>\u20B9${parseFloat(tx.amount).toFixed(2)}</td>
            <td>${tx.method}</td>
            <td>${new Date(tx.date).toLocaleString()}</td>
          </tr>`;
        });
      } else {
        html += "<tr><td colspan='5'>No transactions found</td></tr>";
      }
      html += "</table>";
      document.getElementById('transactions').innerHTML = html;
    })
    .catch(err => {
      console.error('Transactions fetch error:', err);
      document.getElementById('transactions').innerHTML = `<p style="color: red;">Error loading transactions: ${err.message}</p>`;
    });

  // Auto-fill receiver name
  document.querySelector("input[name='receiver']").addEventListener("blur", async () => {
    const method = document.querySelector("select[name='method']").value;
    const receiver = document.querySelector("input[name='receiver']").value;
    const display = document.getElementById("receiver-name");

    if (!method || !receiver) {
      display.innerHTML = "";
      return;
    }

    try {
      const res = await fetch(`../backend/get_receiver.php?method=${method}&receiver=${encodeURIComponent(receiver)}&csrf_token=${getCsrfToken()}`);
      if (!res.ok) throw new Error(`HTTP error! Status: ${res.status}`);
      const name = await res.text();
      display.innerHTML = name.includes("not found") || name.includes("Invalid") ?
        `<span style="color:red;">❌ ${name}</span>` :
        `<span style="color:green;">Receiver: ${name}</span>`;
    } catch (err) {
      display.innerHTML = `<span style="color:red;">Error fetching name: ${err.message}</span>`;
    }
  });

  // Validate amount input
  document.querySelector("input[name='amount']").addEventListener("input", async (e) => {
    const amount = parseFloat(e.target.value);
    const info = document.getElementById("balance-info");

    if (isNaN(amount) || amount <= 0) {
      info.innerHTML = `<span style="color: red;">Please enter a valid amount</span>`;
      return;
    }

    try {
      const res = await fetch("../backend/get_balance.php", {
        headers: { 'X-CSRF-Token': getCsrfToken() }
      });
      if (!res.ok) throw new Error(`HTTP error! Status: ${res.status}`);
      const data = await res.json();
      if (data.error) throw new Error(data.error);
      const balance = parseFloat(data.balance);
      if (isNaN(balance)) throw new Error('Invalid balance response');

      info.innerHTML = amount > balance ?
        `<span style="color: red;">⚠️ You only have ₹${balance.toFixed(2)} available!</span>` :
        `<span style="color: green;">✅ Within balance: ₹${balance.toFixed(2)}</span>`;
    } catch (err) {
      info.innerHTML = `<span style="color: red;">Error fetching balance: ${err.message}</span>`;
    }
  });

  // Handle transfer form submission
  document.getElementById("transfer-form").addEventListener("submit", async function (e) {
    e.preventDefault();
    const amount = parseFloat(document.querySelector("input[name='amount']").value);
    const receiver = document.querySelector("input[name='receiver']").value;
    const method = document.querySelector("select[name='method']").value;

    if (!amount || !receiver || !method) {
      alert("Please fill all fields");
      return;
    }

    try {
      const res = await fetch("../backend/get_balance.php", {
        headers: { 'X-CSRF-Token': getCsrfToken() }
      });
      if (!res.ok) throw new Error(`HTTP error! Status: ${res.status}`);
      const data = await res.json();
      if (data.error) throw new Error(data.error);
      const balance = parseFloat(data.balance);
      if (isNaN(balance)) throw new Error('Invalid balance response');

      if (amount > balance) {
        alert(`❌ You cannot send ₹${amount.toFixed(2)}. You only have ₹${balance.toFixed(2)}.`);
        return;
      }

      const formData = new FormData(document.getElementById("transfer-form"));
      formData.append('csrf_token', getCsrfToken());
      const response = await fetch("../backend/transfer.php", {
        method: 'POST',
        body: formData,
        headers: { 'X-CSRF-Token': getCsrfToken() }
      });
      const result = await response.text();
      alert(result);
      if (response.ok) location.reload(); // Refresh to update balance and transactions
    } catch (err) {
      alert(`Error: ${err.message}`);
    }
  });

  // Handle QR form submission
  document.getElementById("qr-form").addEventListener("submit", async function (e) {
    e.preventDefault();
    const upi_id = document.getElementById("qr_upi_id").value;
    const amount = parseFloat(document.querySelector("input[name='amount']", e.target).value);

    if (!upi_id || isNaN(amount) || amount <= 0) {
      alert("Please enter a valid UPI ID and amount");
      return;
    }

    try {
      const formData = new FormData(e.target);
      formData.append('csrf_token', getCsrfToken());
      const response = await fetch("../backend/qr_generate.php", {
        method: 'POST',
        body: formData,
        headers: { 'X-CSRF-Token': getCsrfToken() }
      });
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      const html = await response.text();
      document.getElementById('qr-code').innerHTML = html;
    } catch (err) {
      document.getElementById('qr-code').innerHTML = `<p style="color: red;">Error generating QR: ${err.message}</p>`;
    }
  });

  // Mock CSRF token function (replace with actual implementation)
  function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || 'mock-csrf-token';
  }
};