<?php
require_once __DIR__ . '/includes/db.php' ;

$pageTitle ='Listings';
$currentPage ='listings.php';

$budget = $_GET['budget'] ?? '';
$place = trim($_GET['place'] ?? '');
$gender =$_GET['gender'] ?? '';
$food =$_GET['food'] ?? '';
$verifiedOnly = isset($_GET['verified']) && $_GET['verified'] === '1';
$sort =$_GET['sort'] ?? 'verified_first';
$activeFilters = 0;

$conditions =["l.status = 'approved'"];
$params = [];
$types ='';

if ($budget!== '') {
    $activeFilters++;
    $range = explode('-', $budget);
    if (count($range)===2) {
        $minBudget =(int) $range[0];
        $maxBudget =(int) $range[1];
        $conditions[] = 'l.rent BETWEEN ? AND ?';
        $params[] =$minBudget;
        $params[] =$maxBudget;
        $types .= 'ii';
    }
}

if ($place!=='') {
    $activeFilters++;
    $conditions[] ='(l.location_area = ? OR l.nearby_college = ?)';
    $params[] =$place;
    $params[] =$place;
    $types .= 'ss';
}

if ($gender!=='') {
    $activeFilters++;
    $conditions[] ='l.gender_allowed = ?';
    $params[] =$gender;
    $types .= 's';
}

if ($food!=='') {
    $activeFilters++;
    $conditions[] = 'l.food_included = ?';
    $params[] =$food;
    $types .='s';
}

if ($verifiedOnly) {
    $activeFilters++;
    $conditions[] = 'l.verified = 1';
}

$orderBy = 'l.verified DESC, l.rent ASC';
if ($sort ==='price_asc') {
    $orderBy ='l.rent ASC';
} elseif ($sort ==='price_desc') {
    $orderBy = 'l.rent DESC';
}

$sql = "
    SELECT l.*, o.owner_name, o.whatsapp_number,
           (SELECT file_path FROM listing_media WHERE listing_id = l.id AND media_type = 'room' ORDER BY id ASC LIMIT 1) AS preview_image
    FROM listings l
    INNER JOIN owners o ON o.id =l.owner_id
    WHERE " . implode(' AND ', $conditions) . "
    ORDER BY $orderBy, l.created_at DESC
";

$stmt = db()->prepare($sql);
if (!empty($params)){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute() ;
$result = $stmt->get_result() ;

$listings = [];
while ($row = $result->fetch_assoc()){
    $listings[] = $row;
}
$stmt->close();

$places = [];
$placesResult = db()->query("
    SELECT DISTINCT location_area AS place_name FROM listings WHERE status = 'approved'
    UNION
    SELECT DISTINCT nearby_college AS place_name FROM listings WHERE status = 'approved'
    ORDER BY place_name ASC
");
if ($placesResult){
    while ($row =$placesResult->fetch_assoc()) {
        $places[] =$row['place_name'];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <div class="surface">
            <span class="eyebrow">Browse listings</span>
            <h1>PGs and hostels with clearer pricing and trust signals</h1>
            <p>Filter by budget, college, food, and verified status. Then take the next step directly on WhatsApp.</p>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container listings-layout">
        <aside class="card sticky-card">
            <span class="eyebrow">Filters</span>
            <form method="GET" action="<?php echo BASE_URL; ?>/listings.php" class="filter-grid">
                <div class="filter-field col-12">
                    <label for="budget">Budget range</label>
                    <select name="budget" id="budget">
                        <option value="">Any budget</option>
                        <option value="0-6000" <?php echo $budget === '0-6000' ? 'selected' : ''; ?>>Up to Rs 6,000</option>
                        <option value="6001-8000" <?php echo $budget === '6001-8000' ? 'selected' : ''; ?>>Rs 6,001 - 8,000</option>
                        <option value="8001-12000" <?php echo $budget === '8001-12000' ? 'selected' : ''; ?>>Rs 8,001 - 12,000</option>
                        <option value="12001-999999" <?php echo $budget === '12001-999999' ? 'selected' : ''; ?>>Above Rs 12,000</option>
                    </select>
                </div>
                <div class="filter-field col-12">
                    <label for="place">College or location</label>
                    <select name="place" id="place">
                        <option value="">All places</option>
                        <?php foreach ($places as $placeOption): ?>
                            <option value="<?php echo e($placeOption); ?>" <?php echo $place === $placeOption ? 'selected' : ''; ?>><?php echo e($placeOption); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-field col-12">
                    <label for="gender">Gender allowed</label>
                    <select name="gender" id="gender">
                        <option value="">Any</option>
                        <option value="Girls" <?php echo $gender === 'Girls' ? 'selected' : ''; ?>>Girls</option>
                        <option value="Boys" <?php echo $gender === 'Boys' ? 'selected' : ''; ?>>Boys</option>
                        <option value="Unisex" <?php echo $gender === 'Unisex' ? 'selected' : ''; ?>>Unisex</option>
                    </select>
                </div>
                <div class="filter-field col-12">
                    <label for="food">Food included</label>
                    <select name="food" id="food">
                        <option value="">Any</option>
                        <option value="Yes" <?php echo $food === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                        <option value="No" <?php echo $food === 'No' ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>
                <div class="filter-field col-12">
                    <label for="sort">Sort by</label>
                    <select name="sort" id="sort">
                        <option value="verified_first" <?php echo $sort === 'verified_first' ? 'selected' : ''; ?>>Verified first</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price low to high</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price high to low</option>
                    </select>
                </div>
                <div class="filter-field col-12">
                    <label class="checkbox-card">
                        <input type="checkbox" name="verified" value="1" <?php echo $verifiedOnly ? 'checked' : ''; ?>>
                        <span>Show verified listings only</span>
                    </label>
                </div>
                <div class="filter-field col-12">
                    <button type="submit">Apply Filters</button>
                </div>
                <div class="filter-field col-12">
                    <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/listings.php">Reset filters</a>
                </div>
            </form>
        </aside>

        <div>
            <div class="card card-soft">
                <div class="results-toolbar">
                    <div class="stack-sm">
                        <div class="badge-row">
                            <span class="badge badge-info"><?php echo count($listings); ?> listings found</span>
                            <span class="badge badge-primary">Approved listings only</span>
                            <?php if ($sort === 'price_asc'): ?>
                                <span class="badge badge-neutral">Price low to high</span>
                            <?php elseif ($sort === 'price_desc'): ?>
                                <span class="badge badge-neutral">Price high to low</span>
                            <?php else: ?>
                                <span class="badge badge-neutral">Verified first</span>
                            <?php endif; ?>
                        </div>
                        <p>Start with trust, then compare the actual trade-offs: rent, food, room type, and seats.</p>
                    </div>
                    <div class="selection-summary">
                        <?php echo $activeFilters > 0 ? $activeFilters . ' active filters applied' : 'No extra filters applied yet'; ?>
                    </div>
                </div>
                <?php if ($activeFilters > 0): ?>
                    <div class="active-filter-row">
                        <?php if ($budget !== ''): ?><span class="badge badge-info">Budget: <?php echo e($budget); ?></span><?php endif; ?>
                        <?php if ($place !== ''): ?><span class="badge badge-info">Place: <?php echo e($place); ?></span><?php endif; ?>
                        <?php if ($gender !== ''): ?><span class="badge badge-info">Gender: <?php echo e($gender); ?></span><?php endif; ?>
                        <?php if ($food !== ''): ?><span class="badge badge-info">Food: <?php echo e($food); ?></span><?php endif; ?>
                        <?php if ($verifiedOnly): ?><span class="badge badge-success">Verified only</span><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="listings-grid" style="margin-top: 1.25rem;">
                <?php if (empty($listings)): ?>
                    <div class="card empty-state">
                        <h3>No listing matched these filters</h3>
                        <p>Try changing the budget or location filter and you should see more approved options.</p>
                        <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/listings.php">Reset all filters</a>
                    </div>
                <?php endif; ?>

                <?php foreach ($listings as $listing): ?>
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
                            <span class="badge badge-info"><?php echo e($listing['room_type']); ?></span>
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
                                <strong>Seats</strong>
                                <span><?php echo (int) $listing['available_seats']; ?> available</span>
                            </div>
                            <div class="listing-feature">
                                <strong>Deposit</strong>
                                <span><?php echo e(format_price($listing['security_deposit'])); ?></span>
                            </div>
                        </div>
                        <p><?php echo e(excerpt($listing['description'], 140)); ?></p>
                        <div class="button-group">
                            <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/listing-details.php?id=<?php echo (int) $listing['id']; ?>">View Details</a>
                            <a class="btn btn-whatsapp" target="_blank" rel="noopener" href="<?php echo e(whatsapp_link($listing['whatsapp_number'], 'Hi, I found ' . $listing['title'] . ' on Thikana. Please share availability and move-in details.')); ?>">WhatsApp Owner</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
