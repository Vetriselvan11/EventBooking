/**
 * CampusEvent Hub — Client-Side Mock Database Engine (mock-db.js)
 * Enables 100% functional browsing, booking, payment, Event CRUD, RBAC access control, and coordinator management
 */

const SEED_CATEGORIES = [
  { id: 1, name: 'Technology & Coding', slug: 'technology-coding', event_count: 2 },
  { id: 2, name: 'Academic Conferences', slug: 'academic-conferences', event_count: 1 },
  { id: 3, name: 'Career & Industry Networking', slug: 'career-networking', event_count: 2 },
  { id: 4, name: 'Cultural & Arts', slug: 'cultural-arts', event_count: 2 },
  { id: 5, name: 'Athletics & Sports', slug: 'athletics-sports', event_count: 1 }
];

const SEED_COORDINATORS = [
  {
    id: 2,
    name: 'Prof. Elena Rostova',
    email: 'coordinator@campus.edu',
    department: 'Computer Science & Engineering',
    designation: 'Associate Professor & Event Chair',
    phone: '+1 (555) 392-1084',
    status: 'active',
    assigned_events: 3
  },
  {
    id: 3,
    name: 'Dr. Marcus Vance',
    email: 'marcus.vance@campus.edu',
    department: 'Business Administration & Career Services',
    designation: 'Director of Corporate Partnerships',
    phone: '+1 (555) 441-9273',
    status: 'active',
    assigned_events: 2
  }
];

const SEED_STUDENTS = [
  {
    id: 4,
    name: 'Alexander Wright',
    email: 'student@campus.edu',
    student_id: 'STU-2024-8841',
    department: 'Computer Science & Engineering',
    year: '3rd Year',
    total_bookings: 2,
    status: 'active'
  },
  {
    id: 5,
    name: 'Sophia Chen',
    email: 's.chen@campus.edu',
    student_id: 'STU-2025-1092',
    department: 'Electrical Engineering',
    year: '2nd Year',
    total_bookings: 1,
    status: 'active'
  },
  {
    id: 6,
    name: 'Liam Rodriguez',
    email: 'l.rodriguez@campus.edu',
    student_id: 'STU-2023-4412',
    department: 'Business Administration',
    year: '4th Year',
    total_bookings: 3,
    status: 'active'
  }
];

const SEED_EVENTS = [
  {
    id: 1,
    category_id: 1,
    category_name: 'Technology & Coding',
    title: 'Annual AI & Cloud Computing Summit 2026',
    slug: 'annual-ai-cloud-summit-2026',
    short_description: 'A premier gathering of engineering researchers, cloud architects, and industry pioneers discussing scalable deep learning systems.',
    description: 'Join industry leaders and academic innovators for the 2026 AI & Cloud Computing Summit. This full-day conference brings together keynote presentations from leading AI researchers, interactive technical workshops on distributed machine learning, cloud-native deployments, and hands-on lab sessions.\n\nAgenda Highlights:\n- 09:00 AM: Keynote Address — Scaling Neural Models in Distributed Environments\n- 11:00 AM: Panel Discussion — Ethics and Governance in Generative AI\n- 01:30 PM: Hands-on Workshop: High-Throughput Kubernetes Clusters\n- 04:00 PM: Student Research Poster Exhibition & Networking Reception\n\nAll registered attendees receive full conference access, luncheon voucher, and digital certificate of participation.',
    event_date: '2026-09-19',
    start_time: '09:00 AM',
    end_time: '05:30 PM',
    venue: 'Grand University Auditorium',
    venue_address: 'Auditorium Complex, North Campus, Gate 2',
    coordinator_name: 'Prof. Elena Rostova',
    coordinator_email: 'coordinator@campus.edu',
    coordinator_dept: 'Computer Science & Engineering',
    coordinator_desig: 'Associate Professor & Event Chair',
    ticket_price: 25.00,
    max_capacity: 250,
    available_seats: 246,
    registration_deadline: '2026-09-17 18:00',
    status: 'open',
    is_featured: 1
  },
  {
    id: 2,
    category_id: 3,
    category_name: 'Career & Industry Networking',
    title: 'National Tech & Engineering Career Fair 2026',
    slug: 'national-tech-career-fair-2026',
    short_description: 'Connect directly with hiring managers and technical recruiters from over 60 Fortune 500 technology companies.',
    description: 'The National Tech & Engineering Career Fair is the campus event for students seeking internships, co-ops, and full-time engineering positions. Meet recruiters from top software companies, aerospace firms, robotics laboratories, and venture-backed startups.\n\nKey Requirements:\n- Bring printed copies of your resume\n- University ID card is mandatory for entry\n- Business casual attire recommended\n- Free resume screening booths available on Floor 2',
    event_date: '2026-09-12',
    start_time: '10:00 AM',
    end_time: '04:00 PM',
    venue: 'Campus Exhibition Center — Hall A & B',
    venue_address: 'Center for Innovation & Career Services',
    coordinator_name: 'Dr. Marcus Vance',
    coordinator_email: 'marcus.vance@campus.edu',
    coordinator_dept: 'Business Administration & Career Services',
    coordinator_desig: 'Director of Corporate Partnerships',
    ticket_price: 0.00,
    max_capacity: 500,
    available_seats: 485,
    registration_deadline: '2026-09-11 23:59',
    status: 'open',
    is_featured: 1
  },
  {
    id: 3,
    category_id: 1,
    category_name: 'Technology & Coding',
    title: '36-Hour Hackathon: HackTheCampus 2026',
    slug: 'hackthecampus-2026',
    short_description: 'Compete in teams of 2-4 to build real-world software solutions addressing sustainable campus challenges with $10,000 in prizes.',
    description: 'HackTheCampus is our flagship university hackathon. Over 36 exhilarating hours, developers, designers, and domain enthusiasts collaborate to engineer high-impact solutions for smart campuses, renewable energy, and educational accessibility.\n\nTracks:\n1. Smart Campus Mobility & Logistics\n2. AI for Academic Productivity\n3. Green Tech & Zero-Waste Solutions\n4. Open Innovation\n\nHardware kits, mentor guidance, 5 meals, high-speed fiber internet, and midnight snacks provided.',
    event_date: '2026-09-26',
    start_time: '06:00 PM',
    end_time: '08:00 AM',
    venue: 'Engineering Innovation Center (Building 4)',
    venue_address: 'West Campus Tech Quad',
    coordinator_name: 'Prof. Elena Rostova',
    coordinator_email: 'coordinator@campus.edu',
    coordinator_dept: 'Computer Science & Engineering',
    coordinator_desig: 'Associate Professor & Event Chair',
    ticket_price: 10.00,
    max_capacity: 150,
    available_seats: 138,
    registration_deadline: '2026-09-23 18:00',
    status: 'open',
    is_featured: 1
  },
  {
    id: 4,
    category_id: 4,
    category_name: 'Cultural & Arts',
    title: 'Symphony Under the Stars — Autumn Gala',
    slug: 'symphony-under-the-stars-2026',
    short_description: 'An enchanting evening of classical masterpieces and modern cinematic scores performed by the Philharmonic Ensemble.',
    description: 'Experience an extraordinary open-air musical evening featuring the 70-piece University Philharmonic Orchestra and special guest vocalists. Program includes works by Beethoven, Dvořák, Hans Zimmer, and John Williams.\n\nRefreshments will be served during intermission. Lawn seating and reserved pavilion chairs available.',
    event_date: '2026-10-03',
    start_time: '07:00 PM',
    end_time: '09:30 PM',
    venue: 'University Amphitheater & Botanic Gardens',
    venue_address: 'South Campus Promenade',
    coordinator_name: 'Prof. Elena Rostova',
    coordinator_email: 'coordinator@campus.edu',
    coordinator_dept: 'Computer Science & Engineering',
    coordinator_desig: 'Associate Professor',
    ticket_price: 15.00,
    max_capacity: 300,
    available_seats: 290,
    registration_deadline: '2026-10-01 18:00',
    status: 'open',
    is_featured: 0
  },
  {
    id: 5,
    category_id: 5,
    category_name: 'Athletics & Sports',
    title: 'Inter-University Basketball Championship Finals',
    slug: 'inter-university-basketball-finals',
    short_description: 'Cheer on the varsity team as they battle rivals in the collegiate tournament championship showdown.',
    description: 'The stage is set for the championship final! High-octane collegiate basketball, halftime performances by the university dance crew, student spirit giveaways, and concessions.\n\nDoors open at 5:00 PM. Seating is on a first-come, first-served basis within reserved ticket zones.',
    event_date: '2026-09-15',
    start_time: '06:00 PM',
    end_time: '09:00 PM',
    venue: 'Spartan Arena & Sports Complex',
    venue_address: 'East Campus Athletic District',
    coordinator_name: 'Dr. Marcus Vance',
    coordinator_email: 'marcus.vance@campus.edu',
    coordinator_dept: 'Business Administration & Career Services',
    coordinator_desig: 'Director of Partnerships',
    ticket_price: 5.00,
    max_capacity: 400,
    available_seats: 394,
    registration_deadline: '2026-09-14 18:00',
    status: 'open',
    is_featured: 0
  }
];

const SEED_BOOKINGS = [
  {
    id: 1,
    booking_reference: 'EVT-2026-A19F8',
    user_id: 4,
    user_name: 'Alexander Wright',
    user_email: 'student@campus.edu',
    event_id: 1,
    event_title: 'Annual AI & Cloud Computing Summit 2026',
    event_date: '2026-09-19',
    event_time: '09:00 AM',
    venue: 'Grand University Auditorium',
    quantity: 2,
    unit_price: 25.00,
    total_amount: 50.00,
    status: 'confirmed',
    booked_at: '2026-09-03 14:20',
    attendees: [
      { attendee_name: 'Alexander Wright', attendee_email: 'student@campus.edu', ticket_code: 'TCK-8841-A1', is_checked_in: 0, checked_in_at: null },
      { attendee_name: 'Marcus Brody', attendee_email: 'm.brody@campus.edu', ticket_code: 'TCK-8841-A2', is_checked_in: 0, checked_in_at: null }
    ]
  },
  {
    id: 2,
    booking_reference: 'EVT-2026-B84E2',
    user_id: 4,
    user_name: 'Alexander Wright',
    user_email: 'student@campus.edu',
    event_id: 2,
    event_title: 'National Tech & Engineering Career Fair 2026',
    event_date: '2026-09-12',
    event_time: '10:00 AM',
    venue: 'Campus Exhibition Center — Hall A & B',
    quantity: 1,
    unit_price: 0.00,
    total_amount: 0.00,
    status: 'confirmed',
    booked_at: '2026-09-02 11:15',
    attendees: [
      { attendee_name: 'Alexander Wright', attendee_email: 'student@campus.edu', ticket_code: 'TCK-8841-B1', is_checked_in: 1, checked_in_at: '2026-09-05 10:30 AM' }
    ]
  }
];

class MockDB {
  static init() {
    if (!localStorage.getItem('ceh_events')) {
      localStorage.setItem('ceh_events', JSON.stringify(SEED_EVENTS));
    }
    if (!localStorage.getItem('ceh_categories')) {
      localStorage.setItem('ceh_categories', JSON.stringify(SEED_CATEGORIES));
    }
    if (!localStorage.getItem('ceh_coordinators')) {
      localStorage.setItem('ceh_coordinators', JSON.stringify(SEED_COORDINATORS));
    }
    if (!localStorage.getItem('ceh_students')) {
      localStorage.setItem('ceh_students', JSON.stringify(SEED_STUDENTS));
    }
    if (!localStorage.getItem('ceh_bookings')) {
      localStorage.setItem('ceh_bookings', JSON.stringify(SEED_BOOKINGS));
    }
    if (!localStorage.getItem('ceh_user')) {
      // Default to student user
      localStorage.setItem('ceh_user', JSON.stringify({
        id: 4,
        name: 'Alexander Wright',
        email: 'student@campus.edu',
        role: 'student',
        student_id: 'STU-2024-8841',
        department: 'Computer Science & Engineering'
      }));
    }
  }

  static getEvents() {
    this.init();
    return JSON.parse(localStorage.getItem('ceh_events') || '[]');
  }

  static getEventById(id) {
    const events = this.getEvents();
    return events.find(e => e.id == id) || null;
  }

  static createEvent(data) {
    const events = this.getEvents();
    const maxCap = parseInt(data.max_capacity, 10) || 100;
    const catId = parseInt(data.category_id, 10);
    const categories = this.getCategories();
    const matchedCat = categories.find(c => c.id == catId);
    const catName = matchedCat ? matchedCat.name : (data.category_name || 'Academic Event');

    const newEvent = {
      id: Date.now(),
      category_id: catId,
      category_name: catName,
      title: data.title,
      slug: data.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''),
      short_description: data.short_description || 'Join us for this exciting campus event.',
      description: data.description || data.short_description || 'Full event agenda, faculty keynotes, and student sessions.',
      event_date: data.event_date,
      start_time: data.start_time || '09:00 AM',
      end_time: data.end_time || '05:00 PM',
      venue: data.venue,
      venue_address: data.venue_address || data.venue,
      coordinator_name: data.coordinator_name || 'Prof. Elena Rostova',
      coordinator_email: data.coordinator_email || 'coordinator@campus.edu',
      coordinator_dept: data.coordinator_dept || 'Computer Science & Engineering',
      coordinator_desig: data.coordinator_desig || 'Faculty Event Chair',
      ticket_price: parseFloat(data.ticket_price) || 0.0,
      max_capacity: maxCap,
      available_seats: maxCap,
      registration_deadline: data.registration_deadline || data.event_date + ' 18:00',
      status: 'open',
      is_featured: data.is_featured ? 1 : 0
    };

    events.unshift(newEvent);
    localStorage.setItem('ceh_events', JSON.stringify(events));

    // Update category count
    if (matchedCat) {
      matchedCat.event_count = (matchedCat.event_count || 0) + 1;
      localStorage.setItem('ceh_categories', JSON.stringify(categories));
    }

    // Update coordinator count
    const coords = this.getCoordinators();
    const coord = coords.find(c => c.name === data.coordinator_name || c.email === data.coordinator_email);
    if (coord) {
      coord.assigned_events = (coord.assigned_events || 0) + 1;
      localStorage.setItem('ceh_coordinators', JSON.stringify(coords));
    }

    return newEvent;
  }

  static deleteEvent(id) {
    let events = this.getEvents();
    events = events.filter(e => e.id != id);
    localStorage.setItem('ceh_events', JSON.stringify(events));
  }

  static toggleEventStatus(id) {
    const events = this.getEvents();
    const event = events.find(e => e.id == id);
    if (!event) throw new Error('Event not found');
    event.status = event.status === 'open' ? 'closed' : 'open';
    localStorage.setItem('ceh_events', JSON.stringify(events));
    return event;
  }

  static getCategories() {
    this.init();
    return JSON.parse(localStorage.getItem('ceh_categories') || '[]');
  }

  static getCoordinators() {
    this.init();
    return JSON.parse(localStorage.getItem('ceh_coordinators') || '[]');
  }

  static createCoordinator(data) {
    const coords = this.getCoordinators();
    const existing = coords.find(c => c.email.toLowerCase() === data.email.toLowerCase());
    if (existing) {
      throw new Error('A coordinator with this email already exists.');
    }
    const newCoord = {
      id: Date.now(),
      name: data.name,
      email: data.email,
      department: data.department,
      designation: data.designation,
      phone: data.phone || '+1 (555) 000-0000',
      status: 'active',
      assigned_events: 0
    };
    coords.push(newCoord);
    localStorage.setItem('ceh_coordinators', JSON.stringify(coords));
    return newCoord;
  }

  static toggleCoordinatorStatus(id) {
    const coords = this.getCoordinators();
    const coord = coords.find(c => c.id == id);
    if (!coord) throw new Error('Coordinator not found');
    coord.status = coord.status === 'active' ? 'suspended' : 'active';
    localStorage.setItem('ceh_coordinators', JSON.stringify(coords));
    return coord;
  }

  static deleteCoordinator(id) {
    let coords = this.getCoordinators();
    coords = coords.filter(c => c.id != id);
    localStorage.setItem('ceh_coordinators', JSON.stringify(coords));
  }

  static getStudents() {
    this.init();
    return JSON.parse(localStorage.getItem('ceh_students') || '[]');
  }

  static getBookings() {
    this.init();
    return JSON.parse(localStorage.getItem('ceh_bookings') || '[]');
  }

  static getBookingByRef(ref) {
    const bookings = this.getBookings();
    return bookings.find(b => b.booking_reference === ref) || null;
  }

  static createBooking(data) {
    const bookings = this.getBookings();
    const events = this.getEvents();
    const event = events.find(e => e.id == data.event_id);

    if (!event) throw new Error('Event not found');
    if (event.available_seats < data.quantity) throw new Error('Not enough seats available');

    // Decrement available seats
    event.available_seats -= data.quantity;
    if (event.available_seats <= 0) event.status = 'full';
    localStorage.setItem('ceh_events', JSON.stringify(events));

    const ref = 'EVT-2026-' + Math.random().toString(36).substring(2, 7).toUpperCase();
    const newBooking = {
      id: Date.now(),
      booking_reference: ref,
      user_id: data.user.id,
      user_name: data.user.name,
      user_email: data.user.email,
      event_id: event.id,
      event_title: event.title,
      event_date: event.event_date,
      event_time: event.start_time,
      venue: event.venue,
      quantity: data.quantity,
      unit_price: event.ticket_price,
      total_amount: event.ticket_price * data.quantity,
      status: 'confirmed',
      booked_at: new Date().toISOString().replace('T', ' ').substring(0, 16),
      attendees: data.attendees.map(att => ({
        attendee_name: att.name,
        attendee_email: att.email,
        ticket_code: 'TCK-' + Math.random().toString(36).substring(2, 6).toUpperCase() + '-' + Math.random().toString(36).substring(2, 4).toUpperCase(),
        is_checked_in: 0,
        checked_in_at: null
      }))
    };

    bookings.unshift(newBooking);
    localStorage.setItem('ceh_bookings', JSON.stringify(bookings));
    return newBooking;
  }

  static cancelBooking(ref, reason) {
    const bookings = this.getBookings();
    const booking = bookings.find(b => b.booking_reference === ref);
    if (!booking) throw new Error('Booking not found');
    
    booking.status = 'cancelled';
    booking.cancellation_reason = reason || 'Requested by student';
    
    // Restore seats
    const events = this.getEvents();
    const event = events.find(e => e.id == booking.event_id);
    if (event) {
      event.available_seats += booking.quantity;
      if (event.status === 'full') event.status = 'open';
      localStorage.setItem('ceh_events', JSON.stringify(events));
    }

    localStorage.setItem('ceh_bookings', JSON.stringify(bookings));
    return booking;
  }

  static getCurrentUser() {
    this.init();
    const userStr = localStorage.getItem('ceh_user');
    return userStr ? JSON.parse(userStr) : null;
  }

  static setCurrentUser(user) {
    localStorage.setItem('ceh_user', JSON.stringify(user));
  }

  static logout() {
    localStorage.removeItem('ceh_user');
    window.location.href = 'login.html';
  }

  static resetDatabase() {
    localStorage.clear();
    MockDB.init();
    alert('Mock Database restored to default seed state.');
    window.location.reload();
  }

  /**
   * Enforces Role-Based Access Control on HTML pages.
   */
  static requireRole(allowedRoles) {
    const user = this.getCurrentUser();

    // 1. Not logged in
    if (!user) {
      this.renderAccessDenied(
        'Authentication Required',
        'You must sign in to view this protected resource.',
        'login.html',
        'Sign In'
      );
      throw new Error('Access Denied: Unauthenticated');
    }

    // 2. Check if user is a suspended coordinator
    if (user.role === 'coordinator') {
      const coords = this.getCoordinators();
      const currentCoord = coords.find(c => c.email.toLowerCase() === user.email.toLowerCase());
      if (currentCoord && currentCoord.status === 'suspended') {
        this.renderAccessDenied(
          'Coordinator Access Suspended',
          'Your Faculty Coordinator access privileges have been suspended by the System Administrator. Please contact the administration office.',
          'login.html',
          'Sign In with Another Account'
        );
        throw new Error('Access Denied: Coordinator Suspended by Admin');
      }
    }

    // 3. Role mismatch
    const roles = Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles];
    if (!roles.includes(user.role)) {
      let roleDesc = roles.map(r => r.toUpperCase()).join(' or ');
      this.renderAccessDenied(
        '403 — Access Denied (Restricted Area)',
        `You are currently logged in as a <strong>${user.role.toUpperCase()}</strong> (${user.name}).<br>This console requires <strong>${roleDesc}</strong> privileges. Regular users and students are strictly blocked from accessing administrator and coordinator controls.`,
        user.role === 'student' ? 'student-dashboard.html' : 'index.html',
        user.role === 'student' ? 'Return to Student Hub' : 'Return to Home'
      );
      throw new Error(`Access Denied: Role '${user.role}' not permitted`);
    }
  }

  static renderAccessDenied(title, message, returnUrl, returnText) {
    document.body.innerHTML = `
      <div style="min-height:100vh; background:#0f172a; display:flex; align-items:center; justify-content:center; padding:20px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
        <div style="max-width:540px; width:100%; background:#ffffff; border-radius:12px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); overflow:hidden; border:1px solid #e2e8f0; text-align:center;">
          <div style="background:#fee2e2; padding:24px 20px; border-bottom:1px solid #fecaca;">
            <div style="width:56px; height:56px; background:#ef4444; color:#fff; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:12px; box-shadow:0 4px 12px rgba(239,68,68,0.35);">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/><path d="M12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <h2 style="font-size:1.4rem; font-weight:800; color:#991b1b; margin:0 0 6px 0;">${title}</h2>
            <div style="display:inline-block; padding:3px 10px; background:#991b1b; color:#fff; font-size:0.75rem; font-weight:700; border-radius:999px; letter-spacing:0.05em; text-transform:uppercase;">
              Security Barrier (RBAC Guard)
            </div>
          </div>
          <div style="padding:28px 24px;">
            <p style="font-size:0.95rem; color:#475569; line-height:1.6; margin-bottom:24px;">
              ${message}
            </p>
            <div style="display:flex; flex-direction:column; gap:10px;">
              <a href="${returnUrl}" style="display:inline-block; padding:12px 20px; background:#1e40af; color:#ffffff; font-weight:600; text-decoration:none; border-radius:6px; font-size:0.9rem; transition:background 0.2s;">
                &larr; ${returnText}
              </a>
              <a href="login.html" style="display:inline-block; padding:10px 20px; background:#f1f5f9; color:#334155; font-weight:600; text-decoration:none; border-radius:6px; font-size:0.85rem; border:1px solid #cbd5e1;">
                Switch User / Login As Different Role
              </a>
            </div>
          </div>
          <div style="background:#f8fafc; padding:12px; border-top:1px solid #e2e8f0; font-size:0.75rem; color:#64748b;">
            CampusEvent Hub Access Enforcement System &bull; Active Role Check
          </div>
        </div>
      </div>
    `;
  }

  /**
   * Dynamically renders role-appropriate header navigation
   */
  static renderHeaderNav(activePage = '') {
    const user = this.getCurrentUser();
    const navList = document.querySelector('.main-nav .nav-list');
    const headerActions = document.querySelector('.header-actions');

    if (navList) {
      let navHtml = `
        <li><a href="index.html" class="nav-link ${activePage === 'home' ? 'active' : ''}">Home</a></li>
        <li><a href="events.html" class="nav-link ${activePage === 'events' ? 'active' : ''}">Catalog</a></li>
        <li><a href="verify-ticket.html" class="nav-link ${activePage === 'verify' ? 'active' : ''}">Verify Pass</a></li>
      `;

      if (user) {
        if (user.role === 'student') {
          navHtml += `<li><a href="student-dashboard.html" class="nav-link nav-link-portal ${activePage === 'student' ? 'active' : ''}">My Passes (Student)</a></li>`;
        } else if (user.role === 'coordinator') {
          navHtml += `<li><a href="coordinator-dashboard.html" class="nav-link nav-link-portal ${activePage === 'coordinator' ? 'active' : ''}">Coordinator Console</a></li>`;
        } else if (user.role === 'admin') {
          navHtml += `<li><a href="admin-dashboard.html" class="nav-link nav-link-portal ${activePage === 'admin' ? 'active' : ''}">Admin Console</a></li>`;
        }
      }

      navList.innerHTML = navHtml;
    }

    if (headerActions) {
      if (user) {
        let badgeColor = user.role === 'admin' ? '#ef4444' : (user.role === 'coordinator' ? '#d97706' : '#2563eb');
        headerActions.innerHTML = `
          <div style="display:flex; align-items:center; gap:12px;">
            <div style="display:flex; flex-direction:column; text-align:right;">
              <span style="font-size:0.85rem; font-weight:700; color:var(--text-primary);">${user.name}</span>
              <span style="font-size:0.72rem; font-weight:700; text-transform:uppercase; color:${badgeColor};">${user.role}</span>
            </div>
            <button onclick="MockDB.logout()" class="btn btn-outline-secondary btn-sm" style="padding:5px 10px; font-size:0.78rem;">
              Sign Out
            </button>
          </div>
        `;
      } else {
        headerActions.innerHTML = `
          <a href="login.html" class="btn btn-outline-primary btn-sm">Sign In</a>
          <a href="register.html" class="btn btn-primary btn-sm">Student Register</a>
        `;
      }
    }

    // Insert Floating Quick Demo Toolbar on all pages for extreme user-friendliness
    this.injectQuickDemoHelper();
  }

  static injectQuickDemoHelper() {
    if (document.getElementById('demoQuickHelper')) return;

    const user = this.getCurrentUser();
    const helper = document.createElement('div');
    helper.id = 'demoQuickHelper';
    helper.innerHTML = `
      <div style="position:fixed; bottom:16px; right:16px; z-index:9999; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
        <button id="quickHelperToggle" onclick="document.getElementById('quickHelperPanel').classList.toggle('open')" style="background:#0f172a; color:#fff; border:1px solid #334155; padding:8px 14px; border-radius:999px; font-size:0.8rem; font-weight:700; box-shadow:0 10px 25px -5px rgba(0,0,0,0.3); cursor:pointer; display:flex; align-items:center; gap:8px; transition:transform 0.15s ease;">
          <span style="width:8px; height:8px; border-radius:50%; background:${user ? '#22c55e' : '#f59e0b'};"></span>
          Quick Role & Demo Switcher
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
        </button>

        <div id="quickHelperPanel" style="display:none; position:absolute; bottom:45px; right:0; width:300px; background:#ffffff; border-radius:10px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.3); border:1px solid #e2e8f0; overflow:hidden;">
          <div style="background:#f8fafc; padding:10px 14px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <strong style="font-size:0.8rem; color:#0f172a;">Fast Demo Switcher</strong>
            <span style="font-size:0.7rem; color:#64748b;">Current: <b>${user ? user.role.toUpperCase() : 'GUEST'}</b></span>
          </div>
          <div style="padding:12px;">
            <div style="font-size:0.72rem; text-transform:uppercase; font-weight:700; color:#64748b; margin-bottom:6px;">1-Click Role Switch:</div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:6px; margin-bottom:12px;">
              <button onclick="MockDB.quickSwitchRole('admin')" style="padding:6px 4px; background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; border-radius:4px; font-size:0.75rem; font-weight:700; cursor:pointer;">Admin</button>
              <button onclick="MockDB.quickSwitchRole('coordinator')" style="padding:6px 4px; background:#fffbeb; color:#92400e; border:1px solid #fde68a; border-radius:4px; font-size:0.75rem; font-weight:700; cursor:pointer;">Coord</button>
              <button onclick="MockDB.quickSwitchRole('student')" style="padding:6px 4px; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; border-radius:4px; font-size:0.75rem; font-weight:700; cursor:pointer;">Student</button>
            </div>

            <div style="font-size:0.72rem; text-transform:uppercase; font-weight:700; color:#64748b; margin-bottom:6px;">Quick Navigate:</div>
            <div style="display:flex; flex-direction:column; gap:4px; font-size:0.8rem;">
              <a href="admin-dashboard.html" style="color:#1e40af; text-decoration:none; padding:4px 6px; border-radius:4px; background:#f8fafc;">📊 Admin Console & Event Creator</a>
              <a href="student-dashboard.html" style="color:#166534; text-decoration:none; padding:4px 6px; border-radius:4px; background:#f8fafc;">🎟️ Student Hub (My Passes)</a>
              <a href="coordinator-dashboard.html" style="color:#92400e; text-decoration:none; padding:4px 6px; border-radius:4px; background:#f8fafc;">📋 Coordinator Desk</a>
              <a href="events.html" style="color:#475569; text-decoration:none; padding:4px 6px; border-radius:4px; background:#f8fafc;">🔍 Event Catalog & Booking</a>
            </div>

            <div style="margin-top:10px; padding-top:8px; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
              <button onclick="MockDB.resetDatabase()" style="border:none; background:none; color:#ef4444; font-size:0.72rem; cursor:pointer; font-weight:600;">Reset Demo Data</button>
              <a href="index.html" style="font-size:0.72rem; color:#64748b; text-decoration:none;">Home &rarr;</a>
            </div>
          </div>
        </div>
      </div>
      <style>
        #quickHelperPanel.open { display: block !important; }
        #quickHelperToggle:hover { transform: translateY(-2px); }
      </style>
    `;
    document.body.appendChild(helper);
  }

  static quickSwitchRole(role) {
    if (role === 'admin') {
      this.setCurrentUser({ id: 1, name: 'Dr. Arthur Pendelton', email: 'admin@campus.edu', role: 'admin' });
      window.location.href = 'admin-dashboard.html';
    } else if (role === 'coordinator') {
      this.setCurrentUser({ id: 2, name: 'Prof. Elena Rostova', email: 'coordinator@campus.edu', role: 'coordinator', department: 'Computer Science & Engineering' });
      window.location.href = 'coordinator-dashboard.html';
    } else {
      this.setCurrentUser({ id: 4, name: 'Alexander Wright', email: 'student@campus.edu', role: 'student', student_id: 'STU-2024-8841', department: 'Computer Science & Engineering' });
      window.location.href = 'student-dashboard.html';
    }
  }
}

// Auto init on script load
MockDB.init();
