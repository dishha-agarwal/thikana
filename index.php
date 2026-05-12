<?php
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Home';
$currentPage = 'index.php';

$featuredListings = [];
$featuredResult = db()->query("
    SELECT l.*, o.owner_name, o.whatsapp_number,
           (SELECT file_path FROM listing_media WHERE listing_id = l.id AND media_type = 'room' ORDER BY id ASC LIMIT 1) AS preview_image
    FROM listings l
    INNER JOIN owners o ON o.id = l.owner_id
    WHERE l.status = 'approved'
    ORDER BY l.verified DESC, l.no_hidden_charges DESC, l.created_at DESC
    LIMIT 3
");
if ($featuredResult) {
    while ($row = $featuredResult->fetch_assoc()) {
        $featuredListings[] = $row;
    }
}

$roommatePosts = [];
$roommateResult = db()->query("
    SELECT *
    FROM roommate_posts
    WHERE approved = 1 AND expires_at > NOW()
    ORDER BY created_at DESC
    LIMIT 3
");
if ($roommateResult) {
    while ($row = $roommateResult->fetch_assoc()) {
        $roommatePosts[] = $row;
    }
}

$reviewPreviews = [];
$reviewResult = db()->query("
    SELECT r.*, l.title
    FROM reviews r
    INNER JOIN listings l ON l.id = r.listing_id
    WHERE r.approved = 1 AND l.status = 'approved'
    ORDER BY r.created_at DESC
    LIMIT 3
");
if ($reviewResult) {
    while ($row = $reviewResult->fetch_assoc()) {
        $reviewPreviews[] = $row;
    }
}

$searchPlaces = [];
$placesResult = db()->query("
    SELECT DISTINCT location_area AS place_name FROM listings WHERE status = 'approved'
    UNION
    SELECT DISTINCT nearby_college AS place_name FROM listings WHERE status = 'approved'
    ORDER BY place_name ASC
");
if ($placesResult) {
    while ($row = $placesResult->fetch_assoc()) {
        $searchPlaces[] = $row['place_name'];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow">Trust-first student housing</span>
            <h1>No hidden charges. Verified PGs only.</h1>
            <p>Find safe, affordable, and transparent PG/hostel stays near your college without fake listings or confusing fees.</p>

            <div class="hero-actions">
                <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/listings.php">Find a PG</a>
                <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/owner-list-property.php">List Your Property</a>
            </div>

            <div class="hero-highlights">
                <div class="hero-highlight">
                    <strong>Manual verification</strong>
                    <p>Badges are assigned only after team review.</p>
                </div>
                <div class="hero-highlight">
                    <strong>Direct WhatsApp contact</strong>
                    <p>No in-app chat, no lead confusion.</p>
                </div>
                <div class="hero-highlight">
                    <strong>Clarity-first pricing</strong>
                    <p>Rent, deposit, food, and rules shown upfront.</p>
                </div>
            </div>
        </div>

        <div class="hero-visual">
            <div class="trust-panel">
                <div class="trust-panel-top">
                    <div>
                        <p class="eyebrow">What students care about</p>
                        <h3>Home away from home, without the guesswork</h3>
                    </div>
                    <span class="badge badge-primary">Warm + reliable</span>
                </div>
                <ul class="hero-trust-list">
                    <li>Verified by Thikana Team after manual review</li>
                    <li>Fixed media format: room, washroom, kitchen, short video</li>
                    <li>Red flag reporting for suspicious or misleading listings</li>
                    <li>Simple roommate board for real community connections</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class= "section-tight">
    <div class="container">
        <div class= "quick-search">
            <div class= "section-heading">
                <span class="eyebrow">Quick search</span> 
                <h2>Start with budget,area,and your basic needs</h2>
                <p>Keep it simple and jump straight to the listings page with a useful filter set.</p>
            </div>

            <form class="filter-grid" action="<?php echo BASE_URL; ?>/listings.php" method="GET">
                <div class="filter-field col-3"> 
                    <label for="budget">Budget</label>
                    <select name="budget" id="budget">
                        <option value="">Any budget</option> 
                        <option value="0-6000">Up to Rs 6,000</option>
                        <option value="6001-8000">Rs 6,001 - 8,000</option> 
                        <option value="8001-12000">Rs 8,001 - 12,000</option>
                        <option value="12001-999999">Above Rs 12,000</option>
                    </select>
                </div>
                <div class="filter-field col-3"> 
                    <label for="place">College or location</label> 
                    <select name="place" id="place">
                        <option value="">All areas</option> 
                        <?php foreach ($searchPlaces as $place): ?>
                            <option value="<?php echo e($place); ?>"><?php echo e($place); ?></option>
                        <?php endforeach; ?> 
                    </select>
                </div>
                <div class="filter-field col-3"> 
                    <label for="gender">Gender</label>
                    <select name="gender" id="gender"> 
                        <option value="">Any</option>  
                        <option value="Girls">Girls</option>
                        <option value="Boys">Boys</option>  
                        <option value="Unisex">Unisex</option>
                    </select> 
                </div>
                <div class="filter-field col-3"> 
                    <label for="food">Food included</label>
                    <select name="food" id="food"> 
                        <option value="">Any</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select> 
                </div> 
                <div class="filter-field col-12">
                    <button type="submit">Search Listings</button>
                </div> 
            </form>
        </div> 
    </div> 
</section> 

<section class="section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Why Thikana</span>
            <h2>Built around trust, clarity, and direct action</h2>
            <p>We keep the product lightweight on purpose. Fewer layers, more useful information, and real ways to take the next step.</p>
        </div>

        <div class="card-grid three">
            <article class="card icon-card">
                <div class="icon-chip">✓</div>
                <h3>Verified Listings</h3>
                <p>Verified badges are manually assigned after the Thikana team checks details and media.</p>
            </article>
            <article class="card icon-card">
                <div class="icon-chip">Rs</div>
                <h3>No Hidden Charges</h3>
                <p>Rent and deposit stay visible. Listings can also carry a clear no hidden charges flag.</p>
            </article>
            <article class="card icon-card">
                <div class="icon-chip">WA</div>
                <h3>Direct WhatsApp Contact</h3>
                <p>Students talk directly to owners with prefilled WhatsApp messages. No in-app chat needed.</p>
            </article>
            <article class="card icon-card">
                <div class="icon-chip">!</div>
                <h3>Report Scam Option</h3>
                <p>If anything feels off, users can submit a simple report with issue details and screenshot proof.</p>
            </article>
            <article class="card icon-card">
                <div class="icon-chip">i</div>
                <h3>Transparent Rules</h3>
                <p>Curfew, visitor policy, food restrictions, and late-night rules are shown before contact.</p>
            </article>
            <article class="card icon-card">
                <div class="icon-chip">▶</div>
                <h3>Real Photos + Video</h3>
                <p>Owners share fixed media types so students know what they are actually looking at.</p>
            </article>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <div class="card-grid two">
            <div class="card">
                <span class="eyebrow">For students</span>
                <h2>How it works</h2>
                <ol class="mini-list">
                    <li>Search PGs by budget, area, food, and gender preference.</li>
                    <li>Compare options side by side before reaching out.</li>
                    <li>Check trust details like verification, rules, and clear pricing.</li>
                    <li>Talk directly on WhatsApp and decide faster.</li>
                </ol>
            </div>
            <div class="card">
                <span class="eyebrow">For owners</span>
                <h2>Simple owner flow</h2>
                <ol class="mini-list">
                    <li>Fill one beginner-friendly form with your property details.</li>
                    <li>Upload room, washroom, kitchen photos and one short video.</li>
                    <li>Wait for manual verification before the listing goes live.</li>
                    <li>Receive direct leads without managing a complex dashboard.</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Featured listings</span>
            <h2>Trusted picks students can scan quickly</h2>
            <p>These approved listings are shown with pricing, badges, and direct contact options right away.</p>
        </div>

        <div class="listings-grid">
            <?php foreach ($featuredListings as $listing): ?>
                <article class="card listing-card card-soft">
                    <div class="listing-card-image">
                        <img src="<?php echo BASE_URL . '/' . e($listing['preview_image'] ?: 'assets/images/room-sunrise.svg'); ?>" alt="<?php echo e($listing['title']); ?>">
                    </div>
                    <div class="badge-row">
                        <?php if ((int) $listing['verified'] === 1): ?>
                            <span class="badge badge-success badge-verified">Manual verification</span>
                        <?php endif; ?>
                        <?php if ((int) $listing['no_hidden_charges'] === 1): ?>
                            <span class="badge badge-primary badge-clear">No hidden charges</span>
                        <?php endif; ?>
                    </div>
                    <div class="listing-card-header">
                        <div>
                            <h3 class="listing-card-title"><?php echo e($listing['title']); ?></h3>
                            <div class="listing-location">
                                <span><strong><?php echo e($listing['location_area']); ?></strong></span>
                                <span aria-hidden="true">&middot;</span>
                                <span><?php echo e($listing['nearby_college']); ?></span>
                            </div>
                        </div>
                        <div class="listing-price-block">
                            <div class="listing-price"><?php echo e(format_price($listing['rent'])); ?></div>
                            <p class="small-text">per month</p>
                        </div>
                    </div>
                    <div class="listing-feature-list">
                        <div class="listing-feature">
                            <strong>Food</strong>
                            <span><?php echo e($listing['food_included']); ?></span>
                        </div>
                        <div class="listing-feature">
                            <strong>Gender</strong>
                            <span><?php echo e($listing['gender_allowed']); ?></span>
                        </div>
                        <div class="listing-feature">
                            <strong>Room type</strong>
                            <span><?php echo e($listing['room_type']); ?></span>
                        </div>
                        <div class="listing-feature">
                            <strong>Deposit</strong>
                            <span><?php echo e(format_price($listing['security_deposit'])); ?></span>
                        </div>
                    </div>
                    <p><?php echo e(excerpt($listing['description'], 120)); ?></p>
                    <div class="button-group">
                        <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/listing-details.php?id=<?php echo (int) $listing['id']; ?>">View Details</a>
                        <a class="btn btn-whatsapp" target="_blank" rel="noopener" href="<?php echo e(whatsapp_link($listing['whatsapp_number'], 'Hi, I found ' . $listing['title'] . ' on Thikana. I would like to know if it is still available.')); ?>">WhatsApp Owner</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <div class="card">
            <span class="eyebrow">Compare before you decide</span>
            <h2>Shortlist 2 or 3 PGs and view the differences clearly</h2>
            <p>Rent, deposit, food, available seats, rules, and trust flags all show up in one practical comparison table.</p>
            <a class="btn btn-secondary" href="<?php echo BASE_URL; ?>/compare.php">Open Compare Page</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="card-grid two">
            <div class="card">
                <span class="eyebrow">Roommate board</span>
                <h2>Recent community posts</h2>
                <p>Posts are text-first and lightweight. They work like a simple notice board, not a heavy social feed.</p>
                <div class="roommate-list">
                    <?php foreach ($roommatePosts as $post): ?>
                        <article class="roommate-card">
                            <div class="badge-row">
                                <span class="badge badge-info"><?php echo e($post['preferred_area']); ?></span>
                                <span class="badge badge-primary">Budget <?php echo e(format_price($post['budget'])); ?></span>
                            </div>
                            <h3><?php echo e($post['poster_name']); ?></h3>
                            <div class="roommate-meta">
                                <span><?php echo e($post['college_or_workplace']); ?></span>
                                <span><?php echo e($post['gender']); ?></span>
                                <span>Expires <?php echo e(date('d M', strtotime($post['expires_at']))); ?></span>
                            </div>
                            <p><?php echo e($post['note']); ?></p>
                            <a class="btn btn-whatsapp" target="_blank" rel="noopener" href="<?php echo e(whatsapp_link($post['whatsapp_number'], 'Hi, I saw your roommate post on Thikana and wanted to connect.')); ?>">Message on WhatsApp</a>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="form-actions">
                    <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/roommate-board.php">View full roommate board</a>
                </div>
            </div>

            <div class="card">
                <span class="eyebrow">Trust signals</span>
                <h2>Approved student reviews</h2>
                <p>Reviews are moderated and may require proof of stay before becoming visible on the site.</p>
                <div class="review-list">
                    <?php foreach ($reviewPreviews as $review): ?>
                        <article class="review-item">
                            <div class="badge-row">
                                <span class="badge badge-success"><?php echo star_rating_html((int) $review['rating']); ?></span>
                                <span class="badge badge-info"><?php echo e($review['title']); ?></span>
                            </div>
                            <h3><?php echo e($review['reviewer_name']); ?></h3>
                            <p><?php echo e($review['review_text']); ?></p>
                            <p class="small-text">Published after moderation and proof check.</p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
