<?php
require_once __DIR__ . '/includes/db.php'; 

$pageTitle = 'Submit Review';
$currentPage = 'submit-review.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    $listingId = (int) ($_POST['listing_id'] ?? 0);
    $reviewerName = trim($_POST['reviewer_name'] ?? '');
    $rating = (int) ($_POST['rating'] ?? 0);
    $reviewText = trim($_POST['review_text'] ?? ''); 

    if ($listingId <= 0 || $reviewerName === '' || $rating < 1 || $rating > 5 || $reviewText === '') {
        set_flash('error', 'Please complete the review form with a valid listing, rating, and review text.');
        redirect_to(BASE_URL . '/submit-review.php?listing_id=' . $listingId);
    }

    $proofUpload = upload_file(
        $_FILES['proof_file'] ?? null,
        'uploads/reviews',
        ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
        5 * 1024 * 1024,
        ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']
    );

    if (!$proofUpload['success']) {
        set_flash('error', 'Please upload a valid image or PDF as proof of stay.');
        redirect_to(BASE_URL . '/submit-review.php?listing_id=' . $listingId);
    }

    $stmt = db()->prepare('INSERT INTO reviews (listing_id, reviewer_name, rating, review_text, proof_file, approved) VALUES (?, ?, ?, ?, ?, 0)');
    $stmt->bind_param('isiss', $listingId, $reviewerName, $rating, $reviewText, $proofUpload['path']);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        set_flash('success', 'Your review has been submitted and will be published after moderation.');
        redirect_to(BASE_URL . '/listing-details.php?id=' . $listingId);
    }

    set_flash('error', 'We could not save your review right now.');
    redirect_to(BASE_URL . '/submit-review.php?listing_id=' . $listingId);
}

$selectedListingId = isset($_GET['listing_id']) ? (int) $_GET['listing_id'] : 0;
$listings = [];
$listingResult = db()->query("SELECT id, title FROM listings WHERE status = 'approved' ORDER BY title ASC");
if ($listingResult) {
    while ($row = $listingResult->fetch_assoc()) {  
        $listings[] = $row;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <div class="surface">
            <span class="eyebrow">Review flow</span>
            <h1>Share a stay review</h1>
            <p>Reviews are manually moderated and may require proof of stay before they appear publicly.</p>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container form-shell">
        <form class="card form-card" action="<?php echo BASE_URL; ?>/submit-review.php" method="POST" enctype="multipart/form-data" data-validate-form>
            <div class="form-grid">
                <div class="form-field col-12">
                    <label for="listing_id">Listing</label>
                    <select id="listing_id" name="listing_id" required>
                        <option value="">Select a listing</option>
                        <?php foreach ($listings as $listing): ?>
                            <option value="<?php echo (int) $listing['id']; ?>" <?php echo $selectedListingId === (int) $listing['id'] ? 'selected' : ''; ?>><?php echo e($listing['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field col-6">
                    <label for="reviewer_name">Your name</label>
                    <input type="text" id="reviewer_name" name="reviewer_name" required minlength="2" maxlength="100">
                </div>
                <div class="form-field col-6">
                    <label for="rating">Rating</label>
                    <select id="rating" name="rating" required>
                        <option value="">Choose rating</option>
                        <option value="5">5 stars</option>
                        <option value="4">4 stars</option>
                        <option value="3">3 stars</option>
                        <option value="2">2 stars</option>
                        <option value="1">1 star</option>
                    </select>
                </div>
                <div class="form-field col-12">
                    <label for="review_text">Review text</label>
                    <textarea id="review_text" name="review_text" required minlength="25" maxlength="1000" placeholder="Share what matched the listing, what felt transparent, and anything future students should know."></textarea>
                </div>
                <div class="form-field col-12">
                    <label for="proof_file">Proof of stay (image or PDF)</label>
                    <input type="file" id="proof_file" name="proof_file" accept=".jpg,.jpeg,.png,.webp,.pdf" required data-file-hint="#proofHint" data-max-size-mb="5" data-extensions="jpg,jpeg,png,webp,pdf">
                    <p class="small-text" id="proofHint">No file chosen</p>
                </div>
            </div> 
            <div class="form-actions"> 
                <button type="submit">Submit Review</button> 
            </div>
            <p class="form-status" data-form-status aria-live="polite"></p> 
        </form>

        <aside class="details-stack">
            <div class="card helper-box">
                <h3>Moderation note</h3>
                <p>Your review is not published instantly. It stays pending until manual moderation is complete.</p>
            </div>
            <div class="card helper-box">
                <h3>Helpful review angle</h3>  
                <p>Students usually care most about pricing honesty, safety, food, cleanliness, and whether the media matched the real place.</p>
            </div>
        </aside>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>  
