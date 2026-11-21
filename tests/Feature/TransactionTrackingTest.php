<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Credit;
use App\Models\Item;
use App\Models\Member;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransactionTrackingTest extends TestCase
{
    use RefreshDatabase;

    private int $memberCounter = 0;

    private function createUser(string $role = 'cashier', array $overrides = []): User
    {
        $this->memberCounter++;

        return User::create(array_merge([
            'name' => ucfirst($role).' User '.$this->memberCounter,
            'username' => $role.$this->memberCounter,
            'email' => null,
            'password' => 'password123',
            'role' => $role,
            'is_active' => true,
        ], $overrides));
    }

    private function createMember(array $overrides = []): Member
    {
        $this->memberCounter++;

        return Member::create(array_merge([
            'first_name' => 'Member',
            'last_name' => $this->memberCounter,
            'member_number' => 'MEM'.str_pad($this->memberCounter, 5, '0', STR_PAD_LEFT),
            'email' => "member{$this->memberCounter}@example.com",
            'total_purchases' => 0,
            'purchase_count' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function createItem(array $overrides = []): Item
    {
        return Item::create(array_merge([
            'item_name' => 'Rice',
            'quantity' => 100,
            'price' => 50.00,
            'category' => 'essentials',
        ], $overrides));
    }

    private function createSale(array $overrides = []): Sale
    {
        return Sale::create(array_merge([
            'cashier_id' => null,
            'member_id' => null,
            'member_number' => null,
            'customer_type' => 'non_member',
            'payment_method' => 'cash',
            'total' => 100.00,
            'status' => Sale::STATUS_COMPLETED,
        ], $overrides));
    }

    public function test_checkout_records_sale_and_items_with_session_cashier(): void
    {
        $item = $this->createItem(['price' => 25.00, 'quantity' => 10]);
        $cashierA = $this->createUser();
        $cashierB = $this->createUser();

        $response = $this->actingAs($cashierA)->postJson('/checkout', [
            'items' => [['id' => $item->id, 'quantity' => 3]],
            'is_non_member' => '1',
            'payment_method' => 'cash',
            'cashier_id' => $cashierB->id,
        ]);

        $response->assertJson(['success' => true]);

        $sale = Sale::with('items')->firstOrFail();

        $this->assertSame($cashierA->id, $sale->cashier_id);
        $this->assertNotSame($cashierB->id, $sale->cashier_id);
        $this->assertSame('75.00', $sale->total);
        $this->assertSame(Sale::STATUS_COMPLETED, $sale->status);
        $this->assertSame(1, $sale->items->count());

        $line = $sale->items->first();
        $this->assertSame($item->id, $line->item_id);
        $this->assertSame('Rice', $line->product_name);
        $this->assertSame(3, $line->quantity);
        $this->assertSame('25.00', $line->unit_price);
        $this->assertSame('75.00', $line->subtotal);
        $this->assertSame($sale->id, $response->json('transaction_id'));
        $response->assertJsonMissingPath('receipt_number');
    }

    public function test_credit_checkout_links_member_to_the_sale(): void
    {
        $member = $this->createMember();
        $item = $this->createItem(['price' => 40.00]);
        $cashier = $this->createUser();

        $this->actingAs($cashier)->postJson('/checkout', [
            'items' => [['id' => $item->id, 'quantity' => 2]],
            'member_id' => (string) $member->id,
            'is_non_member' => '0',
            'payment_method' => 'credit',
        ])->assertJson(['success' => true]);

        $sale = Sale::firstOrFail();

        $this->assertSame($member->id, $sale->member_id);
        $this->assertSame($member->member_number, $sale->member_number);
        $this->assertSame('credit', $sale->payment_method);
        $this->assertSame('80.00', $sale->total);
        $this->assertSame(1, Credit::where('member_id', $member->id)->count());
    }

    public function test_admin_sees_transactions_with_cashier_names_and_totals(): void
    {
        $cashierA = $this->createUser(overrides: ['name' => 'Alice Cashier']);
        $cashierB = $this->createUser(overrides: ['name' => 'Bob Cashier']);

        $saleA = $this->createSale(['cashier_id' => $cashierA->id, 'total' => 125.50]);
        $saleB = $this->createSale(['cashier_id' => $cashierB->id, 'total' => 75.25, 'payment_method' => 'credit']);

        $admin = $this->createUser('admin');
        $response = $this->actingAs($admin)->get('/transactions');

        $response->assertOk();
        $response->assertSee('#'.$saleA->id);
        $response->assertSee('#'.$saleB->id);
        $response->assertSee('Alice Cashier');
        $response->assertSee('Bob Cashier');
        $response->assertSee('&#8369;125.50', false);
        $response->assertSee('&#8369;75.25', false);

        $summary = $response->viewData('summary');
        $this->assertSame(2, $summary['transactions']);
        $this->assertSame(200.75, $summary['total_sales']);
        $this->assertSame(125.50, $summary['cash_total']);
        $this->assertSame(75.25, $summary['credit_total']);
    }

    public function test_admin_can_filter_transactions_by_cashier_and_date(): void
    {
        $cashierA = $this->createUser();
        $cashierB = $this->createUser();

        $oldSale = $this->createSale([
            'cashier_id' => $cashierA->id,
            'total' => 10,
        ]);
        $oldSale->created_at = Carbon::now()->subDays(10);
        $oldSale->save();

        $newSale = $this->createSale([
            'cashier_id' => $cashierA->id,
            'total' => 20,
        ]);

        $otherSale = $this->createSale([
            'cashier_id' => $cashierB->id,
            'total' => 30,
        ]);

        $admin = $this->createUser('admin');

        $response = $this->actingAs($admin)->get('/transactions?'.http_build_query([
            'cashier_id' => $cashierA->id,
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('#'.$newSale->id);
        $response->assertDontSee('#'.$oldSale->id);
        $response->assertDontSee('#'.$otherSale->id);

        $summary = $response->viewData('summary');
        $this->assertSame(1, $summary['transactions']);
        $this->assertSame(20.0, $summary['total_sales']);
    }

    public function test_voided_sales_are_listed_but_excluded_from_totals(): void
    {
        $cashier = $this->createUser();
        $completed = $this->createSale([
            'cashier_id' => $cashier->id,
            'total' => 111.00,
        ]);
        $voided = $this->createSale([
            'cashier_id' => $cashier->id,
            'total' => 999.00,
            'status' => Sale::STATUS_VOIDED,
            'void_reason' => 'Customer changed their mind',
        ]);

        $admin = $this->createUser('admin');
        $response = $this->actingAs($admin)->get('/transactions?cashier_id='.$cashier->id);

        $response->assertOk();
        $response->assertSee('#'.$voided->id);
        $response->assertSee('#'.$completed->id);
        $response->assertSee('Voided');

        $summary = $response->viewData('summary');
        $this->assertSame(2, $summary['transactions']);
        $this->assertSame(111.0, $summary['total_sales']);

        $perCashier = $response->viewData('perCashier');
        $this->assertCount(1, $perCashier);
        $this->assertSame(1, $perCashier->first()['transactions']);
        $this->assertSame(111.0, $perCashier->first()['total_sales']);
    }

    public function test_cashier_is_redirected_away_from_admin_transaction_endpoints(): void
    {
        $cashier = $this->createUser();

        $this->actingAs($cashier)->get('/transactions')->assertRedirect(route('shop.index'));
        $this->actingAs($cashier)->get('/transactions/export')->assertRedirect(route('shop.index'));
        $this->actingAs($cashier)->get('/transactions/activity')->assertRedirect(route('shop.index'));
    }

    public function test_cashier_only_sees_own_transactions_and_defaults_to_today(): void
    {
        $cashierA = $this->createUser();
        $cashierB = $this->createUser();

        $todayA = $this->createSale(['cashier_id' => $cashierA->id]);
        $todayB = $this->createSale(['cashier_id' => $cashierB->id]);

        $yesterdayA = $this->createSale(['cashier_id' => $cashierA->id]);
        $yesterdayA->created_at = Carbon::now()->subDay();
        $yesterdayA->save();

        $response = $this->actingAs($cashierA)->get('/my-transactions?cashier_id='.$cashierB->id);

        $response->assertOk();
        $response->assertSee('#'.$todayA->id);
        $response->assertDontSee('#'.$todayB->id);
        $response->assertDontSee('#'.$yesterdayA->id);

        $summary = $response->viewData('summary');
        $this->assertSame(1, $summary['transactions']);
        $this->assertSame(100.0, $summary['total_sales']);

        $yesterdayResponse = $this->actingAs($cashierA)->get('/my-transactions?'.http_build_query([
            'date_from' => Carbon::now()->subDay()->toDateString(),
            'date_to' => Carbon::now()->subDay()->toDateString(),
        ]));

        $yesterdayResponse->assertSee('#'.$yesterdayA->id);
        $yesterdayResponse->assertDontSee('#'.$todayA->id);
        $yesterdayResponse->assertDontSee('#'.$todayB->id);
    }

    public function test_excel_export_contains_only_filtered_transactions_with_formatted_cells(): void
    {
        $cashierA = $this->createUser();
        $cashierB = $this->createUser();

        $saleA = $this->createSale(['cashier_id' => $cashierA->id, 'total' => 44.50]);
        $saleA->items()->create([
            'product_name' => 'Rice',
            'quantity' => 2,
            'unit_price' => 22.25,
            'subtotal' => 44.50,
        ]);

        $saleB = $this->createSale(['cashier_id' => $cashierB->id, 'total' => 99.00]);

        $admin = $this->createUser('admin');
        $response = $this->actingAs($admin)->get('/transactions/export?cashier_id='.$cashierA->id, [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString('attachment; filename="CCFMPC_Transactions_', $response->headers->get('Content-Disposition'));
        $this->assertStringEndsWith('.xlsx"', $response->headers->get('Content-Disposition'));

        $contents = $response->getContent();
        $this->assertStringStartsWith('PK', $contents);

        $sheet = $this->xlsxEntry($contents, 'xl/worksheets/sheet1.xml');
        $styles = $this->xlsxEntry($contents, 'xl/styles.xml');

        $this->assertNotNull($sheet);
        $this->assertNotNull($styles);

        foreach (['Date / Time', 'Transaction ID', 'Cashier', 'Member', 'Payment', 'Total', 'Status', 'Items'] as $header) {
            $this->assertStringContainsString('<t xml:space="preserve">'.$header.'</t>', $sheet);
        }

        $this->assertStringContainsString('<t xml:space="preserve">#'.$saleA->id.'</t>', $sheet);
        $this->assertStringNotContainsString('<t xml:space="preserve">#'.$saleB->id.'</t>', $sheet);
        $this->assertStringContainsString('<t xml:space="preserve">Rice x2</t>', $sheet);

        $this->assertStringContainsString('&quot;₱&quot;#,##0.00', $styles);
        $this->assertStringContainsString('mmm dd, yyyy h:mm AM/PM', $styles);
        $this->assertStringContainsString('wrapText="1"', $styles);
        $this->assertStringContainsString('<pane ySplit="1" topLeftCell="A2"', $sheet);
        $this->assertStringContainsString('customHeight="1"', $sheet);
        $this->assertStringContainsString('<col min="8" max="8" width="50" customWidth="1"/>', $sheet);
        $this->assertMatchesRegularExpression('/<c r="A2" s="4"><v>\d+(\.\d+)?<\/v><\/c>/', $sheet);
        $this->assertStringContainsString('<c r="A3" s="5" t="inlineStr"><is><t xml:space="preserve">TOTAL</t></is></c>', $sheet);
        $this->assertStringContainsString('<c r="F3" s="6"><f>SUM(F2:F2)</f><v>44.5</v></c>', $sheet);
    }

    public function test_excel_export_rejects_empty_results_and_uses_date_range_filename(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->getJson('/transactions/export')
            ->assertStatus(422)
            ->assertJson(['message' => 'No transactions to export.']);

        $this->actingAs($admin)
            ->get('/transactions/export')
            ->assertRedirect(route('transactions.index'))
            ->assertSessionHas('error', 'No transactions to export.');

        $sale = $this->createSale(['total' => 15.00]);
        $sale->created_at = Carbon::parse('2026-01-15 10:30:00');
        $sale->save();

        $response = $this->actingAs($admin)->get('/transactions/export?date_from=2026-01-01&date_to=2026-01-31');

        $response->assertOk();
        $this->assertStringContainsString(
            'filename="CCFMPC_Transactions_2026-01-01_to_2026-01-31.xlsx"',
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_unknown_cashier_is_displayed_as_unknown(): void
    {
        $sale = $this->createSale(['cashier_id' => null]);

        $admin = $this->createUser('admin');
        $response = $this->actingAs($admin)->get('/transactions');

        $response->assertOk();
        $response->assertSee('#'.$sale->id);
        $response->assertSee('Unknown');
    }

    public function test_activity_log_records_login_logout_and_sale_events(): void
    {
        $cashier = $this->createUser(overrides: ['username' => 'auditcashier']);
        $item = $this->createItem();

        $this->post('/login', [
            'username' => 'auditcashier',
            'password' => 'password123',
        ])->assertRedirect('/pos');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $cashier->id,
            'action' => AuditLog::ACTION_LOGIN,
            'target_id' => $cashier->id,
        ]);

        $this->actingAs($cashier)->postJson('/checkout', [
            'items' => [['id' => $item->id, 'quantity' => 1]],
            'is_non_member' => '1',
        ])->assertJson(['success' => true]);

        $sale = Sale::firstOrFail();

        $saleLog = AuditLog::where('action', AuditLog::ACTION_SALE_COMPLETED)->firstOrFail();
        $this->assertSame('sale', $saleLog->target_type);
        $this->assertSame($sale->id, $saleLog->target_id);
        $this->assertSame($sale->id, $saleLog->details['transaction_id']);
        $this->assertArrayNotHasKey('receipt_number', $saleLog->details);
        $this->assertArrayNotHasKey('password', $saleLog->details);

        $this->actingAs($cashier)->post('/logout')->assertRedirect('/login');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $cashier->id,
            'action' => AuditLog::ACTION_LOGOUT,
        ]);
    }

    public function test_account_actions_are_audited_without_passwords(): void
    {
        $admin = $this->createUser('admin');
        $cashier = $this->createUser();

        $this->actingAs($admin)->post(route('accounts.store'), [
            'name' => 'New Cashier',
            'username' => 'newcashier',
            'password' => 'supersecret123',
            'password_confirmation' => 'supersecret123',
        ])->assertRedirect(route('accounts.index'));

        $created = User::where('username', 'newcashier')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_ACCOUNT_CREATED,
            'target_type' => 'user',
            'target_id' => $created->id,
        ]);

        $this->actingAs($admin)->put(route('accounts.update', $cashier), [
            'name' => 'Renamed Cashier',
            'username' => $cashier->username,
            'is_active' => '1',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_ACCOUNT_UPDATED,
            'target_id' => $cashier->id,
        ]);

        $this->actingAs($admin)->put(route('accounts.password.update', $cashier), [
            'password' => 'anothersecret123',
            'password_confirmation' => 'anothersecret123',
        ])->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_ACCOUNT_PASSWORD_RESET,
            'target_id' => $cashier->id,
        ]);

        $serialized = json_encode(AuditLog::all()->toArray());
        $this->assertStringNotContainsString('supersecret123', $serialized);
        $this->assertStringNotContainsString('anothersecret123', $serialized);
        $this->assertStringNotContainsString('password_confirmation', $serialized);

        foreach (AuditLog::all() as $log) {
            if (is_array($log->details)) {
                $this->assertArrayNotHasKey('password', $log->details);
                $this->assertArrayNotHasKey('password_confirmation', $log->details);
            }
        }
    }

    public function test_activity_log_page_filters_by_user_and_action(): void
    {
        $admin = $this->createUser('admin');
        $cashier = $this->createUser();

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_LOGIN,
            'target_type' => 'user',
            'target_id' => $admin->id,
            'details' => ['username' => $admin->username],
        ]);

        AuditLog::create([
            'user_id' => $cashier->id,
            'action' => AuditLog::ACTION_LOGOUT,
            'target_type' => 'user',
            'target_id' => $cashier->id,
            'details' => ['username' => $cashier->username],
        ]);

        $response = $this->actingAs($admin)->get('/transactions/activity?'.http_build_query([
            'user_id' => $cashier->id,
            'action' => AuditLog::ACTION_LOGOUT,
        ]));

        $response->assertOk();
        $response->assertViewIs('transactions.activity');

        $logs = $response->viewData('logs');
        $this->assertSame(1, $logs->total());
        $this->assertSame($cashier->id, $logs->first()->user_id);
        $this->assertSame(AuditLog::ACTION_LOGOUT, $logs->first()->action);
    }

    public function test_activity_details_are_rendered_in_plain_language(): void
    {
        $this->assertSame(
            'Logged in as admin (Admin)',
            AuditLog::formatActivityDetails(AuditLog::ACTION_LOGIN, ['username' => 'admin', 'role' => 'admin'])
        );

        $this->assertSame(
            'Logged out',
            AuditLog::formatActivityDetails(AuditLog::ACTION_LOGOUT, ['username' => 'jeyms'])
        );

        $this->assertSame(
            'Deactivated account of @jeyms. Active sessions were signed out.',
            AuditLog::formatActivityDetails(AuditLog::ACTION_ACCOUNT_STATUS_CHANGED, [
                'username' => 'jeyms',
                'is_active' => false,
                'previous_is_active' => true,
                'sessions_invalidated' => true,
            ])
        );

        $this->assertSame(
            'Reactivated account of @jeyms.',
            AuditLog::formatActivityDetails(AuditLog::ACTION_ACCOUNT_STATUS_CHANGED, [
                'username' => 'jeyms',
                'is_active' => true,
                'previous_is_active' => false,
                'sessions_invalidated' => false,
            ])
        );

        $updated = AuditLog::formatActivityDetails(AuditLog::ACTION_ACCOUNT_UPDATED, [
            'username' => 'jeyms',
            'is_active' => 'no',
            'previous_is_active' => 'yes',
            'sessions_invalidated' => 'yes',
            'password_changed' => true,
        ]);
        $this->assertStringContainsString('Deactivated account of @jeyms.', $updated);
        $this->assertStringContainsString('Password was changed.', $updated);
        $this->assertStringContainsString('Active sessions were signed out.', $updated);

        $sale = AuditLog::formatActivityDetails(AuditLog::ACTION_SALE_COMPLETED, [
            'transaction_id' => 123,
            'total' => 1250,
            'payment_method' => 'cash',
            'item_count' => 3,
            'member_id' => 4,
        ]);
        $this->assertStringContainsString('Completed sale #123', $sale);
        $this->assertStringContainsString('3 items', $sale);
        $this->assertStringContainsString('₱1,250.00 via cash', $sale);
        $this->assertStringNotContainsString('is_active', $sale);

        $this->assertSame(
            'Is active: yes, Note: hello.',
            AuditLog::formatActivityDetails('custom_event', ['is_active' => true, 'note' => 'hello'])
        );
    }

    public function test_activity_log_table_omits_target_and_uses_plain_details(): void
    {
        $admin = $this->createUser('admin', ['username' => 'adminaudit']);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_LOGIN,
            'target_type' => 'user',
            'target_id' => $admin->id,
            'details' => ['username' => 'adminaudit', 'role' => 'admin'],
        ]);

        $response = $this->actingAs($admin)->get('/transactions/activity');

        $response->assertOk();
        $response->assertSee('Logged in as adminaudit (Admin)');
        $response->assertSee('Details');
        $response->assertDontSee('>Target<', false);
    }

    public function test_sale_items_keep_the_unit_price_at_time_of_sale(): void
    {
        $cashier = $this->createUser();
        $sale = $this->createSale(['cashier_id' => $cashier->id]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'item_id' => null,
            'product_name' => 'Historical Item',
            'quantity' => 4,
            'unit_price' => 12.50,
            'subtotal' => 50.00,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_name' => 'Historical Item',
            'quantity' => 4,
            'unit_price' => 12.50,
            'subtotal' => 50.00,
        ]);
    }

    private function xlsxEntry(string $archive, string $entry): ?string
    {
        $offset = 0;

        while ($offset + 30 <= strlen($archive)) {
            $header = unpack(
                'Vsignature/vversion/vflags/vmethod/vtime/vdate/Vcrc/VcompressedSize/VuncompressedSize/vnameLength/vextraLength',
                substr($archive, $offset, 30)
            );

            if ($header['signature'] !== 0x04034B50) {
                return null;
            }

            $name = substr($archive, $offset + 30, $header['nameLength']);
            $dataStart = $offset + 30 + $header['nameLength'] + $header['extraLength'];
            $data = substr($archive, $dataStart, $header['compressedSize']);

            if ($name === $entry) {
                if ($header['method'] === 8) {
                    $inflated = gzinflate($data);

                    return $inflated === false ? null : $inflated;
                }

                return $data;
            }

            $offset = $dataStart + $header['compressedSize'];
        }

        return null;
    }
}
