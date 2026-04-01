<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; margin: 0; color: #111827; }
        .wrap { max-width: 1100px; margin: 28px auto; padding: 0 12px; }
        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        h1 { margin: 0; font-size: 24px; }
        .meta { color: #4b5563; font-size: 14px; }
        .btn { border: 0; border-radius: 6px; background: #111827; color: #fff; padding: 8px 12px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 10px; text-align: left; font-size: 14px; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 600; }
        .empty { padding: 18px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; color: #6b7280; }
        .pagination { margin-top: 14px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <div>
                <h1>Payments Dashboard</h1>
                <div class="meta">Logged in as {{ session('admin_username', 'admin') }}</div>
            </div>

            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="btn" type="submit">Logout</button>
            </form>
        </div>

        @if($payments->count() === 0)
            <div class="empty">No payments found.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Charge ID</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Card</th>
                        <th>Auth Code</th>
                        <th>Environment</th>
                        <th>Captured At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td>{{ $payment->id }}</td>
                            <td>{{ $payment->charge_id }}</td>
                            <td>{{ $payment->status }}</td>
                            <td>{{ number_format((float) $payment->amount, 2) }} {{ strtoupper($payment->currency) }}</td>
                            <td>{{ $payment->card_type ?? '-' }} {{ $payment->card_last4 ? ('****'.$payment->card_last4) : '' }}</td>
                            <td>{{ $payment->auth_code ?? '-' }}</td>
                            <td>{{ $payment->environment }}</td>
                            <td>{{ $payment->captured_at ?? $payment->created_at }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="pagination">{{ $payments->links() }}</div>
        @endif
    </div>
</body>
</html>
