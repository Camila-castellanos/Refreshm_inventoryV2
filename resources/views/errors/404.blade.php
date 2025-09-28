<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen font-sans">
    <div class="min-h-screen flex flex-col items-center justify-center px-4">
        <div class="max-w-md w-full text-center">
            <!-- Error Icon -->
            <div class="mb-8">
                <svg class="w-24 h-24 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            </div>
            
            <!-- Error Code -->
            <h1 class="text-6xl font-light text-gray-900 mb-4">404</h1>
            
            <!-- Error Message -->
            <h2 class="text-xl font-normal text-gray-600 mb-2">Page not found</h2>
            
            <p class="text-gray-500 mb-8 text-sm leading-relaxed">
                @if(isset($exception) && $exception->getMessage())
                    {{ $exception->getMessage() }}
                @else
                    The page you're looking for doesn't exist or has been moved.
                @endif
            </p>
            
            <!-- Actions -->
            <div class="space-y-3">
                <a href="/" 
                   class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md transition-colors duration-200">
                    Back to Home
                </a>
                
                <button onclick="window.history.back()" 
                        class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 border border-gray-300 hover:border-gray-400 rounded-md transition-colors duration-200">
                    Go Back
                </button>
            </div>
        </div>
    </div>
</body>
</html>