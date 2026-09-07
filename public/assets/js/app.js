/**
 * CampusEvent Hub — Global Interactivity (app.js)
 * Clean, lightweight Vanilla JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. User Profile Dropdown Toggle
  const userMenuBtn = document.getElementById('userMenuBtn');
  const userMenuDropdown = document.getElementById('userMenuDropdown');

  if (userMenuBtn && userMenuDropdown) {
    userMenuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isExpanded = userMenuBtn.getAttribute('aria-expanded') === 'true';
      userMenuBtn.setAttribute('aria-expanded', !isExpanded);
      userMenuDropdown.classList.toggle('show');
    });

    document.addEventListener('click', (e) => {
      if (!userMenuDropdown.contains(e.target) && !userMenuBtn.contains(e.target)) {
        userMenuDropdown.classList.remove('show');
        userMenuBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // 2. Mobile Drawer Navigation Toggle
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const mobileDrawer = document.getElementById('mobileDrawer');
  const drawerClose = document.getElementById('drawerClose');

  if (mobileMenuToggle && mobileDrawer) {
    mobileMenuToggle.addEventListener('click', () => {
      mobileDrawer.classList.add('open');
      mobileMenuToggle.setAttribute('aria-expanded', 'true');
    });

    if (drawerClose) {
      drawerClose.addEventListener('click', () => {
        mobileDrawer.classList.remove('open');
        mobileMenuToggle.setAttribute('aria-expanded', 'false');
      });
    }

    // Close on outside click
    document.addEventListener('click', (e) => {
      if (mobileDrawer.classList.contains('open') && 
          !mobileDrawer.contains(e.target) && 
          !mobileMenuToggle.contains(e.target)) {
        mobileDrawer.classList.remove('open');
        mobileMenuToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // 3. Flash Messages Auto-Dismiss after 6 seconds
  const flashAlerts = document.querySelectorAll('.alert-dismissible');
  flashAlerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-10px)';
      setTimeout(() => alert.remove(), 400);
    }, 6000);
  });

  // 4. Generic Confirm Prompt Helpers (for deletions/cancellations)
  const confirmActions = document.querySelectorAll('[data-confirm]');
  confirmActions.forEach((elem) => {
    elem.addEventListener('click', (e) => {
      const message = elem.getAttribute('data-confirm') || 'Are you sure you want to proceed with this action?';
      if (!confirm(message)) {
        e.preventDefault();
      }
    });
  });
});
