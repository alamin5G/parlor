<?php
// filepath: c:\xampp\htdocs\parlor\employee\calendar.php
$page_title = "My Appointment Calendar";
require_once 'include/header.php';
?>
<div class="container-fluid mt-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fa fa-calendar-alt"></i> My Calendar</h2>
            <p class="text-muted mb-0">View all your assigned appointments in a calendar format.</p>
        </div>
        <div class="d-flex align-items-center">
            <label for="status_filter" class="form-label me-2 mb-0">Filter:</label>
            <select id="status_filter" class="form-select" style="width:180px;">
                <option value="">All Statuses</option>
                <option value="booked">Booked</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
                <option value="rescheduled">Rescheduled</option>
            </select>
        </div>
    </div>
    <div id="calendar"></div>
</div>

<!-- Bootstrap Modal for Event Details -->
<div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="eventDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eventDetailModalLabel">Appointment Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Customer:</strong> <span id="modalCustomerName"></span></p>
        <p><strong>Service:</strong> <span id="modalServiceName"></span></p>
        <p><strong>Time:</strong> <span id="modalTime"></span></p>
        <p><strong>Status:</strong> <span id="modalStatus"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="#" id="modalViewDetailsLink" class="btn btn-primary" target="_blank">View Full Details</a>
      </div>
    </div>
  </div>
</div>

<!-- FIX: Use the stable global bundle for FullCalendar and compatible tooltip libraries -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var statusFilter = document.getElementById('status_filter');
    var eventModal = new bootstrap.Modal(document.getElementById('eventDetailModal'));

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 750,
        themeSystem: 'bootstrap5',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
        events: {
            url: 'calendar_events.php',
            extraParams: function() {
                return { status: statusFilter.value };
            }
        },
        loading: function(isLoading) {
            if (isLoading) {
                calendarEl.classList.add('fc-loading');
            } else {
                calendarEl.classList.remove('fc-loading');
            }
        },
        eventDidMount: function(info) {
            tippy(info.el, {
                content: `
                    <strong>${info.event.extendedProps.serviceName}</strong><br>
                    Customer: ${info.event.extendedProps.customerName}<br>
                    Time: ${info.event.start.toLocaleTimeString([], {hour: 'numeric', minute: '2-digit'})}
                `,
                allowHTML: true,
            });
        },
        eventClick: function(info) {
            info.jsEvent.preventDefault(); // Prevent browser from following link
            
            document.getElementById('modalCustomerName').textContent = info.event.extendedProps.customerName;
            document.getElementById('modalServiceName').textContent = info.event.extendedProps.serviceName;
            document.getElementById('modalTime').textContent = info.event.start.toLocaleString([], {
                dateStyle: 'medium',
                timeStyle: 'short'
            });
            
            var statusEl = document.getElementById('modalStatus');
            statusEl.innerHTML = `<span class="badge" style="background-color:${info.event.backgroundColor}; color: #fff;">${info.event.extendedProps.status}</span>`;
            
            document.getElementById('modalViewDetailsLink').href = 'appointment_view.php?id=' + info.event.id;
            
            eventModal.show();
        },
        noEventsContent: "No appointments for this period."
    });
    calendar.render();

    // Refetch events when filter changes
    statusFilter.addEventListener('change', function() {
        calendar.refetchEvents();
    });
});
</script>
<style>
#calendar {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    padding: 20px;
    position: relative;
}
/* Loading spinner style */
.fc-loading::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 40px;
    height: 40px;
    margin-top: -20px;
    margin-left: -20px;
    border: 4px solid rgba(0, 0, 0, 0.1);
    border-left-color: var(--bs-primary);
    border-radius: 50%;
    animation: fc-spin 1s linear infinite;
    z-index: 100;
}
@keyframes fc-spin {
    to { transform: rotate(360deg); }
}
.modal-body p { margin-bottom: 0.5rem; }
.modal-body strong { color: #333; }
#modalStatus .badge { text-transform: capitalize; font-size: 0.9rem; }
</style>
<?php require_once 'include/footer.php'; ?>