function showToast(message, type = "success", duration = 5000) {
    const toast = document.createElement("div");
    toast.className = "toast " + type;

    toast.innerHTML = `
        <span>${message}</span>
        <button class="toast-close">&times;</button>
    `;

    document.body.appendChild(toast);

    // إغلاق يدوي
    toast.querySelector(".toast-close").onclick = () => toast.remove();

    // إغلاق تلقائي
    setTimeout(() => toast.remove(), duration);
}
