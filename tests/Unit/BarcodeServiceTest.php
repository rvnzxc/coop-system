<?php

namespace Tests\Unit;

use App\Services\BarcodeService;
use PHPUnit\Framework\TestCase;

class BarcodeServiceTest extends TestCase
{
    // ── lookupCandidates ─────────────────────────────────────

    public function test_exact_code_is_the_first_candidate(): void
    {
        $this->assertSame('P123456', BarcodeService::lookupCandidates('P123456')[0]);
    }

    public function test_surrounding_whitespace_and_control_characters_are_stripped(): void
    {
        $candidates = BarcodeService::lookupCandidates(" 012345678905\r\n");

        $this->assertContains('012345678905', $candidates);
        $this->assertNotContains(" 012345678905\r\n", $candidates);
    }

    public function test_upca_and_ean13_forms_of_the_same_product_resolve_to_each_other(): void
    {
        // 12 digit UPC-A gains the EAN leading zero.
        $fromUpca = BarcodeService::lookupCandidates('012345678905');
        $this->assertContains('012345678905', $fromUpca);
        $this->assertContains('0012345678905', $fromUpca);

        // 13 digit EAN-13 keeps its zero-prefixed and stripped forms.
        $fromEan = BarcodeService::lookupCandidates('0012345678905');
        $this->assertContains('0012345678905', $fromEan);
        $this->assertContains('012345678905', $fromEan);
    }

    public function test_gs1_application_identifier_suffix_is_trimmed_back_to_the_gtin(): void
    {
        $candidates = BarcodeService::lookupCandidates('0012345678905171231');

        $this->assertContains('0012345678905', $candidates);
        $this->assertContains('012345678905', $candidates);
    }

    public function test_internal_codes_are_not_padded_or_truncated(): void
    {
        $this->assertSame(['P123456'], BarcodeService::lookupCandidates('P123456'));
    }

    public function test_member_numbers_are_matched_verbatim(): void
    {
        $this->assertContains('MEM00001', BarcodeService::lookupCandidates('MEM00001'));
        $this->assertContains('#00001', BarcodeService::lookupCandidates('#00001'));
    }

    public function test_short_or_empty_input_yields_no_unexpected_candidates(): void
    {
        $this->assertSame([], BarcodeService::lookupCandidates(null));
        $this->assertSame([], BarcodeService::lookupCandidates('   '));
        $this->assertSame(['1234567'], BarcodeService::lookupCandidates('1234567'));
    }

    // ── generateBarcode ──────────────────────────────────────

    public function test_generate_barcode_returns_inline_svg(): void
    {
        $svg = BarcodeService::generateBarcode('P123456');

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('P123456', $svg);
        // Inline SVG is embedded straight into Blade, so the XML prolog and
        // doctype that a standalone document would carry must not be present.
        $this->assertStringNotContainsString('<?xml', $svg);
        $this->assertStringNotContainsString('<!DOCTYPE', $svg);
    }

    public function test_generate_barcode_can_hide_the_human_readable_label(): void
    {
        $withText = BarcodeService::generateBarcode('P123456', 200, true);
        $withoutText = BarcodeService::generateBarcode('P123456', 200, false);

        $this->assertStringContainsString('<text', $withText);
        $this->assertStringNotContainsString('font-family="monospace"', $withoutText);
    }

    public function test_generate_barcode_handles_member_number_characters(): void
    {
        $this->assertStringContainsString('<svg', BarcodeService::generateBarcode('#00001'));
        $this->assertStringContainsString('<svg', BarcodeService::generateBarcode('MEM00001'));
    }

    public function test_generate_barcode_returns_empty_string_for_blank_input(): void
    {
        $this->assertSame('', BarcodeService::generateBarcode(''));
        $this->assertSame('', BarcodeService::generateBarcode('   '));
    }
}
