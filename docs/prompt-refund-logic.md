Here we are taking payment in advance
so now I need refund logic implementation
for it put one checkbox in each row of table "Payment Plan - without cacellation"
give that checkbox colum name Is Paid ?

if it is checked mean payment alread done for that period

so create new table "After Cancellation: Payment Plans( HOST )"
which include only property information which is cancelled
with service fee and final total calculation based on number of cancelled day from original booking

need correction in refund logic
we have added is paid checkbox in each row to mark that guest has already made payment for that period

in refund logic cancelled night must calculated based on that period

for example,  
booking cancellation date is : 26/12/2025
booking checkin and checkout date is
1st dec 2025 to 31st jan 2026
payment plan: monthly
is service fee: No
exclude bank holiday: No

so it has 2 payment plan period as below

1. from 01/12/2025 to 01/01/2026 total 31 days
2. from 01/01/2026 to 31/01/2026 total 31 days

if i mark 1st period paid then exclude 5 cancelled night from 1st periods
