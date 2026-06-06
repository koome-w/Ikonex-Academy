<?php
require_once __DIR__ . '/config.php';
requireLogin();
$username = sanitize($_SESSION['username'] ?? 'Faculty Admin');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Ikonex Academy — Student Management System</title>
  <link rel="stylesheet" href="css/styles.css">
  <script defer src="js/app.js"></script>
  <script>
    sessionStorage.setItem('username', '<?php echo sanitize($_SESSION['username'] ?? 'Faculty Admin'); ?>');
    sessionStorage.setItem('role', '<?php echo sanitize($_SESSION['role'] ?? 'admin'); ?>');
  </script>
</head>
<body>
  <div class="app">
    <aside class="sidebar" id="sidebar">
      <button id="toggleSidebar" class="sidebar-toggle" aria-label="Toggle sidebar">☰</button>
      <div class="brand">
        <div class="avatar">IA</div>
        <div class="brand-text">
          <h1>Ikonex</h1>
          <small>Faculty Admin</small>
        </div>
      </div>
      <nav class="nav">
        <button class="nav-item active" data-page="dashboard">Faculty Portal</button>
        <button class="nav-item" data-page="streams">Manage Streams</button>
        <button class="nav-item" data-page="students">Students</button>
        <button class="nav-item" data-page="subjects">Subjects</button>
        <button class="nav-item" data-page="assessments">Assessments</button>
        <button class="nav-item" data-page="reports">Reports</button>
        <button class="nav-item" data-page="settings">Account Settings</button>
        <a class="nav-item" href="logout.php">Logout</a>
      </nav>
      <footer class="sidebar-footer">&copy; <span id="year"></span> Ikonex Academy</footer>
    </aside>

    <main class="main">
      <header class="topbar">
        <div class="greeting">
          <h2 id="greeting">Hello, <?php echo $username; ?></h2>
          <p class="muted">Welcome! You are logged in as a Faculty Administrator.</p>
        </div>
        <div class="topbar-actions">
          <div id="clock" class="clock">--:--:--</div>
        </div>
      </header>

      <section class="content" id="content">
        <article class="page" id="dashboard" data-title="Dashboard">
          <div class="analytics">
            <div class="card stat stat-primary">
              <div class="stat-icon">📚</div>
              <div class="stat-body">
                <div class="stat-value" id="stat-students">0</div>
                <div class="stat-label">STUDENTS</div>
              </div>
            </div>
            <div class="card stat stat-info">
              <div class="stat-icon">👨‍🏫</div>
              <div class="stat-body">
                <div class="stat-value" id="stat-teachers">0</div>
                <div class="stat-label">TEACHERS</div>
              </div>
            </div>
            <div class="card stat stat-success">
              <div class="stat-icon">🎓</div>
              <div class="stat-body">
                <div class="stat-value" id="stat-faculty">0</div>
                <div class="stat-label">FACULTY</div>
              </div>
            </div>
            <div class="card stat stat-secondary">
              <div class="stat-icon">👥</div>
              <div class="stat-body">
                <div class="stat-value" id="stat-total">0</div>
                <div class="stat-label">TOTAL</div>
              </div>
            </div>
          </div>

          <div class="grid">
            <div class="card announcements">
              <div class="card-header">Announcements</div>
              <div class="card-body" id="announcements">
                <div class="announcement">
                  <h4>Meeting</h4>
                  <p>We will be having an emergency meeting today at 3:00 PM.</p>
                  <div class="muted small">Administrator — <?php echo date('F d, Y'); ?></div>
                </div>
              </div>
            </div>

            <div class="card quick">
              <div class="card-header">Your activities</div>
              <div class="card-body">
                <form id="todoForm">
                  <input type="text" id="todoInput" placeholder="Todo content" />
                  <button type="submit">Add</button>
                </form>
                <ul id="todoList" class="todo-list"></ul>
              </div>
            </div>
          </div>
        </article>

        <article class="page hidden" id="streams" data-title="Class Streams">
          <div class="page-header">
            <h3>Class Streams</h3>
            <button id="addStreamBtn" class="btn">+ Create Stream</button>
          </div>
          <div class="card">
            <div class="card-body">
              <div id="streamsList"></div>
            </div>
          </div>
        </article>

        <article class="page hidden" id="students" data-title="Students">
          <div class="page-header">
            <h3>Students</h3>
            <button id="addStudentBtn" class="btn">+ Register Student</button>
          </div>
          <div class="card">
            <div class="card-body">
              <div class="grid" style="grid-template-columns:1fr 180px; gap:12px; align-items:flex-end; margin-bottom:14px;">
                <div>
                  <label>Filter by Stream</label>
                  <select id="studentFilterStream"></select>
                </div>
              </div>
              <table class="table" id="studentsTable">
                <thead>
                  <tr><th>#</th><th>Name</th><th>Stream</th><th>Admission</th><th>Actions</th></tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </article>

        <article class="page hidden" id="subjects" data-title="Subjects">
          <div class="page-header">
            <h3>Subjects</h3>
            <button id="addSubjectBtn" class="btn">+ Add Subject</button>
          </div>
          <div class="card">
            <div class="card-body">
              <div id="subjectsList"></div>
            </div>
          </div>
        </article>

        <article class="page hidden" id="assessments" data-title="Assessments">
          <div class="page-header">
            <h3>Assessments & Scores</h3>
            <button id="addScoreBtn" class="btn">+ Record Score</button>
          </div>
          <div class="card">
            <div class="card-body">
              <p class="muted">Record examination and continuous assessment scores for students and review class performance.</p>
              <div class="grid" style="grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                <div>
                  <label>Select stream</label>
                  <select id="scoreFilterStream"></select>
                </div>
                <div>
                  <label>Select subject</label>
                  <select id="scoreFilterSubject"></select>
                </div>
              </div>
              <div id="scoreSummary"></div>
              <div id="subjectPerformance"></div>
              <div id="scoreTable"></div>
            </div>
          </div>
        </article>

        <div id="streamModal" class="modal hidden" aria-hidden="true">
          <div class="modal-dialog">
            <button class="modal-close" type="button" aria-label="Close">×</button>
            <div class="modal-content">
              <h3 id="streamModalTitle">Create Stream</h3>
              <form id="streamForm" class="stack">
                <label>Stream Name</label>
                <input type="text" id="streamName" placeholder="e.g., Form 1A" required />
                <input type="hidden" id="streamId" value="" />
                <div class="form-actions">
                  <button type="submit" class="btn primary">Save Stream</button>
                  <button type="button" class="btn modal-close">Cancel</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div id="studentModal" class="modal hidden" aria-hidden="true">
          <div class="modal-dialog">
            <button class="modal-close" type="button" aria-label="Close">×</button>
            <div class="modal-content">
              <h3 id="studentModalTitle">Register Student</h3>
              <form id="studentForm" class="stack">
                <div class="grid-2">
                  <div>
                    <label>First Name</label>
                    <input id="studentFirst" required />
                  </div>
                  <div>
                    <label>Last Name</label>
                    <input id="studentLast" required />
                  </div>
                </div>
                <label>Stream</label>
                <select id="studentStream"></select>
                <label>Admission No</label>
                <input id="studentAdm" />
                <input type="hidden" id="studentId" value="" />
                <div class="form-actions">
                  <button type="submit" class="btn primary">Save Student</button>
                  <button type="button" class="btn modal-close">Cancel</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div id="subjectModal" class="modal hidden" aria-hidden="true">
          <div class="modal-dialog">
            <button class="modal-close" type="button" aria-label="Close">×</button>
            <div class="modal-content">
              <h3 id="subjectModalTitle">Add Subject</h3>
              <form id="subjectForm" class="stack">
                <label>Subject Name</label>
                <input id="subjectName" />
                <label>Assign to Streams</label>
                <div id="subjectStreamsCheckboxes" class="checkbox-group"></div>
                <input type="hidden" id="subjectId" value="" />
                <div class="form-actions">
                  <button type="submit" class="btn primary">Save Subject</button>
                  <button type="button" class="btn modal-close">Cancel</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div id="scoreModal" class="modal hidden" aria-hidden="true">
          <div class="modal-dialog">
            <button class="modal-close" type="button" aria-label="Close">×</button>
            <div class="modal-content">
              <h3 id="scoreModalTitle">Record Score</h3>
              <form id="scoreForm" class="stack">
                <label>Stream</label>
                <select id="scoreStream"></select>
                <label>Subject</label>
                <select id="scoreSubject"></select>
                <label>Student</label>
                <select id="scoreStudent"></select>
                <label>Assessment Type</label>
                <select id="scoreType">
                  <option value="Exam">Exam</option>
                  <option value="CA">Continuous Assessment</option>
                </select>
                <label>Score</label>
                <input id="scoreValue" type="number" min="0" max="100" />
                <input type="hidden" id="scoreId" value="" />
                <div class="form-actions">
                  <button type="submit" class="btn primary">Save Score</button>
                  <button type="button" class="btn modal-close">Cancel</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div id="infoModal" class="modal hidden" aria-hidden="true">
          <div class="modal-dialog info-dialog">
            <button class="modal-close" type="button" aria-label="Close">×</button>
            <div class="modal-content" id="infoContent"></div>
          </div>
        </div>

        <article class="page hidden" id="reports" data-title="Reports">
          <div class="page-header">
            <h3>Reports</h3>
          </div>
          <div class="card">
            <div class="card-body">
              <p class="muted">Generate printable student report cards and class performance reports.</p>
              <div class="grid">
                <div>
                  <label>Choose Stream</label>
                  <select id="reportStream"></select>
                </div>
                <div>
                  <label>Choose Student (optional)</label>
                  <select id="reportStudent"></select>
                </div>
              </div>
              <div class="form-actions">
                <button id="generateReport" class="btn primary">Open Report</button>
              </div>
            </div>
          </div>
        </article>

        <article class="page hidden" id="settings" data-title="Account Settings">
          <div class="page-header">
            <h3>Account Settings</h3>
          </div>
          <div class="card" style="max-width:600px;">
            <div class="card-body">
              <h4>User Profile</h4>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                <div>
                  <label>Username</label>
                  <input type="text" id="settingsUsername" readonly style="background:#f3f4f6;" />
                </div>
                <div>
                  <label>Role</label>
                  <input type="text" id="settingsRole" readonly style="background:#f3f4f6;" />
                </div>
              </div>
              <h4 style="margin-top:24px;">Change Password</h4>
              <form id="passwordForm" class="stack">
                <label>Current Password</label>
                <input type="password" id="currentPassword" required />
                <label>New Password</label>
                <input type="password" id="newPassword" required />
                <label>Confirm New Password</label>
                <input type="password" id="confirmPassword" required />
                <div class="form-actions">
                  <button type="submit" class="btn primary">Update Password</button>
                </div>
              </form>
            </div>
          </div>
        </article>

      </section>

      <footer class="footer muted">Ikonex Academy - Student Management System</footer>
    </main>
  </div>
</body>
</html>
