<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Log in · Liberty LPG Center</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --navy-900: #050a18;
    --navy-800: #0b1a3a;
    --navy-700: #0f2a5c;
    --navy-600: #14305f;
    --blue-500: #1f4e9c;
    --blue-300: #a9c1f0;
    --text: #0b1a3a;
    --muted: #5b6b8a;
    --border: #e3e8f1;
    --white: #ffffff;
    --red-500: #ef4444;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; }
  body {
    font-family: "Inter", system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
    color: var(--text);
    background: var(--white);
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 100vh;
  }

  /* Brand panel */
  .brand {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 48px;
    background: linear-gradient(180deg, var(--navy-900) 0%, var(--navy-800) 45%, var(--navy-600) 100%);
  }
  .brand::before, .brand::after {
    content: "";
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.05);
  }
  .brand::before { width: 360px; height: 360px; top: -120px; right: -90px; }
  .brand::after  { width: 300px; height: 300px; bottom: -110px; left: -90px; background: rgba(255,255,255,0.06); }
  .brand > * { position: relative; z-index: 1; }

  .logo {
    width: 88px; height: 88px;
    border-radius: 22px;
    background: #1e4a8f;
    color: var(--white);
    display: grid; place-items: center;
    font-size: 34px; font-weight: 700;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
    margin-bottom: 28px;
  }
  .brand h1 {
    font-size: 36px; font-weight: 700; letter-spacing: -0.02em;
    color: var(--white);
    margin-bottom: 10px;
  }
  .brand .subtitle {
    font-size: 18px; font-weight: 500;
    color: var(--blue-300);
  }

  /* Form panel */
  .panel {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 24px;
  }
  .form { width: 100%; max-width: 400px; }
  .form h2 {
    font-size: 32px; font-weight: 700; letter-spacing: -0.02em;
    color: var(--navy-800);
    margin-bottom: 8px;
  }
  .form .lead {
    font-size: 15px; color: var(--muted);
    margin-bottom: 32px;
  }
  .field { margin-bottom: 20px; }
  label {
    display: block;
    font-size: 14px; font-weight: 500;
    color: var(--muted);
    margin-bottom: 8px;
  }
  input[type="email"], input[type="text"], input[type="password"] {
    width: 100%;
    height: 48px;
    padding: 0 16px;
    font: inherit; font-size: 15px;
    color: var(--text);
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 10px;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
  }
  input::placeholder { color: #8a96ad; }
  input:focus {
    border-color: var(--blue-500);
    box-shadow: 0 0 0 3px rgba(31, 78, 156, 0.15);
  }
  .row {
    display: flex; align-items: center; justify-content: space-between;
    margin: 4px 0 28px;
    font-size: 14px;
  }
  .remember {
    display: flex; align-items: center; gap: 8px;
    color: var(--muted); cursor: pointer;
    margin: 0; font-weight: 400;
  }
  .remember input { width: 16px; height: 16px; accent-color: var(--navy-700); cursor: pointer; }
  .row a {
    color: var(--blue-500); font-weight: 600; text-decoration: none;
  }
  .row a:hover { text-decoration: underline; }
  button {
    width: 100%; height: 52px;
    font: inherit; font-size: 16px; font-weight: 600;
    color: var(--white);
    background: var(--navy-700);
    border: 0; border-radius: 12px;
    cursor: pointer;
    transition: background .15s, transform .05s;
  }
  button:hover { background: var(--navy-800); }
  button:active { transform: translateY(1px); }
  button:focus-visible, .row a:focus-visible, .remember input:focus-visible {
    outline: 3px solid rgba(31, 78, 156, 0.4); outline-offset: 2px;
  }
  .error-text {
    color: var(--red-500);
    font-size: 13px;
    margin-top: 6px;
    display: block;
  }

  @media (max-width: 800px) {
    body { grid-template-columns: 1fr; grid-template-rows: auto 1fr; }
    .brand { padding: 40px 24px; }
    .logo { width: 68px; height: 68px; font-size: 26px; border-radius: 18px; margin-bottom: 18px; }
    .brand h1 { font-size: 26px; }
    .brand .subtitle { font-size: 16px; }
    .panel { align-items: flex-start; padding-top: 36px; }
  }
</style>
</head>
<body>
  <section class="brand">
    <div class="logo">LG</div>
    <h1>Liberty LPG Center</h1>
    <p class="subtitle">Sales &amp; Inventory System</p>
  </section>

  <main class="panel">
    <div class="form">
      <h2>Welcome back</h2>
      <p class="lead">Please enter your details to sign in.</p>

      <form method="POST" action="{{ route('login') }}">
          @csrf
          <div class="field">
            <label for="email">Email Address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            @error('email')
                <span class="error-text">{{ $message }}</span>
            @enderror
          </div>

          <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
            @error('password')
                <span class="error-text">{{ $message }}</span>
            @enderror
          </div>

          <div class="row">
            <label class="remember"><input type="checkbox" name="remember" id="remember_me"> Remember me</label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">Forgot password?</a>
            @endif
          </div>

          <button type="submit">Log In</button>
      </form>
    </div>
  </main>
</body>
</html>