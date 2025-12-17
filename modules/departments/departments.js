function filterDepartments() {
    let q = document.getElementById("searchBox").value.toLowerCase();
    let rows = document.querySelectorAll("#deptTable tbody tr");

    rows.forEach(r => {
        let text = r.innerText.toLowerCase();
        r.style.display = text.includes(q) ? "" : "none";
    });
}
