<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <x-slot name="title">{{ config('app.name', 'Malaria Detection') }}</x-slot>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">

            <!-- Top Navbar -->
            <nav class="bg-white shadow-sm fixed w-full top-0 z-20">
                <div class="max-w-full px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <!-- Left side -->
                        <div class="flex items-center">
                            <button onclick="toggleSidebar()" class="p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        </div>

                        <!-- Center - Search Bar -->
                        <div class="flex-1 flex items-center justify-center px-2 lg:px-6">
                            <div class="max-w-lg w-full">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input type="search" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search analyses...">
                                </div>
                            </div>
                        </div>

                        <!-- Right side -->
                        <div class="flex items-center space-x-4">
                            <!-- Notifications -->
                            <button class="p-2 rounded-full text-gray-600 hover:bg-gray-100 relative">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400"></span>
                            </button>

                            <!-- Profile Dropdown -->
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="flex items-center space-x-2 p-2 rounded-full hover:bg-gray-100">
                                    <img src="https://ui-avatars.com/api/?name={{ auth()->user()->name }}" alt="" class="h-8 w-8 rounded-full">
                                    <span class="hidden md:block text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
                                </button>

                                <!-- Dropdown Menu -->
                                <div x-show="open" 
                                     @click.away="open = false"
                                     class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100">
                                    <div class="py-1">
                                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</a>
                                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                    </div>
                                    <div class="py-1">
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                Sign out
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>


            <!-- Sidebar -->
            <aside id="sidebar" class="fixed inset-y-0 left-0 bg-white shadow-lg max-h-screen transition-all duration-300 ease-in-out z-30 w-64">
                <div class="flex flex-col h-full">
                    <div class="flex items-center justify-between p-4 border-b">
                        <h1 class="text-xl font-bold leading-none text-gray-900">
                            <span class="text-blue-600">Malaria</span>Detect AI
                        </h1>
                        <button onclick="toggleSidebar()" class="p-1 rounded-lg text-gray-600 hover:bg-gray-100">
                            <svg id="toggle-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex-grow overflow-y-auto">
                        <nav class="p-4">
                            <ul class="space-y-1">
                                <li>
                                    <a href="{{ route('dashboard') }}" 
                                       class="flex items-center rounded-lg font-medium px-4 py-3 {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                        <span class="ml-3 sidebar-text">Dashboard</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('analysis.create') }}" 
                                       class="flex items-center rounded-lg font-medium px-4 py-3 {{ request()->routeIs('analysis.create') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span class="ml-3">New Analysis</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('analysis.index') }}" 
                                       class="flex items-center rounded-lg font-medium px-4 py-3 {{ request()->routeIs('analysis.index') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        <span class="ml-3">Past Analyses</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('reports.index') }}" 
                                       class="flex items-center rounded-lg font-medium px-4 py-3 {{ request()->routeIs('reports.index') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="ml-3">Reports</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    {{-- <div class="p-4 border-t">
                        <a href="" class="flex items-center space-x-4">
                            <img src="https://ui-avatars.com/api/?name={{ auth()->user()->name }}" alt="" class="w-10 h-10 rounded-full">
                            <div class="sidebar-text">
                                <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-600">View Profile</p>
                            </div>
                        </a>
                    </div> --}}
                </div>
            </aside>

            <!-- Main Content -->
            <div id="main-content" class="transition-all duration-300 ease-in-out ml-64">
                <main class="py-12">
                    {{ $slot }}
                </main>
            </div>

            
            <!-- Footer -->
            <footer class="bg-white shadow-inner mt-auto">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {{-- <!-- Company Info -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-600 tracking-wider uppercase mb-4">
                                MalariaDetect AI
                            </h3>
                            <p class="text-sm text-gray-500">
                                Empowering healthcare professionals with AI-powered malaria detection.
                            </p>
                        </div> --}}
                        {{-- <!-- Quick Links -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-600 tracking-wider uppercase mb-4">
                                Quick Links
                            </h3>
                            <ul class="space-y-2">
                                <li>
                                    <a href="#" class="text-sm text-gray-500 hover:text-gray-900">About Us</a>
                                </li>
                                <li>
                                    <a href="#" class="text-sm text-gray-500 hover:text-gray-900">Documentation</a>
                                </li>
                                <li>
                                    <a href="#" class="text-sm text-gray-500 hover:text-gray-900">Support</a>
                                </li>
                            </ul>
                        </div> --}}

                        {{-- <!-- Contact -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-600 tracking-wider uppercase mb-4">
                                Contact
                            </h3>
                            <ul class="space-y-2">
                                <li class="text-sm text-gray-500">
                                    <a href="mailto:support@malariadetect.ai" class="hover:text-gray-900">
                                        support@malariadetect.ai
                                    </a>
                                </li>
                            </ul>
                        </div> --}}
                    </div>

                    <div class="mt-8 border-t border-gray-200 pt-6">
                        <p class="text-sm text-gray-400 text-center">
                            © {{ date('Y') }} MalariaDetect AI. All rights reserved.
                        </p>
                    </div>
                </div>
            </footer>
        </div>

        <script>
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                const mainContent = document.getElementById('main-content');
                const textElements = document.querySelectorAll('.sidebar-text');
                
                sidebar.classList.toggle('w-64');
                sidebar.classList.toggle('w-16');
                mainContent.classList.toggle('ml-64');
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