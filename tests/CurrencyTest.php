<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/currency.php';

class CurrencyTest extends TestCase
{
    public function testFormatCurrencyBRL()
    {
        $this->assertStringContainsString('R$', format_currency(1234.56, 'BRL'));
    }

    public function testFormatCurrencyUSD()
    {
        $out = format_currency(1234.56, 'USD');
        $this->assertStringContainsString('$', $out);
    }

    public function testFormatCurrencyEUR()
    {
        $out = format_currency(1234.56, 'EUR');
        $this->assertStringContainsString('€', $out);
    }

    public function testFormatCurrencyZero()
    {
        $this->assertStringContainsString('0', format_currency(0, 'BRL'));
    }
}
