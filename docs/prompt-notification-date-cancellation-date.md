In booking table

nofication date is when gues informs about cancellation
and cancellation date is the real cancellation start date

for any property if is_cancelled is Yes

so for that property cancellation happen from that booking table cancellation_date

example,

1. booking cancellation date is : 26/12/2025
   booking checkin and checkout date is
   1st dec 2025 to 31st dec 2025
   payment plan: monthly
   is service fee: No
   exclude bank holiday: No

    booking for 1 property name : A
    which night price is 100
    notify day : 0

    so number of night guest will stay here is
    1st dec 2025 to 25th dec 2025

    which is 23 night

    for finalTotal after cancellation is : 23 \* 100 = 2300 pound

    this way need to do finaltotal after cancellation

2. second example,
   booking nofification date is : 26/12/2025
   booking cancellation date is : 28/2220225
   booking checkin and checkout date is
   1st dec 2025 to 31st dec 2025
   payment plan: monthly
   is service fee: No
   exclude bank holiday: No

    booking for 1 property name : A
    which night price is 100
    notify day : 3

    so number of night guest will stay here is
    1st dec 2025 to 27th dec 2025

    which is 25 night

    but here property notify day is 3 but here guest notified on 2 days before ( cancellation_date - notification_date)

    so paid night is : 26

    for finalTotal after cancellation is : 26 \* 100 = 2600 pound

    this way need to do finaltotal after cancellation
