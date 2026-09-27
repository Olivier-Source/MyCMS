{{-- Standalone error page (no database, no theme): server errors and maintenance --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }}</title>
    <style nonce="{{ $cspNonce ?? '' }}">
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f5f8fa; color: #1f2d36; }
        main { max-width: 32rem; padding: 2rem; text-align: center; }
        p.code { font-size: 3.5rem; font-weight: 700; color: #2f6f8f66; margin: 0; }
        h1 { font-size: 1.6rem; margin: .5rem 0; }
        p { color: #4a5a64; line-height: 1.6; }
    </style>
</head>
<body>
<main>
    <p class="code">{{ $code }}</p>
    <h1>{{ $title }}</h1>
    <p>{{ $message }}</p>
</main>
</body>
</html>
