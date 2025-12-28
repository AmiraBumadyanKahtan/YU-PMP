// modules/departments/js/delete.js

function deleteDepartment(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You are about to archive this department. This action can be undone by admin only.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, archive it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            
            // إظهار لودينق
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire(
                        'Archived!',
                        'The department has been archived successfully.',
                        'success'
                    ).then(() => {
                        location.reload(); // تحديث الصفحة
                    });
                } else if (data.status === 'blocked') {
                    // رسالة مخصصة إذا كان الحذف ممنوعاً لوجود بيانات مرتبطة
                    Swal.fire(
                        'Cannot Delete',
                        data.message,
                        'info' // أيقونة معلومات بدلاً من خطأ أحمر
                    );
                } else {
                    Swal.fire(
                        'Error!',
                        data.message || 'Something went wrong.',
                        'error'
                    );
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire(
                    'Error!',
                    'Request failed. Please check your connection.',
                    'error'
                );
            });
        }
    });
}