@extends('layouts.app')

@section('title', 'POS')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="pos-container flex min-w-0 flex-col gap-6 xl:h-[calc(100vh-8rem)] xl:flex-row">
    {{-- Products section --}}
    <section class="products-section flex min-h-0 min-w-0 flex-1 flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
        <div class="search-bar mb-4 text-right">
            <div class="relative w-full sm:ml-auto sm:w-72">
                <i class="fa fa-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                <input
                    type="text"
                    id="search-input"
                    placeholder="Search products..."
                    class="w-full rounded-lg border border-slate-300 bg-slate-50 py-2.5 pl-10 pr-4 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                >
            </div>
        </div>

        <div class="categories mb-4 flex shrink-0 gap-2 overflow-x-auto pb-1 [-webkit-overflow-scrolling:touch] [scrollbar-width:thin]">
            <div class="category flex-none cursor-pointer whitespace-nowrap rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 active" data-category="all">All</div>
            <div class="category flex-none cursor-pointer whitespace-nowrap rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" data-category="disposable">Disposable</div>
            <div class="category flex-none cursor-pointer whitespace-nowrap rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" data-category="condiments">Condiments</div>
            <div class="category flex-none cursor-pointer whitespace-nowrap rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" data-category="frozen">Frozen</div>
            <div class="category flex-none cursor-pointer whitespace-nowrap rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" data-category="canned">Canned</div>
            <div class="category flex-none cursor-pointer whitespace-nowrap rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" data-category="laundry">Laundry</div>
            <div class="category flex-none cursor-pointer whitespace-nowrap rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" data-category="personal-care">Personal Care</div>
            <div class="category flex-none cursor-pointer whitespace-nowrap rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" data-category="snacks">Snacks</div>
            <div class="category flex-none cursor-pointer whitespace-nowrap rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" data-category="ice-cream">Ice Cream</div>
            <div class="category flex-none cursor-pointer whitespace-nowrap rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" data-category="biscuits">Biscuits</div>
            <div class="category flex-none cursor-pointer whitespace-nowrap rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" data-category="beverages">Beverages</div>
            <div class="category flex-none cursor-pointer whitespace-nowrap rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" data-category="candy">Candy</div>
            <div class="category flex-none cursor-pointer whitespace-nowrap rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" data-category="essentials">Essentials</div>
        </div>

        <div class="product-grid grid min-h-0 min-w-0 flex-1 auto-rows-[7rem] grid-cols-2 gap-3 overflow-y-auto p-1 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-3 2xl:grid-cols-4">
            @if($items->count() > 0)
                @foreach($items as $item)
                    <div class="product-card flex min-w-0 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-3 text-center transition-all hover:-translate-y-0.5 hover:border-brand-300 hover:bg-brand-50 hover:shadow-md" data-category="{{ $item->category ?? 'other' }}">
                        <div class="product-name mb-1 line-clamp-2 text-sm font-semibold text-slate-700">{{ ucwords($item->item_name) }}</div>
                        <div class="product-price text-base font-bold text-brand-700">{{ number_format($item->price, 2) }}</div>
                    </div>
                @endforeach
            @else
                <div class="no-products col-span-full py-16 text-center text-sm text-slate-400">
                    <i class="fa fa-cube mb-3 block text-4xl"></i>
                    No products available
                </div>
            @endif
        </div>
    </section>

    {{-- Checkout section --}}
    <aside class="checkout-section flex min-h-0 w-full shrink-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-5 xl:w-96">
        <!-- Member Scanner Section -->
        <div id="memberScanSection" class="mb-4 shrink-0 border-b border-slate-100 pb-4">
          <!-- Selected member display (hidden by default) -->
          <div id="selectedMemberDisplay" style="display: none; align-items: center; justify-content: space-between;" class="mb-3 rounded-lg border border-brand-200 bg-brand-50 p-3">
            <div class="flex items-center gap-2.5">
              <i class="fa fa-user-circle text-xl text-brand-700"></i>
              <div>
                <div id="selectedMemberName" class="text-sm font-semibold text-brand-900"></div>
                <div id="selectedMemberID" class="text-xs text-slate-500"></div>
              </div>
            </div>
            <button onclick="clearMember()" class="cursor-pointer border-0 bg-transparent text-lg leading-none text-red-500 hover:text-red-700">&#x2715;</button>
          </div>

          <!-- Scanner input -->
          <div id="scannerInputSection">
            <label for="memberScanInput" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
              <i class="fa fa-qrcode"></i> Scan Member QR / Barcode
            </label>
            <div class="flex gap-2">
              <input
                type="text"
                id="memberScanInput"
                placeholder="Scan or type member ID..."
                class="flex-1 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                onkeydown="if(event.key==='Enter') lookupMember()"
                autofocus
              />
              <button onclick="lookupMember()" class="rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                <i class="fa fa-search"></i>
              </button>
            </div>
            <div id="memberScanError" style="display: none;" class="mt-1.5 text-xs text-red-600">Member not found.</div>
          </div>

          <!-- Non-member toggle -->
          <div class="mt-3 text-center">
            <button onclick="setNonMember()" id="nonMemberBtn" class="cursor-pointer rounded-full border border-slate-300 bg-white px-4 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50">
              Continue as Non-Member
            </button>
            <div id="nonMemberBadge" style="display: none; align-items: center; justify-content: space-between;" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
              <span><i class="fa fa-user-o"></i> Non-Member</span>
              <button onclick="clearMember()" class="cursor-pointer border-0 bg-transparent text-base leading-none text-red-500 hover:text-red-700">&#x2715;</button>
            </div>
          </div>
        </div>

        <h3 class="mb-3 shrink-0 text-base font-semibold text-slate-900">Check out</h3>
        <div class="checkout-items min-h-0 flex-1 overflow-y-auto rounded-lg border border-slate-200 p-2 max-h-48 sm:max-h-72 lg:max-h-96 xl:max-h-none">
            <div class="empty-cart py-8 text-center text-sm text-slate-400">No items in cart</div>
        </div>
        <div class="total mt-4 -mx-5 shrink-0 border-t border-slate-100 px-5 py-3 text-right text-lg font-bold text-slate-900">TOTAL P0.00</div>
        <div class="checkout-actions mt-1 flex shrink-0 gap-3">
            <button class="btn-cancel flex-1 cursor-pointer rounded-lg border border-red-200 bg-white px-4 py-3 text-sm font-semibold text-red-600 transition-colors hover:bg-red-50" onclick="cancelCheckout()">CANCEL</button>
            <button class="btn-pay flex-1 cursor-pointer rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-slate-300" onclick="processPayment()">PAY (P0.00)</button>
        </div>

        <!-- Hidden inputs for member tracking -->
        <input type="hidden" id="selectedMemberIdInput" name="member_id" value="">
        <input type="hidden" id="isNonMemberInput" name="is_non_member" value="0">
    </aside>
</div>

<script>
// POS functionality
let cart = [];
let total = 0;

// Member tracking
let currentMember = null;
let isNonMember = false;

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
        cartContainer.innerHTML = '<div class="empty-cart py-8 text-center text-sm text-slate-400">No items in cart</div>';
        total = 0;
    } else {
        cartContainer.innerHTML = cart.map((item, index) => `
            <div class="checkout-item flex items-center justify-between gap-2 border-b border-slate-100 py-2.5 last:border-0">
                <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700">${item.name}</span>
                <div class="qty-control flex shrink-0 items-center gap-2">
                    <button onclick="updateQuantity(${index}, -1)" class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-bold text-slate-600 transition-colors hover:bg-slate-50">-</button>
                    <span class="min-w-5 text-center text-sm font-semibold text-slate-900">${item.quantity}</span>
                    <button onclick="updateQuantity(${index}, 1)" class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-bold text-slate-600 transition-colors hover:bg-slate-50">+</button>
                </div>
                <span class="shrink-0 text-sm font-semibold text-slate-900">P${(item.price * item.quantity).toFixed(2)}</span>
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
    clearMember();
}

async function processPayment() {
    if (cart.length === 0) {
        alert('Your cart is empty!');
        return;
    }

    // Check if member or non-member is selected
    if (!currentMember && !isNonMember) {
        alert('Please scan a member or select Non-Member before paying.');
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
                items: cart,
                member_id: document.getElementById('selectedMemberIdInput').value,
                is_non_member: document.getElementById('isNonMemberInput').value
            })
        });

        const result = await response.json();

        if (result.success) {
            // Save sale to localStorage for analytics
            const sale = {
                id: Date.now().toString(),
                date: new Date().toISOString(),
                total: total,
                items: [...cart]
            };
            
            const existing = JSON.parse(localStorage.getItem("ccf_sales") || "[]");
            existing.push(sale);
            localStorage.setItem("ccf_sales", JSON.stringify(existing));
            
            console.log('Sale saved to localStorage:', sale);
            console.log('Total sales in localStorage:', existing.length);
            
            alert('Payment successful! Inventory updated.');
            cart = [];
            total = 0;
            updateCart();
            clearMember();
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

// Member lookup functions
async function lookupMember() {
  const query = document.getElementById('memberScanInput').value.trim();
  console.log('Looking up member with query:', query);
  
  if (!query) return;

  try {
    const response = await fetch(`/members/lookup?q=${encodeURIComponent(query)}`);
    const data = await response.json();
    console.log('Lookup response:', data);

    if (data.found) {
      currentMember = data.member;
      isNonMember = false;
      console.log('Member found:', data.member);

      document.getElementById('selectedMemberName').textContent = data.member.first_name + ' ' + data.member.last_name;
      document.getElementById('selectedMemberID').textContent = 'ID: ' + data.member.member_number;
      document.getElementById('selectedMemberDisplay').style.display = 'flex';
      document.getElementById('scannerInputSection').style.display = 'none';
      document.getElementById('nonMemberBtn').style.display = 'none';
      document.getElementById('nonMemberBadge').style.display = 'none';
      document.getElementById('memberScanError').style.display = 'none';
      document.getElementById('selectedMemberIdInput').value = data.member.id;
      document.getElementById('isNonMemberInput').value = '0';
    } else {
      console.log('Member not found');
      document.getElementById('memberScanError').style.display = 'block';
    }
  } catch (e) {
    console.error('Lookup error:', e);
    document.getElementById('memberScanError').style.display = 'block';
  }
}

function setNonMember() {
  currentMember = null;
  isNonMember = true;

  document.getElementById('selectedMemberDisplay').style.display = 'none';
  document.getElementById('scannerInputSection').style.display = 'none';
  document.getElementById('nonMemberBtn').style.display = 'none';
  document.getElementById('nonMemberBadge').style.display = 'flex';
  document.getElementById('selectedMemberIdInput').value = '';
  document.getElementById('isNonMemberInput').value = '1';
}

function clearMember() {
  currentMember = null;
  isNonMember = false;

  document.getElementById('selectedMemberDisplay').style.display = 'none';
  document.getElementById('nonMemberBadge').style.display = 'none';
  document.getElementById('scannerInputSection').style.display = 'block';
  document.getElementById('nonMemberBtn').style.display = 'block';
  document.getElementById('memberScanInput').value = '';
  document.getElementById('selectedMemberIdInput').value = '';
  document.getElementById('isNonMemberInput').value = '0';
  document.getElementById('memberScanError').style.display = 'none';
}
</script>
@endsection
