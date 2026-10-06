<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require APP_ROOT . '/src/layout.php';

send_security_headers();

$s = settings();
$groups = active_package_groups();
$events = all('SELECT * FROM event_types WHERE is_active = 1 ORDER BY sort_order, id');
$eventOptions = booking_event_options();
$phones = array_values(array_filter([$s['phone_1'], $s['phone_2']], fn ($phone) => $phone !== ''));
$today = date('Y-m-d');

html_head($s['site_name'] . ' - ' . str_replace(["\r", "\n"], ' ', $s['footer_tagline']), $s['meta_description']);
?>

<body data-bs-spy="scroll" data-bs-target="#navbarCollapse" data-bs-offset="100">
    <div class="container-xxl position-relative bg-white p-0" id="home">

        <!-- Navbar Start -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 px-lg-5 py-2 py-lg-0">
            <a href="#home" class="navbar-brand p-0">
                <img src="/img/logo-horizontal.png" alt="<?= e($s['site_name']) ?>" width="640" height="182">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="fa fa-bars"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0 pe-4">
                    <a href="#home" class="nav-item nav-link active">Home</a>
                    <a href="#about" class="nav-item nav-link">About</a>
<?php if ($groups): ?>
                    <a href="#packages" class="nav-item nav-link">Packages</a>
<?php endif; ?>
<?php if ($events): ?>
                    <a href="#events" class="nav-item nav-link">Events</a>
<?php endif; ?>
                    <a href="#booking" class="nav-item nav-link">Contact</a>
                </div>
                <a href="#booking" class="btn btn-primary py-2 px-4 mb-3 mb-lg-0">Book Now</a>
            </div>
        </nav>
        <!-- Navbar End -->


        <!-- Hero Start -->
        <div class="container-xxl py-5 hero-header">
            <div class="container my-5 py-5">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6 text-center text-lg-start">
                        <p class="hero-tagline ff-secondary animated slideInLeft mb-2"><?= e($s['hero_tagline']) ?></p>
                        <h1 class="display-3 text-white animated slideInLeft"><?= nl2br(e($s['hero_title'])) ?></h1>
                        <p class="hero-lead animated slideInLeft mb-4 pb-2"><?= nl2br(e($s['hero_text'])) ?></p>
                        <a href="#booking" class="btn btn-primary py-3 px-4 px-sm-5 me-sm-3 mb-3 mb-sm-0 animated slideInLeft">Book Your Event</a>
<?php if ($groups): ?>
                        <a href="#packages" class="btn btn-outline-light py-3 px-4 px-sm-5 animated slideInLeft">View Packages</a>
<?php endif; ?>
                    </div>
                    <div class="col-lg-6 text-center text-lg-end overflow-hidden">
                        <img class="img-fluid hero-platter" src="/img/hero.png" alt="A platter of freshly grilled food" width="800" height="800">
                    </div>
                </div>
            </div>
        </div>
        <!-- Hero End -->


        <!-- Promise Start -->
        <div class="container-xxl py-5">
            <div class="container">
                <div class="row g-4">
<?php for ($i = 1; $i <= 4; $i++): ?>
<?php if ($s["promise_{$i}_title"] !== ''): ?>
                    <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="<?= 0.1 + ($i - 1) * 0.2 ?>s">
                        <div class="service-item rounded pt-3 h-100">
                            <div class="p-4">
                                <i class="fa fa-3x <?= e(valid_icon($s["promise_{$i}_icon"])) ?> text-primary mb-4"></i>
                                <h5><?= e($s["promise_{$i}_title"]) ?></h5>
                                <p class="mb-0"><?= e($s["promise_{$i}_text"]) ?></p>
                            </div>
                        </div>
                    </div>
<?php endif; ?>
<?php endfor; ?>
                </div>
            </div>
        </div>
        <!-- Promise End -->


        <!-- About Start -->
        <div class="container-xxl py-5" id="about">
            <div class="container">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6">
                        <div class="row g-3 about-grid">
                            <div class="col-6">
                                <img class="img-fluid rounded w-100 wow zoomIn" data-wow-delay="0.1s" src="/img/chef.jpg" alt="Chef Muller in his chef's whites" width="235" height="325" loading="lazy">
                            </div>
                            <div class="col-6">
                                <img class="img-fluid rounded w-100 wow zoomIn" data-wow-delay="0.3s" src="/img/plate.jpg" alt="A plated starter of samosas and spring rolls" width="280" height="215" loading="lazy">
                            </div>
                            <div class="col-6">
                                <img class="img-fluid rounded w-100 wow zoomIn" data-wow-delay="0.5s" src="/img/salad.jpg" alt="Fresh garden salad" width="158" height="150" loading="lazy">
                            </div>
                            <div class="col-6">
                                <img class="img-fluid rounded w-100 wow zoomIn" data-wow-delay="0.7s" src="/img/buffet.jpg" alt="A buffet of hot dishes in serving trays" width="538" height="180" loading="lazy">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <h5 class="section-title ff-secondary text-start text-primary fw-normal">About Us</h5>
                        <h1 class="mb-4"><?= e($s['about_heading']) ?></h1>
                        <p class="mb-4"><?= nl2br(e($s['about_text_1'])) ?></p>
<?php if ($s['about_text_2'] !== ''): ?>
                        <p class="mb-4"><?= nl2br(e($s['about_text_2'])) ?></p>
<?php endif; ?>
                        <div class="row g-4 mb-4">
<?php for ($i = 1; $i <= 2; $i++): ?>
<?php if ($s["stat_{$i}_value"] !== ''): ?>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center border-start border-5 border-primary px-3">
                                    <h1 class="flex-shrink-0 display-5 text-primary mb-0"><?= e($s["stat_{$i}_value"]) ?></h1>
                                    <div class="ps-4">
                                        <p class="mb-0"><?= e($s["stat_{$i}_top"]) ?></p>
                                        <h6 class="text-uppercase mb-0"><?= e($s["stat_{$i}_bottom"]) ?></h6>
                                    </div>
                                </div>
                            </div>
<?php endif; ?>
<?php endfor; ?>
                        </div>
                        <a class="btn btn-primary py-3 px-5 mt-2" href="#booking">Book <?= e($s['contact_name']) ?></a>
                    </div>
                </div>
            </div>
        </div>
        <!-- About End -->

<?php if ($groups): ?>

        <!-- Packages Start -->
        <div class="container-xxl py-5 bg-light" id="packages">
            <div class="container">
                <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                    <h5 class="section-title ff-secondary text-center text-primary fw-normal">Meal Packages</h5>
                    <h1 class="mb-3"><?= e($s['packages_heading']) ?></h1>
                    <p class="mx-auto mb-5 package-intro"><?= nl2br(e($s['packages_intro'])) ?></p>
                </div>
                <div class="row g-4 justify-content-center">
<?php foreach ($groups as $index => $group): ?>
                    <div class="col-lg-5 col-md-6 wow fadeInUp" data-wow-delay="<?= 0.1 + ($index % 2) * 0.2 ?>s">
                        <div class="package-card rounded h-100">
                            <h4 class="package-title"><?= e($group['name']) ?></h4>
<?php foreach ($group['items'] as $item): ?>
                            <a class="package-row" href="#booking" data-package="<?= (int) $item['id'] ?>">
                                <i class="fa <?= e(valid_icon($item['icon'])) ?>"></i>
                                <span class="package-name"><?= e($item['name']) ?></span>
                                <span class="package-dots"></span>
                                <span class="package-price"><?= e(money($item['price'])) ?></span>
                            </a>
<?php endforeach; ?>
<?php if ($group['note'] !== ''): ?>
                            <p class="package-note mb-0"><?= e($group['note']) ?></p>
<?php endif; ?>
                        </div>
                    </div>
<?php endforeach; ?>
                </div>
<?php if ($s['packages_footnote'] !== ''): ?>
                <p class="text-center mt-4 mb-0"><small><?= e($s['packages_footnote']) ?></small></p>
<?php endif; ?>
            </div>
        </div>
        <!-- Packages End -->
<?php endif; ?>

<?php if ($events): ?>

        <!-- Events Start -->
        <div class="container-xxl py-5" id="events">
            <div class="container">
                <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                    <h5 class="section-title ff-secondary text-center text-primary fw-normal">What We Offer</h5>
                    <h1 class="mb-5"><?= e($s['events_heading']) ?></h1>
                </div>
                <div class="row g-4">
<?php foreach ($events as $index => $event): ?>
                    <div class="col-lg-4 col-sm-6 wow fadeInUp" data-wow-delay="<?= 0.1 + ($index % 3) * 0.1 ?>s">
                        <div class="event-item rounded h-100">
                            <i class="fa fa-2x <?= e(valid_icon($event['icon'])) ?>"></i>
                            <div>
                                <h5><?= e($event['name']) ?></h5>
                                <p class="mb-0"><?= e($event['description']) ?></p>
                            </div>
                        </div>
                    </div>
<?php endforeach; ?>
                </div>
            </div>
        </div>
        <!-- Events End -->
<?php endif; ?>


        <!-- Booking Start -->
        <div class="container-xxl py-5 px-0 wow fadeInUp" data-wow-delay="0.1s" id="booking">
            <div class="row g-0">
                <div class="col-lg-5 bg-dark d-flex align-items-center">
                    <div class="p-4 p-sm-5">
                        <h5 class="section-title ff-secondary text-start text-primary fw-normal">Bookings</h5>
                        <h1 class="text-white mb-4"><?= e($s['booking_heading']) ?></h1>
                        <ol class="booking-steps">
                            <li>Fill in the form with your event details.</li>
                            <li>Send us your booking on WhatsApp from the next screen.</li>
                            <li><?= e($s['contact_name']) ?> confirms the menu, date and price with you.</li>
                        </ol>
<?php if ($phones): ?>
                        <p class="booking-label mb-2">Call or WhatsApp <?= e($s['contact_name']) ?></p>
<?php foreach ($phones as $phone): ?>
                        <a class="booking-phone" href="<?= e(tel_link($phone)) ?>"><i class="fa fa-phone-alt me-3"></i><?= e($phone) ?></a>
<?php endforeach; ?>
<?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-7 bg-light">
                    <div class="p-4 p-sm-5">
                        <h3 class="mb-4">Request a Booking</h3>
                        <form id="bookingForm" method="post" action="/booking.php" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="bookName" name="name" placeholder="Your Name" minlength="2" maxlength="80" autocomplete="name" required>
                                        <label for="bookName">Your Name</label>
                                        <div class="invalid-feedback" data-default="Please enter your name.">Please enter your name.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="bookPhone" name="phone" placeholder="Phone Number" maxlength="20" pattern="\+?[0-9 \-]{7,20}" autocomplete="tel" required>
                                        <label for="bookPhone">Phone Number</label>
                                        <div class="invalid-feedback" data-default="Please enter a valid phone number.">Please enter a valid phone number.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="bookEvent" name="event_type" required>
                                            <option value="" selected>Select event type</option>
<?php foreach ($eventOptions as $option): ?>
                                            <option><?= e($option) ?></option>
<?php endforeach; ?>
                                        </select>
                                        <label for="bookEvent">Type of Event</label>
                                        <div class="invalid-feedback" data-default="Please choose an event type.">Please choose an event type.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="bookPackage" name="package_id">
                                            <option value="" selected>Not sure yet</option>
<?php foreach ($groups as $group): ?>
                                            <optgroup label="<?= e($group['name']) ?>">
<?php foreach ($group['items'] as $item): ?>
                                                <option value="<?= (int) $item['id'] ?>"><?= e($item['name'] . ' (' . money($item['price']) . ')') ?></option>
<?php endforeach; ?>
                                            </optgroup>
<?php endforeach; ?>
                                        </select>
                                        <label for="bookPackage">Meal Package (optional)</label>
                                        <div class="invalid-feedback" data-default="Please choose a package from the list.">Please choose a package from the list.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" id="bookDate" name="event_date" placeholder="Event Date" min="<?= e($today) ?>" required>
                                        <label for="bookDate">Event Date</label>
                                        <div class="invalid-feedback" data-default="Please choose a date from today onwards.">Please choose a date from today onwards.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" class="form-control" id="bookGuests" name="guests" placeholder="Number of Guests" min="1" max="5000" step="1" inputmode="numeric" required>
                                        <label for="bookGuests">Number of Guests</label>
                                        <div class="invalid-feedback" data-default="Please enter the number of guests.">Please enter the number of guests.</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="bookVenue" name="venue" placeholder="Venue or Area" maxlength="120">
                                        <label for="bookVenue">Venue or Area (optional)</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control booking-details" placeholder="Tell us about your event" id="bookDetails" name="details" maxlength="600"></textarea>
                                        <label for="bookDetails">Event Details &amp; Special Requests (optional)</label>
                                    </div>
                                </div>
                                <!-- Honeypot: hidden from people, filled in by spam bots -->
                                <div class="hp-field" aria-hidden="true">
                                    <label for="bookWebsite">Leave this field empty</label>
                                    <input type="text" id="bookWebsite" name="website" tabindex="-1" autocomplete="off">
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="bookConsent" name="consent" value="1" required>
                                        <label class="form-check-label" for="bookConsent">
                                            I agree to <?= e($s['site_name']) ?> storing these details to handle my booking, as set out in the <a href="/privacy.php" target="_blank" rel="noopener">privacy notice</a>.
                                        </label>
                                        <div class="invalid-feedback" data-default="Please tick this box so we can handle your booking.">Please tick this box so we can handle your booking.</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div id="bookingError" class="alert alert-danger d-none mb-0" role="alert"></div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary w-100 py-3" type="submit">Request Booking</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Booking End -->


        <!-- Footer Start -->
        <div class="container-fluid bg-dark text-light footer pt-5 wow fadeIn" data-wow-delay="0.1s">
            <div class="container py-5">
                <div class="row g-5">
                    <div class="col-lg-4 col-md-6 text-center text-md-start">
                        <img class="footer-logo mb-3" src="/img/logo-emblem.png" alt="<?= e($s['site_name']) ?> emblem" width="520" height="520" loading="lazy">
                        <p class="footer-tagline mb-0"><?= e($s['footer_tagline']) ?></p>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Quick Links</h4>
                        <a class="btn btn-link" href="#home">Home</a>
                        <a class="btn btn-link" href="#about">About</a>
<?php if ($groups): ?>
                        <a class="btn btn-link" href="#packages">Packages</a>
<?php endif; ?>
<?php if ($events): ?>
                        <a class="btn btn-link" href="#events">Events</a>
<?php endif; ?>
                        <a class="btn btn-link" href="#booking">Book Now</a>
                        <a class="btn btn-link" href="/privacy.php">Privacy</a>
                    </div>
                    <div class="col-lg-3 col-md-6">
<?php if ($events): ?>
                        <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">We Cater</h4>
<?php foreach ($events as $event): ?>
                        <p class="mb-2"><?= e($event['name']) ?></p>
<?php endforeach; ?>
<?php endif; ?>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Contact</h4>
                        <p class="mb-2"><i class="fa fa-user me-3"></i><?= e($s['contact_name']) ?></p>
<?php foreach ($phones as $phone): ?>
                        <p class="mb-2"><a href="<?= e(tel_link($phone)) ?>"><i class="fa fa-phone-alt me-3"></i><?= e($phone) ?></a></p>
<?php endforeach; ?>
<?php if ($s['whatsapp_number'] !== ''): ?>
                        <div class="d-flex pt-2">
                            <a class="btn btn-outline-light btn-social" href="<?= e(wa_link($s['whatsapp_number'])) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        </div>
<?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="copyright text-center">
                    &copy; <?= date('Y') ?> <?= e($s['site_name']) ?>. All Rights Reserved.
                </div>
            </div>
        </div>
        <!-- Footer End -->

<?php if ($s['whatsapp_number'] !== ''): ?>

        <!-- WhatsApp Button -->
        <a href="<?= e(wa_link($s['whatsapp_number'], 'Hello ' . $s['contact_name'] . ', I would like to make a booking.')) ?>" class="whatsapp-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp" title="Chat on WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>
<?php endif; ?>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/lib/wow/wow.min.js"></script>

    <!-- Template Javascript -->
    <script src="/js/main.js"></script>
</body>

</html>
