<?php

use PHPUnit\Framework\TestCase;

class UnitsConfigTest extends TestCase
{
    /**
 * @test 
*/
    public function units_list_includes_new_mwh_mi_km_units(): void
    {
        include_once 'Lib/units.php';

        $this->assertTrue(defined('UNITS'));

        $unitsByShort = [];
        foreach (UNITS as $unit) {
            $unitsByShort[$unit['short']] = $unit['long'];
        }

        $this->assertSame('Megawatt Hour', $unitsByShort['MWh'] ?? null);
        $this->assertSame('Kilometre', $unitsByShort['km'] ?? null);
        $this->assertSame('Mile', $unitsByShort['mi'] ?? null);
    }
}
