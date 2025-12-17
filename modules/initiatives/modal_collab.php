<!-- ================= COLLABORATION MODAL ================= -->
<div id="collabModal" class="modal-backdrop" style="display:none;">
    <div class="modal">

        <div class="modal-header">
            <h2>Request Collaboration</h2>
            <button type="button" class="close-btn" onclick="closeCollabModal()">&times;</button>
        </div>

        <div class="modal-body">

            <!-- Department -->
            <div class="form-field full" style="margin-bottom: 25px;">
                <label>Department <span class="required">*</span></label>
                <select id="collab_dept">
                    <option value="">Select Department…</option>
                    <?php 
                    $departments = db()->query("
                        SELECT id, name 
                        FROM departments 
                        ORDER BY name ASC
                    ")->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Reason -->
            <div class="form-field full">
                <label>Reason <span class="required">*</span></label>
                <textarea id="collab_reason" rows="3" placeholder="Explain why support is needed..."></textarea>
            </div>

        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeCollabModal()">Cancel</button>
            <button type="button" class="btn-primary" onclick="addCollaboration()">Add</button>
        </div>

    </div>
</div>
