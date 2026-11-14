<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $auth->getUserId();
$financeManager = new FinanceManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !$auth->validateCSRFToken($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Action: Parse CSV for Preview & Column Mapping
    if ($action === 'preview_csv') {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Please select a valid CSV file to upload.']);
            exit;
        }

        $account_id = intval($_POST['account_id'] ?? 0);
        if ($account_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please select an account for statement import.']);
            exit;
        }

        // Verify account ownership
        $account = $financeManager->getAccount($account_id, $user_id);
        if (!$account) {
            echo json_encode(['success' => false, 'message' => 'Selected account not found or access denied.']);
            exit;
        }

        $tmp_file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($tmp_file, 'r');
        if (!$handle) {
            echo json_encode(['success' => false, 'message' => 'Failed to read uploaded CSV file.']);
            exit;
        }

        $rows = [];
        $header = null;
        $line_count = 0;

        while (($data = fgetcsv($handle, 2000, ",")) !== false) {
            $line_count++;
            // Clean UTF-8 BOM
            if ($line_count === 1 && !empty($data)) {
                $data[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $data[0]);
            }

            // Skip empty rows
            if (empty(array_filter($data, 'strlen'))) {
                continue;
            }

            if ($header === null) {
                $header = array_map('trim', $data);
                continue;
            }

            $row_assoc = [];
            foreach ($header as $i => $col_name) {
                $row_assoc[$col_name] = isset($data[$i]) ? trim($data[$i]) : '';
            }
            $rows[] = $row_assoc;

            if (count($rows) >= 500) {
                break; // Limit max 500 records per upload for performance
            }
        }
        fclose($handle);

        if (empty($header) || empty($rows)) {
            echo json_encode(['success' => false, 'message' => 'CSV file appears to be empty or has an invalid structure.']);
            exit;
        }

        // Auto-detect columns
        $date_col = null;
        $desc_col = null;
        $amount_col = null;
        $debit_col = null;
        $credit_col = null;
        $type_col = null;
        $category_col = null;

        foreach ($header as $col) {
            $lc = strtolower($col);
            if (!$date_col && (strpos($lc, 'date') !== false || strpos($lc, 'txn date') !== false || strpos($lc, 'time') !== false)) {
                $date_col = $col;
            } elseif (!$desc_col && (strpos($lc, 'desc') !== false || strpos($lc, 'narration') !== false || strpos($lc, 'particular') !== false || strpos($lc, 'remark') !== false)) {
                $desc_col = $col;
            } elseif (!$debit_col && (strpos($lc, 'debit') !== false || strpos($lc, 'withdrawal') !== false || strpos($lc, 'dr') !== false)) {
                $debit_col = $col;
            } elseif (!$credit_col && (strpos($lc, 'credit') !== false || strpos($lc, 'deposit') !== false || strpos($lc, 'cr') !== false)) {
                $credit_col = $col;
            } elseif (!$amount_col && (strpos($lc, 'amount') !== false || strpos($lc, 'txn amt') !== false)) {
                $amount_col = $col;
            } elseif (!$type_col && (strpos($lc, 'type') !== false)) {
                $type_col = $col;
            } elseif (!$category_col && (strpos($lc, 'cat') !== false)) {
                $category_col = $col;
            }
        }

        // Parse preview rows with duplicate check
        $parsed_preview = [];
        $check_stmt = $financeManager->getDb()->conn->prepare("
            SELECT id FROM transactions 
            WHERE user_id = ? AND account_id = ? AND transaction_date = ? AND amount = ? AND deleted_at IS NULL 
            LIMIT 1
        ");

        foreach ($rows as $idx => $r) {
            // Determine date
            $raw_date = $date_col ? ($r[$date_col] ?? '') : '';
            $parsed_date = date('Y-m-d');
            if (!empty($raw_date)) {
                $ts = strtotime(str_replace('/', '-', $raw_date));
                if ($ts !== false) {
                    $parsed_date = date('Y-m-d', $ts);
                }
            }

            // Determine description
            $description = $desc_col ? ($r[$desc_col] ?? '') : 'Bank Transaction';
            if (empty($description)) $description = 'Imported Transaction';

            // Determine type & amount
            $type = 'expense';
            $amount = 0.00;

            if ($debit_col && !empty($r[$debit_col]) && floatval(preg_replace('/[^\d.]/', '', $r[$debit_col])) > 0) {
                $type = 'expense';
                $amount = floatval(preg_replace('/[^\d.]/', '', $r[$debit_col]));
            } elseif ($credit_col && !empty($r[$credit_col]) && floatval(preg_replace('/[^\d.]/', '', $r[$credit_col])) > 0) {
                $type = 'income';
                $amount = floatval(preg_replace('/[^\d.]/', '', $r[$credit_col]));
            } elseif ($amount_col && isset($r[$amount_col])) {
                $val = floatval(preg_replace('/[^\d.-]/', '', $r[$amount_col]));
                if ($val < 0) {
                    $type = 'expense';
                    $amount = abs($val);
                } else {
                    $type = ($type_col && strtolower($r[$type_col] ?? '') === 'debit') ? 'expense' : 'income';
                    $amount = $val;
                }
            }

            if ($amount <= 0) {
                continue; // Skip zero amount rows
            }

            // Categorization inference
            $desc_lower = strtolower($description);
            $category = 'Other';
            if (preg_match('/(swiggy|zomato|starbucks|mcdonald|food|hotel|restaurant|cafe)/', $desc_lower)) {
                $category = 'Food & Dining';
            } elseif (preg_match('/(uber|ola|rapido|metro|petrol|fuel|indian oil|hpcl|bpcl)/', $desc_lower)) {
                $category = 'Transportation';
            } elseif (preg_match('/(electricity|bescom|tata power|airtel|jio|act fibernet|broadband|water|gas)/', $desc_lower)) {
                $category = 'Utilities & Bills';
            } elseif (preg_match('/(netflix|spotify|prime|hotstar|youtube|cinema|movie|bookmyshow)/', $desc_lower)) {
                $category = 'Entertainment';
            } elseif (preg_match('/(amazon|flipkart|myntra|zara|shopping|supermarket|dmart|blinkit|zepto|instamart)/', $desc_lower)) {
                $category = 'Shopping';
            } elseif (preg_match('/(salary|payroll|bonus|dividend|freelance|stipend)/', $desc_lower)) {
                $category = 'Salary & Income';
            } elseif (preg_match('/(hospital|pharmacy|apollo|medplus|doctor|health|clinic)/', $desc_lower)) {
                $category = 'Healthcare & Medical';
            } elseif (preg_match('/(rent|housing|maintenance|landlord)/', $desc_lower)) {
                $category = 'Housing & Rent';
            }

            // Duplicate detection check
            $is_duplicate = false;
            $check_stmt->bind_param("iisd", $user_id, $account_id, $parsed_date, $amount);
            $check_stmt->execute();
            if ($check_stmt->get_result()->fetch_assoc()) {
                $is_duplicate = true;
            }

            $parsed_preview[] = [
                'index' => $idx,
                'date' => $parsed_date,
                'description' => $description,
                'type' => $type,
                'amount' => $amount,
                'category' => $category,
                'is_duplicate' => $is_duplicate
            ];
        }

        echo json_encode([
            'success' => true,
            'header' => $header,
            'detected' => [
                'date_col' => $date_col,
                'desc_col' => $desc_col,
                'amount_col' => $amount_col,
                'debit_col' => $debit_col,
                'credit_col' => $credit_col
            ],
            'total_parsed' => count($parsed_preview),
            'items' => $parsed_preview
        ]);
        exit;
    }

    // Action: Commit and Batch Ingest Transactions
    if ($action === 'commit_import') {
        $account_id = intval($_POST['account_id'] ?? 0);
        $records_json = $_POST['records'] ?? '[]';
        $records = json_decode($records_json, true);

        if ($account_id <= 0 || empty($records) || !is_array($records)) {
            echo json_encode(['success' => false, 'message' => 'No valid transactions selected for import.']);
            exit;
        }

        $account = $financeManager->getAccount($account_id, $user_id);
        if (!$account) {
            echo json_encode(['success' => false, 'message' => 'Target account not found.']);
            exit;
        }

        $db = $financeManager->getDb()->conn;
        $db->begin_transaction();

        try {
            $insert_stmt = $db->prepare("
                INSERT INTO transactions (user_id, account_id, type, amount, description, category, transaction_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $update_bal_stmt = $db->prepare("
                UPDATE accounts 
                SET current_balance = current_balance + ? 
                WHERE id = ? AND user_id = ?
            ");

            $imported_count = 0;
            $total_net_delta = 0.00;

            foreach ($records as $item) {
                $type = ($item['type'] === 'income') ? 'income' : 'expense';
                $amount = floatval($item['amount'] ?? 0);
                $desc = trim($item['description'] ?? 'Imported Statement Item');
                $cat = trim($item['category'] ?? 'Other');
                $tdate = trim($item['date'] ?? date('Y-m-d'));

                if ($amount <= 0) {
                    continue;
                }

                $insert_stmt->bind_param("iisdsss", $user_id, $account_id, $type, $amount, $desc, $cat, $tdate);
                $insert_stmt->execute();

                $delta = ($type === 'income') ? $amount : -$amount;
                $total_net_delta += $delta;
                $imported_count++;
            }

            // Apply net delta to account
            $update_bal_stmt->bind_param("dii", $total_net_delta, $account_id, $user_id);
            $update_bal_stmt->execute();

            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => "Successfully imported {$imported_count} transactions into {$account['name']}.",
                'imported_count' => $imported_count,
                'net_delta' => $total_net_delta
            ]);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}
