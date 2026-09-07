/**
 * CampusEvent Hub — Interactive Booking Logic (booking.js)
 */

document.addEventListener('DOMContentLoaded', () => {
  const qtyInput = document.getElementById('ticketQuantity');
  const unitPriceElem = document.getElementById('unitPrice');
  const totalPriceElem = document.getElementById('totalPrice');
  const attendeesContainer = document.getElementById('attendeesContainer');
  const bookingForm = document.getElementById('bookingForm');

  if (!qtyInput || !attendeesContainer) return;

  const unitPrice = parseFloat(unitPriceElem ? unitPriceElem.getAttribute('data-price') : 0) || 0;
  const maxAvailable = parseInt(qtyInput.getAttribute('max') || 10, 10);

  function updatePricingAndAttendees() {
    let qty = parseInt(qtyInput.value, 10) || 1;
    if (qty < 1) qty = 1;
    if (qty > maxAvailable) qty = maxAvailable;
    qtyInput.value = qty;

    const total = qty * unitPrice;
    if (totalPriceElem) {
      totalPriceElem.textContent = total === 0 ? 'Free' : '$' + total.toFixed(2);
    }

    // Adjust attendee rows
    const currentRows = attendeesContainer.querySelectorAll('.attendee-row');
    const currentCount = currentRows.length;

    if (qty > currentCount) {
      for (let i = currentCount + 1; i <= qty; i++) {
        const row = document.createElement('div');
        row.className = 'attendee-row card';
        row.style.marginBottom = '14px';
        row.style.padding = '16px';
        row.id = `attendee_row_${i}`;
        row.innerHTML = `
          <div style="font-weight:700; font-size:0.88rem; color:#1e40af; margin-bottom:10px; display:flex; justify-content:space-between;">
            <span>Pass Holder #${i} Details</span>
          </div>
          <div class="form-row">
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label form-label-required">Full Name</label>
              <input type="text" name="attendee_names[]" class="form-control" placeholder="e.g. John Doe" required>
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label form-label-required">Attendee Email</label>
              <input type="email" name="attendee_emails[]" class="form-control" placeholder="e.g. j.doe@campus.edu" required>
            </div>
          </div>
        `;
        attendeesContainer.appendChild(row);
      }
    } else if (qty < currentCount) {
      for (let i = currentCount; i > qty; i--) {
        const rowToRemove = document.getElementById(`attendee_row_${i}`);
        if (rowToRemove) {
          rowToRemove.remove();
        }
      }
    }
  }

  qtyInput.addEventListener('change', updatePricingAndAttendees);
  qtyInput.addEventListener('input', updatePricingAndAttendees);

  // Quantity stepper buttons
  const btnMinus = document.getElementById('btnQtyMinus');
  const btnPlus = document.getElementById('btnQtyPlus');

  if (btnMinus) {
    btnMinus.addEventListener('click', () => {
      let val = parseInt(qtyInput.value, 10) || 1;
      if (val > 1) {
        qtyInput.value = val - 1;
        updatePricingAndAttendees();
      }
    });
  }

  if (btnPlus) {
    btnPlus.addEventListener('click', () => {
      let val = parseInt(qtyInput.value, 10) || 1;
      if (val < maxAvailable) {
        qtyInput.value = val + 1;
        updatePricingAndAttendees();
      }
    });
  }

  // Initial populate
  updatePricingAndAttendees();
});
