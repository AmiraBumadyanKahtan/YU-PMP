<?php
// modules/initiatives/tabs/approvals.php

// 1. جلب بيانات مسار الموافقة الحالي (Instance)
$stmt = $db->prepare("
    SELECT ai.*, u.full_name_en as creator_name 
    FROM approval_instances ai 
    JOIN users u ON u.id = ai.created_by
    WHERE ai.entity_type_id = 2 AND ai.entity_id = ? 
    ORDER BY ai.id DESC LIMIT 1
");
$stmt->execute([$id]);
$instance = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. إذا لم يتم الإرسال بعد
if (!$instance) {
    echo '
    <div class="empty-state">
        <i class="fa-solid fa-paper-plane" style="color:#ddd;"></i>
        <h3>Not Submitted Yet</h3>
        <p>This initiative is currently a draft. Submit it to start the approval workflow.</p>
    </div>';
    return; // خروج من الملف
}

// 3. جلب جميع المراحل (Stages) لهذا المسار لرسم الـ Stepper
// ملاحظة: نفترض أن workflow_id ثابت للمبادرات (مثلاً 7 أو 8 حسب الجدول)
// أو نجلبه من الـ workflow المرتبط بالمرحلة الحالية
$wfId = $db->query("SELECT workflow_id FROM approval_workflow_stages WHERE id = " . ($instance['current_stage_id'] ?: 0))->fetchColumn();
if(!$wfId) $wfId = 7; // Fallback (Initiative Flow)

$stages = $db->query("SELECT * FROM approval_workflow_stages WHERE workflow_id = $wfId ORDER BY stage_order ASC")->fetchAll(PDO::FETCH_ASSOC);

// 4. جلب سجل الأحداث (History Log) مع التعليقات
$logs = $db->prepare("
    SELECT aa.*, u.full_name_en, u.avatar, s.stage_name
    FROM approval_actions aa
    LEFT JOIN users u ON u.id = aa.reviewer_user_id
    LEFT JOIN approval_workflow_stages s ON s.id = aa.stage_id
    WHERE aa.approval_instance_id = ?
    ORDER BY aa.created_at DESC
");
$logs->execute([$instance['id']]);
$history = $logs->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
    /* --- Timeline & Stepper CSS --- */
    
    /* 1. Visual Stepper (Top) */
    .stepper-wrapper { display: flex; justify-content: space-between; margin-bottom: 40px; position: relative; }
    .stepper-wrapper::before { content: ''; position: absolute; top: 15px; left: 0; width: 100%; height: 3px; background: #eee; z-index: 0; }
    
    .step-item { position: relative; z-index: 1; text-align: center; width: 100%; }
    .step-circle { 
        width: 35px; height: 35px; border-radius: 50%; background: #fff; border: 3px solid #eee; 
        display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; 
        font-weight: bold; color: #999; transition: 0.3s;
    }
    .step-title { font-size: 0.85rem; color: #999; font-weight: 600; }
    
    /* Active/Completed States */
    .step-item.completed .step-circle { background: #2ecc71; border-color: #2ecc71; color: #fff; }
    .step-item.completed .step-title { color: #2ecc71; }
    
    .step-item.active .step-circle { background: #3498db; border-color: #3498db; color: #fff; box-shadow: 0 0 0 5px rgba(52,152,219,0.2); }
    .step-item.active .step-title { color: #3498db; font-weight: 800; }
    
    .step-item.rejected .step-circle { background: #e74c3c; border-color: #e74c3c; color: #fff; }
    .step-item.rejected .step-title { color: #e74c3c; }

    .step-item.returned .step-circle { background: #e67e22; border-color: #e67e22; color: #fff; }
    .step-item.returned .step-title { color: #e67e22; }

    /* 2. Action Box (If Returned) */
    .action-required-box { 
        background: #fff8e1; border: 1px solid #ffe0b2; padding: 20px; border-radius: 12px; 
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;
        animation: slideDown 0.5s;
    }
    .arb-content h4 { margin: 0 0 5px 0; color: #e65100; display: flex; align-items: center; gap: 8px; }
    
    /* 3. Detailed Timeline (Bottom) */
    .timeline { position: relative; padding-left: 30px; border-left: 2px solid #eee; margin-top: 20px; }
    .tl-item { position: relative; margin-bottom: 30px; }
    .tl-icon { 
        position: absolute; left: -39px; top: 0; width: 16px; height: 16px; 
        border-radius: 50%; background: #fff; border: 3px solid #ccc; 
    }
    .tl-content { background: #fff; padding: 15px 20px; border-radius: 12px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
    .tl-header { display: flex; justify-content: space-between; margin-bottom: 8px; }
    .tl-user { font-weight: 700; color: #2d3436; display: flex; align-items: center; gap: 8px; }
    .tl-avatar { width: 24px; height: 24px; border-radius: 50%; }
    .tl-date { font-size: 0.8rem; color: #b2bec3; }
    .tl-decision { 
        display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 0.75rem; 
        font-weight: 700; text-transform: uppercase; margin-bottom: 8px;
    }
    .tl-comment { background: #f9f9f9; padding: 10px; border-radius: 6px; color: #555; font-size: 0.9rem; border-left: 3px solid #ddd; }

    /* Colors for Decisions */
    .dec-approved { background: #e8f5e9; color: #27ae60; }
    .dec-rejected { background: #ffebee; color: #c0392b; }
    .dec-returned { background: #fff3e0; color: #e67e22; }
    
    @keyframes slideDown { from { transform: translateY(-10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

<div class="tab-card">

    <div class="stepper-wrapper">
        <?php 
        $passedCurrent = false;
        foreach($stages as $s): 
            $statusClass = '';
            $icon = $s['stage_order'];

            // Logic to color steps
            if ($instance['status'] == 'approved') {
                $statusClass = 'completed';
                $icon = '<i class="fa-solid fa-check"></i>';
            } elseif ($instance['status'] == 'rejected' && $instance['current_stage_id'] == $s['id']) {
                $statusClass = 'rejected';
                $icon = '<i class="fa-solid fa-xmark"></i>';
            } elseif ($instance['status'] == 'returned' && $instance['current_stage_id'] == $s['id']) {
                $statusClass = 'returned';
                $icon = '<i class="fa-solid fa-rotate-left"></i>';
            } elseif ($instance['current_stage_id'] == $s['id'] && $instance['status'] == 'in_progress') {
                $statusClass = 'active';
                $icon = '<i class="fa-solid fa-hourglass-half"></i>';
                $passedCurrent = true; // Stop marking next steps as active
            } elseif (!$passedCurrent && $instance['status'] != 'returned' && $instance['status'] != 'rejected') {
                 // Previous steps are completed
                 $statusClass = 'completed';
                 $icon = '<i class="fa-solid fa-check"></i>';
            }
        ?>
            <div class="step-item <?= $statusClass ?>">
                <div class="step-circle"><?= $icon ?></div>
                <div class="step-title"><?= htmlspecialchars($s['stage_name']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if($instance['status'] == 'returned' && $isOwner): ?>
        <div class="action-required-box">
            <div class="arb-content">
                <h4><i class="fa-solid fa-circle-exclamation"></i> Action Required: Returned for Revision</h4>
                <p style="margin:0; color:#7f8c8d; font-size:0.9rem;">
                    Please check the comments below, modify the initiative details, and then resubmit.
                </p>
            </div>
            <form method="POST">
                <input type="hidden" name="submit_approval" value="1">
                <button type="submit" class="btn-primary" style="background:#e67e22;">
                    <i class="fa-solid fa-paper-plane"></i> Resubmit Now
                </button>
            </form>
        </div>
    <?php endif; ?>

    <h3 style="margin-top:0; color:#2c3e50; border-bottom:1px solid #eee; padding-bottom:10px;">Approval History & Comments</h3>
    
    <div class="timeline">
        <?php if(empty($history)): ?>
            <p style="color:#aaa; font-style:italic;">No actions taken yet. Waiting for review...</p>
        <?php else: ?>
            <?php foreach($history as $h): 
                $av = $h['avatar'] ? '../../assets/uploads/avatars/'.$h['avatar'] : '../../assets/uploads/avatars/default-profile.png';
                
                $decClass = 'dec-approved';
                $iconColor = '#2ecc71';
                
                if($h['decision'] == 'rejected') { $decClass = 'dec-rejected'; $iconColor = '#e74c3c'; }
                if($h['decision'] == 'returned') { $decClass = 'dec-returned'; $iconColor = '#e67e22'; }
            ?>
            <div class="tl-item">
                <div class="tl-icon" style="border-color:<?= $iconColor ?>"></div>
                <div class="tl-content">
                    <div class="tl-header">
                        <div class="tl-user">
                            <img src="<?= $av ?>" class="tl-avatar">
                            <span><?= htmlspecialchars($h['full_name_en']) ?></span>
                            <span style="font-weight:400; color:#aaa; font-size:0.8rem;">(<?= htmlspecialchars($h['stage_name']) ?>)</span>
                        </div>
                        <div class="tl-date"><?= date('M d, Y h:i A', strtotime($h['created_at'])) ?></div>
                    </div>
                    
                    <span class="tl-decision <?= $decClass ?>"><?= ucfirst($h['decision']) ?></span>
                    
                    <?php if(!empty($h['comments'])): ?>
                        <div class="tl-comment">
                            <i class="fa-solid fa-quote-left" style="color:#ddd; margin-right:5px;"></i>
                            <?= nl2br(htmlspecialchars($h['comments'])) ?>
                        </div>
                    <?php else: ?>
                        <div style="font-size:0.85rem; color:#ccc; margin-top:5px;">No comments provided.</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="tl-item">
            <div class="tl-icon" style="border-color:#3498db; background:#3498db;"></div>
            <div class="tl-content" style="opacity:0.7;">
                <div class="tl-header">
                    <div class="tl-user">
                        <i class="fa-solid fa-user-circle" style="font-size:1.5rem; color:#aaa;"></i>
                        <span><?= htmlspecialchars($instance['creator_name']) ?></span>
                    </div>
                    <div class="tl-date"><?= date('M d, Y h:i A', strtotime($instance['created_at'])) ?></div>
                </div>
                <span style="background:#f0f2f5; padding:3px 8px; border-radius:4px; font-size:0.75rem; font-weight:bold; color:#777;">SUBMITTED</span>
                <div style="margin-top:5px; font-size:0.9rem; color:#777;">Initiative submitted for approval.</div>
            </div>
        </div>

    </div>

</div>