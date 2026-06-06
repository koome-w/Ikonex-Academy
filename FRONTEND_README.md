# Ikonex Academy — Frontend

This folder contains the HTML/CSS/JS frontend scaffold for the Ikonex Academy Student Management System.

- `index.html` — Single-page scaffold with sidebar, dashboard, and pages for Streams, Students, Subjects, Assessments, Reports.
- `css/styles.css` — Theme variables, layout, and component styles (responsive).
- `js/app.js` — Frontend logic (page routing, demo data, forms). Includes comments and PHP integration hooks.

How to run

Open `index.html` in a browser (double-click or serve via a static server). Later you'll replace client demo data with AJAX calls to PHP endpoints.

Next steps to connect to PHP backend

- Create API endpoints (e.g., `/api/streams.php`, `/api/students.php`) returning JSON.
- Replace local `state` usage in `js/app.js` with `fetch` calls to those endpoints.
- Implement server-side validation to prevent duplicate score entries.
