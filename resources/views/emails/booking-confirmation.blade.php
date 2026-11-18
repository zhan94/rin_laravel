<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Booking confirmation</title>
</head>
<body>
<p>Hello {{ $customerName }},</p>

<p>Your booking #{{ $bookingId }} was created successfully.</p>

<p><strong>Service:</strong> {{ $serviceName }}</p>
@if ($dates !== '')
    <p><strong>Dates:</strong> {{ $dates }}</p>
@endif
<p><strong>Total:</strong> €{{ $totalEur }}</p>
<p><strong>Paid:</strong> €{{ $paidEur }}</p>

<p>Thank you.</p>
</body>
</html>
