<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../db.php';

$currentUser = require_role($pdo, 'teacher', '../index.php');

try {
    $stmt = $pdo->prepare("SELECT id, username, name, email, created_at FROM users WHERE role = 'student' ORDER BY name ASC");
    $stmt->execute();
    $students = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$subjectCatalog = [
    [
        'name' => 'Web Development',
        'code' => 'WD101',
        'schedule' => 'Mon and Wed',
        'room' => 'Computer Lab 1',
        'focus' => 'HTML, CSS, PHP, and forms'
    ],
    [
        'name' => 'Database Fundamentals',
        'code' => 'DB102',
        'schedule' => 'Tue and Thu',
        'room' => 'Computer Lab 2',
        'focus' => 'Tables, records, SQL, and relationships'
    ],
    [
        'name' => 'Programming Logic',
        'code' => 'PL103',
        'schedule' => 'Monday',
        'room' => 'Room 204',
        'focus' => 'Conditions, loops, arrays, and problem solving'
    ],
    [
        'name' => 'Computer Systems',
        'code' => 'CS104',
        'schedule' => 'Friday',
        'room' => 'Room 105',
        'focus' => 'Hardware, software, networks, and troubleshooting'
    ],
    [
        'name' => 'Digital Literacy',
        'code' => 'DL105',
        'schedule' => 'Wednesday',
        'room' => 'Library Lab',
        'focus' => 'Online safety, productivity, and research'
    ]
];

$courseCatalog = [
    [
        'title' => 'Build a Login System',
        'level' => 'Beginner',
        'module' => 'Web Development',
        'next_activity' => 'Validate login form input'
    ],
    [
        'title' => 'Student Record Database',
        'level' => 'Intermediate',
        'module' => 'Database Fundamentals',
        'next_activity' => 'Create student grade queries'
    ],
    [
        'title' => 'Programming Practice Lab',
        'level' => 'Beginner',
        'module' => 'Programming Logic',
        'next_activity' => 'Solve loop exercises'
    ],
    [
        'title' => 'Computer Maintenance Basics',
        'level' => 'Beginner',
        'module' => 'Computer Systems',
        'next_activity' => 'Review troubleshooting checklist'
    ],
    [
        'title' => 'Responsible Online Research',
        'level' => 'Beginner',
        'module' => 'Digital Literacy',
        'next_activity' => 'Compare reliable sources'
    ]
];

$taskCatalog = [
    [
        'title' => 'Check submitted login forms',
        'type' => 'Review',
        'due' => 'Today'
    ],
    [
        'title' => 'Prepare quiz questions',
        'type' => 'Planning',
        'due' => 'Tomorrow'
    ],
    [
        'title' => 'Update course materials',
        'type' => 'Materials',
        'due' => 'Friday'
    ],
    [
        'title' => 'Record student lab grades',
        'type' => 'Grades',
        'due' => 'Today'
    ],
    [
        'title' => 'Review attendance notes',
        'type' => 'Classroom',
        'due' => 'This week'
    ],
    [
        'title' => 'Give feedback on mini projects',
        'type' => 'Feedback',
        'due' => 'Next week'
    ]
];

function random_teacher_items($items, $count)
{
    shuffle($items);
    return array_slice($items, 0, min($count, count($items)));
}

function teacher_grade_status($grade)
{
    if ($grade >= 90) {
        return ['label' => 'Excellent', 'class' => 'excellent'];
    }

    if ($grade >= 85) {
        return ['label' => 'Very Good', 'class' => 'very-good'];
    }

    if ($grade >= 75) {
        return ['label' => 'Passing', 'class' => 'passing'];
    }

    return ['label' => 'Needs Support', 'class' => 'needs-support'];
}

$studentIds = [];
foreach ($students as $student) {
    $studentIds[] = $student['id'];
}
$studentKey = implode('-', $studentIds);

if (
    empty($_SESSION['teacher_dashboard_user_id']) ||
    $_SESSION['teacher_dashboard_user_id'] !== $currentUser['id'] ||
    ($_SESSION['teacher_student_key'] ?? '') !== $studentKey ||
    empty($_SESSION['teacher_subjects']) ||
    empty($_SESSION['teacher_courses']) ||
    empty($_SESSION['teacher_tasks'])
) {
    $teacherSubjects = random_teacher_items($subjectCatalog, 3);
    $teacherCourses = random_teacher_items($courseCatalog, 3);
    $studentGrades = [];

    foreach ($teacherSubjects as $index => $subject) {
        $teacherSubjects[$index]['students'] = count($students);
    }

    foreach ($teacherCourses as $index => $course) {
        $teacherCourses[$index]['progress'] = random_int(25, 90);
        $teacherCourses[$index]['materials'] = random_int(4, 12);
    }

    foreach ($students as $student) {
        $subject = $teacherSubjects[array_rand($teacherSubjects)];
        $course = $teacherCourses[array_rand($teacherCourses)];
        $grade = random_int(72, 99);
        $status = teacher_grade_status($grade);

        $studentGrades[] = [
            'name' => $student['name'],
            'username' => $student['username'],
            'subject' => $subject['name'],
            'course' => $course['title'],
            'grade' => $grade,
            'status' => $status['label'],
            'status_class' => $status['class']
        ];
    }

    $_SESSION['teacher_dashboard_user_id'] = $currentUser['id'];
    $_SESSION['teacher_student_key'] = $studentKey;
    $_SESSION['teacher_subjects'] = $teacherSubjects;
    $_SESSION['teacher_courses'] = $teacherCourses;
    $_SESSION['teacher_grades'] = $studentGrades;
    $_SESSION['teacher_tasks'] = random_teacher_items($taskCatalog, 5);
}

$teacherSubjects = $_SESSION['teacher_subjects'];
$teacherCourses = $_SESSION['teacher_courses'];
$studentGrades = $_SESSION['teacher_grades'] ?? [];
$teacherTasks = $_SESSION['teacher_tasks'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <p class="eyebrow">Teacher Dashboard</p>
                <h1>Welcome, <?php echo e($currentUser['name']); ?></h1>
            </div>
            <a class="logout-btn" href="../index.php?logout=true">Logout</a>
        </div>
        <hr>

        <div class="metric-grid">
            <div class="metric-card">
                <span>Subjects</span>
                <strong><?php echo e(count($teacherSubjects)); ?></strong>
            </div>
            <div class="metric-card">
                <span>Students</span>
                <strong><?php echo e(count($students)); ?></strong>
            </div>
            <div class="metric-card">
                <span>Courses</span>
                <strong><?php echo e(count($teacherCourses)); ?></strong>
            </div>
            <div class="metric-card">
                <span>Tasks</span>
                <strong><?php echo e(count($teacherTasks)); ?></strong>
            </div>
        </div>

        <h2>Assigned Subjects</h2>
        <div class="subject-grid">
            <?php foreach ($teacherSubjects as $subject): ?>
            <article class="subject-card">
                <div class="card-top">
                    <span class="pill"><?php echo e($subject['code']); ?></span>
                    <span class="muted-pill"><?php echo e($subject['schedule']); ?></span>
                </div>
                <h3><?php echo e($subject['name']); ?></h3>
                <p><?php echo e($subject['focus']); ?></p>
                <p><strong>Room:</strong> <?php echo e($subject['room']); ?></p>
                <p><strong>Students:</strong> <?php echo e($subject['students']); ?></p>
            </article>
            <?php endforeach; ?>
        </div>

        <h2>Courses You Handle</h2>
        <div class="course-list">
            <?php foreach ($teacherCourses as $course): ?>
            <div class="course-item">
                <div class="course-main">
                    <span class="pill"><?php echo e($course['level']); ?></span>
                    <h3><?php echo e($course['title']); ?></h3>
                    <p><?php echo e($course['module']); ?> - <?php echo e($course['next_activity']); ?></p>
                    <div class="progress-track">
                        <span style="width: <?php echo e($course['progress']); ?>%;"></span>
                    </div>
                </div>
                <div class="course-meta">
                    <strong><?php echo e($course['progress']); ?>%</strong>
                    <span><?php echo e($course['materials']); ?> materials</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <h2>Student Grades</h2>
        <?php if (empty($studentGrades)): ?>
            <p class="empty-state">No student grades available yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Student</th>
                        <th>Subject</th>
                        <th>Course</th>
                        <th>Grade</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach ($studentGrades as $gradeRow): ?>
                    <tr>
                        <td>
                            <?php echo e($gradeRow['name']); ?>
                            <span class="table-subtext"><?php echo e($gradeRow['username']); ?></span>
                        </td>
                        <td><?php echo e($gradeRow['subject']); ?></td>
                        <td><?php echo e($gradeRow['course']); ?></td>
                        <td><strong><?php echo e($gradeRow['grade']); ?></strong></td>
                        <td><span class="grade-badge <?php echo e($gradeRow['status_class']); ?>"><?php echo e($gradeRow['status']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>

        <h2>Teacher Tasks</h2>
        <div class="task-list">
            <?php foreach ($teacherTasks as $task): ?>
            <div class="task-item">
                <div>
                    <span class="pill"><?php echo e($task['type']); ?></span>
                    <h3><?php echo e($task['title']); ?></h3>
                </div>
                <span class="muted-pill"><?php echo e($task['due']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <h2>Enrolled Students</h2>
        <?php if (empty($students)): ?>
            <p class="empty-state">No students registered yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Created</th>
                    </tr>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo e($student['name']); ?></td>
                        <td><?php echo e($student['username']); ?></td>
                        <td><?php echo e($student['email']); ?></td>
                        <td><?php echo e($student['created_at']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
