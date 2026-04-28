@extends('layouts.app')

@section('title', 'POS')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="pos-container">
    <div class="products-section">
        <div class="search-bar">
            <input type="text" id="search-input" placeholder="Search products...">
        </div>
        
        <div class="categories">
            <div class="category active" data-category="all">All</div>
            <div class="category" data-category="disposable">Disposable</div>
            <div class="category" data-category="condiments">Condiments</div>
            <div class="category" data-category="frozen">Frozen</div>
            <div class="category" data-category="canned">Canned</div>
            <div class="category" data-category="laundry">Laundry</div>
            <div class="category" data-category="personal-care">Personal Care</div>
            <div class="category" data-category="snacks">Snacks</div>
            <div class="category" data-category="ice-cream">Ice Cream</div>
            <div class="category" data-category="biscuits">Biscuits</div>
            <div class="category" data-category="beverages">Beverages</div>
            <div class="category" data-category="candy">Candy</div>
            <div class="category" data-category="essentials">Essentials</div>
        </div>
        
        <div class="product-grid">
            @if($items->count() > 0)
                @foreach($items as $item)
                    <div class="product-card" data-category="{{ $item->category ?? 'other' }}">
                        <div class="product-name">{{ $item->item_name }}</div>
                        <div class="product-price">{{ number_format($item->price, 2) }}</div>
                    </div>
                @endforeach
            @else
                <div class="no-products">No products available</div>
            @endif
        </div>
    </div>

    <div class="checkout-section">
        <h3>Check out</h3>
        <div class="checkout-items">
            <div class="empty-cart">No items in cart</div>
        </div>
        <div class="total">TOTAL P0.00</div>
        <div class="checkout-actions">
            <button class="btn-cancel" onclick="cancelCheckout()">CANCEL</button>
            <button class="btn-pay" onclick="processPayment()">PAY (P0.00)</button>
        </div>
    </div>
</div>

<script>
// POS functionality
let cart = [];
let total = 0;

document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('search-input');
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const activeCategory = document.querySelector('.category.active').dataset.category;
        
        document.querySelectorAll('.product-card').forEach(product => {
            const productName = product.querySelector('.product-name').textContent.toLowerCase();
            const category = product.dataset.category;
            
            const matchesSearch = productName.includes(searchTerm);
            const matchesCategory = activeCategory === 'all' || category === activeCategory;
            
            if (matchesSearch && matchesCategory) {
                product.style.display = 'block';
            } else {
                product.style.display = 'none';
            }
        });
    });
    
    // Category filtering
    document.querySelectorAll('.category').forEach(cat => {
        cat.addEventListener('click', function() {
            document.querySelectorAll('.category').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            
            const category = this.dataset.category;
            const searchTerm = searchInput.value.toLowerCase();
            
            document.querySelectorAll('.product-card').forEach(product => {
                const productName = product.querySelector('.product-name').textContent.toLowerCase();
                const productCategory = product.dataset.category;
                
                const matchesSearch = productName.includes(searchTerm);
                const matchesCategory = category === 'all' || productCategory === category;
                
                if (matchesSearch && matchesCategory) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        });
    });
    
    // Product click to add to cart
    document.querySelectorAll('.product-card').forEach(product => {
        product.addEventListener('click', function() {
            const name = this.querySelector('.product-name').textContent;
            const price = parseFloat(this.querySelector('.product-price').textContent.replace(',', ''));
            addToCart(name, price);
        });
    });
});

function addToCart(name, price) {
    const existingItem = cart.find(item => item.name === name);
    if(existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({ name, price, quantity: 1 });
    }
    updateCart();
}

function updateCart() {
    const cartContainer = document.querySelector('.checkout-items');
    if(cart.length === 0) {
        cartContainer.innerHTML = '<div class="empty-cart">No items in cart</div>';
        total = 0;
    } else {
        cartContainer.innerHTML = cart.map((item, index) => `
            <div class="checkout-item">
                <span>${item.name}</span>
                <div class="qty-control">
                    <button onclick="updateQuantity(${index}, -1)">-</button>
                    <span>${item.quantity}</span>
                    <button onclick="updateQuantity(${index}, 1)">+</button>
                </div>
                <span>P${(item.price * item.quantity).toFixed(2)}</span>
            </div>
        `).join('');
        total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    }
    
    document.querySelector('.total').textContent = `TOTAL P${total.toFixed(2)}`;
    document.querySelector('.btn-pay').textContent = `PAY (P${total.toFixed(2)})`;
}

function updateQuantity(index, change) {
    cart[index].quantity += change;
    if(cart[index].quantity <= 0) {
        cart.splice(index, 1);
    }
    updateCart();
}

function cancelCheckout() {
    cart = [];
    total = 0;
    updateCart();
}

async function processPayment() {
    if (cart.length === 0) {
        alert('Your cart is empty!');
        return;
    }

    const payButton = document.querySelector('.btn-pay');
    payButton.disabled = true;
    payButton.textContent = 'Processing...';

    try {
        const response = await fetch('/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                items: cart
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('Payment successful! Inventory updated.');
            cart = [];
            total = 0;
            updateCart();
            // Refresh the page to show updated quantities
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            alert('Payment failed: ' + result.message);
        }
    } catch (error) {
        alert('Error processing payment: ' + error.message);
    } finally {
        payButton.disabled = false;
        payButton.textContent = `PAY (P${total.toFixed(2)})`;
    }
}
</script>
@endsection