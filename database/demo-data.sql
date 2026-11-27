-- ============================================================
-- SDMS demo data: official CvSU forms from storage/uploads.
-- Safe to re-import. Run AFTER schema.sql + seed.sql.
-- No `USE` statement: select your database in phpMyAdmin first.
-- Demo users (admin/staff/viewer) are kept from seed.sql.
-- ============================================================

-- 1) Remove smoke-test documents from earlier testing
DELETE dal FROM document_access_logs dal
  JOIN documents d ON dal.document_id = d.document_id
  WHERE d.title LIKE 'Smoke %';
DELETE FROM documents WHERE title LIKE 'Smoke %';

-- 1b) Remove previously imported demo copies (makes this file idempotent)
DELETE dal FROM document_access_logs dal
  JOIN documents d ON dal.document_id = d.document_id
  WHERE d.title IN (
    'Laboratory Exercise 3 - SQL Basics and Prepared Statements',
    'Chapter 1 - The Problem and Its Background (Draft)',
    'ITEC 85 Project Proposal - Secure Document Management System',
    'Midterm Grade Report - BSIT 3-A',
    'Chapter 2 - Review of Related Literature (Draft 1)'
  );
DELETE FROM documents WHERE title IN (
    'Laboratory Exercise 3 - SQL Basics and Prepared Statements',
    'Chapter 1 - The Problem and Its Background (Draft)',
    'ITEC 85 Project Proposal - Secure Document Management System',
    'Midterm Grade Report - BSIT 3-A',
    'Chapter 2 - Review of Related Literature (Draft 1)'
);

-- 2) Give the demo accounts realistic names (usernames/passwords unchanged)
UPDATE users SET full_name = 'Andrea Reyes'  WHERE username = 'admin';
UPDATE users SET full_name = 'Maria Santos'   WHERE username = 'staff';
UPDATE users SET full_name = 'Juan Dela Cruz' WHERE username = 'viewer';

-- 3) Official CvSU forms (files already placed in storage/uploads)
-- category_id: 1=Assignments, 2=Research Papers, 3=Project Documents,
--              4=Reports, 5=Others
-- uploaded_by: 2 (staff)
-- status: approved (official forms are pre-released)
INSERT INTO documents
  (title, description, category_id, uploaded_by, original_filename, stored_filename,
   file_ext, mime_type, file_size, file_hash, status, reviewed_by, reviewed_at, review_remarks, created_at)
VALUES
('CEIT-QF-19 UCC Service Request Form',
 'Official CvSU service request form from the College of Engineering and Information Technology.',
 5, 2, 'CEIT-QF-19 UCC Service Request Form.jpg', 'aabf4ebb99f9cd7867441ea06e33afac.jpg',
 'jpg', 'image/jpeg', 819673, '24030b576b3d8a58ac8ec5a976e437c4e907402e5b4feac793ab3fc01b296437',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, DATE_SUB(NOW(), INTERVAL 4 DAY)),

('CEIT-QF-20 Claim Stub',
 'Official CvSU claim stub form from the College of Engineering and Information Technology.',
 5, 2, 'CEIT-QF-20 Claim Stub.jpg', '7ed5cc075784df64442e4361cfec3f18.jpg',
 'jpg', 'image/jpeg', 679160, '1f79c70d1f55a881dc61c8cffeb846dca6538850dcdc7aac96e762e36acfbfcf',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),

('CEIT-QF-21 Provisional Receipt for ICT Repair and Maintenance',
 'Official CvSU provisional receipt form for ICT repair and maintenance services.',
 5, 2, 'CEIT-QF-21 Provisional Receipt for ICT Repair and Maintenance.jpg', '6e6aab8089bff362541081ac2c3e5c2b.jpg',
 'jpg', 'image/jpeg', 724139, 'a540ea836507c3089d8c5cf374de6338e0d2b2ee5e85ad4a2b17298521738b69',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, DATE_SUB(NOW(), INTERVAL 4 DAY)),

('CEIT-QF-22 Advisory Committee Form',
 'Official CvSU advisory committee form from the College of Engineering and Information Technology.',
 5, 2, 'CEIT-QF-22 Advisory Committee Form.pdf', '0703dba7a93694be63080b1eb7c14c61.pdf',
 'pdf', 'application/pdf', 168889, '047d3ab0d7cb7949af6183035155c05f655eecafca6d67c70da8964fd4623a02',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 3 DAY), NULL, DATE_SUB(NOW(), INTERVAL 6 DAY)),

('CEIT-QF-23 Undergraduate Thesis-Design Project-Capstone Comments-Suggestions Form',
 'Official CvSU comments and suggestions form for undergraduate thesis, design project, and capstone.',
 2, 2, 'CEIT-QF-23 Undergraduate Thesis-Design Project-Capstone Comments-Suggestions Form.pdf', '7f5605e07bc1eef548931d0cc98a9130.pdf',
 'pdf', 'application/pdf', 345624, '8c7e7ac67b57b3360066a9c6200a5284939dd2799e43ec5cf812bd7c69e2c77c',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 2 DAY),
 NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('CEIT-QF-24 Summary of Evaluators Comments',
 'Official CvSU summary of evaluators comments form.',
 2, 2, 'CEIT-QF-24 Summary of Evaluators Comments.pdf', '9f91968099bb07cf2a60d97dfc13fcd2.pdf',
 'pdf', 'application/pdf', 157830, 'a4857574c58fd7dd17eb5c36cd9585110b7140145cf88fb5cd99033ab1fdde28',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('CEIT-QF-25 Undergraduate Thesis-Design Project-Capstone Timeline Form',
 'Official CvSU timeline form for undergraduate thesis, design project, and capstone.',
 2, 2, 'CEIT-QF-25 Undergraduate Thesis-Design Project-Capstone Timeline Form.pdf', '555c6bafcff15e6788ef4c13d433add3.pdf',
 'pdf', 'application/pdf', 489262, '38d5e1fcba5e0d287914aa0ae936fcadddde9411c057e912c8171977eb32b0a4',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('CEIT-QF-26 Undergraduate Thesis-Design Project-Capstone Timeline Form',
 'Official CvSU timeline form for undergraduate thesis, design project, and capstone.',
 2, 2, 'CEIT-QF-26 Undergraduate Thesis-Design Project-Capstone Timeline Form.pdf', '9e1f7acd0b98cef6a70b857d9c9e27df.pdf',
 'pdf', 'application/pdf', 386197, '00fdb097459cbaa76824624d84cd4370a1f99c3fcdedd46c174e74fd7bbbeac2',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('CEIT-QF-27 Timeline for 200A Title Proposal Form',
 'Official CvSU timeline form for 200A title proposal.',
 2, 2, 'CEIT-QF-27 Timeline for 200A Title Proposal Form.pdf', '00ebfa81b7f73f3071141d39d25161dc.pdf',
 'pdf', 'application/pdf', 365444, 'd995dc4b5e3bcf51c474e51405e13b813f04d1c4987d9d4108629665b0c59820',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('CEIT-QF-28 Timeline for 200B Thesis Design Project Capstone Outline',
 'Official CvSU timeline form for 200B thesis design project capstone outline.',
 2, 2, 'CEIT-QF-28 Timeline for 200B Thesis Design Project Capstone Outline.pdf', '0552e93fa4f76fe3d64624d0e5056c62.pdf',
 'pdf', 'application/pdf', 406519, '78fd8c40b12c1b8fdee6d8847bf63533f97adc80a378e66c0774c22c9a3959fe',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('CEIT-QF-29 Timeline for 200C Final Manuscript Form',
 'Official CvSU timeline form for 200C final manuscript.',
 2, 2, 'CEIT-QF-29 Timeline for 200C Final Manuscript Form.pdf', '13cb0dfcd1184c06e282d8fdd412662f.pdf',
 'pdf', 'application/pdf', 388670, '7fdaa41e5d39dac0e59f4997f32e62323bf3a5ea2697ff5a20187f331c835c9c',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('CEIT-QF-30 PAPER REVIEW FORM',
 'Official CvSU paper review form.',
 2, 2, 'CEIT-QF-30 PAPER REVIEW FORM.pdf', 'e20a66398c68102d24f3b34d128aaa3e.pdf',
 'pdf', 'application/pdf', 319963, '435eff4b752c60db21de317097892af0078c15aee660ce7116b132599b544bf4',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('UREG-QF-05 Pre-Registration Form',
 'Official CvSU pre-registration form from the University Registrar.',
 5, 2, 'UREG-QF-05 Pre-Registration Form.docx', '8017c5178d260c226b0fa6b0ac7ad015.docx',
 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 297591, '68029d07b1f1387190ee93de807c9c21bca1862de994dbe7546fcbdd4ee6b80a',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, DATE_SUB(NOW(), INTERVAL 4 DAY)),
('UREG-QF-09 Adding_Dropping_Changing of Subject_Schedule Form',
 'Official CvSU form for adding, dropping, or changing subject schedule from the University Registrar.',
 5, 2, 'UREG-QF-09 Adding_Dropping_Changing of Subject_Schedule Form.docx', 'c950e057c8fc6e84645f1519fb6e3983.docx',
 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 49036, '61b41d46c9e2a5763e9e58842c0b5a3dbe5744b92b716854a67b4d545ec2e57c',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, DATE_SUB(NOW(), INTERVAL 4 DAY));
