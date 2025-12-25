We are using xero accounting software where we send invoice to guest for payment plan wise invoice
it can be montly weekly ...

now we need to add notifiy / due field in payment plan tables with its following new defination

notify date is date when we are sending invoice to guest into xero it is 16 days before checkin date
due date is the last date for payment which is 7 days before checkin date

so if checkin date : 1st feb 2026 , checkout date: 28th feb 2026
then notify date is : 1st feb 2026 - 16 days
and due date is : 1st feb 2026 - 7 days

calculate nofity and due date as per given above defination and show it on payment plan tables

Need correction in booking_notify_dt and booking_due_dt

currently it is calculated based on $checkin_imm

```
$checkin_imm = DateTimeImmutable::createFromMutable($checkin_dt);
$booking_notify_dt = $checkin_imm->modify('-16 days');
$booking_due_dt = $checkin_imm->modify('-7 days');
```

caculate it based on plan period start date instead now for each table row
also add one condition
if current date > calculated notification date
nofitification date is current date
if current date > calculated due date then
due date is current date

when it is replace by current date show date in gree font in table
