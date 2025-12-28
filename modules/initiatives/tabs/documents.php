<?php
// modules/initiatives/tabs/documents.php

// 1. التحقق من الصلاحيات
$canManageDocs = ($isOwner || $isSuper || Auth::can('manage_initiative_documents')) && !$isLocked;

// 2. معالجة الرفع (Upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document']) && $canManageDocs) {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    
    // تحديد نوع الارتباط
    $linkType = $_POST['link_type']; // initiative, milestone, task, risk
    $linkId = $_POST['link_id'] ?: 0;
    
    // إذا كان الارتباط عام بالمبادرة، نستخدم parent_id = initiative_id
    // ولكن في الجدول، parent_id يعود للكيان الفرعي (milestone id, task id)
    // لذا سنستخدم parent_type لتحديد النوع، و parent_id لتحديد الكيان
    
    $parentType = 'initiative'; // Default
    $parentId = $id; // Default to initiative ID
    
    if ($linkType == 'milestone') {
        $parentType = 'milestone';
        $parentId = $linkId;
    } elseif ($linkType == 'task') { // Note: Your DB enum might need 'task' added if not present, check schema
        $parentType = 'task'; // Add 'task' to enum in DB if missing, or use 'project' as fallback? 
        // Based on your schema: enum('initiative','project','milestone','task','risk','pillar') -> 'task' exists!
        $parentId = $linkId;
    } elseif ($linkType == 'risk') {
        $parentType = 'risk';
        $parentId = $linkId;
    }
    
    // معالجة الملف
    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] == 0) {
        $uploadDir = '../../assets/uploads/documents/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileExt = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $_FILES['doc_file']['name']);
        $targetFile = $uploadDir . $fileName;
        
        // التحقق من الامتدادات المسموحة
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'png', 'zip'];
        
        if (in_array($fileExt, $allowed)) {
            if (move_uploaded_file($_FILES['doc_file']['tmp_name'], $targetFile)) {
                $stmt = $db->prepare("
                    INSERT INTO documents (
                        parent_type, parent_id, title, description, 
                        file_name, file_path, file_size, file_type, 
                        uploaded_by, uploaded_at, created_at
                    ) VALUES (
                        ?, ?, ?, ?, 
                        ?, ?, ?, ?, 
                        ?, NOW(), NOW()
                    )
                ");
                $stmt->execute([
                    $parentType, $parentId, $title, $desc,
                    $_FILES['doc_file']['name'], $targetFile, $_FILES['doc_file']['size'], $fileExt,
                    $_SESSION['user_id']
                ]);
                
                echo "<script>window.location.href='view.php?id=$id&tab=documents&msg=doc_uploaded';</script>";
            } else {
                echo "<script>Swal.fire('Upload Error', 'Failed to move uploaded file.', 'error');</script>";
            }
        } else {
            echo "<script>Swal.fire('Type Error', 'File type not allowed.', 'error');</script>";
        }
    } else {
        echo "<script>Swal.fire('File Error', 'Please select a valid file.', 'error');</script>";
    }
}

// 3. معالجة الحذف
if (isset($_GET['delete_doc']) && $canManageDocs) {
    $docId = $_GET['delete_doc'];
    $db->prepare("UPDATE documents SET is_deleted=1 WHERE id=?")->execute([$docId]);
    echo "<script>window.location.href='view.php?id=$id&tab=documents&msg=doc_deleted';</script>";
}

// 4. جلب المستندات (بذكاء)
// نحتاج لجلب المستندات المرتبطة بالمبادرة مباشرة، أو بأي من عناصرها الفرعية
// الطريقة الأسهل: جلب IDs العناصر الفرعية
$milestoneIds = $db->query("SELECT id FROM initiative_milestones WHERE initiative_id=$id")->fetchAll(PDO::FETCH_COLUMN);
$taskIds = $db->query("SELECT id FROM initiative_tasks WHERE initiative_id=$id")->fetchAll(PDO::FETCH_COLUMN);
$riskIds = $db->query("SELECT id FROM risk_assessments WHERE parent_type='initiative' AND parent_id=$id")->fetchAll(PDO::FETCH_COLUMN);

$msIdsStr = !empty($milestoneIds) ? implode(',', $milestoneIds) : '0';
$tkIdsStr = !empty($taskIds) ? implode(',', $taskIds) : '0';
$rsIdsStr = !empty($riskIds) ? implode(',', $riskIds) : '0';

$docsQuery = "
    SELECT d.*, u.full_name_en as uploader_name,
    CASE 
        WHEN d.parent_type = 'initiative' THEN 'Initiative (General)'
        WHEN d.parent_type = 'milestone' THEN (SELECT CONCAT('Milestone: ', name) FROM initiative_milestones WHERE id = d.parent_id)
        WHEN d.parent_type = 'task' THEN (SELECT CONCAT('Task: ', title) FROM initiative_tasks WHERE id = d.parent_id)
        WHEN d.parent_type = 'risk' THEN (SELECT CONCAT('Risk: ', title) FROM risk_assessments WHERE id = d.parent_id)
        ELSE 'Unknown'
    END as context_name
    FROM documents d
    LEFT JOIN users u ON u.id = d.uploaded_by
    WHERE d.is_deleted = 0 AND (
        (d.parent_type = 'initiative' AND d.parent_id = $id) OR
        (d.parent_type = 'milestone' AND d.parent_id IN ($msIdsStr)) OR
        (d.parent_type = 'task' AND d.parent_id IN ($tkIdsStr)) OR
        (d.parent_type = 'risk' AND d.parent_id IN ($rsIdsStr))
    )
    ORDER BY d.created_at DESC
";
$docList = $db->query($docsQuery)->fetchAll(PDO::FETCH_ASSOC);

// قوائم للمودال (للربط)
if ($canManageDocs) {
    $milestones = $db->query("SELECT id, name FROM initiative_milestones WHERE initiative_id=$id AND is_deleted=0")->fetchAll();
    $tasks = $db->query("SELECT id, title FROM initiative_tasks WHERE initiative_id=$id AND is_deleted=0")->fetchAll();
    $risks = $db->query("SELECT id, title FROM risk_assessments WHERE parent_type='initiative' AND parent_id=$id AND status_id!=4")->fetchAll();
}
?>

<style>
    /* Doc Cards */
    .doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 25px; }
    
    .doc-card { 
        background: #fff; border: 1px solid #f0f2f5; border-radius: 16px; padding: 20px; 
        transition: all 0.3s ease; position: relative; overflow: hidden;
        display: flex; flex-direction: column; height: 100%;
    }
    .doc-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-color: #ff8c00; }
    
    .doc-icon { 
        width: 50px; height: 50px; border-radius: 12px; background: #f8f9fa; 
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #555;
        margin-bottom: 15px;
    }
    .type-pdf { color: #e74c3c; background: #ffebee; }
    .type-xls { color: #27ae60; background: #e8f5e9; }
    .type-doc { color: #3498db; background: #e3f2fd; }
    .type-img { color: #9b59b6; background: #f3e5f5; }

    .doc-title { font-weight: 700; color: #2d3436; font-size: 1rem; margin-bottom: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .doc-ctx { font-size: 0.75rem; color: #fff; background: #95a5a6; padding: 2px 8px; border-radius: 4px; display: inline-block; margin-bottom: 10px; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ctx-init { background: #34495e; }
    .ctx-ms { background: #e67e22; }
    .ctx-task { background: #3498db; }
    .ctx-risk { background: #e74c3c; }

    .doc-meta { font-size: 0.8rem; color: #95a5a6; margin-top: auto; padding-top: 15px; border-top: 1px dashed #eee; display: flex; justify-content: space-between; align-items: center; }
    
    .doc-actions { position: absolute; top: 15px; right: 15px; }
    .btn-icon-sm { color: #b2bec3; cursor: pointer; transition: 0.2s; }
    .btn-icon-sm:hover { color: #e74c3c; }
    .btn-icon-sm.dl:hover { color: #3498db; }

    /* Upload Zone */
    .upload-zone { 
        border: 2px dashed #ddd; border-radius: 16px; padding: 40px; text-align: center; 
        background: #fafafa; cursor: pointer; transition: 0.3s; margin-bottom: 25px;
    }
    .upload-zone:hover { border-color: #ff8c00; background: #fffaf0; }
    .uz-icon { font-size: 2.5rem; color: #ccc; margin-bottom: 10px; }
    .uz-text { font-weight: 600; color: #7f8c8d; }

    /* Modal (Reuse) */
</style>

<div class="tab-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
            <h3 style="margin:0; color:#2c3e50;">Documents & Files</h3>
            <p style="margin:5px 0 0; color:#95a5a6; font-size:0.9rem;">Central repository for all initiative-related files.</p>
        </div>
        <?php if($canManageDocs): ?>
            <button onclick="openDocModal()" class="btn-primary" style="padding:12px 25px; border-radius:30px; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-cloud-arrow-up"></i> Upload File
            </button>
        <?php endif; ?>
    </div>

    <?php if(empty($docList)): ?>
        <div class="upload-zone" onclick="openDocModal()">
            <i class="fa-solid fa-folder-open uz-icon"></i>
            <div class="uz-text">No documents found. Click here to upload.</div>
        </div>
    <?php else: ?>
        <div class="doc-grid">
            <?php foreach($docList as $d): 
                $icon = 'fa-file'; $style = '';
                if(in_array($d['file_type'], ['pdf'])) { $icon = 'fa-file-pdf'; $style = 'type-pdf'; }
                elseif(in_array($d['file_type'], ['xls','xlsx'])) { $icon = 'fa-file-excel'; $style = 'type-xls'; }
                elseif(in_array($d['file_type'], ['doc','docx'])) { $icon = 'fa-file-word'; $style = 'type-doc'; }
                elseif(in_array($d['file_type'], ['jpg','png'])) { $icon = 'fa-file-image'; $style = 'type-img'; }

                $ctxClass = 'ctx-init';
                if($d['parent_type'] == 'milestone') $ctxClass = 'ctx-ms';
                if($d['parent_type'] == 'task') $ctxClass = 'ctx-task';
                if($d['parent_type'] == 'risk') $ctxClass = 'ctx-risk';
            ?>
            <div class="doc-card">
                <div class="doc-actions">
                    <a href="<?= $d['file_path'] ?>" target="_blank" class="btn-icon-sm dl" title="Download" style="margin-right:8px;"><i class="fa-solid fa-download"></i></a>
                    <?php if($canManageDocs): ?>
                        <a href="view.php?id=<?= $id ?>&tab=documents&delete_doc=<?= $d['id'] ?>" class="btn-icon-sm" onclick="return confirm('Delete file?')" title="Delete"><i class="fa-solid fa-trash"></i></a>
                    <?php endif; ?>
                </div>

                <div class="doc-icon <?= $style ?>"><i class="fa-regular <?= $icon ?>"></i></div>
                <div class="doc-title" title="<?= htmlspecialchars($d['title']) ?>"><?= htmlspecialchars($d['title']) ?></div>
                <div class="doc-ctx <?= $ctxClass ?>" title="<?= htmlspecialchars($d['context_name']) ?>">
                    <?= htmlspecialchars($d['context_name']) ?>
                </div>
                
                <div class="doc-meta">
                    <span><?= round($d['file_size']/1024) ?> KB</span>
                    <span><?= date('d M Y', strtotime($d['created_at'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if($canManageDocs): ?>
<div id="addDocModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header" style="padding:25px; border-bottom:1px solid #eee; display:flex; justify-content:space-between;">
            <h3 style="margin:0; color:#2c3e50;">Upload Document</h3>
            <span onclick="closeDocModal()" style="font-size:1.5rem; cursor:pointer; color:#ccc;">&times;</span>
        </div>
        
        <form method="POST" enctype="multipart/form-data" style="padding:30px;">
            <input type="hidden" name="upload_document" value="1">
            
            <div class="form-row">
                <label class="form-lbl">Document Title <span style="color:red">*</span></label>
                <input type="text" name="title" class="form-input" required placeholder="e.g. Project Charter">
            </div>

            <div class="form-row">
                <label class="form-lbl">Related To</label>
                <div style="display:flex; gap:10px;">
                    <select name="link_type" id="link_type" class="form-input" style="width:35%;" onchange="toggleLinkSelect()">
                        <option value="initiative">Initiative (General)</option>
                        <option value="milestone">Milestone</option>
                        <option value="task">Task</option>
                        <option value="risk">Risk</option>
                    </select>
                    
                    <select name="link_id" id="link_id" class="form-input" style="width:65%;" disabled>
                        <option value="0">-- Select Related Item --</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <label class="form-lbl">File <span style="color:red">*</span></label>
                <input type="file" name="doc_file" class="form-input" required>
            </div>

            <div class="form-row">
                <label class="form-lbl">Description</label>
                <textarea name="description" class="form-input" style="height:80px;"></textarea>
            </div>

            <div class="modal-footer" style="padding-top:20px; border-top:1px solid #f0f0f0; display:flex; justify-content:flex-end;">
                <button type="button" class="btn-cancel" onclick="closeDocModal()" style="margin-right:10px;">Cancel</button>
                <button type="submit" class="btn-save">Upload</button>
            </div>
        </form>
    </div>
</div>

<script>
    // تخزين البيانات للـ dropdown
    const milestones = <?= json_encode($milestones) ?>;
    const tasks = <?= json_encode($tasks) ?>;
    const risks = <?= json_encode($risks) ?>;

    function openDocModal() { document.getElementById('addDocModal').style.display = 'flex'; }
    function closeDocModal() { document.getElementById('addDocModal').style.display = 'none'; }

    function toggleLinkSelect() {
        const type = document.getElementById('link_type').value;
        const select = document.getElementById('link_id');
        select.innerHTML = '<option value="0">-- Select Related Item --</option>';
        
        let data = [];
        if (type === 'milestone') data = milestones;
        else if (type === 'task') data = tasks;
        else if (type === 'risk') data = risks;

        if (type === 'initiative') {
            select.disabled = true;
        } else {
            select.disabled = false;
            data.forEach(item => {
                // Task/Risk uses title, Milestone uses name
                let label = item.name || item.title; 
                select.innerHTML += `<option value="${item.id}">${label}</option>`;
            });
        }
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('addDocModal')) closeDocModal();
    }
</script>
<?php endif; ?>