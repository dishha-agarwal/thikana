<?php
require_once __DIR__ . '/includes/db.php';

$listingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pageTitle = 'Listing Details';
$currentPage = 'listing-details.php';

$stmt = db()->prepare("
    SELECT l.*, o.owner_name, o.phone, o.whatsapp_number
    FROM listings l
    INNER JOIN owners o ON o.id = l.owner_id
    WHERE l.id = ? AND l.status = 'approved'
    LIMIT 1
");
$stmt->bind_param('i', $listingId);
$stmt->execute();
$listingResult = $stmt->get_result();
$listing = $listingResult->fetch_assoc();
$stmt->close();

if (!$listing) {
    http_response_code(404); 
    require_once __DIR__ . '/includes/header.php';
    ?>
    <section class="section">
        <div class="container"> 
            <div class="card empty-state">
                <h1>Listing not found</h1>
                <p>This listing may be pending, removed, or the link may be incorrect.</p>
                <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/listings.php">Back to listings</a>
            </div>  
        </div> 
    </section>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $listing['title']; 
$media = get_listing_media_map($listingId); 
$rules = parse_list($listing['rules']); 
$amenities = parse_list($listing['amenities']); 

$reviews = [];
$reviewStmt = db()->prepare(" 
    SELECT *
    FROM reviews
    WHERE listing_id = ? AND approved = 1
    ORDER BY created_at DESC
");
$reviewStmt->bind_param('i', $listingId);
$reviewStmt->execute();
$reviewResult = $reviewStmt->get_result();
while ($row = $reviewResult->fetch_assoc()) {
    $reviews[] = $row;
}
$reviewStmt->close();

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <div class="surface details-header">
            <div class="badge-row">
                <?php if ((int) $listing['verified'] === 1): ?>
                    <span class="badge badge-success badge-verified">Verified by Thikana Team</span>
                <?php endif; ?>
                <?php if ((int) $listing['no_hidden_charges'] === 1): ?>
                    <span class="badge badge-primary badge-clear">No Hidden Charges</span>
                <?php endif; ?>
                <span class="badge badge-info"><?php echo e($listing['gender_allowed']); ?></span>
            </div>
            <h1><?php echo e($listing['title']); ?></h1>
            <p><?php echo e($listing['address']); ?></p>
            <div class="listing-meta">
                <span><?php echo e($listing['location_area']); ?></span>
                <span><?php echo e($listing['nearby_college']); ?></span>
                <span><?php echo e($listing['room_type']); ?></span>
                <span><?php echo (int) $listing['available_seats']; ?> seats available</span>
            </div>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container details-grid">
        <div class="details-stack">
            <section class="card">
                <span class="eyebrow">Media gallery</span>
                <h2>Real photos in a standard format</h2>
                <div class="media-grid">
                    <article class="media-card">
                        <div class="media-card-header">
                            <h3>Room photo</h3>
                            <span class="badge badge-info">Required media</span>
                        </div>
                        <div class="media-card-body">
                            <img src="<?php echo BASE_URL . '/' . e($media['room'] ?: 'assets/images/room-sunrise.svg'); ?>" alt="Room photo for <?php echo e($listing['title']); ?>">
                        </div>
                    </article>
                    <article class="media-card">
                        <div class="media-card-header">
                            <h3>Washroom photo</h3>
                            <span class="badge badge-info">Required media</span>
                        </div>
                        <div class="media-card-body">
                            <img src="<?php echo BASE_URL . '/' . e($media['washroom'] ?: 'assets/images/washroom-soft.svg'); ?>" alt="Washroom photo for <?php echo e($listing['title']); ?>">
                        </div>
                    </article>
                    <article class="media-card">
                        <div class="media-card-header">
                            <h3>Kitchen photo</h3>
                            <span class="badge badge-info">Required media</span>
                        </div>
                        <div class="media-card-body">
                            <img src="<?php echo BASE_URL . '/' . e($media['kitchen'] ?: 'assets/images/kitchen-warm.svg'); ?>" alt="Kitchen photo for <?php echo e($listing['title']); ?>">
                        </div>
                    </article>
                    <article class="media-card">
                        <div class="media-card-header">
                            <h3>Short video walkthrough</h3>
                            <span class="badge badge-primary">Optional</span>
                        </div>
                        <div class="media-card-body">
                            <?php if (!empty($media['video']) && file_exists(PROJECT_ROOT . '/' . $media['video'])): ?>
                                <video controls>
                                    <source src="<?php echo BASE_URL . '/' . e($media['video']); ?>" type="video/mp4">
                                </video>
                            <?php else: ?>
                                <div class="placeholder-panel">
                                    <div>
                                        <h3>Video not available yet</h3>
                                        <p>The fixed video slot is reserved so students know exactly what kind of media to expect.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            </section>

            <section class="card">
                <span class="eyebrow">Pricing transparency</span>
                <h2>Clear pricing before you contact</h2>
                <div class="detail-list">
                    <div class="detail-line">
                        <strong>Monthly rent</strong>
                        <span><?php echo e(format_price($listing['rent'])); ?></span>
                    </div>
                    <div class="detail-line">
                        <strong>Security deposit</strong>
                        <span><?php echo e(format_price($listing['security_deposit'])); ?></span>
                    </div>
                    <div class="detail-line">
                        <strong>Food included</strong>
                        <span><?php echo e($listing['food_included']); ?></span>
                    </div>
                    <div class="detail-line">
                        <strong>Maintenance / electricity</strong>
                        <span>Ask owner for monthly split details if applicable.</span>
                    </div>
                    <div class="detail-line">
                        <strong>Extra charges note</strong>
                        <span><?php echo (int) $listing['no_hidden_charges'] === 1 ? 'No hidden charges declared on this listing.' : 'Please confirm all extras directly with the owner.'; ?></span>
                    </div>
                </div>
            </section>

            <section class="card">
                <span class="eyebrow">Rules</span>
                <h2>House rules shown before move-in</h2>
                <ul class="icon-list">
                    <?php foreach ($rules as $rule): ?>
                        <li>
                            <span class="icon-bullet">i</span>
                            <span><?php echo e($rule); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="card">
                <span class="eyebrow">Amenities</span>
                <h2>What is included</h2>
                <ul class="icon-list">
                    <?php foreach ($amenities as $amenity): ?>
                        <li>
                            <span class="icon-bullet">+</span>
                            <span><?php echo e($amenity); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="card">
                <span class="eyebrow">Description</span>
                <h2>What this stay feels like</h2>
                <p><?php echo nl2br(e($listing['description'])); ?></p>
            </section>

            <section class="card">
                <span class="eyebrow">Reviews</span>
                <h2>Student reviews published after moderation</h2>
                <p class="small-text">Only approved reviews appear here. Proof of stay may be requested before publishing.</p>
                <div class="review-list">
                    <?php if (empty($reviews)): ?>
                        <article class="review-item">
                            <h3>No approved reviews yet</h3>
                            <p>This listing can still receive reviews. New submissions are moderated before they go live.</p>
                        </article>
                    <?php endif; ?>
                    <?php foreach ($reviews as $review): ?>
                        <article class="review-item">
                            <div class="badge-row">
                                <span class="badge badge-success"><?php echo star_rating_html((int) $review['rating']); ?></span>
                                <span class="badge badge-info">Moderated review</span>
                            </div>
                            <h3><?php echo e($review['reviewer_name']); ?></h3>
                            <p><?php echo e($review['review_text']); ?></p>
                            <p class="small-text">Published after moderation and proof check.</p>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="form-actions">
                    <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/submit-review.php?listing_id=<?php echo (int) $listing['id']; ?>">Submit a review</a>
                </div>
            </section>
        </div>

        <aside class="details-stack">
            <section class="card contact-card">
                <span class="eyebrow">Contact owner</span>
                <h2>Talk directly on WhatsApp</h2>
                <p>Keep the next step simple. Ask about availability, move-in date, exact charges, and meal timings.</p>
                <div class="detail-list">
                    <div class="detail-line">
                        <strong>Owner</strong>
                        <span><?php echo e($listing['owner_name']); ?></span>
                    </div>
                    <div class="detail-line">
                        <strong>Phone</strong>
                        <span><?php echo e($listing['phone']); ?></span>
                    </div>
                    <div class="detail-line">
                        <strong>WhatsApp</strong>
                        <span><?php echo e($listing['whatsapp_number']); ?></span>
                    </div>
                </div>
                <div class="form-actions">
                    <a class="btn btn-whatsapp" target="_blank" rel="noopener" href="<?php echo e(whatsapp_link($listing['whatsapp_number'], 'Hi, I found ' . $listing['title'] . ' on Thikana. Is it available right now?')); ?>">WhatsApp Owner</a>
                    <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/compare.php?listing_ids[]=<?php echo (int) $listing['id']; ?>">Add to compare</a>
                </div>
            </section>

            <section class="card alert-card">
                <span class="eyebrow">Trust action</span>
                <h2>Report suspicious listing</h2>
                <p>If any media, pricing, or owner behavior feels misleading, submit a scam report for review.</p>
                <a class="btn btn-danger" href="<?php echo BASE_URL; ?>/submit-scam-report.php?listing_id=<?php echo (int) $listing['id']; ?>">Report Scam</a>
            </section>

            <section class="card sidebar-card">
                <span class="eyebrow">Safety note</span>
                <p class="safety-note">Verified badges are manually assigned by Thikana after team review. They are not automatic or system-generated.</p>
            </section>
        </aside>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
