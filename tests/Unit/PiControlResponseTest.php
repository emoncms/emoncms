<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Modules/admin/admin_controller.php';
require_once __DIR__ . '/../../Modules/admin/info/SystemInfoModel.php';

final class PiControlResponseTest extends TestCase
{
    public static function powerRequests(): array
    {
        return [
            'shutdown JSON' => ['shutdown', 'json', 'json'],
            'reboot JSON' => ['reboot', 'json', 'json'],
            'legacy shutdown' => ['shutdown', 'html', 'text'],
            'legacy reboot' => ['reboot', 'html', 'text'],
        ];
    }

    #[DataProvider('powerRequests')]
    public function test_response_preserves_requested_json_format(
        string $action,
        string $requestedFormat,
        string $expectedFormat
    ): void {
        // Exercise the real controller without ever shutting down a Pi.
        if ((new SystemInfoModel())->isPi()) {
            $this->markTestSkipped('This test must run on a non-Pi host.');
        }

        $previousSession = $GLOBALS['session'] ?? null;
        $previousRoute = $GLOBALS['route'] ?? null;
        $GLOBALS['session'] = ['write' => true, 'admin' => true];
        $GLOBALS['route'] = (object) ['action' => $action, 'format' => $requestedFormat];

        try {
            $result = admin_controller();
            $this->assertSame($expectedFormat, $GLOBALS['route']->format);
            $this->assertSame(
                ucfirst($action) . ' command is only available on Raspberry Pi systems',
                $result
            );
        } finally {
            $GLOBALS['session'] = $previousSession;
            $GLOBALS['route'] = $previousRoute;
        }
    }
}
