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
                        <div class="product-price text-base font-bold text-brand-700">&#8369;{{ number_format($item->price, 2) }}</div>
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
            <button class="btn-cancel flex-1 cursor-pointer rounded-lg border border-red-200 bg-white px-4 py-3 text-sm font-semibold text-red-600 transition-colors hover:bg-red-50" onclick="cancelCheckout()">CANCEL [Backspace]</button>
            <button class="btn-pay flex-1 cursor-pointer rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-slate-300" onclick="handleCheckout()">PAY (P0.00) [Enter]</button>
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
        showToast(isNonMember ? 'Credit is for members only.' : 'Select a member to use credit.', 'error');
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

let checkoutBackspaceShortcutController = null;

function isTypingTarget(target) {
    if (!(target instanceof Element)) {
        return false;
    }

    const tagName = target.tagName;
    const editable = target.closest('[contenteditable]');

    return tagName === 'INPUT'
        || tagName === 'TEXTAREA'
        || tagName === 'SELECT'
        || target.isContentEditable
        || (editable !== null && editable.getAttribute('contenteditable') !== 'false');
}

function handleCheckoutBackspaceShortcut(event) {
    if (event.key !== 'Backspace') {
        return;
    }

    if (isTypingTarget(event.target)) {
        return;
    }

    event.preventDefault();
    cancelCheckout();
}

function mountCheckoutBackspaceShortcut() {
    if (checkoutBackspaceShortcutController) {
        return;
    }

    checkoutBackspaceShortcutController = new AbortController();
    document.addEventListener('keydown', handleCheckoutBackspaceShortcut, {
        signal: checkoutBackspaceShortcutController.signal,
    });
}

function unmountCheckoutBackspaceShortcut() {
    if (!checkoutBackspaceShortcutController) {
        return;
    }

    checkoutBackspaceShortcutController.abort();
    checkoutBackspaceShortcutController = null;
}

document.addEventListener('DOMContentLoaded', mountCheckoutBackspaceShortcut);
window.addEventListener('pageshow', mountCheckoutBackspaceShortcut);
window.addEventListener('pagehide', unmountCheckoutBackspaceShortcut);

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
        event.preventDefault();
        event.stopPropagation();
        handleCheckout();
        posEnterIgnoreUntil = Date.now() + 300;
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

function focusMemberInput() {
    const input = document.getElementById('memberScanInput');
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
    const memberResult = await lookupMember(value, !cameFromMemberInput);

    if (memberResult === 'inactive') {
        resetScans();
        resetProductFilter();
        focusMemberInput();
        return false;
    }

    if (memberResult) {
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

function refreshCartTotals() {
    total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    const totalEl = document.querySelector('.total');
    if (totalEl) {
        totalEl.textContent = `TOTAL ${moneyP(total)}`;
    }

    const payButton = document.querySelector('.btn-pay');
    if (payButton && !isCheckoutProcessing()) {
        const method = document.getElementById('paymentMethodInput').value === 'credit' ? 'credit' : 'cash';
        payButton.textContent = method === 'credit'
            ? `CHARGE TO CREDIT (${moneyP(total)}) [Enter]`
            : `PAY (${moneyP(total)}) [Enter]`;
    }
}

function updateCartLineTotal(input) {
    const index = Number(input.dataset.index);
    const item = cart[index];
    if (!item) return;

    const row = input.closest('.checkout-item');
    const lineTotal = row ? row.querySelector('.line-total') : null;
    if (lineTotal) {
        lineTotal.textContent = moneyP(item.price * item.quantity);
    }
}

function syncQuantityControls(input) {
    const index = Number(input.dataset.index);
    const item = cart[index];
    if (!item) return;

    const stock = getItemStock(item.id);
    const row = input.closest('.checkout-item');
    const plusButton = row ? row.querySelector('.qty-plus') : null;
    if (plusButton) {
        const canIncrease = stock === null ? true : item.quantity < stock;
        plusButton.disabled = !canIncrease;
        plusButton.title = canIncrease ? '' : 'No more stock';
    }
}

function showQuantityWarning(input, message) {
    const row = input.closest('.checkout-item');
    if (!row) return;

    const warning = row.querySelector('.qty-warning');
    if (!warning) return;

    warning.textContent = message;
    warning.classList.remove('hidden');

    clearTimeout(row._qtyWarningTimer);
    row._qtyWarningTimer = setTimeout(function () {
        warning.classList.add('hidden');
    }, 1800);
}

function selectQuantity(input) {
    input.dataset.previous = input.value;
    input.select();
}

function handleQuantityInput(input) {
    const index = Number(input.dataset.index);
    const item = cart[index];
    if (!item) return;

    const cleaned = input.value.replace(/[^0-9]/g, '');
    if (cleaned !== input.value) {
        input.value = cleaned;
    }

    // Empty or 0 is allowed while typing; the blur handler resets it to 1.
    if (cleaned === '') return;

    let quantity = parseInt(cleaned, 10);
    if (isNaN(quantity) || quantity < 1) return;

    const stock = getItemStock(item.id);
    if (stock !== null && stock >= 1 && quantity > stock) {
        quantity = stock;
        input.value = String(stock);
        showQuantityWarning(input, 'Only ' + stock + ' in stock');
    }

    item.quantity = quantity;
    updateCartLineTotal(input);
    syncQuantityControls(input);
    refreshCartTotals();
}

function commitQuantity(input, refocus) {
    const index = Number(input.dataset.index);
    const item = cart[index];
    if (!item) return;

    const stock = getItemStock(item.id);
    const cleaned = String(input.value).replace(/[^0-9]/g, '');
    let quantity = parseInt(cleaned, 10);

    if (isNaN(quantity) || quantity < 1) {
        quantity = 1;
    } else if (stock !== null && stock >= 1 && quantity > stock) {
        quantity = stock;
        showQuantityWarning(input, 'Only ' + stock + ' in stock');
    }

    item.quantity = quantity;
    input.value = String(quantity);
    updateCartLineTotal(input);
    syncQuantityControls(input);
    refreshCartTotals();

    if (refocus) {
        focusSearch();
    }
}

function handleQuantityKeydown(event, input) {
    if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();

        const index = Number(input.dataset.index);
        const item = cart[index];
        const previous = parseInt(input.dataset.previous, 10);
        if (item && !isNaN(previous) && previous >= 1) {
            item.quantity = previous;
            input.value = String(previous);
            updateCartLineTotal(input);
            syncQuantityControls(input);
            refreshCartTotals();
        }

        input.blur();
        return;
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        event.stopPropagation();
        commitQuantity(input, true);
        return;
    }

    // Let Backspace edit the field instead of reaching the cancel shortcut.
    if (event.key === 'Backspace') {
        event.stopPropagation();
    }
}

function updateCart() {
    const cartContainer = document.querySelector('.checkout-items');
    if(cart.length === 0) {
        cartContainer.innerHTML = '<div class="empty-cart py-8 text-center text-sm text-slate-400">No items in cart</div>';
    } else {
        cartContainer.innerHTML = cart.map((item, index) => {
            const stock = getItemStock(item.id);
            const canIncrease = stock === null ? true : item.quantity < stock;
            return `
            <div class="checkout-item flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 py-2.5 last:border-0">
                <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700">${item.name}</span>
                <div class="qty-control flex shrink-0 items-center gap-2">
                    <button onclick="updateQuantity(${index}, -1)" class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-bold text-slate-600 transition-colors hover:bg-slate-50">-</button>
                    <input
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        value="${item.quantity}"
                        data-index="${index}"
                        aria-label="Quantity"
                        class="qty-input h-7 w-12 rounded-md border border-slate-300 bg-white px-1 text-center text-sm font-semibold text-slate-900 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                        onfocus="selectQuantity(this)"
                        onclick="this.select()"
                        oninput="handleQuantityInput(this)"
                        onblur="commitQuantity(this, false)"
                        onkeydown="handleQuantityKeydown(event, this)"
                    >
                    <button onclick="updateQuantity(${index}, 1)" ${!canIncrease ? 'disabled' : ''} title="${!canIncrease ? 'No more stock' : ''}" class="qty-plus flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-bold text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">+</button>
                </div>
                <span class="line-total shrink-0 text-sm font-semibold text-slate-900" data-index="${index}">${moneyP(item.price * item.quantity)}</span>
                <span class="qty-warning hidden w-full text-right text-[10px] font-medium text-amber-600"></span>
            </div>
        `;
        }).join('');
    }

    refreshCartTotals();
}

function updateQuantity(index, change) {
    const item = cart[index];
    if (!item) return;
    if (change > 0) {
        const stock = getItemStock(item.id);
        if (stock !== null && item.quantity + 1 > stock) {
            updateCart();
            const input = document.querySelector('.qty-input[data-index="' + index + '"]');
            if (input) showQuantityWarning(input, 'Only ' + stock + ' in stock');
            return;
        }
    }
    item.quantity = Number(item.quantity) + change;
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
        document.querySelector('.btn-pay').textContent = `PAY (${moneyP(total)}) [Enter]`;
    } else {
        btnCredit.className = 'flex-1 cursor-pointer rounded-lg border-2 border-brand-600 bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700 transition-colors';
        btnCash.className = 'flex-1 cursor-pointer rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition-colors hover:border-brand-300 hover:text-brand-600';
        document.querySelector('.btn-pay').textContent = `CHARGE TO CREDIT (${moneyP(total)}) [Enter]`;
    }
}

function selectedCheckoutMethod() {
    const input = document.getElementById('paymentMethodInput');
    return input && input.value === 'credit' ? 'credit' : 'cash';
}

function handleCheckout() {
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

    // Read the payment method at checkout time, not when the button was wired.
    const paymentMethod = selectedCheckoutMethod();
    setPaymentMethod(paymentMethod);

    if (paymentMethod === 'credit') {
        openCreditPayment();
        return;
    }

    openCashPayment();
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

const pesoFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
});

function moneyPFromCents(cents) {
    const c = Math.round(Number(cents) || 0);
    const pesos = c / 100;
    return pesoFormatter.format(pesos);
}

function moneyP(value) {
    if (typeof value === 'string') {
        const s = value.trim();
        if (s.indexOf('.') === -1 && /^-?\d+$/.test(s) && s.length > 4) {
            const c = parseInt(s, 10);
            const pesos = Math.round(c) / 100;
            return pesoFormatter.format(pesos);
        }
    }
    const cents = toCents(value);
    const pesos = Math.round(cents) / 100;
    return pesoFormatter.format(pesos);
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
        showToast(isNonMember ? 'Credit is for members only.' : 'Select a member to use credit.', 'error');
        return;
    }

    creditModalLastFocus = document.querySelector('.btn-pay');

    document.getElementById('creditMemberName').textContent = currentMember.first_name + ' ' + currentMember.last_name;
    document.getElementById('creditMemberId').textContent = 'ID: ' + (currentMember.member_number || currentMember.id || '-');
    document.getElementById('creditDueAmount').textContent = moneyP(total);
    const btn = document.getElementById('creditConfirmBtn');
    btn.disabled = false;
    btn.textContent = 'Confirm Payment';

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
        showToast(isNonMember ? 'Credit is for members only.' : 'Select a member to use credit.', 'error');
        closeCreditPayment();
        return;
    }

    const btn = document.getElementById('creditConfirmBtn');
    btn.disabled = true;
    btn.textContent = 'Processing...';

    setPaymentMethod('credit');
    document.getElementById('paymentMethodInput').value = 'credit';
    selectedPaymentMethod = 'credit';

    const outcome = await processPayment({ closeDialog: false });

    if (outcome === 'inactive') {
        focusMemberInput();
        return;
    }

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

function isInactiveMemberResponse(response, result) {
    return response && !response.ok && (
        result.message === 'This member is inactive'
        || result.is_active === false
        || result.member?.is_active === false
    );
}

function clearInactiveMemberCheckout() {
    const cashModal = document.getElementById('cashPaymentModal');
    const creditModal = document.getElementById('creditPaymentModal');

    if (cashModal && !cashModal.classList.contains('hidden')) {
        closeCashPayment();
    }

    if (creditModal && !creditModal.classList.contains('hidden')) {
        closeCreditPayment();
    }

    clearMember(false);
    focusMemberInput();
    showToast('This member is inactive', 'error');
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
    let paymentOutcome = 'failed';

    const paymentMethod = document.getElementById('paymentMethodInput').value === 'credit' ? 'credit' : 'cash';

    try {
    const body = {
        items: cart.map(item => ({ id: item.id, name: item.name, quantity: Number(item.quantity) })),
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

        const result = await response.json().catch(() => ({}));

        if (isInactiveMemberResponse(response, result)) {
            clearInactiveMemberCheckout();
            paymentOutcome = 'inactive';
            return paymentOutcome;
        }

        if (result.success) {
            paymentSucceeded = true;
            paymentOutcome = 'success';

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
                payButton.textContent = `CHARGE TO CREDIT (${moneyP(total)}) [Enter]`;
            } else {
                payButton.textContent = `PAY (${moneyP(total)}) [Enter]`;
            }
        } else {
            if (document.getElementById('paymentMethodInput').value === 'credit') {
                payButton.textContent = `CHARGE TO CREDIT (${moneyP(total)}) [Enter]`;
            } else {
                payButton.textContent = `PAY (${moneyP(total)}) [Enter]`;
            }
            payButton.disabled = false;
            const si = document.getElementById('search-input');
            if (si) {
                si.value = '';
                si.blur();
            }
            if (paymentOutcome !== 'inactive' && document.activeElement && document.activeElement.tagName === 'INPUT') {
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
            creditBtn.disabled = false;
            creditBtn.textContent = 'Confirm Payment';
        }
    }
}

// Member lookup functions
function blockInactiveMemberLookup() {
  const memberInput = document.getElementById('memberScanInput');
  const memberError = document.getElementById('memberScanError');

  showToast('This member is inactive', 'error');

  if (memberInput) {
    memberInput.value = '';
    memberInput.focus();
  }

  if (memberError) {
    memberError.style.display = 'none';
  }

  return 'inactive';
}

async function lookupMember(queryOverride, exact = false) {
  const memberInput = document.getElementById('memberScanInput');
  const query = (queryOverride !== undefined ? queryOverride : (memberInput ? memberInput.value : '')).trim();
  if (!query) return false;

  try {
    const params = new URLSearchParams({ q: query });
    if (exact) params.set('exact', '1');

    const response = await fetch(`/members/lookup?${params.toString()}`);
    const data = await response.json().catch(() => ({}));

    if (!response.ok && (data.message === 'This member is inactive' || data.is_active === false || data.member?.is_active === false)) {
      return blockInactiveMemberLookup();
    }

    if (!response.ok) {
      return false;
    }

    if (data.found) {
      if (data.member?.is_active === false || data.is_active === false) {
        return blockInactiveMemberLookup();
      }

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
  document.getElementById('paymentMethodInput').value = 'cash';
  selectedPaymentMethod = 'cash';
  focusSearch();
}

function clearMember(focus = true) {
  currentMember = null;
  isNonMember = false;

  document.getElementById('selectedMemberDisplay').style.display = 'none';
  document.getElementById('nonMemberBadge').style.display = 'none';
  document.getElementById('scannerInputSection').style.display = 'block';
  document.getElementById('nonMemberBtn').style.display = '';
  document.getElementById('memberScanInput').value = '';
  document.getElementById('selectedMemberIdInput').value = '';
  document.getElementById('isNonMemberInput').value = '0';
  document.getElementById('paymentMethodSection').style.display = 'none';
  document.getElementById('paymentMethodInput').value = 'cash';
  selectedPaymentMethod = 'cash';
  if (focus) {
    focusSearch();
  }
}

</script>
@endsection
