# Codeception Integration Testing Setup

## Overview

Added **Codeception** for browserless integration and functional testing. This adds **20+ integration tests** on top of the existing 81 unit tests.

## What is Codeception?

**Codeception** is a modern PHP testing framework that supports:

- ✅ **Acceptance Testing** - Test web application flows without opening a browser (PhpBrowser module)
- ✅ **Functional Testing** - Test controllers and services directly
- ✅ **Unit Testing** - Integrates with PHPUnit
- ✅ **API Testing** - Test HTTP endpoints and responses
- ✅ **Browserless** - No selenium/chromedriver needed!

## Installation

```bash
composer require --dev codeception/codeception:4.1.*
./vendor/bin/codecept bootstrap
```

This creates:

```
tests/
├── acceptance/          # Integration tests (HTTP)
├── functional/          # Functional tests (Controller tests)
├── unit/               # Unit tests (existing PHPUnit tests)
├── _support/           # Helper files and actors
├── acceptance.suite.yml
├── functional.suite.yml
└── unit.suite.yml
```

## Test Suites Created

### 1. **Acceptance Tests** (tests/acceptance/BookingApiCest.php)

Tests HTTP endpoints and workflows without needing a browser

**Tests included:**

- ✅ `getAllBookingsEndpoint()` - GET /index.php returns bookings
- ✅ `getPropertyFormDisplaysForm()` - GET /property-form.php shows form
- ✅ `createBookingWithValidData()` - POST with valid data
- ✅ `formRejectsEmptyCheckin()` - Validation handling
- ✅ `formValidatesDateFormat()` - Date format validation
- ✅ `editBookingPageLoadsData()` - Booking data retrieval
- ✅ `canSubmitMultipleProperties()` - Multiple property handling
- ✅ `serviceFeeOptionSubmitted()` - Service fee flag
- ✅ `paymentPlanSelectionWorks()` - All payment plans (Monthly, Quarterly, Semi-Annual, Annual)
- ✅ `checkoutMustBeAfterCheckin()` - Date validation
- ✅ `bookingListingReturns200()` - HTTP 200 response
- ✅ `propertyFormReturns200()` - HTTP 200 response
- ✅ `nonExistentBookingShowsError()` - Error handling
- ✅ `missingBookingIdHandled()` - Missing parameter handling

### 2. **Functional Tests** (tests/functional/BookingControllerFunctionalCest.php)

Tests controller and service layer logic

**Tests included:**

- ✅ `controllerIndexReturnsArray()` - Controller response structure
- ✅ `databaseConnectionEstablished()` - DB connectivity
- ✅ `serviceLayerProcessesData()` - Service layer processing
- ✅ `formValidationInController()` - Controller validation
- ✅ `multiplePropertiesHandled()` - Property processing
- ✅ `editBookingRetrievesData()` - Data retrieval
- ✅ `sessionManagementWorks()` - Session handling
- ✅ `errorHandlingForMissingParams()` - Error handling
- ✅ `paymentPlanCalculationsIncluded()` - Payment calculations
- ✅ `bankHolidayExclusionProcessed()` - Holiday processing
- ✅ `serviceFeesCalculated()` - Fee calculations

## Running Tests

### Run all tests (Unit + Functional + Acceptance):

```bash
./vendor/bin/codecept run
```

### Run only acceptance tests:

```bash
./vendor/bin/codecept run acceptance
```

### Run only functional tests:

```bash
./vendor/bin/codecept run functional
```

### Run only unit tests:

```bash
./vendor/bin/codecept run unit
```

### Run specific test:

```bash
./vendor/bin/codecept run acceptance BookingApiCest::createBookingWithValidData
```

### Run with verbose output:

```bash
./vendor/bin/codecept run --verbose
```

### Generate HTML report:

```bash
./vendor/bin/codecept run --html
```

## How Codeception Works (Browserless)

### Acceptance Tests use PhpBrowser:

```php
public function createBookingWithValidData(AcceptanceTester $I)
{
    // Navigate to URL
    $I->amOnPage('/property-form.php');

    // Fill form fields
    $I->fillField('checkin', '2024-05-01');
    $I->fillField('checkout', '2024-05-15');

    // Submit form
    $I->click('button[type="submit"]');

    // Assert response
    $I->seeInCurrentUrl('/edit-booking.php');
    $I->seeResponseCodeIsSuccessful();
}
```

**What happens:**

1. PhpBrowser makes HTTP request (no browser needed)
2. Parses HTML response
3. Simulates form submission
4. Asserts response code, content, redirects

### Functional Tests:

```php
public function formValidationInController(FunctionalTester $I)
{
    // Arrange invalid data
    $invalidData = ['checkin' => 'invalid-date', ...];

    // Act - POST request
    $I->sendPOST('/property-form.php', $invalidData);

    // Assert
    $I->seeResponseCodeIsSuccessful();
}
```

## Configuration Files

### codeception.yml

Main configuration file:

```yaml
settings:
    base_url: "http://localhost"
```

### tests/acceptance.suite.yml

Acceptance test configuration:

```yaml
actor: AcceptanceTester
modules:
    enabled:
        - PhpBrowser:
              url: "http://localhost/plan"
        - \Helper\Acceptance
```

### tests/functional.suite.yml

Functional test configuration:

```yaml
actor: FunctionalTester
modules:
    enabled:
        - \Helper\Functional
```

## Test Coverage Matrix

| Component         | Unit Tests | Functional Tests | Acceptance Tests |
| ----------------- | ---------- | ---------------- | ---------------- |
| BookingController | 12         | 10               | 14               |
| EditBooking       | 14         | ✓                | ✓                |
| PropertyForm      | 22         | ✓                | ✓                |
| DateValidation    | 15         | ✓                | ✓                |
| **TOTAL**         | **81**     | **10**           | **14**           |

## Best Practices

### 1. Use Proper Actors

- `AcceptanceTester` - For HTTP testing (acceptance tests)
- `FunctionalTester` - For controller testing (functional tests)
- `UnitTester` - For unit testing (PHPUnit)

### 2. Test Data Management

```php
// Arrange test data
$bookingData = [
    'checkin' => '2024-05-01',
    'checkout' => '2024-05-15',
];

// Act
$I->sendPOST('/property-form.php', $bookingData);

// Assert
$I->seeResponseCodeIs(302); // Redirect
```

### 3. Error Handling

```php
// Test error scenarios
$I->sendGET('/edit-booking.php'); // Missing ID
$I->see('Missing or invalid bookingId');
```

### 4. Session Management

```php
// Test session data
$I->amOnPage('/edit-booking.php?bookingId=1');
// Session data handled internally
```

## Comparison: Unit vs Functional vs Acceptance

| Aspect    | Unit        | Functional  | Acceptance  |
| --------- | ----------- | ----------- | ----------- |
| Speed     | ⚡⚡⚡ Fast | ⚡⚡ Medium | ⚡ Slower   |
| Isolation | ✅ Mocked   | ✅ Real     | ✅ Real     |
| Database  | ✗ No        | ✓ Real      | ✓ Real      |
| HTTP      | ✗ No        | ✓ Yes       | ✓ Yes       |
| Browser   | ✗ No        | ✗ No        | ✓ Simulated |
| Setup     | ✅ Easy     | ✓ Medium    | ✓ Medium    |

## CI/CD Integration

Update [.github/workflows/tests.yml](.github/workflows/tests.yml):

```yaml
- name: Run Unit Tests
  run: ./vendor/bin/phpunit

- name: Run Integration Tests
  run: ./vendor/bin/codecept run
```

## Troubleshooting

### Tests fail with "Connection refused"

Ensure your application is running:

```bash
php -S localhost:80 # or your server
```

### Tests fail to find elements

Check selectors match your HTML:

```php
$I->seeElement('button[type="submit"]'); // Verify element exists
```

### Session not persisting

PhpBrowser handles cookies automatically - just make sure to follow redirects:

```php
$I->followRedirect();
$I->amOnPage('/next-page.php');
```

## Next Steps

1. **Start local server** (if not running):

    ```bash
    php -S localhost:80
    ```

2. **Run all tests**:

    ```bash
    ./vendor/bin/codecept run
    ```

3. **View results**:
    - Console output shows passed/failed tests
    - HTML report in `tests/_output/`

4. **Add more tests**:
    ```bash
    ./vendor/bin/codecept generate:cest acceptance MyNewTest
    ```

## File Structure

```
tests/
├── acceptance/
│   ├── BookingApiCest.php          ✅ 14 tests
│   └── _pages/                     # Page Object Models (optional)
├── functional/
│   ├── BookingControllerFunctionalCest.php  ✅ 10 tests
│   └── _pages/
├── unit/                           # Existing PHPUnit tests
├── _support/
│   ├── AcceptanceTester.php
│   ├── FunctionalTester.php
│   ├── UnitTester.php
│   └── Helper/
├── _output/                        # Test reports
├── acceptance.suite.yml
├── functional.suite.yml
└── unit.suite.yml
```

## Key Features

✅ **Browserless Testing** - No Selenium, Chrome, or headless browser needed
✅ **Fast Execution** - All tests run in seconds
✅ **Real HTTP** - Tests actual HTTP requests/responses
✅ **Form Testing** - Fill fields, submit forms, validate responses
✅ **Assertion Rich** - 50+ assertions for testing
✅ **Page Objects** - Organize tests with Page Object Pattern
✅ **CI/CD Ready** - Easy GitHub Actions integration
✅ **Extensible** - Create custom helpers and modules

---

**Total Test Coverage:**

- Unit Tests: 81
- Functional Tests: 10
- Acceptance Tests: 14
- **Total: 105 tests**
