<?php

class Courses {
    public static function byCollege() {
        return [
            'College of Agriculture' => [
                'BS Agribusiness',
                'BS Agriculture',
            ],
            'College of Arts and Social Sciences' => [
                'BA Filipino',
                'BA Literature',
                'BA Social Sciences',
                'BS Development Communication',
                'BS Psychology',
                'BA International Studies',
            ],
            'College of Business and Accountancy' => [
                'BS Accountancy',
                'BS Business Administration',
                'BS Entrepreneurship',
                'BS Management Accounting',
            ],
            'College of Education' => [
                'Bachelor of Culture and Arts Education',
                'Bachelor of Early Childhood Education',
                'Bachelor of Elementary Education',
                'Bachelor of Physical Education',
                'Bachelor of Secondary Education',
                'Bachelor of Technology and Livelihood Education',
            ],
            'College of Engineering' => [
                'BS Agricultural and Biosystems Engineering',
                'BS Civil Engineering',
                'BS Information Technology',
            ],
            'College of Fisheries' => [
                'BS Fisheries',
            ],
            'College of Home Science and Industry' => [
                'BS Food Technology',
                'BS Fashion and Textile Technology',
                'BS Hospitality Management',
                'BS Tourism Management',
            ],
            'College of Science' => [
                'BS Biology',
                'BS Chemistry',
                'BS Environmental Science',
                'BS Mathematics',
                'BS Meteorology',
                'BS Statistics',
            ],
            'College of Veterinary Science and Medicine' => [
                'Doctor of Veterinary Medicine',
            ],
        ];
    }

    public static function all() {
        return array_merge(...array_values(self::byCollege()));
    }

    public static function forCollege($college) {
        return self::byCollege()[(string) $college] ?? [];
    }

    public static function belongsToCollege($course, $college) {
        return in_array(self::canonical($course), self::forCollege($college), true);
    }

    public static function canonical($course) {
        $course = trim((string) $course);
        $aliases = [
            'Bachelor of Science in Agribusiness (BSAb)' => 'BS Agribusiness', 'Bachelor of Science in Agriculture (BSA)' => 'BS Agriculture',
            'Bachelor of Arts in Filipino (BAFil)' => 'BA Filipino', 'Bachelor of Arts in Literature (BALit)' => 'BA Literature',
            'Bachelor of Arts in Social Sciences (BASS)' => 'BA Social Sciences', 'Bachelor of Science in Development Communication (BSDC)' => 'BS Development Communication',
            'Bachelor of Science in Psychology (BSPsych)' => 'BS Psychology', 'Bachelor of Arts in International Studies (BAIS)' => 'BA International Studies',
            'Bachelor of Science in Accountancy (BSAc)' => 'BS Accountancy', 'Bachelor of Science in Business Administration (BSBA)' => 'BS Business Administration',
            'Bachelor of Science in Entrepreneurship (BSEntrep)' => 'BS Entrepreneurship', 'Bachelor of Science in Management Accounting (BSMA)' => 'BS Management Accounting',
            'Bachelor of Culture and Arts Education (BCAEd)' => 'Bachelor of Culture and Arts Education', 'Bachelor of Early Childhood Education (BECEd)' => 'Bachelor of Early Childhood Education',
            'Bachelor of Elementary Education (BEEd)' => 'Bachelor of Elementary Education', 'Bachelor of Physical Education (BPEd)' => 'Bachelor of Physical Education',
            'Bachelor of Secondary Education (BSEd)' => 'Bachelor of Secondary Education', 'Bachelor of Technology and Livelihood Education (BTLEd)' => 'Bachelor of Technology and Livelihood Education',
            'Bachelor of Science in Agricultural and Biosystems Engineering (BSABE)' => 'BS Agricultural and Biosystems Engineering',
            'Bachelor of Science in Civil Engineering (BSCE)' => 'BS Civil Engineering', 'Bachelor of Science in Information Technology (BSIT)' => 'BS Information Technology',
            'Bachelor of Science in Fisheries (BSF)' => 'BS Fisheries', 'Bachelor of Science in Food Technology (BSFT)' => 'BS Food Technology',
            'Bachelor of Science in Textile and Fashion Technology (BSTFT)' => 'BS Fashion and Textile Technology',
            'Bachelor of Science in Hospitality Management (BSHM)' => 'BS Hospitality Management', 'Bachelor of Science in Tourism Management (BSTM)' => 'BS Tourism Management',
            'Bachelor of Science in Biology (BSBio)' => 'BS Biology', 'Bachelor of Science in Chemistry (BSChem)' => 'BS Chemistry',
            'Bachelor of Science in Environmental Science (BSES)' => 'BS Environmental Science', 'Bachelor of Science in Mathematics (BSMath)' => 'BS Mathematics',
            'Bachelor of Science in Meteorology (BSMet)' => 'BS Meteorology', 'Bachelor of Science in Statistics (BSStat)' => 'BS Statistics',
            'Doctor of Veterinary Medicine (DVM)' => 'Doctor of Veterinary Medicine',
        ];
        return $aliases[$course] ?? $course;
    }

    public static function yearOptions() {
        return [
            1 => 'First Year',
            2 => 'Second Year',
            3 => 'Third Year',
            4 => 'Fourth Year',
            5 => 'Fifth Year',
            6 => 'Sixth Year',
        ];
    }

    public static function sections() {
        $groups = [];
        foreach (self::yearOptions() as $year => $label) {
            $groups[$label] = array_map(fn($section) => $year . '-' . $section, range(1, 7));
        }
        return $groups;
    }

    public static function yearLevel($section) {
        if (!self::containsSection($section)) return '';
        $labels = self::yearOptions();
        return $labels[(int) explode('-', (string) $section)[0]] ?? '';
    }

    public static function isValidSection($section) {
        return self::containsSection($section);
    }

    public static function split($value) {
        $parts = array_map('trim', explode('|', (string) $value, 2));
        return [
            'course' => in_array(self::canonical($parts[0] ?? ''), self::all(), true) ? self::canonical($parts[0] ?? '') : '',
            'section' => self::containsSection($parts[1] ?? '') ? $parts[1] : '',
        ];
    }

    public static function combine($course, $section) {
        $course = self::canonical($course);
        return in_array($course, self::all(), true) && self::containsSection($section)
            ? $course . ' | ' . $section
            : '';
    }

    private static function containsSection($section) {
        foreach (self::sections() as $sections) {
            if (in_array($section, $sections, true)) return true;
        }
        return false;
    }
}
