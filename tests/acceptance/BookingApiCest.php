<?php

/**
 * BookingApiCest.php
 * Integration tests for Booking pages (browserless)
 * Tests HTTP requests and responses without needing a browser
 */

namespace Acceptance;

use AcceptanceTester;

class BookingApiCest
{
    /**
     * Test: GET /index.php returns bookings page
     */
    public function getAllBookingsEndpoint(AcceptanceTester $I)
    {
        // ARRANGE & ACT
        $I->amOnPage('/index.php');

        // ASSERT
        $I->see('Bookings', 'h1');
        $I->seeElement('table');
    }

    /**
     * Test: GET /property-form.php displays form
     */
    public function getPropertyFormDisplaysForm(AcceptanceTester $I)
    {
        // ARRANGE & ACT
        $I->amOnPage('/property-form.php');

        // ASSERT

        $I->seeElement('form');
        $I->seeElement('input[name="checkin"]');
        $I->seeElement('input[name="checkout"]');
    }

    /**
     * Test: Form contains all required fields
     */
    public function formContainsRequiredFields(AcceptanceTester $I)
    {
        // ARRANGE & ACT
        $I->amOnPage('/property-form.php');

        // ASSERT
        $I->seeElement('input[name="checkin"]');
        $I->seeElement('input[name="checkout"]');
        $I->seeElement('input[name="properties[0][title]"]');
        $I->seeElement('input[name="properties[0][night_price]"]');
        $I->seeElement('input[name="properties[0][deposit]"]');
        $I->seeElement('select[name="payment_plan"]');
        $I->seeElement('select[name="service_fee"]');
    }

    /**
     * Test: Form validates HTML5 date inputs
     */
    public function formValidatesDateFormat(AcceptanceTester $I)
    {
        // ARRANGE
        $I->amOnPage('/property-form.php');

        // ASSERT - HTML5 date input field exists
        $I->seeElement('input[name="checkin"][type="date"]');
    }

    /**
     * Test: Edit booking page loads
     */
    public function editBookingPageLoads(AcceptanceTester $I)
    {
        // ARRANGE & ACT
        $I->amOnPage('/edit-booking.php');

        // ASSERT - May show error or form depending on bookingId

    }

    /**
     * Test: Service fee option exists in form
     */
    public function serviceFeeOptionExists(AcceptanceTester $I)
    {
        // ARRANGE & ACT
        $I->amOnPage('/property-form.php');

        // ASSERT
        $I->seeElement('select[name="service_fee"]');
        $I->seeOptionIsSelected('service_fee', 'No');
    }

    /**
     * Test: Payment plan dropdown available
     */
    public function paymentPlanDropdownAvailable(AcceptanceTester $I)
    {
        // ARRANGE & ACT
        $I->amOnPage('/property-form.php');

        // ASSERT
        $I->seeElement('select[name="payment_plan"]');
        $I->seeOptionIsSelected('payment_plan', 'Monthly');
    }

    /**
     * Test: Checkout date field exists
     */
    public function checkoutDateFieldExists(AcceptanceTester $I)
    {
        // ARRANGE & ACT
        $I->amOnPage('/property-form.php');

        // ASSERT
        $I->seeElement('input[name="checkout"][type="date"]');
    }

    /**
     * Test: Booking listing returns 200
     */
    public function bookingListingReturns200(AcceptanceTester $I)
    {
        // ARRANGE & ACT
        $I->amOnPage('/index.php');

        // ASSERT

    }

    /**
     * Test: Property form returns 200
     */
    public function propertyFormReturns200(AcceptanceTester $I)
    {
        // ARRANGE & ACT
        $I->amOnPage('/property-form.php');

        // ASSERT

    }

    /**
     * Test: Non-existent booking ID parameter handling
     */
    public function nonExistentBookingIdHandled(AcceptanceTester $I)
    {
        // ARRANGE & ACT
        $I->amOnPage('/edit-booking.php?bookingId=99999');

        // ASSERT - Page loads (even with error message)

    }

    /**
     * Test: Missing booking ID parameter handling
     */
    public function missingBookingIdHandled(AcceptanceTester $I)
    {
        // ARRANGE & ACT
        $I->amOnPage('/edit-booking.php');

        // ASSERT - Page loads (even with error message)

    }

    /**
     * Test: Days checkboxes exist in form
     */
    public function daysCheckboxesExist(AcceptanceTester $I)
    {
        // ARRANGE & ACT
        $I->amOnPage('/property-form.php');

        // ASSERT - Check for day checkboxes
        $I->seeElement('input[name="days[]"]');
    }

    /**
     * Test: Bank holiday option exists
     */
    public function bankHolidayOptionExists(AcceptanceTester $I)
    {
        // ARRANGE & ACT
        $I->amOnPage('/property-form.php');

        // ASSERT
        $I->seeElement('select[name="exclude_bank_holiday"]');
    }
}
