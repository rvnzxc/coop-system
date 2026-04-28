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
        <!-- Member Scanner Section -->
        <div id="memberScanSection" style="margin-bottom: 15px; border-bottom: 1px solid #e0e0e0; padding-bottom: 15px;">
          
          <!-- Selected member display (hidden by default) -->
          <div id="selectedMemberDisplay" style="display: none; background: #e8f5e8; border: 1px solid #2d5a2d; border-radius: 8px; padding: 10px 12px; margin-bottom: 10px; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <i class="fa fa-user-circle" style="color: #1b3a1b; font-size: 20px;"></i>
              <div>
                <div id="selectedMemberName" style="font-weight: bold; font-size: 13px; color: #1b3a1b;"></div>
                <div id="selectedMemberID" style="font-size: 11px; color: #555;"></div>
              </div>
            </div>
            <button onclick="clearMember()" style="background: none; border: none; color: #d32f2f; cursor: pointer; font-size: 18px; line-height: 1;">&#x2715;</button>
          </div>

          <!-- Scanner input -->
          <div id="scannerInputSection">
            <label style="font-size: 12px; font-weight: bold; color: #555; display: block; margin-bottom: 6px;">
              <i class="fa fa-qrcode"></i> Scan Member QR / Barcode
            </label>
            <div style="display: flex; gap: 6px;">
              <input 
                type="text" 
                id="memberScanInput" 
                placeholder="Scan or type member ID..." 
                style="flex: 1; padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;"
                onkeydown="if(event.key==='Enter') lookupMember()"
                autofocus
              />
              <button onclick="lookupMember()" style="background: #1b3a1b; color: #d0ff00; border: none; border-radius: 6px; padding: 8px 10px; cursor: pointer; font-size: 13px; font-weight: bold;">
                <i class="fa fa-search"></i>
              </button>
            </div>
            <div id="memberScanError" style="color: #d32f2f; font-size: 11px; margin-top: 4px; display: none;">Member not found.</div>
          </div>

          <!-- Non-member toggle -->
          <div style="margin-top: 10px; text-align: center;">
            <button onclick="setNonMember()" id="nonMemberBtn" style="background: none; border: 1px solid #aaa; border-radius: 20px; padding: 4px 14px; font-size: 11px; color: #666; cursor: pointer;">
              Continue as Non-Member
            </button>
            <div id="nonMemberBadge" style="display: none; background: #f5f5f5; border: 1px solid #ccc; border-radius: 8px; padding: 6px 10px; font-size: 12px; color: #666; align-items: center; justify-content: space-between;">
              <span><i class="fa fa-user-o"></i> Non-Member</span>
              <button onclick="clearMember()" style="background: none; border: none; color: #d32f2f; cursor: pointer; font-size: 16px;">&#x2715;</button>
            </div>
          </div>

        </div>

        <h3>Check out</h3>
        <div class="checkout-items">
            <div class="empty-cart">No items in cart</div>
        </div>
        <div class="total">TOTAL P0.00</div>
        <div class="checkout-actions">
            <button class="btn-cancel" onclick="cancelCheckout()">CANCEL</button>
            <button class="btn-pay" onclick="processPayment()">PAY (P0.00)</button>
        </div>
        
        <!-- Hidden inputs for member tracking -->
        <input type="hidden" id="selectedMemberIdInput" name="member_id" value="">
        <input type="hidden" id="isNonMemberInput" name="is_non_member" value="0">
    </div>
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