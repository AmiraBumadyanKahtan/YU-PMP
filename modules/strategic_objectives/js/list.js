const searchInput = document.getElementById("searchInput");
const pillarFilter = document.getElementById("pillarFilter");
const applyBtn = document.getElementById("applyFilterBtn");
const resetBtn = document.getElementById("resetFilterBtn");

function filterTable() {
    const term = searchInput.value.toLowerCase();
    const pillar = pillarFilter.value;

    document.querySelectorAll("#objectivesTable tbody tr").forEach(row => {
        const matchesPillar = !pillar || row.dataset.pillar === pillar;
        const matchesText = row.innerText.toLowerCase().includes(term);
        row.style.display = (matchesPillar && matchesText) ? "" : "none";
    });
}

applyBtn.onclick = filterTable;

resetBtn.onclick = () => {
    searchInput.value = "";
    pillarFilter.value = "";
    filterTable();
};
