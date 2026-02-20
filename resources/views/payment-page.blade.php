<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Property Tax Payment</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; text-align: center; }
        .container { max-width: 500px; margin: 0 auto; }
        .btn { background: #3399cc; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        .details { background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Property Tax Payment</h2>
        <div class="details">
            <p><strong>Holding No:</strong> {{ $holdingNo }}</p>
            <p><strong>Amount:</strong> ₹{{ number_format($amount, 2) }}</p>
        </div>
        <button class="btn" onclick="payNow()">Pay Now</button>
    </div>

    <script>
        function payNow() {
            var options = {
                "key": "{{ trim(config('razorpay.RAZORPAY_KEY')) }}",
                "amount": "{{ $amount * 100 }}",
                "currency": "INR",
                "name": "Property Tax",
                "description": "Holding No: {{ $holdingNo }}",
                "order_id": "{{ $orderId }}",
                "handler": function (response) {
                    alert('Payment Successful! Payment ID: ' + response.razorpay_payment_id);
                    window.location.href = "{{ url('/') }}";
                },
                "theme": { "color": "#3399cc" }
            };
            var rzp = new Razorpay(options);
            rzp.open();
        }
        window.onload = function() { payNow(); };
    </script>
</body>
</html>
