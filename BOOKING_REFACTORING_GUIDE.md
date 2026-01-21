# Booking Management System - Complete Refactoring Guide

## Overview

All PHP files have been refactored from monolithic procedural code into a clean, modular architecture following SOLID principles and PSR-4 standards.

## New Architecture

### Complete Project Structure

```
plan/
├── src/
│   ├── Database/
│   │   └── DatabaseConnection.php      (PDO connection management)
│   ├── Models/
│   │   ├── Holiday.php                 (Holiday entity)
│   │   ├── Booking.php                 (Booking entity)
│   │   └── Property.php                (Property entity)
│   ├── Repositories/
│   │   ├── HolidayRepository.php       (Holiday data access)
│   │   ├── BookingRepository.php       (Booking data access)
│   │   └── PropertyRepository.php      (Property data access)
│   ├── Services/
│   │   ├── HolidayService.php          (Holiday business logic)
│   │   └── BookingService.php          (Booking business logic)
│   ├── Controllers/
│   │   ├── HolidayController.php       (Holiday request handling)
│   │   └── BookingController.php       (Booking request handling)
│   └── Utils/
│       ├── DateValidator.php           (Date validation)
│       ├── BookingValidator.php        (Booking validation)
│       └── PropertyValidator.php       (Property validation)
├── views/
│   ├── holidays.php                    (Holidays template)
│   └── (other views in main files)
├── addmore-dates.php                   (Refactored entry point)
├── property-form.php                   (Refactored entry point)
├── index.php                           (Refactored entry point)
├── edit-booking.php                    (Refactored entry point)
├── autoloader.php                      (PSR-4 Autoloader)
└── migrations/
    └── add_notification_cancellation_dates.sql
```

## Component Details

### Models

#### **Booking** (`src/Models/Booking.php`)

Represents a booking with all associated data:

- Properties: `id`, `checkin`, `checkout`, `days`, `serviceFee`, `excludeBankHoliday`, `paymentPlan`, `notificationDate`, `cancellationDate`, `createdAt`
- Full getter/setter with fluent interface
- Array serialization via `toArray()`

#### **Property** (`src/Models/Property.php`)

Represents a property within a booking:

- Properties: `id`, `bookingId`, `title`, `nightPrice`, `deposit`, `checkoutDate`, `isCancelled`, `notifyDay`, `createdAt`
- Full getter/setter with fluent interface
- Array serialization via `toArray()`

### Repositories

#### **BookingRepository** (`src/Repositories/BookingRepository.php`)

Data access layer for bookings:

- `findById(int)` - Find single booking
- `findAll()` - Get all bookings
- `findAllWithPropertyCount()` - Get all with property counts
- `save(Booking)` - Insert new booking
- `update(Booking)` - Update existing booking
- `initializeTables()` - Create tables if missing

#### **PropertyRepository** (`src/Repositories/PropertyRepository.php`)

Data access layer for properties:

- `findById(int)` - Find single property
- `findByBookingId(int)` - Get properties for booking
- `findAllRawByBookingId(int)` - Get raw data for display
- `save(Property)` - Insert property
- `saveBatch(array)` - Insert multiple with transaction
- `deleteByBookingId(int)` - Delete all for booking
- `initializeTables()` - Create tables if missing

### Validators

#### **BookingValidator** (`src/Utils/BookingValidator.php`)

Validates booking form data:

- `validateDates(checkin, checkout)` - Validate and compare dates
- `validateNotificationDate(date)` - Validate optional notification date
- `validateCancellationDate(date)` - Validate optional cancellation date
- `validateServiceFee(value)` - Validate enum
- `validateExcludeBankHoliday(value)` - Validate enum
- `validatePaymentPlan(value)` - Validate enum
- `validateDays(array)` - Normalize days array

#### **PropertyValidator** (`src/Utils/PropertyValidator.php`)

Validates property form data:

- `validateProperty(array, index)` - Validate single property
- `validateIsCancelled(value)` - Validate enum
- `normalizeProperty(array)` - Extract and normalize property data

### Services

#### **BookingService** (`src/Services/BookingService.php`)

Business logic orchestration:

- `createBooking(data, properties)` - Create new booking with properties
- `updateBooking(id, data, properties)` - Update booking and properties
- `getBooking(id)` - Retrieve single booking
- `getAllBookings()` - Retrieve all bookings
- `getBookingProperties(id)` - Get properties for booking

### Controllers

#### **BookingController** (`src/Controllers/BookingController.php`)

HTTP request handling:

- `index()` - Display all bookings
- `createForm()` - Show creation form
- `create(formData)` - Handle booking creation
- `editForm(id)` - Show edit form
- `update(id, formData)` - Handle booking update
- Error and success message management

## Refactored Entry Points

### [property-form.php](property-form.php)

**Purpose**: Create new bookings and properties
**Changes**:

- ✅ Uses `BookingService` for business logic
- ✅ Uses `BookingValidator` and `PropertyValidator` for validation
- ✅ Uses `BookingRepository` and `PropertyRepository` for data access
- ✅ Cleaner initialization and error handling
- ✅ Transaction support via repositories
- ✅ Redirects to edit page on success

### [index.php](index.php)

**Purpose**: Display all bookings
**Changes**:

- ✅ Uses `BookingService.getAllBookings()`
- ✅ Single repository call via service
- ✅ Cleaner database initialization
- ✅ Simplified error handling

### [edit-booking.php](edit-booking.php)

**Purpose**: Edit existing bookings
**Changes**:

- ✅ Uses `BookingService` for updates
- ✅ Uses validators for all form data
- ✅ Leverages repositories for save/delete operations
- ✅ Transaction support for data consistency
- ✅ Session management for paid periods (preserved from original)

## Key Improvements

### 1. **Code Organization**

- Before: ~400-950 lines per file mixing all concerns
- After: Separated into focused classes with single responsibilities

### 2. **Validation**

- Centralized in dedicated validator classes
- Reusable across create and update operations
- Clear separation of concerns

### 3. **Error Handling**

- Validation errors collected and reported clearly
- Transaction support for data integrity
- Proper exception handling

### 4. **Data Flow**

```
HTTP Request
    ↓
Controller (HTTP handling)
    ↓
Service (Business logic)
    ↓
Validators (Input validation)
    ↓
Repository (Data access)
    ↓
Database
```

### 5. **Testability**

Each component can be tested independently:

```php
// Easy to mock
$mockRepository = $this->createMock(BookingRepository::class);
$service = new BookingService($mockRepository, $propRepository);
```

### 6. **Reusability**

Services can be used in different contexts:

```php
$bookingService->createBooking($data, $properties);
// Or used in API endpoints, CLI commands, etc.
```

### 7. **Type Safety**

- All methods have type hints for parameters and returns
- Stricter validation with strict_types=1
- Better IDE support

### 8. **SOLID Principles**

| Principle                 | Implementation                      |
| ------------------------- | ----------------------------------- |
| **S**ingle Responsibility | Each class has one reason to change |
| **O**pen/Closed           | Easy to extend validators, services |
| **L**iskov Substitution   | Repositories can be swapped         |
| **I**nterface Segregation | No bloated classes                  |
| **D**ependency Inversion  | Services depend on repositories     |

## Usage Examples

### Creating a Booking

```php
$bookingData = [
    'checkin' => '2026-02-01',
    'checkout' => '2026-02-15',
    'days' => ['mon', 'tue', 'wed'],
    'payment_plan' => 'Monthly',
    // ... other fields
];

$propertiesData = [
    ['title' => 'Beach House', 'night_price' => 100, 'deposit' => 500],
    // ... more properties
];

$bookingId = $bookingService->createBooking($bookingData, $propertiesData);
```

### Updating a Booking

```php
$result = $bookingController->update($bookingId, [
    'booking' => $bookingData,
    'properties' => $propertiesData,
]);

if ($result['success']) {
    header('Location: edit-booking.php?bookingId=' . $bookingId);
}
```

### Retrieving Data

```php
// Get all bookings with property counts
$bookings = $bookingService->getAllBookings();

// Get single booking
$booking = $bookingService->getBooking($bookingId);

// Get properties for booking
$properties = $bookingService->getBookingProperties($bookingId);
```

## Database Schema

### Bookings Table

```sql
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    checkin DATE NOT NULL,
    checkout DATE NOT NULL,
    days JSON DEFAULT '[]',
    service_fee ENUM('No','Yes') DEFAULT 'No',
    exclude_bank_holiday ENUM('No','Yes') DEFAULT 'No',
    payment_plan ENUM('weekly','fortnighly','Monthly','full') DEFAULT 'Monthly',
    notification_date DATE NULL,
    cancellation_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

### Properties Table

```sql
CREATE TABLE properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    night_price DECIMAL(10,2) DEFAULT 0,
    deposit DECIMAL(10,2) DEFAULT 0,
    checkout_date DATE NULL,
    is_cancelled ENUM('No','Yes') DEFAULT 'No',
    notify_day INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
)
```

## Future Enhancements

1. **Interface Contracts**

    ```php
    interface RepositoryInterface { ... }
    interface ValidatorInterface { ... }
    ```

2. **Dependency Injection Container**

    ```php
    $container = new Container();
    $controller = $container->get(BookingController::class);
    ```

3. **Unit Tests**

    ```php
    class BookingServiceTest extends TestCase {
        public function testCreateBooking() { ... }
    }
    ```

4. **Environment Configuration**
    - Move database credentials to `.env`
    - Environment-specific settings

5. **Logging**

    ```php
    $logger->info('Booking created', ['booking_id' => $id]);
    ```

6. **Caching**

    ```php
    $bookings = $cache->get('all_bookings', function() {
        return $service->getAllBookings();
    });
    ```

7. **API Layer**
    ```php
    POST /api/bookings
    GET /api/bookings/{id}
    PUT /api/bookings/{id}
    ```

## Performance Considerations

1. **Database Queries**: Batch operations reduce round trips
2. **Transactions**: Ensure data consistency
3. **Caching**: Holiday list rarely changes
4. **Lazy Loading**: Load properties only when needed
5. **Indexing**: Add indexes on foreign keys and dates

## Security Improvements Implemented

✅ Parameterized queries (PDO prepared statements)
✅ HTML escaping in templates
✅ Type hints prevent type juggling vulnerabilities
✅ Isolated error messages from users
✅ Transaction support for ACID compliance
✅ Input validation on all forms

## Migration from Old Code

No database changes required! The new code:

- Works with existing table structure
- Maintains backward compatibility
- Supports all original features
- Adds better error handling

## Support

For questions or issues:

1. Check REFACTORING_GUIDE.md for holidays refactoring
2. Review class documentation in source files
3. Examine test files for usage examples
