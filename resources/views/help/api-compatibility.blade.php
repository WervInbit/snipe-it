<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>API and Compatibility Contract</title>
    <style>
        body {
            color: #222;
            font-family: system-ui, sans-serif;
            line-height: 1.55;
            margin: 0 auto;
            max-width: 840px;
            padding: 2rem;
        }

        code {
            background: #f2f2f2;
            padding: .1rem .25rem;
        }

        h1,
        h2 {
            line-height: 1.2;
        }
    </style>
</head>
<body>
    <main>
        {!! $content !!}
    </main>
</body>
</html>
