/* ==========================================================
   GLOBAL VARIABLES
========================================================== */

let objectiveIndex = 0;
let resourceIndex  = 0;
let teamIndex      = 0;
let collabIndex    = 0;


/* ==========================================================
   0) AUTO-GENERATE INITIATIVE CODE
   (I-YYYYMMDD-XXX)
========================================================== */

document.addEventListener("DOMContentLoaded", () => {
    const codeInput = document.getElementById("initiative_code_auto");
    if (codeInput) {
        codeInput.value = generateInitiativeCode();
    }
});

function generateInitiativeCode() {
    const today = new Date();
    const y = today.getFullYear();
    const m = String(today.getMonth() + 1).padStart(2, '0');
    const d = String(today.getDate()).padStart(2, '0');
    const random = String(Math.floor(Math.random() * 999)).padStart(3, '0');

    return `I-${y}${m}${d}-${random}`;
}


/* ==========================================================
   1) LOAD OBJECTIVES BY PILLAR
========================================================== */

function loadObjectivesByPillar(pillarId) {
    const select = document.getElementById("objective_select");
    const list = document.getElementById("selectedObjectives");

    select.innerHTML = `<option value="">Loading...</option>`;
    list.innerHTML = ""; // clear selected if pillar changed

    if (!pillarId) {
        select.innerHTML = `<option value="">Select Objective...</option>`;
        return;
    }

    fetch(`load_objectives.php?pillar_id=${pillarId}`)
        .then(res => res.json())
        .then(data => {
            select.innerHTML = `<option value="">Select Objective...</option>`;
            data.forEach(obj => {
                select.innerHTML += `
                    <option value="${obj.id}">${obj.objective_text}</option>
                `;
            });
        })
        .catch(err => {
            console.error(err);
            select.innerHTML = `<option value="">Error loading...</option>`;
        });
}


/* ==========================================================
   2) ADD STRATEGIC OBJECTIVE
========================================================== */

function addObjective() {
    const select = document.getElementById("objective_select");
    const list   = document.getElementById("selectedObjectives");

    const id = select.value;
    const text = select.options[select.selectedIndex]?.text;

    if (!id) return;

    // Prevent duplicates
    if (list.querySelector(`li[data-id="${id}"]`)) return;

    const li = document.createElement("li");
    li.className = "tag-item";
    li.dataset.id = id;

    li.innerHTML = `
        <span>${text}</span>
        <button type="button" onclick="this.parentElement.remove()">×</button>
        <input type="hidden" name="objective_ids[]" value="${id}">
    `;

    list.appendChild(li);
}


/* ==========================================================
   3) ADD RESOURCE (work_resources)
========================================================== */

function addResourceRow() {
    const container = document.getElementById("resourceContainer");

    const div = document.createElement("div");
    div.className = "resource-row";

    div.innerHTML = `
        <input type="text" name="resources[${resourceIndex}][name]" placeholder="Resource Name" required>

        <select name="resources[${resourceIndex}][resource_type_id]" required>
            ${window.resourceTypes
                .map(t => `<option value="${t.id}">${t.type_name}</option>`)
                .join("")}
        </select>

        <input type="number" name="resources[${resourceIndex}][qty]" value="1" min="1">

        <button type="button" class="icon-btn danger" onclick="this.parentElement.remove()">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;

    container.appendChild(div);
    resourceIndex++;
}


/* ==========================================================
   4) ADD TEAM MEMBER (initiative_team)
========================================================== */

function addTeamMember() {
    const userSel = document.getElementById("team_user_select");
    const roleSel = document.getElementById("team_role_select");
    const list    = document.getElementById("teamList");

    const user_id = userSel.value;
    const role_id = roleSel.value;

    if (!user_id || !role_id) return;

    const userName = userSel.options[userSel.selectedIndex].text;
    const roleName = roleSel.options[roleSel.selectedIndex].text;

    const li = document.createElement("li");
    li.className = "tag-item";

    li.innerHTML = `
        <span>${userName} – ${roleName}</span>
        <button type="button" onclick="this.parentElement.remove()">×</button>

        <input type="hidden" name="team_members[${teamIndex}][user_id]" value="${user_id}">
        <input type="hidden" name="team_members[${teamIndex}][role_id]" value="${role_id}">
    `;

    list.appendChild(li);

    userSel.value = "";
    roleSel.value = "";
    teamIndex++;
}


/* ==========================================================
   5) COLLABORATION (collaborations)
========================================================== */

function openCollabModal() {
    document.getElementById("collabModal").style.display = "flex";
}

function closeCollabModal() {
    document.getElementById("collabModal").style.display = "none";
}

function addCollaboration() {
    const deptId = document.getElementById("collab_dept").value;
    const reason = document.getElementById("collab_reason").value.trim();

    if (!deptId || !reason) return;

    const deptName = document.getElementById("collab_dept")
                     .options[document.getElementById("collab_dept").selectedIndex].text;

    const list = document.getElementById("collaborationList");

    const li = document.createElement("li");
    li.className = "tag-item";

    li.innerHTML = `
        <span>${deptName} — ${reason}</span>
        <button type="button" onclick="this.parentElement.remove()">×</button>

        <input type="hidden" name="collaboration[${collabIndex}][department_id]" value="${deptId}">
        <input type="hidden" name="collaboration[${collabIndex}][reason]" value="${reason}">
    `;

    list.appendChild(li);

    collabIndex++;
    closeCollabModal();
}
