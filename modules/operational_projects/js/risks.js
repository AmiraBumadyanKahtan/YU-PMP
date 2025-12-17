function openModal() {
        document.getElementById('riskModal').style.display = 'block';
        document.getElementById('modalTitle').innerText = 'Identify New Risk';
        document.getElementById('r_id').value = '';
        document.getElementById('r_title').value = '';
        document.getElementById('r_desc').value = '';
        document.getElementById('r_plan').value = '';
        document.getElementById('r_prob').value = '3';
        document.getElementById('r_imp').value = '3';
        document.getElementById('status_div').style.display = 'none';
    }

    // دالة التعديل الآمنة
    function editRisk(btn) {
        document.getElementById('riskModal').style.display = 'block';
        document.getElementById('modalTitle').innerText = 'Edit Risk';
        
        // قراءة البيانات من الزر
        document.getElementById('r_id').value = btn.getAttribute('data-id');
        document.getElementById('r_title').value = btn.getAttribute('data-title');
        document.getElementById('r_desc').value = btn.getAttribute('data-desc');
        document.getElementById('r_plan').value = btn.getAttribute('data-plan');
        document.getElementById('r_prob').value = btn.getAttribute('data-prob');
        document.getElementById('r_imp').value = btn.getAttribute('data-imp');
        
        document.getElementById('status_div').style.display = 'block';
        document.getElementById('r_status').value = btn.getAttribute('data-status');
    }

    function closeModal() {
        document.getElementById('riskModal').style.display = 'none';
    }