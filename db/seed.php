<?php

declare(strict_types=1);

namespace App\Database;

use App\Core\Database;
use PDO;

require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/helpers.php';

function seedDatabase(): void
{
    $pdo = Database::connection();

    echo "==> Seeding database...\n";

    // 1. Ensure default Admin account exists
    $adminEmail = 'admin@studyplanner.test';
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $adminEmail]);
    $adminId = $stmt->fetchColumn();

    if (!$adminId) {
        $insertAdmin = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, "admin")'
        );
        $insertAdmin->execute([
            'name' => 'Admin User',
            'email' => $adminEmail,
            'password_hash' => password_hash('AdminPassword123!', PASSWORD_DEFAULT),
        ]);
        $adminId = (int) $pdo->lastInsertId();
        echo "  [+] Created admin account: {$adminEmail} (AdminPassword123!)\n";
    } else {
        echo "  [.] Admin account exists: {$adminEmail}\n";
    }

    // 2. Ensure default Student account exists
    $studentEmail = 'user@studyplanner.test';
    $stmt->execute(['email' => $studentEmail]);
    $studentId = $stmt->fetchColumn();

    if (!$studentId) {
        $insertUser = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, "user")'
        );
        $insertUser->execute([
            'name' => 'Student User',
            'email' => $studentEmail,
            'password_hash' => password_hash('Student123!', PASSWORD_DEFAULT),
        ]);
        $studentId = (int) $pdo->lastInsertId();
        echo "  [+] Created student account: {$studentEmail} (Student123!)\n";
    } else {
        echo "  [.] Student account exists: {$studentEmail}\n";
    }

    // 3. Find target student accounts to seed demo academic data for
    // Seed for default student and for Ayush if present
    $targetUserIds = [(int) $studentId];
    $stmt->execute(['email' => 'panditayush708@gmail.com']);
    $ayushId = $stmt->fetchColumn();
    if ($ayushId) {
        $targetUserIds[] = (int) $ayushId;
    }

    $subjectsDefinition = [
        ['name' => 'Web Technologies', 'color' => '#1d5aab', 'description' => 'PHP MVC backend architecture, MySQL relational database, and frontend UI design.'],
        ['name' => 'Data Structures & Algorithms', 'color' => '#26745c', 'description' => 'Trees, Graphs, Dynamic Programming, sorting, and algorithmic complexity.'],
        ['name' => 'Database Management Systems', 'color' => '#7e3af2', 'description' => 'Relational algebra, SQL normalization, indexing, transaction processing, and ACID properties.'],
        ['name' => 'Operating Systems', 'color' => '#d97706', 'description' => 'Processes, concurrency, memory management, virtual memory paging, and file systems.'],
    ];

    foreach ($targetUserIds as $userId) {
        // Fetch user info for logging
        $userStmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = :id');
        $userStmt->execute(['id' => $userId]);
        $userRow = $userStmt->fetch();
        if (!$userRow) continue;

        echo "  --> Populating academic data for {$userRow['name']} ({$userRow['email']})...\n";

        $subjectMap = [];
        $checkSub = $pdo->prepare('SELECT id FROM subjects WHERE user_id = :user_id AND name = :name LIMIT 1');
        $createSub = $pdo->prepare('INSERT INTO subjects (user_id, name, color, description) VALUES (:user_id, :name, :color, :description)');

        foreach ($subjectsDefinition as $sub) {
            $checkSub->execute(['user_id' => $userId, 'name' => $sub['name']]);
            $subId = $checkSub->fetchColumn();
            if (!$subId) {
                $createSub->execute([
                    'user_id' => $userId,
                    'name' => $sub['name'],
                    'color' => $sub['color'],
                    'description' => $sub['description'],
                ]);
                $subId = (int) $pdo->lastInsertId();
            }
            $subjectMap[$sub['name']] = (int) $subId;
        }

        // Seed Tasks
        $tasksDefinition = [
            [
                'subject' => 'Web Technologies',
                'title' => 'Build PHP MVC backend and connect MySQL database',
                'description' => 'Set up database schema, migrations, connection singleton, and full CRUD routes for tasks, subjects, and exams.',
                'due_date' => date('Y-m-d', strtotime('+1 day')),
                'priority' => 'high',
                'status' => 'completed',
            ],
            [
                'subject' => 'Web Technologies',
                'title' => 'Implement CSRF token protection and secure password hashing',
                'description' => 'Verify session tokens on all POST endpoints and hash passwords with bcrypt.',
                'due_date' => date('Y-m-d', strtotime('-2 days')),
                'priority' => 'high',
                'status' => 'completed',
            ],
            [
                'subject' => 'Database Management Systems',
                'title' => 'Study B-Tree and B+ Tree indexing for query optimization',
                'description' => 'Read textbook chapters 14 and 15 on clustered vs non-clustered indexes and execution plans.',
                'due_date' => date('Y-m-d', strtotime('+3 days')),
                'priority' => 'high',
                'status' => 'in_progress',
            ],
            [
                'subject' => 'Data Structures & Algorithms',
                'title' => 'Solve 10 LeetCode graph traversal problems (BFS & DFS)',
                'description' => 'Focus on topological sort, cycle detection in directed graphs, and Dijkstra algorithm.',
                'due_date' => date('Y-m-d', strtotime('+5 days')),
                'priority' => 'medium',
                'status' => 'in_progress',
            ],
            [
                'subject' => 'Operating Systems',
                'title' => 'Review virtual memory paging and page replacement algorithms',
                'description' => 'Compare LRU, FIFO, and Optimal page replacement with numerical practice problems.',
                'due_date' => date('Y-m-d', strtotime('+7 days')),
                'priority' => 'medium',
                'status' => 'pending',
            ],
            [
                'subject' => 'Web Technologies',
                'title' => 'Prepare presentation slides for Web Technologies project review',
                'description' => 'Include architecture diagrams, MVC request lifecycle, and live feature walkthrough.',
                'due_date' => date('Y-m-d', strtotime('+4 days')),
                'priority' => 'high',
                'status' => 'pending',
            ],
        ];

        $checkTask = $pdo->prepare('SELECT id FROM tasks WHERE user_id = :user_id AND title = :title LIMIT 1');
        $createTask = $pdo->prepare(
            'INSERT INTO tasks (user_id, subject_id, title, description, due_date, priority, status)
             VALUES (:user_id, :subject_id, :title, :description, :due_date, :priority, :status)'
        );

        foreach ($tasksDefinition as $task) {
            $checkTask->execute(['user_id' => $userId, 'title' => $task['title']]);
            if (!$checkTask->fetchColumn()) {
                $createTask->execute([
                    'user_id' => $userId,
                    'subject_id' => $subjectMap[$task['subject']] ?? null,
                    'title' => $task['title'],
                    'description' => $task['description'],
                    'due_date' => $task['due_date'],
                    'priority' => $task['priority'],
                    'status' => $task['status'],
                ]);
            }
        }

        // Seed Exams
        $examsDefinition = [
            [
                'subject' => 'Web Technologies',
                'title' => 'Web Technologies Practical & Viva',
                'exam_at' => date('Y-m-d 10:00:00', strtotime('+5 days')),
                'status' => 'scheduled',
            ],
            [
                'subject' => 'Database Management Systems',
                'title' => 'DBMS Mid-Semester Examination',
                'exam_at' => date('Y-m-d 14:00:00', strtotime('+10 days')),
                'status' => 'scheduled',
            ],
            [
                'subject' => 'Operating Systems',
                'title' => 'Operating Systems Theory Exam',
                'exam_at' => date('Y-m-d 09:30:00', strtotime('+14 days')),
                'status' => 'scheduled',
            ],
            [
                'subject' => 'Data Structures & Algorithms',
                'title' => 'Data Structures Unit Assessment',
                'exam_at' => date('Y-m-d 11:00:00', strtotime('-10 days')),
                'status' => 'completed',
            ],
        ];

        $checkExam = $pdo->prepare('SELECT id FROM exams WHERE user_id = :user_id AND title = :title LIMIT 1');
        $createExam = $pdo->prepare(
            'INSERT INTO exams (user_id, subject_id, title, exam_at, status)
             VALUES (:user_id, :subject_id, :title, :exam_at, :status)'
        );

        foreach ($examsDefinition as $exam) {
            $checkExam->execute(['user_id' => $userId, 'title' => $exam['title']]);
            if (!$checkExam->fetchColumn()) {
                $createExam->execute([
                    'user_id' => $userId,
                    'subject_id' => $subjectMap[$exam['subject']] ?? null,
                    'title' => $exam['title'],
                    'exam_at' => $exam['exam_at'],
                    'status' => $exam['status'],
                ]);
            }
        }

        // Seed activity log
        $pdo->prepare(
            'INSERT INTO activity_logs (actor_user_id, actor_name, event, description)
             VALUES (:actor_user_id, :actor_name, "system.seed", "Initialized sample subjects, study tasks, and exam schedule.")'
        )->execute([
            'actor_user_id' => $userId,
            'actor_name' => $userRow['name'],
        ]);
    }

    echo "  [✓] Academic data seeded successfully.\n";
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    seedDatabase();
}
