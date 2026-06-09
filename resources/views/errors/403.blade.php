<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Forbidden | IEEPIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 min-h-screen flex items-center justify-center">
    <div class="text-center px-6">
        <div class="flex items-center justify-center gap-3 mb-8">
            <img src="{{ asset('images/ieepis-logo.png') }}" alt="IEEPIS Logo" class="h-12 w-12 object-contain" />
            <span class="text-2xl font-bold text-gray-950 dark:text-white">EQUiP</span>
        </div>
        <h1 class="text-6xl font-bold text-green-600 mb-4">403</h1>
        <p class="text-xl text-gray-600 dark:text-gray-400 mb-2">Access Denied</p>
        <p class="text-gray-500 dark:text-gray-500 mb-8">You don't have permission to access this page.</p>
        <a href="/admin" class="inline-block px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
            Back to Dashboard
        </a>
    </div>
</body>
</html>
