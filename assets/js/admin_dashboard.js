function showContent(sectionId, element) {
    // Hide all content sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => section.classList.remove('active'));

    // Remove 'active' class from all nav links' parent li in the sidebar
    const navLinks = document.querySelectorAll('.sidebar .menu-items a');
    navLinks.forEach(link => link.closest('li').classList.remove('active'));

    // Show the selected content section
    const activeSection = document.getElementById(sectionId);
    if (activeSection) {
        activeSection.classList.add('active');
        // Update the main header title
        const headerTitle = document.getElementById('main-header-title');
        const sectionTitle = activeSection.querySelector('h2');
        if(headerTitle && sectionTitle) {
            headerTitle.innerHTML = sectionTitle.innerHTML;
        }
    }

    // Add 'active' class to the clicked nav link's parent li
    if (element) {
        element.classList.add('active');
        // Save the active tab to session storage to remember it
        sessionStorage.setItem('adminActiveTab', sectionId);
    }
}

function initializeDashboardView() {
    // Check for a tab in the URL first, then session storage, then default
    const urlParams = new URLSearchParams(window.location.search);
    const tabFromUrl = urlParams.get('tab');

    const activeTabId = tabFromUrl || sessionStorage.getItem('adminActiveTab') || 'dashboard-home';
    const activeLink = document.querySelector(`a[onclick*="'${activeTabId}'"]`);

    if (activeLink) {
        showContent(activeTabId, activeLink.parentElement);
    } else {
        // If the intended link doesn't exist (e.g. no permission), click the first available one.
        document.querySelector('.sidebar .menu-items a')?.click();
    }
}

// --- Modal Logic ---
function openEditSubjectModal(subjectData) {
    // Populate the form fields
    document.getElementById('edit_subject_id').value = subjectData.subject_id;
    document.getElementById('edit_subject_code').value = subjectData.subject_code;
    document.getElementById('edit_subject_name').value = subjectData.subject_name;
    document.getElementById('edit_units').value = subjectData.units;
    document.getElementById('edit_course_id').value = subjectData.course_id;
    document.getElementById('edit_semester').value = subjectData.semester;
    document.getElementById('edit_status').value = subjectData.status;

    document.getElementById('editSubjectModal').style.display = 'flex';
}

function openEditScheduleModal(scheduleData) {
    // Populate the form fields
    document.getElementById('edit_schedule_id').value = scheduleData.schedule_id;
    document.getElementById('edit_sched_subject_id').value = scheduleData.subject_id;
    document.getElementById('edit_sched_instructor_id').value = scheduleData.instructor_id;
    document.getElementById('edit_sched_room_id').value = scheduleData.room_id;
    document.getElementById('edit_sched_day').value = scheduleData.day;
    
    // Convert 'hh:mm AM/PM' to 'HH:mm' for time input
    const timeStart = new Date('1970-01-01 ' + scheduleData.time_start_formatted).toTimeString().substring(0,5);
    const timeEnd = new Date('1970-01-01 ' + scheduleData.time_end_formatted).toTimeString().substring(0,5);
    document.getElementById('edit_sched_time_start').value = timeStart;
    document.getElementById('edit_sched_time_end').value = timeEnd;

    document.getElementById('edit_sched_section_id').value = scheduleData.section_id;
    document.getElementById('edit_sched_school_year').value = scheduleData.school_year;
    document.getElementById('edit_sched_semester').value = scheduleData.semester;

    document.getElementById('editScheduleModal').style.display = 'flex';
}

function openAddScheduleModal() {
    document.getElementById('addScheduleForm').reset();
    // Reset all select2 fields in this modal
    $('#add_sched_subject_id').val(null).trigger('change');
    $('#add_sched_section_id').val(null).trigger('change');
    $('#add_sched_instructor_id').val(null).trigger('change');
    $('#add_sched_room_id').val(null).trigger('change');
    document.getElementById('addScheduleModal').style.display = 'flex';
}
function openAddInstructorModal() {
    document.getElementById('addInstructorForm').reset(); // Clear form on open
    $('#add_instructor_department').val(null).trigger('change'); // Reset select2
    document.getElementById('addInstructorModal').style.display = 'flex'; // Use flex to enable centering
}

function openAddStudentModal() {
    document.getElementById('addStudentForm').reset();
    $('#add_student_course').val(null).trigger('change'); // Reset select2
    $('#add_student_section').val(null).trigger('change'); // Reset select2
    document.getElementById('addStudentModal').style.display = 'flex';
}

function openAddSubjectModal() {
    document.getElementById('addSubjectForm').reset();
    $('#add_subject_course').val(null).trigger('change'); // Reset select2
    document.getElementById('addSubjectModal').style.display = 'flex';
}

function openAddRoomModal() {
    document.getElementById('addRoomForm').reset();
    $('#add_room_department').val(null).trigger('change'); // Reset select2
    document.getElementById('addRoomModal').style.display = 'flex';
}

function openAddSectionModal() {
    document.getElementById('addSectionForm').reset();
    $('#add_section_course').val(null).trigger('change'); // Reset select2
    document.getElementById('addSectionModal').style.display = 'flex';
}

function openAddCourseModal() {
    document.getElementById('addCourseForm').reset();
    $('#add_course_department').val(null).trigger('change'); // Reset select2
    document.getElementById('addCourseModal').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal if clicking outside of it
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    for (const modal of modals) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
});

// Initialize Select2 searchable dropdowns
$(document).ready(function() {
  initializeDashboardView();
  window.addEventListener('pageshow', function(event) {
      if (event.persisted) {
          initializeDashboardView();
      }
  });

  // Helper function to initialize Select2
  const initSelect2 = (selector, modalId, placeholder) => {
    $(selector).select2({
      dropdownParent: $(modalId),
      placeholder: placeholder,
      width: '100%'
    });
  };

  initSelect2('#add_student_course', '#addStudentModal', 'Search for a course');
  initSelect2('#add_student_section', '#addStudentModal', 'Search for a section');
  initSelect2('#add_subject_course', '#addSubjectModal', 'Search for a course');
  initSelect2('#add_sched_subject_id', '#addScheduleModal', 'Search for a subject');
  initSelect2('#add_sched_instructor_id', '#addScheduleModal', 'Search for an instructor');
  initSelect2('#add_sched_room_id', '#addScheduleModal', 'Search for a room');
  initSelect2('#add_sched_section_id', '#addScheduleModal', 'Search for a section');
  initSelect2('#add_section_course', '#addSectionModal', 'Search for a course');
  initSelect2('#add_room_department', '#addRoomModal', 'Search for a department');
  initSelect2('#add_instructor_department', '#addInstructorModal', 'Search for a department');
  initSelect2('#add_course_department', '#addCourseModal', 'Search for a department');

  // Accordion for department lists (instructors, rooms, etc.)
  document.querySelectorAll('.department-header').forEach(header => {
    header.addEventListener('click', () => {
      const content = header.nextElementSibling;
      const icon = header.querySelector('i');
      content.style.display = content.style.display === 'block' ? 'none' : 'block';
      icon.classList.toggle('fa-chevron-down');
      icon.classList.toggle('fa-chevron-up');
    });
  });

  // Accordion for course lists in Manage Subjects
  document.querySelectorAll('.course-group-header').forEach(header => {
    header.addEventListener('click', () => {
      const content = header.nextElementSibling;
      const icon = header.querySelector('i');
      content.style.display = content.style.display === 'block' ? 'none' : 'block';
      icon.classList.toggle('fa-chevron-down');
      icon.classList.toggle('fa-chevron-up');
    });
  });

  // Accordion for semester lists within courses in Manage Subjects
  document.querySelectorAll('.semester-group-header').forEach(header => {
    header.addEventListener('click', () => {
      const content = header.nextElementSibling;
      const icon = header.querySelector('i');
      content.style.display = content.style.display === 'block' ? 'none' : 'block';
      icon.classList.toggle('fa-chevron-down');
      icon.classList.toggle('fa-chevron-up');
    });
  });
});