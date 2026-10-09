<?php

function isValidReportDate(string $date): bool
{
    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);
    $errors = DateTime::getLastErrors();

    if (
        $dateObject === false
        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        || $dateObject->format('Y-m-d') !== $date
    ) {
        return false;
    }

    // Allow up to today in the local timezone (plus +1 day buffer for any client-server timezone difference)
    $maxAllowed = (new DateTime('today'))->modify('+1 day');
    return $dateObject <= $maxAllowed;
}

