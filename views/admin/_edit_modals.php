<!-- Edit Subject Modal -->
<div id="editSubjectModal" class="modal">
    <div class="modal-content modal-lg">
        <span class="close-btn" onclick="closeModal('editSubjectModal')">&times;</span>
        <h2><i class="fa-solid fa-pencil"></i> Edit Subject</h2>
        <form id="editSubjectForm" action="../actions/edit_subject.php" method="POST" class="modal-form">
            <input type="hidden" id="edit_subject_id" name="subject_id">
            
            <div class="form-grid">
                <div class="input-group">
                    <label for="edit_subject_code">Subject Code:</label>
                    <input type="text" id="edit_subject_code" name="subject_code" required>
                </div>
                <div class="input-group">
                    <label for="edit_subject_name">Subject Name:</label>
                    <input type="text" id="edit_subject_name" name="subject_name" required>
                </div>
                <div class="input-group">
                    <label for="edit_units">Units:</label>
                    <input type="number" id="edit_units" name="units" required min="1" max="6">
                </div>
                <div class="input-group">
                    <label for="edit_course_id">Course:</label>
                    <select id="edit_course_id" name="course_id" required>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['course_code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="edit_year_level">Year Level:</label>
                    <select id="edit_year_level" name="year_level" required>
                        <option value="1st">1st Year</option>
                        <option value="2nd">2nd Year</option>
                        <option value="3rd">3rd Year</option>
                        <option value="4th">4th Year</option>
                        <option value="5th">5th Year</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="edit_semester">Semester:</label>
                    <select id="edit_semester" name="semester" required>
                        <option value="1st">1st</option>
                        <option value="2nd">2nd</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="edit_status">Status:</label>
                    <select id="edit_status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="button"><i class="fa-solid fa-save"></i> Save Changes</button>
        </form>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div id="editScheduleModal" class="modal">
    <div class="modal-content modal-xl">
        <span class="close-btn" onclick="closeModal('editScheduleModal')">&times;</span>
        <h2><i class="fa-solid fa-pencil"></i> Edit Schedule</h2>
        <form id="editScheduleForm" action="../actions/edit_schedule.php" method="POST" class="modal-form">
            <input type="hidden" id="edit_schedule_id" name="schedule_id">
            
            <div class="form-grid-auto">
                <div class="input-group">
                    <label for="edit_sched_subject_id">Subject:</label>
                    <select id="edit_sched_subject_id" name="subject_id" required>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['subject_id'] ?>"><?= htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="edit_sched_instructor_id">Instructor:</label>
                    <select id="edit_sched_instructor_id" name="instructor_id" required>
                        <?php foreach ($all_instructors as $instructor): ?>
                            <option value="<?= $instructor['instructor_id'] ?>"><?= htmlspecialchars($instructor['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="edit_sched_room_id">Room:</label>
                    <select id="edit_sched_room_id" name="room_id" required>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= $room['room_id'] ?>"><?= htmlspecialchars($room['room_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="edit_sched_day">Day:</label>
                    <select id="edit_sched_day" name="day" required>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                    </select>
                </div>
                <!-- Add other fields like time, school year, semester as needed -->
            </div>
            <button type="submit" class="button"><i class="fa-solid fa-save"></i> Save Changes</button>
        </form>
    </div>
</div>