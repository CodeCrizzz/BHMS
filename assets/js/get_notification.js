function refreshNotifications() {
    fetch('../includes/get_notifications.php')
        .then(res => res.json())
        .then(data => {
            const bellBox = document.getElementById('sidebar-bell-container');
            const chatBox = document.getElementById('sidebar-chat-container');

            // Handle Bell Color Logic
            if (data.pending > 0) {
                const colorClass = data.urgent ? 'bell-red' : 'bell-yellow';
                bellBox.innerHTML = `<i class="fa fa-bell bell-ring-active ${colorClass}"></i>`;
            } else {
                bellBox.innerHTML = '';
            }

            // Handle Chat Badge
            if (data.unread > 0) {
                chatBox.innerHTML = `<span class="badge bg-danger rounded-pill shadow-sm">${data.unread}</span>`;
            } else {
                chatBox.innerHTML = '';
            }
        });
}
// Run on load and every 10 seconds
refreshNotifications();
setInterval(refreshNotifications, 10000);