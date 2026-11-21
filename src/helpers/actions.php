<?php
declare(strict_types=1);

function handlePostActions(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    if (!verifyCsrf()) {
        flash('Invalid session token. Please try again.', 'danger');
        header('Location: index.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $user = currentUser();
    $role = currentRole();

    switch ($action) {
        case 'login': {
            $email = trim((string)($_POST['email'] ?? ''));
            if ($email === '') {
                flash('Please enter your email address.');
                header('Location: index.php?modal=login');
                exit;
            }
            $matchedKey = 'customer';
            foreach ($_SESSION['pw_demo_users'] as $k => $u) {
                if (strcasecmp($u['email'], $email) === 0) {
                    $matchedKey = $k;
                    break;
                }
            }
            if ($matchedKey === 'customer' && strcasecmp($_SESSION['pw_demo_users']['customer']['email'], $email) !== 0) {
                $_SESSION['pw_demo_users']['customer']['email'] = $email;
            }
            $targetRole = $_SESSION['pw_demo_users'][$matchedKey]['roles'][0] ?? 'customer';
            loginAsDemo($targetRole, $matchedKey);
            flash('Logged in as ' . $_SESSION['pw_user']['name'] . '.');
            header('Location: index.php?view=workspace&page=' . urlencode($targetRole === 'guest' ? 'Find Parking' : 'Dashboard'));
            exit;
        }

        case 'register': {
            $name = trim((string)($_POST['full_name'] ?? 'Jordan Davis'));
            $email = trim((string)($_POST['email'] ?? 'customer@parkwise.ph'));
            $_SESSION['pw_demo_users']['customer']['name'] = $name ?: 'Jordan Davis';
            $_SESSION['pw_demo_users']['customer']['email'] = $email ?: 'customer@parkwise.ph';
            loginAsDemo('customer', 'customer');
            flash('Customer account created.');
            header('Location: index.php?view=workspace&page=Dashboard');
            exit;
        }

        case 'demo': {
            $demoRole = (string)($_POST['role'] ?? 'customer');
            $valid = ['customer', 'owner', 'guard', 'superadmin', 'guest'];
            if (!in_array($demoRole, $valid, true)) {
                $demoRole = 'customer';
            }
            loginAsDemo($demoRole, $demoRole);
            flash((roleLabels()[$demoRole] ?? 'Demo') . ' demo opened.');
            header('Location: index.php?view=workspace&page=' . urlencode($demoRole === 'guest' ? 'Find Parking' : 'Dashboard'));
            exit;
        }

        case 'switch_role': {
            $newRole = (string)($_POST['role'] ?? 'customer');
            if ($user && in_array($newRole, $user['roles'] ?? [], true)) {
                $_SESSION['pw_role'] = $newRole;
                flash('Switched to ' . (roleLabels()[$newRole] ?? $newRole) . '.');
            }
            header('Location: index.php?view=workspace&page=Dashboard');
            exit;
        }

        case 'logout': {
            unset($_SESSION['pw_user'], $_SESSION['pw_role']);
            flash('You have logged out.');
            header('Location: index.php');
            exit;
        }

        case 'toggle_fav': {
            $lotId = (int)($_POST['lot_id'] ?? 0);
            if ($lotId > 0) {
                $favs = $_SESSION['pw_favorites'] ?? [];
                if (in_array($lotId, $favs, true)) {
                    $_SESSION['pw_favorites'] = array_values(array_filter($favs, static fn(int $id): bool => $id !== $lotId));
                    flash('Removed from saved parking lots.');
                } else {
                    $favs[] = $lotId;
                    $_SESSION['pw_favorites'] = array_unique($favs);
                    flash('Saved to your favorite parking lots.');
                }
            }
            $back = $_POST['redirect'] ?? 'index.php';
            header('Location: ' . $back);
            exit;
        }

        case 'guard_mode': {
            $mode = ($_POST['mode'] ?? 'in') === 'out' ? 'out' : 'in';
            $_SESSION['pw_guard_mode'] = $mode;
            $_SESSION['pw_guard_result'] = null;
            header('Location: index.php?view=workspace&page=Dashboard');
            exit;
        }

        case 'guard_submit': {
            $plate = strtoupper(preg_replace('/\s+/', ' ', trim((string)($_POST['plate'] ?? ''))));
            $mode = $_SESSION['pw_guard_mode'] ?? 'in';
            $guardName = $user['name'] ?? 'Chris Reyes';

            if ($plate === '') {
                flash('Please enter a vehicle plate number.');
                header('Location: index.php?view=workspace&page=Dashboard');
                exit;
            }
            if (in_array($_SESSION['pw_status'][$guardName] ?? '', ['Suspended', 'Revoked'], true)) {
                flash('Your guard access was revoked. Contact the owner.');
                header('Location: index.php?view=workspace&page=Dashboard');
                exit;
            }

            $lot = &$_SESSION['pw_lots'][0];
            if ($mode === 'in') {
                foreach ($_SESSION['pw_sessions'] as $s) {
                    if ($s['plate'] === $plate && $s['status'] === 'Active') {
                        flash('This vehicle already has an active parking session.');
                        header('Location: index.php?view=workspace&page=Dashboard');
                        exit;
                    }
                }
                if (!empty($lot['closed']) || (int)$lot['available'] < 1) {
                    flash('The lot is full or closed. No more vehicles can enter.');
                    header('Location: index.php?view=workspace&page=Dashboard');
                    exit;
                }
                $now = time();
                $timeIn = date('h:i A', $now);
                $session = [
                    'id' => $now,
                    'lotId' => $lot['id'],
                    'business' => $lot['name'],
                    'plate' => $plate,
                    'date' => date('m/d/Y', $now),
                    'timeIn' => $timeIn,
                    'timeOut' => '',
                    't' => $now,
                    'rate' => $lot['rate'],
                    'hours' => null,
                    'total' => null,
                    'status' => 'Active',
                    'paid' => false,
                    'no' => '',
                    'by' => $guardName,
                ];
                $_SESSION['pw_sessions'][] = $session;
                $lot['available'] = max(0, (int)$lot['available'] - 1);
                foreach ($_SESSION['pw_spaces'] as &$sp) {
                    if ($sp['status'] === 'Available') {
                        $sp['status'] = 'Occupied';
                        $sp['plate'] = $plate;
                        $sp['timeIn'] = $timeIn;
                        break;
                    }
                }
                unset($sp);
                logActivity($guardName, 'Vehicle time-in', $plate . ' · ' . $lot['name']);
                $_SESSION['pw_guard_result'] = [
                    'type' => 'in',
                    'plate' => $plate,
                    'business' => $lot['name'],
                    'timeIn' => $timeIn,
                    'available' => $lot['available'],
                ];
                flash('Vehicle time-in recorded.');
            } else {
                $foundIndex = null;
                foreach ($_SESSION['pw_sessions'] as $idx => $s) {
                    if ($s['plate'] === $plate && $s['status'] === 'Active') {
                        $foundIndex = $idx;
                        break;
                    }
                }
                if ($foundIndex === null) {
                    $_SESSION['pw_guard_result'] = ['type' => 'missing', 'plate' => $plate];
                    header('Location: index.php?view=workspace&page=Dashboard');
                    exit;
                }
                $s = &$_SESSION['pw_sessions'][$foundIndex];
                $now = time();
                $elapsed = max(1, (int)ceil(($now - ($s['t'] ?? $now)) / 3600));
                $s['timeOut'] = date('h:i A', $now);
                $s['status'] = 'Completed';
                $s['hours'] = $elapsed;
                $s['total'] = $elapsed * (float)$s['rate'];
                $lot['available'] = min((int)$lot['total'], (int)$lot['available'] + 1);
                foreach ($_SESSION['pw_spaces'] as &$sp) {
                    if (($sp['plate'] ?? '') === $plate) {
                        $sp['status'] = 'Available';
                        $sp['plate'] = null;
                        $sp['timeIn'] = null;
                        break;
                    }
                }
                unset($sp);
                logActivity($guardName, 'Vehicle time-out', $plate . ' · ₱' . $s['total']);
                $_SESSION['pw_guard_result'] = [
                    'type' => 'out',
                    'session' => $s,
                ];
                flash('Time-out recorded. Confirm payment to print the bill.');
            }
            header('Location: index.php?view=workspace&page=Dashboard');
            exit;
        }

        case 'confirm_payment': {
            $sid = (int)($_POST['session_id'] ?? 0);
            foreach ($_SESSION['pw_sessions'] as &$s) {
                if ((int)$s['id'] === $sid) {
                    if (empty($s['paid'])) {
                        $s['paid'] = true;
                        $s['no'] = $s['no'] ?: 'PW-' . str_pad((string)($sid % 1000000), 6, '0', STR_PAD_LEFT);
                        logActivity($s['by'] ?? 'Guard', 'Bill printed', $s['plate'] . ' · ₱' . $s['total'], $s['no']);
                    }
                    break;
                }
            }
            unset($s);
            flash('Payment recorded and receipt prepared.');
            header('Location: index.php?view=workspace&page=Dashboard&receipt=' . $sid);
            exit;
        }

        case 'add_vehicle': {
            $model = trim((string)($_POST['make_model'] ?? ''));
            $plate = strtoupper(trim((string)($_POST['plate_number'] ?? '')));
            $type = trim((string)($_POST['vehicle_type'] ?? 'Sedan'));
            $color = trim((string)($_POST['color'] ?? 'Silver'));
            if ($model !== '' && $plate !== '') {
                $_SESSION['pw_vehicles'][] = [
                    'id' => time(),
                    'model' => $model,
                    'plate' => $plate,
                    'type' => $type,
                    'color' => $color ?: 'Silver',
                    'default' => count($_SESSION['pw_vehicles']) === 0,
                    'added' => date('M j, Y'),
                ];
                flash('Vehicle added to your profile.');
            }
            header('Location: index.php?view=workspace&page=' . urlencode('My Vehicles'));
            exit;
        }

        case 'set_default_vehicle': {
            $vid = (int)($_POST['vehicle_id'] ?? 0);
            foreach ($_SESSION['pw_vehicles'] as &$v) {
                $v['default'] = ((int)$v['id'] === $vid);
            }
            unset($v);
            flash('Default vehicle updated.');
            header('Location: index.php?view=workspace&page=' . urlencode('My Vehicles'));
            exit;
        }

        case 'add_space': {
            $code = strtoupper(trim((string)($_POST['space_code'] ?? '')));
            $zone = trim((string)($_POST['zone'] ?? 'Main entrance'));
            $type = trim((string)($_POST['slot_type'] ?? 'Standard'));
            if ($code !== '') {
                $_SESSION['pw_spaces'][] = [
                    'id' => time(),
                    'code' => $code,
                    'zone' => $zone,
                    'type' => $type,
                    'status' => 'Available',
                    'plate' => null,
                    'timeIn' => null,
                ];
                $_SESSION['pw_lots'][0]['total']++;
                $_SESSION['pw_lots'][0]['available']++;
                logActivity($user['name'] ?? 'Owner', 'Added parking space', 'Northgate · ' . $code);
                flash('Parking space ' . $code . ' added.');
            }
            header('Location: index.php?view=workspace&page=' . urlencode('Parking Spaces'));
            exit;
        }

        case 'toggle_space': {
            $spId = (int)($_POST['space_id'] ?? 0);
            foreach ($_SESSION['pw_spaces'] as &$sp) {
                if ((int)$sp['id'] === $spId && $sp['status'] !== 'Occupied') {
                    $sp['status'] = $sp['status'] === 'Available' ? 'Unavailable' : 'Available';
                    break;
                }
            }
            unset($sp);
            flash('Parking space status updated.');
            header('Location: index.php?view=workspace&page=' . urlencode('Parking Spaces'));
            exit;
        }

        case 'add_guard': {
            $gName = trim((string)($_POST['guard_name'] ?? ''));
            $ent = trim((string)($_POST['entrance'] ?? 'Main entrance'));
            $shift = trim((string)($_POST['shift'] ?? '8 AM - 4 PM'));
            if ($gName !== '') {
                $_SESSION['pw_guards'][] = [
                    'id' => time(),
                    'name' => $gName,
                    'biz' => 'Northgate Parking Hub',
                    'ent' => $ent,
                    'shift' => $shift,
                    'phone' => '+63 917 888 9900',
                ];
                logActivity($user['name'] ?? 'Owner', 'Added guard', $gName);
                flash('Guard account added.');
            }
            header('Location: index.php?view=workspace&page=Guards');
            exit;
        }

        case 'submit_review': {
            $biz = trim((string)($_POST['business'] ?? ''));
            $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
            $text = trim((string)($_POST['review_text'] ?? ''));
            if ($biz !== '' && $text !== '') {
                $lotId = 1;
                foreach ($_SESSION['pw_lots'] as $l) {
                    if ($l['name'] === $biz) {
                        $lotId = (int)$l['id'];
                        break;
                    }
                }
                $_SESSION['pw_reviews'][] = [
                    'id' => time(),
                    'lotId' => $lotId,
                    'business' => $biz,
                    'rating' => $rating,
                    'text' => $text,
                    'date' => date('m/d/Y'),
                    'by' => $user['name'] ?? 'Customer',
                ];
                flash('Review submitted.');
            }
            header('Location: index.php?view=workspace&page=' . urlencode('My Reviews'));
            exit;
        }

        case 'submit_owner_app': {
            $name = trim((string)($_POST['full_name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $biz = trim((string)($_POST['business_name'] ?? ''));
            $pass = (string)($_POST['password'] ?? '');

            if ($name === '' || $email === '' || $phone === '' || strlen($pass) < 6) {
                flash('Please complete all required fields (password min 6 characters).');
                header('Location: index.php?view=recruit&modal=owner_apply');
                exit;
            }

            $docLabels = ['Land Title', 'Business Permit', 'Valid ID'];
            $docs = [];
            $uploadDir = __DIR__ . '/../../public/uploads/';
            for ($i = 0; $i < 3; $i++) {
                $key = 'doc_' . $i;
                $fileInfo = null;
                if (!empty($_FILES[$key]['name']) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                    $orig = basename((string)$_FILES[$key]['name']);
                    $size = (int)$_FILES[$key]['size'];
                    $safeName = time() . '_' . $i . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
                    move_uploaded_file($_FILES[$key]['tmp_name'], $uploadDir . $safeName);
                    $fileInfo = [
                        'n' => $orig,
                        'path' => 'uploads/' . $safeName,
                        'size' => $size,
                    ];
                }
                $docs[] = [
                    'type' => $docLabels[$i],
                    'name' => $name,
                    'st' => 'pending',
                    'why' => '',
                    'file' => $fileInfo,
                ];
            }

            $_SESSION['pw_apps'][] = [
                'id' => time(),
                'who' => $name,
                'email' => $email,
                'phone' => $phone,
                'acct' => $_SESSION['pw_acct'] ?? 'customer',
                'biz' => $biz ?: ($name . "'s Parking Lot"),
                'date' => date('M j, Y'),
                'status' => 'Pending',
                'reason' => '',
                'titleMatch' => false,
                'docs' => $docs,
            ];
            logActivity($name, 'Submitted owner application', '3 documents');
            flash('Application submitted! Super Admin will verify your documents.');
            header('Location: index.php?view=recruit');
            exit;
        }

        case 'doc_decision': {
            $appId = (int)($_POST['app_id'] ?? 0);
            $docIdx = (int)($_POST['doc_idx'] ?? 0);
            $decision = ($_POST['decision'] ?? '') === 'verify' ? 'verified' : 'rejected';
            $reason = trim((string)($_POST['doc_reason'] ?? ''));
            $titleMatch = !empty($_POST['title_match']);

            if ($decision === 'rejected' && $reason === '') {
                flash('Add a reason to reject this document.');
                header('Location: index.php?view=workspace&page=' . urlencode('Owner Applications') . '&app=' . $appId . '&doc=' . $docIdx);
                exit;
            }

            foreach ($_SESSION['pw_apps'] as &$app) {
                if ((int)$app['id'] === $appId && isset($app['docs'][$docIdx])) {
                    if ($docIdx === 0) {
                        if ($decision === 'verified' && !$titleMatch && !empty($app['docs'][0]['file'])) {
                            flash('Confirm the name on the land title matches the applicant.');
                            header('Location: index.php?view=workspace&page=' . urlencode('Owner Applications') . '&app=' . $appId . '&doc=0');
                            exit;
                        }
                        if ($decision === 'verified' && empty($app['docs'][0]['file'])) {
                            $app['titleMatch'] = strcasecmp($app['docs'][0]['name'] ?? '', $app['who']) === 0 || $titleMatch;
                        } else {
                            $app['titleMatch'] = $decision === 'verified';
                        }
                    }
                    $app['docs'][$docIdx]['st'] = $decision;
                    $app['docs'][$docIdx]['why'] = $decision === 'rejected' ? $reason : '';
                    logActivity($user['name'] ?? 'Super Admin', ($decision === 'verified' ? 'Verified' : 'Rejected') . ' document', $app['who'] . ' · ' . $app['docs'][$docIdx]['type'], $reason);
                    break;
                }
            }
            unset($app);
            flash('Document status updated.');
            header('Location: index.php?view=workspace&page=' . urlencode('Owner Applications') . '&app=' . $appId);
            exit;
        }

        case 'app_decision': {
            $appId = (int)($_POST['app_id'] ?? 0);
            $decision = ($_POST['decision'] ?? '') === 'approve' ? 'Approved' : 'Rejected';
            $reason = trim((string)($_POST['decision_reason'] ?? ''));

            if ($decision === 'Rejected' && $reason === '') {
                flash('Please provide a reason for rejection.');
                header('Location: index.php?view=workspace&page=' . urlencode('Owner Applications') . '&app=' . $appId);
                exit;
            }

            foreach ($_SESSION['pw_apps'] as &$app) {
                if ((int)$app['id'] === $appId) {
                    $app['status'] = $decision;
                    $app['reason'] = $decision === 'Rejected' ? $reason : '';
                    if ($decision === 'Approved') {
                        $pk = $app['acct'] ?: $app['who'];
                        if (!in_array($pk, $_SESSION['pw_promoted'], true)) {
                            $_SESSION['pw_promoted'][] = $pk;
                        }
                        if ($user && ($user['name'] === $app['who'] || ($_SESSION['pw_acct'] ?? '') === $app['acct'])) {
                            if (!in_array('owner', $_SESSION['pw_user']['roles'], true)) {
                                $_SESSION['pw_user']['roles'][] = 'owner';
                            }
                        }
                        logActivity($user['name'] ?? 'Super Admin', 'Approved owner application', $app['biz'] ?: $app['who']);
                        flash('Approved. ' . $app['who'] . ' is now an owner and keeps their customer account.');
                    } else {
                        logActivity($user['name'] ?? 'Super Admin', 'Rejected owner application', $app['biz'] ?: $app['who'], $reason);
                        flash('Application rejected. Reason recorded.');
                    }
                    break;
                }
            }
            unset($app);
            header('Location: index.php?view=workspace&page=' . urlencode('Owner Applications'));
            exit;
        }

        case 'account_warn': {
            $who = trim((string)($_POST['who'] ?? ''));
            $act = trim((string)($_POST['warn_act'] ?? 'Issue warning'));
            $reason = trim((string)($_POST['warn_reason'] ?? ''));
            $lotId = (int)($_POST['lot_id'] ?? 0);
            $returnPage = trim((string)($_POST['return_page'] ?? 'Business Directory'));

            if ($reason === '') {
                flash('A reason is required.');
                header('Location: index.php?view=workspace&page=' . urlencode($returnPage));
                exit;
            }
            $mapped = $act === 'Issue warning' ? 'Warned' : ($act === 'Temporarily suspend' ? 'Suspended' : 'Revoked');
            $_SESSION['pw_status'][$who] = $mapped;
            if ($lotId > 0 && $act !== 'Issue warning') {
                foreach ($_SESSION['pw_lots'] as &$l) {
                    if ((int)$l['id'] === $lotId) {
                        $l['closed'] = true;
                        break;
                    }
                }
                unset($l);
            }
            logActivity($user['name'] ?? 'Super Admin', $act, $who, $reason);
            flash('Administrative action recorded.');
            header('Location: index.php?view=workspace&page=' . urlencode($returnPage));
            exit;
        }

        case 'save_profile': {
            $name = trim((string)($_POST['full_name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            if ($user && $name !== '' && $email !== '') {
                $_SESSION['pw_user']['name'] = $name;
                $_SESSION['pw_user']['email'] = $email;
                $_SESSION['pw_user']['phone'] = $phone;
                flash('Profile changes saved.');
            }
            header('Location: index.php?view=workspace&page=' . urlencode('My Profile'));
            exit;
        }
    }
}
