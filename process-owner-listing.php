<?php
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to(BASE_URL . '/owner-list-property.php');
}

$ownerName = trim($_POST['owner_name'] ?? '');
$phone = normalize_phone($_POST['phone'] ?? '');
$whatsappNumber = normalize_phone($_POST['whatsapp_number'] ?? '');
$title = trim($_POST['title'] ?? '');
$address = trim($_POST['address'] ?? '');
$nearbyCollege = trim($_POST['nearby_college'] ?? ''); 
$locationArea = trim($_POST['location_area'] ?? '');
$rent = (int) ($_POST['rent'] ?? 0);   
$securityDeposit = (int) ($_POST['security_deposit'] ?? 0);
$foodIncluded = $_POST['food_included'] ?? 'No';
$genderAllowed = $_POST['gender_allowed'] ?? 'Unisex';
$roomType = trim($_POST['room_type'] ?? '');
$availableSeats = (int) ($_POST['available_seats'] ?? 0);
$description = trim($_POST['description'] ?? '');  
$rules = trim($_POST['rules'] ?? '');
$amenities = trim($_POST['amenities'] ?? '');
$noHiddenCharges = isset($_POST['no_hidden_charges']) ? 1 : 0;
$manualVerificationAck = isset($_POST['manual_verification_ack']);  

if (
    $ownerName === '' || $phone === '' || $whatsappNumber === '' || $title === '' || $address === '' ||
    $nearbyCollege === '' || $locationArea === '' || $rent <= 0 || $securityDeposit < 0 ||
    $roomType === '' || $availableSeats <= 0 || $description === '' || $rules === '' || $amenities === ''
) {
    set_flash('error', 'Please fill all required listing fields before submitting.');
    redirect_to(BASE_URL . '/owner-list-property.php');
}

if ($noHiddenCharges !== 1 || !$manualVerificationAck) {
    set_flash('error', 'Please confirm the no hidden charges and manual verification checkboxes.');
    redirect_to(BASE_URL . '/owner-list-property.php');
}

$imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
$imageMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];   

$roomUpload = upload_file($_FILES['room_photo'] ?? null, 'uploads/listings', $imageExtensions, 5 * 1024 * 1024, $imageMimeTypes);
$washroomUpload = upload_file($_FILES['washroom_photo'] ?? null, 'uploads/listings', $imageExtensions, 5 * 1024 * 1024, $imageMimeTypes);
$kitchenUpload = upload_file($_FILES['kitchen_photo'] ?? null, 'uploads/listings', $imageExtensions, 5 * 1024 * 1024, $imageMimeTypes);

if (!$roomUpload['success'] || !$washroomUpload['success'] || !$kitchenUpload['success']) {
    $messages = [
        $roomUpload['message'] ?? '',
        $washroomUpload['message'] ?? '',
        $kitchenUpload['message'] ?? '',
    ];   
    set_flash('error', trim(implode(' ', array_filter($messages))) ?: 'Please upload valid JPG, PNG, or WEBP photos.');
    redirect_to(BASE_URL . '/owner-list-property.php');
}

$videoUploadPath = null;
if (isset($_FILES['property_video']) && ($_FILES['property_video']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $videoUpload = upload_file(
        $_FILES['property_video'],
        'uploads/listings',
        ['mp4'],
        10 * 1024 * 1024,
        ['video/mp4', 'application/mp4']         
    );

    if (!$videoUpload['success']) {
        set_flash('error', 'Please upload a valid MP4 video under 10MB.');
        redirect_to(BASE_URL . '/owner-list-property.php');
    }

    $videoUploadPath = $videoUpload['path'];       
}

db()->begin_transaction();

try {
    $ownerStmt = db()->prepare('INSERT INTO owners (owner_name, phone, whatsapp_number) VALUES (?, ?, ?)');
    $ownerStmt->bind_param('sss', $ownerName, $phone, $whatsappNumber);
    $ownerStmt->execute();
    $ownerId = $ownerStmt->insert_id;
    $ownerStmt->close();

    $listingStmt = db()->prepare("
        INSERT INTO listings (
            owner_id, title, address, nearby_college, location_area, rent, security_deposit,
            food_included, gender_allowed, room_type, available_seats, description, rules,
            amenities, verified, no_hidden_charges, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'pending')
    ");
    $listingStmt->bind_param(
        'issssiisssisssi',               
        $ownerId,
        $title,
        $address,
        $nearbyCollege,
        $locationArea,
        $rent,
        $securityDeposit,
        $foodIncluded,
        $genderAllowed,
        $roomType,
        $availableSeats,
        $description,
        $rules,
        $amenities,
        $noHiddenCharges
    );
    $listingStmt->execute();
    $listingId = $listingStmt->insert_id;            
    $listingStmt->close();

    $mediaStmt = db()->prepare('INSERT INTO listing_media (listing_id, media_type, file_path) VALUES (?, ?, ?)');

    $roomMediaType = 'room';
    $mediaStmt->bind_param('iss', $listingId, $roomMediaType, $roomUpload['path']);
    $mediaStmt->execute();

    $washroomMediaType = 'washroom';
    $mediaStmt->bind_param('iss', $listingId, $washroomMediaType, $washroomUpload['path']);
    $mediaStmt->execute();

    $kitchenMediaType = 'kitchen';                
    $mediaStmt->bind_param('iss', $listingId, $kitchenMediaType, $kitchenUpload['path']);
    $mediaStmt->execute();

    if ($videoUploadPath !== null) {
        $videoMediaType = 'video';
        $mediaStmt->bind_param('iss', $listingId, $videoMediaType, $videoUploadPath);
        $mediaStmt->execute();
    }

    $mediaStmt->close();             

    db()->commit();
    set_flash('success', 'Your property has been submitted. It will appear only after manual verification by the Thikana team.');
} catch (Throwable $exception) {
    db()->rollback();
    set_flash('error', 'We could not save the listing right now. Please try again.');
}

redirect_to(BASE_URL . '/owner-list-property.php');
