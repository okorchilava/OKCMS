<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
    // 1. Tooltip-ების ინიციალიზაცია
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // ─────────────────────────────────────────────────────────────────────────────
    // 2. GLOBAL FUNCTIONS (ჰედერის ინტერაქციისთვის)
    // ─────────────────────────────────────────────────────────────────────────────
    
    // ეს ფუნქცია იძახება ჰედერის დროპდაუნში "ყველას წაკითხვაზე" დაჭერისას
    window.okMarkAllRead = function(e) {
        e.stopPropagation(); 
        fetch('index.php?ajax_action=mark_notifications_read')
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    // 1. გავაქროთ ბეიჯი ჰედერში
                    const badge = document.getElementById('ok-notif-badge');
                    if(badge) badge.style.display = 'none';
                    
                    // 2. მოვხსნათ 'unread' კლასი დროპდაუნის ელემენტებს
                    document.querySelectorAll('.ok-notif-item').forEach(el => el.classList.remove('unread'));
                    
                    // 3. გავაქროთ საიდბარის ბეიჯიც (ვიზუალურად)
                    const sidebarBadge = document.querySelector('#ok-notif-live-wrapper .ok-menu-badge');
                    if(sidebarBadge) sidebarBadge.style.display = 'none';
                }
            })
            .catch(err => console.error('Error:', err));
    }

    // ეს ფუნქცია იძახება ჰედერის დროპდაუნში თითოეული ნოთიფიკაციის წერტილზე დაჭერისას
    window.okToggleRead = function(e, id) {
        e.preventDefault(); 
        e.stopPropagation(); 
        
        // ვაგზავნით AJAX მოთხოვნას სტატუსის შესაცვლელად
        const formData = new FormData();
        formData.append('ajax_action', 'ok_toggle_read_status');
        formData.append('n_id', id);

        fetch('index.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                const item = document.getElementById('notif-item-' + id);
                if(item) {
                    item.classList.toggle('unread');
                    // ბეიჯის განახლებას Live Update მიხედავს 5 წამში, 
                    // ან შეგვიძლია აქაც გავაკეთოთ ლოგიკა, მაგრამ Live Update საკმარისია.
                }
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 3. 🔔 Live Notification Update (ავტომატური განახლება)
    // ─────────────────────────────────────────────────────────────────────────────

    document.addEventListener("DOMContentLoaded", function() {

        function checkNotifications() {
            fetch('index.php?ajax_action=get_unread_count')
                .then(response => {
                    if (!response.ok) throw new Error("Network response failed");
                    return response.json();
                })
                .then(data => {
                    const count = parseInt(data.count);
                    updateSidebarBadge(count);
                    updateHeaderBadge(count);
                })
                .catch(err => console.error('Live Update Error:', err));
        }

        // --- A. საიდბარის განახლება ---
        function updateSidebarBadge(count) {
            const wrapper = document.getElementById('ok-notif-live-wrapper');
            if (!wrapper) return;

            let badge = wrapper.querySelector('.ok-menu-badge');

            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'badge bg-danger rounded-pill ok-menu-badge';
                    wrapper.appendChild(badge);
                }
                badge.textContent = count;
                badge.style.display = 'flex';
            } else {
                if (badge) badge.style.display = 'none';
            }
        }

        // --- B. ჰედერის განახლება ---
        function updateHeaderBadge(count) {
            const dropdownBtn = document.getElementById('notifDropdown');
            if (!dropdownBtn) return;

            let badge = document.getElementById('ok-notif-badge');

            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.id = 'ok-notif-badge';
                    badge.className = 'badge rounded-pill bg-danger notif-badge-anim ok-badge-adjusted';
                    badge.innerHTML = count; 
                    dropdownBtn.appendChild(badge);
                }
                badge.firstChild.textContent = (count > 99) ? '99+' : count;
                badge.style.display = 'inline-block';
            } else {
                if (badge) badge.style.display = 'none';
            }
        }

        // პირველი გაშვება
        checkNotifications();

        // გამეორება ყოველ 5 წამში
        setInterval(checkNotifications, 5000);
    });
</script>

</body>
</html>