<?php

require_once __DIR__ . '/Colleges.php';
require_once __DIR__ . '/Courses.php';

class ProfileCompletion {
    public static function isStudentAccount(array $user) {
        return self::roleKey($user) === 'student';
    }

    public static function roleKey($user) {
        return strtolower(str_replace(['_', ' '], '-', (string) ($user['role'] ?? '')));
    }

    public static function requiredFields() {
        return [
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'phone_number' => 'Phone number',
            'gender' => 'Gender',
            'address' => 'Address',
            'student_number' => 'Student number',
            'college' => 'College',
            'course' => 'Course',
            'section' => 'Section',
            'birthday' => 'Birthday',
        ];
    }

    public static function missingFields(array $user) {
        $missing = [];

        foreach (self::requiredFields() as $field => $label) {
            if (trim((string) ($user[$field] ?? '')) === '') {
                $missing[] = $label;
            }
        }

        $college = trim((string) ($user['college'] ?? ''));
        $course = trim((string) ($user['course'] ?? ''));
        $section = trim((string) ($user['section'] ?? ''));

        if ($college !== '' && !Colleges::contains($college) && !in_array('College', $missing, true)) {
            $missing[] = 'College';
        }
        if ($course !== '' && !Courses::belongsToCollege($course, $college) && !in_array('Course', $missing, true)) {
            $missing[] = 'Course';
        }
        if ($section !== '' && !Courses::isValidSection($section) && !in_array('Section', $missing, true)) {
            $missing[] = 'Section';
        }

        if ((string) ($user['auth_provider'] ?? '') === 'google' && empty($user['password_hash'])) {
            $missing[] = 'Password';
        }

        return $missing;
    }

    public static function isComplete(array $user) {
        return count(self::missingFields($user)) === 0;
    }
}
