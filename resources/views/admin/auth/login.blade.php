<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TALASAJI — Admin Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('css/talasaji.css') }}" />
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:#FFF2EA; overflow:auto; }
    .login-wrap { width:100%; max-width:420px; padding:24px; }
    .login-card { background:#fff; border-radius:20px; padding:40px 36px; box-shadow:0 8px 40px rgba(154,30,34,0.10); }
    .login-logo { width:130px; margin-bottom:8px; }
    .login-brand-sub { font-family:'Poppins',sans-serif; font-size:11px; color:#aaa; margin-bottom:32px; display:block; }
    .login-title { font-family:'Playfair Display',serif; font-size:26px; font-weight:700; color:#9A1E22; margin-bottom:6px; }
    .login-subtitle { font-family:'Plus Jakarta Sans',sans-serif; font-size:13px; color:#888; margin-bottom:28px; }
    .form-group { margin-bottom:18px; }
    .form-label { font-family:'Plus Jakarta Sans',sans-serif; font-size:13px; font-weight:600; color:#1A1A1A; display:block; margin-bottom:6px; }
    .form-input { width:100%; padding:12px 14px; border:1px solid #e8ddd6; border-radius:10px; font-family:'Plus Jakarta Sans',sans-serif; font-size:14px; color:#1A1A1A; background:#fff; outline:none; box-sizing:border-box; transition:border-color 0.15s; }
    .form-input:focus { border-color:#9A1E22; }
    .form-input.is-invalid { border-color:#9A1E22; background:#fff5f5; }
    .error-msg { font-family:'Poppins',sans-serif; font-size:12px; color:#9A1E22; margin-top:5px; display:block; }
    .btn-login { width:100%; background:#9A1E22; color:#fff; border:none; border-radius:10px; padding:14px; font-family:'Plus Jakarta Sans',sans-serif; font-size:15px; font-weight:700; cursor:pointer; transition:background 0.15s; margin-top:8px; }
    .btn-login:hover { background:#7d181b; }
    .remember-row { display:flex; align-items:center; gap:8px; margin-bottom:20px; }
    .remember-row input { accent-color:#9A1E22; width:16px; height:16px; }
    .remember-row label { font-family:'Plus Jakarta Sans',sans-serif; font-size:13px; color:#555; cursor:pointer; }
    .alert-error { background:#fff0f0; border:1px solid #f5c0c0; border-radius:10px; padding:12px 16px; margin-bottom:20px; font-family:'Plus Jakarta Sans',sans-serif; font-size:13px; color:#9A1E22; }
  </style>
</head>
<body>
  <div class="login-wrap">
    <div class="login-card">
      <img src="{{ asset('images/logo_talasaji.png') }}" alt="TALASAJI" class="login-logo" />
      <span class="login-brand-sub">Culinary Curator</span>
      <h1 class="login-title">Admin Login</h1>
      <p class="login-subtitle">Masuk ke panel kurator TALASAJI</p>

      @if($errors->any())
        <div class="alert-error">
          @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
          @endforeach
        </div>
      @endif

      @if(session('error'))
        <div class="alert-error">{{ session('error') }}</div>
      @endif

      <form method="POST" action="{{ route('admin.login.submit') }}">
        @csrf
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input type="email" id="email" name="email" class="form-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                 value="{{ old('email') }}" placeholder="admin@talasaji.com" required autofocus />
          @error('email') <span class="error-msg">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input type="password" id="password" name="password" class="form-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                 placeholder="••••••••" required />
          @error('password') <span class="error-msg">{{ $message }}</span> @enderror
        </div>
        <div class="remember-row">
          <input type="checkbox" id="remember" name="remember" value="1" />
          <label for="remember">Ingat saya</label>
        </div>
        <button type="submit" class="btn-login">Masuk ke Dashboard</button>
      </form>
    </div>
  </div>
</body>
</html>
