<?php
ob_end_flush();
?>
  </main>
  <!-- FOOTER -->
  <footer class="dashboard-footer">
    <div class="container">
      <?php if (!empty($settings['school_logo'])): ?>
        <img src="<?= htmlspecialchars($settings['school_logo']) ?>" alt="School Logo" class="dashboard-footer__logo">
      <?php else: ?>
        <div></div>
      <?php endif; ?>

      <div class="dashboard-footer__text">
        <p class="mb-1">&copy; <?= date('Y') ?> <?= htmlspecialchars($settings['site_title']) ?>. All Rights Reserved.</p>
        <p class="mb-0">Contact: <a href="mailto:<?= htmlspecialchars($settings['contact_email']) ?>"><?= htmlspecialchars($settings['contact_email']) ?></a></p>
      </div>

      <?php if (!empty($settings['organization_logo'])): ?>
        <img src="<?= htmlspecialchars($settings['organization_logo']) ?>" alt="Organization Logo" class="dashboard-footer__logo">
      <?php else: ?>
        <div></div>
      <?php endif; ?>
    </div>
  </footer>

  <!-- jQuery and Bootstrap Bundle (includes Popper) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Update the clock in the header every second
    function updateDashboardClock() {
      const options = {
        timeZone: 'Asia/Manila',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      };
      document.getElementById('dashboardClock').textContent = new Date().toLocaleString('en-US', options);
    }
    setInterval(updateDashboardClock, 1000);
    updateDashboardClock();

    // Toggle the sidebar open/close
    document.getElementById('dashboardSidebarToggle').addEventListener('click', function () {
      document.getElementById('dashboardSidebar').classList.toggle('active');
    });

    // Adjust footer position if page content is short
    function updateDashboardFooterPosition() {
      const footer = document.querySelector('.dashboard-footer');
      const bodyHeight = document.body.offsetHeight;
      const windowHeight = window.innerHeight;
      if (bodyHeight < windowHeight) {
        footer.style.position = 'fixed';
        footer.style.bottom = '0';
        footer.style.width = '100%';
      } else {
        footer.style.position = 'relative';
      }
    }
    window.addEventListener('load', updateDashboardFooterPosition);
    window.addEventListener('resize', updateDashboardFooterPosition);
    window.addEventListener('orientationchange', updateDashboardFooterPosition);

    // Example Notification Marking Functionality
    $(document).on('click', '.notification-item .mark-read', function(e) {
      e.stopPropagation();
      var $this = $(this);
      var notificationId = $this.closest('.notification-item').data('id');
      $.ajax({
        url: 'notif/mark_notification',
        method: 'POST',
        data: { notification_id: notificationId },
        success: function(response) {
          $this.closest('.notification-item').removeClass('unread')
            .find('.notification-status').removeClass('bg-primary').addClass('bg-secondary').text('Read');
          updateNotificationCountAfterMark(1);
        },
        error: function() {
          console.error("Could not mark notification as read.");
        }
      });
    });

    $('#dashboardMarkAllRead').on('click', function(e) {
      e.preventDefault();
      $.ajax({
        url: 'notif/mark_notification',
        method: 'POST',
        data: { mark_all: true },
        success: function(response) {
          $('.notification-item').removeClass('unread')
            .find('.notification-status').removeClass('bg-primary').addClass('bg-secondary').text('Read');
          updateNotificationCountAfterMark('all');
        },
        error: function() {
          console.error("Could not mark all notifications as read.");
        }
      });
    });

    function updateNotificationCountAfterMark(markedCount) {
      var $badge = $('#dashboardNotificationCount');
      if ($badge.length) {
        if (markedCount === 'all') {
          $badge.remove();
        } else {
          var currentCount = parseInt($badge.text());
          var newCount = currentCount - markedCount;
          if (newCount > 0) {
            $badge.text(newCount);
          } else {
            $badge.remove();
          }
        }
      }
    }
  </script>
</body>
</html>