-- extra seed data — adds 3 users, 6 posts, some ratings + comments
-- run this in phpmyadmin after schema.sql
-- all new accounts use password "password"

USE db202200881;

-- 3 new users (2 writers + 1 reader)
INSERT INTO dbProj_users (username, email, password, role) VALUES
('alex_dev',      'alex@blog.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'creator'),
('maria_travels', 'maria@blog.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'creator'),
('sara_reads',    'sara@blog.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer');

-- grab the new user ids so we can reference them below
SET @alex_uid  = (SELECT uid FROM dbProj_users WHERE username = 'alex_dev');
SET @maria_uid = (SELECT uid FROM dbProj_users WHERE username = 'maria_travels');
SET @sara_uid  = (SELECT uid FROM dbProj_users WHERE username = 'sara_reads');

-- 6 new posts spread across the categories
-- cat ids: 1=Technology, 2=Lifestyle, 3=Travel, 4=Health, 5=Sports
INSERT INTO dbProj_posts (title, short_desc, full_content, image_path, cat_id, uid, published, created_at) VALUES

-- Alex (Tech) — 2 posts
('Git Rebase vs Merge', 'A friendly explainer for cleaner Git history.',
 'When should you rebase and when should you merge? We cover the trade-offs, the cases where each shines, and why your team probably has the wrong default. Includes real examples from a 5-year-old monorepo.',
 NULL, 1, @alex_uid, 1, '2026-04-22 09:00:00'),

('CSS Grid in 5 Minutes', 'Stop reaching for Bootstrap for every layout.',
 'CSS Grid lets you build any layout in pure CSS without a framework. We walk through the 10 properties you actually need, then build a responsive blog layout in 30 lines of code.',
 NULL, 1, @alex_uid, 1, '2026-04-23 14:30:00'),

-- Maria (Travel) — 2 posts
('Backpacking Vietnam', 'Two weeks, north to south, on $40 a day.',
 'Hanoi, Hue, Hoi An, Saigon — plus the Ha Giang loop on motorbike. We share our exact route, accommodation picks, and the food spots that locals actually go to.',
 NULL, 3, @maria_uid, 1, '2026-04-22 11:00:00'),

('Why Portugal is Underrated', 'The best-value country in Western Europe.',
 'Lisbon, Porto, the Algarve, and the Azores. We break down why Portugal punches above its weight for food, weather, and prices — and why summer is not the best time to visit.',
 NULL, 3, @maria_uid, 1, '2026-04-24 10:15:00'),

-- Jane (existing writer) — 2 more posts
('The 10K Steps Myth Updated', 'New research shows the optimal number is lower.',
 'A 2025 study tracked 100,000 walkers and found the sweet spot is around 7,500 steps a day, not 10,000. Beyond that, returns diminish quickly. Here is what the data really says.',
 NULL, 4, 2, 1, '2026-04-21 08:00:00'),

('World Cup 2026 — Hot Takes', 'Predictions for the upcoming summer tournament.',
 'Argentina to defend the cup, USA out in the round of 16, and a surprise semifinalist nobody is talking about. Plus our top 5 players to watch — including one teenager who could change the tournament.',
 NULL, 5, 2, 1, '2026-04-25 16:00:00');

-- new ratings (sara reads a lot, alex/maria rate each other)
INSERT INTO dbProj_ratings (post_id, uid, rating) VALUES
(1,  @sara_uid,  5),  -- sara likes the PHP intro
(4,  @sara_uid,  4),  -- morning routine
(6,  @sara_uid,  5),  -- SE Asia travel
(9,  @sara_uid,  5),  -- sleep science
(12, @sara_uid,  4),  -- premier league
(2,  @alex_uid,  5),  -- alex likes VS Code post
(3,  @alex_uid,  5),  -- and the FULLTEXT one
(7,  @maria_uid, 4),  -- maria liked Oman gems
(8,  @maria_uid, 5),  -- and Japan budget tips
(14, @sara_uid,  5);  -- REST API tutorial

-- new comments from the new users
INSERT INTO dbProj_comments (post_id, uid, comment_text) VALUES
(1,  @sara_uid,  'Just bookmarked this — perfect timing, I am learning PHP for a class project right now.'),
(6,  @maria_uid, 'Did Vietnam last year and your budget is spot on. Add Cat Ba island to your list for next time!'),
(9,  @sara_uid,  'The blue light section saved my sleep. Cannot recommend the F.lux/Night Shift tip enough.'),
(14, @alex_uid,  'Solid REST API tutorial. The PUT vs PATCH section is rare to see explained well in PHP examples.'),
(12, @alex_uid,  'Bold predictions! Not sure about your top-4 pick though, Liverpool look stronger than ever.'),
(7,  @sara_uid,  'Just spent two weeks in Oman. Wahiba Sands at sunset is the highlight you cannot skip.');

-- done — new test accounts (password = "password"):
--   alex@blog.com   (creator)
--   maria@blog.com  (creator)
--   sara@blog.com   (viewer)
