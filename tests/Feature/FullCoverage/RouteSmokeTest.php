<?php

namespace Tests\Feature\FullCoverage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_get_route_responds_without_server_error(): void
    {
        $admin = $this->admin();
        $cashier = $this->cashier();
        $member = $this->member();
        $item = $this->item();
        $credit = $this->credit(['member_id' => $member->id]);

        $routes = [
            '/',
            '/login',
            '/account/status',
            '/pos',
            '/items/lookup?q='.urlencode($item->barcode),
            '/members/lookup?q='.urlencode($member->member_number),
            '/credits',
            '/credits/'.$credit->id,
            '/my-transactions',
            '/inventory',
            '/inventory/barcode/'.urlencode($item->barcode).'/download',
            '/inventory/low-stock',
            '/inventory/add',
            '/inventory/edit/'.$item->id,
            '/members',
            '/members/create',
            '/members/edit/'.$member->id,
            '/members/analytics/'.$member->id,
            '/members/analytics',
            '/members/card/'.$member->id,
            '/members/card/'.$member->id.'/download',
            '/accounts',
            '/accounts/create',
            '/accounts/me',
            '/accounts/'.$cashier->id.'/edit',
            '/accounts/'.$cashier->id.'/password',
            '/analytics',
            '/reports/credits?format=csv',
            '/reports/sales?format=csv',
            '/reports/inventory?format=csv',
            '/reports/member-purchases?format=csv',
            '/reports/transactions?format=csv',
            '/transactions',
            '/transactions/export',
            '/transactions/activity',
            '/api/analytics/sales?period=daily',
            '/api/analytics/customer-type?period=daily',
            '/api/test',
            '/api/create-sample-data',
            '/api/user',
        ];

        $failures = [];
        foreach ($routes as $uri) {
            $response = $this->actingAs($admin)->get($uri);
            if ($response->getStatusCode() >= 500) {
                $failures[] = "GET {$uri} returned {$response->getStatusCode()}";
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_every_cashier_allowed_get_route_responds_without_server_error(): void
    {
        $cashier = $this->cashier();
        $member = $this->member();
        $item = $this->item();
        $credit = $this->credit(['member_id' => $member->id]);

        $routes = [
            '/pos',
            '/items/lookup?q='.urlencode($item->barcode),
            '/members/lookup?q='.urlencode($member->member_number),
            '/credits',
            '/credits/'.$credit->id,
            '/my-transactions',
            '/inventory',
            '/inventory/barcode/'.urlencode($item->barcode).'/download',
            '/inventory/low-stock',
            '/analytics',
        ];

        $failures = [];
        foreach ($routes as $uri) {
            $response = $this->actingAs($cashier)->get($uri);
            if ($response->getStatusCode() >= 500) {
                $failures[] = "GET {$uri} returned {$response->getStatusCode()}";
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }
}
