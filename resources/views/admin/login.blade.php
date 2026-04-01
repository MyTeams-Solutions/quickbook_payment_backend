<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; }
        .container { max-width: 420px; margin: 80px auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,.08); }
        h1 { margin: 0 0 18px; font-size: 22px; }
        label { display: block; margin-bottom: 6px; font-size: 14px; color: #374151; }
        input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; margin-bottom: 14px; box-sizing: border-box; }
        .error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 10px; margin-bottom: 14px; border-radius: 6px; font-size: 14px; }
        button { width: 100%; padding: 10px; border: 0; border-radius: 6px; background: #111827; color: #fff; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Login</h1>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>

            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
