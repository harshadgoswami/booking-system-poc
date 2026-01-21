# Quick Reference Guide

## Class Map

### Models

- **Booking** - `src/Models/Booking.php`
- **Property** - `src/Models/Property.php`
- **Holiday** - `src/Models/Holiday.php`

### Repositories (Data Access)

- **BookingRepository** - `src/Repositories/BookingRepository.php`
- **PropertyRepository** - `src/Repositories/PropertyRepository.php`
- **HolidayRepository** - `src/Repositories/HolidayRepository.php`

### Services (Business Logic)

- **BookingService** - `src/Services/BookingService.php`
- **HolidayService** - `src/Services/HolidayService.php`

### Controllers (Request Handling)

- **BookingController** - `src/Controllers/BookingController.php`
- **HolidayController** - `src/Controllers/HolidayController.php`

### Validators (Input Validation)

- **BookingValidator** - `src/Utils/BookingValidator.php`
- **PropertyValidator** - `src/Utils/PropertyValidator.php`
- **DateValidator** - `src/Utils/DateValidator.php`

### Database

- **DatabaseConnection** - `src/Database/DatabaseConnection.php`

---

## Common Operations

### Creating a Booking

```php
require 'autoloader.php';

use App\Database\DatabaseConnection;
use App\Repositories\BookingRepository;
use App\Repositories\PropertyRepository;
use App\Services\BookingService;

$db = new DatabaseConnection();
$pdo = $db->connect();
$bookingRepo = new BookingRepository($pdo);
$propRepo = new PropertyRepository($pdo);
$service = new BookingService($bookingRepo, $propRepo);

$bookingId = $service->createBooking(
    [
        'checkin' => '2026-02-01',
        'checkout' => '2026-02-15',
        'days' => ['mon', 'tue', 'wed'],
        'payment_plan' => 'Monthly',
    ],
    [
        ['title' => 'Beach House', 'night_price' => 100, 'deposit' => 500]
    ]
);
```

### Retrieving Bookings

```php
$bookings = $service->getAllBookings();
$booking = $service->getBooking($bookingId);
$properties = $service->getBookingProperties($bookingId);
```

### Updating a Booking

```php
$service->updateBooking($bookingId, $bookingData, $propertiesData);
```

### Validating Data

```php
use App\Utils\BookingValidator;
use App\Utils\PropertyValidator;

// Validate dates
$errors = BookingValidator::validateDates('2026-02-01', '2026-02-15');

// Normalize days
$days = BookingValidator::validateDays(['MON', 'TUE', 'wed']);

// Validate property
$propErrors = PropertyValidator::validateProperty($propertyData, 0);
```

### Working with Holidays

```php
use App\Services\HolidayService;
use App\Repositories\HolidayRepository;

$holidayRepo = new HolidayRepository($pdo);
$holidayService = new HolidayService($holidayRepo);

$holidays = $holidayService->getAllHolidays();
$stats = $holidayService->syncHolidays(['2026-12-25', '2026-01-01']);
```

---

## Directory Structure

```
src/
├── Controllers/          Controllers for HTTP requests
├── Database/            Database connection management
├── Models/              Entity classes
├── Repositories/        Data access layer
├── Services/            Business logic layer
└── Utils/              Validators and helpers

views/                   HTML templates
autoloader.php          PSR-4 autoloader
```

---

## Entry Points

| File                | Purpose         | Uses                        |
| ------------------- | --------------- | --------------------------- |
| `index.php`         | List bookings   | BookingController::index()  |
| `property-form.php` | Create booking  | BookingController::create() |
| `edit-booking.php`  | Edit booking    | BookingController::update() |
| `addmore-dates.php` | Manage holidays | HolidayController::sync()   |

---

## Key Methods by Class

### BookingService

```php
createBooking(array $data, array $properties): int
updateBooking(int $id, array $data, array $properties): bool
getBooking(int $id): ?Booking
getAllBookings(): array
getBookingProperties(int $id): array
```

### BookingController

```php
index(): array
create(array $formData): array
update(int $id, array $formData): array
editForm(int $id): array
```

### BookingRepository

```php
save(Booking $booking): int
update(Booking $booking): bool
findById(int $id): ?Booking
findAll(): array
initializeTables(): void
```

### PropertyRepository

```php
save(Property $property): int
saveBatch(array $properties): int
findByBookingId(int $id): array
deleteByBookingId(int $id): int
initializeTables(): void
```

---

## Validation Methods

### BookingValidator

```php
validateDates(string $checkin, string $checkout): array
validateDays(array $days): array
validateServiceFee(string $value): string
validatePaymentPlan(string $value): string
validateNotificationDate(string $date): ?string
validateCancellationDate(string $date): ?string
```

### PropertyValidator

```php
validateProperty(array $property, int $index): array
normalizeProperty(array $property): array
validateIsCancelled(string $value): string
```

### DateValidator

```php
isValidDate(string $date): bool
isCancellationDateValid(string $booking, string $cancel): bool
normalizeDates(array $dates): array
```

---

## Error Handling

All services throw `InvalidArgumentException` for validation errors:

```php
try {
    $bookingId = $service->createBooking($data, $properties);
} catch (InvalidArgumentException $e) {
    echo "Validation Error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage();
}
```

---

## Database Configuration

Edit `src/Database/DatabaseConnection.php`:

```php
new DatabaseConnection(
    host: '127.0.0.1',
    dbname: 'booking_system',
    username: 'root',
    password: '',
    charset: 'utf8mb4'
)
```

---

## Type Hints Reference

```php
// Return types
function save(Booking $booking): int { }
function findById(int $id): ?Booking { }
function findAll(): array { }
function validateDates(string $a, string $b): array { }

// Parameter types
function __construct(PDO $pdo) { }
function createBooking(array $data, array $properties): int { }
```

---

## Testing Example

```php
$mockRepository = $this->createMock(BookingRepository::class);
$mockRepository
    ->method('save')
    ->willReturn(1);

$service = new BookingService($mockRepository, $mockPropertyRepository);
$result = $service->createBooking($validData, $validProperties);

$this->assertEquals(1, $result);
```

---

## Performance Tips

1. **Use Batch Operations**

    ```php
    $propertyRepository->saveBatch($properties); // Single transaction
    ```

2. **Avoid N+1 Queries**

    ```php
    $allBookings = $service->getAllBookings(); // Gets property counts in one query
    ```

3. **Cache Frequently Accessed Data**

    ```php
    $holidays = $holidayService->getAllHolidays(); // Store in cache
    ```

4. **Use Transactions for Multi-Table Operations**
    ```php
    $pdo->beginTransaction();
    // ... save booking and properties
    $pdo->commit(); // Atomic operation
    ```

---

## Debugging

Enable error reporting:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

Check logs:

```php
echo json_encode($service->getAllBookings());
var_dump($booking->toArray());
```

---

## File Sizes

| File              | Lines | Size |
| ----------------- | ----- | ---- |
| BookingService    | 180   | 6 KB |
| BookingRepository | 140   | 5 KB |
| BookingValidator  | 80    | 3 KB |
| PropertyValidator | 60    | 2 KB |
| BookingController | 100   | 4 KB |
| Booking Model     | 70    | 3 KB |
| Property Model    | 70    | 3 KB |

**Total: ~35 KB for all classes**

---

## IDE Support

Full type hints enable:

- ✅ Autocomplete
- ✅ Type checking
- ✅ Jump to definition
- ✅ Refactoring tools
- ✅ Documentation tooltips

---

## PSR Standards Followed

- ✅ **PSR-1**: Basic Coding Standard
- ✅ **PSR-4**: Autoloading Standard
- ✅ **PSR-12**: Extended Coding Style

---

## Useful Links

- [PHP Namespaces](https://www.php.net/manual/en/language.namespaces.php)
- [Type Declarations](https://www.php.net/manual/en/functions.arguments.php#functions.arguments.type-declaration)
- [PDO Prepared Statements](https://www.php.net/manual/en/pdo.prepared-statements.php)
- [PSR-4 Autoloading](https://www.php-fig.org/psr/psr-4/)

---

**Last Updated**: January 20, 2026
**Version**: 1.0
