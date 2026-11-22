<x-tab-bar
    :tabs="[
        ['key' => 'index', 'label' => 'Transactions', 'href' => route('transactions.index')],
        ['key' => 'activity', 'label' => 'Activity Log', 'href' => route('transactions.activity')],
    ]"
    :active="request()->routeIs('transactions.activity') ? 'activity' : 'index'"
/>
