<!-- ================= KPI SELECT MODAL ================= -->
<div id="kpiModal" class="modal-backdrop" style="display:none;">
    <div class="modal">

        <div class="modal-header">
            <h2>Select KPIs</h2>
            <button type="button" class="close-btn" onclick="closeKpiModal()">&times;</button>
        </div>

        <div class="modal-body">

            <input type="text" id="kpiSearch" placeholder="Search..." oninput="filterKpis()">

            <div class="kpi-table-wrapper">
                <table class="kpi-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>KPI</th>
                            <th>Type</th>
                            <th>Current</th>
                            <th>Target</th>
                            <th>Frequency</th>
                        </tr>
                    </thead>
                    <tbody id="kpiTableBody">
                        <!-- Will be loaded dynamically by JS -->
                    </tbody>
                </table>
            </div>

        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeKpiModal()">Close</button>
            <button type="button" class="btn-primary" onclick="applySelectedKpis()">Add Selected</button>
        </div>

    </div>
</div>
