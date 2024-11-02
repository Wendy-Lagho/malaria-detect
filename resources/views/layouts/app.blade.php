<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Malaria Detection'))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <aside id="sidebar" class="fixed inset-y-0 left-0 bg-white shadow-lg max-h-screen w-64 transition-width duration-300">
                <div class="flex flex-col justify-between h-full">
                    <button onclick="toggleSidebar()" class="p-4 text-gray-600 focus:outline-none hover:text-gray-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <div class="flex-grow">
                        <div class="px-4 py-6 text-center border-b">
                            <h1 class="text-xl font-bold leading-none text-gray-900">
                                <span class="text-blue-600">Malaria</span>Detect AI
                            </h1>
                        </div>
                        <div class="p-4">
                            <ul class="space-y-1">
                                <li>
                                    <a href="{{ route('dashboard') }}" class="flex items-center bg-blue-50 rounded-lg font-medium text-blue-600 px-4 py-3 hover:bg-blue-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                        <span class="ml-3">Dashboard</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('analysis.create') }}" class="flex items-center rounded-lg font-medium text-gray-700 px-4 py-3 hover:bg-gray-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span class="ml-3">New Analysis</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('analysis.index') }}" class="flex items-center rounded-lg font-medium text-gray-700 px-4 py-3 hover:bg-gray-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        <span class="ml-3">Past Analyses</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('reports.index') }}" class="flex items-center rounded-lg font-medium text-gray-700 px-4 py-3 hover:bg-gray-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="ml-3">Reports</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="p-4 border-t">
                            <a href="" class="flex items-center space-x-4">
                                <img src="https://ui-avatars.com/api/?name={{ auth()->user()->name }}" alt="" class="w-10 h-10 rounded-full">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-600">View Profile</p>
                                </div>
                            </a>
                    </div>
                </div>
            </aside>

        <!-- Main content -->
        <div class="ml-64 p-8">
            <!-- Page Heading -->
            <header class="bg-white shadow-sm rounded-lg px-8 py-6 mb-8">
                <div class="max-w-7xl mx-auto">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        @yield('header')
                    </h2>
                </div>
            </header>


            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
            
        </div>
    </div>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const textElements = document.querySelectorAll('.sidebar-text');
            
            sidebar.classList.toggle('w-16');
            mainContent.classList.toggle('ml-16');

            textElements.forEach(element => {
                element.classList.toggle('hidden');
            });
        }
    </script>

    @stack('modals')
    @stack('scripts')
</body>
</html>