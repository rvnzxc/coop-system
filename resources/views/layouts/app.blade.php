<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CCFMPC - @yield('title')</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        /* Override styles for bordered navigation */
        .sidebar-nav {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
        }
        
        .sidebar-nav a {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border: none !important;
            border-left: 6px solid #d0ff00 !important;
            background: linear-gradient(90deg, rgba(208, 255, 0, 0.1) 0%, rgba(208, 255, 0, 0.05) 50%, transparent 100%) !important;
            border-radius: 0 16px 16px 0 !important;
            margin: 8px 0 !important;
            width: 100% !important;
            text-align: left !important;
            padding: 20px 25px !important;
            position: relative !important;
            transition: all 0.3s ease !important;
            font-size: 16px !important;
            font-weight: bold !important;
            min-height: 60px !important;
            color: #ffffff !important;
        }
        
        .sidebar-nav a:hover {
            border-left-color: #fff !important;
            background: linear-gradient(90deg, rgba(208, 255, 0, 0.2) 0%, rgba(208, 255, 0, 0.1) 50%, transparent 100%) !important;
            transform: translateX(8px) !important;
            box-shadow: 0 4px 15px rgba(208, 255, 0, 0.4) !important;
        }
        
        .sidebar-nav a.active {
            border-left: 6px solid #fff !important;
            background: linear-gradient(90deg, rgba(208, 255, 0, 0.3) 0%, rgba(208, 255, 0, 0.15) 50%, transparent 100%) !important;
            box-shadow: 0 4px 20px rgba(208, 255, 0, 0.5) !important;
        }

        .sidebar-nav a::before {
            content: '' !important;
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 2px !important;
            height: 100% !important;
            background: linear-gradient(180deg, transparent, #d0ff00, transparent) !important;
            opacity: 0 !important;
            transition: opacity 0.3s ease !important;
        }

        .sidebar-nav a:hover::before {
            opacity: 1 !important;
        }

        /* Hide scrollbar and better center the sidebar content */
        .sidebar {
            overflow: hidden !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .sidebar-nav {
            width: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: flex-start !important;
            padding: 0 0 20px 0 !important;
            margin-top: -370px !important;
        }

        /* Responsive Design for All Devices */

        /* Large Desktop (1200px and up) */
        @media (min-width: 1200px) {
            .sidebar {
                width: 220px !important;
            }
            
            .content-area {
                flex: 1;
                margin-left: 220px;
                min-height: 100vh;
            }
            
            .sidebar-nav a {
                font-size: 18px !important;
                padding: 22px 30px !important;
                min-height: 65px !important;
            }
        }

        /* Desktop (992px to 1199px) */
        @media (max-width: 1199px) and (min-width: 992px) {
            .sidebar {
                width: 260px !important;
            }
            
            .content-area {
                margin-left: 260px !important;
            }
            
            .sidebar-nav a {
                font-size: 17px !important;
                padding: 21px 28px !important;
                min-height: 62px !important;
            }
        }

        /* Tablet Landscape (768px to 991px) */
        @media (max-width: 991px) and (min-width: 768px) {
            .sidebar {
                width: 220px !important;
            }
            
            .content-area {
                margin-left: 220px !important;
            }
            
            .sidebar-nav a {
                font-size: 15px !important;
                padding: 18px 22px !important;
                min-height: 55px !important;
                border-left: 5px solid #d0ff00 !important;
                border-radius: 0 14px 14px 0 !important;
            }
            
            header {
                font-size: 28px !important;
                height: 70px !important;
            }
            
            .main-content {
                min-height: calc(100vh - 70px) !important;
            }
        }

        /* Tablet Portrait (600px to 767px) */
        @media (max-width: 767px) and (min-width: 600px) {
            .sidebar {
                width: 200px !important;
            }
            
            .content-area {
                margin-left: 200px !important;
            }
            
            .sidebar-nav a {
                font-size: 14px !important;
                padding: 16px 20px !important;
                min-height: 50px !important;
                border-left: 4px solid #d0ff00 !important;
                border-radius: 0 12px 12px 0 !important;
            }
            
            header {
                font-size: 26px !important;
                height: 65px !important;
            }
            
            .main-content {
                min-height: calc(100vh - 65px) !important;
            }
        }

        /* Mobile Landscape (480px to 599px) */
        @media (max-width: 599px) and (min-width: 480px) {
            .sidebar {
                width: 180px !important;
            }
            
            .content-area {
                margin-left: 180px !important;
            }
            
            .sidebar-nav a {
                font-size: 13px !important;
                padding: 14px 18px !important;
                min-height: 45px !important;
                border-left: 3px solid #d0ff00 !important;
                border-radius: 0 10px 10px 0 !important;
            }
            
            header {
                font-size: 24px !important;
                height: 60px !important;
            }
            
            .main-content {
                min-height: calc(100vh - 60px) !important;
            }
        }

        /* Mobile Portrait (320px to 479px) */
        @media (max-width: 479px) {
            .sidebar {
                width: 160px !important;
            }
            
            .content-area {
                margin-left: 160px !important;
            }
            
            .sidebar-nav a {
                font-size: 12px !important;
                padding: 12px 15px !important;
                min-height: 40px !important;
                border-left: 3px solid #d0ff00 !important;
                border-radius: 0 8px 8px 0 !important;
                margin: 6px 0 !important;
            }
            
            .sidebar-nav a i {
                font-size: 14px !important;
            }
            
            header {
                font-size: 20px !important;
                height: 55px !important;
            }
            
            .main-content {
                min-height: calc(100vh - 55px) !important;
                padding: 15px !important;
            }
        }

        /* Ultra Small Mobile (up to 319px) */
        @media (max-width: 319px) {
            .sidebar {
                width: 140px !important;
            }
            
            .content-area {
                margin-left: 140px !important;
            }
            
            .sidebar-nav a {
                font-size: 11px !important;
                padding: 10px 12px !important;
                min-height: 35px !important;
                border-left: 2px solid #d0ff00 !important;
                border-radius: 0 6px 6px 0 !important;
                margin: 4px 0 !important;
            }
            
            .sidebar-nav a i {
                font-size: 12px !important;
                display: none !important; /* Hide icons on very small screens */
            }
            
            header {
                font-size: 18px !important;
                height: 50px !important;
            }
            
            .main-content {
                min-height: calc(100vh - 50px) !important;
                padding: 10px !important;
            }

            /* Category buttons responsive styles */
            .category {
                font-size: 9px !important;
                padding: 4px 6px !important;
                margin: 0 2px !important;
            }

            .categories {
                flex-wrap: wrap !important;
                gap: 4px !important;
            }
        }

        /* Enhanced Category Buttons Responsive Design */
        
        /* Large Desktop (1200px and up) */
        @media (min-width: 1200px) {
            .category {
                font-size: 14px !important;
                padding: 10px 16px !important;
                margin: 0 4px !important;
            }
            
            .categories {
                flex-wrap: nowrap !important;
                gap: 8px !important;
                justify-content: flex-start !important;
                overflow-x: auto !important;
                padding: 5px 0 10px 0 !important;
            }
        }

        /* Desktop (992px to 1199px) */
        @media (max-width: 1199px) and (min-width: 992px) {
            .category {
                font-size: 13px !important;
                padding: 9px 14px !important;
                margin: 0 3px !important;
            }
            
            .categories {
                flex-wrap: nowrap !important;
                gap: 6px !important;
                justify-content: flex-start !important;
                overflow-x: auto !important;
                padding: 5px 0 10px 0 !important;
            }
        }

        /* Tablet Landscape (768px to 991px) */
        @media (max-width: 991px) and (min-width: 768px) {
            .category {
                font-size: 12px !important;
                padding: 8px 12px !important;
                margin: 0 2px !important;
            }
            
            .categories {
                flex-wrap: wrap !important;
                gap: 5px !important;
                justify-content: flex-start !important;
            }
        }

        /* Tablet Portrait (600px to 767px) */
        @media (max-width: 767px) and (min-width: 600px) {
            .category {
                font-size: 11px !important;
                padding: 7px 10px !important;
                margin: 0 2px !important;
                min-width: auto !important;
                flex: 0 0 auto !important;
            }
            
            .categories {
                flex-wrap: wrap !important;
                gap: 4px !important;
                justify-content: flex-start !important;
            }
        }

        /* Mobile Landscape (480px to 599px) */
        @media (max-width: 599px) and (min-width: 480px) {
            .category {
                font-size: 10px !important;
                padding: 6px 8px !important;
                margin: 0 1px !important;
                min-width: auto !important;
                flex: 0 0 auto !important;
            }
            
            .categories {
                flex-wrap: wrap !important;
                gap: 3px !important;
                justify-content: flex-start !important;
            }
        }

        /* Mobile Portrait (320px to 479px) */
        @media (max-width: 479px) {
            .category {
                font-size: 9px !important;
                padding: 5px 6px !important;
                margin: 0 1px !important;
                min-width: auto !important;
                flex: 0 0 auto !important;
            }
            
            .categories {
                flex-wrap: wrap !important;
                gap: 2px !important;
                justify-content: flex-start !important;
            }
        }

        /* Ultra Small Mobile (up to 319px) */
        @media (max-width: 319px) {
            .category {
                font-size: 8px !important;
                padding: 4px 5px !important;
                margin: 0 1px !important;
                min-width: auto !important;
                flex: 0 0 auto !important;
            }
            
            .categories {
                flex-wrap: wrap !important;
                gap: 2px !important;
                justify-content: flex-start !important;
            }
        }

        @media (max-width: 599px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 8px !important;
            }
        }

        @media (max-width: 479px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 6px !important;
            }
            
            .product-card {
                padding: 8px !important;
            }
            
            .product-name {
                font-size: 11px !important;
            }
            
            .product-price {
                font-size: 10px !important;
            }
        }

        @media (max-width: 319px) {
            .product-grid {
                grid-template-columns: repeat(1, 1fr) !important;
                gap: 4px !important;
            }
            
            .product-card {
                padding: 6px !important;
                min-height: 80px !important;
            }
            
            .product-name {
                font-size: 10px !important;
            }
            
            .product-price {
                font-size: 9px !important;
            }
        }
    </style>
</head>
<body>
    <div class="layout-container">
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <a href="{{ route('shop.index') }}" {{ request()->is('shop*') ? 'class="active"' : '' }}><i class="fa fa-shopping-cart"></i> POS</a>
                <a href="{{ route('inventory.index') }}" {{ request()->is('inventory*') ? 'class="active"' : '' }}><i class="fa fa-archive"></i> Inventory</a>
                <a href="#" onclick="toggleNotifications()" class="notification-link">
                    <i class="fa fa-bell"></i> Notifications
                    <span class="notification-badge" id="notificationBadge">0</span>
                    <span id="redDotIndicator" style="display: none; position: absolute; top: 4px; right: 4px; width: 14px; height: 14px; background: #ff0000; border-radius: 50%; border: 2px solid #fff; animation: blink 1.5s infinite; z-index: 9999;"></span>
                </a>
                <a href="#" {{ request()->is('members*') ? 'class="active"' : '' }}><i class="fa fa-users"></i> Members</a>
            </nav>
        </aside>
        <header>
            <div class="topnav" id="myTopnav">
                <div class="logo-placeholder">
                    <i class="fa fa-image" style="font-size: 40px; color: #d0ff00;"></i>
                </div>
                <div class="title">Cavite College of Fisheries Multi-Purpose Cooperative </div>
            </div>
        </header>
        <div class="content-area">
            <main class="main-content">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Notification Modal -->
    <div class="notif-overlay" id="notificationOverlay">
        <div class="notif-box">
            <span class="notif-close" onclick="toggleNotifications()">&times;</span>
            <h2>Low Stock Notifications</h2>
            <div class="notif-content" id="notificationContent">
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
            const notificationLink = document.querySelector('.notification-link');
            
            badge.textContent = lowStockItems.length;
            badge.style.display = lowStockItems.length > 0 ? 'flex' : 'none';
            
            // Show/hide red dot indicator element
            if (lowStockItems.length > 0) {
                redDot.style.display = 'block';
                notificationLink.classList.add('has-notifications');
            } else {
                redDot.style.display = 'none';
                notificationLink.classList.remove('has-notifications');
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
    </script>

</body>
</html>