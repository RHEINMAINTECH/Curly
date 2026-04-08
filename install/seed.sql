-- Default Data for Curly CMS

-- Admin User (password: admin123 - CHANGE IN PRODUCTION)
-- Note: This is a placeholder hash. The installer will generate a proper one.
INSERT INTO users (name, email, password, role, status) VALUES
('Admin', 'admin@example.com', '$argon2id$v=19$m=65536,t=4,p=3$c2FsdHNhbHRzYWx0$placeholder', 'admin', 'active');

-- Default Settings
INSERT INTO settings (key, value) VALUES
('site_title', 'Curly CMS'),
('site_description', 'A modern AI-native Content Management System by RheinMainTech GmbH'),
('site_url', 'http://localhost'),
('site_language', 'en'),
('posts_per_page', '10'),
('theme', 'default'),
('footer_text', 'Powered by Curly CMS'),
('admin_email', 'admin@example.com'),
('timezone', 'Europe/Berlin');

-- Default Categories
INSERT INTO categories (name, slug, description) VALUES
('Uncategorized', 'uncategorized', 'Default category for posts'),
('News', 'news', 'News and announcements'),
('Tutorials', 'tutorials', 'How-to guides and tutorials'),
('Blog', 'blog', 'General blog posts');

-- Default Menus
INSERT INTO menus (name, slug) VALUES
('Main Menu', 'main-menu'),
('Footer Menu', 'footer-menu');

-- Default Menu Items for Main Menu
INSERT INTO menu_items (menu_id, parent_id, title, url, sort_order) VALUES
(1, 0, 'Home', '/', 0),
(1, 0, 'Blog', '/posts', 1),
(1, 0, 'About', '/page/about', 2),
(1, 0, 'Contact', '/page/contact', 3);

-- Default Menu Items for Footer Menu
INSERT INTO menu_items (menu_id, parent_id, title, url, sort_order) VALUES
(2, 0, 'Privacy Policy', '/page/privacy-policy', 0),
(2, 0, 'Terms of Service', '/page/terms', 1),
(2, 0, 'Imprint', '/page/imprint', 2);

-- Default Home Page
INSERT INTO pages (title, slug, content, structure, status, sort_order, meta_title, meta_description) VALUES
('Home', 'home', 'Welcome to Curly CMS', '{"type":"container","children":[{"type":"row","children":[{"type":"column","cols":12,"children":[{"type":"heading","level":1,"content":"Welcome to Curly CMS"},{"type":"paragraph","content":"A modern, AI-native Content Management System."},{"type":"button","content":"Get Started","href":"/admin","btn_class":"btn-primary"}]}]}]}', 'published', 0, 'Home - Curly CMS', 'Welcome to Curly CMS - A modern, AI-native Content Management System');

-- Sample About Page
INSERT INTO pages (title, slug, content, structure, status, sort_order, meta_title, meta_description) VALUES
('About', 'about', 'Learn more about us.', '{"type":"container","children":[{"type":"row","children":[{"type":"column","cols":12,"children":[{"type":"heading","level":1,"content":"About Us"},{"type":"paragraph","content":"We are passionate about building great software."}]}]}]}', 'published', 1, 'About Us', 'Learn more about our company');

-- Sample Welcome Post
INSERT INTO posts (title, slug, excerpt, content, structure, category_id, status, published_at, meta_title, meta_description) VALUES
('Welcome to Curly CMS', 'welcome-to-curly-cms', 'Get started with Curly CMS - your AI-native content management solution.', 'Curly CMS is a modern, AI-native Content Management System designed for the future of web content management.', '{"type":"container","children":[{"type":"row","children":[{"type":"column","cols":12,"children":[{"type":"heading","level":2,"content":"Getting Started"},{"type":"paragraph","content":"Curly CMS makes it easy to create and manage your website content with the power of AI."}]}]}]}', 1, 'published', datetime('now'), 'Welcome to Curly CMS', 'Get started with Curly CMS');

-- Default Templates
INSERT INTO templates (name, description, type, structure) VALUES
('Full Width Page', 'A simple full width page layout', 'page', '{"type":"container","children":[{"type":"row","children":[{"type":"column","cols":12,"children":[]}]}]}'),
('Two Column Page', 'Page with main content and sidebar', 'page', '{"type":"container","children":[{"type":"row","children":[{"type":"column","cols":8,"children":[]},{"type":"column","cols":4,"children":[]}]}]}'),
('Hero Section', 'Landing page hero section', 'section', '{"type":"section","children":[{"type":"container","children":[{"type":"row","children":[{"type":"column","cols":12,"children":[{"type":"heading","level":1,"content":"Hero Title"},{"type":"paragraph","content":"Hero description text"},{"type":"button","content":"Call to Action","href":"#"}]}]}]}]}'),
('Blog Post', 'Standard blog post layout', 'post', '{"type":"container","children":[{"type":"row","children":[{"type":"column","cols":12,"children":[{"type":"heading","level":1,"content":"Post Title"},{"type":"paragraph","content":"Post content goes here..."}]}]}]}');

-- API Key for AI Services
INSERT INTO api_keys (name, key, active) VALUES
('Default API Key', 'curly_cms_default_api_key_change_me', 1);

-- MCS Token
INSERT INTO mcs_tokens (token, name, active) VALUES
('mcs_default_token_change_me', 'Default MCS Token', 1);

-- A2A Token
INSERT INTO a2a_tokens (token, agent_id, active) VALUES
('a2a_default_token_change_me', 'cms-agent', 1);
