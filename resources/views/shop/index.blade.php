@extends('layouts.app')

@section('title', 'POS')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="pos-container flex min-w-0 flex-col gap-6 xl:h-[calc(100vh-8rem)] xl:flex-row">
    {{-- Products section --}}
    <section class="products-section flex min-h-0 min-w-0 flex-1 flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
        <div class="search-bar mb-4 text-right">
            <div class="relative w-full sm:ml-auto sm:w-72">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                <input
                    type="text"
                    id="search-input"
                    placeholder="Search item or scan barcode..."
                    autocomplete="off"
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
                    @php $stockQty = (int) ($item->quantity ?? 0); @endphp
                    <div class="product-card flex min-w-0 {{ $stockQty <= 0 ? 'cursor-not-allowed opacity-60' : 'cursor-pointer' }} flex-col items-center justify-center overflow-hidden rounded-xl border {{ $stockQty <= 0 ? 'border-slate-200 bg-slate-100' : 'border-slate-200 bg-slate-50 hover:-translate-y-0.5 hover:border-brand-300 hover:bg-brand-50 hover:shadow-md' }} p-3 text-center transition-all" data-category="{{ $item->category ?? 'other' }}" data-id="{{ $item->id }}" data-name="{{ $item->item_name }}" data-barcode="{{ $item->barcode }}" data-manufacturer-barcode="{{ $item->manufacturer_barcode }}" data-stock="{{ $stockQty }}" data-price="{{ $item->price }}">
                        <div class="product-name mb-1 line-clamp-2 text-sm font-semibold text-slate-700">{{ ucwords($item->item_name) }}</div>
                        <div class="product-price text-base font-bold text-brand-700">{{ number_format($item->price, 2) }}</div>
                        @if($stockQty <= 0)
                            <div class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-red-600">Out of stock</div>
                        @elseif($stockQty <= 5)
                            <div class="mt-1 text-[10px] font-medium text-amber-600">Low: {{ $stockQty }} left</div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="no-products col-span-full py-16 text-center text-sm text-slate-400">
                    <x-icon name="cube" class="mb-3 h-9 w-9" />
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
              <x-icon name="user-circle" class="h-5 w-5 text-brand-700" />
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
                <x-icon name="credit-card" class="h-3.5 w-3.5 text-amber-600" />
                <span class="text-xs font-medium text-amber-700">Outstanding Credit: <span id="creditBalanceAmount" class="font-bold">P0.00</span></span>
              </div>
              <button onclick="openPayCreditModal()" class="cursor-pointer rounded bg-amber-600 px-2.5 py-1 text-xs font-semibold text-white transition-colors hover:bg-amber-700">Pay</button>
            </div>
          </div>

          <!-- Scanner input -->
          <div id="scannerInputSection">
            <label for="memberScanInput" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
              <x-icon name="qrcode" class="h-3 w-3" /> Scan Member QR / Barcode
            </label>
            <div class="flex gap-2">
              <input
                type="text"
                id="memberScanInput"
                placeholder="Scan or type member ID..."
                class="flex-1 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                onkeydown="if(event.key==='Enter') routeScan(this.value)"
              />
              <button onclick="routeScan(document.getElementById('memberScanInput').value, false, true)" class="rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                <x-icon name="search" class="h-3.5 w-3.5" />
              </button>
            </div>
            <div id="memberScanError" style="display: none;" class="mt-1.5 text-xs text-red-600">Member not found.</div>
          </div>

          <!-- Non-member toggle -->
          <div class="mt-3 text-center">
            <button onclick="setNonMember()" id="nonMemberBtn" class="inline-block cursor-pointer rounded-full border border-slate-300 bg-white px-4 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50">
              Continue as Non-Member
            </button>
            <div id="nonMemberBadge" style="display: none; align-items: center; justify-content: space-between;" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
              <span><x-icon name="user-o" class="h-3 w-3" /> Non-Member</span>
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
              <x-icon name="money" class="mr-1 h-3.5 w-3.5" /> Cash (F11)
            </button>
            <button onclick="setPaymentMethod('credit')" id="btnCredit" class="flex-1 cursor-pointer rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition-colors hover:border-brand-300 hover:text-brand-600">
              <x-icon name="credit-card" class="mr-1 h-3.5 w-3.5" /> Credit (F12)
            </button>
          </div>
        </div>

        <div class="total mt-4 -mx-5 shrink-0 border-t border-slate-100 px-5 py-3 text-right text-lg font-bold text-slate-900">TOTAL P0.00</div>
        <div class="checkout-actions mt-1 flex shrink-0 gap-3">
            <button class="btn-cancel flex-1 cursor-pointer rounded-lg border border-red-200 bg-white px-4 py-3 text-sm font-semibold text-red-600 transition-colors hover:bg-red-50" onclick="cancelCheckout()">CANCEL</button>
            <button class="btn-pay flex-1 cursor-pointer rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-slate-300" onclick="openCashPayment()">PAY (P0.00)</button>
        </div>
        <div class="mt-1 text-center text-[10px] text-slate-400">Scan items - Enter = Pay - F11 Cash - F12 Credit (members)</div>

        <!-- Hidden inputs for member tracking -->
        <input type="hidden" id="selectedMemberIdInput" name="member_id" value="">
        <input type="hidden" id="isNonMemberInput" name="is_non_member" value="0">
        <input type="hidden" id="paymentMethodInput" name="payment_method" value="cash">
    </aside>
</div>

<div id="cashPaymentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="cashPaymentTitle" tabindex="-1">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" role="document">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <h3 id="cashPaymentTitle" class="text-lg font-bold text-slate-900">Cash Payment</h3>
                <p class="mt-1 text-sm text-slate-500">Enter the amount received from the customer</p>
            </div>
            <button type="button" onclick="closeCashPayment()" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600" aria-label="Close">&times;</button>
        </div>

        <div class="mb-4 rounded-lg bg-slate-50 p-4 text-center">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Amount Due</div>
            <div id="cashDueAmount" class="mt-1 text-3xl font-bold text-slate-900">P0.00</div>
        </div>

        <div class="mb-3">
            <label for="cashReceivedInput" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Amount Received</label>
            <input type="number" id="cashReceivedInput" inputmode="decimal" step="0.01" min="0" placeholder="0.00" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30" oninput="updateCashChange()">
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <button type="button" onclick="setCashAmount('exact')" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50">Exact</button>
            <button type="button" onclick="setCashAmount(50)" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50">50</button>
            <button type="button" onclick="setCashAmount(100)" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50">100</button>
            <button type="button" onclick="setCashAmount(200)" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50">200</button>
            <button type="button" onclick="setCashAmount(500)" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50">500</button>
            <button type="button" onclick="setCashAmount(1000)" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50">1000</button>
        </div>

        <div class="mb-5 rounded-lg border border-slate-200 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Change</div>
            <div id="cashChangeDisplay" class="mt-1 text-2xl font-bold text-slate-900">P0.00</div>
            <div id="cashShortDisplay" class="mt-1 hidden text-sm font-semibold text-red-600"></div>
        </div>

        <div class="flex gap-3">
            <button type="button" onclick="closeCashPayment()" class="flex-1 cursor-pointer rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50">Cancel</button>
            <button id="cashConfirmBtn" type="button" onclick="confirmCashPayment()" class="flex-1 cursor-pointer rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-slate-300">Confirm Payment</button>
        </div>
    </div>
</div>

<!-- Credit Payment Confirmation Modal -->
<div id="creditPaymentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="creditPaymentTitle" tabindex="-1">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" role="document">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <h3 id="creditPaymentTitle" class="text-lg font-bold text-slate-900">Credit Payment</h3>
                <p class="mt-1 text-sm text-slate-500">Confirm credit charge</p>
            </div>
            <button type="button" onclick="closeCreditPayment()" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600" aria-label="Close">&times;</button>
        </div>

        <div class="mb-4 rounded-lg bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Member</div>
            <div id="creditMemberName" class="mt-1 text-base font-semibold text-slate-900">-</div>
            <div id="creditMemberId" class="text-xs text-slate-500">-</div>
        </div>

        <div class="mb-4 rounded-lg bg-slate-50 p-4 text-center">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Amount Due</div>
            <div id="creditDueAmount" class="mt-1 text-3xl font-bold text-slate-900">P0.00</div>
        </div>

        <div class="mb-4 rounded-lg border border-slate-200 px-4 py-3">
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600">Credit/Balance</span>
                <span id="creditAvailable" class="font-semibold text-slate-900">P0.00</span>
            </div>
            <div id="creditModalError" class="mt-2 hidden text-sm font-semibold text-red-600"></div>
        </div>

        <div class="mb-4 text-center text-xs text-slate-500">Press Enter to confirm - Esc to cancel</div>

        <div class="flex gap-3">
            <button type="button" onclick="closeCreditPayment()" class="flex-1 cursor-pointer rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50">Cancel</button>
            <button id="creditConfirmBtn" type="button" onclick="confirmCreditPayment()" class="flex-1 cursor-pointer rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-slate-300">Confirm Payment</button>
        </div>
    </div>
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

// Barcode scanners type much faster than a person. Keystrokes closer together
// than this are treated as a scan burst and kept away from the product filter.
const SCAN_KEY_GAP = 50;
const SEARCH_DEBOUNCE = 150;
let lastSearchKeyAt = 0;
let scannerBurst = false;
let burstResetTimer = null;
let searchFilterTimer = null;

    document.getElementById('cashPaymentModal').addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            closeCashPayment();
            return;
        }
        if (event.key === 'Enter') {
            if (event.repeat) {
                event.preventDefault();
                event.stopPropagation();
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            if (!document.getElementById('cashConfirmBtn').disabled) {
                confirmCashPayment();
            }
            return;
        }
    });

    document.getElementById('creditPaymentModal').addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            closeCreditPayment();
            return;
        }
        if (event.key === 'Enter') {
            if (event.repeat) {
                event.preventDefault();
                event.stopPropagation();
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            const btn = document.getElementById('creditConfirmBtn');
            if (btn && !btn.disabled) {
                confirmCreditPayment();
            }
            return;
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            const cashModal = document.getElementById('cashPaymentModal');
            if (cashModal && !cashModal.classList.contains('hidden')) {
                event.preventDefault();
                event.stopPropagation();
                closeCashPayment();
                return;
            }
            const creditModal = document.getElementById('creditPaymentModal');
            if (creditModal && !creditModal.classList.contains('hidden')) {
                event.preventDefault();
                event.stopPropagation();
                closeCreditPayment();
                return;
            }
        }
    });

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');

    searchInput.addEventListener('input', function() {
        const now = performance.now();
        const gap = now - lastSearchKeyAt;
        lastSearchKeyAt = now;

        if (gap < SCAN_KEY_GAP) {
            scannerBurst = true;
            clearTimeout(burstResetTimer);
            burstResetTimer = setTimeout(() => {
                scannerBurst = false;
                if (searchInput.value.trim() !== '') applyProductFilter();
            }, 500);
        }

        clearTimeout(searchFilterTimer);

        // Do not filter while a scanner is mid-burst; the value is a code,
        // not a search phrase, and filtering it makes member IDs flash.
        if (scannerBurst) return;

        searchFilterTimer = setTimeout(applyProductFilter, SEARCH_DEBOUNCE);
    });

    searchInput.addEventListener('keydown', function(event) {
        if (event.key !== 'Enter') return;

        event.preventDefault();
        event.stopPropagation();
        clearTimeout(searchFilterTimer);

        const value = this.value.trim();
        if (!value) return;

        const fromScanner = scannerBurst && value.length >= 4;
        scannerBurst = false;
        clearTimeout(burstResetTimer);

        routeScan(value, fromScanner);
    });

    // Category filtering
    document.querySelectorAll('.category').forEach(cat => {
        cat.addEventListener('click', function() {
            document.querySelectorAll('.category').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            applyProductFilter();
        });
    });

    // Product click to add to cart
    document.querySelectorAll('.product-card').forEach(product => {
        product.addEventListener('click', function() {
        const stock = parseInt(this.dataset.stock || '0', 10);
        if (isNaN(stock) || stock <= 0) {
            const name = this.dataset.name;
            showToast(name + ' is out of stock', 'warning');
            return;
        }
        const id = parseInt(this.dataset.id, 10);
        const name = this.dataset.name;
        const price = parseFloat(this.dataset.price !== undefined ? this.dataset.price : (this.querySelector('.product-price').textContent.replace(/,/g, '')));
        addToCart(id, name, price);
        });
    });

});

function isAnyModalOpen() {
    const cashModal = document.getElementById('cashPaymentModal');
    if (cashModal && !cashModal.classList.contains('hidden')) {
        return true;
    }
    const creditModal = document.getElementById('creditPaymentModal');
    if (creditModal && !creditModal.classList.contains('hidden')) {
        return true;
    }
    return false;
}

function isCheckoutProcessing() {
    const payBtn = document.querySelector('.btn-pay');
    if (!payBtn) return false;
    return payBtn.disabled && payBtn.textContent.trim().toLowerCase() === 'processing...';
}

let posShortcutIgnoreUntil = 0;
let posEnterIgnoreUntil = 0;

function triggerCashPaymentShortcut() {
    const now = Date.now();
    if (now < posShortcutIgnoreUntil) {
        return;
    }
    if (cart.length === 0) {
        showToast('Cart is empty', 'warning');
        return;
    }
    if (isCheckoutProcessing()) {
        showToast('Checkout is processing', 'warning');
        return;
    }
    if (isAnyModalOpen()) {
        return;
    }

    setPaymentMethod('cash');
    document.getElementById('paymentMethodInput').value = 'cash';
    selectedPaymentMethod = 'cash';
    if (document.activeElement && document.activeElement.tagName === 'INPUT') {
        try { document.activeElement.blur(); } catch (e) {}
    }
    posShortcutIgnoreUntil = Date.now() + 350;
}

function triggerCreditPaymentShortcut() {
    const now = Date.now();
    if (now < posShortcutIgnoreUntil) {
        return;
    }
    if (cart.length === 0) {
        showToast('Cart is empty', 'warning');
        return;
    }
    if (isCheckoutProcessing()) {
        showToast('Checkout is processing', 'warning');
        return;
    }
    if (isAnyModalOpen()) {
        return;
    }

    if (!currentMember || isNonMember) {
        showToast('Credit is for members only. Scan a member card first.', 'error');
        return;
    }

    setPaymentMethod('credit');
    document.getElementById('paymentMethodInput').value = 'credit';
    selectedPaymentMethod = 'credit';
    if (document.activeElement && document.activeElement.tagName === 'INPUT') {
        try { document.activeElement.blur(); } catch (e) {}
    }
    posShortcutIgnoreUntil = Date.now() + 350;
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'F11') {
        event.preventDefault();
        event.stopPropagation();
        if (event.repeat) return;
        triggerCashPaymentShortcut();
        return;
    }
    if (event.key === 'F12') {
        event.preventDefault();
        event.stopPropagation();
        if (event.repeat) return;
        triggerCreditPaymentShortcut();
        return;
    }

    if (event.key === 'Enter') {
        if (event.repeat) return;
        const target = event.target;
        if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA')) {
            if (target.id === 'memberScanInput') {
                return;
            }
            if (target.id === 'search-input') {
                const val = target.value.trim();
                if (val !== '') {
                    return;
                }
            } else {
                return;
            }
        }
        if (isAnyModalOpen()) {
            return;
        }
        const searchInput = document.getElementById('search-input');
        if (searchInput && document.activeElement === searchInput && searchInput.value.trim() !== '') {
            return;
        }
        const memberInput = document.getElementById('memberScanInput');
        if (memberInput && document.activeElement === memberInput && memberInput.value.trim() !== '') {
            return;
        }
        if (Date.now() - lastItemAddedAt < 400) {
            return;
        }
        if (Date.now() < posEnterIgnoreUntil) {
            return;
        }
        if (cart.length === 0) {
            showToast('Cart is empty', 'warning');
            return;
        }
        if (isCheckoutProcessing()) {
            showToast('Checkout is processing', 'warning');
            return;
        }
        const paymentMethod = document.getElementById('paymentMethodInput').value === 'credit' ? 'credit' : 'cash';
        if (paymentMethod === 'cash') {
            event.preventDefault();
            event.stopPropagation();
            setPaymentMethod('cash');
            document.getElementById('paymentMethodInput').value = 'cash';
            selectedPaymentMethod = 'cash';
            openCashPayment();
            posEnterIgnoreUntil = Date.now() + 300;
            return;
        }
        if (paymentMethod === 'credit') {
            event.preventDefault();
            event.stopPropagation();
            if (!currentMember || isNonMember) {
                showToast('Credit is for members only. Scan a member card first.', 'error');
                return;
            }
            setPaymentMethod('credit');
            document.getElementById('paymentMethodInput').value = 'credit';
            selectedPaymentMethod = 'credit';
            openCreditPayment();
            posEnterIgnoreUntil = Date.now() + 300;
            return;
        }
    }
}, true);

function applyProductFilter() {
    const searchInput = document.getElementById('search-input');
    if (!searchInput) return;

    const searchTerm = searchInput.value.trim().toLowerCase();
    const activeCategory = document.querySelector('.category.active')?.dataset.category || 'all';

    document.querySelectorAll('.product-card').forEach(product => {
        const productName = (product.dataset.name || '').toLowerCase();
        const barcode = (product.dataset.barcode || '').toLowerCase();
        const manufacturerBarcode = (product.dataset.manufacturerBarcode || '').toLowerCase();
        const category = product.dataset.category;

        const matchesSearch = searchTerm === ''
            || productName.includes(searchTerm)
            || barcode.includes(searchTerm)
            || manufacturerBarcode.includes(searchTerm);
        const matchesCategory = activeCategory === 'all' || category === activeCategory;

        product.style.display = (matchesSearch && matchesCategory) ? 'block' : 'none';
    });
}

function resetProductFilter() {
    const searchInput = document.getElementById('search-input');
    if (searchInput) searchInput.value = '';

    clearTimeout(searchFilterTimer);
    applyProductFilter();
}

function focusSearch() {
    const input = document.getElementById('search-input');
    if (input) input.focus();
}

// Cart lines are keyed by item id, so two products that share a name never
// collide. `name` is kept purely for display.
function getItemStock(id) {
    const card = document.querySelector('.product-card[data-id="' + id + '"]');
    if (!card) return null;
    const stock = parseInt(card.dataset.stock || '0', 10);
    if (isNaN(stock)) return null;
    return stock;
}

function updateProductCardStock(id, stock) {
    const card = document.querySelector('.product-card[data-id="' + id + '"]');
    if (!card) return;
    card.dataset.stock = stock;
    if (parseInt(stock, 10) <= 0) {
        card.classList.remove('cursor-pointer', 'hover:-translate-y-0.5', 'hover:border-brand-300', 'hover:bg-brand-50', 'hover:shadow-md');
        card.classList.add('cursor-not-allowed', 'opacity-60');
        card.classList.remove('bg-slate-50');
        card.classList.add('bg-slate-100');
        const existingBadge = card.querySelector('.stock-badge');
        if (existingBadge) existingBadge.remove();
        const badge = document.createElement('div');
        badge.className = 'stock-badge mt-1 text-[10px] font-semibold uppercase tracking-wide text-red-600';
        badge.textContent = 'Out of stock';
        card.appendChild(badge);
    } else {
        card.classList.remove('cursor-not-allowed', 'opacity-60');
        card.classList.add('cursor-pointer', 'hover:-translate-y-0.5', 'hover:border-brand-300', 'hover:bg-brand-50', 'hover:shadow-md');
        card.classList.remove('bg-slate-100');
        card.classList.add('bg-slate-50');
        const existingBadge = card.querySelector('.stock-badge');
        if (existingBadge) existingBadge.remove();
        if (parseInt(stock, 10) <= 5) {
            const badge = document.createElement('div');
            badge.className = 'stock-badge mt-1 text-[10px] font-medium text-amber-600';
            badge.textContent = 'Low: ' + stock + ' left';
            card.appendChild(badge);
        }
    }
}

function addToCart(id, name, price) {
    const stock = getItemStock(id);
    if (stock !== null && stock <= 0) {
        showToast(name + ' is out of stock', 'warning');
        return;
    }
    const existingItem = cart.find(item => item.id === id);
    if (existingItem) {
        if (stock !== null && existingItem.quantity + 1 > stock) {
            showToast('Only ' + stock + ' ' + name + ' left in stock', 'warning');
            updateCart();
            return;
        }
        existingItem.quantity++;
    } else {
        if (stock !== null && stock < 1) {
            showToast(name + ' is out of stock', 'warning');
            return;
        }
        cart.push({ id, name, price, quantity: 1 });
    }
    updateCart();
    lastItemAddedAt = Date.now();
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.value = '';
        searchInput.blur();
    }
    if (document.activeElement && document.activeElement.tagName === 'INPUT') {
        try { document.activeElement.blur(); } catch (e) {}
    }
}

// Look up a scanned product barcode and add it to the cart.
// Returns true when something was added, so the member scanner can fall back
// to treating an unmatched member code as a product code.
async function scanItem(code) {
    const value = (code || '').trim();
    if (!value) return false;

    try {
        const response = await fetch(`/items/lookup?code=${encodeURIComponent(value)}`);
        const data = await response.json();

    if (data.found) {
        const stock = data.item.quantity !== undefined && data.item.quantity !== null ? parseInt(data.item.quantity, 10) : null;
        if (stock !== null && !isNaN(stock)) {
            updateProductCardStock(data.item.id, stock);
        }
        if (stock !== null && !isNaN(stock) && stock <= 0) {
            showToast(data.item.display_name + ' is out of stock', 'warning');
            return true;
        }
        addToCart(data.item.id, data.item.display_name, data.item.price);
        const si = document.getElementById('search-input');
        if (si) {
            si.value = '';
            si.blur();
        }
        return true;
    }

    return false;
    } catch (e) {
        console.error('Item scan error:', e);
        return false;
    }
}

let scanBuffer = '';
let lastItemAddedAt = 0;

document.addEventListener('focusin', function () {
    scanBuffer = '';
});

document.addEventListener('keydown', function (event) {
    const target = event.target;
    if (target !== document.body && target !== document.documentElement) return;
    if (event.ctrlKey || event.altKey || event.metaKey) return;

    if (event.key === 'Enter') {
        event.preventDefault();
        const value = scanBuffer.trim();
        scanBuffer = '';
        if (value) {
            routeScan(value, true);
        }
        return;
    }

    if (event.key.length === 1 && !event.repeat) {
        scanBuffer += event.key;
        event.preventDefault();
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.value = scanBuffer;
            searchInput.focus();
        }
    }
}, true);

async function routeScan(code, fromScanner = false, fromMemberInput = false) {
    const value = (code || '').trim();
    if (!value) return false;

    const searchInput = document.getElementById('search-input');
    const memberInput = document.getElementById('memberScanInput');
    const memberError = document.getElementById('memberScanError');
    const cameFromMemberInput = fromMemberInput
        || (memberInput !== null && document.activeElement === memberInput);

    if (searchInput) searchInput.value = value;
    if (memberInput) memberInput.value = value;
    if (memberError) memberError.style.display = 'none';

    const resetScans = () => {
        if (searchInput) {
            searchInput.value = '';
            searchInput.blur();
        }
        if (memberInput) memberInput.value = '';
    };

    // a. Member card / ID wins so a member number never enters the product grid.
    // Scans from the product search must be exact: the dedicated member box
    // still allows the old name/partial lookup.
    if (await lookupMember(value, !cameFromMemberInput)) {
        resetScans();
        resetProductFilter();
        focusSearch();
        return true;
    }

    // b. Exact product barcode.
    if (await scanItem(value)) {
        resetScans();
        resetProductFilter();
        focusSearch();
        return true;
    }

    // c. Unknown scan: toast and clear. Manual Enter stays a normal search.
    if (cameFromMemberInput) {
        if (memberError) memberError.style.display = 'block';
        resetScans();
        resetProductFilter();
    } else if (fromScanner) {
        showToast('Item/Member not found', 'warning');
        resetScans();
        resetProductFilter();
    } else {
        if (memberInput) memberInput.value = '';
        if (searchInput) searchInput.value = value;
        applyProductFilter();
    }

    focusSearch();
    return false;
}

function updateCart() {
    const cartContainer = document.querySelector('.checkout-items');
    if(cart.length === 0) {
        cartContainer.innerHTML = '<div class="empty-cart py-8 text-center text-sm text-slate-400">No items in cart</div>';
        total = 0;
    } else {
        cartContainer.innerHTML = cart.map((item, index) => {
            const stock = getItemStock(item.id);
            const canIncrease = stock === null ? true : item.quantity < stock;
            return `
            <div class="checkout-item flex items-center justify-between gap-2 border-b border-slate-100 py-2.5 last:border-0">
                <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700">${item.name}</span>
                <div class="qty-control flex shrink-0 items-center gap-2">
                    <button onclick="updateQuantity(${index}, -1)" class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-bold text-slate-600 transition-colors hover:bg-slate-50">-</button>
                    <span class="min-w-5 text-center text-sm font-semibold text-slate-900">${item.quantity}</span>
                    <button onclick="updateQuantity(${index}, 1)" ${!canIncrease ? 'disabled' : ''} title="${!canIncrease ? 'No more stock' : ''}" class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-bold text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">+</button>
                </div>
                <span class="shrink-0 text-sm font-semibold text-slate-900">P${(item.price * item.quantity).toFixed(2)}</span>
            </div>
        `;
        }).join('');
        total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    }
    
    document.querySelector('.total').textContent = `TOTAL P${total.toFixed(2)}`;
    document.querySelector('.btn-pay').textContent = `PAY (P${total.toFixed(2)})`;
}

function updateQuantity(index, change) {
    const item = cart[index];
    if (!item) return;
    if (change > 0) {
        const stock = getItemStock(item.id);
        if (stock !== null && item.quantity + 1 > stock) {
            showToast('Only ' + stock + ' ' + item.name + ' left in stock', 'warning');
            updateCart();
            return;
        }
    }
    item.quantity += change;
    if (item.quantity <= 0) {
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

let cashModalLastFocus = null;

function toCents(value) {
    if (value === null || value === undefined) return 0;
    const str = String(value).replace(/[^0-9.\-]/g, '');
    if (str === '' || str === '-' || str === '.') return 0;
    const num = Number(str);
    if (isNaN(num)) return 0;
    return Math.round((num + Number.EPSILON) * 100);
}

function fromCents(cents) {
    const c = Math.round(Number(cents) || 0);
    return c / 100;
}

function moneyPFromCents(cents) {
    const c = Math.round(Number(cents) || 0);
    const pesos = c / 100;
    return 'P' + pesos.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function moneyP(value) {
    if (typeof value === 'string') {
        const s = value.trim();
        if (s.indexOf('.') === -1 && /^-?\d+$/.test(s) && s.length > 4) {
            const c = parseInt(s, 10);
            const pesos = Math.round(c) / 100;
            return 'P' + pesos.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }
    const cents = toCents(value);
    const pesos = Math.round(cents) / 100;
    return 'P' + pesos.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function pesosToString(value) {
    return (Math.round(toCents(value)) / 100).toFixed(2);
}

function openCashPayment() {
    if (cart.length === 0) {
        showToast('Cart is empty', 'warning');
        return;
    }

    if (!currentMember && !isNonMember) {
        isNonMember = true;
        selectedPaymentMethod = 'cash';
        document.getElementById('selectedMemberIdInput').value = '';
        document.getElementById('isNonMemberInput').value = '1';
        document.getElementById('paymentMethodInput').value = 'cash';
    } else if (!isNonMember && currentMember) {
        selectedPaymentMethod = 'cash';
        document.getElementById('paymentMethodInput').value = 'cash';
    } else {
        selectedPaymentMethod = 'cash';
        document.getElementById('paymentMethodInput').value = 'cash';
    }

    cashModalLastFocus = document.querySelector('.btn-pay');
    document.getElementById('cashDueAmount').textContent = moneyP(total);
    const input = document.getElementById('cashReceivedInput');
    input.value = '';
    document.getElementById('cashChangeDisplay').textContent = moneyPFromCents(0);
    document.getElementById('cashChangeDisplay').className = 'mt-1 text-2xl font-bold text-slate-900';
    document.getElementById('cashShortDisplay').classList.add('hidden');
    document.getElementById('cashShortDisplay').textContent = '';
    const btn = document.getElementById('cashConfirmBtn');
    btn.disabled = true;
    btn.textContent = 'Confirm Payment';

    const modal = document.getElementById('cashPaymentModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    window.setTimeout(() => {
        input.focus();
        input.select();
    }, 50);
}

function closeCashPayment() {
    const modal = document.getElementById('cashPaymentModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    if (cashModalLastFocus && typeof cashModalLastFocus.focus === 'function') {
        cashModalLastFocus.focus();
    } else {
        focusSearch();
    }
}

let creditModalLastFocus = null;

function openCreditPayment() {
    if (cart.length === 0) {
        showToast('Cart is empty', 'warning');
        return;
    }
    if (isCheckoutProcessing()) {
        showToast('Checkout is processing', 'warning');
        return;
    }
    if (isAnyModalOpen()) {
        return;
    }

    if (!currentMember || isNonMember) {
        showToast('Credit is for members only. Scan a member card first.', 'error');
        return;
    }

    creditModalLastFocus = document.querySelector('.btn-pay');

    document.getElementById('creditMemberName').textContent = currentMember.first_name + ' ' + currentMember.last_name;
    document.getElementById('creditMemberId').textContent = 'ID: ' + (currentMember.member_number || currentMember.id || '-');
    document.getElementById('creditDueAmount').textContent = moneyP(total);
    const available = parseFloat(currentOutstandingBalance) || 0;
    document.getElementById('creditAvailable').textContent = moneyP(available);
    const errEl = document.getElementById('creditModalError');
    errEl.classList.add('hidden');
    errEl.textContent = '';
    const btn = document.getElementById('creditConfirmBtn');
    btn.disabled = available < total - 0.00001 ? true : false;
    btn.textContent = 'Confirm Payment';
    if (available < total - 0.00001) {
        errEl.textContent = 'Insufficient credit. Please select another payment method.';
        errEl.classList.remove('hidden');
    }

    const modal = document.getElementById('creditPaymentModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    window.setTimeout(() => {
        btn.focus();
    }, 50);
}

function closeCreditPayment() {
    const modal = document.getElementById('creditPaymentModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    if (creditModalLastFocus && typeof creditModalLastFocus.focus === 'function') {
        creditModalLastFocus.focus();
    } else {
        focusSearch();
    }
}

async function confirmCreditPayment() {
    if (cart.length === 0) {
        showToast('Cart is empty', 'warning');
        closeCreditPayment();
        return;
    }
    if (isCheckoutProcessing()) {
        return;
    }
    if (!currentMember || isNonMember) {
        showToast('Credit is for members only. Scan a member card first.', 'error');
        closeCreditPayment();
        return;
    }
    const available = parseFloat(currentOutstandingBalance) || 0;
    if (available < total - 0.00001) {
        const errEl = document.getElementById('creditModalError');
        errEl.textContent = 'Insufficient credit. Please select another payment method.';
        errEl.classList.remove('hidden');
        document.getElementById('creditConfirmBtn').disabled = true;
        return;
    }

    const btn = document.getElementById('creditConfirmBtn');
    btn.disabled = true;
    btn.textContent = 'Processing...';

    setPaymentMethod('credit');
    document.getElementById('paymentMethodInput').value = 'credit';
    selectedPaymentMethod = 'credit';

    await processPayment({ closeDialog: false });
    closeCreditPayment();
}

function setCashAmount(val) {
    const input = document.getElementById('cashReceivedInput');
    if (val === 'exact') {
        input.value = (Math.round(toCents(total)) / 100).toFixed(2);
    } else {
        input.value = (Math.round(toCents(val)) / 100).toFixed(2);
    }
    updateCashChange();
    input.focus();
}

function updateCashChange() {
    const input = document.getElementById('cashReceivedInput');
    const changeEl = document.getElementById('cashChangeDisplay');
    const shortEl = document.getElementById('cashShortDisplay');
    const btn = document.getElementById('cashConfirmBtn');

    const totalC = toCents(total);
    const rawReceived = input.value;
    if (rawReceived === '' || rawReceived === null) {
        changeEl.textContent = moneyPFromCents(0);
        changeEl.className = 'mt-1 text-2xl font-bold text-slate-900';
        shortEl.classList.add('hidden');
        shortEl.textContent = '';
        btn.disabled = true;
        return;
    }

    const receivedC = toCents(rawReceived);
    if (receivedC < 0) {
        changeEl.textContent = moneyPFromCents(0);
        changeEl.className = 'mt-1 text-2xl font-bold text-slate-900';
        shortEl.classList.add('hidden');
        shortEl.textContent = '';
        btn.disabled = true;
        return;
    }

    if (receivedC >= totalC) {
        const changeC = receivedC - totalC;
        changeEl.textContent = moneyPFromCents(changeC);
        changeEl.className = 'mt-1 text-2xl font-bold text-emerald-700';
        shortEl.classList.add('hidden');
        shortEl.textContent = '';
        btn.disabled = false;
    } else {
        const shortC = totalC - receivedC;
        changeEl.textContent = moneyPFromCents(0);
        changeEl.className = 'mt-1 text-2xl font-bold text-slate-900';
        shortEl.textContent = 'Short by ' + moneyPFromCents(shortC);
        shortEl.classList.remove('hidden');
        btn.disabled = true;
    }
}

async function confirmCashPayment() {
    const input = document.getElementById('cashReceivedInput');
    const btn = document.getElementById('cashConfirmBtn');
    const totalC = toCents(total);
    const receivedC = toCents(input.value);

    if (receivedC < totalC) {
        updateCashChange();
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Processing...';

    await processPayment({ amountReceived: (receivedC / 100), closeDialog: true });
}

async function processPayment(options) {
    options = options || {};
    if (cart.length === 0) {
        showToast('Your cart is empty!', 'warning');
        return;
    }

    if (!currentMember && !isNonMember) {
        isNonMember = true;
        selectedPaymentMethod = 'cash';
        document.getElementById('selectedMemberIdInput').value = '';
        document.getElementById('isNonMemberInput').value = '1';
        document.getElementById('paymentMethodInput').value = 'cash';
    } else if (!isNonMember && currentMember) {
        selectedPaymentMethod = document.getElementById('paymentMethodInput').value === 'credit' ? 'credit' : 'cash';
    } else {
        selectedPaymentMethod = 'cash';
        document.getElementById('paymentMethodInput').value = 'cash';
    }

    const payButton = document.querySelector('.btn-pay');
    payButton.disabled = true;
    payButton.textContent = 'Processing...';
    let paymentSucceeded = false;

    const paymentMethod = document.getElementById('paymentMethodInput').value === 'credit' ? 'credit' : 'cash';

    try {
    const body = {
        items: cart.map(item => ({ id: item.id, name: item.name, quantity: item.quantity })),
        member_id: document.getElementById('selectedMemberIdInput').value,
        is_non_member: document.getElementById('isNonMemberInput').value,
        payment_method: paymentMethod
    };

    if (paymentMethod === 'cash' && options.amountReceived !== undefined && options.amountReceived !== null) {
        body.amount_received = toCents(options.amountReceived) / 100;
    }

        const response = await fetch('/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(body)
        });

        const result = await response.json();

        if (result.success) {
            paymentSucceeded = true;

            if (options.closeDialog) {
                closeCashPayment();
            }

            const paidAmount = toCents(result.total !== undefined ? result.total : total) / 100;
            const paidMethod = result.payment_method === 'credit' ? 'Credit' : 'Cash';
            const customerName = currentMember
                ? `${currentMember.first_name} ${currentMember.last_name}`
                : 'Non-Member';

            cart = [];
            total = 0;
            selectedPaymentMethod = 'cash';
            document.getElementById('paymentMethodInput').value = 'cash';
            const btnCash = document.getElementById('btnCash');
            const btnCredit = document.getElementById('btnCredit');
            if (btnCash) btnCash.className = 'flex-1 cursor-pointer rounded-lg border-2 border-brand-600 bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700 transition-colors';
            if (btnCredit) btnCredit.className = 'flex-1 cursor-pointer rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition-colors hover:border-brand-300 hover:text-brand-600';
            if (result.items && Array.isArray(result.items)) {
                result.items.forEach(function(it) {
                    if (it && it.id !== undefined && it.quantity !== undefined && it.quantity !== null) {
                        updateProductCardStock(it.id, it.quantity);
                    }
                });
            }
            cart = [];
            total = 0;
            updateCart();
            clearMember(false);
            const si = document.getElementById('search-input');
            if (si) {
                si.value = '';
                si.blur();
            }
            if (document.activeElement && document.activeElement.tagName === 'INPUT') {
                try { document.activeElement.blur(); } catch (e) {}
            }

            if (paidMethod === 'Cash' && result.amount_received !== null && result.amount_received !== undefined && result.change_amount !== null && result.change_amount !== undefined) {
            showToast(
                `Total ${moneyPFromCents(toCents(paidAmount))} • Received ${moneyPFromCents(toCents(result.amount_received))} • Change ${moneyPFromCents(toCents(result.change_amount))}`,
                'success',
                { title: 'Payment Successful', duration: 4000 }
            );
            } else {
                showToast(
                    `${moneyP(paidAmount)} \u2022 ${paidMethod} \u2022 ${customerName}`,
                    'success',
                    { title: 'Payment Successful' }
                );
            }
        } else {
            showToast('Payment failed: ' + result.message, 'error');
        }
    } catch (error) {
        showToast('Error processing payment: ' + error.message, 'error');
    } finally {
        if (paymentSucceeded) {
            payButton.disabled = false;
            if (document.getElementById('paymentMethodInput').value === 'credit') {
                payButton.textContent = `CHARGE TO CREDIT (P${total.toFixed(2)})`;
            } else {
                payButton.textContent = `PAY (P${total.toFixed(2)})`;
            }
        } else {
            if (document.getElementById('paymentMethodInput').value === 'credit') {
                payButton.textContent = `CHARGE TO CREDIT (P${total.toFixed(2)})`;
            } else {
                payButton.textContent = `PAY (P${total.toFixed(2)})`;
            }
            payButton.disabled = false;
            const si = document.getElementById('search-input');
            if (si) {
                si.value = '';
                si.blur();
            }
            if (document.activeElement && document.activeElement.tagName === 'INPUT') {
                try { document.activeElement.blur(); } catch (e) {}
            }
        }

        const cashBtn = document.getElementById('cashConfirmBtn');
        if (cashBtn) {
            if (paymentSucceeded) {
                cashBtn.disabled = false;
                cashBtn.textContent = 'Confirm Payment';
            } else {
                cashBtn.disabled = false;
                cashBtn.textContent = 'Confirm Payment';
                updateCashChange();
            }
        }

        const creditBtn = document.getElementById('creditConfirmBtn');
        if (creditBtn) {
        if (paymentSucceeded) {
            creditBtn.disabled = false;
            creditBtn.textContent = 'Confirm Payment';
        } else {
            creditBtn.disabled = false;
            creditBtn.textContent = 'Confirm Payment';
            const errEl = document.getElementById('creditModalError');
            if (errEl) {
                errEl.classList.add('hidden');
                errEl.textContent = '';
            }
        }
        }
    }
}

// Member lookup functions
async function lookupMember(queryOverride, exact = false) {
  const memberInput = document.getElementById('memberScanInput');
  const query = (queryOverride !== undefined ? queryOverride : (memberInput ? memberInput.value : '')).trim();
  if (!query) return false;

  try {
    const params = new URLSearchParams({ q: query });
    if (exact) params.set('exact', '1');

    const response = await fetch(`/members/lookup?${params.toString()}`);
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

      focusSearch();
      return true;
    }

    return false;
  } catch (e) {
    console.error('Lookup error:', e);
    return false;
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
  focusSearch();
}

function clearMember(focus = true) {
  currentMember = null;
  isNonMember = false;
  currentOutstandingBalance = 0;

  document.getElementById('selectedMemberDisplay').style.display = 'none';
  document.getElementById('nonMemberBadge').style.display = 'none';
  document.getElementById('scannerInputSection').style.display = 'block';
  document.getElementById('nonMemberBtn').style.display = '';
  document.getElementById('memberScanInput').value = '';
  document.getElementById('selectedMemberIdInput').value = '';
  document.getElementById('isNonMemberInput').value = '0';
  document.getElementById('paymentMethodSection').style.display = 'none';
  document.getElementById('creditBalanceSection').style.display = 'none';
  document.getElementById('paymentMethodInput').value = 'cash';
  selectedPaymentMethod = 'cash';
  if (focus) {
    focusSearch();
  }
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
        <button onclick="closePayCreditModal()" class="flex-1 cursor-pointer rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
        <button onclick="submitCreditPayment()" class="flex-1 cursor-pointer rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Record Payment</button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);
}

function closePayCreditModal() {
  const modal = document.getElementById('payCreditModal');
  if (modal) modal.remove();
  focusSearch();
}

async function submitCreditPayment() {
  const amount = parseFloat(document.getElementById('payCreditAmount').value);
  if (!amount || amount <= 0) {
    showToast('Please enter a valid amount.', 'warning');
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
        showToast('Could not find the credit record. Please use the Credits page to record this payment.', 'error');
      }
    }
  } catch (e) {
    console.error('Pay credit error:', e);
    showToast('Error recording payment. Please try again.', 'error');
  }
}
</script>
@endsection
