<?php

/**
 * tests/HolidayControllerTest.php
 * Unit tests for HolidayController class
 * Tests: show(), sync()
 */

namespace Tests;

use App\Controllers\HolidayController;
use App\Services\HolidayService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class HolidayControllerTest extends TestCase
{
    private HolidayController $controller;
    private MockObject|HolidayService $serviceMock;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(HolidayService::class);
        $this->controller = new HolidayController($this->serviceMock);
    }

    /**
     * Test: show() returns holidays from service
     */
    public function testShowReturnsHolidaysFromService(): void
    {
        $expectedHolidays = ['2024-12-25', '2025-01-01'];
        $this->serviceMock->method('getAllHolidays')->willReturn($expectedHolidays);

        $result = $this->controller->show();

        $this->assertEqual($result['holidays'], $expectedHolidays);
        $this->assertEmpty($result['errors']);
        $this->assertEmpty($result['successes']);
    }

    /**
     * Test: show() handles service exceptions gracefully
     */
    public function testShowHandlesServiceException(): void
    {
        $this->serviceMock->method('getAllHolidays')
            ->willThrowException(new \Exception('Database connection failed'));

        $result = $this->controller->show();

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Failed to load holidays', $result['errors'][0]);
        $this->assertEqual($result['holidays'], ['']);
    }

    /**
     * Test: sync() rejects empty dates
     */
    public function testSyncRejectsEmptyDates(): void
    {
        $result = $this->controller->sync([]);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('No dates provided', $result['errors'][0]);
    }

    /**
     * Test: sync() calls service and returns success
     */
    public function testSyncCallsServiceAndReturnsSuccess(): void
    {
        $dates = ['2024-12-25', '2025-01-01'];
        $stats = [
            'inserted' => 2,
            'deleted' => 0,
            'message' => '2 inserted.',
        ];

        $this->serviceMock->method('syncHolidays')->willReturn($stats);
        $this->serviceMock->method('getAllHolidays')->willReturn($dates);

        $result = $this->controller->sync($dates);

        $this->assertNotEmpty($result['successes']);
        $this->assertStringContainsString('2 inserted', $result['successes'][0]);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test: sync() handles service exceptions
     */
    public function testSyncHandlesServiceException(): void
    {
        $dates = ['2024-12-25'];
        $this->serviceMock->method('syncHolidays')
            ->willThrowException(new \Exception('Sync failed'));

        $result = $this->controller->sync($dates);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Failed to sync dates', $result['errors'][0]);
    }

    /**
     * Test: sync() returns holidays after sync
     */
    public function testSyncReturnsHolidaysAfterSync(): void
    {
        $dates = ['2024-12-25'];
        $stats = ['inserted' => 1, 'deleted' => 0, 'message' => '1 inserted.'];
        $allHolidays = ['2024-12-25'];

        $this->serviceMock->method('syncHolidays')->willReturn($stats);
        $this->serviceMock->method('getAllHolidays')->willReturn($allHolidays);

        $result = $this->controller->sync($dates);

        $this->assertEqual($result['holidays'], $allHolidays);
        $this->assertNotEmpty($result['successes']);
    }

    /**
     * Test: addError() adds error and returns self for chaining
     */
    public function testAddErrorReturnsChainableController(): void
    {
        $result = $this->controller->addError('Test error');

        $this->assertInstanceOf(HolidayController::class, $result);
        $this->assertContains('Test error', $this->controller->getErrors());
    }

    /**
     * Test: getErrors() returns all errors
     */
    public function testGetErrorsReturnsAllErrors(): void
    {
        $this->controller->addError('Error 1');
        $this->controller->addError('Error 2');

        $errors = $this->controller->getErrors();

        $this->assertCount(2, $errors);
        $this->assertContains('Error 1', $errors);
        $this->assertContains('Error 2', $errors);
    }

    /**
     * Test: getSuccesses() returns all success messages
     */
    public function testGetSuccessesReturnsSuccessMessages(): void
    {
        $dates = ['2024-12-25'];
        $stats = ['inserted' => 1, 'deleted' => 0, 'message' => '1 inserted.'];

        $this->serviceMock->method('syncHolidays')->willReturn($stats);
        $this->serviceMock->method('getAllHolidays')->willReturn($dates);

        $this->controller->sync($dates);
        $successes = $this->controller->getSuccesses();

        $this->assertCount(1, $successes);
        $this->assertStringContainsString('1 inserted', $successes[0]);
    }

    /**
     * Test: sync() with multiple dates
     */
    public function testSyncWithMultipleDateChanges(): void
    {
        $dates = ['2024-12-25', '2025-01-01', '2025-02-14'];
        $stats = ['inserted' => 3, 'deleted' => 1, 'message' => '3 inserted and 1 deleted.'];

        $this->serviceMock->method('syncHolidays')->willReturn($stats);
        $this->serviceMock->method('getAllHolidays')->willReturn($dates);

        $result = $this->controller->sync($dates);

        $this->assertNotEmpty($result['successes']);
        $this->assertStringContainsString('3 inserted and 1 deleted', $result['successes'][0]);
    }

    /**
     * Helper: Assert two values are equal
     */
    private function assertEqual($actual, $expected): void
    {
        $this->assertEquals($expected, $actual);
    }
}
