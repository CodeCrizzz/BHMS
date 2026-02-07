document.addEventListener("DOMContentLoaded", function() {
    const toggleBtn = document.getElementById("darkModeToggle");
    const icon = toggleBtn.querySelector("i");
    const body = document.body;

    // Check LocalStorage for preference
    if (localStorage.getItem("theme") === "dark") {
        body.classList.add("dark-mode");
        icon.classList.remove("fa-moon");
        icon.classList.add("fa-sun");
    }

    // Handle Click
    toggleBtn.addEventListener("click", function(e) {
        e.preventDefault();
        body.classList.toggle("dark-mode");

        if (body.classList.contains("dark-mode")) {
            localStorage.setItem("theme", "dark");
            icon.classList.remove("fa-moon");
            icon.classList.add("fa-sun");
        } else {
            localStorage.setItem("theme", "light");
            icon.classList.remove("fa-sun");
            icon.classList.add("fa-moon");
        }
    });
});