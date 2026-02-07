<?php
// Central definition of all permissions in the system.
// This is the single source of truth for role permissions.
$role_specific_permissions = [
    'admin' => [
        'Dashboard & Core' => [
            'view_admin_dashboard' => 'View Admin Dashboard & Statistics',
            'manage_announcements' => 'Create, Edit, and Delete Announcements',
        ],
        'User Management' => [
            'manage_instructors' => 'Manage Instructor Accounts (Add, Edit, Deactivate)',
            'manage_students' => 'Manage Student Accounts (Add, Edit, Deactivate)',
        ],
        'Academic Management' => [
            'manage_courses' => 'Manage Courses',
            'manage_subjects' => 'Manage Subjects',
            'manage_rooms_sections' => 'Manage Rooms & Sections',
            'manage_schedules' => 'Create and Assign Class Schedules',
            'manage_enrollments' => 'Approve or Reject Student Enrollments',
        ],
    ],
    'instructor' => [
        'Dashboard & Classes' => [
            'view_instructor_dashboard' => 'View Instructor Dashboard',
            'view_instructor_schedule' => 'View Own Teaching Schedule',
            'view_class_lists' => 'View Student Rosters for Assigned Classes',
        ],
        'Core Functions' => [
            'manage_grades' => 'Enter and Edit Grades for Assigned Classes',
            'view_announcements' => 'View System Announcements', // Shared key
            'edit_instructor_profile' => 'Edit Own Profile Information',
        ],
    ],
    'student' => [
        'Dashboard & Academics' => [
            'view_student_dashboard' => 'View Student Dashboard',
            'submit_enrollment' => 'Access Enrollment and Submit Requests',
            'view_student_schedule' => 'View Own Class Schedule',
            'view_grades' => 'View Own Grades',
            'edit_student_profile' => 'Edit Personal Information and Profile',
        ],
    ],
];
?>