<?php
// index.php - Parlor Management System Landing Page
?>
<!DOCTYPE html>
<html>
<head>
    <title>Parlor Management System</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.5.5/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f7fafc;
        }
        .hero-section {
            background: linear-gradient(90deg, #e0c3fc 0%, #8ec5fc 100%);
            padding: 60px 0 40px 0;
            text-align: center;
            color: #353535;
        }
        .hero-section h1 {
            font-size: 3em;
            font-weight: 700;
        }
        .hero-section p {
            font-size: 1.4em;
            margin: 18px 0 36px 0;
        }
        .hero-btn {
            margin: 10px;
            min-width: 140px;
            font-size: 1.15em;
            padding: 12px 30px;
            border-radius: 28px;
        }
        .features-section {
            background: #fff;
            padding: 50px 0 40px 0;
        }
        .feature-icon {
            font-size: 40px;
            color: #6f42c1;
            margin-bottom: 16px;
        }
        .service-section {
            padding: 40px 0 20px 0;
            background: #f3f4f6;
        }
        .footer {
            background: #23272b;
            color: #eee;
            padding: 18px 0;
            text-align: center;
            font-size: 1.1em;
            margin-top: 40px;
        }
    </style>
    <!-- Font Awesome for icons (Optional) -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <h1>Welcome to Parlor Management System</h1>
        <p>Modernize your salon with smart booking, easy management, and seamless experiences for admins, beauticians, and customers.</p>
        <a href="login.php" class="btn btn-primary hero-btn"><i class="fa fa-sign-in"></i> Login</a>
        <a href="register.php" class="btn btn-success hero-btn"><i class="fa fa-user-plus"></i> Register</a>
    </div>
</div>

<!-- Features Section -->
<div class="features-section">
    <div class="container">
        <h2 class="text-center" style="font-weight:600;">Why Choose Us?</h2>
        <div class="row text-center" style="margin-top:40px;">
            <div class="col-sm-4">
                <div class="feature-icon"><i class="fa fa-calendar-check-o"></i></div>
                <h4>Easy Online Booking</h4>
                <p>Customers can book, reschedule, or cancel appointments anytime, anywhere.</p>
            </div>
            <div class="col-sm-4">
                <div class="feature-icon"><i class="fa fa-user-circle"></i></div>
                <h4>Role-based Management</h4>
                <p>Admins manage services, staff & appointments; beauticians see schedules; customers manage profiles.</p>
            </div>
            <div class="col-sm-4">
                <div class="feature-icon"><i class="fa fa-lock"></i></div>
                <h4>Secure & Reliable</h4>
                <p>99% uptime, secure login, privacy-first design, fast and responsive on any device.</p>
            </div>
        </div>
    </div>
</div>

<!-- Service Highlight Section -->
<div class="service-section">
    <div class="container">
        <h3 class="text-center" style="font-weight:600;">Our Top Services</h3>
        <div class="row text-center" style="margin-top:30px;">
            <div class="col-sm-3">
                <div class="panel panel-default">
                    <div class="panel-body"><i class="fa fa-scissors feature-icon"></i>
                        <h5>Haircut & Styling</h5>
                        <p>Trendy, classic, and custom cuts for all ages.</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="panel panel-default">
                    <div class="panel-body"><i class="fa fa-magic feature-icon"></i>
                        <h5>Facials & Skincare</h5>
                        <p>Pamper yourself with our rejuvenating treatments.</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="panel panel-default">
                    <div class="panel-body"><i class="fa fa-diamond feature-icon"></i>
                        <h5>Bridal Packages</h5>
                        <p>Special packages for your special day. Book ahead!</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="panel panel-default">
                    <div class="panel-body"><i class="fa fa-users feature-icon"></i>
                        <h5>Group & Family Bookings</h5>
                        <p>Get exclusive deals for group sessions and families.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<div class="footer">
    Parlor Management System &copy; <?php echo date('Y'); ?> | Powered by Bootstrap & PHP
</div>

</body>
</html>
