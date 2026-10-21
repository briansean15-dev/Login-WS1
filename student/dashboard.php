<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../db.php';

$currentUser = require_role($pdo, 'student', '../index.php');

function student_grade_status($score)
{
    if ($score >= 90) {
        return ['label' => 'Excellent', 'class' => 'excellent'];
    }

    if ($score >= 85) {
        return ['label' => 'Very Good', 'class' => 'very-good'];
    }

    if ($score >= 75) {
        return ['label' => 'Passing', 'class' => 'passing'];
    }

    return ['label' => 'Needs Support', 'class' => 'needs-support'];
}

$gradesError = '';
$studentGrades = [];
try {
    $gradesStmt = $pdo->prepare("
        SELECT subject, assessment, score, feedback, graded_at
        FROM grades
        WHERE student_id = ?
        ORDER BY graded_at DESC, id DESC
    ");
    $gradesStmt->execute([$currentUser['id']]);
    $studentGrades = $gradesStmt->fetchAll();
} catch (PDOException $e) {
    $gradesError = 'Grades are temporarily unavailable. Please try again later.';
}

$gradeCount = count($studentGrades);
$averageGrade = $gradeCount > 0 ? array_sum(array_column($studentGrades, 'score')) / $gradeCount : null;

$courseCatalog = [
    [
        'title' => 'Introduction to Web Development',
        'teacher' => 'Ms. Rivera',
        'schedule' => 'Mon and Wed',
        'description' => 'Build simple pages with HTML, CSS, and PHP basics.',
        'next_lesson' => 'HTML forms and input validation'
    ],
    [
        'title' => 'Database Fundamentals',
        'teacher' => 'Mr. Cruz',
        'schedule' => 'Tue and Thu',
        'description' => 'Learn tables, records, relationships, and SQL queries.',
        'next_lesson' => 'Selecting and filtering student records'
    ],
    [
        'title' => 'Computer Systems',
        'teacher' => 'Ms. Tan',
        'schedule' => 'Friday',
        'description' => 'Explore hardware, software, networks, and troubleshooting.',
        'next_lesson' => 'Operating system file management'
    ],
    [
        'title' => 'Programming Logic',
        'teacher' => 'Mr. Lim',
        'schedule' => 'Mon and Thu',
        'description' => 'Practice conditions, loops, arrays, and problem solving.',
        'next_lesson' => 'Using loops for repeated tasks'
    ],
    [
        'title' => 'Digital Literacy',
        'teacher' => 'Ms. Santos',
        'schedule' => 'Wednesday',
        'description' => 'Use online tools safely, responsibly, and productively.',
        'next_lesson' => 'Checking reliable sources online'
    ],
    [
        'title' => 'Application Design',
        'teacher' => 'Mr. Reyes',
        'schedule' => 'Tuesday',
        'description' => 'Plan screens, layouts, and user flows for simple apps.',
        'next_lesson' => 'Designing a dashboard layout'
    ]
];

$materialCatalog = [
    [
        'course' => 'Introduction to Web Development',
        'title' => 'HTML Login Form Guide',
        'type' => 'PDF',
        'detail' => 'Read before the next activity'
    ],
    [
        'course' => 'Introduction to Web Development',
        'title' => 'CSS Dashboard Styling Checklist',
        'type' => 'Worksheet',
        'detail' => 'Practice layout and spacing'
    ],
    [
        'course' => 'Database Fundamentals',
        'title' => 'SQL SELECT Practice Set',
        'type' => 'Exercise',
        'detail' => 'Due this week'
    ],
    [
        'course' => 'Computer Systems',
        'title' => 'Basic Troubleshooting Notes',
        'type' => 'Notes',
        'detail' => 'Review for quiz'
    ],
    [
        'course' => 'Programming Logic',
        'title' => 'Loops and Arrays Examples',
        'type' => 'Code',
        'detail' => 'Try the sample problems'
    ],
    [
        'course' => 'Digital Literacy',
        'title' => 'Online Safety Quick Review',
        'type' => 'Slides',
        'detail' => 'Available now'
    ],
    [
        'course' => 'Application Design',
        'title' => 'Simple Wireframe Template',
        'type' => 'Template',
        'detail' => 'Use for your mini project'
    ],
    [
        'course' => 'Database Fundamentals',
        'title' => 'Entity Relationship Diagram Sample',
        'type' => 'Image',
        'detail' => 'Study reference'
    ]
];

function random_student_items($items, $count)
{
    shuffle($items);
    return array_slice($items, 0, min($count, count($items)));
}

if (
    empty($_SESSION['student_dashboard_user_id']) ||
    $_SESSION['student_dashboard_user_id'] !== $currentUser['id'] ||
    empty($_SESSION['student_courses']) ||
    empty($_SESSION['student_materials'])
) {
    $studentCourses = random_student_items($courseCatalog, 3);

    foreach ($studentCourses as $index => $course) {
        $studentCourses[$index]['progress'] = random_int(20, 85);
    }

    $_SESSION['student_dashboard_user_id'] = $currentUser['id'];
    $_SESSION['student_courses'] = $studentCourses;
    $_SESSION['student_materials'] = random_student_items($materialCatalog, 4);
}

$studentCourses = $_SESSION['student_courses'];
$studentMaterials = $_SESSION['student_materials'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <p class="eyebrow">Student Dashboard</p>
                <h1>Welcome, <?php echo e($currentUser['name']); ?></h1>
            </div>
            <a class="logout-btn" href="../index.php?logout=true">Logout</a>
        </div>
        <hr>

        <div class="summary-card">
            <h2>Your Account</h2>
            <p><strong>Username:</strong> <?php echo e($currentUser['username']); ?></p>
            <p><strong>Email:</strong> <?php echo e($currentUser['email']); ?></p>
            <p><strong>Role:</strong> <?php echo e($currentUser['role']); ?></p>
        </div>

        <h2 id="my-grades">My Grades</h2>
        <?php if ($gradesError !== ''): ?>
            <p class="empty-state" role="alert"><?php echo e($gradesError); ?></p>
        <?php elseif (empty($studentGrades)): ?>
            <p class="empty-state">You do not have any grades yet. Your teacher's posted grades will appear here.</p>
        <?php else: ?>
            <div class="grade-summary">
                <div>
                    <span>Assessments graded</span>
                    <strong><?php echo e($gradeCount); ?></strong>
                </div>
                <div>
                    <span>Average score</span>
                    <strong><?php echo e(rtrim(rtrim(number_format((float) $averageGrade, 2, '.', ''), '0'), '.')); ?>%</strong>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Assessment</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Teacher Feedback</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentGrades as $grade): ?>
                            <?php $gradeStatus = student_grade_status((float) $grade['score']); ?>
                            <tr>
                                <td><?php echo e($grade['subject']); ?></td>
                                <td><?php echo e($grade['assessment']); ?></td>
                                <td><strong><?php echo e(rtrim(rtrim(number_format((float) $grade['score'], 2, '.', ''), '0'), '.')); ?>%</strong></td>
                                <td><span class="grade-badge <?php echo e($gradeStatus['class']); ?>"><?php echo e($gradeStatus['label']); ?></span></td>
                                <td class="grade-feedback"><?php echo e($grade['feedback'] !== null && $grade['feedback'] !== '' ? $grade['feedback'] : '-'); ?></td>
                                <td><?php echo e(date('M j, Y', strtotime($grade['graded_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2>Your Courses</h2>
        <div class="course-grid">
            <?php foreach ($studentCourses as $course): ?>
            <article class="course-card">
                <div class="course-card-top">
                    <span class="course-label"><?php echo e($course['schedule']); ?></span>
                    <span class="course-progress"><?php echo e($course['progress']); ?>%</span>
                </div>
                <h3><?php echo e($course['title']); ?></h3>
                <p class="course-teacher"><?php echo e($course['teacher']); ?></p>
                <p><?php echo e($course['description']); ?></p>
                <div class="progress-track">
                    <span style="width: <?php echo e($course['progress']); ?>%;"></span>
                </div>
                <p class="next-lesson"><strong>Next:</strong> <?php echo e($course['next_lesson']); ?></p>
            </article>
            <?php endforeach; ?>
        </div>

        <h2>Learning Materials</h2>
        <div class="material-list">
            <?php foreach ($studentMaterials as $material): ?>
            <div class="material-item">
                <div>
                    <span class="material-type"><?php echo e($material['type']); ?></span>
                    <h3><?php echo e($material['title']); ?></h3>
                    <p><?php echo e($material['course']); ?></p>
                </div>
                <span class="material-detail"><?php echo e($material['detail']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
