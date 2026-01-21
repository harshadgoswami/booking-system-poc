# Test Examples & Best Practices

## 📚 Real-World Test Examples

### Example 1: Testing Holiday Sync with New Dates

**Scenario:** User submits form with 3 new holidays

**Test Code:**

```php
public function testSyncHolidaysInsertsNewDates(): void
{
    // ARRANGE: Set up existing and submitted dates
    $existingDates = ['2024-12-25'];
    $submittedDates = ['2024-12-25', '2024-12-26', '2025-01-01'];

    // Configure mock to return existing dates, then inserted count
    $this->repositoryMock->method('getAllDates')
        ->willReturn($existingDates);
    $this->repositoryMock->method('saveBatch')
        ->willReturn(2); // 2 new dates inserted

    // ACT: Call service method
    $result = $this->service->syncHolidays($submittedDates);

    // ASSERT: Verify results
    $this->assertEqual($result['inserted'], 2);
    $this->assertEqual($result['deleted'], 0);
    $this->assertStringContainsString('2 inserted', $result['message']);
}
```

**What it tests:**

- ✅ New holidays are identified
- ✅ Correct count returned
- ✅ Success message generated
- ✅ No holidays deleted

---

### Example 2: Testing Date Validation

**Scenario:** User enters mix of valid and invalid dates

**Test Code:**

```php
public function testValidateDatesSeperatesValidAndInvalid(): void
{
    // ARRANGE: Prepare mixed input
    $dates = [
        '2024-12-25',   // Valid
        'invalid',      // Invalid
        '2025-01-01',   // Valid
        '',             // Invalid (empty)
        '13-25-2024'    // Invalid (wrong format)
    ];

    // ACT: Validate dates
    $result = $this->service->validateDates($dates);

    // ASSERT: Check separation
    $this->assertCount(2, $result['valid']);
    $this->assertCount(3, $result['invalid']);
    $this->assertContains('2024-12-25', $result['valid']);
    $this->assertContains('2025-01-01', $result['valid']);
    $this->assertContains('invalid', $result['invalid']);
}
```

**What it tests:**

- ✅ Valid dates extracted correctly
- ✅ Invalid dates identified
- ✅ Empty strings handled
- ✅ Wrong format rejected

---

### Example 3: Testing Error Handling

**Scenario:** Database connection fails when loading holidays

**Test Code:**

```php
public function testShowHandlesServiceException(): void
{
    // ARRANGE: Mock service to throw exception
    $this->serviceMock->method('getAllHolidays')
        ->willThrowException(
            new \Exception('Database connection failed')
        );

    // ACT: Call controller
    $result = $this->controller->show();

    // ASSERT: Verify error response
    $this->assertNotEmpty($result['errors']);
    $this->assertStringContainsString(
        'Failed to load holidays',
        $result['errors'][0]
    );
    $this->assertEqual($result['holidays'], ['']);
}
```

**What it tests:**

- ✅ Exceptions caught gracefully
- ✅ Error message generated
- ✅ Safe fallback returned
- ✅ User sees helpful message

---

### Example 4: Testing Date Format Normalization

**Scenario:** User submits dates with whitespace and duplicates

**Test Code:**

```php
public function testNormalizeDatesRemovesDuplicatesAndTrims(): void
{
    // ARRANGE: Prepare dates with issues
    $input = [
        '  2024-12-25  ',  // Extra whitespace
        '2024-12-25',      // Duplicate
        '2025-01-01',
        '\t2025-01-01\n',  // Tab and newline
    ];

    // ACT: Normalize dates
    $result = DateValidator::normalizeDates($input);

    // ASSERT: Verify cleanup
    $this->assertCount(2, $result);
    $this->assertContains('2024-12-25', $result);
    $this->assertContains('2025-01-01', $result);
}
```

**What it tests:**

- ✅ Whitespace removed
- ✅ Duplicates eliminated
- ✅ Special characters handled
- ✅ Result is clean array

---

### Example 5: Testing Leap Year Validation

**Scenario:** Edge case - February 29 on leap vs non-leap year

**Test Code:**

```php
public function testIsValidDateHandlesLeapYears(): void
{
    // ARRANGE: Prepare leap year and non-leap year dates

    // ACT & ASSERT: Test valid leap year date
    $this->assertTrue(
        DateValidator::isValidDate('2020-02-29'),
        '2020 is a leap year'
    );

    // ACT & ASSERT: Test invalid non-leap year date
    $this->assertFalse(
        DateValidator::isValidDate('2021-02-29'),
        '2021 is not a leap year'
    );

    // ACT & ASSERT: Test year boundary
    $this->assertTrue(
        DateValidator::isValidDate('2024-12-31')
    );
    $this->assertTrue(
        DateValidator::isValidDate('2025-01-01')
    );
}
```

**What it tests:**

- ✅ Leap year calculations correct
- ✅ Feb 29 validation accurate
- ✅ Year boundaries handled
- ✅ Edge cases covered

---

## 🎯 Best Practices

### 1. Use Descriptive Test Names

❌ **Bad:**

```php
public function test1(): void { ... }
public function testIt(): void { ... }
public function testService(): void { ... }
```

✅ **Good:**

```php
public function testSyncHolidaysInsertsNewDatesCorrectly(): void { ... }
public function testNormalizeDatesRemovesDuplicates(): void { ... }
public function testShowHandlesServiceExceptionGracefully(): void { ... }
```

**Why:** Test names should describe what's being tested and expected outcome.

---

### 2. Follow AAA Pattern (Arrange-Act-Assert)

✅ **Good Structure:**

```php
public function testExample(): void
{
    // ARRANGE: Set up test data and mocks
    $input = ['2024-12-25'];
    $this->mockRepository->method('save')->willReturn(true);

    // ACT: Execute the code being tested
    $result = $this->service->save($input);

    // ASSERT: Verify the results
    $this->assertTrue($result);
}
```

**Why:** Improves readability and makes intention clear.

---

### 3. One Assertion Per Test (When Possible)

❌ **Too Many Assertions:**

```php
public function testSync(): void
{
    $result = $this->service->sync(['2024-12-25']);

    $this->assertEqual($result['inserted'], 1);
    $this->assertEqual($result['deleted'], 0);
    $this->assertStringContainsString('inserted', $result['message']);
    $this->assertIsArray($result);
    // ... 5 more assertions
}
```

✅ **Focused Tests:**

```php
public function testSyncReturnsInsertedCount(): void
{
    $result = $this->service->sync(['2024-12-25']);
    $this->assertEqual($result['inserted'], 1);
}

public function testSyncGeneratesSuccessMessage(): void
{
    $result = $this->service->sync(['2024-12-25']);
    $this->assertStringContainsString('inserted', $result['message']);
}
```

**Why:** Easier to identify which assertion failed; clearer test purpose.

---

### 4. Test Edge Cases

✅ **Cover Edge Cases:**

```php
// Boundary values
DateValidator::isValidDate('0000-00-00');  // Invalid
DateValidator::isValidDate('9999-12-31');  // Valid

// Leap years
DateValidator::isValidDate('2020-02-29');  // Valid
DateValidator::isValidDate('2021-02-29');  // Invalid

// Empty/null
DateValidator::isValidDate('');            // Invalid
DateValidator::isValidDate(null);          // Invalid
```

**Why:** Catches bugs that happy-path tests miss.

---

### 5. Use Mocks to Isolate Units

❌ **Coupled to Database:**

```php
public function testService(): void
{
    $pdo = new PDO('sqlite::memory:');
    $repo = new HolidayRepository($pdo);
    $service = new HolidayService($repo);

    // Slow, brittle, not truly unit testing
    $result = $service->getAllHolidays();
}
```

✅ **Isolated with Mocks:**

```php
public function testServiceGetAllHolidays(): void
{
    // Mock the repository
    $repoMock = $this->createMock(HolidayRepository::class);
    $repoMock->method('getAllDates')
        ->willReturn(['2024-12-25']);

    // Test service in isolation
    $service = new HolidayService($repoMock);
    $result = $service->getAllHolidays();

    $this->assertEqual($result, ['2024-12-25']);
}
```

**Why:** Tests run fast, reliable, and test only the unit.

---

### 6. Test Both Success and Failure

✅ **Test Happy Path AND Errors:**

```php
// Success case
public function testSyncWithValidDates(): void
{
    $result = $this->controller->sync(['2024-12-25']);
    $this->assertNotEmpty($result['successes']);
}

// Failure case
public function testSyncWithEmptyDates(): void
{
    $result = $this->controller->sync([]);
    $this->assertNotEmpty($result['errors']);
}

// Exception case
public function testSyncWithServiceException(): void
{
    $this->serviceMock->method('sync')
        ->willThrowException(new \Exception('Failed'));

    $result = $this->controller->sync(['2024-12-25']);
    $this->assertNotEmpty($result['errors']);
}
```

**Why:** Incomplete error testing leads to production bugs.

---

### 7. Use Meaningful Mock Return Values

✅ **Realistic Mock Data:**

```php
// Good: Realistic data
$this->repositoryMock->method('getAllDates')
    ->willReturn([
        '2024-12-25',
        '2025-01-01',
        '2025-04-18',
    ]);

// Also good: Use fixtures
private array $testHolidays = [
    '2024-12-25',
    '2025-01-01',
];

$this->repositoryMock->method('getAllDates')
    ->willReturn($this->testHolidays);
```

**Why:** Catches bugs that contrived data wouldn't reveal.

---

## 📋 Test Checklist

Before considering tests complete:

- [ ] All happy path scenarios covered
- [ ] Error cases tested
- [ ] Edge cases tested (boundaries, empty, null)
- [ ] Exceptions handled gracefully
- [ ] Mock objects used for isolation
- [ ] Test names describe intent
- [ ] AAA pattern followed
- [ ] No database dependencies in unit tests
- [ ] Fast execution (< 1 second total)
- [ ] 80%+ code coverage achieved

---

## 🔍 Common Test Mistakes

### ❌ Mistake 1: Overly Complex Tests

```php
// Bad: Too much setup
public function testComplicated(): void
{
    $mock1 = $this->createMock(Class1::class);
    $mock2 = $this->createMock(Class2::class);
    $mock3 = $this->createMock(Class3::class);

    $mock1->method('a')->willReturn($mock2);
    $mock2->method('b')->willReturn($mock3);
    $mock3->method('c')->willReturnCallback(function($x) {
        return $x + $this->complexCalculation($x);
    });

    // Actual test buried in complexity
}
```

**Fix:** Break into smaller, focused tests.

### ❌ Mistake 2: Order-Dependent Tests

```php
// Bad: Tests depend on execution order
public function testA(): void
{
    // Sets up global state
    self::$database->insert(...);
}

public function testB(): void
{
    // Assumes testA ran first
    $data = self::$database->find(...);
}
```

**Fix:** Each test should be independent.

### ❌ Mistake 3: Testing Implementation, Not Behavior

```php
// Bad: Tests how code works, not what it does
public function testSync(): void
{
    $this->assertTrue($this->repo->called);  // Implementation detail
}

// Good: Tests business behavior
public function testSyncAddsNewHolidays(): void
{
    $result = $this->service->sync(['2024-12-25']);
    $this->assertEqual($result['inserted'], 1);  // Business result
}
```

---

## 🚀 Running Tests Effectively

### Run All Tests

```bash
./vendor/bin/phpunit
```

### Run With Specific Options

```bash
# Verbose output
./vendor/bin/phpunit --verbose

# Stop on first failure
./vendor/bin/phpunit --stop-on-failure

# Generate report
./vendor/bin/phpunit --coverage-html coverage

# Run specific test
./vendor/bin/phpunit tests/HolidayServiceTest.php::testSyncHolidaysInsertsNewDates
```

---

## 📊 Coverage Targets

| Target    | Coverage % | Effort    |
| --------- | ---------- | --------- |
| Minimal   | 60%        | Low       |
| Good      | 80%        | Medium    |
| Excellent | 90%+       | High      |
| Perfect   | 100%       | Very High |

**Recommendation:** Aim for **80%+** coverage, focus on critical paths.

---

## 💡 Summary

✅ **DO:**

- Use descriptive test names
- Follow AAA pattern
- Test edge cases and errors
- Use mocks for isolation
- Keep tests focused and simple
- Verify both success and failure
- Run tests frequently

❌ **DON'T:**

- Write complex setup code
- Make tests order-dependent
- Test library code
- Skip error scenarios
- Use contrived test data
- Test implementation details
- Ignore code coverage

---

**Remember:** Good tests are an investment that pays dividends through confidence and faster development! 🎯
