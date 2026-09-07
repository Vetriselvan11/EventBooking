/**
 * CampusEvent Hub — Client-Side Mock Database Engine (mock-db.js)
 * Enterprise LocalStorage Store with Real-Time Reactive State, Full Live Admin CRUD & Toast Notifications
 */

const SEED_CATEGORIES = [
  { id: 1, name: 'Technology & Coding', slug: 'technology-coding', description: 'Hackathons, cloud summits, and AI bootcamps', icon: 'terminal', event_count: 2 },
  { id: 2, name: 'Academic Conferences', slug: 'academic-conferences', description: 'Research symposiums, keynote lectures, and seminars', icon: 'book-open', event_count: 1 },
  { id: 3, name: 'Career & Industry Networking', slug: 'career-networking', description: 'Job fairs, corporate roundtables, and resume clinics', icon: 'briefcase', event_count: 2 },
  { id: 4, name: 'Cultural & Arts', slug: 'cultural-arts', description: 'Musical galas, theatrical plays, and gallery exhibits', icon: 'music', event_count: 2 },
  { id: 5, name: 'Athletics & Sports', slug: 'athletics-sports', description: 'Inter-collegiate championships and athletic meets', icon: 'trophy', event_count: 1 }
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
      // Default guest / student state
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

  /* =========================================================================
     EVENTS CRUD
     ========================================================================= */
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
      status: data.status || 'open',
      is_featured: data.is_featured ? 1 : 0
    };

    events.unshift(newEvent);
    localStorage.setItem('ceh_events', JSON.stringify(events));
    this.recalculateCategoryAndCoordCounts();
    return newEvent;
  }

  static updateEvent(id, data) {
    const events = this.getEvents();
    const idx = events.findIndex(e => e.id == id);
    if (idx === -1) throw new Error('Event not found');

    const prev = events[idx];
    const catId = parseInt(data.category_id !== undefined ? data.category_id : prev.category_id, 10);
    const categories = this.getCategories();
    const matchedCat = categories.find(c => c.id == catId);
    const catName = matchedCat ? matchedCat.name : prev.category_name;

    const newMax = data.max_capacity !== undefined ? parseInt(data.max_capacity, 10) : prev.max_capacity;
    const bookedCount = prev.max_capacity - prev.available_seats;
    const newAvail = Math.max(0, newMax - bookedCount);

    events[idx] = {
      ...prev,
      ...data,
      category_id: catId,
      category_name: catName,
      max_capacity: newMax,
      available_seats: newAvail,
      ticket_price: data.ticket_price !== undefined ? parseFloat(data.ticket_price) : prev.ticket_price,
      is_featured: data.is_featured !== undefined ? (data.is_featured ? 1 : 0) : prev.is_featured
    };

    localStorage.setItem('ceh_events', JSON.stringify(events));
    this.recalculateCategoryAndCoordCounts();
    return events[idx];
  }

  static deleteEvent(id) {
    let events = this.getEvents();
    events = events.filter(e => e.id != id);
    localStorage.setItem('ceh_events', JSON.stringify(events));
    this.recalculateCategoryAndCoordCounts();
  }

  static toggleEventStatus(id) {
    const events = this.getEvents();
    const event = events.find(e => e.id == id);
    if (!event) throw new Error('Event not found');
    event.status = event.status === 'open' ? 'closed' : 'open';
    localStorage.setItem('ceh_events', JSON.stringify(events));
    return event;
  }

  static toggleEventFeatured(id) {
    const events = this.getEvents();
    const event = events.find(e => e.id == id);
    if (!event) throw new Error('Event not found');
    event.is_featured = event.is_featured ? 0 : 1;
    localStorage.setItem('ceh_events', JSON.stringify(events));
    return event;
  }

  /* =========================================================================
     CATEGORIES CRUD
     ========================================================================= */
  static getCategories() {
    this.init();
    return JSON.parse(localStorage.getItem('ceh_categories') || '[]');
  }

  static createCategory(data) {
    const categories = this.getCategories();
    const slug = data.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    const newCat = {
      id: Date.now(),
      name: data.name,
      slug: slug,
      description: data.description || 'Academic events and student workshops',
      icon: data.icon || 'tag',
      event_count: 0
    };
    categories.push(newCat);
    localStorage.setItem('ceh_categories', JSON.stringify(categories));
    return newCat;
  }

  static updateCategory(id, data) {
    const categories = this.getCategories();
    const cat = categories.find(c => c.id == id);
    if (!cat) throw new Error('Category not found');
    if (data.name) {
      cat.name = data.name;
      cat.slug = data.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }
    if (data.description !== undefined) cat.description = data.description;
    if (data.icon) cat.icon = data.icon;
    localStorage.setItem('ceh_categories', JSON.stringify(categories));
    return cat;
  }

  static deleteCategory(id) {
    let categories = this.getCategories();
    categories = categories.filter(c => c.id != id);
    localStorage.setItem('ceh_categories', JSON.stringify(categories));
  }

  /* =========================================================================
     COORDINATORS CRUD
     ========================================================================= */
  static getCoordinators() {
    this.init();
    return JSON.parse(localStorage.getItem('ceh_coordinators') || '[]');
  }

  static createCoordinator(data) {
    const coords = this.getCoordinators();
    const existing = coords.find(c => c.email.toLowerCase() === data.email.toLowerCase());
    if (existing) {
      throw new Error('A faculty coordinator with this email already exists.');
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

  static updateCoordinator(id, data) {
    const coords = this.getCoordinators();
    const coord = coords.find(c => c.id == id);
    if (!coord) throw new Error('Coordinator not found');
    if (data.name) coord.name = data.name;
    if (data.email) coord.email = data.email;
    if (data.department) coord.department = data.department;
    if (data.designation) coord.designation = data.designation;
    if (data.phone) coord.phone = data.phone;
    if (data.status) coord.status = data.status;
    localStorage.setItem('ceh_coordinators', JSON.stringify(coords));
    return coord;
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

  /* =========================================================================
     STUDENTS CRUD
     ========================================================================= */
  static getStudents() {
    this.init();
    return JSON.parse(localStorage.getItem('ceh_students') || '[]');
  }

  static createStudent(data) {
    const students = this.getStudents();
    const existing = students.find(s => s.email.toLowerCase() === data.email.toLowerCase());
    if (existing) throw new Error('A student with this email is already registered.');
    const newStu = {
      id: Date.now(),
      student_id: 'STU-' + new Date().getFullYear() + '-' + Math.floor(1000 + Math.random() * 9000),
      name: data.name,
      email: data.email,
      department: data.department || 'General Studies',
      year: data.year || '1st Year',
      total_bookings: 0,
      status: 'active'
    };
    students.push(newStu);
    localStorage.setItem('ceh_students', JSON.stringify(students));
    return newStu;
  }

  static deleteStudent(id) {
    let students = this.getStudents();
    students = students.filter(s => s.id != id);
    localStorage.setItem('ceh_students', JSON.stringify(students));
  }

  /* =========================================================================
     BOOKINGS & ORDERS
     ========================================================================= */
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
      user_id: data.user.id || 4,
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

    // Update student booking count
    const students = this.getStudents();
    const student = students.find(s => s.email.toLowerCase() === (data.user.email || '').toLowerCase());
    if (student) {
      student.total_bookings = (student.total_bookings || 0) + 1;
      localStorage.setItem('ceh_students', JSON.stringify(students));
    }

    return newBooking;
  }

  static cancelBooking(ref, reason) {
    const bookings = this.getBookings();
    const booking = bookings.find(b => b.booking_reference === ref);
    if (!booking) throw new Error('Booking not found');
    
    booking.status = 'cancelled';
    booking.cancellation_reason = reason || 'Cancelled by administrator / student';
    
    // Restore seats
    const events = this.getEvents();
    const event = events.find(e => e.id == booking.event_id);
    if (event) {
      event.available_seats += booking.quantity;
      if (event.status === 'full' || event.status === 'closed') event.status = 'open';
      localStorage.setItem('ceh_events', JSON.stringify(events));
    }

    localStorage.setItem('ceh_bookings', JSON.stringify(bookings));
    return booking;
  }

  static deleteBooking(id) {
    let bookings = this.getBookings();
    const booking = bookings.find(b => b.id == id);
    if (booking && booking.status === 'confirmed') {
      const events = this.getEvents();
      const event = events.find(e => e.id == booking.event_id);
      if (event) {
        event.available_seats += booking.quantity;
        if (event.status === 'full') event.status = 'open';
        localStorage.setItem('ceh_events', JSON.stringify(events));
      }
    }
    bookings = bookings.filter(b => b.id != id);
    localStorage.setItem('ceh_bookings', JSON.stringify(bookings));
  }

  /* =========================================================================
     RECALCULATE STATS HELPER
     ========================================================================= */
  static recalculateCategoryAndCoordCounts() {
    const events = this.getEvents();
    const categories = this.getCategories();
    const coords = this.getCoordinators();

    categories.forEach(cat => {
      cat.event_count = events.filter(e => e.category_id == cat.id).length;
    });
    localStorage.setItem('ceh_categories', JSON.stringify(categories));

    coords.forEach(coord => {
      coord.assigned_events = events.filter(e => e.coordinator_name === coord.name || e.coordinator_email === coord.email).length;
    });
    localStorage.setItem('ceh_coordinators', JSON.stringify(coords));
  }

  /* =========================================================================
     AUTH & SESSION
     ========================================================================= */
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
    MockDB.toast('Database successfully restored to default seed state.', 'success');
    setTimeout(() => window.location.reload(), 600);
  }

  /* =========================================================================
     RBAC ENFORCEMENT
     ========================================================================= */
  static requireRole(allowedRoles) {
    const user = this.getCurrentUser();

    if (!user) {
      this.renderAccessDenied(
        'Authentication Required',
        'You must sign in to view this protected console.',
        'login.html',
        'Sign In'
      );
      throw new Error('Access Denied: Unauthenticated');
    }

    if (user.role === 'coordinator') {
      const coords = this.getCoordinators();
      const currentCoord = coords.find(c => c.email.toLowerCase() === user.email.toLowerCase());
      if (currentCoord && currentCoord.status === 'suspended') {
        this.renderAccessDenied(
          'Coordinator Access Suspended',
          'Your Faculty Coordinator access privileges have been suspended by the System Administrator.',
          'login.html',
          'Sign In with Another Account'
        );
        throw new Error('Access Denied: Coordinator Suspended by Admin');
      }
    }

    const roles = Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles];
    if (!roles.includes(user.role)) {
      let roleDesc = roles.map(r => r.toUpperCase()).join(' or ');
      this.renderAccessDenied(
        '403 — Access Denied (Restricted Area)',
        `You are logged in as <strong>${user.role.toUpperCase()}</strong> (${user.name}).<br>This console requires <strong>${roleDesc}</strong> privileges.`,
        user.role === 'student' ? 'student-dashboard.html' : 'index.html',
        user.role === 'student' ? 'Return to Student Hub' : 'Return to Home'
      );
      throw new Error(`Access Denied: Role '${user.role}' not permitted`);
    }
  }

  static renderAccessDenied(title, message, returnUrl, returnText) {
    document.body.innerHTML = `
      <div style="min-height:100vh; background:#0f172a; display:flex; align-items:center; justify-content:center; padding:20px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
        <div style="max-width:520px; width:100%; background:#ffffff; border-radius:12px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); overflow:hidden; border:1px solid #e2e8f0; text-align:center;">
          <div style="background:#fee2e2; padding:24px 20px; border-bottom:1px solid #fecaca;">
            <div style="width:52px; height:52px; background:#ef4444; color:#fff; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:10px;">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/><path d="M12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <h2 style="font-size:1.35rem; font-weight:800; color:#991b1b; margin:0 0 4px 0;">${title}</h2>
          </div>
          <div style="padding:24px;">
            <p style="font-size:0.92rem; color:#475569; line-height:1.6; margin-bottom:20px;">${message}</p>
            <div style="display:flex; flex-direction:column; gap:10px;">
              <a href="${returnUrl}" style="display:inline-block; padding:11px 18px; background:#1e40af; color:#ffffff; font-weight:600; text-decoration:none; border-radius:6px; font-size:0.9rem;">
                &larr; ${returnText}
              </a>
              <a href="login.html" style="display:inline-block; padding:10px 18px; background:#f1f5f9; color:#334155; font-weight:600; text-decoration:none; border-radius:6px; font-size:0.85rem; border:1px solid #cbd5e1;">
                Switch User / Login
              </a>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  static renderHeaderNav(activePage = '') {
    const user = this.getCurrentUser();
    const navList = document.querySelector('.main-nav .nav-list');
    const headerActions = document.querySelector('.header-actions');

    if (navList) {
      let navHtml = `
        <li><a href="index.html" class="nav-link ${activePage === 'home' ? 'active' : ''}">Home</a></li>
        <li><a href="events.html" class="nav-link ${activePage === 'events' ? 'active' : ''}">Discover Events</a></li>
        <li><a href="verify-ticket.html" class="nav-link ${activePage === 'verify' ? 'active' : ''}">Verify Pass</a></li>
      `;

      if (user) {
        if (user.role === 'student') {
          navHtml += `<li><a href="student-dashboard.html" class="nav-link nav-link-portal ${activePage === 'student' ? 'active' : ''}">Student Hub</a></li>`;
        } else if (user.role === 'coordinator') {
          navHtml += `<li><a href="coordinator-dashboard.html" class="nav-link nav-link-portal ${activePage === 'coordinator' ? 'active' : ''}">Coordinator Desk</a></li>`;
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
            <button onclick="MockDB.logout()" class="btn btn-outline-secondary btn-sm" style="padding:5px 12px; font-size:0.8rem;">
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
  }

  /* =========================================================================
     MODERN TOAST NOTIFICATION ENGINE
     ========================================================================= */
  static toast(message, type = 'success', duration = 3200) {
    let container = document.getElementById('cehToastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'cehToastContainer';
      container.style.cssText = `
        position: fixed;
        top: 24px;
        right: 24px;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
        max-width: 380px;
        width: 100%;
      `;
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.style.cssText = `
      background: #0f172a;
      color: #ffffff;
      padding: 14px 18px;
      border-radius: 8px;
      box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3), 0 8px 10px -6px rgba(0,0,0,0.2);
      border-left: 4px solid ${type === 'success' ? '#22c55e' : (type === 'error' ? '#ef4444' : (type === 'warning' ? '#f59e0b' : '#3b82f6'))};
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.88rem;
      font-weight: 500;
      line-height: 1.4;
      pointer-events: auto;
      transform: translateX(120%);
      transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease;
      opacity: 0;
    `;

    const iconMap = {
      success: '✓',
      error: '✕',
      warning: '⚠',
      info: 'ℹ'
    };

    toast.innerHTML = `
      <span style="font-size:1.1rem; font-weight:800; color:${type === 'success' ? '#4ade80' : (type === 'error' ? '#f87171' : (type === 'warning' ? '#fbbf24' : '#60a5fa'))}">
        ${iconMap[type] || 'ℹ'}
      </span>
      <div style="flex:1;">${message}</div>
      <button style="background:none; border:none; color:#94a3b8; font-size:1.2rem; cursor:pointer; padding:0 4px; line-height:1;" onclick="this.parentElement.remove()">&times;</button>
    `;

    container.appendChild(toast);
    requestAnimationFrame(() => {
      toast.style.transform = 'translateX(0)';
      toast.style.opacity = '1';
    });

    setTimeout(() => {
      toast.style.transform = 'translateX(120%)';
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 250);
    }, duration);
  }

  /* =========================================================================
     CUSTOM CONFIRM MODAL
     ========================================================================= */
  static confirmModal({ title = 'Confirm Action', message = 'Are you sure?', confirmText = 'Confirm', confirmClass = 'btn-primary', onConfirm }) {
    let modal = document.getElementById('cehConfirmModal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'cehConfirmModal';
      modal.style.cssText = `
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(4px);
        z-index: 10000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
      `;
      document.body.appendChild(modal);
    }

    modal.innerHTML = `
      <div style="background:#ffffff; border-radius:12px; max-width:440px; width:100%; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); border:1px solid #e2e8f0; overflow:hidden; animation: modalPop 0.2s cubic-bezier(0.16, 1, 0.3, 1);">
        <div style="padding:20px 24px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
          <h3 style="font-size:1.1rem; font-weight:700; color:#0f172a; margin:0;">${title}</h3>
          <button id="cehConfirmClose" style="border:none; background:none; font-size:1.3rem; cursor:pointer; color:#64748b;">&times;</button>
        </div>
        <div style="padding:22px 24px; font-size:0.92rem; color:#475569; line-height:1.5;">
          ${message}
        </div>
        <div style="padding:16px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
          <button id="cehConfirmCancel" class="btn btn-outline-secondary btn-sm" style="padding:8px 16px;">Cancel</button>
          <button id="cehConfirmOk" class="btn ${confirmClass} btn-sm" style="padding:8px 18px; font-weight:700;">${confirmText}</button>
        </div>
      </div>
      <style>
        @keyframes modalPop {
          from { opacity: 0; transform: scale(0.95); }
          to { opacity: 1; transform: scale(1); }
        }
      </style>
    `;

    modal.style.display = 'flex';

    const close = () => { modal.style.display = 'none'; };
    modal.querySelector('#cehConfirmClose').onclick = close;
    modal.querySelector('#cehConfirmCancel').onclick = close;
    modal.querySelector('#cehConfirmOk').onclick = () => {
      close();
      if (typeof onConfirm === 'function') onConfirm();
    };
  }
}

// Auto-initialize on script load
MockDB.init();
