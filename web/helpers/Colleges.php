<?php

class Colleges {
    public static function all() {
        return [
            'College of Agriculture',
            'College of Arts and Social Sciences',
            'College of Business and Accountancy',
            'College of Education',
            'College of Engineering',
            'College of Fisheries',
            'College of Home Science and Industry',
            'College of Science',
            'College of Veterinary Science and Medicine',
        ];
    }

    public static function contains($college) {
        return in_array((string) $college, self::all(), true);
    }

    public static function aliasesFor($college) {
        if (!self::contains($college)) return [(string) $college];
        return [$college, preg_replace('/^College of /', '', $college)];
    }

    public static function canonical($college) {
        $value = trim((string) $college);
        foreach (self::all() as $official) {
            foreach (self::aliasesFor($official) as $alias) {
                if (strcasecmp($value, $alias) === 0) return $official;
            }
        }
        return $value ?: 'Unspecified';
    }
}
