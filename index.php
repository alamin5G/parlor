
<?php
// index.php - Parlor Management System Landing Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Aura Salon & Spa | Parlor Management System</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts (Poppins) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #fdfcff;
            color: #4a4a4a;
        }

        /* --- Navigation Bar --- */
        .navbar {
            background-color: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            transition: background-color 0.3s ease;
        }
        .navbar-brand {
            font-weight: 700;
            color: #6a11cb !important;
        }
        .nav-link {
            font-weight: 600;
            color: #555 !important;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: #6a11cb !important;
        }
        .btn-gradient {
            background-image: linear-gradient(to right, #6a11cb 0%, #2575fc 100%);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        /* --- Hero Section --- */
        .hero-section {
            background-image: linear-gradient(45deg, rgba(106, 17, 203, 0.85), rgba(37, 117, 252, 0.85)), url('https://images.unsplash.com/photo-1521590832167-7bcbfaa6381f?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            padding: 120px 0;
            color: white;
        }
        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .hero-section p {
            font-size: 1.25rem;
            font-weight: 300;
            max-width: 700px;
            margin: 20px auto 40px auto;
        }

        /* --- Section Styling --- */
        .section {
            padding: 80px 0;
        }
        .section-title {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #333;
        }
        .section-subtitle {
            font-weight: 400;
            color: #777;
            margin-bottom: 50px;
        }

        /* --- Features Section --- */
        .feature-card {
            background: white;
            border: none;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.07);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(37, 117, 252, 0.15);
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            background: -webkit-linear-gradient(45deg, #6a11cb, #2575fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* --- Roles Section --- */
        .bg-light-purple {
            background-color: #f8f5ff;
        }
        .nav-pills .nav-link {
            background-color: #e9e1f8;
            color: #6a11cb;
            font-weight: 600;
            border-radius: 50px;
            margin: 0 5px;
        }
        .nav-pills .nav-link.active {
            background-image: linear-gradient(to right, #6a11cb 0%, #2575fc 100%);
            color: white;
        }
        .role-content {
            text-align: left;
        }
        .role-content i {
            color: #28a745;
            margin-right: 8px;
        }

        /* --- Footer --- */
        .footer {
            background-color: #1c1c1c;
            color: #a0a0a0;
            padding: 40px 0 20px 0;
        }
        .footer h5 {
            color: white;
            font-weight: 600;
        }
        .footer a {
            color: #a0a0a0;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .footer a:hover {
            color: white;
        }
        .footer .social-icons a {
            font-size: 1.5rem;
            margin: 0 10px;
        }
        .footer .copyright {
            border-top: 1px solid #333;
            padding-top: 20px;
            margin-top: 30px;
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="fa-solid fa-spa"></i> Aura Salon & Spa</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#roles">For Everyone</a></li>
                <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                <li class="nav-item ms-lg-3">
                    <a href="login.php" class="btn btn-outline-primary rounded-pill px-4 me-2">Login</a>
                </li>
                <li class="nav-item">
                    <a href="register.php" class="btn btn-gradient">Register</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section d-flex align-items-center">
    <div class="container text-center">
        <h1 class="display-4">Effortless Beauty, Managed Beautifully</h1>
        <p class="lead">
            Modernize your salon with smart booking, easy management, and seamless experiences for admins, beauticians, and customers.
        </p>
        <a href="register.php" class="btn btn-light btn-lg rounded-pill px-5 py-3 fw-bold">Get Started Today</a>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="section">
    <div class="container text-center">
        <h2 class="section-title">The Future of Salon Management</h2>
        <p class="section-subtitle">Everything you need to run your parlor smoothly and efficiently.</p>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-calendar-check"></i></div>
                    <h5 class="fw-bold">Smart Appointments</h5>
                    <p>Customers can book, reschedule, or cancel appointments 24/7. Admins get a clear view of the schedule.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-users-gear"></i></div>
                    <h5 class="fw-bold">Role-Based Access</h5>
                    <p>Dedicated dashboards for Admins, Beauticians, and Customers ensure everyone sees exactly what they need.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                    <h5 class="fw-bold">Simple Billing</h5>
                    <p>Generate bills instantly for completed appointments. We keep it simple with cash payments and clear receipts.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <h5 class="fw-bold">Secure & Reliable</h5>
                    <p>Your data is safe with us. Enjoy a fast, responsive, and secure platform with 99% uptime guaranteed.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- For Everyone (Roles) Section -->
<section id="roles" class="section bg-light-purple">
    <div class="container text-center">
        <h2 class="section-title">Designed For Everyone</h2>
        <p class="section-subtitle">A tailored experience for every role in your salon ecosystem.</p>
        
        <ul class="nav nav-pills justify-content-center mb-4" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-customers-tab" data-bs-toggle="pill" data-bs-target="#pills-customers" type="button">For Customers</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-beauticians-tab" data-bs-toggle="pill" data-bs-target="#pills-beauticians" type="button">For Beauticians</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-admins-tab" data-bs-toggle="pill" data-bs-target="#pills-admins" type="button">For Admins</button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            <div class="tab-pane fade show active" id="pills-customers" role="tabpanel">
                <div class="row justify-content-center align-items-center">
                    <div class="col-md-5">
                        <img src="https://cdni.iconscout.com/illustration/premium/thumb/online-booking-4489220-3729975.png" class="img-fluid" alt="Customer booking appointment">
                    </div>
                    <div class="col-md-5 role-content">
                        <ul class="list-unstyled fs-5">
                            <li class="mb-2"><i class="fa-solid fa-circle-check"></i> Book appointments with ease</li>
                            <li class="mb-2"><i class="fa-solid fa-circle-check"></i> Manage your profile and history</li>
                            <li class="mb-2"><i class="fa-solid fa-circle-check"></i> Receive email reminders</li>
                            <li class="mb-2"><i class="fa-solid fa-circle-check"></i> Leave reviews and ratings</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="pills-beauticians" role="tabpanel">
                 <div class="row justify-content-center align-items-center">
                    <div class="col-md-5">
                        <img src="https://cdni.iconscout.com/illustration/premium/thumb/female-hairdresser-2974612-2476891.png" class="img-fluid" alt="Beautician checking schedule">
                    </div>
                    <div class="col-md-5 role-content">
                        <ul class="list-unstyled fs-5">
                            <li class="mb-2"><i class="fa-solid fa-circle-check"></i> View your daily/weekly schedule</li>
                            <li class="mb-2"><i class="fa-solid fa-circle-check"></i> See assigned appointments</li>
                            <li class="mb-2"><i class="fa-solid fa-circle-check"></i> Manage your specialization</li>
                            <li class="mb-2"><i class="fa-solid fa-circle-check"></i> Track your performance</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="pills-admins" role="tabpanel">
                 <div class="row justify-content-center align-items-center">
                    <div class="col-md-5">
                        <img src="https://cdni.iconscout.com/illustration/premium/thumb/business-dashboard-3322429-2785108.png" class="img-fluid" alt="Admin managing dashboard">
                    </div>
                    <div class="col-md-5 role-content">
                        <ul class="list-unstyled fs-5">
                            <li class="mb-2"><i class="fa-solid fa-circle-check"></i> Full control dashboard</li>
                            <li class="mb-2"><i class="fa-solid fa-circle-check"></i> Manage services, staff, and prices</li>
                            <li class="mb-2"><i class="fa-solid fa-circle-check"></i> Assign time slots to beauticians</li>
                            <li class="mb-2"><i class="fa-solid fa-circle-check"></i> Oversee all appointments & billing</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section id="services" class="section">
    <div class="container text-center">
        <h2 class="section-title">Our Signature Services</h2>
        <p class="section-subtitle">Discover the perfect treatment to refresh your look and spirit.</p>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1597848212624-a19eb35e2651?q=80&w=1902&auto=format&fit=crop" class="card-img-top" alt="Haircut & Styling">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">Haircut & Styling</h5>
                        <p class="card-text">Trendy, classic, and custom cuts for all ages.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?q=80&w=1887&auto=format&fit=crop" class="card-img-top" alt="Facials & Skincare">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">Facials & Skincare</h5>
                        <p class="card-text">Pamper yourself with our rejuvenating treatments.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1595877244574-e90ce41ce099?q=80&w=1887&auto=format&fit=crop" class="card-img-top" alt="Bridal Packages">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">Bridal Packages</h5>
                        <p class="card-text">Special packages for your special day. Book ahead!</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1556760544-4421761a0212?q=80&w=1887&auto=format&fit=crop" class="card-img-top" alt="Group Bookings">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">Group & Family</h5>
                        <p class="card-text">Get exclusive deals for group sessions and families.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <h5><i class="fa-solid fa-spa"></i> Aura Salon & Spa</h5>
                <p class="mt-3">The complete management solution to elevate your salon business. Join us and transform your operations.</p>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-4 mb-lg-0">
                <h5>Quick Links</h5>
                <ul class="list-unstyled mt-3">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#roles">For Everyone</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-4 col-6 mb-4 mb-lg-0">
                <h5>Contact</h5>
                <ul class="list-unstyled mt-3">
                    <li><i class="fa-solid fa-location-dot me-2"></i> 123 Beauty Lane, Glamour City</li>
                    <li><i class="fa-solid fa-phone me-2"></i> (123) 456-7890</li>
                    <li><i class="fa-solid fa-envelope me-2"></i> contact@aurasalon.com</li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-4 mb-4 mb-lg-0">
                <h5>Follow Us</h5>
                <div class="social-icons mt-3">
                    <a href="#"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#"><i class="fa-brands fa-twitter"></i></a>
                </div>
            </div>
        </div>
        <div class="copyright text-center">
            Parlor Management System &copy; <?php echo date('Y'); ?> | Powered by Bootstrap & PHP
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
