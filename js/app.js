const state = {
  streams: [],
  students: [],
  subjects: [],
  scores: []
};

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

function showPage(id) {
  $$('.page').forEach(page => page.classList.add('hidden'));
  const page = $(`#${id}`);
  if (page) page.classList.remove('hidden');
  $$('.nav-item').forEach(btn => btn.classList.toggle('active', btn.dataset.page === id));
}

function getGradeForScore(score) {
  const scale = [
    { grade: 'A', min: 70 },
    { grade: 'B', min: 60 },
    { grade: 'C', min: 50 },
    { grade: 'D', min: 40 },
    { grade: 'E', min: 30 },
    { grade: 'F', min: 0 }
  ];
  const value = Number(score);
  const rule = scale.find(item => value >= item.min);
  return rule ? rule.grade : 'F';
}

function showModal(selector) {
  const modal = $(selector);
  if (!modal) return;
  modal.classList.remove('hidden');
  modal.setAttribute('aria-hidden', 'false');
}

function hideModal(selector) {
  const modal = $(selector);
  if (!modal) return;
  modal.classList.add('hidden');
  modal.setAttribute('aria-hidden', 'true');
}

function closeAllModals() {
  $$('.modal').forEach(modal => modal.classList.add('hidden'));
}

function init() {
  $('#year').textContent = new Date().getFullYear();
  $$('.nav-item').forEach(btn => btn.addEventListener('click', () => showPage(btn.dataset.page)));
  
  const toggleBtn = $('#toggleSidebar');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const sb = $('#sidebar');
      if (sb) {
        sb.classList.toggle('active');
      }
    });
  }
  
  // Close sidebar when nav item is clicked on mobile
  $$('.nav-item').forEach(btn => {
    btn.addEventListener('click', () => {
      if (window.innerWidth <= 900) {
        const sb = $('#sidebar');
        if (sb) {
          sb.classList.remove('active');
        }
      }
    });
  });

  bindForms();
  loadData();
  populateSettingsPage();
  setInterval(() => { $('#clock').textContent = new Date().toLocaleTimeString(); }, 1000);
}

async function apiFetch(path, options = {}) {
  const response = await fetch(path, {
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    ...options,
    body: options.body ? JSON.stringify(options.body) : undefined
  });
  const data = await response.json();
  if (!response.ok) {
    throw new Error(data.error || 'Server error');
  }
  return data;
}

async function loadData() {
  try {
    const [streams, students, subjects, scores] = await Promise.all([
      apiFetch('api/streams.php'),
      apiFetch('api/students.php'),
      apiFetch('api/subjects.php'),
      apiFetch('api/scores.php')
    ]);
    state.streams = streams.data;
    state.students = students.data;
    state.subjects = subjects.data;
    state.scores = scores.data;
    renderStats();
    renderStreamsList();
    renderStudentsTable();
    renderSubjectsList();
    renderScoreTable();
    populateSelectors();
    renderScoreSummary();
  } catch (error) {
    console.error(error);
    alert('Unable to load backend data. Please verify PHP and database configuration.');
  }
}

function renderStats() {
  $('#stat-students').textContent = state.students.length;
  $('#stat-teachers').textContent = 2;
  $('#stat-faculty').textContent = 1;
  $('#stat-total').textContent = state.students.length + 3;
}

function renderStreamsList() {
  const container = $('#streamsList');
  container.innerHTML = '';
  state.streams.forEach(stream => {
    const studentsInStream = state.students.filter(student => student.stream_id === stream.id).length;
    const assignedSubjects = state.subjects.filter(subject => subject.stream_ids.includes(stream.id)).length;
    const item = document.createElement('div');
    item.className = 'stream-item card';
    item.innerHTML = `
      <div style="display:flex;justify-content:space-between;align-items:center">
        <div>
          <strong>${stream.name}</strong>
          <div class="muted">Students: ${studentsInStream} · Subjects: ${assignedSubjects}</div>
        </div>
        <div>
          <button data-id="${stream.id}" class="btn small view-stream">Details</button>
          <button data-id="${stream.id}" class="btn small edit-stream">Edit</button>
          <button data-id="${stream.id}" class="btn small danger delete-stream">Delete</button>
        </div>
      </div>
    `;
    container.appendChild(item);
  });
}

function renderStudentsTable() {
  const tbody = $('#studentsTable tbody');
  tbody.innerHTML = '';
  const filterStreamId = parseInt($('#studentFilterStream')?.value, 10) || 0;
  const studentsToRender = filterStreamId ? state.students.filter(student => student.stream_id === filterStreamId) : state.students;
  studentsToRender.forEach((student, idx) => {
    const stream = state.streams.find(s => s.id === student.stream_id)?.name || '-';
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${idx + 1}</td>
      <td>${student.first_name} ${student.last_name}</td>
      <td>${stream}</td>
      <td>${student.admission_number || ''}</td>
      <td>
        <button class="btn small view-student" data-id="${student.id}">View</button>
        <button class="btn small edit-student" data-id="${student.id}">Edit</button>
        <button class="btn small danger delete-student" data-id="${student.id}">Delete</button>
      </td>
    `;
    tbody.appendChild(row);
  });
}

function renderSubjectsList() {
  const container = $('#subjectsList');
  container.innerHTML = '';
  state.subjects.forEach(subject => {
    const assigned = subject.stream_ids.length ? state.streams.filter(stream => subject.stream_ids.includes(stream.id)).map(stream => stream.name).join(', ') : 'Unassigned';
    const item = document.createElement('div');
    item.className = 'stream-item card';
    item.innerHTML = `
      <div style="display:flex;justify-content:space-between;align-items:center">
        <div>
          <strong>${subject.name}</strong>
          <div class="muted">Assigned to: ${assigned}</div>
        </div>
        <div>
          <button data-id="${subject.id}" class="btn small edit-subject">Edit</button>
          <button data-id="${subject.id}" class="btn small danger delete-subject">Delete</button>
        </div>
      </div>
    `;
    container.appendChild(item);
  });
}

function renderScoreTable() {
  const container = $('#scoreTable');
  container.innerHTML = '';
  if (!state.scores.length) {
    container.innerHTML = '<p class="muted">No scores recorded yet.</p>';
    return;
  }
  const table = document.createElement('table');
  table.className = 'table';
  table.innerHTML = `
    <thead>
      <tr><th>#</th><th>Student</th><th>Stream</th><th>Subject</th><th>Type</th><th>Score</th><th>Actions</th></tr>
    </thead>
    <tbody></tbody>
  `;
  const tbody = table.querySelector('tbody');
  state.scores.forEach((score, idx) => {
    const stream = state.streams.find(s => s.id === score.stream_id)?.name || '-';
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${idx + 1}</td>
      <td>${score.student_name}</td>
      <td>${stream}</td>
      <td>${score.subject_name}</td>
      <td>${score.assessment_type}</td>
      <td>${score.score}</td>
      <td>
        <button class="btn small edit-score" data-id="${score.id}">Edit</button>
        <button class="btn small danger delete-score" data-id="${score.id}">Delete</button>
      </td>
    `;
    tbody.appendChild(tr);
  });
  container.appendChild(table);
}

function renderScoreSummary() {
  const container = $('#scoreSummary');
  container.innerHTML = '';
  if (!state.streams.length || !state.students.length || !state.subjects.length) {
    container.innerHTML = '<p class="muted">Add streams, students, and subjects to begin scoring.</p>';
    return;
  }
  const streamId = parseInt($('#scoreFilterStream').value, 10) || 0;
  const subjectId = parseInt($('#scoreFilterSubject').value, 10) || 0;
  const selectedStream = state.streams.find(item => item.id === streamId);
  const selectedSubject = state.subjects.find(item => item.id === subjectId);
  const summary = document.createElement('div');
  summary.className = 'card';
  const rows = [];
  if (selectedStream) {
    const streamScores = state.scores.filter(score => score.stream_id === selectedStream.id);
    const streamAverage = streamScores.length ? (streamScores.reduce((sum, record) => sum + parseFloat(record.score), 0) / streamScores.length).toFixed(2) : '0.00';
    rows.push(`<p><strong>${selectedStream.name}</strong> has ${streamScores.length} recorded scores with an average of ${streamAverage}.</p>`);
  }
  if (selectedSubject) {
    const subjectScores = selectedStream ? state.scores.filter(score => score.subject_id === selectedSubject.id && score.stream_id === selectedStream.id) : state.scores.filter(score => score.subject_id === selectedSubject.id);
    const subjectAverage = subjectScores.length ? (subjectScores.reduce((sum, record) => sum + parseFloat(record.score), 0) / subjectScores.length).toFixed(2) : '0.00';
    rows.push(`<p><strong>${selectedSubject.name}</strong> ${selectedStream ? `in ${selectedStream.name}` : ''} average score: ${subjectAverage}. Recorded entries: ${subjectScores.length}.</p>`);
  }
  summary.innerHTML = rows.length ? rows.join('') : '<p class="muted">Select a stream and subject to view performance details.</p>';
  container.appendChild(summary);
}

function renderSubjectPerformance() {
  const container = $('#subjectPerformance');
  container.innerHTML = '';
  const streamId = parseInt($('#scoreFilterStream').value, 10) || 0;
  const subjectId = parseInt($('#scoreFilterSubject').value, 10) || 0;
  if (!streamId || !subjectId) {
    container.innerHTML = '<p class="muted">Choose a stream and subject to see class performance.</p>';
    return;
  }
  const selectedStream = state.streams.find(item => item.id === streamId);
  const selectedSubject = state.subjects.find(item => item.id === subjectId);
  const studentsInStream = state.students.filter(student => student.stream_id === streamId);
  const rows = studentsInStream.map(student => {
    const examScore = state.scores.find(score => score.student_id === student.id && score.subject_id === subjectId && score.assessment_type === 'Exam');
    const caScore = state.scores.find(score => score.student_id === student.id && score.subject_id === subjectId && score.assessment_type === 'CA');
    const total = (examScore ? Number(examScore.score) : 0) + (caScore ? Number(caScore.score) : 0);
    return { student, exam: examScore ? Number(examScore.score) : 0, ca: caScore ? Number(caScore.score) : 0, total };
  }).sort((a, b) => b.total - a.total);

  const table = document.createElement('table');
  table.className = 'table';
  table.innerHTML = `
    <thead>
      <tr><th>Position</th><th>Student</th><th>Exam</th><th>CA</th><th>Total</th><th>Grade</th></tr>
    </thead>
    <tbody>${rows.map((row, index) => `
      <tr>
        <td>${index + 1}</td>
        <td>${row.student.first_name} ${row.student.last_name}</td>
        <td>${row.exam.toFixed(2)}</td>
        <td>${row.ca.toFixed(2)}</td>
        <td>${row.total.toFixed(2)}</td>
        <td>${getGradeForScore(row.total)}</td>
      </tr>
    `).join('')}</tbody>
  `;
  const title = document.createElement('div');
  title.className = 'card';
  title.innerHTML = `<strong>Subject performance for ${selectedSubject.name} (${selectedStream.name})</strong>`;
  container.appendChild(title);
  container.appendChild(table);
}

function buildStreamDetailsHtml(stream) {
  const studentsInStream = state.students.filter(student => student.stream_id === stream.id);
  const subjectsAssigned = state.subjects.filter(subject => subject.stream_ids.includes(stream.id));
  return `
    <h3>${stream.name}</h3>
    <p class="muted">Stream details for ${stream.name}.</p>
    <div class="card" style="margin-bottom:14px;padding:16px;">
      <strong>Students:</strong> ${studentsInStream.length}<br>
      <strong>Subjects:</strong> ${subjectsAssigned.length}
    </div>
    <div class="card" style="padding:16px;">
      <h4>Students</h4>
      <ul>${studentsInStream.map(student => `<li>${student.first_name} ${student.last_name} (${student.admission_number || 'No admission no'})</li>`).join('') || '<li class="muted">No students assigned yet.</li>'}</ul>
    </div>
    <div class="card" style="padding:16px; margin-top:12px;">
      <h4>Assigned subjects</h4>
      <ul>${subjectsAssigned.map(subject => `<li>${subject.name}</li>`).join('') || '<li class="muted">No subjects assigned yet.</li>'}</ul>
    </div>
  `;
}

function buildStudentDetailsHtml(student) {
  const stream = state.streams.find(s => s.id === student.stream_id);
  const scores = state.scores.filter(score => score.student_id === student.id);
  const grouped = scores.reduce((acc, score) => {
    const key = score.subject_id;
    if (!acc[key]) acc[key] = { subject_name: score.subject_name, exam: 0, ca: 0 };
    if (score.assessment_type === 'Exam') acc[key].exam += Number(score.score);
    else acc[key].ca += Number(score.score);
    return acc;
  }, {});
  const rows = Object.values(grouped).map(subject => {
    const total = subject.exam + subject.ca;
    const grade = getGradeForScore(total);
    return `<tr><td>${subject.subject_name}</td><td>${subject.exam.toFixed(2)}</td><td>${subject.ca.toFixed(2)}</td><td>${total.toFixed(2)}</td><td>${grade}</td></tr>`;
  }).join('');
  const total = Object.values(grouped).reduce((sum, subject) => sum + subject.exam + subject.ca, 0);
  const subjectsCount = Object.keys(grouped).length;
  const average = subjectsCount ? (total / subjectsCount).toFixed(2) : '0.00';
  return `
    <h3>${student.first_name} ${student.last_name}</h3>
    <p class="muted">Admission No: ${student.admission_number || 'N/A'} | Stream: ${stream?.name || 'N/A'}</p>
    <div class="card" style="margin-bottom:14px;padding:16px;">
      <strong>Total Marks:</strong> ${total.toFixed(2)}<br>
      <strong>Average:</strong> ${average}
    </div>
    <h4>Subject performance</h4>
    <table class="table">
      <thead><tr><th>Subject</th><th>Exam</th><th>CA</th><th>Total</th><th>Grade</th></tr></thead>
      <tbody>${rows || '<tr><td class="muted" colspan="5">No scores recorded yet.</td></tr>'}</tbody>
    </table>
  `;
}

function populateSelectors() {
  // Populate form selectors for creating/editing records
  ['#studentStream', '#scoreStream', '#reportStream'].forEach(selector => {
    const element = $(selector);
    if (!element) return;
    element.innerHTML = '';
    state.streams.forEach(stream => {
      const option = document.createElement('option');
      option.value = stream.id;
      option.textContent = stream.name;
      element.appendChild(option);
    });
  });

  // Populate filter dropdowns
  ['#studentFilterStream', '#scoreFilterStream'].forEach(selector => {
    const element = $(selector);
    if (!element) return;
    element.innerHTML = '<option value="">-- All --</option>';
    state.streams.forEach(stream => {
      const option = document.createElement('option');
      option.value = stream.id;
      option.textContent = stream.name;
      element.appendChild(option);
    });
  });

  // Populate subject filter dropdown
  const subjectFilterSelect = $('#scoreFilterSubject');
  if (subjectFilterSelect) {
    subjectFilterSelect.innerHTML = '<option value="">-- All --</option>';
    state.subjects.forEach(subject => {
      const option = document.createElement('option');
      option.value = subject.id;
      option.textContent = subject.name;
      subjectFilterSelect.appendChild(option);
    });
  }

  populateSubjectStreamsCheckboxes();

  const reportStudent = $('#reportStudent');
  if (reportStudent) {
    reportStudent.innerHTML = '<option value="">--- Select ---</option>';
    state.students.forEach(student => {
      const option = document.createElement('option');
      option.value = student.id;
      option.textContent = `${student.first_name} ${student.last_name}`;
      reportStudent.appendChild(option);
    });
  }

  updateScoreSubjectAndStudent();
}

function populateSubjectStreamsCheckboxes() {
  const container = $('#subjectStreamsCheckboxes');
  if (!container) return;
  container.innerHTML = '';
  state.streams.forEach(stream => {
    const div = document.createElement('div');
    div.className = 'checkbox-item';
    div.innerHTML = `
      <input type="checkbox" id="subjectStream_${stream.id}" value="${stream.id}" class="subject-stream-checkbox" />
      <label for="subjectStream_${stream.id}">${stream.name}</label>
    `;
    container.appendChild(div);
  });
}

function populateSettingsPage() {
  const username = sessionStorage.getItem('username') || 'Faculty Admin';
  const role = sessionStorage.getItem('role') || 'admin';
  $('#settingsUsername').value = username;
  $('#settingsRole').value = role.charAt(0).toUpperCase() + role.slice(1);
}

function populateReportStudentOptions() {
  const streamId = parseInt($('#reportStream')?.value, 10) || 0;
  const reportStudent = $('#reportStudent');
  if (!reportStudent) return;
  reportStudent.innerHTML = '<option value="">--- Select ---</option>';
  state.students
    .filter(student => !streamId || student.stream_id === streamId)
    .forEach(student => {
      const option = document.createElement('option');
      option.value = student.id;
      option.textContent = `${student.first_name} ${student.last_name}`;
      reportStudent.appendChild(option);
    });
}

async function updateScoreSubjectAndStudent() {
  const streamId = parseInt($('#scoreStream').value, 10) || 0;
  const subjectSelect = $('#scoreSubject');
  const studentSelect = $('#scoreStudent');
  subjectSelect.innerHTML = '';
  studentSelect.innerHTML = '';

  state.students.filter(student => student.stream_id === streamId).forEach(student => {
    const option = document.createElement('option');
    option.value = student.id;
    option.textContent = `${student.first_name} ${student.last_name}`;
    studentSelect.appendChild(option);
  });

  if (streamId === 0) {
    subjectSelect.innerHTML = '<option value="">Select stream first</option>';
    renderScoreSummary();
    return;
  }

  try {
    const subjects = (await apiFetch(`api/subjects.php?stream_id=${streamId}`)).data;
    subjects.forEach(subject => {
      const option = document.createElement('option');
      option.value = subject.id;
      option.textContent = subject.name;
      subjectSelect.appendChild(option);
    });
  } catch (error) {
    console.warn('Unable to load subjects by stream', error);
  }

  renderScoreSummary();
}

function bindForms() {
  $$('.modal-close').forEach(button => button.addEventListener('click', () => closeAllModals()));
  $$('.modal').forEach(modal => modal.addEventListener('click', e => {
    if (e.target === modal) closeAllModals();
  }));

  $('#addStreamBtn').addEventListener('click', () => {
    $('#streamModalTitle').textContent = 'Create Stream';
    $('#streamForm').reset();
    $('#streamId').value = '';
    showModal('#streamModal');
  });

  $('#streamForm').addEventListener('submit', async e => {
    e.preventDefault();
    const name = $('#streamName').value.trim();
    if (!name) return;
    const id = $('#streamId').value;
    try {
      if (id) {
        await apiFetch('api/streams.php', { method: 'PUT', body: { id: parseInt(id, 10), name } });
      } else {
        await apiFetch('api/streams.php', { method: 'POST', body: { name } });
      }
      closeAllModals();
      loadData();
    } catch (error) {
      alert(error.message);
    }
  });

  $('#addStudentBtn').addEventListener('click', () => {
    $('#studentModalTitle').textContent = 'Register Student';
    $('#studentForm').reset();
    $('#studentId').value = '';
    showModal('#studentModal');
  });

  $('#studentForm').addEventListener('submit', async e => {
    e.preventDefault();
    const payload = {
      first_name: $('#studentFirst').value.trim(),
      last_name: $('#studentLast').value.trim(),
      admission_number: $('#studentAdm').value.trim(),
      stream_id: parseInt($('#studentStream').value, 10)
    };
    const id = $('#studentId').value;
    try {
      if (id) {
        payload.id = parseInt(id, 10);
        await apiFetch('api/students.php', { method: 'PUT', body: payload });
      } else {
        await apiFetch('api/students.php', { method: 'POST', body: payload });
      }
      closeAllModals();
      loadData();
    } catch (error) {
      alert(error.message);
    }
  });

  $('#addSubjectBtn').addEventListener('click', () => {
    $('#subjectModalTitle').textContent = 'Add Subject';
    $('#subjectForm').reset();
    $('#subjectId').value = '';
    showModal('#subjectModal');
  });

  $('#subjectForm').addEventListener('submit', async e => {
    e.preventDefault();
    const checkedBoxes = $$('.subject-stream-checkbox:checked');
    const streamIds = Array.from(checkedBoxes).map(checkbox => parseInt(checkbox.value, 10));
    const payload = {
      name: $('#subjectName').value.trim(),
      stream_ids: streamIds
    };
    const id = $('#subjectId').value;
    try {
      if (id) {
        payload.id = parseInt(id, 10);
        await apiFetch('api/subjects.php', { method: 'PUT', body: payload });
      } else {
        await apiFetch('api/subjects.php', { method: 'POST', body: payload });
      }
      closeAllModals();
      loadData();
    } catch (error) {
      alert(error.message);
    }
  });

  $('#addScoreBtn').addEventListener('click', () => {
    $('#scoreModalTitle').textContent = 'Record Score';
    $('#scoreForm').reset();
    $('#scoreId').value = '';
    $('#scoreStream').disabled = false;
    $('#scoreSubject').disabled = false;
    $('#scoreStudent').disabled = false;
    $('#scoreType').disabled = false;
    showModal('#scoreModal');
  });

  $('#scoreForm').addEventListener('submit', async e => {
    e.preventDefault();
    const id = $('#scoreId').value;
    let payload;
    if (id) {
      payload = {
        id: parseInt(id, 10),
        score: parseFloat($('#scoreValue').value)
      };
    } else {
      payload = {
        student_id: parseInt($('#scoreStudent').value, 10),
        subject_id: parseInt($('#scoreSubject').value, 10),
        assessment_type: $('#scoreType').value,
        score: parseFloat($('#scoreValue').value)
      };
    }

    try {
      if (id) {
        await apiFetch('api/scores.php', { method: 'PUT', body: payload });
      } else {
        await apiFetch('api/scores.php', { method: 'POST', body: payload });
      }
      closeAllModals();
      loadData();
      alert('Score saved successfully.');
    } catch (error) {
      alert(error.message);
    }
  });

  $('#generateReport').addEventListener('click', () => {
    const streamId = parseInt($('#reportStream').value, 10);
    const studentId = parseInt($('#reportStudent').value, 10);
    if (!streamId) {
      alert('Select a stream before generating a report.');
      return;
    }
    if (studentId) {
      window.open(`student_report.php?student_id=${studentId}`, '_blank');
    } else {
      window.open(`class_report.php?stream_id=${streamId}`, '_blank');
    }
  });

  $('#studentFilterStream')?.addEventListener('change', () => renderStudentsTable());
  $('#reportStream')?.addEventListener('change', () => populateReportStudentOptions());
  $('#scoreFilterStream')?.addEventListener('change', () => {
    renderScoreSummary();
  });
  $('#scoreFilterSubject')?.addEventListener('change', () => {
    renderScoreSummary();
  });

  $('#scoreStream').addEventListener('change', updateScoreSubjectAndStudent);
  $('#streamsList').addEventListener('click', async e => {
    const id = e.target.dataset.id;
    if (!id) return;
    const stream = state.streams.find(item => item.id === parseInt(id, 10));
    if (e.target.classList.contains('view-stream')) {
      showModal('#infoModal');
      $('#infoContent').innerHTML = buildStreamDetailsHtml(stream);
      return;
    }
    if (e.target.classList.contains('edit-stream')) {
      if (!stream) return;
      $('#streamModalTitle').textContent = 'Edit Stream';
      $('#streamName').value = stream.name;
      $('#streamId').value = stream.id;
      showModal('#streamModal');
      return;
    }
    if (e.target.classList.contains('delete-stream')) {
      if (!confirm('Delete this stream? This will remove related students and assignments.')) return;
      try {
        await apiFetch('api/streams.php', { method: 'DELETE', body: { id: parseInt(id, 10) } });
        loadData();
      } catch (error) {
        alert(error.message);
      }
    }
  });

  $('#studentsTable').addEventListener('click', async e => {
    const id = e.target.dataset.id;
    if (!id) return;
    const student = state.students.find(item => item.id === parseInt(id, 10));
    if (e.target.classList.contains('view-student')) {
      showModal('#infoModal');
      $('#infoContent').innerHTML = buildStudentDetailsHtml(student);
      return;
    }
    if (e.target.classList.contains('edit-student')) {
      if (!student) return;
      $('#studentModalTitle').textContent = 'Edit Student';
      $('#studentFirst').value = student.first_name;
      $('#studentLast').value = student.last_name;
      $('#studentAdm').value = student.admission_number;
      $('#studentStream').value = student.stream_id;
      $('#studentId').value = student.id;
      showModal('#studentModal');
      return;
    }
    if (e.target.classList.contains('delete-student')) {
      if (!confirm('Delete this student?')) return;
      try {
        await apiFetch('api/students.php', { method: 'DELETE', body: { id: parseInt(id, 10) } });
        loadData();
      } catch (error) {
        alert(error.message);
      }
    }
  });

  $('#subjectsList').addEventListener('click', async e => {
    const id = e.target.dataset.id;
    if (!id) return;
    const subject = state.subjects.find(item => item.id === parseInt(id, 10));
    if (e.target.classList.contains('edit-subject')) {
      if (!subject) return;
      $('#subjectModalTitle').textContent = 'Edit Subject';
      $('#subjectName').value = subject.name;
      $('#subjectId').value = subject.id;
      
      // Check the appropriate checkboxes
      $$('.subject-stream-checkbox').forEach(checkbox => {
        checkbox.checked = subject.stream_ids.includes(parseInt(checkbox.value, 10));
      });
      showModal('#subjectModal');
      return;
    }
    if (e.target.classList.contains('delete-subject')) {
      if (!confirm('Delete this subject?')) return;
      try {
        await apiFetch('api/subjects.php', { method: 'DELETE', body: { id: parseInt(id, 10) } });
        loadData();
      } catch (error) {
        alert(error.message);
      }
    }
  });

  $('#passwordForm').addEventListener('submit', async e => {
    e.preventDefault();
    const current = $('#currentPassword').value;
    const newPass = $('#newPassword').value;
    const confirm = $('#confirmPassword').value;
    
    if (newPass !== confirm) {
      alert('Passwords do not match.');
      return;
    }
    
    if (newPass.length < 6) {
      alert('Password must be at least 6 characters long.');
      return;
    }
    
    // Note: In production, use proper backend validation and hashing
    alert('Password change functionality requires backend implementation. Please contact your administrator.');
    $('#passwordForm').reset();
  });

  $('#scoreTable').addEventListener('click', async e => {
    const id = e.target.dataset.id;
    if (!id) return;
    const score = state.scores.find(item => item.id === parseInt(id, 10));
    if (e.target.classList.contains('edit-score')) {
      if (!score) return;
      $('#scoreModalTitle').textContent = 'Update Score';
      $('#scoreStream').disabled = true;
      $('#scoreSubject').disabled = true;
      $('#scoreStudent').disabled = true;
      $('#scoreType').disabled = true;
      $('#scoreStream').value = score.stream_id;
      await updateScoreSubjectAndStudent();
      $('#scoreSubject').value = score.subject_id;
      $('#scoreStudent').value = score.student_id;
      $('#scoreType').value = score.assessment_type;
      $('#scoreValue').value = score.score;
      $('#scoreId').value = score.id;
      showModal('#scoreModal');
      return;
    }
    if (e.target.classList.contains('delete-score')) {
      if (!confirm('Delete this score entry?')) return;
      try {
        await apiFetch('api/scores.php', { method: 'DELETE', body: { id: parseInt(id, 10) } });
        loadData();
      } catch (error) {
        alert(error.message);
      }
    }
  });
}

document.addEventListener('DOMContentLoaded', init);

