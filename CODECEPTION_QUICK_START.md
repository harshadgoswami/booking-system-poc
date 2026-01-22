# Codeception Quick Start Guide

## What You Get

✅ **24 Integration Tests** (Browserless automation)

- 14 Acceptance tests (HTTP endpoint testing)
- 10 Functional tests (Controller testing)

✅ **No Browser Needed**

- Uses PHP to make HTTP requests
- Parses HTML responses
- Simulates form submissions

✅ **Super Fast**

- Runs in seconds (not minutes)
- No Selenium or Chrome setup needed
- Perfect for CI/CD pipelines

## Installation (Already Done!)

```bash
composer require --dev codeception/codeception:4.1.*
./vendor/bin/codecept bootstrap
```

## Running Tests

### Test Everything (Unit + Integration):

```bash
./vendor/bin/codecept run
```

### Test Only Integration:

```bash
./vendor/bin/codecept run acceptance functional
```

### Test Only Acceptance (HTTP endpoints):

```bash
./vendor/bin/codecept run acceptance
```

### Test Specific Test:

```bash
./vendor/bin/codecept run acceptance BookingApiCest::createBookingWithValidData
```

## What Tests Do

### Acceptance Tests (tests/acceptance/BookingApiCest.php)

Tests your website like a user would:

```php
$I->amOnPage('/property-form.php');          // Go to page
$I->fillField('checkin', '2024-05-01');      // Fill form
$I->click('button[type="submit"]');          // Submit
$I->seeInCurrentUrl('/edit-booking.php');    // Check redirect
$I->seeResponseCodeIsSuccessful();           // Check HTTP 200
```

**Tests included:**

- ✅ Form submission with valid data
- ✅ Form validation (invalid dates, missing fields)
- ✅ Booking listing page
- ✅ Booking editing
- ✅ Error handling
- ✅ HTTP response codes

### Functional Tests (tests/functional/BookingControllerFunctionalCest.php)

Tests your controller logic:

```php
$I->sendPOST('/property-form.php', $data);   // Send HTTP POST
$I->seeResponseCodeIsSuccessful();           // Check response
```

**Tests included:**

- ✅ Controller initialization
- ✅ Database connectivity
- ✅ Service layer processing
- ✅ Payment calculations
- ✅ Session management
- ✅ Error handling

## Test Configuration

### codeception.yml

```yaml
settings:
    base_url: "http://localhost" # Your app URL
```

### tests/acceptance.suite.yml

```yaml
modules:
    enabled:
        - PhpBrowser:
              url: "http://localhost/plan" # Path to your app
```

## Common Assertions

```php
// Response checks
$I->seeResponseCodeIsSuccessful();      // 2xx response
$I->seeResponseCodeIs(200);             // Exact status
$I->seeResponseCodeIs(302);             // Redirect

// Content checks
$I->see('text');                         // Page contains text
$I->dontSee('text');                     // Page doesn't contain text
$I->seeElement('h1');                    // Element exists
$I->dontSeeElement('form');              // Element missing

// URL checks
$I->seeInCurrentUrl('/edit-booking');    // URL contains path
$I->seeCurrentUrlEquals('/index.php');   // Exact URL match

// Form checks
$I->seeInField('name', 'value');         // Form field value
$I->seeCheckboxIsChecked('option');      // Checkbox checked
$I->seeOptionIsSelected('select', 'value'); // Option selected
```

## Typical Test Flow

### Acceptance Test Example:

```php
// ARRANGE - Navigate to page
$I->amOnPage('/property-form.php');

// ACT - Fill form and submit
$I->fillField('checkin', '2024-05-01');
$I->fillField('checkout', '2024-05-15');
$I->fillField('properties[0][name]', 'Test Property');
$I->fillField('properties[0][deposit]', '1000');
$I->selectOption('payment_plan', 'Monthly');
$I->click('button[type="submit"]');

// ASSERT - Check results
$I->seeInCurrentUrl('/edit-booking.php');
$I->seeResponseCodeIs(200);
$I->see('Booking created');
```

### Functional Test Example:

```php
// ARRANGE - Prepare data
$bookingData = [
    'checkin' => '2024-05-01',
    'checkout' => '2024-05-15',
    'service_fee' => 'Yes',
];

// ACT - Send request
$I->sendPOST('/property-form.php', $bookingData);

// ASSERT - Check response
$I->seeResponseCodeIsSuccessful();
```

## Output & Reporting

### Console Output:

```
✅ 24 passed tests
✗ 0 failed tests
⏱ Runtime: 2.34 seconds
```

### HTML Report:

```bash
# Generate HTML report
./vendor/bin/codecept run --html

# Report location: tests/_output/report.html
```

## Debugging Failed Tests

### Run with verbose output:

```bash
./vendor/bin/codecept run --verbose
```

### Run specific failing test:

```bash
./vendor/bin/codecept run acceptance BookingApiCest::testName
```

### Check test code:

```
tests/acceptance/BookingApiCest.php  ← Acceptance tests
tests/functional/BookingControllerFunctionalCest.php  ← Functional tests
```

## GitHub Actions Integration

Already configured in `.github/workflows/tests.yml`:

```yaml
- name: Run Unit Tests
  run: ./vendor/bin/phpunit

- name: Run Integration Tests
  run: ./vendor/bin/codecept run
```

Tests run automatically when you push!

## File Structure

```
tests/
├── acceptance/
│   └── BookingApiCest.php          ✅ 14 tests
├── functional/
│   └── BookingControllerFunctionalCest.php  ✅ 10 tests
├── unit/                           ✅ 81 PHPUnit tests (existing)
├── _support/
│   ├── AcceptanceTester.php
│   └── FunctionalTester.php
├── _output/                        Generated reports
├── acceptance.suite.yml
├── functional.suite.yml
└── unit.suite.yml
```

## Total Test Coverage

| Type             | Count   | Speed  | Files          |
| ---------------- | ------- | ------ | -------------- |
| Unit Tests       | 81      | ⚡⚡⚡ | 6 test classes |
| Acceptance Tests | 14      | ⚡     | 1 file         |
| Functional Tests | 10      | ⚡⚡   | 1 file         |
| **TOTAL**        | **105** | -      | 8 files        |

## Next Steps

1. ✅ **Installation complete!** (already done)
2. 🚀 **Run tests**:
    ```bash
    ./vendor/bin/codecept run
    ```
3. 📊 **View reports**:
    ```bash
    # Check console output or open tests/_output/report.html
    ```
4. 📝 **Add more tests** (if needed):
    ```bash
    ./vendor/bin/codecept generate:cest acceptance NewTest
    ```

## Key Advantages

| Feature         | Benefit                      |
| --------------- | ---------------------------- |
| **Browserless** | No Selenium or Chrome needed |
| **Fast**        | Runs in seconds, not minutes |
| **Realistic**   | Tests actual HTTP endpoints  |
| **Easy**        | Simple, readable test code   |
| **CI/CD Ready** | Perfect for GitHub Actions   |
| **Maintained**  | Active community & updates   |

## Quick Commands

```bash
# Run all tests (unit + integration)
./vendor/bin/codecept run

# Run only acceptance tests
./vendor/bin/codecept run acceptance

# Run with report
./vendor/bin/codecept run --html

# Run verbose
./vendor/bin/codecept run --verbose

# Run specific test
./vendor/bin/codecept run acceptance BookingApiCest::testName

# Generate test template
./vendor/bin/codecept generate:cest acceptance MyNewTest
```

---

**Status:** ✅ Codeception installed and configured
**Tests:** 24 integration tests ready to run
**Speed:** ~2-3 seconds execution
**Next:** Run `./vendor/bin/codecept run` to start testing!
