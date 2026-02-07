document.addEventListener("DOMContentLoaded", function() {
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.querySelector(".sidebar");
    const contentArea = document.querySelector(".flex-grow-1");

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener("click", function(e) {
            e.stopPropagation(); 
            sidebar.classList.toggle("show");
        });

        document.addEventListener("click", function(event) {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnToggle = sidebarToggle.contains(event.target);

            if (sidebar.classList.contains("show") && !isClickInsideSidebar && !isClickOnToggle) {
                sidebar.classList.remove("show");
            }
        });
    }
});