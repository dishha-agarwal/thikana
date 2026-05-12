<?php
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Compare Listings';
$currentPage = 'compare.php';

$allListings = [];
$allListingsResult = db()->query("
    SELECT l.id, l.title, l.location_area, l.nearby_college, l.rent, l.verified,
           (SELECT file_path FROM listing_media WHERE listing_id = l.id AND media_type = 'room' ORDER BY id ASC LIMIT 1) AS preview_image
    FROM listings l
    WHERE l.status = 'approved'
    ORDER BY l.verified DESC, l.rent ASC
");
if ($allListingsResult) {
    while ($row = $allListingsResult->fetch_assoc()) {
        $allListings[] = $row;
    }
}
$selectedIds = [];
if(!empty($_GET['listing_ids']) && is_array($_GET['listing_ids'])) {
    foreach ($_GET['listing_ids'] as $id) {
        $selectedIds[] = (int) $id;
    } 
    $selectedIds = array_values(array_unique(array_filter($selectedIds)));
    $selectedIds = array_slice($selectedIds, 0, 3);
}
$comparisonListings = [];
if (!empty($selectedIds)) {
    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
    $types = str_repeat('i', count($selectedIds));
    $sql = "
        SELECT l.*, o.owner_name, o.whatsapp_number
        FROM listings l
        INNER JOIN owners o ON o.id = l.owner_id
        WHERE l.status = 'approved' AND l.id IN ($placeholders)
    ";
    $stmt = db()->prepare($sql);
    $stmt->bind_param($types, ...$selectedIds);
    $stmt->execute();
    $comparisonResult = $stmt->get_result();
    while ($row = $comparisonResult->fetch_assoc()) {
        $comparisonListings[$row['id']] = $row;
    }
    $stmt->close();
}

$selectedCount = count($selectedIds);

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <div class="surface">
            <span class="eyebrow">Compare tool</span>
            <h1>Compare 2 or 3 listings side by side</h1>
            <p>Use this before you reach out on WhatsApp so your shortlisting feels clearer and faster.</p>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container details-stack">
        <form class="comparison-select" method="GET" action="<?php echo BASE_URL; ?>/compare.php" data-compare-limit>
            <div class="section-heading">
                <span class="eyebrow">Pick listings</span>
                <h2>Choose up to 3 approved listings</h2>
                <p>We recommend comparing at least 2 options to spot pricing, rules, and trust differences quickly.</p>
            </div>
            <div class="selection-summary" data-compare-summary>
                <?php if ($selectedCount >= 2): ?>
                    <?php echo $selectedCount; ?> listings selected. Scroll down to review the table.
                <?php elseif ($selectedCount === 1): ?>
                    1 listing selected. Pick at least 1 more to compare.
                <?php else: ?>
                    Pick 2 or 3 listings to unlock the comparison table.
                <?php endif; ?>
            </div>
            <div class="compare-selection-grid" style="margin-top: 1rem;">
                <?php foreach ($allListings as $listing): ?>
                    <label class="card listing-card compare-choice <?php echo in_array((int) $listing['id'], $selectedIds, true) ? 'is-selected' : ''; ?>" data-compare-card>
                        <div class="listing-card-image">
                            <img src="<?php echo BASE_URL . '/' . e($listing['preview_image'] ?: 'assets/images/room-sunrise.svg'); ?>" alt="<?php echo e($listing['title']); ?>">
                        </div>
                        <div class="badge-row">
                            <?php if ((int) $listing['verified'] === 1): ?>
                                <span class="badge badge-success badge-verified">Manual verification</span>
                            <?php endif; ?>
                            <span class="badge badge-primary badge-clear"><?php echo e(format_price($listing['rent'])); ?>/month</span>
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
                        </div>
                        <div class="checkbox-card">
                            <input type="checkbox" name="listing_ids[]" value="<?php echo (int) $listing['id']; ?>" <?php echo in_array((int) $listing['id'], $selectedIds, true) ? 'checked' : ''; ?>>
                            <span>Add this listing to compare</span>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="form-actions">
                <button type="submit" data-compare-submit <?php echo $selectedCount < 2 ? 'disabled' : ''; ?>>Compare selected listings</button>
                <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/compare.php">Clear selection</a>
            </div>
        </form>

        <?php if (count($selectedIds) < 2): ?>
            <div class="card">
                <h2>Pick at least 2 listings to see the comparison table</h2>
                <p>Once you choose 2 or 3 options, rent, deposit, food, rules, and amenities will show in a single table.</p>
            </div>
        <?php else: ?>
            <div class="card">
                <span class="eyebrow">Comparison table</span>
                <h2>Shortlist with confidence</h2>
                <div class="compare-table-summary">
                    <span class="badge badge-success">Trust first</span>
                    <span class="badge badge-primary">Pricing visible</span>
                    <span class="badge badge-info">Rules side by side</span>
                </div>
                <div class="compare-table-wrap">
                    <table class="compare-table">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <?php foreach ($selectedIds as $selectedId): ?>
                                    <?php if (!isset($comparisonListings[$selectedId])) { continue; } ?>
                                    <th><?php echo e($comparisonListings[$selectedId]['title']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Rent</td>
                                <?php foreach ($selectedIds as $selectedId): ?>
                                    <?php if (!isset($comparisonListings[$selectedId])) { continue; } ?>
                                    <td><?php echo e(format_price($comparisonListings[$selectedId]['rent'])); ?>/month</td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td>Security deposit</td>
                                <?php foreach ($selectedIds as $selectedId): ?>
                                    <?php if (!isset($comparisonListings[$selectedId])) { continue; } ?>
                                    <td><?php echo e(format_price($comparisonListings[$selectedId]['security_deposit'])); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td>Food included</td>
                                <?php foreach ($selectedIds as $selectedId): ?>
                                    <?php if (!isset($comparisonListings[$selectedId])) { continue; } ?>
                                    <td><?php echo e($comparisonListings[$selectedId]['food_included']); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td>Nearby college / location</td>
                                <?php foreach ($selectedIds as $selectedId): ?>
                                    <?php if (!isset($comparisonListings[$selectedId])) { continue; } ?>
                                    <td><?php echo e($comparisonListings[$selectedId]['nearby_college']); ?><br><?php echo e($comparisonListings[$selectedId]['location_area']); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td>Verified</td>
                                <?php foreach ($selectedIds as $selectedId): ?>
                                    <?php if (!isset($comparisonListings[$selectedId])) { continue; } ?>
                                    <td><?php echo (int) $comparisonListings[$selectedId]['verified'] === 1 ? 'Yes, manually verified' : 'Not yet verified'; ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td>No hidden charges</td>
                                <?php foreach ($selectedIds as $selectedId): ?>
                                    <?php if (!isset($comparisonListings[$selectedId])) { continue; } ?>
                                    <td><?php echo (int) $comparisonListings[$selectedId]['no_hidden_charges'] === 1 ? 'Declared' : 'Confirm with owner'; ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td>Room type</td>
                                <?php foreach ($selectedIds as $selectedId): ?>
                                    <?php if (!isset($comparisonListings[$selectedId])) { continue; } ?>
                                    <td><?php echo e($comparisonListings[$selectedId]['room_type']); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td>Available seats</td>
                                <?php foreach ($selectedIds as $selectedId): ?>
                                    <?php if (!isset($comparisonListings[$selectedId])) { continue; } ?>
                                    <td><?php echo (int) $comparisonListings[$selectedId]['available_seats']; ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td>Rules</td>
                                <?php foreach ($selectedIds as $selectedId): ?>
                                    <?php if (!isset($comparisonListings[$selectedId])) { continue; } ?>
                                    <td><?php echo nl2br(e($comparisonListings[$selectedId]['rules'])); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td>Amenities</td>
                                <?php foreach ($selectedIds as $selectedId): ?>
                                    <?php if (!isset($comparisonListings[$selectedId])) { continue; } ?>
                                    <td><?php echo e($comparisonListings[$selectedId]['amenities']); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td>Next step</td>
                                <?php foreach ($selectedIds as $selectedId): ?>
                                    <?php if (!isset($comparisonListings[$selectedId])) { continue; } ?>
                                    <td>
                                        <div class="stack-sm">
                                            <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/listing-details.php?id=<?php echo (int) $comparisonListings[$selectedId]['id']; ?>">View details</a>
                                            <a class="btn btn-whatsapp" target="_blank" rel="noopener" href="<?php echo e(whatsapp_link($comparisonListings[$selectedId]['whatsapp_number'], 'Hi, I found ' . $comparisonListings[$selectedId]['title'] . ' on Thikana compare. Please share latest availability.')); ?>">WhatsApp owner</a>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="small-text scroll-hint">On smaller screens, swipe the table horizontally. The field column stays visible for easier scanning.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php
require_once __DIR__ . '/includes/db.php';
$pageTitle='Compare PGs';
$currentPage='compare.php';
$selectedIds=[];
if(!empty($_GET['listing_ids']) && is_array($_GET['listing_ids'])){
foreach($_GET['listing_ids'] as $id){$selectedIds[]=(int)$id;}
$selectedIds=array_unique($selectedIds);
$selectedIds=array_slice($selectedIds,0,3);
}
$listings=[];
if(!empty($selectedIds)){
$placeholders=implode(',',array_fill(0,count($selectedIds),'?'));
$sql="SELECT * FROM listings WHERE status='approved' AND id IN ($placeholders)";
$stmt=db()->prepare($sql);
$types=str_repeat('i',count($selectedIds));
$stmt->bind_param($types,...$selectedIds);
$stmt->execute();
$result=$stmt->get_result();
while($row=$result->fetch_assoc()){$listings[]=$row;}
$stmt->close();
}
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
<div class="container">
<div class="card"><h1>Compare PG Listings</h1></div>

<?php if(count($listings)<2): ?>

<div class="card"><h3>Select at least 2 PGs</h3></div>

<?php else: ?>

<div class="card">
<table class="compare-table">
<thead>
<tr>
<th>Feature</th>
<?php foreach($listings as $listing): ?>
<th><?php echo e($listing['title']); ?></th>
<?php endforeach; ?>
</tr>
</thead>

<tbody>

<tr>
<td>Rent</td>
<?php foreach($listings as $listing): ?>
<td><?php echo e(format_price($listing['rent'])); ?></td>
<?php endforeach; ?>
</tr>

<tr>
<td>Deposit</td>
<?php foreach($listings as $listing): ?>
<td><?php echo e(format_price($listing['security_deposit'])); ?></td>
<?php endforeach; ?>
</tr>

<tr>
<td>Food</td>
<?php foreach($listings as $listing): ?>
<td><?php echo e($listing['food_included']); ?></td>
<?php endforeach; ?>
</tr>

<tr>
<td>Room Type</td>
<?php foreach($listings as $listing): ?>
<td><?php echo e($listing['room_type']); ?></td>
<?php endforeach; ?>
</tr>

<tr>
<td>Seats</td>
<?php foreach($listings as $listing): ?>
<td><?php echo (int)$listing['available_seats']; ?></td>
<?php endforeach; ?>
</tr>

<tr>
<td>Location</td>
<?php foreach($listings as $listing): ?>
<td><?php echo e($listing['location_area']); ?></td>
<?php endforeach; ?>
</tr>

<tr>
<td>Verified</td>
<?php foreach($listings as $listing): ?>
<td><?php echo (int)$listing['verified']===1?'Yes':'No'; ?></td>
<?php endforeach; ?>
</tr>

</tbody>
</table>
</div>

<?php endif; ?>

</div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
