<div class="mb-6 flex gap-2 border-b border-slate-200">
    <a href="{{ route('transactions.index') }}" class="border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs('transactions.index') ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
        Transactions
    </a>
    <a href="{{ route('transactions.activity') }}" class="border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs('transactions.activity') ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
        Activity Log
    </a>
</div>
