-- ============================================================
-- SDMS demo data: realistic sample documents + friendly names.
-- Safe to re-import. Run AFTER schema.sql + seed.sql.
-- No `USE` statement: select your database in phpMyAdmin first (see seed.sql).
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
UPDATE users SET full_name = 'Maria Santos'   WHERE username = 'staff';
UPDATE users SET full_name = 'Juan Dela Cruz' WHERE username = 'viewer';

-- 3) Sample documents (files already placed in storage/uploads)
INSERT INTO documents
  (title, description, category_id, uploaded_by, original_filename, stored_filename,
   file_ext, mime_type, file_size, file_hash, status, reviewed_by, reviewed_at, review_remarks, created_at)
VALUES
('Laboratory Exercise 3 - SQL Basics and Prepared Statements',
 'Lab handout with objectives, tasks, and submission instructions for Week 3.',
 1, 2, 'ITEC85-Lab3-SQL-Basics.txt', 'b6de415b548b4424bfeb5bf9b8f3877e.txt', 'txt', 'text/plain', 784, '78bbe7192ada32ac30be520e3646da478bb4ec0c826c307922bd3b09b91d7de3',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, DATE_SUB(NOW(), INTERVAL 4 DAY)),

('Chapter 1 - The Problem and Its Background (Draft)',
 'Draft of the research introduction for the group paper. Comments welcome.',
 2, 2, 'Chapter1-Problem-and-Background.txt', '8f7ab75245284d4cab6522811821125b.txt', 'txt', 'text/plain', 1132, '28e0b689c3ebd8f607976bb5a6628c9e9198f6c7fd0f1f47c7f473ba58d492f2',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),

('ITEC 85 Project Proposal - Secure Document Management System',
 'Group proposal: objectives, deliverables, and schedule for the final project.',
 3, 2, 'ITEC85-Project-Proposal-Group4.txt', 'a18f85a49b7e4354b4335d4f94833f26.txt', 'txt', 'text/plain', 812, '37eeb2614e98e4f01021cc0d97024b2075df11239bd03f607df33ce14fdbb08a',
 'pending', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),

('Midterm Grade Report - BSIT 3-A',
 'Midterm grades for the class, generated from the grade sheet.',
 4, 2, 'Midterm-Grade-Report-BSIT3A.txt', 'e06a13e2de074fba83b4c1dccc530d55.txt', 'txt', 'text/plain', 730, '36d1786dd3f499ec9893bb0418c26af1c36d52b8f67e5a55a81e6401074a6747',
 'approved', 1, DATE_SUB(NOW(), INTERVAL 3 DAY), NULL, DATE_SUB(NOW(), INTERVAL 6 DAY)),

('Chapter 2 - Review of Related Literature (Draft 1)',
 'First draft of the related-literature chapter. Needs citation work.',
 2, 2, 'Chapter2-RRL-Draft1.txt', '537065c1b6b8418bb2fb38628d1b8f7d.txt', 'txt', 'text/plain', 646, '7c979cd7ded8856a8946dac7dd67157bd15041e2394ac59fc6786cb0b4e8e18c',
 'rejected', 1, DATE_SUB(NOW(), INTERVAL 2 DAY),
 'Please add proper citations for the third to fifth paragraphs and re-upload.',
 DATE_SUB(NOW(), INTERVAL 2 DAY));
