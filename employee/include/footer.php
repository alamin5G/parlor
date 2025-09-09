
        </div> <!-- Closes .content-wrapper -->
    </div> <!-- Closes .main-content -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Notification Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notificationBadge = document.getElementById('notification-badge');
            const notificationList = document.getElementById('notification-list');
            const notificationBellLink = document.getElementById('notificationBellLink');

            function fetchNotifications() {
                fetch('get_notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        notificationList.innerHTML = data.html;
                        if (data.count > 0) {
                            notificationBadge.textContent = data.count;
                            notificationBadge.style.display = 'block';
                        } else {
                            notificationBadge.style.display = 'none';
                        }
                    })
                    .catch(error => console.error('Error fetching notifications:', error));
            }

            function markNotificationsSeen() {
                // Only run if there's a badge to clear
                if (notificationBadge.style.display === 'none') return;

                fetch('mark_notifications_seen.php', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            // Hide badge immediately for better UX, then refetch
                            notificationBadge.style.display = 'none';
                            fetchNotifications();
                        }
                    });
            }

            // Mark as seen when the dropdown is opened
            notificationBellLink.addEventListener('click', markNotificationsSeen);

            // Fetch notifications on page load and then every 60 seconds
            fetchNotifications();
            setInterval(fetchNotifications, 60000); 
        });
    </script>
</body>
</html>