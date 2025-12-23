create file property_from.php

which connect same database booking_system
use jquery and bootstrap

create following form Booking has two section
booking form details
and property form details

in booking form detail section add field
checkin date , checkout date
with validation that checkout date greater then checkin date
days checkbox group from mon to sun
is Service Fee ?? selection box with option No and Yes No default
Exclude bank Holidy? selection box with option No and Yes No default
Select Payment Plan selection box with option weekly , fortnighly, Monthly ,full where Monthly default

in Property form details section
form field are dynamic
field are
title , night price, deposit which is number , checkout date which is date field , is Cancelled which is selection box with No and Yes option No default , Notify Day which is number

this group of field i can add or remove so i can input multiple property information

in Nofify- Due
also consider current date
show current date on top after main header too
if currentdate is greater then calcuated notify date then show current date
for example
checkin: 1st dec 2025 and checkout : 31st dec 2025
and booking property notify day : 10 days
so as per current calculation it is 11/12/2025 but current date is
23rd dec 2025
so now Notify -Due in payment plan will be 23rd dec 2025
