<!-- ================= RISK MODAL ================= -->
<div id="riskModal" class="modal-backdrop" style="display:none;">
    <div class="modal">

        <div class="modal-header">
            <h2>Report Risk</h2>
            <button type="button" class="close-btn" onclick="closeRiskModal()">&times;</button>
        </div>

        <div class="modal-body">

            <div class="form-field full">
                <label>Risk Title <span class="required">*</span></label>
                <input type="text" id="risk_title">
            </div>

            <div class="form-field full">
                <label>Description</label>
                <textarea id="risk_desc" rows="3"></textarea>
            </div>

            <div class="form-field full">
                <label>Mitigation Plan</label>
                <textarea id="risk_mitigation" rows="3"></textarea>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label>Probability</label>
                    <select id="risk_probability">
                        <option value="1">Low</option>
                        <option value="2">Medium</option>
                        <option value="3">High</option>
                        <option value="4">Critical</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Impact</label>
                    <select id="risk_impact">
                        <option value="1">Low</option>
                        <option value="2">Medium</option>
                        <option value="3">High</option>
                        <option value="4">Critical</option>
                    </select>
                </div>
            </div>

        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeRiskModal()">Cancel</button>
            <button type="button" class="btn-primary" onclick="addRisk()">Save Risk</button>
        </div>

    </div>
</div>
