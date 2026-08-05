<?php

use PHPUnit\Framework\TestCase;

class UnitsConfigTest extends TestCase
{
    /**
     * @test
     */
    public function units_list_includes_expected_energy_distance_pressure_and_volume_units(): void
    {
        include_once 'Lib/units.php';

        $this->assertTrue(defined('UNITS'));

        $unitsByShort = [];
        foreach (UNITS as $unit) {
            $unitsByShort[$unit['short']] = $unit['long'];
        }

        $this->assertSame('Megawatt Hour', $unitsByShort['MWh'] ?? null);
        $this->assertSame('Liters', $unitsByShort['L'] ?? null);
        $this->assertSame('Kilometre', $unitsByShort['km'] ?? null);
        $this->assertSame('Mile', $unitsByShort['mi'] ?? null);
        $this->assertSame('Bar', $unitsByShort['bar'] ?? null);
        $this->assertSame('Pounds per Square Inch', $unitsByShort['psi'] ?? null);
    }
}
