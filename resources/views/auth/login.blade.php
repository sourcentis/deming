<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Deming :: Log in</title>
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            background-image: url('/images/deming.png');
            background-size: 800px 800px;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            background-color: rgba(255, 255, 255, 0.75);
            padding: 2rem;
            border: 1px solid black;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            max-width: 400px;
            width: 100%;
        }
    </style>
</head>
<body>
@if (Config::get('app.test'))
<div class="test-banner bg-warning">
    <i class="bi bi-exclamation-triangle"></i> {{ trans('menu.test') }}
</div>
@endif
<form method="POST" action="/login">
    @csrf

    <div class="login-box">
        <div class="h3 fw-bold">{{ trans("cruds.login.connection") }}</div>

        <div class="input-group has-validation mb-3">
            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
            <input type="text" name="login" id="login" size="30" required autocomplete="username"
                   class="form-control @error('login') is-invalid @enderror @error('email') is-invalid @enderror"
                   value="{{ old('login') }}">
            @error('login')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
            @error('email')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
        </div>

        <div class="input-group has-validation mb-3">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" name="password" size="30" required autocomplete="current-password"
                   class="form-control @error('password') is-invalid @enderror">
            @error('password')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
        </div>

        <div class="mt-5 d-flex justify-content-start align-items-center">
            <button class="btn btn-primary">{{ trans("cruds.login.identification") }}</button>
        </div>

        @if(count(Config::get('services.socialite_controller.providers')) > 0)
        <hr />
        @foreach(Config::get('services.socialite_controller.providers') as $provider)
        <div class="d-flex align-items-center my-2">
            <a href="{{ route('socialite.redirect', $provider) }}" class="btn btn-secondary w-100">
                <span class="bi bi-share text-white me-2"></span>
                {{ trans("cruds.login.connection_with") }} <strong>{{ Config::get('services.socialite_controller.'.$provider.'.display_name') }}</strong>
            </a>
        </div>
        @endforeach
        @if($errors->has('socialite'))
        <span class="invalid-feedback d-block"><strong>{{ $errors->first('socialite') }}</strong></span>
        @endif
        @endif
    </div>
</form>

<script @nonce>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            document.getElementById('login').focus();
        }, 1500);
    });
</script>
</body>
</html>
