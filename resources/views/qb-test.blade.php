<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickBooks Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            max-width: 860px;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }
        input, button {
            padding: 10px;
            margin: 6px 0;
            width: 100%;
            box-sizing: border-box;
        }
        button {
            cursor: pointer;
        }
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        pre {
            background: #f6f8fa;
            border: 1px solid #e1e4e8;
            border-radius: 6px;
            padding: 12px;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .hint {
            color: #555;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h1>QuickBooks Sandbox Test</h1>
    <p class="hint">
        Step 1: Connect account. Step 2: Generate sandbox card token. Step 3: Charge with that token.
    </p>

    <div class="card">
        <h3>1) OAuth Connect</h3>
        <button id="connect-btn" type="button">Connect QuickBooks</button>
        <p class="hint">This opens the OAuth flow in the same tab and should return to callback.</p>
    </div>

    <div class="card">
        <h3>2) Generate Sandbox Card Token</h3>
        <form id="token-form">
            <div class="row">
                <div>
                    <label>Card Number</label>
                    <input type="text" name="number" value="4111111111111111" required>
                </div>
                <div>
                    <label>CVV</label>
                    <input type="text" name="cvc" value="123" required>
                </div>
            </div>

            <div class="row">
                <div>
                    <label>Exp Month</label>
                    <input type="text" name="exp_month" value="12" required>
                </div>
                <div>
                    <label>Exp Year</label>
                    <input type="text" name="exp_year" value="2030" required>
                </div>
            </div>

            <div class="row">
                <div>
                    <label>Name</label>
                    <input type="text" name="name" value="Sandbox Tester">
                </div>
                <div>
                    <label>Postal Code</label>
                    <input type="text" name="postal_code" value="94086">
                </div>
            </div>

            <button type="submit">Generate Token</button>
        </form>
        <p class="hint">If successful, token is auto-filled below.</p>
    </div>

    <div class="card">
        <h3>3) Charge Test</h3>
        <form id="charge-form">
            <div class="row">
                <div>
                    <label>Amount</label>
                    <input type="number" step="0.01" name="amount" value="10.50" required>
                </div>
                <div>
                    <label>Currency</label>
                    <input type="text" name="currency" value="USD" maxlength="3" required>
                </div>
            </div>

            <label>Card Token</label>
            <input type="text" name="card_token" placeholder="Paste real card token" required>

            <button type="submit">Send Charge</button>
        </form>
    </div>

    <div class="card">
        <h3>Response</h3>
        <pre id="result">No request yet.</pre>
    </div>

    <script>
        const connectBtn = document.getElementById('connect-btn');
        const tokenForm = document.getElementById('token-form');
        const chargeForm = document.getElementById('charge-form');
        const result = document.getElementById('result');

        connectBtn.addEventListener('click', () => {
            window.location.href = '/qb/connect';
        });

        tokenForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            result.textContent = 'Generating token...';

            const formData = new FormData(tokenForm);
            const payload = {
                number: formData.get('number'),
                exp_month: formData.get('exp_month'),
                exp_year: formData.get('exp_year'),
                cvc: formData.get('cvc'),
                name: formData.get('name'),
                postal_code: formData.get('postal_code')
            };

            try {
                const response = await fetch('/qb/tokenize', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                const tokenCandidate =
                    data?.card_token ??
                    data?.data?.value ??
                    data?.data?.token ??
                    data?.data?.id ??
                    '';

                if (tokenCandidate) {
                    chargeForm.querySelector('input[name="card_token"]').value = tokenCandidate;
                }

                result.textContent = JSON.stringify({
                    status: response.status,
                    data
                }, null, 2);
            } catch (error) {
                result.textContent = 'Token request failed: ' + error.message;
            }
        });

        chargeForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            result.textContent = 'Sending...';

            const formData = new FormData(chargeForm);
            const payload = {
                amount: formData.get('amount'),
                currency: formData.get('currency'),
                card_token: formData.get('card_token')
            };

            try {
                const response = await fetch('/qb/charge', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                result.textContent = JSON.stringify({
                    status: response.status,
                    data
                }, null, 2);
            } catch (error) {
                result.textContent = 'Request failed: ' + error.message;
            }
        });
    </script>
</body>
</html>
