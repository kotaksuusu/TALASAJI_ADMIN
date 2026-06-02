<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TALASAJI — Admin Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/png" href="{{ asset('images/logo_mangkok.png') }}" />
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
    .password-wrapper {
      position: relative;
    }
    .password-wrapper .form-input {
      padding-right: 44px;
      box-sizing: border-box;
      width: 100%;
    }
    .toggle-password {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      padding: 0;
      display: flex;
      align-items: center;
      color: #aaa;
      line-height: 1;
    }
    .toggle-password:hover { color: #9A1E22; }
  </style>
</head>
<body>
  <div class="login-wrap">
    <div class="login-card">
      <img src="{{ asset('images/Logo_Talasaji_1.png') }}" alt="TALASAJI" class="login-logo" />
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
          <div class="password-wrapper">
            <input type="password" id="password" name="password" class="form-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                   placeholder="••••••••" required />
            <button type="button" class="toggle-password" onclick="togglePassword()" tabindex="-1" aria-label="Toggle password">
              <svg id="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                   fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <svg id="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                   fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   style="display:none;">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
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
<script>
function togglePassword() {
  const input  = document.getElementById('password');
  const eyeOn  = document.getElementById('icon-eye');
  const eyeOff = document.getElementById('icon-eye-off');
  if (input.type === 'password') {
    input.type = 'text';
    eyeOn.style.display  = 'none';
    eyeOff.style.display = 'block';
  } else {
    input.type = 'password';
    eyeOn.style.display  = 'block';
    eyeOff.style.display = 'none';
  }
}
</script>
</body>
</html>
