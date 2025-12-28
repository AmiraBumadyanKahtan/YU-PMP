<?php
// modules/initiatives/tabs/projects.php

// جلب المشاريع المرتبطة بهذه المبادرة
$linkedProjects = $db->query("
    SELECT p.*, s.name as status_name, s.color as status_color, u.full_name_en as manager_name
    FROM operational_projects p
    LEFT JOIN operational_project_statuses s ON s.id = p.status_id
    LEFT JOIN users u ON u.id = p.manager_id
    WHERE p.initiative_id = $id AND p.is_deleted = 0
    ORDER BY p.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .proj-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
    .proj-card { 
        background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 20px; 
        transition: 0.2s; position: relative; border-left: 5px solid #ddd;
    }
    .proj-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    
    .pc-status { position: absolute; top: 20px; right: 20px; font-size: 0.7rem; padding: 3px 10px; border-radius: 12px; background: #eee; font-weight: 700; }
    .pc-title { font-size: 1.1rem; font-weight: 700; color: #2d3436; margin-bottom: 5px; }
    .pc-meta { font-size: 0.85rem; color: #95a5a6; margin-bottom: 15px; display: flex; gap: 10px; }
    
    .pc-progress { height: 6px; background: #f0f0f0; border-radius: 3px; overflow: hidden; margin-bottom: 5px; }
    .pc-fill { height: 100%; background: #3498db; }
    .pc-val { font-size: 0.8rem; font-weight: 700; text-align: right; color: #3498db; }

    .btn-link-proj { 
        background: #fff; border: 2px dashed #ddd; color: #aaa; width: 100%; padding: 15px; 
        border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.2s;
    }
    .btn-link-proj:hover { border-color: #3498db; color: #3498db; background: #f0f8ff; }
</style>

<div class="tab-card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; color:#2c3e50;">Operational Projects</h3>
        <?php if($canManage): ?>
            <a href="../../modules/operational_projects/create.php?initiative_id=<?= $id ?>" class="btn-primary" style="text-decoration:none; font-size:0.9rem; padding:8px 20px;">
                <i class="fa-solid fa-plus"></i> New Project
            </a>
        <?php endif; ?>
    </div>

    <?php if(empty($linkedProjects)): ?>
        <div style="text-align:center; padding:40px; color:#ccc;">
            <i class="fa-solid fa-briefcase" style="font-size:3rem; margin-bottom:10px;"></i>
            <p>No projects linked to this initiative.</p>
        </div>
    <?php else: ?>
        <div class="proj-grid">
            <?php foreach($linkedProjects as $p): ?>
                <div class="proj-card" style="border-left-color: <?= $p['status_color'] ?>;">
                    <span class="pc-status" style="background:<?= $p['status_color'] ?>20; color:<?= $p['status_color'] ?>;">
                        <?= $p['status_name'] ?>
                    </span>
                    <div class="pc-title"><?= htmlspecialchars($p['name']) ?></div>
                    <div class="pc-meta">
                        <span><i class="fa-regular fa-user"></i> <?= htmlspecialchars($p['manager_name']) ?></span>
                        <span><i class="fa-regular fa-calendar"></i> <?= date('M Y', strtotime($p['end_date'])) ?></span>
                    </div>
                    
                    <div class="pc-progress">
                        <div class="pc-fill" style="width:<?= $p['progress_percentage'] ?>%; background:<?= $p['status_color'] ?>;"></div>
                    </div>
                    <div class="pc-val"><?= $p['progress_percentage'] ?>%</div>

                    <a href="../../modules/operational_projects/view.php?id=<?= $p['id'] ?>" style="display:block; margin-top:10px; text-align:right; font-size:0.85rem; text-decoration:none; color:#3498db; font-weight:700;">
                        View Project &rarr;
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>