<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $market->meta_title ?? $market->name . ' - Online Market' }}</title>
    <meta name="description" content="{{ $market->meta_description ?? 'Browse and shop ' . $market->name . ' collection of quality refurbished devices and electronics.' }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: @json($market->theme_colors ?? [
                        'primary' => [
                            '50' => '#f0f9ff',
                            '500' => '#3b82f6',
                            '600' => '#2563eb',
                            '700' => '#1d4ed8',
                        ]
                    ])
                }
            }
        }
    </script>

    <style>
        .gradient-bg {
            background: linear-gradient(135deg, 
                {{ $market->theme_colors['gradient']['from'] ?? '#667eea' }} 0%, 
                {{ $market->theme_colors['gradient']['to'] ?? '#764ba2' }} 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-1px);
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo and Market Name -->
                <div class="flex items-center space-x-4">
                    @if($market->logo_url)
                        <img src="{{ $market->logo_url }}" alt="{{ $market->name }}" class="h-8 w-auto">
                    @endif
                    <div class="text-2xl font-bold text-gray-900">
                        {{ $market->name }}
                    </div>
                    @if($market->tagline)
                        <span class="hidden sm:inline-block px-3 py-1 text-xs font-medium text-primary-700 bg-primary-50 rounded-full">
                            {{ $market->tagline }}
                        </span>
                    @endif
                </div>

                <!-- Navigation -->
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('ecommerce.index', $market->slug) }}" class="text-gray-700 hover:text-primary-600 font-medium transition-colors">
                        Home
                    </a>
                    @if($categories->count() > 0)
                        <div class="relative group">
                            <button class="text-gray-700 hover:text-primary-600 font-medium transition-colors flex items-center">
                                Categories
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-10">
                                <div class="py-2">
                                    @foreach($categories as $category)
                                        <a href="{{ route('ecommerce.category', [$market->slug, $category]) }}" 
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary-600 transition-colors">
                                            {{ ucfirst($category) }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                    <a href="{{ route('ecommerce.search', $market->slug) }}" class="text-gray-700 hover:text-primary-600 font-medium transition-colors">
                        Search
                    </a>
                </nav>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button" class="text-gray-500 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            @if($market->banner_url)
                <div class="mb-8">
                    <img src="{{ $market->banner_url }}" alt="{{ $market->name }} Banner" class="mx-auto max-h-32 rounded-lg shadow-lg">
                </div>
            @endif
            
            <h1 class="text-4xl md:text-6xl font-bold mb-6">
                Welcome to {{ $market->name }}
            </h1>
            
            @if($market->description)
                <p class="text-xl md:text-2xl mb-8 opacity-90 max-w-3xl mx-auto">
                    {{ $market->description }}
                </p>
            @else
                <p class="text-xl md:text-2xl mb-8 opacity-90 max-w-3xl mx-auto">
                    Discover quality refurbished devices and electronics at unbeatable prices. 
                    Every product is thoroughly tested and comes with our satisfaction guarantee.
                </p>
            @endif
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button onclick="document.getElementById('products').scrollIntoView()" 
                        class="btn-primary px-8 py-4 rounded-lg font-semibold text-lg shadow-lg">
                    Shop Now
                </button>
                @if($totalItemsCount > 0)
                    <div class="flex items-center justify-center space-x-2 bg-white/10 px-6 py-4 rounded-lg backdrop-blur-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="font-medium">{{ $totalItemsCount }} Products Available</span>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- Market Stats -->
    @if($stats['total_products'] > 0)
    <section class="py-12 bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600">{{ $stats['total_products'] }}</div>
                    <div class="text-sm font-medium text-gray-600">Products Available</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600">{{ $stats['categories_count'] }}</div>
                    <div class="text-sm font-medium text-gray-600">Categories</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600">${{ number_format($stats['price_range']['min'], 0) }}</div>
                    <div class="text-sm font-medium text-gray-600">Starting Price</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600">${{ number_format($stats['price_range']['max'], 0) }}</div>
                    <div class="text-sm font-medium text-gray-600">Top Price</div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Categories Section -->
    @if($categories->count() > 0)
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Shop by Category</h2>
                <p class="text-lg text-gray-600">Find exactly what you're looking for</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
                @foreach($categories as $category)
                    <a href="{{ route('ecommerce.category', [$market->slug, $category]) }}" 
                       class="group block p-6 bg-white rounded-xl border border-gray-200 card-hover text-center">
                        <div class="w-12 h-12 bg-primary-100 rounded-lg mx-auto mb-3 flex items-center justify-center group-hover:bg-primary-200 transition-colors">
                            <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                            {{ ucfirst($category) }}
                        </h3>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Featured Products -->
    @if($featuredItems->count() > 0)
    <section id="products" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Featured Products</h2>
                <p class="text-lg text-gray-600">Hand-picked items from our latest inventory</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                @foreach($featuredItems as $item)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-hover">
                        <!-- Product Image Placeholder -->
                        <div class="aspect-w-16 aspect-h-12 bg-gray-100 flex items-center justify-center h-48">
                            <div class="text-center p-8">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <p class="text-sm text-gray-500">{{ $item->manufacturer ?? 'Device' }}</p>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="mb-2">
                                @if($item->type)
                                    <span class="inline-block px-2 py-1 text-xs font-semibold text-primary-700 bg-primary-100 rounded-full">
                                        {{ ucfirst($item->type) }}
                                    </span>
                                @endif
                            </div>
                            
                            <h3 class="font-semibold text-lg text-gray-900 mb-2 line-clamp-2">
                                {{ $item->model }}
                            </h3>
                            
                            @if($item->issues)
                                <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                    {{ Str::limit($item->issues, 80) }}
                                </p>
                            @endif

                            <div class="flex items-center justify-between mb-3">
                                <div class="text-2xl font-bold text-primary-600">
                                    {{ $market->currency }} {{ number_format($item->selling_price, 2) }}
                                </div>
                                
                                @if($market->show_inventory_count)
                                    <span class="text-sm text-green-600 font-medium">In Stock</span>
                                @endif
                            </div>

                            <a href="{{ route('ecommerce.product', [$market->slug, $item->id]) }}" 
                               class="btn-primary w-full px-4 py-2 rounded-lg text-white font-medium text-sm text-center block">
                                View Details
                            </a>

                            @if($item->imei)
                                <div class="mt-3 pt-3 border-t border-gray-100">
                                    <p class="text-xs text-gray-500">
                                        IMEI: {{ Str::mask($item->imei, '*', 4, -4) }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if($totalItemsCount > $featuredItems->count())
                <div class="text-center mt-12">
                    <p class="text-gray-600 mb-6">Showing {{ $featuredItems->count() }} of {{ $totalItemsCount }} products</p>
                    <a href="{{ route('ecommerce.search', $market->slug) }}" class="btn-primary px-8 py-3 rounded-lg text-white font-semibold">
                        View All Products
                    </a>
                </div>
            @endif
        </div>
    </section>
    @else
    <!-- No Products Available -->
    <section id="products" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="max-w-md mx-auto">
                <svg class="w-24 h-24 text-gray-300 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">No Products Available</h3>
                <p class="text-gray-600 mb-6">Check back soon for new arrivals and inventory updates.</p>
                <a href="{{ route('ecommerce.index', $market->slug) }}" 
                   class="inline-flex items-center px-4 py-2 text-primary-600 hover:text-primary-700 font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh Page
                </a>
            </div>
        </div>
    </section>
    @endif

    <!-- Policies Section -->
    @if($market->return_policy || $market->shipping_policy)
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Our Guarantee</h2>
            </div>
            <div class="grid md:grid-cols-2 gap-8">
                @if($market->return_policy)
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="font-semibold text-lg text-gray-900 mb-3">Return Policy</h3>
                    <p class="text-gray-600">{{ Str::limit($market->return_policy, 200) }}</p>
                </div>
                @endif
                
                @if($market->shipping_policy)
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="font-semibold text-lg text-gray-900 mb-3">Shipping Policy</h3>
                    <p class="text-gray-600">{{ Str::limit($market->shipping_policy, 200) }}</p>
                </div>
                @endif
            </div>
        </div>
    </section>
    @endif

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">{{ $market->name }}</h3>
                    <p class="text-gray-400 mb-4">
                        {{ $market->description ?? 'Quality refurbished devices with guaranteed satisfaction.' }}
                    </p>
                    @if($market->shop->company ?? null)
                        <p class="text-sm text-gray-500">
                            Powered by {{ $market->shop->company->name }}
                        </p>
                    @endif
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Shop</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('ecommerce.index', $market->slug) }}" class="hover:text-white transition-colors">All Products</a></li>
                        @foreach($categories->take(4) as $category)
                            <li>
                                <a href="{{ route('ecommerce.category', [$market->slug, $category]) }}" 
                                   class="hover:text-white transition-colors">
                                    {{ ucfirst($category) }}
                                </a>
                            </li>
                        @endforeach
                        <li><a href="{{ route('ecommerce.search', $market->slug) }}" class="hover:text-white transition-colors">Search</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold mb-4">Information</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">About Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Quality Guarantee</a></li>
                        @if($market->return_policy)
                            <li><a href="#" class="hover:text-white transition-colors">Return Policy</a></li>
                        @endif
                        @if($market->contact_email)
                            <li><a href="mailto:{{ $market->contact_email }}" class="hover:text-white transition-colors">Contact</a></li>
                        @endif
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold mb-4">Contact</h4>
                    <ul class="space-y-2 text-gray-400">
                        @if($market->contact_email)
                            <li class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                {{ $market->contact_email }}
                            </li>
                        @endif
                        @if($market->contact_phone)
                            <li class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                {{ $market->contact_phone }}
                            </li>
                        @endif
                        @if($market->address)
                            <li class="flex items-start">
                                <svg class="w-4 h-4 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ $market->address }}
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} {{ $market->name }}. All rights reserved.</p>
                <p class="text-sm mt-2">Professional ecommerce marketplace powered by Laravel</p>
            </div>
        </div>
    </footer>
</body>

</html>