<!-- ================= KPI CREATE MODAL ================= -->
<div id="addKpiModal" class="modal-backdrop" style="display:none;">
    <div class="modal">

        <div class="modal-header">
            <h2>Create New KPI</h2>
            <button type="button" class="close-btn" onclick="closeAddKpiModal()">&times;</button>
        </div>

        <div class="modal-body">

            <div class="form-field full">
                <label>KPI Name <span class="required">*</span></label>
                <input type="text" id="kpi_new_name">
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label>Target Value <span class="required">*</span></label>
                    <input type="number" id="kpi_new_target">
                </div>

                <div class="form-field">
                    <label>Unit</label>
                    <select id="kpi_new_unit">
                        <option value="%">%</option>
                        <option value="SAR">SAR</option>
                        <option value="Number">Number</option>
                        <option value="Ratio">Ratio</option>
                        <option value="Rating">Rating</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label>KPI Type</label>
                    <select id="kpi_new_type">
                        <option value="Numeric">Numeric</option>
                        <option value="Percentage">Percentage</option>
                        <option value="Ratio">Ratio</option>
                        <option value="Financial">Financial</option>
                        <option value="Rating">Rating</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Frequency</label>
                    <select id="kpi_new_frequency">
                        <option value="Monthly">Monthly</option>
                        <option value="Quarterly">Quarterly</option>
                        <option value="Annually">Annually</option>
                        <option value="Semester">Semester</option>
                        <option value="On-demand">On Demand</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label>Baseline</label>
                    <input type="number" id="kpi_new_baseline">
                </div>

                <div class="form-field">
                    <label>Current</label>
                    <input type="number" id="kpi_new_current">
                </div>
            </div>

            <div class="form-field full">
                <label>Data Source</label>
                <input type="text" id="kpi_new_source">
            </div>

            <div class="form-field full">
                <label>KPI Owner <span class="required">*</span></label>
                <select id="kpi_new_owner">
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field full">
                <label>Description</label>
                <textarea id="kpi_new_description" rows="2"></textarea>
            </div>

        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeAddKpiModal()">Cancel</button>
            <button type="button" class="btn-primary" onclick="submitNewKpi()">Add KPI</button>
        </div>

    </div>
</div>
