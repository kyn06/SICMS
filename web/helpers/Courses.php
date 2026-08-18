<?php

class Courses {
    public static function all() {
        return [
            'Bachelor of Science in Agribusiness (BSAb)',
            'Bachelor of Science in Agriculture (BSA)',
            'Bachelor of Arts in Filipino (BAFil)',
            'Bachelor of Arts in Literature (BALit)',
            'Bachelor of Arts in Social Sciences (BASS)',
            'Bachelor of Arts in International Studies (BAIS)',
            'Bachelor of Science in Development Communication (BSDC)',
            'Bachelor of Science in Psychology (BSPsych)',
            'Bachelor of Science in Accountancy (BSAc)',
            'Bachelor of Science in Business Administration (BSBA)',
            'Bachelor of Science in Entrepreneurship (BSEntrep)',
            'Bachelor of Science in Management Accounting (BSMA)',
            'Bachelor of Culture and Arts Education (BCAEd)',
            'Bachelor of Early Childhood Education (BECEd)',
            'Bachelor of Elementary Education (BEEd)',
            'Bachelor of Physical Education (BPEd)',
            'Bachelor of Secondary Education (BSEd)',
            'Bachelor of Technology and Livelihood Education (BTLEd)',
            'Bachelor of Science in Agricultural and Biosystems Engineering (BSABE)',
            'Bachelor of Science in Civil Engineering (BSCE)',
            'Bachelor of Science in Information Technology (BSIT)',
            'Bachelor of Science in Fisheries (BSF)',
            'Bachelor of Science in Food Technology (BSFT)',
            'Bachelor of Science in Hospitality Management (BSHM)',
            'Bachelor of Science in Tourism Management (BSTM)',
            'Bachelor of Science in Textile and Fashion Technology (BSTFT)',
            'Bachelor of Science in Biology (BSBio)',
            'Bachelor of Science in Chemistry (BSChem)',
            'Bachelor of Science in Environmental Science (BSES)',
            'Bachelor of Science in Mathematics (BSMath)',
            'Bachelor of Science in Meteorology (BSMet)',
            'Bachelor of Science in Statistics (BSStat)',
            'Doctor of Veterinary Medicine (DVM)',
        ];
    }

    public static function sections() {
        $groups = [];
        foreach ([1 => 'First Year', 2 => 'Second Year', 3 => 'Third Year', 4 => 'Fourth Year', 5 => 'Fifth Year'] as $year => $label) {
            $groups[$label] = array_map(fn($section) => $year . '-' . $section, range(1, 7));
        }
        return $groups;
    }

    public static function split($value) {
        $parts = array_map('trim', explode('|', (string) $value, 2));
        return [
            'course' => in_array($parts[0] ?? '', self::all(), true) ? $parts[0] : '',
            'section' => self::containsSection($parts[1] ?? '') ? $parts[1] : '',
        ];
    }

    public static function combine($course, $section) {
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
