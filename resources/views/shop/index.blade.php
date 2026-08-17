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

          <!-- Credit balance display (hidden by default) -->
          <div id="creditBalanceSection" style="display: none;" class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <i class="fa fa-credit-card text-sm text-amber-600"></i>
                <span class="text-xs font-medium text-amber-700">Outstanding Credit: <span id="creditBalanceAmount" class="font-bold">P0.00</span></span>
              </div>
              <button onclick="openPayCreditModal()" class="cursor-pointer rounded bg-amber-600 px-2.5 py-1 text-xs font-semibold text-white transition-colors hover:bg-amber-700">Pay</button>
            </div>
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

        <!-- Payment method selection (members only) -->
        <div id="paymentMethodSection" style="display: none;" class="mt-3 shrink-0">
          <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Payment Method</div>
          <div class="flex gap-2">
            <button onclick="setPaymentMethod('cash')" id="btnCash" class="flex-1 cursor-pointer rounded-lg border-2 border-brand-600 bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700 transition-colors">
              <i class="fa fa-money mr-1"></i> Cash
            </button>
            <button onclick="setPaymentMethod('credit')" id="btnCredit" class="flex-1 cursor-pointer rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition-colors hover:border-brand-300 hover:text-brand-600">
              <i class="fa fa-credit-card mr-1"></i> Credit
            </button>
          </div>
        </div>

        <div class="total mt-4 -mx-5 shrink-0 border-t border-slate-100 px-5 py-3 text-right text-lg font-bold text-slate-900">TOTAL P0.00</div>
        <div class="checkout-actions mt-1 flex shrink-0 gap-3">
            <button class="btn-cancel flex-1 cursor-pointer rounded-lg border border-red-200 bg-white px-4 py-3 text-sm font-semibold text-red-600 transition-colors hover:bg-red-50" onclick="cancelCheckout()">CANCEL</button>
            <button class="btn-pay flex-1 cursor-pointer rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-slate-300" onclick="processPayment()">PAY (P0.00)</button>
        </div>

        <!-- Hidden inputs for member tracking -->
        <input type="hidden" id="selectedMemberIdInput" name="member_id" value="">
        <input type="hidden" id="isNonMemberInput" name="is_non_member" value="0">
        <input type="hidden" id="paymentMethodInput" name="payment_method" value="cash">
    </aside>
</div>

<script>
// POS functionality
let cart = [];
let total = 0;

// Member tracking
let currentMember = null;
let isNonMember = false;
let selectedPaymentMethod = 'cash';
let currentOutstandingBalance = 0;

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

// Payment method
function setPaymentMethod(method) {
    selectedPaymentMethod = method;
    document.getElementById('paymentMethodInput').value = method;

    const btnCash = document.getElementById('btnCash');
    const btnCredit = document.getElementById('btnCredit');

    if (method === 'cash') {
        btnCash.className = 'flex-1 cursor-pointer rounded-lg border-2 border-brand-600 bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700 transition-colors';
        btnCredit.className = 'flex-1 cursor-pointer rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition-colors hover:border-brand-300 hover:text-brand-600';
        document.querySelector('.btn-pay').textContent = `PAY (P${total.toFixed(2)})`;
    } else {
        btnCredit.className = 'flex-1 cursor-pointer rounded-lg border-2 border-brand-600 bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700 transition-colors';
        btnCash.className = 'flex-1 cursor-pointer rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition-colors hover:border-brand-300 hover:text-brand-600';
        document.querySelector('.btn-pay').textContent = `CHARGE TO CREDIT (P${total.toFixed(2)})`;
    }
}

async function processPayment() {
    if (cart.length === 0) {
        alert('Your cart is empty!');
        return;
    }

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
                is_non_member: document.getElementById('isNonMemberInput').value,
                payment_method: document.getElementById('paymentMethodInput').value
            })
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message || 'Payment successful! Inventory updated.');
            cart = [];
            total = 0;
            selectedPaymentMethod = 'cash';
            updateCart();
            clearMember();
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
  if (!query) return;

  try {
    const response = await fetch(`/members/lookup?q=${encodeURIComponent(query)}`);
    const data = await response.json();

    if (data.found) {
      currentMember = data.member;
      isNonMember = false;

      document.getElementById('selectedMemberName').textContent = data.member.first_name + ' ' + data.member.last_name;
      document.getElementById('selectedMemberID').textContent = 'ID: ' + data.member.member_number;
      document.getElementById('selectedMemberDisplay').style.display = 'flex';
      document.getElementById('scannerInputSection').style.display = 'none';
      document.getElementById('nonMemberBtn').style.display = 'none';
      document.getElementById('nonMemberBadge').style.display = 'none';
      document.getElementById('memberScanError').style.display = 'none';
      document.getElementById('selectedMemberIdInput').value = data.member.id;
      document.getElementById('isNonMemberInput').value = '0';

      // Show payment method section
      document.getElementById('paymentMethodSection').style.display = 'block';
      setPaymentMethod('cash');

      // Show credit balance if any
      const balance = parseFloat(data.outstanding_credit_balance) || 0;
      currentOutstandingBalance = balance;
      if (balance > 0) {
        document.getElementById('creditBalanceAmount').textContent = 'P' + balance.toFixed(2);
        document.getElementById('creditBalanceSection').style.display = 'block';
      } else {
        document.getElementById('creditBalanceSection').style.display = 'none';
      }
    } else {
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
  document.getElementById('paymentMethodSection').style.display = 'none';
  document.getElementById('creditBalanceSection').style.display = 'none';
  document.getElementById('paymentMethodInput').value = 'cash';
  selectedPaymentMethod = 'cash';
}

function clearMember() {
  currentMember = null;
  isNonMember = false;
  currentOutstandingBalance = 0;

  document.getElementById('selectedMemberDisplay').style.display = 'none';
  document.getElementById('nonMemberBadge').style.display = 'none';
  document.getElementById('scannerInputSection').style.display = 'block';
  document.getElementById('nonMemberBtn').style.display = 'block';
  document.getElementById('memberScanInput').value = '';
  document.getElementById('selectedMemberIdInput').value = '';
  document.getElementById('isNonMemberInput').value = '0';
  document.getElementById('memberScanError').style.display = 'none';
  document.getElementById('paymentMethodSection').style.display = 'none';
  document.getElementById('creditBalanceSection').style.display = 'none';
  document.getElementById('paymentMethodInput').value = 'cash';
  selectedPaymentMethod = 'cash';
}

// Pay credit modal
function openPayCreditModal() {
  if (!currentMember || currentOutstandingBalance <= 0) return;

  const remaining = currentOutstandingBalance;
  const modal = document.createElement('div');
  modal.id = 'payCreditModal';
  modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50';
  modal.innerHTML = `
    <div class="mx-4 w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
      <h3 class="mb-1 text-base font-bold text-slate-900">Pay Credit</h3>
      <p class="mb-4 text-sm text-slate-500">${currentMember.first_name} ${currentMember.last_name} — Outstanding: P${remaining.toFixed(2)}</p>
      <div class="mb-4">
        <label class="mb-1 block text-xs font-semibold text-slate-600">Payment Amount</label>
        <input type="number" id="payCreditAmount" step="0.01" min="0.01" max="${remaining}" value="${remaining.toFixed(2)}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
      </div>
      <div class="flex gap-3">
        <button onclick="document.getElementById('payCreditModal').remove()" class="flex-1 cursor-pointer rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
        <button onclick="submitCreditPayment()" class="flex-1 cursor-pointer rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Record Payment</button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);
}

async function submitCreditPayment() {
  const amount = parseFloat(document.getElementById('payCreditAmount').value);
  if (!amount || amount <= 0) {
    alert('Please enter a valid amount.');
    return;
  }

  if (!currentMember) return;

  try {
    const response = await fetch(`/members/lookup?q=${currentMember.member_number}`);
    const data = await response.json();

    if (data.found && data.outstanding_credit_balance > 0) {
      // We need the credit_id — fetch it from the credits page
      const creditsResponse = await fetch(`/credits?status=unpaid`);
      const html = await creditsResponse.text();
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const creditRows = doc.querySelectorAll('tr[data-member-id="' + currentMember.id + '"]');

      if (creditRows.length > 0) {
        const creditId = creditRows[0].dataset.creditId;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/credits/${creditId}/pay?redirect=pos`;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfToken);

        const amountInput = document.createElement('input');
        amountInput.type = 'hidden';
        amountInput.name = 'amount_paid';
        amountInput.value = amount;
        form.appendChild(amountInput);

        document.body.appendChild(form);
        form.submit();
      } else {
        // Fallback: pay against the first outstanding credit via API
        alert('Could not find the credit record. Please use the Credits page to record this payment.');
      }
    }
  } catch (e) {
    console.error('Pay credit error:', e);
    alert('Error recording payment. Please try again.');
  }
}
</script>
@endsection
