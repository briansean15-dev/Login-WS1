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

if (empty($_SESSION['grade_csrf_token'])) {
    $_SESSION['grade_csrf_token'] = bin2hex(random_bytes(32));
}

$gradeFormError = '';
$gradeFormSuccess = $_SESSION['grade_form_success'] ?? '';
unset($_SESSION['grade_form_success']);
$gradeFormValues = [
    'student_id' => '',
    'subject' => '',
    'assessment' => '',
    'score' => '',
    'feedback' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_grade') {
    $postedText = static function ($key) {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    };
    $gradeFormValues = [
        'student_id' => $postedText('student_id'),
        'subject' => $postedText('subject'),
        'assessment' => $postedText('assessment'),
        'score' => $postedText('score'),
        'feedback' => $postedText('feedback')
    ];
    $submittedCsrfToken = $_POST['csrf_token'] ?? '';
    $studentId = filter_var($gradeFormValues['student_id'], FILTER_VALIDATE_INT);
    $studentIds = array_map('intval', array_column($students, 'id'));
    $subjectNames = array_column($subjectCatalog, 'name');

    if (!is_string($submittedCsrfToken) || !hash_equals($_SESSION['grade_csrf_token'], $submittedCsrfToken)) {
        $gradeFormError = 'Your form expired. Please review the details and submit again.';
    } elseif (!$studentId || !in_array($studentId, $studentIds, true)) {
        $gradeFormError = 'Choose a registered student.';
    } elseif (!in_array($gradeFormValues['subject'], $subjectNames, true)) {
        $gradeFormError = 'Choose a valid subject.';
    } elseif ($gradeFormValues['assessment'] === '' || strlen($gradeFormValues['assessment']) > 150) {
        $gradeFormError = 'Enter an assessment name of 1 to 150 characters.';
    } elseif (!is_numeric($gradeFormValues['score']) || (float) $gradeFormValues['score'] < 0 || (float) $gradeFormValues['score'] > 100) {
        $gradeFormError = 'Enter a score from 0 to 100.';
    } elseif (strlen($gradeFormValues['feedback']) > 5000) {
        $gradeFormError = 'Feedback must be 5,000 characters or fewer.';
    } else {
        try {
            $insertGrade = $pdo->prepare("
                INSERT INTO grades (student_id, teacher_id, subject, assessment, score, feedback)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insertGrade->execute([
                $studentId,
                $currentUser['id'],
                $gradeFormValues['subject'],
                $gradeFormValues['assessment'],
                (float) $gradeFormValues['score'],
                $gradeFormValues['feedback'] !== '' ? $gradeFormValues['feedback'] : null
            ]);

            $_SESSION['grade_form_success'] = 'Grade saved. The student can now see it in their dashboard.';
            $_SESSION['grade_csrf_token'] = bin2hex(random_bytes(32));
            header('Location: dashboard.php#student-grades');
            exit();
        } catch (PDOException $e) {
            $gradeFormError = 'The grade could not be saved. Please try again.';
        }
    }

    $_SESSION['grade_csrf_token'] = bin2hex(random_bytes(32));
}

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

if (
    empty($_SESSION['teacher_dashboard_user_id']) ||
    $_SESSION['teacher_dashboard_user_id'] !== $currentUser['id'] ||
    empty($_SESSION['teacher_subjects']) ||
    empty($_SESSION['teacher_courses']) ||
    empty($_SESSION['teacher_tasks'])
) {
    $teacherSubjects = random_teacher_items($subjectCatalog, 3);
    $teacherCourses = random_teacher_items($courseCatalog, 3);

    foreach ($teacherSubjects as $index => $subject) {
        $teacherSubjects[$index]['students'] = count($students);
    }

    foreach ($teacherCourses as $index => $course) {
        $teacherCourses[$index]['progress'] = random_int(25, 90);
        $teacherCourses[$index]['materials'] = random_int(4, 12);
    }

    $_SESSION['teacher_dashboard_user_id'] = $currentUser['id'];
    $_SESSION['teacher_subjects'] = $teacherSubjects;
    $_SESSION['teacher_courses'] = $teacherCourses;
    $_SESSION['teacher_tasks'] = random_teacher_items($taskCatalog, 5);
}

$teacherSubjects = $_SESSION['teacher_subjects'];
$teacherCourses = $_SESSION['teacher_courses'];
$teacherTasks = $_SESSION['teacher_tasks'];

$gradesStmt = $pdo->query("
    SELECT g.id, g.student_id, g.subject, g.assessment, g.score, g.feedback, g.graded_at,
           s.name, s.username
    FROM grades g
    INNER JOIN users s ON s.id = g.student_id
    ORDER BY g.graded_at DESC, g.id DESC
");
$studentGrades = $gradesStmt->fetchAll();
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

        <h2 id="student-grades">Student Grades</h2>
        <?php if ($gradeFormSuccess !== ''): ?>
            <p class="form-success" role="status"><?php echo e($gradeFormSuccess); ?></p>
        <?php endif; ?>
        <?php if ($gradeFormError !== ''): ?>
            <p class="form-error" role="alert"><?php echo e($gradeFormError); ?></p>
        <?php endif; ?>
        <?php if (empty($students)): ?>
            <p class="empty-state">Register a student before recording grades.</p>
        <?php else: ?>
            <form class="grade-form" action="dashboard.php#student-grades" method="post">
                <input type="hidden" name="action" value="add_grade">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['grade_csrf_token']); ?>">
                <div class="grade-form-grid">
                    <label>
                        Student
                        <select name="student_id" required>
                            <option value="">Choose a student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo e($student['id']); ?>" <?php echo (string) $gradeFormValues['student_id'] === (string) $student['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($student['name']); ?> (<?php echo e($student['username']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Subject
                        <select name="subject" required>
                            <option value="">Choose a subject</option>
                            <?php foreach ($subjectCatalog as $subject): ?>
                                <option value="<?php echo e($subject['name']); ?>" <?php echo $gradeFormValues['subject'] === $subject['name'] ? 'selected' : ''; ?>><?php echo e($subject['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Assessment
                        <input type="text" name="assessment" maxlength="150" value="<?php echo e($gradeFormValues['assessment']); ?>" placeholder="e.g. Unit 1 quiz" required>
                    </label>
                    <label>
                        Score (%)
                        <input type="number" name="score" min="0" max="100" step="0.01" value="<?php echo e($gradeFormValues['score']); ?>" placeholder="0 to 100" required>
                    </label>
                    <label class="grade-feedback-field">
                        Feedback <span>(optional)</span>
                        <textarea name="feedback" rows="3" maxlength="5000" placeholder="Add a note for the student"><?php echo e($gradeFormValues['feedback']); ?></textarea>
                    </label>
                </div>
                <button class="grade-submit" type="submit">Save Grade</button>
            </form>
        <?php endif; ?>

        <?php if (empty($studentGrades)): ?>
            <p class="empty-state">No grades have been recorded yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Student</th>
                        <th>Subject</th>
                        <th>Assessment</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th>Feedback</th>
                        <th>Recorded</th>
                    </tr>
                    <?php foreach ($studentGrades as $gradeRow): ?>
                    <tr>
                        <td>
                            <?php echo e($gradeRow['name']); ?>
                            <span class="table-subtext"><?php echo e($gradeRow['username']); ?></span>
                        </td>
                        <td><?php echo e($gradeRow['subject']); ?></td>
                        <td><?php echo e($gradeRow['assessment']); ?></td>
                        <td><strong><?php echo e(rtrim(rtrim(number_format((float) $gradeRow['score'], 2, '.', ''), '0'), '.')); ?>%</strong></td>
                        <?php $gradeStatus = teacher_grade_status((float) $gradeRow['score']); ?>
                        <td><span class="grade-badge <?php echo e($gradeStatus['class']); ?>"><?php echo e($gradeStatus['label']); ?></span></td>
                        <td><?php echo e($gradeRow['feedback'] !== null && $gradeRow['feedback'] !== '' ? $gradeRow['feedback'] : '-'); ?></td>
                        <td><?php echo e(date('M j, Y', strtotime($gradeRow['graded_at']))); ?></td>
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
