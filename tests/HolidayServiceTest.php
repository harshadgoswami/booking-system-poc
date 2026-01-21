<?php

/**
 * tests/HolidayServiceTest.php
 * Unit tests for HolidayService class
 * Tests: getAllHolidays, syncHolidays, validateDates
 */

namespace Tests;

use App\Models\Holiday;
use App\Services\HolidayService;
use App\Repositories\HolidayRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class HolidayServiceTest extends TestCase
{
    private HolidayService $service;
    private MockObject|HolidayRepository $repositoryMock;

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(HolidayRepository::class);
        $this->service = new HolidayService($this->repositoryMock);
    }

    /**
     * Test: getAllHolidays returns existing holidays
     */
    public function testGetAllHolidaysReturnsExistingHolidays(): void
    {
        $expectedDates = ['2024-12-25', '2024-12-26', '2025-01-01'];
        $this->repositoryMock->method('getAllDates')->willReturn($expectedDates);

        $result = $this->service->getAllHolidays();

        $this->assertEqual($result, $expectedDates);
    }

    /**
     * Test: getAllHolidays returns empty array as fallback
     */
    public function testGetAllHolidaysReturnsEmptyArrayWhenNoHolidays(): void
    {
        $this->repositoryMock->method('getAllDates')->willReturn([]);

        $result = $this->service->getAllHolidays();

        $this->assertEqual($result, ['']);
    }

    /**
     * Test: syncHolidays detects no changes correctly
     */
    public function testSyncHolidaysNoChanges(): void
    {
        $existingDates = ['2024-12-25', '2024-12-26'];
        $this->repositoryMock->method('getAllDates')->willReturn($existingDates);

        $result = $this->service->syncHolidays($existingDates);

        $this->assertEqual($result['inserted'], 0);
        $this->assertEqual($result['deleted'], 0);
        $this->assertEqual($result['message'], 'No changes detected.');
    }

    /**
     * Test: syncHolidays inserts new dates correctly
     */
    public function testSyncHolidaysInsertsNewDates(): void
    {
        $existingDates = ['2024-12-25'];
        $submittedDates = ['2024-12-25', '2024-12-26', '2025-01-01'];

        $this->repositoryMock->method('getAllDates')->willReturn($existingDates);
        $this->repositoryMock->method('saveBatch')->willReturn(2);

        $result = $this->service->syncHolidays($submittedDates);

        $this->assertEqual($result['inserted'], 2);
        $this->assertEqual($result['deleted'], 0);
        $this->assertStringContainsString('2 inserted', $result['message']);
    }

    /**
     * Test: syncHolidays deletes removed dates correctly
     */
    public function testSyncHolidaysDeletesRemovedDates(): void
    {
        $existingDates = ['2024-12-25', '2024-12-26', '2025-01-01'];
        $submittedDates = ['2024-12-25'];

        $this->repositoryMock->method('getAllDates')->willReturn($existingDates);
        $this->repositoryMock->method('deleteBatch')->willReturn(2);

        $result = $this->service->syncHolidays($submittedDates);

        $this->assertEqual($result['inserted'], 0);
        $this->assertEqual($result['deleted'], 2);
        $this->assertStringContainsString('2 deleted', $result['message']);
    }

    /**
     * Test: syncHolidays handles mixed insert and delete
     */
    public function testSyncHolidaysMixedInsertAndDelete(): void
    {
        $existingDates = ['2024-12-25', '2024-12-26'];
        $submittedDates = ['2024-12-25', '2025-01-01', '2025-01-02'];

        $this->repositoryMock->method('getAllDates')->willReturn($existingDates);
        $this->repositoryMock->method('saveBatch')->willReturn(2);
        $this->repositoryMock->method('deleteBatch')->willReturn(1);

        $result = $this->service->syncHolidays($submittedDates);

        $this->assertEqual($result['inserted'], 2);
        $this->assertEqual($result['deleted'], 1);
        $this->assertStringContainsString('2 inserted and 1 deleted', $result['message']);
    }

    /**
     * Test: syncHolidays normalizes dates (removes duplicates, invalid dates)
     */
    public function testSyncHolidaysNormalizesDates(): void
    {
        $this->repositoryMock->method('getAllDates')->willReturn([]);
        $this->repositoryMock->method('saveBatch')->willReturn(3);

        $submittedDates = ['2024-12-25', '2024-12-25', 'invalid', '', '2025-01-01', '2025-01-01'];
        $result = $this->service->syncHolidays($submittedDates);

        $this->assertEqual($result['inserted'], 3);
    }

    /**
     * Test: validateDates correctly identifies valid and invalid dates
     */
    public function testValidateDatesSeperatesValidAndInvalid(): void
    {
        $dates = ['2024-12-25', 'invalid', '2025-01-01', '', '13-25-2024'];
        $result = $this->service->validateDates($dates);

        $this->assertCount(2, $result['valid']);
        $this->assertCount(3, $result['invalid']);
        $this->assertContains('2024-12-25', $result['valid']);
        $this->assertContains('2025-01-01', $result['valid']);
    }

    /**
     * Test: validateDates returns empty arrays for empty input
     */
    public function testValidateDatesHandlesEmptyInput(): void
    {
        $result = $this->service->validateDates([]);

        $this->assertEmpty($result['valid']);
        $this->assertEmpty($result['invalid']);
    }

    /**
     * Test: validateDates returns all valid for valid input
     */
    public function testValidateDatesReturnsAllValidForValidDates(): void
    {
        $dates = ['2024-12-25', '2025-01-01', '2025-02-28'];
        $result = $this->service->validateDates($dates);

        $this->assertEqual($result['valid'], $dates);
        $this->assertEmpty($result['invalid']);
    }

    /**
     * Test: syncHolidays throws exception on repository error
     */
    public function testSyncHolidaysHandlesRepositoryException(): void
    {
        $this->repositoryMock->method('getAllDates')
            ->willThrowException(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->service->syncHolidays(['2024-12-25']);
    }

    /**
     * Helper: Assert two arrays are equal
     */
    private function assertEqual($actual, $expected): void
    {
        $this->assertEquals($expected, $actual);
    }
}
