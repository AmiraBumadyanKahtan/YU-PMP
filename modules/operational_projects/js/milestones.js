    function toggleBody(id) {
        var el = document.getElementById(id);
        var isOpen = (el.style.display === 'block');
        el.style.display = isOpen ? 'none' : 'block';
        var icon = document.getElementById('icon-' + id);
        if(icon) icon.className = isOpen ? 'fa-solid fa-chevron-right' : 'fa-solid fa-chevron-down';
    }

    function openModal(id) { document.getElementById(id).style.display = "block"; }
    function closeModal(id) { document.getElementById(id).style.display = "none"; }
    
    function openTaskModal(msId) {
        document.getElementById('taskModalTitle').innerText = "Add New Task";
        document.getElementById('t_id').value = "";
        document.getElementById('t_ms_id').value = msId ? msId : ""; 
        document.getElementById('t_title').value = "";
        document.getElementById('t_desc').value = "";
        document.getElementById('t_assigned').value = "";
        document.getElementById('t_start').value = "";
        document.getElementById('t_due').value = "";
        document.getElementById('t_cost').value = "0";
        document.getElementById('t_spent').value = "0";
        document.getElementById('t_weight').value = "1";
        document.getElementById('t_status').value = "1";
        updateProgressDisplay("1");
        openModal('taskModal');
    }

    function editTask(btn) {
        document.getElementById('taskModalTitle').innerText = "Edit Task";
        document.getElementById('t_id').value = btn.getAttribute('data-id');
        var msId = btn.getAttribute('data-ms-id');
        document.getElementById('t_ms_id').value = msId ? msId : "";
        document.getElementById('t_title').value = btn.getAttribute('data-title');
        document.getElementById('t_desc').value = btn.getAttribute('data-desc');
        document.getElementById('t_assigned').value = btn.getAttribute('data-assigned');
        document.getElementById('t_start').value = btn.getAttribute('data-start');
        document.getElementById('t_due').value = btn.getAttribute('data-due');
        document.getElementById('t_cost').value = btn.getAttribute('data-cost');
        document.getElementById('t_spent').value = btn.getAttribute('data-spent');
        document.getElementById('t_priority').value = btn.getAttribute('data-priority');
        document.getElementById('t_weight').value = btn.getAttribute('data-weight');
        document.getElementById('t_status').value = btn.getAttribute('data-status');
        updateProgressDisplay(btn.getAttribute('data-status'));
        openModal('taskModal');
    }

    function updateProgressDisplay(statusId) {
        let progress = 0;
        if(statusId == 3) progress = 100;
        else if(statusId == 2) progress = 50;
        else progress = 0;
        document.getElementById('t_progress').value = progress;
    }