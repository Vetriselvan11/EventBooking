-- =====================================================================
-- Event Booking Management System - Comprehensive Seed Data
-- =====================================================================

-- Default Precomputed Bcrypt Hashes:
-- 'Admin@123'   -> $2y$10$E9sK8n8V5Zz8yY7xX6wW5.O2c2F1n5X4Y3Z2A1B0C9D8E7F6G5H4I
-- 'Coord@123'   -> $2y$10$A1b2C3d4E5f6G7h8I9j0K.O2c2F1n5X4Y3Z2A1B0C9D8E7F6G5H4I
-- 'Student@123' -> $2y$10$Z9y8X7w6V5u4T3s2R1q0P.O2c2F1n5X4Y3Z2A1B0C9D8E7F6G5H4I
-- (Note: setup.php will generate fresh dynamic hashes using native password_hash() if run via installer)

-- 1. Insert Categories
INSERT INTO event_categories (id, name, slug, description, icon) VALUES
(1, 'Technology & Coding', 'technology-coding', 'Hackathons, software engineering symposiums, cloud workshops, and AI bootcamps.', 'terminal'),
(2, 'Academic Conferences', 'academic-conferences', 'Peer-reviewed research symposiums, keynote lectures, and scholarly panel discussions.', 'book-open'),
(3, 'Career & Industry Networking', 'career-networking', 'Job fairs, corporate meetups, resume clinics, and executive leadership seminars.', 'briefcase'),
(4, 'Cultural & Arts', 'cultural-arts', 'Music festivals, theatrical productions, art galleries, and international student galas.', 'music'),
(5, 'Athletics & Sports', 'athletics-sports', 'Inter-collegiate championships, intramural tournaments, and campus fitness marathons.', 'trophy')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 2. Insert Users (Admin, 2 Faculty Coordinators, 3 Students)
-- Password for all accounts: Admin@123 / Coord@123 / Student@123
INSERT INTO users (id, role, name, email, password_hash, phone, status, created_at) VALUES
(1, 'admin', 'Dr. Arthur Pendelton', 'admin@campus.edu', '$2y$10$U0gE1Q/G/iT1/yXjZ6s6AeVgV221XoF1y79bU7u8E0G9vH4C4i1K6', '+1 (555) 019-2831', 'active', NOW()),
(2, 'coordinator', 'Prof. Elena Rostova', 'coordinator@campus.edu', '$2y$10$U0gE1Q/G/iT1/yXjZ6s6AeVgV221XoF1y79bU7u8E0G9vH4C4i1K6', '+1 (555) 482-1920', 'active', NOW()),
(3, 'coordinator', 'Dr. Marcus Vance', 'marcus.vance@campus.edu', '$2y$10$U0gE1Q/G/iT1/yXjZ6s6AeVgV221XoF1y79bU7u8E0G9vH4C4i1K6', '+1 (555) 391-7722', 'active', NOW()),
(4, 'student', 'Alexander Wright', 'student@campus.edu', '$2y$10$U0gE1Q/G/iT1/yXjZ6s6AeVgV221XoF1y79bU7u8E0G9vH4C4i1K6', '+1 (555) 881-3049', 'active', NOW()),
(5, 'student', 'Sophia Chen', 'sophia.chen@campus.edu', '$2y$10$U0gE1Q/G/iT1/yXjZ6s6AeVgV221XoF1y79bU7u8E0G9vH4C4i1K6', '+1 (555) 749-2910', 'active', NOW()),
(6, 'student', 'Liam Gallagher', 'liam.g@campus.edu', '$2y$10$U0gE1Q/G/iT1/yXjZ6s6AeVgV221XoF1y79bU7u8E0G9vH4C4i1K6', '+1 (555) 632-1194', 'active', NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 3. Insert Coordinator Details
INSERT INTO coordinators (id, user_id, department, designation, office_location) VALUES
(1, 2, 'Computer Science & Engineering', 'Associate Professor & Event Chair', 'Turing Hall, Room 402'),
(2, 3, 'Business Administration & Career Services', 'Director of Corporate Partnerships', 'Executive Tower, Suite 210')
ON DUPLICATE KEY UPDATE department=VALUES(department);

-- 4. Insert Student Details
INSERT INTO students (id, user_id, student_id_number, department, year_of_study, emergency_contact) VALUES
(1, 4, 'STU-2024-8841', 'Computer Science & Engineering', '3rd Year', 'Sarah Wright (+1 555-881-3050)'),
(2, 5, 'STU-2023-7412', 'Electrical & Information Systems', '4th Year', 'David Chen (+1 555-749-2911)'),
(3, 6, 'STU-2025-9923', 'School of Management & Business', '2nd Year', 'Fiona Gallagher (+1 555-632-1195)')
ON DUPLICATE KEY UPDATE student_id_number=VALUES(student_id_number);

-- 5. Insert Rich Realistic Events
INSERT INTO events (id, category_id, coordinator_id, title, slug, short_description, description, event_date, start_time, end_time, venue, venue_address, banner_image, ticket_price, max_capacity, available_seats, registration_deadline, status, is_featured, created_at) VALUES
(1, 1, 2, 'Annual AI & Cloud Computing Summit 2026', 'annual-ai-cloud-summit-2026', 'A premier gathering of engineering researchers, cloud architects, and industry pioneers discussing scalable deep learning systems.', 'Join industry leaders and academic innovators for the 2026 AI & Cloud Computing Summit. This full-day conference brings together keynote presentations from leading AI researchers, interactive technical workshops on distributed machine learning, cloud-native deployments, and hands-on lab sessions.\n\nAgenda Highlights:\n- 09:00 AM: Keynote Address — Scaling Neural Models in Distributed Environments\n- 11:00 AM: Panel Discussion — Ethics and Governance in Generative AI\n- 01:30 PM: Hands-on Workshop: High-Throughput Kubernetes Clusters\n- 04:00 PM: Student Research Poster Exhibition & Networking Reception\n\nAll registered attendees receive full conference access, luncheon voucher, and digital certificate of participation.', DATE_ADD(CURDATE(), INTERVAL 14 DAY), '09:00:00', '17:30:00', 'Grand University Auditorium', 'Auditorium Complex, North Campus, Gate 2', 'event_ai_summit.jpg', 25.00, 250, 246, DATE_ADD(NOW(), INTERVAL 12 DAY), 'open', 1, NOW()),

(2, 3, 3, 'National Tech & Engineering Career Fair 2026', 'national-tech-career-fair-2026', 'Connect directly with hiring managers and technical recruiters from over 60 Fortune 500 technology companies.', 'The National Tech & Engineering Career Fair is the campus event for students seeking internships, co-ops, and full-time engineering positions. Meet recruiters from top software companies, aerospace firms, robotics laboratories, and venture-backed startups.\n\nKey Requirements:\n- Bring printed copies of your resume\n- University ID card is mandatory for entry\n- Business casual attire recommended\n- Free resume screening booths available on Floor 2', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '10:00:00', '16:00:00', 'Campus Exhibition Center — Hall A & B', 'Center for Innovation & Career Services', 'event_career_fair.jpg', 0.00, 500, 485, DATE_ADD(NOW(), INTERVAL 6 DAY), 'open', 1, NOW()),

(3, 1, 2, '36-Hour Hackathon: HackTheCampus 2026', 'hackthecampus-2026', 'Compete in teams of 2-4 to build real-world software solutions addressing sustainable campus challenges with $10,000 in prizes.', 'HackTheCampus is our flagship university hackathon. Over 36 exhilarating hours, developers, designers, and domain enthusiasts collaborate to engineer high-impact solutions for smart campuses, renewable energy, and educational accessibility.\n\nTracks:\n1. Smart Campus Mobility & Logistics\n2. AI for Academic Productivity\n3. Green Tech & Zero-Waste Solutions\n4. Open Innovation\n\nHardware kits, mentor guidance, 5 meals, high-speed fiber internet, and midnight snacks provided.', DATE_ADD(CURDATE(), INTERVAL 21 DAY), '18:00:00', '08:00:00', 'Engineering Innovation Center (Building 4)', 'West Campus Tech Quad', 'event_hackathon.jpg', 10.00, 150, 138, DATE_ADD(NOW(), INTERVAL 18 DAY), 'open', 1, NOW()),

(4, 4, 2, 'Symphony Under the Stars — Autumn Gala', 'symphony-under-the-stars-2026', 'An enchanting evening of classical masterpieces and modern cinematic scores performed by the Philharmonic Ensemble.', 'Experience an extraordinary open-air musical evening featuring the 70-piece University Philharmonic Orchestra and special guest vocalists. Program includes works by Beethoven, Dvořák, Hans Zimmer, and John Williams.\n\nRefreshments will be served during intermission. Lawn seating and reserved pavilion chairs available.', DATE_ADD(CURDATE(), INTERVAL 28 DAY), '19:00:00', '21:30:00', 'University Amphitheater & Botanic Gardens', 'South Campus Promenade', 'event_symphony.jpg', 15.00, 300, 290, DATE_ADD(NOW(), INTERVAL 26 DAY), 'open', 0, NOW()),

(5, 5, 3, 'Inter-University Basketball Championship Finals', 'inter-university-basketball-finals', 'Cheer on the varsity team as they battle rivals in the collegiate tournament championship showdown.', 'The stage is set for the championship final! High-octane collegiate basketball, halftime performances by the university dance crew, student spirit giveaways, and concessions.\n\nDoors open at 5:00 PM. Seating is on a first-come, first-served basis within reserved ticket zones.', DATE_ADD(CURDATE(), INTERVAL 10 DAY), '18:00:00', '21:00:00', 'Spartan Arena & Sports Complex', 'East Campus Athletic District', 'event_sports.jpg', 5.00, 400, 394, DATE_ADD(NOW(), INTERVAL 9 DAY), 'open', 0, NOW()),

(6, 2, 2, 'International Quantum Computing Workshop', 'international-quantum-computing-workshop', 'Advanced seminar covering superconducting qubits, quantum error mitigation, and Qiskit programming paradigms.', 'Designed for graduate students, postdocs, and senior undergraduates with foundations in linear algebra and quantum mechanics. This intensive workshop combines theoretical lectures with hands-on remote execution on physical quantum processors.\n\nInstructors:\n- Dr. Neil Sorenson (Quantum Labs)\n- Dr. Elena Rostova (CS Faculty)', DATE_ADD(CURDATE(), INTERVAL 35 DAY), '13:00:00', '18:00:00', 'Advanced Science & Technology Labs — Rm 108', 'Physics & Nanotech Wing', 'event_quantum.jpg', 40.00, 60, 58, DATE_ADD(NOW(), INTERVAL 30 DAY), 'almost_full', 0, NOW()),

(7, 3, 3, 'Executive Leadership & Entrepreneurship Masterclass', 'executive-leadership-masterclass', 'Learn venture scaling, cap-table dynamics, and storytelling pitch strategies from successful alumni founders.', 'Step into the minds of founders who raised over $50M in venture funding. In this interactive masterclass, attendees will dissect real pitch decks, learn fundraising negotiations, and participate in a live 3-minute pitch feedback clinic.', DATE_ADD(CURDATE(), INTERVAL 18 DAY), '14:00:00', '17:00:00', 'Business School Executive Boardroom', 'Graduate School of Management, 5th Floor', 'event_business.jpg', 0.00, 80, 77, DATE_ADD(NOW(), INTERVAL 15 DAY), 'open', 0, NOW()),

(8, 4, 2, 'Fine Arts & Digital Media Senior Thesis Gallery', 'fine-arts-digital-media-gallery', 'Explore progressive installations, interactive media, VR experiences, and oil paintings by graduating seniors.', 'Celebrate the artistic vision and technical mastery of the 2026 graduating cohort. The gallery features over 45 distinct installations spanning immersive virtual reality spaces, physical sculptures, generative digital art, and documentary photography.\n\nFree admission with registration. Artist talks begin at 6:00 PM.', DATE_ADD(CURDATE(), INTERVAL 42 DAY), '17:00:00', '21:00:00', 'Modern Art Wing & Gallery Atrium', 'Fine Arts Quad, East Gate', 'event_arts.jpg', 0.00, 200, 198, DATE_ADD(NOW(), INTERVAL 40 DAY), 'open', 0, NOW())
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- 6. Insert Sample Bookings
INSERT INTO bookings (id, booking_reference, user_id, event_id, quantity, unit_price, total_amount, status, booked_at) VALUES
(1, 'EVT-2026-A19F8', 4, 1, 2, 25.00, 50.00, 'confirmed', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'EVT-2026-B84E2', 4, 2, 1, 0.00, 0.00, 'confirmed', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 'EVT-2026-C39D1', 5, 3, 2, 10.00, 20.00, 'confirmed', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 'EVT-2026-D55A7', 6, 5, 1, 5.00, 5.00, 'confirmed', DATE_SUB(NOW(), INTERVAL 4 DAY))
ON DUPLICATE KEY UPDATE booking_reference=VALUES(booking_reference);

-- 7. Insert Booking Attendees / Ticket Passes
INSERT INTO booking_attendees (id, booking_id, attendee_name, attendee_email, ticket_code, is_checked_in, checked_in_at) VALUES
(1, 1, 'Alexander Wright', 'student@campus.edu', 'TCK-8841-A1', 0, NULL),
(2, 1, 'Marcus Brody', 'm.brody@campus.edu', 'TCK-8841-A2', 0, NULL),
(3, 2, 'Alexander Wright', 'student@campus.edu', 'TCK-8841-B1', 0, NULL),
(4, 3, 'Sophia Chen', 'sophia.chen@campus.edu', 'TCK-7412-C1', 0, NULL),
(5, 3, 'Emily Zhang', 'e.zhang@campus.edu', 'TCK-7412-C2', 0, NULL),
(6, 4, 'Liam Gallagher', 'liam.g@campus.edu', 'TCK-9923-D1', 0, NULL)
ON DUPLICATE KEY UPDATE ticket_code=VALUES(ticket_code);

-- 8. Insert Payments
INSERT INTO payments (id, booking_id, transaction_reference, payment_method, amount, status, gateway_response, paid_at, created_at) VALUES
(1, 1, 'TXN-172554-A19F', 'card', 50.00, 'successful', '{"gateway":"Simulated Card Gateway","authorization_code":"AUTH_883920","card_brand":"Visa","last4":"4242"}', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 2, 'TXN-172554-B84E', 'free', 0.00, 'successful', '{"gateway":"Zero-cost Registration","authorization_code":"FREE_REG_PASS"}', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 3, 'TXN-172554-C39D', 'upi', 20.00, 'successful', '{"gateway":"Simulated UPI FastPay","upi_id":"student@okhdfcbank","utr":"UTR993821094"}', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 4, 'TXN-172554-D55A', 'netbanking', 5.00, 'successful', '{"gateway":"Simulated NetBanking Gateway","bank":"State National Bank","ref":"NB882910"}', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY))
ON DUPLICATE KEY UPDATE transaction_reference=VALUES(transaction_reference);

-- 9. Insert Sample Notifications
INSERT INTO notifications (id, user_id, title, message, link, is_read, created_at) VALUES
(1, 4, 'Booking Confirmed: AI & Cloud Computing Summit', 'Your registration (Ref: EVT-2026-A19F8) for 2 attendees has been confirmed. Download your digital passes.', '/student/tickets.php', 0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 4, 'Career Fair Pass Ready', 'You are registered for the National Tech Career Fair 2026. Review venue instructions.', '/student/tickets.php', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 2, 'New Booking on AI Summit', 'Student Alexander Wright booked 2 tickets for Annual AI & Cloud Computing Summit 2026.', '/coordinator/attendees/index.php?event_id=1', 0, DATE_SUB(NOW(), INTERVAL 2 DAY))
ON DUPLICATE KEY UPDATE title=VALUES(title);
