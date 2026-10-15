<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>EPITA</title>
    <link rel="stylesheet" href="{{ asset('assets/tailwind.css') }}?v={{ filemtime(public_path('assets/tailwind.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/react/inertia.css') }}?v={{ filemtime(public_path('assets/react/inertia.css')) }}">
    <script type="module" src="{{ asset('assets/react/inertia.js') }}?v={{ filemtime(public_path('assets/react/inertia.js')) }}"></script>
    @inertiaHead
</head>
<body>@inertia</body>
</html>
