document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("darkModeToggle");
    if (!toggleBtn) return;

    const icon = toggleBtn.querySelector("i");
    const htmlElement = document.documentElement; // Targets the <html> tag

    // 1. Check LocalStorage for preference on load
    const currentTheme = localStorage.getItem("theme") || "light";
    htmlElement.setAttribute("data-bs-theme", currentTheme);

    if (currentTheme === "dark") {
        icon.classList.remove("fa-moon");
        icon.classList.add("fa-sun");
    }

    // 2. Handle Click Event
    toggleBtn.addEventListener("click", function (e) {
        e.preventDefault();

        const current = htmlElement.getAttribute("data-bs-theme");
        const nextTheme = current === "dark" ? "light" : "dark";

        // Apply the theme to Bootstrap natively
        htmlElement.setAttribute("data-bs-theme", nextTheme);
        localStorage.setItem("theme", nextTheme);

        // Swap the icons
        if (nextTheme === "dark") {
            icon.classList.remove("fa-moon");
            icon.classList.add("fa-sun");
        } else {
            icon.classList.remove("fa-sun");
            icon.classList.add("fa-moon");
        }
    });
});