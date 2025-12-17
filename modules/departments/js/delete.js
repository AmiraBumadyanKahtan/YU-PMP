// modules/departments/js/delete.js

function deleteDepartment(id) {
    Swal.fire({
        title: 'Delete Department?',
        text: 'This will archive the department (Soft Delete)',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) return;

        fetch('delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        })
        .then(response => {
            if (!response.ok) {
                throw new Error("HTTP error " + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                Swal.fire(
                    'Deleted!',
                    'Department has been archived.',
                    'success'
                ).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire(
                    'Error!',
                    data.message || 'Something went wrong.',
                    'error'
                );
            }
        })
        .catch(error => {
            console.error(error);
            Swal.fire('Error', 'Request failed or invalid response.', 'error');
        });
    });
}