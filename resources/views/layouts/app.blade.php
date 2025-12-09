<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Reservasi Ruang Rapat')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    @auth
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <!-- Logo/Brand -->
                    <div class="flex-shrink-0 flex items-center">
                        <span class="text-xl font-bold text-gray-800">Reservasi Ruang Rapat</span>
                    </div>
                    <!-- Navigation Links -->
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="{{ route('dashboard') }}" 
                           class="@if(request()->routeIs('dashboard')) border-blue-500 text-gray-900 @else border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 @endif inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="{{ route('reservations.index') }}" 
                           class="@if(request()->routeIs('reservations.*')) border-blue-500 text-gray-900 @else border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 @endif inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Reservasi Saya
                        </a>
                    </div>
                </div>
                <!-- User Menu -->
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <span class="text-gray-700 mr-4">{{ Auth::user()->name }}</span>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    @endauth

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>
</body>
</html>
