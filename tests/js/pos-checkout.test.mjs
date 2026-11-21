import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { test } from 'node:test';
import { fileURLToPath } from 'node:url';
import { JSDOM } from 'jsdom';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const bladePath = path.join(root, 'resources', 'views', 'shop', 'index.blade.php');
const blade = fs.readFileSync(bladePath, 'utf8');
const scriptMatch = blade.match(/<script>([\s\S]*?)<\/script>/);

if (!scriptMatch) {
    throw new Error('Could not find the POS script in shop/index.blade.php');
}

const posScript = scriptMatch[1];

const fixture = `<!doctype html>
<html>
<head>
    <meta name="csrf-token" content="test-token">
</head>
<body>
    <div id="paymentMethodSection"></div>
    <button id="btnCash" type="button"></button>
    <button id="btnCredit" type="button"></button>
    <button class="btn-pay" type="button"></button>
    <div class="checkout-items"></div>
    <div class="total"></div>
    <input id="paymentMethodInput" type="hidden" value="cash">
    <input id="selectedMemberIdInput" type="hidden" value="">
    <input id="isNonMemberInput" type="hidden" value="0">
    <input id="search-input" type="text">
    <input id="memberScanInput" type="text">
    <div id="selectedMemberDisplay"></div>
    <span id="selectedMemberName"></span>
    <span id="selectedMemberID"></span>
    <div id="scannerInputSection"></div>
    <button id="nonMemberBtn" type="button"></button>
    <div id="nonMemberBadge"></div>
    <div id="memberScanError"></div>
    <div id="cashPaymentModal" class="hidden"></div>
    <div id="creditPaymentModal" class="hidden"></div>
    <div id="cashDueAmount"></div>
    <input id="cashReceivedInput">
    <div id="cashChangeDisplay"></div>
    <div id="cashShortDisplay" class="hidden"></div>
    <button id="cashConfirmBtn" type="button"></button>
    <span id="creditMemberName"></span>
    <span id="creditMemberId"></span>
    <span id="creditDueAmount"></span>
    <button id="creditConfirmBtn" type="button"></button>
</body>
</html>`;

function createPos() {
    const dom = new JSDOM(fixture, {
        runScripts: 'outside-only',
        url: 'http://localhost/shop',
    });
    const { window } = dom;

    window.__toasts = [];
    window.__requests = [];
    window.showToast = (message, type, options) => {
        window.__toasts.push({ message, type, options });
    };
    window.fetch = async (url, options = {}) => {
        const body = options.body ? JSON.parse(options.body) : null;
        window.__requests.push({ url, body });

        return {
            json: async () => ({
                success: true,
                total: body?.items?.reduce((sum, item) => sum + (70 * item.quantity), 0) ?? 70,
                payment_method: body?.payment_method ?? 'cash',
                amount_received: body?.amount_received ?? null,
                change_amount: null,
                items: [],
            }),
        };
    };

    window.eval(`${posScript}
window.__pos = {
    setCart(items) {
        cart = items.map((item) => ({ ...item }));
        total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        updateCart();
    },
    setMember(member) {
        currentMember = member;
        isNonMember = member === null;
        document.getElementById('selectedMemberIdInput').value = member ? String(member.id) : '';
        document.getElementById('isNonMemberInput').value = member ? '0' : '1';
    },
    state() {
        return {
            cartLength: cart.length,
            total,
            selectedPaymentMethod,
            hiddenPaymentMethod: document.getElementById('paymentMethodInput').value,
            payLabel: document.querySelector('.btn-pay').textContent,
        };
    },
};
`);

    const document = window.document;
    const payButton = document.querySelector('.btn-pay');
    const cashModal = document.getElementById('cashPaymentModal');
    const creditModal = document.getElementById('creditPaymentModal');

    payButton.addEventListener('click', () => window.handleCheckout());
    document.getElementById('btnCash').addEventListener('click', () => window.setPaymentMethod('cash'));
    document.getElementById('btnCredit').addEventListener('click', () => window.setPaymentMethod('credit'));
    document.getElementById('creditConfirmBtn').addEventListener('click', () => window.confirmCreditPayment());

    const pos = window.__pos;
    pos.setCart([{ id: 1, name: 'Item', price: 70, quantity: 1 }]);
    pos.setMember({
        id: 1,
        first_name: 'Test',
        last_name: 'Member',
        member_number: 'MEM00001',
    });

    return {
        window,
        document,
        pos,
        payButton,
        cashModal,
        creditModal,
        click(element) {
            element.dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true }));
        },
        pressEnter() {
            document.body.dispatchEvent(new window.KeyboardEvent('keydown', {
                key: 'Enter',
                bubbles: true,
                cancelable: true,
            }));
        },
        isOpen(modal) {
            return !modal.classList.contains('hidden');
        },
    };
}

test('Pay button and Enter use the shared handleCheckout path', () => {
    assert.match(blade, /<button class="btn-pay[^"]*"[^>]*onclick="handleCheckout\(\)"/);
    assert.doesNotMatch(blade, /onclick="openCashPayment\(\)"/);

    const enterHandler = blade.slice(
        blade.indexOf("if (event.key === 'F11')"),
        blade.indexOf('function applyProductFilter'),
    );

    assert.match(enterHandler, /handleCheckout\(\)/);
    assert.doesNotMatch(enterHandler, /openCashPayment\(\)|openCreditPayment\(\)/);
});

test('member + credit + mouse click charges credit without opening the cash modal', async () => {
    const page = createPos();

    page.click(page.document.getElementById('btnCredit'));
    page.click(page.payButton);

    assert.equal(page.isOpen(page.cashModal), false);
    assert.equal(page.isOpen(page.creditModal), true);
    assert.equal(page.window.__requests.length, 0);

    await page.window.confirmCreditPayment();

    assert.equal(page.window.__requests.length, 1);
    assert.equal(page.window.__requests[0].body.payment_method, 'credit');
    assert.equal(page.window.__requests[0].body.amount_received, undefined);
    assert.equal(page.pos.state().cartLength, 0);
    assert.ok(page.window.__toasts.some((toast) => /Credit/.test(toast.message)));
});

test('member + credit + Enter charges credit without opening the cash modal', async () => {
    const page = createPos();

    page.click(page.document.getElementById('btnCredit'));
    page.pressEnter();

    assert.equal(page.isOpen(page.cashModal), false);
    assert.equal(page.isOpen(page.creditModal), true);

    await page.window.confirmCreditPayment();

    assert.equal(page.window.__requests.length, 1);
    assert.equal(page.window.__requests[0].body.payment_method, 'credit');
    assert.equal(page.window.__requests[0].body.amount_received, undefined);
});

test('cash + mouse click opens the cash payment modal', () => {
    const page = createPos();

    page.click(page.document.getElementById('btnCash'));
    page.click(page.payButton);

    assert.equal(page.isOpen(page.cashModal), true);
    assert.equal(page.isOpen(page.creditModal), false);
    assert.equal(page.window.__requests.length, 0);
});

test('cash + Enter opens the cash payment modal', () => {
    const page = createPos();

    page.click(page.document.getElementById('btnCash'));
    page.pressEnter();

    assert.equal(page.isOpen(page.cashModal), true);
    assert.equal(page.isOpen(page.creditModal), false);
    assert.equal(page.window.__requests.length, 0);
});

test('non-member credit is blocked with an error', () => {
    const page = createPos();

    page.pos.setMember(null);
    page.click(page.document.getElementById('btnCredit'));
    page.click(page.payButton);

    assert.equal(page.isOpen(page.cashModal), false);
    assert.equal(page.isOpen(page.creditModal), false);
    assert.equal(page.window.__requests.length, 0);
    assert.ok(page.window.__toasts.some((toast) => /Credit is for members only/.test(toast.message)));
});

test('switching payment method with the mouse keeps label and hidden state in sync', () => {
    const page = createPos();

    page.click(page.document.getElementById('btnCredit'));
    assert.equal(page.pos.state().hiddenPaymentMethod, 'credit');
    assert.match(page.pos.state().payLabel, /^CHARGE TO CREDIT \(\u20B170\.00\)/);

    page.click(page.document.getElementById('btnCash'));
    assert.equal(page.pos.state().hiddenPaymentMethod, 'cash');
    assert.match(page.pos.state().payLabel, /^PAY \(\u20B170\.00\)/);

    page.click(page.document.getElementById('btnCredit'));
    assert.equal(page.pos.state().hiddenPaymentMethod, 'credit');
    assert.match(page.pos.state().payLabel, /^CHARGE TO CREDIT \(\u20B170\.00\)/);
});

test('rapid double click on Charge to Credit creates only one transaction', async () => {
    const page = createPos();

    page.click(page.document.getElementById('btnCredit'));
    page.click(page.payButton);
    page.click(page.payButton);

    assert.equal(page.isOpen(page.cashModal), false);
    assert.equal(page.isOpen(page.creditModal), true);
    assert.equal(page.window.__requests.length, 0);

    const confirm = page.document.getElementById('creditConfirmBtn');
    page.click(confirm);
    page.click(confirm);
    await page.window.confirmCreditPayment();

    assert.equal(page.window.__requests.length, 1);
    assert.equal(page.window.__requests[0].body.payment_method, 'credit');
});
