// Function to print a specific area (Simulating PDF Export)
function printDashboard() {
    // Get the content of the dashboard
    var printContents = document.getElementById('printableArea').innerHTML;
    var originalContents = document.body.innerHTML;

    // Swap the body content with just the printable area
    document.body.innerHTML = printContents;

    window.print();

    // Restore original content so the buttons work again
    document.body.innerHTML = originalContents;
    location.reload(); // Reload to re-bind event listeners
}

// Auto-hide alerts after 3 seconds
document.addEventListener("DOMContentLoaded", function () {
    setTimeout(function () {
        let alerts = document.querySelectorAll('.alert');
        alerts.forEach(function (alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 3000);
});