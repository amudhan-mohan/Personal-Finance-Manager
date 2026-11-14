<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $auth->getUserId();
$financeManager = new FinanceManager();

$accounts_res = $financeManager->getAccounts($user_id);
$accounts = [];
while ($acc = $accounts_res->fetch_assoc()) {
    $accounts[] = $acc;
}

$page_title = "Bank Statement CSV Importer";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="../assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?php echo asset_version(); ?>">
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col font-sans antialiased">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-6 border-b border-slate-200 gap-4">
            <div>
                <div class="flex items-center space-x-2 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
                    <span>Data Integration</span>
                    <span>/</span>
                    <span class="text-slate-800">CSV Ingestion</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Bank Statement CSV Importer</h1>
                <p class="text-sm text-slate-600 mt-1">Upload banking statements, auto-detect debit/credit columns, detect duplicates, and batch ingest transactions.</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="transactions.php" class="inline-flex items-center px-3.5 py-2 border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 rounded-lg text-xs font-semibold transition">
                    View Transaction Ledger
                </a>
            </div>
        </div>

        <!-- Step 1: Upload & Account Selection -->
        <div id="stepUpload" class="mt-8 max-w-2xl mx-auto bg-white rounded-xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-1">Step 1: Select Account & Upload CSV File</h2>
            <p class="text-xs text-slate-500 mb-6">Compatible with standard statements from HDFC, SBI, ICICI, Axis, Kotak, and custom CSV spreadsheets.</p>

            <form id="uploadForm" onsubmit="handlePreview(event)" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->getCSRFToken(); ?>">
                <input type="hidden" name="action" value="preview_csv">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Destination Portfolio / Account</label>
                    <select id="accountId" name="account_id" required class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                        <option value="">Select target account</option>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?php echo $acc['id']; ?>">
                                <?php echo htmlspecialchars($acc['name']); ?> (<?php echo htmlspecialchars($acc['type']); ?> - ₹<?php echo number_format($acc['current_balance'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Statement File (.CSV)</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl hover:border-slate-400 transition bg-slate-50/50">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-10 w-10 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-xs text-slate-600 justify-center">
                                <label for="csvFile" class="relative cursor-pointer bg-transparent rounded-md font-bold text-slate-900 hover:underline">
                                    <span>Browse CSV file</span>
                                    <input id="csvFile" name="csv_file" type="file" accept=".csv" required class="sr-only" onchange="updateFileName(this)">
                                </label>
                                <p class="pl-1 text-slate-500">or drag and drop here</p>
                            </div>
                            <p id="fileNameDisplay" class="text-xs text-slate-500">Max size 5MB &bull; Comma-separated values</p>
                        </div>
                    </div>
                </div>

                <div id="uploadAlert" class="hidden p-3 rounded-lg text-xs font-semibold"></div>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" onclick="downloadSampleCSV()" class="text-xs text-slate-600 hover:text-slate-900 font-semibold underline">
                        Download Template CSV
                    </button>
                    <button type="submit" id="previewBtn" class="px-5 py-2.5 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition shadow-sm">
                        Parse & Preview
                    </button>
                </div>
            </form>
        </div>

        <!-- Step 2: Interactive Review & Ingestion Table -->
        <div id="stepReview" class="mt-8 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden hidden">
            <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Step 2: Review Transactions & Resolve Duplicates</h2>
                    <p class="text-xs text-slate-500 mt-0.5" id="reviewSubtitle"></p>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="resetToUpload()" class="px-3 py-1.5 border border-slate-300 text-slate-700 bg-white rounded-lg text-xs font-semibold hover:bg-slate-50">
                        Choose Another File
                    </button>
                    <button type="button" onclick="commitImport()" id="commitBtn" class="px-4 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                        Import Selected (<span id="selectedCountDisplay">0</span>)
                    </button>
                </div>
            </div>

            <!-- Bulk actions pill bar -->
            <div class="px-5 py-2.5 bg-white border-b border-slate-200 flex flex-wrap items-center justify-between gap-2 text-xs">
                <div class="flex items-center space-x-3">
                    <label class="flex items-center space-x-1.5 font-semibold text-slate-700 cursor-pointer">
                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this.checked)" checked class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                        <span>Select All</span>
                    </label>
                    <button type="button" onclick="deselectDuplicates()" class="text-slate-600 hover:text-slate-900 font-medium underline">
                        Deselect Potential Duplicates
                    </button>
                </div>
                <div class="text-slate-500 font-medium" id="duplicateSummaryDisplay"></div>
            </div>

            <!-- Preview Table with Fixed Height & Inner Scrolling -->
            <div class="overflow-x-auto max-h-[480px] overflow-y-auto border border-slate-200 rounded-xl inscrolling-table-container">
                <table class="w-full text-left border-collapse text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-200">
                        <tr class="bg-slate-50 text-slate-600 text-xs uppercase font-semibold">
                            <th class="py-3 px-4 w-10 bg-slate-50">Include</th>
                            <th class="py-3 px-4 bg-slate-50">Date</th>
                            <th class="py-3 px-4 bg-slate-50">Description</th>
                            <th class="py-3 px-4 bg-slate-50">Category</th>
                            <th class="py-3 px-4 bg-slate-50">Type</th>
                            <th class="py-3 px-4 bg-slate-50">Amount</th>
                            <th class="py-3 px-4 bg-slate-50">Audit Status</th>
                        </tr>
                    </thead>
                    <tbody id="previewTableBody" class="divide-y divide-slate-200">
                    </tbody>
                </table>
            </div>

            <div id="commitAlert" class="hidden m-5 p-4 rounded-lg text-xs font-semibold"></div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        let parsedRecords = [];
        let selectedAccountId = null;

        function updateFileName(input) {
            if (input.files && input.files[0]) {
                document.getElementById('fileNameDisplay').textContent = 'Selected: ' + input.files[0].name;
            }
        }

        function downloadSampleCSV() {
            const csvContent = "data:text/csv;charset=utf-8,Date,Narration,Debit,Credit,Category\n"
                + "2026-11-01,Monthly Salary Credit,,75000.00,Salary & Income\n"
                + "2026-11-02,Swiggy Bangalore,450.00,,Food & Dining\n"
                + "2026-11-03,Bescom Electricity Bill,1850.00,,Utilities & Bills\n"
                + "2026-11-05,HP Petrol Bunk,2000.00,,Transportation\n"
                + "2026-11-08,Amazon India Electronics,3499.00,,Shopping\n";
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "statement_template.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        async function handlePreview(e) {
            e.preventDefault();
            const btn = document.getElementById('previewBtn');
            const alertBox = document.getElementById('uploadAlert');
            alertBox.classList.add('hidden');
            btn.disabled = true;
            btn.textContent = 'Parsing...';

            selectedAccountId = document.getElementById('accountId').value;
            const formData = new FormData(document.getElementById('uploadForm'));

            try {
                const res = await fetch('../ajax/import.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    parsedRecords = data.items;
                    renderPreviewTable();
                    document.getElementById('stepUpload').classList.add('hidden');
                    document.getElementById('stepReview').classList.remove('hidden');
                } else {
                    alertBox.className = 'p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 block';
                    alertBox.textContent = data.message || 'Failed to parse CSV file.';
                }
            } catch (err) {
                alertBox.className = 'p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 block';
                alertBox.textContent = 'Network or parsing error occurred.';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Parse & Preview';
            }
        }

        function renderPreviewTable() {
            const tbody = document.getElementById('previewTableBody');
            tbody.innerHTML = '';
            let dupCount = 0;

            parsedRecords.forEach((item, index) => {
                if (item.is_duplicate) dupCount++;
                const isChecked = !item.is_duplicate; // uncheck duplicates by default

                const tr = document.createElement('tr');
                tr.className = item.is_duplicate ? 'bg-amber-50/50 hover:bg-amber-50 transition' : 'hover:bg-slate-50 transition';
                tr.innerHTML = `
                    <td class="py-3 px-4">
                        <input type="checkbox" data-index="${index}" ${isChecked ? 'checked' : ''} onchange="updateSelectedCount()" class="row-checkbox rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    </td>
                    <td class="py-3 px-4 text-xs font-semibold text-slate-800">${item.date}</td>
                    <td class="py-3 px-4 text-xs font-bold text-slate-900 max-w-xs truncate" title="${escapeHtml(item.description)}">${escapeHtml(item.description)}</td>
                    <td class="py-3 px-4 text-xs text-slate-600">${escapeHtml(item.category)}</td>
                    <td class="py-3 px-4 text-xs">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase ${item.type === 'income' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'}">
                            ${item.type}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-xs font-bold text-slate-900">₹${parseFloat(item.amount).toFixed(2)}</td>
                    <td class="py-3 px-4 text-xs">
                        ${item.is_duplicate 
                            ? '<span class="inline-flex items-center text-[10px] font-bold text-amber-700 bg-amber-100 border border-amber-300 px-1.5 py-0.5 rounded">Possible Duplicate</span>' 
                            : '<span class="text-emerald-700 font-semibold text-[11px]">&bull; Verified New</span>'}
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('reviewSubtitle').textContent = `${parsedRecords.length} records parsed from statement.`;
            document.getElementById('duplicateSummaryDisplay').textContent = `${dupCount} possible duplicate(s) flagged.`;
            updateSelectedCount();
        }

        function escapeHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            let count = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) count++;
            });
            document.getElementById('selectedCountDisplay').textContent = count;
        }

        function toggleSelectAll(val) {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => cb.checked = val);
            updateSelectedCount();
        }

        function deselectDuplicates() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => {
                const idx = parseInt(cb.dataset.index);
                if (parsedRecords[idx] && parsedRecords[idx].is_duplicate) {
                    cb.checked = false;
                }
            });
            updateSelectedCount();
        }

        function resetToUpload() {
            document.getElementById('stepReview').classList.add('hidden');
            document.getElementById('stepUpload').classList.remove('hidden');
        }

        async function commitImport() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            if (checkboxes.length === 0) {
                if (window.showToast) window.showToast('Selection Required', 'Please select at least one transaction to import.', 'error');
                else alert('Please select at least one transaction to import.');
                return;
            }

            const selectedItems = [];
            checkboxes.forEach(cb => {
                const idx = parseInt(cb.dataset.index);
                if (parsedRecords[idx]) {
                    selectedItems.push(parsedRecords[idx]);
                }
            });

            const btn = document.getElementById('commitBtn');
            const alertBox = document.getElementById('commitAlert');
            alertBox.classList.add('hidden');
            btn.disabled = true;
            btn.textContent = 'Ingesting Records...';

            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $auth->getCSRFToken(); ?>');
            formData.append('action', 'commit_import');
            formData.append('account_id', selectedAccountId);
            formData.append('records', JSON.stringify(selectedItems));

            try {
                const res = await fetch('../ajax/import.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    alertBox.className = 'm-5 p-4 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 block flex items-center space-x-2';
                    alertBox.innerHTML = '<svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Successfully Ingested! Redirecting to ledger...</span>';
                    btn.textContent = 'Successfully Imported!';
                    btn.className = 'px-4 py-2 bg-emerald-600 text-white rounded-lg text-xs font-semibold';
                    sessionStorage.setItem('flash_toast', JSON.stringify({ 
                        title: 'Successfully Imported', 
                        message: data.message || 'Transactions imported successfully into ledger.', 
                        type: 'success' 
                    }));
                    setTimeout(() => {
                        window.location.href = 'transactions.php?account_id=' + selectedAccountId;
                    }, 800);
                } else {
                    alertBox.className = 'm-5 p-4 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 block';
                    alertBox.textContent = data.message || 'Failed to complete ledger ingestion.';
                    btn.disabled = false;
                    btn.textContent = `Import Selected (${selectedItems.length})`;
                }
            } catch (err) {
                alertBox.className = 'm-5 p-4 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 block';
                alertBox.textContent = 'Network or server error during ingestion.';
                btn.disabled = false;
                btn.textContent = `Import Selected (${selectedItems.length})`;
            }
        }
    </script>
</body>
</html>
