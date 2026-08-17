<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCFMPC - @yield('title')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-brand-50 text-slate-900 antialiased">
    @php
        $role = Auth::user()->role ?? null;
    @endphp

    <div class="flex min-h-screen">
        {{-- Off-canvas drawer backdrop (below lg) --}}
        <div id="sidebarBackdrop" class="fixed inset-0 z-40 hidden bg-slate-900/50 lg:hidden" onclick="closeSidebar()"></div>

        {{-- Sidebar: off-canvas drawer < lg, static sticky sidebar >= lg --}}
        <aside id="sidebar" class="sidebar fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-brand-200 bg-white shadow-xl transition-transform duration-200 lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:shrink-0 lg:translate-x-0 lg:border-b-0 lg:shadow-none">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 lg:h-16 lg:px-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                        <i class="fa fa-leaf"></i>
                    </div>
                    <div class="min-w-0 leading-tight">
                        <p class="truncate text-sm font-semibold text-slate-900">CCFMPC</p>
                        <p class="hidden truncate text-xs text-slate-500 lg:block">Cooperative Store</p>
                    </div>
                </div>
                <button type="button" onclick="closeSidebar()" class="-mr-2 flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 lg:hidden" aria-label="Close menu">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <nav class="flex flex-1 flex-col items-center gap-1 overflow-y-auto px-2 py-2 lg:items-stretch lg:overflow-visible lg:px-3 lg:py-0 lg:pt-4 lg:pb-6">
                @auth
                    @if($role === 'admin')
                        <div class="hidden px-3 pb-2 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 lg:block">Point of Sale</div>
                        <a href="{{ route('shop.index') }}" class="flex items-center gap-3 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('shop.index') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <i class="fa fa-shopping-cart w-5 text-center text-base"></i> POS
                        </a>

                        <div class="hidden px-3 pb-2 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 lg:block">Management</div>
                        <a href="{{ route('inventory.index') }}" class="flex items-center gap-3 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('inventory.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <i class="fa fa-archive w-5 text-center text-base"></i> Inventory
                        </a>
                        <a href="{{ route('members.index') }}" class="flex items-center gap-3 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('members.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <i class="fa fa-users w-5 text-center text-base"></i> Members
                        </a>
                        <a href="{{ route('analytics.index') }}" class="flex items-center gap-3 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('analytics.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <i class="fa fa-bar-chart w-5 text-center text-base"></i> Analytics
                        </a>
                        <a href="{{ route('credits.index') }}" class="flex items-center gap-3 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('credits.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <i class="fa fa-credit-card w-5 text-center text-base"></i> Credits
                        </a>
                    @else
                        <div class="hidden px-3 pb-2 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 lg:block">Point of Sale</div>
                        <a href="{{ route('shop.index') }}" class="flex items-center gap-3 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('shop.index') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <i class="fa fa-shopping-cart w-5 text-center text-base"></i> POS
                        </a>
                        <a href="{{ route('credits.index') }}" class="flex items-center gap-3 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('credits.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <i class="fa fa-credit-card w-5 text-center text-base"></i> Credits
                        </a>
                    @endif

                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="flex items-center gap-3 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm font-medium text-red-600 transition-colors hover:bg-red-50 lg:mt-auto">
                        <i class="fa fa-sign-out w-5 text-center text-base"></i> Logout
                    </a>
                @endauth
            </nav>
        </aside>

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 border-b border-brand-200 bg-white/90 backdrop-blur">
                <div class="flex h-16 items-center justify-between gap-4 px-4 md:px-6">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" onclick="openSidebar()" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 lg:hidden" aria-label="Open menu">
                            <i class="fa fa-bars"></i>
                        </button>
                        <div class="min-w-0">
                            <p class="hidden truncate text-sm text-slate-500 sm:block">Cavite College of Fisheries Multi-Purpose Cooperative</p>
                            <h1 class="truncate text-lg font-semibold text-slate-900">@hasSection('title') @yield('title') @else Overview @endif</h1>
                        </div>
                    </div>
                    <div class="notification-top relative flex items-center">
                        <a href="#" onclick="toggleNotifications()" class="notification-link-top relative flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700">
                            <i class="fa fa-bell text-lg"></i>
                            <span class="notification-badge absolute -right-0.5 -top-0.5 hidden h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white" id="notificationBadge" style="display: none;">0</span>
                            <span id="redDotIndicator" style="display: none; position: absolute; top: 2px; right: 2px; width: 10px; height: 10px; background: #ef4444; border-radius: 50%; border: 2px solid #fff; animation: blink 1.5s infinite; z-index: 9999;"></span>
                        </a>
                    </div>
                </div>
            </header>

            <main class="min-w-0 flex-1 px-4 py-6 md:px-6 lg:px-8 lg:py-8">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Logout Form -->
    @auth
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    @endauth

    <!-- Notification Modal -->
    <div class="notif-overlay fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4" id="notificationOverlay">
        <div class="notif-box relative flex h-[80vh] w-full max-w-lg flex-col rounded-xl bg-white p-6 shadow-xl">
            <span class="notif-close absolute right-5 top-4 cursor-pointer text-2xl leading-none text-slate-400 transition-colors hover:text-slate-600" onclick="toggleNotifications()">&times;</span>
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Low Stock Notifications</h2>
            <div class="notif-content flex-1 overflow-y-auto pr-1" id="notificationContent">
                <div class="no-notifications">No low stock items</div>
            </div>
        </div>
    </div>

    <script>
        // Notification System
        let lowStockItems = [];
        const LOW_STOCK_THRESHOLD = 10; // Items with stock <= 10 will trigger notification

        // Toggle notification modal
        function toggleNotifications() {
            const overlay = document.getElementById('notificationOverlay');
            overlay.style.display = overlay.style.display === 'flex' ? 'none' : 'flex';
        }

        // Update notification badge
        function updateNotificationBadge() {
            const badge = document.getElementById('notificationBadge');
            const redDot = document.getElementById('redDotIndicator');
            const notificationLink = document.querySelector('.notification-link-top');
            
            console.log('Updating notification badge. Low stock items:', lowStockItems.length);
            
            badge.textContent = lowStockItems.length;
            badge.style.display = lowStockItems.length > 0 ? 'flex' : 'none';
            
            // Show/hide red dot indicator element
            if (lowStockItems.length > 0) {
                redDot.style.display = 'block';
                notificationLink.classList.add('has-notifications');
                console.log('Showing red dot - items found:', lowStockItems.length);
            } else {
                redDot.style.display = 'none';
                notificationLink.classList.remove('has-notifications');
                console.log('Hiding red dot - no items found');
            }
        }

        // Render notification content
        function renderNotifications() {
            const content = document.getElementById('notificationContent');
            
            if (lowStockItems.length === 0) {
                content.innerHTML = '<div class="no-notifications">No low stock items</div>';
            } else {
                content.innerHTML = lowStockItems.map(item => `
                    <div class="notif-item">
                        <div>
                            <div class="notif-item-name">${item.name}</div>
                            <div class="notif-item-details">Category: ${item.category}</div>
                        </div>
                        <div class="notif-item-stock">${item.stock} units</div>
                    </div>
                `).join('');
            }
        }

        // Check for low stock items
        async function checkLowStock() {
            try {
                const response = await fetch('/inventory/low-stock');
                const data = await response.json();
                
                if (data.low_stock_items) {
                    lowStockItems = data.low_stock_items.map(item => ({
                        name: item.item_name,
                        category: item.category,
                        stock: item.quantity,
                        id: item.id
                    }));
                } else {
                    lowStockItems = [];
                }
                
                updateNotificationBadge();
                renderNotifications();
            } catch (error) {
                console.error('Error fetching low stock data:', error);
                // Fallback to empty array if API fails
                lowStockItems = [];
                updateNotificationBadge();
                renderNotifications();
            }
        }

        // Initialize notification system
        document.addEventListener('DOMContentLoaded', function() {
            // Check low stock on page load
            checkLowStock();
            
            // Set up periodic checking (every 30 seconds)
            setInterval(checkLowStock, 30000);
        });

        // Close modal when clicking outside
        document.getElementById('notificationOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                toggleNotifications();
            }
        });

        // Mobile/tablet off-canvas sidebar
        function openSidebar() {
            document.getElementById('sidebar').classList.add('open');
            document.getElementById('sidebarBackdrop').classList.remove('hidden');
        }

        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarBackdrop').classList.add('hidden');
        }
    </script>

</body>
</html>
