    function openModal() { document.getElementById('uploadModal').style.display = 'block'; }
    function closeModal() { document.getElementById('uploadModal').style.display = 'none'; }
    
    function toggleParentSelect() {
        document.getElementById('select_milestone').style.display = 'none';
        document.getElementById('select_task').style.display = 'none';
        document.getElementById('select_risk').style.display = 'none';
        
        var type = document.getElementById('parentTypeSelect').value;
        if(type === 'milestone') document.getElementById('select_milestone').style.display = 'block';
        if(type === 'task') document.getElementById('select_task').style.display = 'block';
        if(type === 'risk') document.getElementById('select_risk').style.display = 'block';
    }

