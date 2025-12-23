in edit-booking.php
add section Payment Plan

Which show upcoming payment details in table format datewise
example ,

table fields are ,
checkin-checkout
notify-due
deposit
service_fee
final_total
nights

checkin-checkout show date dd/mm/yyyy / dd/mm/yyyy where first on is checkin date second one is checkout date

notify-due format is like (dd/mm/yyyy / dd/mm/yyyy)
where each date is associated with property record
that date is calculated as booking checking date + property notify days

deposit is represented in pound numeric format
it is a sum of all property deposit from that booking

service fee is represented in pount numeric format
per property 0.5 pound per day
so if booking of 30 days for 2 properties then
calculation is like
= numberof booking days _ numberof properties _ 0.5
= 30 _ 2 _ 0.5
= 30 pound
only calculate service fee if service_fee is Yes in booking
else it is 0 pound

final_total is numeric represented in pound
calculation formula is
= night_price \* noofDaysOfBooking
for each property in booking
and it's sum is final_total

nights is
noofDays of booking
which is checkout date - checkin date

need correction in $nights calculation it is currently based on
checkout - checkin days
but also consider days field in booking also need to consider
so if it is 4 days per week
then ignore the remaining days in $night calculations

in $night calculation also consider holiday
if exclude_bank_holiday is Yes in booking then
from holidays table capture holiday_date and exclude that days in $night calculation too
